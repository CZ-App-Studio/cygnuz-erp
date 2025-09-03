<?php

namespace Modules\WMSInventoryCore\app\Http\Controllers;

use App\ApiClasses\Error;
use App\ApiClasses\Success;
use App\Helpers\FormattingHelper;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\CRMCore\app\Models\Customer;
use Modules\WMSInventoryCore\app\Services\WMSInventoryCoreSettingsService;
use Modules\WMSInventoryCore\Models\Inventory;
use Modules\WMSInventoryCore\Models\InventoryTransaction;
use Modules\WMSInventoryCore\Models\Product;
use Modules\WMSInventoryCore\Models\Sale;
use Modules\WMSInventoryCore\Models\SaleProduct;
use Modules\WMSInventoryCore\Models\Warehouse;

class SaleController extends Controller
{
    /**
     * Display a listing of sales.
     *
     * @return Renderable
     */
    public function index()
    {
        // $this->authorize('wmsinventory.view-sales');

        return view('wmsinventorycore::sales.index');
    }

    /**
     * Process ajax request for sales datatable.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDataAjax(Request $request)
    {
        // $this->authorize('wmsinventory.view-sales');
        $draw = $request->get('draw');
        $start = $request->get('start');
        $rowPerPage = $request->get('length');

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');

        $searchValue = $request->get('search')['value'] ?? '';
        $statusFilter = $request->get('status') ?? '';
        $customerFilter = $request->get('customer_id') ?? '';
        $warehouseFilter = $request->get('warehouse_id') ?? '';

        // Query builder with filters
        $query = Sale::with(['customer', 'warehouse', 'createdBy', 'fulfilledBy', 'salesPerson'])
            ->withCount(['products'])
            ->withSum('products', 'subtotal');

        if (! empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('code', 'like', '%'.$searchValue.'%')
                    ->orWhere('reference_no', 'like', '%'.$searchValue.'%')
                    ->orWhere('invoice_no', 'like', '%'.$searchValue.'%')
                    ->orWhere('order_no', 'like', '%'.$searchValue.'%')
                    ->orWhereHas('customer', function ($cq) use ($searchValue) {
                        $cq->where('name', 'like', '%'.$searchValue.'%');
                    });
            });
        }

        if (! empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        if (! empty($customerFilter)) {
            $query->where('customer_id', $customerFilter);
        }

        if (! empty($warehouseFilter)) {
            $query->where('warehouse_id', $warehouseFilter);
        }

        // Get total count
        $totalRecords = $query->count();

        // Handle ordering
        if (! empty($columnIndex_arr)) {
            $columnIndex = $columnIndex_arr[0]['column'];
            $columnName = $columnName_arr[$columnIndex]['data'];
            $columnSortOrder = $order_arr[0]['dir'];

            if ($columnName != 'actions') {
                $query->orderBy($columnName, $columnSortOrder);
            } else {
                $query->orderBy('id', 'desc');
            }
        } else {
            $query->orderBy('id', 'desc');
        }

        // Apply pagination
        $sales = $query->skip($start)
            ->take($rowPerPage)
            ->get();

        // Format data for DataTables
        $data = [];

        foreach ($sales as $sale) {
            $data[] = [
                'id' => $sale->id,
                'code' => $sale->code,
                'sale_date' => FormattingHelper::formatDate($sale->date),
                'customer' => $sale->customer ? $sale->customer->name : 'N/A',
                'warehouse' => $sale->warehouse ? $sale->warehouse->name : 'N/A',
                'expected_delivery_date' => $sale->expected_delivery_date ? FormattingHelper::formatDate($sale->expected_delivery_date) : '-',
                'total_amount' => FormattingHelper::formatCurrency($sale->total_amount),
                'status' => view('components.status-badge', [
                    'status' => $sale->status,
                    'type' => $this->getStatusBadgeType($sale->status),
                ])->render(),
                'payment_status' => view('components.status-badge', [
                    'status' => $sale->payment_status ?? 'unpaid',
                    'type' => $this->getPaymentBadgeType($sale->payment_status),
                ])->render(),
                'fulfillment_status' => view('components.status-badge', [
                    'status' => $sale->fulfillment_status ?? 'pending',
                    'type' => $this->getFulfillmentBadgeType($sale->fulfillment_status),
                ])->render(),
                'actions' => view('components.datatable-actions', [
                    'id' => $sale->id,
                    'actions' => $this->getActionButtons($sale),
                ])->render(),
            ];
        }

        return response()->json([
            'draw' => intval($draw),
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data,
        ]);
    }

    /**
     * Show the form for creating a new sale.
     *
     * @return Renderable
     */
    public function create()
    {
        // $this->authorize('wmsinventory.create-sale');

        $customers = Customer::active()->get();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('wmsinventorycore::sales.create', [
            'customers' => $customers,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Store a newly created sale in storage.
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // $this->authorize('wmsinventory.create-sale');

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'sale_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'order_no' => 'nullable|string|max:255',
            'expected_delivery_date' => 'nullable|date|after:sale_date',
            'payment_terms' => 'nullable|string|max:255',
            'payment_due_date' => 'nullable|date|after_or_equal:sale_date',
            'shipping_address' => 'nullable|string',
            'shipping_method' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
            'subtotal' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:draft,pending,approved,fulfilled,shipped,delivered,cancelled',
        ]);

        try {
            $sale = null;
            DB::transaction(function () use ($validated, &$sale) {
                // Generate sale code
                $code = $this->generateSaleCode();

                // Calculate totals
                $subtotal = $validated['subtotal'] ?? 0;
                $discountAmount = $validated['discount_amount'] ?? 0;
                $taxAmount = $validated['tax_amount'] ?? 0;
                $shippingCost = $validated['shipping_cost'] ?? 0;
                $totalAmount = $validated['total_amount'] ?? 0;
                $totalCost = 0;

                // If totals are not provided, calculate them
                if ($subtotal == 0) {
                    foreach ($validated['items'] as $item) {
                        $subtotal += $item['quantity'] * $item['unit_price'];
                    }
                }

                if ($discountAmount == 0 && isset($validated['discount_percentage'])) {
                    $discountAmount = $subtotal * ($validated['discount_percentage'] / 100);
                }

                $subtotalAfterDiscount = $subtotal - $discountAmount;

                if ($taxAmount == 0 && isset($validated['tax_percentage'])) {
                    $taxAmount = $subtotalAfterDiscount * ($validated['tax_percentage'] / 100);
                }

                if ($totalAmount == 0) {
                    $totalAmount = $subtotalAfterDiscount + $taxAmount + $shippingCost;
                }

                // Calculate total cost for profit calculation
                foreach ($validated['items'] as $item) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        $totalCost += $item['quantity'] * $product->cost_price;
                    }
                }

                $totalProfit = $subtotalAfterDiscount - $totalCost;
                $profitMargin = $subtotalAfterDiscount > 0 ? ($totalProfit / $subtotalAfterDiscount) * 100 : 0;

                // Create sale
                $sale = Sale::create([
                    'code' => $code,
                    'customer_id' => $validated['customer_id'],
                    'warehouse_id' => $validated['warehouse_id'],
                    'date' => $validated['sale_date'],
                    'reference_no' => $validated['reference'] ?? null,
                    'order_no' => $validated['order_no'] ?? null,
                    'invoice_no' => null,
                    'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                    'shipping_address' => $validated['shipping_address'] ?? null,
                    'shipping_method' => $validated['shipping_method'] ?? null,
                    'payment_terms' => $validated['payment_terms'] ?? null,
                    'payment_due_date' => $validated['payment_due_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'shipping_cost' => $shippingCost,
                    'total_amount' => $totalAmount,
                    'total_cost' => $totalCost,
                    'total_profit' => $totalProfit,
                    'profit_margin' => $profitMargin,
                    'status' => $validated['status'] ?? 'draft',
                    'payment_status' => 'unpaid',
                    'fulfillment_status' => 'pending',
                    'paid_amount' => 0,
                    'sales_person_id' => auth()->id(),
                    'created_by_id' => auth()->id(),
                    'updated_by_id' => auth()->id(),
                ]);

                // Add products to sale
                foreach ($validated['items'] as $productData) {
                    $product = Product::findOrFail($productData['product_id']);

                    $taxRate = 0;
                    $discountRate = 0;

                    $unitPrice = $productData['unit_price'];
                    $quantity = $productData['quantity'];
                    $lineSubtotal = $quantity * $unitPrice;

                    $discountAmount = $lineSubtotal * ($discountRate / 100);
                    $taxableAmount = $lineSubtotal - $discountAmount;
                    $taxAmount = $taxableAmount * ($taxRate / 100);
                    $finalSubtotal = $taxableAmount + $taxAmount;

                    $unitCost = $product->cost_price ?? 0;
                    $profit = $finalSubtotal - ($quantity * $unitCost);

                    SaleProduct::create([
                        'sale_id' => $sale->id,
                        'product_id' => $productData['product_id'],
                        'quantity' => $quantity,
                        'unit_id' => $product->unit_id,
                        'unit_price' => $unitPrice,
                        'unit_cost' => $unitCost,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $taxAmount,
                        'discount_rate' => $discountRate,
                        'discount_amount' => $discountAmount,
                        'subtotal' => $finalSubtotal,
                        'profit' => $profit,
                        'fulfilled_quantity' => 0,
                        'is_fully_fulfilled' => false,
                        'returned_quantity' => 0,
                        'created_by_id' => auth()->id(),
                        'updated_by_id' => auth()->id(),
                    ]);
                }
            });

            return redirect()->route('wmsinventorycore.sales.show', $sale->id)
                ->with('success', __('Sale order has been created successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to create sale order: '.$e->getMessage());

            return redirect()->back()
                ->with('error', __('Failed to create sale order'))
                ->withInput();
        }
    }

