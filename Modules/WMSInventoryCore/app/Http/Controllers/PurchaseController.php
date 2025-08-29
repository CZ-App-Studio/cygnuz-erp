<?php

namespace Modules\WMSInventoryCore\app\Http\Controllers;

use Illuminate\Contracts\Support\Renderable;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\ApiClasses\Success;
use App\ApiClasses\Error;
use App\Helpers\FormattingHelper;
use Modules\WMSInventoryCore\Models\Purchase;
use Modules\WMSInventoryCore\Models\PurchaseProduct;
use Modules\WMSInventoryCore\Models\Vendor;
use Modules\WMSInventoryCore\Models\Warehouse;
use Modules\WMSInventoryCore\Models\Product;
use Modules\WMSInventoryCore\Models\Inventory;
use Modules\WMSInventoryCore\Models\InventoryTransaction;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class PurchaseController extends Controller
{
    /**
     * Display a listing of purchase orders.
     *
     * @return Renderable
     */
    public function index()
    {
        // $this->authorize('wmsinventory.view-purchases');
        
        return view('wmsinventorycore::purchases.index');
    }

    /**
     * Process ajax request for purchases datatable.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDataAjax(Request $request)
    {
        // $this->authorize('wmsinventory.view-purchases');
        $draw = $request->get('draw');
        $start = $request->get("start");
        $rowPerPage = $request->get("length");

        $columnIndex_arr = $request->get('order');
        $columnName_arr = $request->get('columns');
        $order_arr = $request->get('order');

        $searchValue = $request->get('search')['value'] ?? '';
        $statusFilter = $request->get('status') ?? '';
        $vendorFilter = $request->get('vendor_id') ?? '';
        $warehouseFilter = $request->get('warehouse_id') ?? '';

        // Query builder with filters
        $query = Purchase::with(['vendor', 'warehouse', 'createdBy', 'approvedBy', 'receivedBy'])
            ->withCount(['products'])
            ->withSum('products', 'subtotal');

        if (!empty($searchValue)) {
            $query->where(function ($q) use ($searchValue) {
                $q->where('code', 'like', '%' . $searchValue . '%')
                    ->orWhere('reference_no', 'like', '%' . $searchValue . '%')
                    ->orWhere('invoice_no', 'like', '%' . $searchValue . '%')
                    ->orWhereHas('vendor', function ($vq) use ($searchValue) {
                        $vq->where('name', 'like', '%' . $searchValue . '%');
                    });
            });
        }

        if (!empty($statusFilter)) {
            $query->where('status', $statusFilter);
        }

        if (!empty($vendorFilter)) {
            $query->where('vendor_id', $vendorFilter);
        }

        if (!empty($warehouseFilter)) {
            $query->where('warehouse_id', $warehouseFilter);
        }

        // Get total count
        $totalRecords = $query->count();

        // Handle ordering
        if (!empty($columnIndex_arr)) {
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
        $purchases = $query->skip($start)
            ->take($rowPerPage)
            ->get();

        // Format data for DataTables
        $data = [];

        foreach ($purchases as $purchase) {
            $data[] = [
                'id' => $purchase->id,
                'code' => $purchase->code,
                'po_date' => FormattingHelper::formatDate($purchase->date),
                'vendor' => $purchase->vendor ? $purchase->vendor->name : 'N/A',
                'warehouse' => $purchase->warehouse ? $purchase->warehouse->name : 'N/A',
                'expected_delivery_date' => $purchase->expected_delivery_date ? FormattingHelper::formatDate($purchase->expected_delivery_date) : '-',
                'total_amount' => FormattingHelper::formatCurrency($purchase->total_amount),
                'status' => view('components.status-badge', [
                    'status' => $purchase->status,
                    'type' => $this->getStatusBadgeType($purchase->status)
                ])->render(),
                'payment_status' => view('components.status-badge', [
                    'status' => $purchase->payment_status ?? 'unpaid',
                    'type' => $this->getPaymentBadgeType($purchase->payment_status)
                ])->render(),
                'actions' => view('components.datatable-actions', [
                    'id' => $purchase->id,
                    'actions' => $this->getActionButtons($purchase)
                ])->render()
            ];
        }

        return response()->json([
            "draw" => intval($draw),
            "recordsTotal" => $totalRecords,
            "recordsFiltered" => $totalRecords,
            "data" => $data
        ]);
    }

    /**
     * Show the form for creating a new purchase order.
     *
     * @return Renderable
     */
    public function create()
    {
        // $this->authorize('wmsinventory.create-purchase');
        
        $vendors = Vendor::active()->get();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('wmsinventorycore::purchases.create', [
            'vendors' => $vendors,
            'warehouses' => $warehouses
        ]);
    }

    /**
     * Store a newly created purchase order in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        // $this->authorize('wmsinventory.create-purchase');
        
        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'po_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'expected_delivery_date' => 'nullable|date|after:po_date',
            'payment_terms' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
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
            'status' => 'nullable|in:draft,pending,approved,ordered,received,cancelled',
        ]);

        try {
            $purchase = null;
            DB::transaction(function () use ($validated, $request, &$purchase) {
                // Generate purchase code
                $code = $this->generatePurchaseCode();

                // Calculate totals
                $subtotal = $validated['subtotal'] ?? 0;
                $discountAmount = $validated['discount_amount'] ?? 0;
                $taxAmount = $validated['tax_amount'] ?? 0;
                $shippingCost = $validated['shipping_cost'] ?? 0;
                $totalAmount = $validated['total_amount'] ?? 0;
                
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

                // Create purchase order
                $purchase = Purchase::create([
                    'code' => $code,
                    'vendor_id' => $validated['vendor_id'],
                    'warehouse_id' => $validated['warehouse_id'],
                    'date' => $validated['po_date'],
                    'reference_no' => $validated['reference'] ?? null,
                    'invoice_no' => null,
                    'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                    'shipping_method' => null,
                    'payment_terms' => $validated['payment_terms'] ?? null,
                    'payment_due_date' => null,
                    'notes' => $validated['notes'] ?? null,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'shipping_cost' => $shippingCost,
                    'total_amount' => $totalAmount,
                    'status' => $validated['status'] ?? 'draft',
                    'approval_status' => 'pending',
                    'payment_status' => 'unpaid',
                    'paid_amount' => 0,
                    'created_by_id' => auth()->id(),
                    'updated_by_id' => auth()->id(),
                ]);

                // Add products to purchase order
                foreach ($validated['items'] as $productData) {
                    $product = Product::findOrFail($productData['product_id']);
                    
                    $taxRate = 0;
                    $discountRate = 0;
                    
                    $unitCost = $productData['unit_price'];
                    $quantity = $productData['quantity'];
                    $lineSubtotal = $quantity * $unitCost;
                    
                    $discountAmount = $lineSubtotal * ($discountRate / 100);
                    $taxableAmount = $lineSubtotal - $discountAmount;
                    $taxAmount = $taxableAmount * ($taxRate / 100);
                    $finalSubtotal = $taxableAmount + $taxAmount;

                    PurchaseProduct::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $productData['product_id'],
                        'quantity' => $quantity,
                        'unit_id' => $product->unit_id,
                        'unit_cost' => $unitCost,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $taxAmount,
                        'discount_rate' => $discountRate,
                        'discount_amount' => $discountAmount,
                        'subtotal' => $finalSubtotal,
                        'batch_number' => null,
                        'expiry_date' => null,
                        'received_quantity' => 0,
                        'is_fully_received' => false,
                        'accepted_quantity' => 0,
                        'rejected_quantity' => 0,
                        'created_by_id' => auth()->id(),
                        'updated_by_id' => auth()->id(),
                    ]);
                }
            });

            return redirect()->route('wmsinventorycore.purchases.show', $purchase->id)
                ->with('success', __('Purchase order has been created successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to create purchase order: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', __('Failed to create purchase order'))
                ->withInput();
        }
    }

    /**
     * Display the specified purchase order.
     *
     * @param int $id
     * @return Renderable
     */
    public function show($id)
    {
        // $this->authorize('wmsinventory.view-purchases');
        
        $purchase = Purchase::with([
            'vendor', 
            'warehouse', 
            'products.product.unit', 
            'createdBy', 
            'approvedBy', 
            'receivedBy'
        ])->findOrFail($id);

        return view('wmsinventorycore::purchases.show', [
            'purchase' => $purchase
        ]);
    }

    /**
     * Show the form for editing the specified purchase order.
     *
     * @param int $id
     * @return Renderable
     */
    public function edit($id)
    {
        // $this->authorize('wmsinventory.edit-purchase');
        
        $purchase = Purchase::with(['products.product'])->findOrFail($id);

        // Only allow editing draft purchases
        if ($purchase->status !== 'draft') {
            return redirect()->route('wmsinventorycore.purchases.show', $id)
                ->with('error', __('Only draft purchase orders can be edited'));
        }

        $vendors = Vendor::active()->get();
        $warehouses = Warehouse::where('is_active', true)->get();

        return view('wmsinventorycore::purchases.edit', [
            'purchase' => $purchase,
            'vendors' => $vendors,
            'warehouses' => $warehouses
        ]);
    }

    /**
     * Update the specified purchase order in storage.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(Request $request, $id)
    {
        // $this->authorize('wmsinventory.edit-purchase');
        
        $purchase = Purchase::findOrFail($id);

        // Only allow editing draft purchases
        if ($purchase->status !== 'draft') {
            return redirect()->route('wmsinventorycore.purchases.show', $id)
                ->with('error', __('Only draft purchase orders can be edited'));
        }

        $validated = $request->validate([
            'vendor_id' => 'required|exists:vendors,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'po_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'invoice_no' => 'nullable|string|max:255',
            'expected_delivery_date' => 'nullable|date|after:po_date',
            'shipping_method' => 'nullable|string|max:255',
            'payment_terms' => 'nullable|string|max:255',
            'payment_due_date' => 'nullable|date|after_or_equal:po_date',
            'notes' => 'nullable|string',
            'terms_conditions' => 'nullable|string',
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
            DB::transaction(function () use ($purchase, $validated, $request) {
                // Calculate totals
                $subtotal = $validated['subtotal'] ?? 0;
                $discountAmount = $validated['discount_amount'] ?? 0;
                $taxAmount = $validated['tax_amount'] ?? 0;
                $shippingCost = $validated['shipping_cost'] ?? 0;
                $totalAmount = $validated['total_amount'] ?? 0;
                
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

                // Update purchase order
                $purchase->update([
                    'vendor_id' => $validated['vendor_id'],
                    'warehouse_id' => $validated['warehouse_id'],
                    'date' => $validated['po_date'],
                    'reference_no' => $validated['reference'] ?? null,
                    'invoice_no' => $validated['invoice_no'] ?? null,
                    'expected_delivery_date' => $validated['expected_delivery_date'] ?? null,
                    'shipping_method' => $validated['shipping_method'] ?? null,
                    'payment_terms' => $validated['payment_terms'] ?? null,
                    'payment_due_date' => $validated['payment_due_date'] ?? null,
                    'notes' => $validated['notes'] ?? null,
                    'terms_conditions' => $validated['terms_conditions'] ?? null,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'shipping_cost' => $shippingCost,
                    'total_amount' => $totalAmount,
                    'updated_by_id' => auth()->id(),
                ]);

                // Remove existing products and add new ones
                $purchase->products()->delete();

                foreach ($validated['items'] as $productData) {
                    $product = Product::findOrFail($productData['product_id']);
                    
                    $taxRate = 0;
                    $discountRate = 0;
                    
                    $unitCost = $productData['unit_price'];
                    $quantity = $productData['quantity'];
                    $lineSubtotal = $quantity * $unitCost;
                    
                    $discountAmount = $lineSubtotal * ($discountRate / 100);
                    $taxableAmount = $lineSubtotal - $discountAmount;
                    $taxAmount = $taxableAmount * ($taxRate / 100);
                    $finalSubtotal = $taxableAmount + $taxAmount;

                    PurchaseProduct::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $productData['product_id'],
                        'quantity' => $quantity,
                        'unit_id' => $product->unit_id,
                        'unit_cost' => $unitCost,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $taxAmount,
                        'discount_rate' => $discountRate,
                        'discount_amount' => $discountAmount,
                        'subtotal' => $finalSubtotal,
                        'notes' => $productData['notes'] ?? null,
                        'batch_number' => null,
                        'expiry_date' => null,
                        'received_quantity' => 0,
                        'is_fully_received' => false,
                        'accepted_quantity' => 0,
                        'rejected_quantity' => 0,
                        'created_by_id' => auth()->id(),
                        'updated_by_id' => auth()->id(),
                    ]);
                }
            });

            return redirect()->route('wmsinventorycore.purchases.show', $purchase->id)
                ->with('success', __('Purchase order has been updated successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to update purchase order: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', __('Failed to update purchase order'))
                ->withInput();
        }
    }

    /**
     * Remove the specified purchase order from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        // $this->authorize('wmsinventory.delete-purchase');
        
        try {
            $purchase = Purchase::findOrFail($id);

            // Only allow deleting draft purchases
            if ($purchase->status !== 'draft') {
                return Error::response(__('Only draft purchase orders can be deleted'));
            }

            DB::transaction(function () use ($purchase) {
                // Delete products first
                $purchase->products()->delete();
                // Soft delete purchase
                $purchase->delete();
            });

            return Success::response(__('Purchase order has been deleted successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to delete purchase order: ' . $e->getMessage());
            return Error::response(__('Failed to delete purchase order'));
        }
    }

    /**
     * Approve a purchase order.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function approve($id)
    {
        // $this->authorize('wmsinventory.approve-purchase');
        
        try {
            $purchase = Purchase::findOrFail($id);

            if ($purchase->approval_status !== 'pending') {
                return Error::response(__('Purchase order is not in pending approval status'));
            }

            DB::transaction(function () use ($purchase) {
                $purchase->update([
                    'approval_status' => 'approved',
                    'status' => 'approved',
                    'approved_by_id' => auth()->id(),
                    'approved_at' => now(),
                    'updated_by_id' => auth()->id(),
                ]);
            });

            return Success::response([
                'message' => __('Purchase order has been approved successfully'),
                'purchase' => $purchase->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to approve purchase order: ' . $e->getMessage());
            return Error::response(__('Failed to approve purchase order'));
        }
    }

    /**
     * Reject a purchase order.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function reject(Request $request, $id)
    {
        // $this->authorize('wmsinventory.reject-purchase');
        
        $validated = $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ]);

        try {
            $purchase = Purchase::findOrFail($id);

            if ($purchase->approval_status !== 'pending') {
                return Error::response(__('Purchase order is not in pending approval status'));
            }

            DB::transaction(function () use ($purchase, $validated) {
                $purchase->update([
                    'approval_status' => 'rejected',
                    'status' => 'rejected',
                    'notes' => ($purchase->notes ? $purchase->notes . "\n\n" : '') . 
                               __('Rejection Reason: ') . $validated['rejection_reason'],
                    'updated_by_id' => auth()->id(),
                ]);
            });

            return Success::response([
                'message' => __('Purchase order has been rejected'),
                'purchase' => $purchase->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to reject purchase order: ' . $e->getMessage());
            return Error::response(__('Failed to reject purchase order'));
        }
    }

    /**
     * Show the receive form for a purchase order.
     *
     * @param int $id
     * @return \Illuminate\View\View
     */
    public function showReceive($id)
    {
        // $this->authorize('wmsinventory.receive-purchase');
        
        $purchase = Purchase::with(['products.product', 'vendor', 'warehouse'])->findOrFail($id);

        if (!in_array($purchase->status, ['approved', 'partially_received'])) {
            return redirect()->route('wmsinventorycore.purchases.show', $id)
                ->with('error', __('Only approved or partially received purchase orders can be received'));
        }

        return view('wmsinventorycore::purchases.receive', [
            'purchase' => $purchase
        ]);
    }

    /**
     * Receive all items in the purchase order.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function receive(Request $request, $id)
    {
        // $this->authorize('wmsinventory.receive-purchase');
        
        try {
            $purchase = Purchase::with('products.product')->findOrFail($id);

            if (!in_array($purchase->status, ['approved', 'partially_received'])) {
                return Error::response(__('Only approved or partially received purchase orders can be received'));
            }

            $items = $request->input('items', []);
            $hasReceivedItems = false;

            DB::transaction(function () use ($purchase, $items, &$hasReceivedItems) {
                foreach ($items as $item) {
                    // Skip if not marked for receiving
                    if (!isset($item['receive']) || !$item['receive']) {
                        continue;
                    }

                    $purchaseProduct = $purchase->products()->find($item['item_id']);
                    if (!$purchaseProduct) {
                        continue;
                    }

                    $quantityToReceive = floatval($item['quantity_received'] ?? 0);
                    if ($quantityToReceive <= 0) {
                        continue;
                    }

                    // Calculate actual quantity that can be received
                    $remainingQuantity = $purchaseProduct->quantity - ($purchaseProduct->received_quantity ?? 0);
                    $actualQuantityReceived = min($quantityToReceive, $remainingQuantity);

                    if ($actualQuantityReceived > 0) {
                        $hasReceivedItems = true;
                        
                        // Update purchase product
                        $newReceivedQuantity = ($purchaseProduct->received_quantity ?? 0) + $actualQuantityReceived;
                        $status = $item['status'] ?? 'accepted';
                        
                        $purchaseProduct->update([
                            'received_quantity' => $newReceivedQuantity,
                            'accepted_quantity' => $status === 'accepted' ? 
                                ($purchaseProduct->accepted_quantity ?? 0) + $actualQuantityReceived : 
                                ($purchaseProduct->accepted_quantity ?? 0),
                            'rejected_quantity' => $status === 'rejected' ? 
                                ($purchaseProduct->rejected_quantity ?? 0) + $actualQuantityReceived : 
                                ($purchaseProduct->rejected_quantity ?? 0),
                            'is_fully_received' => $newReceivedQuantity >= $purchaseProduct->quantity,
                            'notes' => $item['notes'] ?? $purchaseProduct->notes,
                            'updated_by_id' => auth()->id(),
                        ]);

                        // Update inventory only for accepted items
                        if ($status === 'accepted') {
                            $this->updateInventory(
                                $purchaseProduct->product_id,
                                $purchase->warehouse_id,
                                $actualQuantityReceived,
                                $purchaseProduct->unit_cost,
                                'purchase_receive',
                                "Received from PO: {$purchase->code}",
                                $purchase->id
                            );
                        }
                    }
                }

                if ($hasReceivedItems) {
                    // Check if all items are fully received
                    $allReceived = $purchase->products()
                        ->where('received_quantity', '<', DB::raw('quantity'))
                        ->count() === 0;

                    // Update purchase status
                    $purchase->update([
                        'status' => $allReceived ? 'received' : 'partially_received',
                        'received_by_id' => auth()->id(),
                        'received_at' => $purchase->received_at ?? now(),
                        'actual_delivery_date' => now()->format('Y-m-d'),
                        'updated_by_id' => auth()->id(),
                    ]);
                }
            });

            if (!$hasReceivedItems) {
                return Error::response(__('No items were selected for receiving'));
            }

            return Success::response([
                'message' => __('Items have been received successfully'),
                'purchase' => $purchase->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to receive purchase order: ' . $e->getMessage());
            return Error::response(__('Failed to receive purchase order'));
        }
    }

    /**
     * Partially receive items in the purchase order.
     *
     * @param Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function receivePartial(Request $request, $id)
    {
        // $this->authorize('wmsinventory.receive-purchase');
        
        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.purchase_product_id' => 'required|exists:purchase_products,id',
            'items.*.received_quantity' => 'required|numeric|min:0.01',
            'items.*.accepted_quantity' => 'nullable|numeric|min:0',
            'items.*.rejected_quantity' => 'nullable|numeric|min:0',
            'items.*.rejection_reason' => 'nullable|string|max:500',
        ]);

        try {
            $purchase = Purchase::with('products')->findOrFail($id);

            if ($purchase->status !== 'approved') {
                return Error::response(__('Only approved purchase orders can be received'));
            }

            DB::transaction(function () use ($purchase, $validated) {
                foreach ($validated['items'] as $itemData) {
                    $purchaseProduct = $purchase->products()->findOrFail($itemData['purchase_product_id']);
                    $receivedQty = $itemData['received_quantity'];
                    $acceptedQty = $itemData['accepted_quantity'] ?? $receivedQty;
                    $rejectedQty = $itemData['rejected_quantity'] ?? 0;

                    // Validate quantities
                    if ($acceptedQty + $rejectedQty != $receivedQty) {
                        throw new \Exception(__('Accepted and rejected quantities must equal received quantity'));
                    }

                    $newReceivedTotal = $purchaseProduct->received_quantity + $receivedQty;
                    if ($newReceivedTotal > $purchaseProduct->quantity) {
                        throw new \Exception(__('Cannot receive more than ordered quantity'));
                    }

                    // Update purchase product
                    $purchaseProduct->update([
                        'received_quantity' => $newReceivedTotal,
                        'accepted_quantity' => $purchaseProduct->accepted_quantity + $acceptedQty,
                        'rejected_quantity' => $purchaseProduct->rejected_quantity + $rejectedQty,
                        'is_fully_received' => $newReceivedTotal >= $purchaseProduct->quantity,
                        'rejection_reason' => $rejectedQty > 0 ? $itemData['rejection_reason'] : null,
                        'updated_by_id' => auth()->id(),
                    ]);

                    // Update inventory only for accepted items
                    if ($acceptedQty > 0) {
                        $this->updateInventory(
                            $purchaseProduct->product_id,
                            $purchase->warehouse_id,
                            $acceptedQty,
                            $purchaseProduct->unit_cost,
                            'purchase_receive',
                            "Partial receive from PO: {$purchase->code}"
                        );
                    }
                }

                // Check if all items are fully received
                $allReceived = $purchase->products()->where('is_fully_received', false)->count() === 0;
                
                $purchase->update([
                    'status' => $allReceived ? 'received' : 'partially_received',
                    'received_by_id' => auth()->id(),
                    'received_at' => $allReceived ? now() : ($purchase->received_at ?? now()),
                    'actual_delivery_date' => $allReceived ? now()->format('Y-m-d') : $purchase->actual_delivery_date,
                    'updated_by_id' => auth()->id(),
                ]);
            });

            return Success::response([
                'message' => __('Items have been received successfully'),
                'purchase' => $purchase->fresh()
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to partially receive purchase order: ' . $e->getMessage());
            return Error::response(__('Failed to receive items: ') . $e->getMessage());
        }
    }

    /**
     * Generate PDF for the purchase order.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function generatePDF($id)
    {
        // $this->authorize('wmsinventory.view-purchases');
        
        try {
            $purchase = Purchase::with([
                'vendor', 
                'warehouse', 
                'products.product.unit',
                'createdBy'
            ])->findOrFail($id);

            $pdf = Pdf::loadView('wmsinventorycore::purchases.pdf', [
                'purchase' => $purchase
            ]);

            return $pdf->download("purchase-order-{$purchase->code}.pdf");
        } catch (\Exception $e) {
            Log::error('Failed to generate purchase order PDF: ' . $e->getMessage());
            return redirect()->back()->with('error', __('Failed to generate PDF'));
        }
    }

    /**
     * Duplicate a purchase order as draft.
     *
     * @param int $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function duplicate($id)
    {
        // $this->authorize('wmsinventory.create-purchase');
        
        try {
            $originalPurchase = Purchase::with('products')->findOrFail($id);
            
            $newPurchase = null;
            DB::transaction(function () use ($originalPurchase, &$newPurchase) {
                // Create new purchase
                $purchaseData = $originalPurchase->toArray();
                unset($purchaseData['id'], $purchaseData['code'], $purchaseData['created_at'], $purchaseData['updated_at']);
                
                $purchaseData['code'] = $this->generatePurchaseCode();
                $purchaseData['status'] = 'draft';
                $purchaseData['approval_status'] = 'pending';
                $purchaseData['approved_by_id'] = null;
                $purchaseData['approved_at'] = null;
                $purchaseData['received_by_id'] = null;
                $purchaseData['received_at'] = null;
                $purchaseData['actual_delivery_date'] = null;
                $purchaseData['date'] = now()->format('Y-m-d');
                $purchaseData['created_by_id'] = auth()->id();
                $purchaseData['updated_by_id'] = auth()->id();

                $newPurchase = Purchase::create($purchaseData);

                // Copy products
                foreach ($originalPurchase->products as $originalProduct) {
                    $productData = $originalProduct->toArray();
                    unset($productData['id'], $productData['purchase_id'], $productData['created_at'], $productData['updated_at']);
                    
                    $productData['purchase_id'] = $newPurchase->id;
                    $productData['received_quantity'] = 0;
                    $productData['accepted_quantity'] = 0;
                    $productData['rejected_quantity'] = 0;
                    $productData['is_fully_received'] = false;
                    $productData['rejection_reason'] = null;
                    $productData['created_by_id'] = auth()->id();
                    $productData['updated_by_id'] = auth()->id();

                    PurchaseProduct::create($productData);
                }
            });

            return redirect()->route('wmsinventorycore.purchases.edit', $newPurchase->id)
                ->with('success', __('Purchase order has been duplicated successfully'));
        } catch (\Exception $e) {
            Log::error('Failed to duplicate purchase order: ' . $e->getMessage());
            return redirect()->back()->with('error', __('Failed to duplicate purchase order'));
        }
    }

    /**
     * Get products by vendor for AJAX requests.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getVendorProducts(Request $request)
    {
        try {
            $vendorId = $request->get('vendor_id');
            $search = $request->get('search', '');
            $limit = $request->get('limit', 50);

            if (!$vendorId) {
                return response()->json([]);
            }

            $query = Product::where('is_purchasable', true)
                ->where('status', 'active');

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('code', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%");
                });
            }

            $products = $query->with('unit')
                ->limit($limit)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'text' => "{$product->name} ({$product->code})",
                        'name' => $product->name,
                        'code' => $product->code,
                        'sku' => $product->sku,
                        'unit' => $product->unit?->name,
                        'unit_id' => $product->unit_id,
                        'cost_price' => $product->cost_price,
                        'track_batch' => $product->track_batch,
                        'track_expiry' => $product->track_expiry,
                    ];
                });

            return response()->json($products);
        } catch (\Exception $e) {
            Log::error('Failed to get vendor products: ' . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Update inventory when receiving purchase items.
     *
     * @param int $productId
     * @param int $warehouseId
     * @param float $quantity
     * @param float $unitCost
     * @param string $transactionType
     * @param string $description
     * @return void
     */
    private function updateInventory($productId, $warehouseId, $quantity, $unitCost, $transactionType, $description, $referenceId = null)
    {
        // Get product to get unit_id
        $product = Product::find($productId);
        if (!$product) {
            return;
        }

        // Get or create inventory record
        $inventory = Inventory::firstOrCreate([
            'product_id' => $productId,
            'warehouse_id' => $warehouseId,
        ], [
            'stock_level' => 0,
            'reserved_quantity' => 0,
            'created_by_id' => auth()->id(),
        ]);

        // Update stock level
        $oldStockLevel = $inventory->stock_level;
        $newStockLevel = $oldStockLevel + $quantity;
        
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
            'reference_type' => $referenceId ? 'purchase' : null,
            'notes' => $description,
            'created_by_id' => auth()->id(),
        ]);

        // Update product cost price with weighted average
        $totalCurrentValue = $oldStockLevel * $product->cost_price;
        $newValue = $quantity * $unitCost;
        $totalValue = $totalCurrentValue + $newValue;
        $newCostPrice = $newStockLevel > 0 ? $totalValue / $newStockLevel : $unitCost;

        $product->update([
            'cost_price' => $newCostPrice,
        ]);
    }

    /**
     * Generate unique purchase order code.
     *
     * @return string
     */
    private function generatePurchaseCode()
    {
        $prefix = 'PO';
        $date = now()->format('Ymd');
        
        $lastPurchase = Purchase::where('code', 'like', "{$prefix}-{$date}-%")
            ->orderBy('code', 'desc')
            ->first();

        if ($lastPurchase) {
            $lastNumber = (int) substr($lastPurchase->code, -4);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return "{$prefix}-{$date}-" . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Get status badge type for DataTable.
     *
     * @param string $status
     * @return string
     */
    private function getStatusBadgeType($status)
    {
        return match($status) {
            'draft' => 'secondary',
            'pending_approval', 'approved' => 'info',
            'partially_received' => 'warning',
            'received' => 'success',
            'rejected', 'cancelled' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get approval badge type for DataTable.
     *
     * @param string $status
     * @return string
     */
    private function getApprovalBadgeType($status)
    {
        return match($status) {
            'pending' => 'warning',
            'approved' => 'success',
            'rejected' => 'danger',
            default => 'secondary'
        };
    }

    /**
     * Get payment badge type for DataTable.
     *
     * @param string $status
     * @return string
     */
    private function getPaymentBadgeType($status)
    {
        return match($status) {
            'unpaid' => 'danger',
            'partial' => 'warning',
            'paid' => 'success',
            'overpaid' => 'info',
            default => 'secondary'
        };
    }

    /**
     * Get action buttons for DataTable based on purchase status.
     *
     * @param Purchase $purchase
     * @return array
     */
    private function getActionButtons($purchase)
    {
        $actions = [];

        // View action (always available)
        $actions[] = [
            'label' => __('View'), 
            'icon' => 'bx bx-show', 
            'onclick' => "window.location.href='" . route('wmsinventorycore.purchases.show', $purchase->id) . "'"
        ];

        // Edit action (only for draft)
        if ($purchase->status === 'draft') {
            $actions[] = [
                'label' => __('Edit'), 
                'icon' => 'bx bx-edit', 
                'onclick' => "window.location.href='" . route('wmsinventorycore.purchases.edit', $purchase->id) . "'"
            ];
        }

        // Approve action (only for pending approval)
        if ($purchase->approval_status === 'pending') {
            $actions[] = [
                'label' => __('Approve'), 
                'icon' => 'bx bx-check', 
                'onclick' => "approveRecord({$purchase->id})"
            ];
            $actions[] = [
                'label' => __('Reject'), 
                'icon' => 'bx bx-x', 
                'onclick' => "rejectRecord({$purchase->id})"
            ];
        }

        // Removed receive actions - these are now only available from the view page

        // PDF action (for approved/received orders)
        if (in_array($purchase->status, ['approved', 'partially_received', 'received'])) {
            $actions[] = [
                'label' => __('Download PDF'), 
                'icon' => 'bx bx-download', 
                'onclick' => "window.open('" . route('wmsinventorycore.purchases.pdf', $purchase->id) . "')"
            ];
        }

        // Duplicate action
        $actions[] = [
            'label' => __('Duplicate'), 
            'icon' => 'bx bx-copy', 
            'onclick' => "duplicateRecord({$purchase->id})"
        ];

        // Delete action (only for draft)
        if ($purchase->status === 'draft') {
            $actions[] = [
                'label' => __('Delete'), 
                'icon' => 'bx bx-trash', 
                'onclick' => "deleteRecord({$purchase->id})"
            ];
        }

        return $actions;
    }
}