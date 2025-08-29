<?php

namespace Modules\WMSInventoryCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\WMSInventoryCore\Models\Sale;
use Modules\WMSInventoryCore\Models\SaleProduct;
use Modules\WMSInventoryCore\Models\Product;
use Modules\WMSInventoryCore\Models\Warehouse;
use Modules\WMSInventoryCore\Models\Unit;
use Modules\CRMCore\app\Models\Customer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding sales demo data...');
            
            // Get first user for created_by fields
            $adminUser = User::first();
            
            if (!$adminUser) {
                $this->command->error('No users found in database. Please run user seeders first.');
                return;
            }
            
            $userId = $adminUser->id;
            
            // Check prerequisites
            if (!$this->checkPrerequisites()) {
                return;
            }
            
            // Seed Sales
            $this->seedSales($userId);
            
            $this->command->info('Sales demo data seeded successfully!');
        });
    }
    
    /**
     * Check if required data exists
     */
    private function checkPrerequisites(): bool
    {
        $customers = Customer::count();
        if ($customers === 0) {
            $this->command->warn('No customers found. Please run CRM customer seeders first.');
            return false;
        }
        
        $warehouses = Warehouse::count();
        if ($warehouses === 0) {
            $this->command->warn('No warehouses found. Please run warehouse seeders first.');
            return false;
        }
        
        $products = Product::count();
        if ($products === 0) {
            $this->command->warn('No products found. Please run product seeders first.');
            return false;
        }
        
        $units = Unit::count();
        if ($units === 0) {
            $this->command->warn('No units found. Please run unit seeders first.');
            return false;
        }
        
        return true;
    }
    
    /**
     * Seed sales demo data
     */
    private function seedSales($userId): void
    {
        $this->command->info('Creating demo sales...');
        
        // Get data collections
        $customers = Customer::active()->take(10)->get();
        $warehouses = Warehouse::where('status', 'active')->take(4)->get();
        $products = Product::where('status', 'active')->take(15)->get();
        $units = Unit::where('status', 'active')->take(5)->get();
        $salesUsers = User::take(3)->get();
        
        $saleCounter = 1;
        
        // Create 18 sales with various statuses
        for ($i = 0; $i < 18; $i++) {
            $customer = $customers->random();
            $warehouse = $warehouses->random();
            $salesPerson = $salesUsers->random();
            
            $status = $this->getRandomStatus($i);
            $paymentStatus = $this->getRandomPaymentStatus($status);
            $fulfillmentStatus = $this->getRandomFulfillmentStatus($status);
            
            $saleDate = Carbon::now()->subDays(rand(1, 90));
            $paymentDueDate = $saleDate->copy()->addDays($this->getPaymentTermDays($customer->payment_terms ?? 'net30'));
            
            $expectedDeliveryDate = $saleDate->copy()->addDays(rand(2, 14));
            $actualDeliveryDate = null;
            $fulfilledAt = null;
            
            if (in_array($status, ['shipped', 'delivered', 'completed'])) {
                $actualDeliveryDate = $expectedDeliveryDate->copy()->addDays(rand(-1, 3));
                $fulfilledAt = $saleDate->copy()->addDays(rand(1, 3));
            }
            
            // Calculate financial details
            $subtotal = 0;
            $taxRate = $customer->tax_exempt ? 0 : rand(5, 15); // 0-15% tax
            $discountRate = $customer->discount_percentage ?? rand(0, 10); // 0-10% discount
            $shippingCost = rand(10, 150) + (rand(0, 99) / 100);
            
            // Calculate subtotal first (we'll create products after)
            $numProducts = rand(2, 8);
            $productSubtotals = [];
            $totalCost = 0;
            
            for ($p = 0; $p < $numProducts; $p++) {
                $product = $products->random();
                $quantity = rand(1, 20);
                $unitPrice = $product->selling_price * (1 + (rand(-10, 20) / 100)); // ±20% price variation
                $unitCost = $product->cost_price;
                
                $productSubtotal = $quantity * $unitPrice;
                $productCost = $quantity * $unitCost;
                
                $productSubtotals[] = [
                    'subtotal' => $productSubtotal,
                    'cost' => $productCost,
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'unit_cost' => $unitCost
                ];
                
                $subtotal += $productSubtotal;
                $totalCost += $productCost;
            }
            
            $taxAmount = $subtotal * ($taxRate / 100);
            $discountAmount = $subtotal * ($discountRate / 100);
            $totalAmount = $subtotal + $taxAmount - $discountAmount + $shippingCost;
            $totalProfit = $totalAmount - $totalCost - $shippingCost;
            $profitMargin = $totalAmount > 0 ? ($totalProfit / $totalAmount) * 100 : 0;
            
            $paidAmount = 0;
            if ($paymentStatus === 'paid') {
                $paidAmount = $totalAmount;
            } elseif ($paymentStatus === 'partially_paid') {
                $paidAmount = $totalAmount * (rand(30, 80) / 100);
            }
            
            $sale = Sale::create([
                'date' => $saleDate,
                'code' => 'SO-' . date('Y', $saleDate->timestamp) . '-' . str_pad($saleCounter++, 5, '0', STR_PAD_LEFT),
                'reference_no' => 'REF-' . strtoupper(uniqid()),
                'invoice_no' => rand(0, 1) ? 'INV-' . rand(10000, 99999) : null,
                'order_no' => 'ORD-' . rand(100000, 999999),
                'customer_id' => $customer->id,
                'warehouse_id' => $warehouse->id,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'shipping_cost' => $shippingCost,
                'total_amount' => $totalAmount,
                'notes' => $this->getRandomNotes(),
                'status' => $status,
                'payment_status' => $paymentStatus,
                'payment_terms' => $customer->payment_terms ?? 'net30',
                'payment_due_date' => $paymentDueDate,
                'paid_amount' => $paidAmount,
                'shipping_address' => $this->getRandomShippingAddress($customer),
                'shipping_method' => $this->getRandomShippingMethod(),
                'tracking_number' => in_array($status, ['shipped', 'delivered']) ? 'TRK' . rand(100000000, 999999999) : null,
                'expected_delivery_date' => $expectedDeliveryDate,
                'actual_delivery_date' => $actualDeliveryDate,
                'fulfillment_status' => $fulfillmentStatus,
                'fulfilled_by_id' => $fulfilledAt ? $userId : null,
                'fulfilled_at' => $fulfilledAt,
                'sales_person_id' => $salesPerson->id,
                'profit_margin' => $profitMargin,
                'total_cost' => $totalCost,
                'total_profit' => $totalProfit,
                'created_by_id' => $userId,
                'updated_by_id' => $userId,
                'created_at' => $saleDate,
                'updated_at' => $saleDate,
            ]);
            
            // Create sale products
            foreach ($productSubtotals as $productData) {
                $product = $productData['product'];
                $unit = $units->random();
                $quantity = $productData['quantity'];
                $unitPrice = $productData['unit_price'];
                $unitCost = $productData['unit_cost'];
                
                $productTaxAmount = $unitPrice * $quantity * ($taxRate / 100);
                $productDiscountAmount = $unitPrice * $quantity * ($discountRate / 100);
                $productProfit = ($unitPrice - $unitCost) * $quantity - $productDiscountAmount;
                
                $fulfilledQuantity = 0;
                $isFullyFulfilled = false;
                $returnedQuantity = 0;
                
                if (in_array($fulfillmentStatus, ['fulfilled', 'shipped', 'delivered'])) {
                    $fulfilledQuantity = $quantity;
                    $isFullyFulfilled = true;
                } elseif ($fulfillmentStatus === 'partial') {
                    $fulfilledQuantity = rand(1, $quantity - 1);
                }
                
                // Small chance of returns for delivered items
                if ($status === 'delivered' && rand(1, 20) === 1) {
                    $returnedQuantity = rand(1, min(2, $fulfilledQuantity));
                }
                
                SaleProduct::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_id' => $unit->id,
                    'weight' => rand(1, 100) + (rand(0, 99) / 100),
                    'unit_price' => $unitPrice,
                    'unit_cost' => $unitCost,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $productTaxAmount,
                    'discount_rate' => $discountRate,
                    'discount_amount' => $productDiscountAmount,
                    'subtotal' => $productData['subtotal'],
                    'profit' => $productProfit,
                    'fulfilled_quantity' => $fulfilledQuantity,
                    'is_fully_fulfilled' => $isFullyFulfilled,
                    'returned_quantity' => $returnedQuantity,
                    'return_reason' => $returnedQuantity > 0 ? $this->getRandomReturnReason() : null,
                    'created_by_id' => $userId,
                    'updated_by_id' => $userId,
                    'created_at' => $saleDate,
                    'updated_at' => $saleDate,
                ]);
            }
        }
        
        $totalSales = Sale::count();
        $this->command->info('Created ' . $totalSales . ' demo sales with products.');
    }
    
    /**
     * Get random sale status with weighted distribution
     */
    private function getRandomStatus($index): string
    {
        // Ensure we have various statuses distributed
        $statuses = [
            'draft' => 2,
            'pending' => 2, 
            'approved' => 3,
            'fulfilled' => 4,
            'shipped' => 3,
            'delivered' => 3,
            'completed' => 1
        ];
        
        $weightedStatuses = [];
        foreach ($statuses as $status => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $weightedStatuses[] = $status;
            }
        }
        
        return $weightedStatuses[$index % count($weightedStatuses)];
    }
    
    /**
     * Get random payment status based on sale status
     */
    private function getRandomPaymentStatus($status): string
    {
        if (in_array($status, ['draft', 'pending'])) {
            return 'unpaid';
        }
        
        if (in_array($status, ['completed', 'delivered'])) {
            $statuses = ['paid', 'paid', 'paid', 'partially_paid']; // More likely to be paid
        } else {
            $statuses = ['unpaid', 'unpaid', 'partially_paid', 'paid'];
        }
        
        return $statuses[array_rand($statuses)];
    }
    
    /**
     * Get random fulfillment status based on sale status
     */
    private function getRandomFulfillmentStatus($status): string
    {
        switch ($status) {
            case 'draft':
            case 'pending':
                return 'pending';
            case 'approved':
                return rand(0, 1) ? 'pending' : 'processing';
            case 'fulfilled':
                return 'fulfilled';
            case 'shipped':
                return 'shipped';
            case 'delivered':
            case 'completed':
                return 'delivered';
            default:
                return 'pending';
        }
    }
    
    /**
     * Get payment term days
     */
    private function getPaymentTermDays($terms): int
    {
        switch ($terms) {
            case 'cod':
                return 0;
            case 'net15':
                return 15;
            case 'net30':
                return 30;
            case 'net45':
                return 45;
            case 'net60':
                return 60;
            default:
                return 30;
        }
    }
    
    /**
     * Get random shipping method
     */
    private function getRandomShippingMethod(): string
    {
        $methods = [
            'Standard Ground',
            'Express Delivery', 
            'Next Day Air',
            'Two Day Air',
            'Freight',
            'Local Pickup',
            'Same Day Delivery',
            'International Express'
        ];
        
        return $methods[array_rand($methods)];
    }
    
    /**
     * Get random shipping address
     */
    private function getRandomShippingAddress($customer): ?string
    {
        // Use customer's address or generate random
        if ($customer->contact && rand(0, 1)) {
            return $customer->contact->address_street . ', ' . 
                   $customer->contact->address_city . ', ' . 
                   $customer->contact->address_state . ' ' . 
                   $customer->contact->address_postal_code;
        }
        
        $addresses = [
            '123 Main Street, Anytown, CA 90210',
            '456 Oak Avenue, Springfield, IL 62701',
            '789 Pine Road, Portland, OR 97201',
            '321 Elm Street, Denver, CO 80202',
            '654 Maple Drive, Austin, TX 78701',
            '987 Cedar Lane, Seattle, WA 98101',
            '147 Birch Boulevard, Phoenix, AZ 85001',
            '258 Willow Way, Miami, FL 33101',
            '369 Spruce Street, Boston, MA 02101',
            '741 Ash Avenue, Chicago, IL 60601'
        ];
        
        return $addresses[array_rand($addresses)];
    }
    
    /**
     * Get random notes for sales order
     */
    private function getRandomNotes(): ?string
    {
        $notes = [
            'Customer requested expedited shipping',
            'Handle with care - fragile items',
            'Leave at front door if no answer',
            'Call customer before delivery',
            'VIP customer - priority handling',
            'Gift wrapping requested',
            'Bulk discount applied',
            'Customer pickup scheduled',
            'Special packaging required',
            'Corporate account - Net 30 terms',
            null,
            null, // Some orders without notes
        ];
        
        return $notes[array_rand($notes)];
    }
    
    /**
     * Get random return reason
     */
    private function getRandomReturnReason(): string
    {
        $reasons = [
            'Defective product',
            'Wrong item shipped',
            'Customer changed mind',
            'Size/color incorrect',
            'Damaged during shipping',
            'Not as described',
            'Arrived late',
            'Duplicate order'
        ];
        
        return $reasons[array_rand($reasons)];
    }
}