    /**
     * Display the specified sale.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function show($id)
    {
        // $this->authorize('wmsinventory.view-sales');

        $sale = Sale::with([
            'customer',
            'warehouse',
            'products.product.unit',
            'createdBy',
            'fulfilledBy',
            'salesPerson',
        ])->findOrFail($id);

        return view('wmsinventorycore::sales.show', [
            'sale' => $sale,
        ]);
    }

    /**
     * Show the form for editing the specified sale.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function edit($id)
    {
        // $this->authorize('wmsinventory.edit-sale');

        $sale = Sale::with(['products.product'])->findOrFail($id);

        // Only allow editing draft sales
        if ($sale->status !== 'draft') {
            return redirect()->route('wmsinventorycore.sales.show', $id)
                ->with('error', __('Only draft sale orders can be edited'));
        }

        $customers = Customer::active()->get();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('wmsinventorycore::sales.edit', [
            'sale' => $sale,
            'customers' => $customers,
            'warehouses' => $warehouses,
        ]);
    }

    /**
     * Update the specified sale in storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // $this->authorize('wmsinventory.edit-sale');

        $sale = Sale::findOrFail($id);

        // Only allow editing draft sales
        if ($sale->status !== 'draft') {
            return redirect()->route('wmsinventorycore.sales.show', $id)
                ->with('error', __('Only draft sale orders can be edited'));
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'sale_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'order_no' => 'nullable|string|max:255',
            'invoice_no' => 'nullable|string|max:255',
            'expected_delivery_date' => 'nullable|date|after:sale_date',
            'shipping_address' => 'nullable|string',
            'shipping_method' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string|max:255',
            'payment_due_date' => 'nullable|date|after_or_equal:sale_date',
            'notes' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
            'subtotal' => 'nullable|numeric|min:0',
            'tax_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'discount_amount' => 'nullable|numeric|min:0',
            'shipping_cost' => 'nullable|numeric|min:0',
            'total_amount' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::transaction(function () use ($sale, $validated) {
                // Calculate totals
                $subtotal = $validated['subtotal'] ?? 0;
                $discountAmount = $validated['discount_amount'] ?? 0;
                $taxAmount = $validated['tax_amount'] ?? 0;
                $shippingCost = $validated['shipping_cost'] ?? 0;
                $totalAmount = $validated['total_amount'] ?? 0;
                $totalCost = 0;

                // If totals are not provided, calculate them
                if ($subtotal == 0) {
                    foreach ($validated['items'] as $item) {
                        $subtotal += $item['quantity'] * $item['unit_price'];
                    }
                }

                if ($discountAmount == 0 && isset($validated['discount_percentage'])) {
                    $discountAmount = $subtotal * ($validated['discount_percentage'] / 100);
                }

                $subtotalAfterDiscount = $subtotal - $discountAmount;

                if ($taxAmount == 0 && isset($validated['tax_percentage'])) {
                    $taxAmount = $subtotalAfterDiscount * ($validated['tax_percentage'] / 100);
                }

                if ($totalAmount == 0) {
                    $totalAmount = $subtotalAfterDiscount + $taxAmount + $shippingCost;
                }

                // Calculate total cost for profit calculation
                foreach ($validated['items'] as $item) {
                    $product = Product::find($item['product_id']);
                    if ($product) {
                        $totalCost += $item['quantity'] * $product->cost_price;
                    }
                }

                $totalProfit = $subtotalAfterDiscount - $totalCost;
                $profitMargin = $subtotalAfterDiscount > 0 ? ($totalProfit / $subtotalAfterDiscount) * 100 : 0;

                // Update sale
                $sale->update([
                    'customer_id' => $validated['customer_id'],
                    'warehouse_id' => $validated['warehouse_id'],
                    'date' => $validated['sale_date'],
                    'reference_no' => $validated['reference'] ?? null,
                    'order_no' => $validated['order_no'] ?? null,
                    'invoice_no' => $validated['invoice_no'] ?? null,
                    'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                    'shipping_address' => $validated['shipping_address'] ?? null,
                    'shipping_method' => $validated['shipping_method'] ?? null,
                    'payment_terms' => $validated['payment_terms'] ?? null,
                    'payment_due_date' => $validated['payment_due_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'shipping_cost' => $shippingCost,
                    'total_amount' => $totalAmount,
                    'total_cost' => $totalCost,
                    'total_profit' => $totalProfit,
                    'profit_margin' => $profitMargin,
                    'updated_by_id' => auth()->id(),
                ]);

                // Remove existing products and add new ones
                $sale->products()->delete();

                foreach ($validated['items'] as $productData) {
                    $product = Product::findOrFail($productData['product_id']);

                    $taxRate = 0;
                    $discountRate = 0;

                    $unitPrice = $productData['unit_price'];
                    $quantity = $productData['quantity'];
                    $lineSubtotal = $quantity * $unitPrice;

                    $discountAmount = $lineSubtotal * ($discountRate / 100);
                    $taxableAmount = $lineSubtotal - $discountAmount;
                    $taxAmount = $taxableAmount * ($taxRate / 100);
                    $finalSubtotal = $taxableAmount + $taxAmount;

                    $unitCost = $product->cost_price ?? 0;
                    $profit = $finalSubtotal - ($quantity * $unitCost);

                    SaleProduct::create([
                        'sale_id' => $sale->id,
                        'product_id' => $productData['product_id'],
                        'quantity' => $quantity,
                        'unit_id' => $product->unit_id,
                        'unit_price' => $unitPrice,
                        'unit_cost' => $unitCost,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $taxAmount,
                        'discount_rate' => $discountRate,
                        'discount_amount' => $discountAmount,
                        'subtotal' => $finalSubtotal,
                        'profit' => $profit,
                        'fulfilled_quantity' => 0,
                        'is_fully_fulfilled' => false,
                        'returned_quantity' => 0,
                        'created_by_id' => auth()->id(),
                        'updated_by_id' => auth()->id(),
                    ]);
                }
            });

            return redirect()->route('wmsinventorycore.sales.show', $sale->id)
                ->with('success', __('Sale order has been updated successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update sale order: '.$e->getMessage());

            return redirect()->back()
                ->with('error', __('Failed to update sale order'))
                ->withInput();
        }
    }

    /**
     * Remove the specified sale from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // $this->authorize('wmsinventory.delete-sale');

        try {
            $sale = Sale::findOrFail($id);

            // Only allow deleting draft sales
            if ($sale->status !== 'draft') {
                return Error::response(__('Only draft sale orders can be deleted'));
            }

            DB::transaction(function () use ($sale) {
                // Delete products first
                $sale->products()->delete();
                // Soft delete sale
                $sale->delete();
            });

            return Success::response(__('Sale order has been deleted successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to delete sale order: '.$e->getMessage());

            return Error::response(__('Failed to delete sale order'));
        }
    }

    /**
     * Approve a sale order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve($id)
    {
        // $this->authorize('wmsinventory.approve-sale');

        try {
            $sale = Sale::findOrFail($id);

            if ($sale->status !== 'pending') {
                return Error::response(__('Sale order is not in pending status'));
            }

            DB::transaction(function () use ($sale) {
                $sale->update([
                    'status' => 'approved',
                    'updated_by_id' => auth()->id(),
                ]);
            });

            return Success::response([
                'message' => __('Sale order has been approved successfully'),
                'sale' => $sale->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to approve sale order: '.$e->getMessage());

            return Error::response(__('Failed to approve sale order'));
        }
    }

    /**
     * Reject a sale order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, $id)
    {
        // $this->authorize('wmsinventory.reject-sale');

        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        try {
            $sale = Sale::findOrFail($id);

            if ($sale->status !== 'pending') {
                return Error::response(__('Sale order is not in pending status'));
            }

            DB::transaction(function () use ($sale, $validated) {
                $sale->update([
                    'status' => 'rejected',
                    'notes' => ($sale->notes ? $sale->notes."\n\n" : '').
                               __('Rejection Reason: ').$validated['rejection_reason'],
                    'updated_by_id' => auth()->id(),
                ]);
            });

            return Success::response([
                'message' => __('Sale order has been rejected'),
                'sale' => $sale->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reject sale order: '.$e->getMessage());

            return Error::response(__('Failed to reject sale order'));
        }
    }

    /**
     * Fulfill a sale order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function fulfill(Request $request, $id)
    {
        // $this->authorize('wmsinventory.fulfill-sale');

        try {
            $sale = Sale::with('products.product')->findOrFail($id);

            if (! in_array($sale->status, ['approved', 'partially_fulfilled'])) {
                return Error::response(__('Only approved or partially fulfilled sale orders can be fulfilled'));
            }

            $items = $request->input('items', []);
            $hasFulfilledItems = false;

            DB::transaction(function () use ($sale, $items, &$hasFulfilledItems) {
                foreach ($items as $item) {
                    // Skip if not marked for fulfilling
                    if (! isset($item['fulfill']) || ! $item['fulfill']) {
                        continue;
                    }

                    $saleProduct = $sale->products()->find($item['item_id']);
                    if (! $saleProduct) {
                        continue;
                    }

                    $quantityToFulfill = floatval($item['quantity_fulfilled'] ?? 0);
                    if ($quantityToFulfill <= 0) {
                        continue;
                    }

                    // Calculate actual quantity that can be fulfilled
                    $remainingQuantity = $saleProduct->quantity - ($saleProduct->fulfilled_quantity ?? 0);
                    $actualQuantityFulfilled = min($quantityToFulfill, $remainingQuantity);

                    if ($actualQuantityFulfilled > 0) {
                        // Check inventory availability
                        $inventory = Inventory::where('product_id', $saleProduct->product_id)
                            ->where('warehouse_id', $sale->warehouse_id)
                            ->first();

                        if (! $inventory || $inventory->stock_level < $actualQuantityFulfilled) {
                            throw new \Exception(__('Insufficient inventory for product: ').$saleProduct->product->name);
                        }

                        $hasFulfilledItems = true;

                        // Update sale product
                        $newFulfilledQuantity = ($saleProduct->fulfilled_quantity ?? 0) + $actualQuantityFulfilled;

                        $saleProduct->update([
                            'fulfilled_quantity' => $newFulfilledQuantity,
                            'is_fully_fulfilled' => $newFulfilledQuantity >= $saleProduct->quantity,
                            'updated_by_id' => auth()->id(),
                        ]);

                        // Update inventory - reduce stock
                        $this->updateInventory(
                            $saleProduct->product_id,
                            $sale->warehouse_id,
                            -$actualQuantityFulfilled, // Negative for sale
                            $saleProduct->unit_cost,
                            'sale_fulfill',
                            "Fulfilled from SO: {$sale->code}",
                            $sale->id
                        );
                    }
                }

                if ($hasFulfilledItems) {
                    // Check if all items are fully fulfilled
                    $allFulfilled = $sale->products()
                        ->where('fulfilled_quantity', '<', DB::raw('quantity'))
                        ->count() === 0;

                    // Update sale status
                    $sale->update([
                        'status' => $allFulfilled ? 'fulfilled' : 'partially_fulfilled',
                        'fulfillment_status' => $allFulfilled ? 'fulfilled' : 'partially_fulfilled',
                        'fulfilled_by_id' => auth()->id(),
                        'fulfilled_at' => $sale->fulfilled_at ?? now(),
                        'updated_by_id' => auth()->id(),
                    ]);
                }
            });

            if (! $hasFulfilledItems) {
                return Error::response(__('No items were selected for fulfillment'));
            }

            return Success::response([
                'message' => __('Items have been fulfilled successfully'),
                'sale' => $sale->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to fulfill sale order: '.$e->getMessage());

            return Error::response(__('Failed to fulfill sale order: ').$e->getMessage());
        }
    }

    /**
     * Ship a sale order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function ship(Request $request, $id)
    {
        // $this->authorize('wmsinventory.ship-sale');

        $validated = $request->validate([
            'shipping_method' => 'nullable|string|max:255',
            'tracking_number' => 'nullable|string|max:255',
            'shipping_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $sale = Sale::findOrFail($id);

            if (! in_array($sale->status, ['fulfilled', 'partially_fulfilled'])) {
                return Error::response(__('Only fulfilled or partially fulfilled sale orders can be shipped'));
            }

            DB::transaction(function () use ($sale, $validated) {
                $sale->update([
                    'status' => 'shipped',
                    'fulfillment_status' => 'shipped',
                    'shipping_method' => $validated['shipping_method'] ?? $sale->shipping_method,
                    'tracking_number' => $validated['tracking_number'] ?? null,
                    'notes' => $sale->notes."\n\n".__('Shipping Notes: ').($validated['shipping_notes'] ?? ''),
                    'updated_by_id' => auth()->id(),
                ]);
            });

            return Success::response([
                'message' => __('Sale order has been marked as shipped'),
                'sale' => $sale->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to ship sale order: '.$e->getMessage());

            return Error::response(__('Failed to ship sale order'));
        }
    }

    /**
     * Deliver a sale order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function deliver(Request $request, $id)
    {
        // $this->authorize('wmsinventory.deliver-sale');

        $validated = $request->validate([
            'delivery_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $sale = Sale::findOrFail($id);

            if ($sale->status !== 'shipped') {
                return Error::response(__('Only shipped sale orders can be marked as delivered'));
            }

            DB::transaction(function () use ($sale, $validated) {
                $sale->update([
                    'status' => 'delivered',
                    'fulfillment_status' => 'delivered',
                    'actual_delivery_date' => now()->format('Y-m-d'),
                    'notes' => $sale->notes."\n\n".__('Delivery Notes: ').($validated['delivery_notes'] ?? ''),
                    'updated_by_id' => auth()->id(),
                ]);
            });

            return Success::response([
                'message' => __('Sale order has been marked as delivered'),
                'sale' => $sale->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to mark sale order as delivered: '.$e->getMessage());

            return Error::response(__('Failed to mark sale order as delivered'));
        }
    }

    /**
     * Generate invoice for a sale order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateInvoice($id)
    {
        // $this->authorize('wmsinventory.generate-invoice');

        try {
            $sale = Sale::findOrFail($id);

            if (! in_array($sale->status, ['fulfilled', 'shipped', 'delivered'])) {
                return Error::response(__('Only fulfilled, shipped or delivered sale orders can have invoices generated'));
            }

            DB::transaction(function () use ($sale) {
                if (! $sale->invoice_no) {
                    $invoiceNo = $this->generateInvoiceNumber();
                    $sale->update([
                        'invoice_no' => $invoiceNo,
                        'updated_by_id' => auth()->id(),
                    ]);
                }
            });

            return Success::response([
                'message' => __('Invoice has been generated successfully'),
                'invoice_no' => $sale->invoice_no,
                'sale' => $sale->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to generate invoice: '.$e->getMessage());

            return Error::response(__('Failed to generate invoice'));
        }
    }

    /**
     * Generate PDF for the sale order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function generatePDF($id)
    {
        // $this->authorize('wmsinventory.view-sales');

        try {
            $sale = Sale::with([
                'customer',
                'warehouse',
                'products.product.unit',
                'createdBy',
                'salesPerson',
            ])->findOrFail($id);

            $pdf = Pdf::loadView('wmsinventorycore::sales.pdf', [
                'sale' => $sale,
            ]);

            return $pdf->download("sale-order-{$sale->code}.pdf");
        } catch (\Exception $e) {
            Log::error('Failed to generate sale order PDF: '.$e->getMessage());

            return redirect()->back()->with('error', __('Failed to generate PDF'));
        }
    }

    /**
     * Duplicate a sale order as draft.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function duplicate($id)
    {
        // $this->authorize('wmsinventory.create-sale');

        try {
            $originalSale = Sale::with('products')->findOrFail($id);

            $newSale = null;
            DB::transaction(function () use ($originalSale, &$newSale) {
                // Create new sale
                $saleData = $originalSale->toArray();
                unset($saleData['id'], $saleData['code'], $saleData['created_at'], $saleData['updated_at']);

                $saleData['code'] = $this->generateSaleCode();
                $saleData['status'] = 'draft';
                $saleData['fulfillment_status'] = 'pending';
                $saleData['payment_status'] = 'unpaid';
                $saleData['invoice_no'] = null;
                $saleData['fulfilled_by_id'] = null;
                $saleData['fulfilled_at'] = null;
                $saleData['actual_delivery_date'] = null;
                $saleData['tracking_number'] = null;
                $saleData['paid_amount'] = 0;
                $saleData['date'] = now()->format('Y-m-d');
                $saleData['sales_person_id'] = auth()->id();
                $saleData['created_by_id'] = auth()->id();
                $saleData['updated_by_id'] = auth()->id();

                $newSale = Sale::create($saleData);

                // Copy products
                foreach ($originalSale->products as $originalProduct) {
                    $productData = $originalProduct->toArray();
                    unset($productData['id'], $productData['sale_id'], $productData['created_at'], $productData['updated_at']);

                    $productData['sale_id'] = $newSale->id;
                    $productData['fulfilled_quantity'] = 0;
                    $productData['is_fully_fulfilled'] = false;
                    $productData['returned_quantity'] = 0;
                    $productData['return_reason'] = null;
                    $productData['created_by_id'] = auth()->id();
                    $productData['updated_by_id'] = auth()->id();

                    SaleProduct::create($productData);
                }
            });

            return redirect()->route('wmsinventorycore.sales.edit', $newSale->id)
                ->with('success', __('Sale order has been duplicated successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to duplicate sale order: '.$e->getMessage());

            return redirect()->back()->with('error', __('Failed to duplicate sale order'));
        }
    }

    /**
     * Update inventory when fulfilling sale items.
     *
     * @param  int  $productId
     * @param  int  $warehouseId
     * @param  float  $quantity
     * @param  float  $unitCost
     * @param  string  $transactionType
     * @param  string  $description
     * @param  int|null  $referenceId
     * @return void
     */
    private function updateInventory($productId, $warehouseId, $quantity, $unitCost, $transactionType, $description, $referenceId = null)
    {
        // Get product to get unit_id
        $product = Product::find($productId);
        if (! $product) {
            return;
        }

        // Get inventory record
        $inventory = Inventory::where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();

        if (! $inventory) {
            throw new \Exception("Inventory record not found for product ID: {$productId} in warehouse ID: {$warehouseId}");
        }

        // Update stock level
        $oldStockLevel = $inventory->stock_level;
        $newStockLevel = $oldStockLevel + $quantity; // quantity will be negative for sales

        if ($newStockLevel < 0) {
            throw new \Exception("Insufficient stock. Available: {$oldStockLevel}, Required: ".abs($quantity));
        }

        // Check if negative stock is allowed
        if (! WMSInventoryCoreSettingsService::allowNegativeStock() && $newStockLevel < 0) {
            throw new \Exception("This sale would result in negative stock and is not allowed by system settings. Available: {$oldStockLevel}, Required: ".abs($quantity));
        }

        $inventory->update([
            'stock_level' => $newStockLevel,
        ]);

        // Create inventory transaction
        InventoryTransaction::create([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
            'transaction_type' => $transactionType,
            'quantity' => $quantity,
            'stock_before' => $oldStockLevel,
            'stock_after' => $newStockLevel,
            'unit_id' => $product->unit_id,
            'reference_id' => $referenceId,
            'reference_type' => $referenceId ? 'sale' : null,
            'notes' => $description,
            'created_by_id' => auth()->id(),
        ]);
    }

    /**
     * Generate unique sale order code.
     *
     * @return string
     */
    private function generateSaleCode()
    {
        $prefix = 'SO';
        $year = now()->format('Y');

        $lastSale = Sale::where('code', 'like', "{$prefix}-{$year}-%")
            ->orderBy('code', 'desc')
            ->first();

        if ($lastSale) {
            $lastNumber = (int) substr($lastSale->code, -6);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "{$prefix}-{$year}-".str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Generate unique invoice number.
     *
     * @return string
     */
    private function generateInvoiceNumber()
    {
        $prefix = 'INV';
        $year = now()->format('Y');

        $lastSale = Sale::where('invoice_no', 'like', "{$prefix}-{$year}-%")
            ->orderBy('invoice_no', 'desc')
            ->first();

        if ($lastSale) {
            $lastNumber = (int) substr($lastSale->invoice_no, -6);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "{$prefix}-{$year}-".str_pad($nextNumber, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get status badge type for DataTable.
     *
     * @param  string  $status
     * @return string
     */
    private function getStatusBadgeType($status)
    {
        return match ($status) {
            'draft' => 'secondary',
            'pending', 'approved' => 'info',
            'partially_fulfilled' => 'warning',
            'fulfilled' => 'primary',
            'shipped' => 'warning',
            'delivered' => 'success',
            'rejected', 'cancelled' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get payment badge type for DataTable.
     *
     * @param  string  $status
     * @return string
     */
    private function getPaymentBadgeType($status)
    {
        return match ($status) {
            'unpaid' => 'danger',
            'partial' => 'warning',
            'paid' => 'success',
            'overpaid' => 'info',
            default => 'secondary'
        };
    }

    /**
     * Get fulfillment badge type for DataTable.
     *
     * @param  string  $status
     * @return string
     */
    private function getFulfillmentBadgeType($status)
    {
        return match ($status) {
            'pending' => 'secondary',
            'partially_fulfilled' => 'warning',
            'fulfilled' => 'primary',
            'shipped' => 'info',
            'delivered' => 'success',
            default => 'secondary'
        };
    }

    /**
     * Get action buttons for DataTable based on sale status.
     *
     * @param  Sale  $sale
     * @return array
     */
    private function getActionButtons($sale)
    {
        $actions = [];

        // View action (always available)
        $actions[] = [
            'label' => __('View'),
            'icon' => 'bx bx-show',
            'onclick' => "window.location.href='".route('wmsinventorycore.sales.show', $sale->id)."'",
        ];

        // Edit action (only for draft)
        if ($sale->status === 'draft') {
            $actions[] = [
                'label' => __('Edit'),
                'icon' => 'bx bx-edit',
                'onclick' => "window.location.href='".route('wmsinventorycore.sales.edit', $sale->id)."'",
            ];
        }

        // Approve action (only for pending)
        if ($sale->status === 'pending') {
            $actions[] = [
                'label' => __('Approve'),
                'icon' => 'bx bx-check',
                'onclick' => "approveRecord({$sale->id})",
            ];
            $actions[] = [
                'label' => __('Reject'),
                'icon' => 'bx bx-x',
                'onclick' => "rejectRecord({$sale->id})",
            ];
        }

        // Fulfill action (for approved/partially fulfilled)
        if (in_array($sale->status, ['approved', 'partially_fulfilled'])) {
            $actions[] = [
                'label' => __('Fulfill'),
                'icon' => 'bx bx-package',
                'onclick' => "fulfillRecord({$sale->id})",
            ];
        }

        // Ship action (for fulfilled)
        if (in_array($sale->status, ['fulfilled', 'partially_fulfilled'])) {
            $actions[] = [
                'label' => __('Ship'),
                'icon' => 'bx bx-send',
                'onclick' => "shipRecord({$sale->id})",
            ];
        }

        // Deliver action (for shipped)
        if ($sale->status === 'shipped') {
            $actions[] = [
                'label' => __('Mark Delivered'),
                'icon' => 'bx bx-check-circle',
                'onclick' => "deliverRecord({$sale->id})",
            ];
        }

        // Generate Invoice action (for fulfilled/shipped/delivered)
        if (in_array($sale->status, ['fulfilled', 'shipped', 'delivered']) && ! $sale->invoice_no) {
            $actions[] = [
                'label' => __('Generate Invoice'),
                'icon' => 'bx bx-receipt',
                'onclick' => "generateInvoice({$sale->id})",
            ];
        }

        // PDF action (for approved/fulfilled/shipped/delivered orders)
        if (in_array($sale->status, ['approved', 'fulfilled', 'shipped', 'delivered'])) {
            $actions[] = [
                'label' => __('Download PDF'),
                'icon' => 'bx bx-download',
                'onclick' => "window.open('".route('wmsinventorycore.sales.pdf', $sale->id)."')",
            ];
        }

        // Duplicate action
        $actions[] = [
            'label' => __('Duplicate'),
            'icon' => 'bx bx-copy',
            'onclick' => "duplicateRecord({$sale->id})",
        ];

        // Delete action (only for draft)
        if ($sale->status === 'draft') {
            $actions[] = [
                'label' => __('Delete'),
                'icon' => 'bx bx-trash',
                'onclick' => "deleteRecord({$sale->id})",
            ];
        }

        return $actions;
    }

    /**
     * Show fulfill form for sale order.
     *
     * @param  int  $id
     * @return Renderable
     */
    public function showFulfill($id)
    {
        // $this->authorize('wmsinventory.fulfill-sale');

        $sale = Sale::with(['customer', 'warehouse', 'products.product', 'products.unit'])
            ->findOrFail($id);

        if (! in_array($sale->status, ['approved', 'partially_fulfilled'])) {
            return redirect()->route('wmsinventorycore.sales.show', $id)
                ->with('error', __('Only approved or partially fulfilled sale orders can be fulfilled'));
        }

        return view('wmsinventorycore::sales.fulfill', compact('sale'));
    }

    /**
     * Fulfill partial items in a sale order.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function fulfillPartial(Request $request, $id)
    {
        // $this->authorize('wmsinventory.fulfill-sale');

        $validated = $request->validate([
            'items' => 'required|array',
            'items.*.item_id' => 'required|exists:sale_products,id',
            'items.*.quantity_fulfilled' => 'required|numeric|min:0',
            'fulfillment_notes' => 'nullable|string|max:1000',
        ]);

        try {
            $sale = Sale::with('products.product')->findOrFail($id);

            if (! in_array($sale->status, ['approved', 'partially_fulfilled'])) {
                return Error::response(__('Only approved or partially fulfilled sale orders can be fulfilled'));
            }

            DB::transaction(function () use ($sale, $validated) {
                $allFullyFulfilled = true;

                foreach ($validated['items'] as $item) {
                    $saleProduct = $sale->products()->find($item['item_id']);
                    if (! $saleProduct) {
                        continue;
                    }

                    $quantityToFulfill = floatval($item['quantity_fulfilled']);
                    if ($quantityToFulfill <= 0) {
                        continue;
                    }

                    // Update fulfilled quantity
                    $newFulfilledQuantity = ($saleProduct->fulfilled_quantity ?? 0) + $quantityToFulfill;
                    $newFulfilledQuantity = min($newFulfilledQuantity, $saleProduct->quantity);

                    $saleProduct->update([
                        'fulfilled_quantity' => $newFulfilledQuantity,
                        'is_fully_fulfilled' => $newFulfilledQuantity >= $saleProduct->quantity,
                    ]);

                    // Update inventory
                    if ($saleProduct->product) {
                        $inventory = Inventory::where('product_id', $saleProduct->product_id)
                            ->where('warehouse_id', $sale->warehouse_id)
                            ->first();

                        if ($inventory && $inventory->quantity >= $quantityToFulfill) {
                            $inventory->decrement('quantity', $quantityToFulfill);
                            $inventory->increment('reserved_quantity', $quantityToFulfill);

                            // Log inventory transaction
                            InventoryTransaction::create([
                                'product_id' => $saleProduct->product_id,
                                'warehouse_id' => $sale->warehouse_id,
                                'type' => 'sale_fulfillment',
                                'quantity' => -$quantityToFulfill,
                                'reference_type' => 'sale',
                                'reference_id' => $sale->id,
                                'description' => 'Partial fulfillment for sale order '.$sale->code,
                                'created_by_id' => auth()->id(),
                            ]);
                        }
                    }

                    if (! $saleProduct->is_fully_fulfilled) {
                        $allFullyFulfilled = false;
                    }
                }

                // Update sale status
                $sale->update([
                    'status' => $allFullyFulfilled ? 'fulfilled' : 'partially_fulfilled',
                    'fulfillment_status' => $allFullyFulfilled ? 'fulfilled' : 'partially_fulfilled',
                    'fulfilled_by_id' => auth()->id(),
                    'fulfilled_at' => now(),
                    'notes' => $sale->notes."\n\n".__('Fulfillment Notes: ').($validated['fulfillment_notes'] ?? ''),
                    'updated_by_id' => auth()->id(),
                ]);
            });

            return Success::response([
                'message' => __('Items have been partially fulfilled successfully'),
                'sale' => $sale->fresh(),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to partially fulfill sale order: '.$e->getMessage());

            return Error::response(__('Failed to partially fulfill sale order'));
        }
    }

    /**
     * Search customers for AJAX requests.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchCustomers(Request $request)
    {
        // $this->authorize('wmsinventory.search-customers');

        $search = $request->get('search', '');
        $page = $request->get('page', 1);
        $perPage = 10;

        $query = Customer::with(['contact.company']);

        if ($search) {
            $query->whereHas('contact', function ($q) use ($search) {
                $q->where(function ($subQ) use ($search) {
                    $subQ->where('first_name', 'like', '%'.$search.'%')
                        ->orWhere('last_name', 'like', '%'.$search.'%')
                        ->orWhere('email_primary', 'like', '%'.$search.'%')
                        ->orWhere('phone_primary', 'like', '%'.$search.'%')
                        ->orWhereHas('company', function ($companyQ) use ($search) {
                            $companyQ->where('name', 'like', '%'.$search.'%');
                        });
                });
            });
        }

        $customers = $query->paginate($perPage, ['*'], 'page', $page);

        $results = [];
        foreach ($customers as $customer) {
            $contact = $customer->contact;
            $name = '';
            $email = '';
            $phone = '';
            $companyName = '';

            if ($contact) {
                $name = trim(($contact->first_name ?? '').' '.($contact->last_name ?? ''));
                $email = $contact->email_primary ?? '';
                $phone = $contact->phone_primary ?? '';

                if ($contact->company) {
                    $companyName = $contact->company->name ?? '';
                }
            }

            // Build display text
            $displayText = $name ?: 'Customer #'.$customer->code;
            if ($companyName) {
                $displayText .= ' ('.$companyName.')';
            }

            $results[] = [
                'id' => $customer->id,
                'text' => $displayText,
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'company' => $companyName,
            ];
        }

        return response()->json([
            'results' => $results,
            'pagination' => [
                'more' => $customers->hasMorePages(),
            ],
        ]);
    }

    /**
     * Get customer products for AJAX requests.
     *
     * @param  int  $customerId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getCustomerProducts(Request $request, $customerId)
    {
        // This method can be used to get customer-specific products/pricing
        // For now, it returns all products available in the selected warehouse

        $warehouseId = $request->get('warehouse_id');

        $products = Product::with(['unit', 'category'])
            ->when($warehouseId, function ($query) use ($warehouseId) {
                $query->whereHas('inventories', function ($q) use ($warehouseId) {
                    $q->where('warehouse_id', $warehouseId)
                        ->where('quantity', '>', 0);
                });
            })
            ->get()
            ->map(function ($product) use ($warehouseId) {
                $inventory = $product->inventories()
                    ->where('warehouse_id', $warehouseId)
                    ->first();

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'unit' => $product->unit?->name,
                    'unit_id' => $product->unit_id,
                    'price' => $product->selling_price,
                    'cost' => $product->cost,
                    'stock' => $inventory?->quantity ?? 0,
                ];
            });

        return response()->json($products);
    }
}
