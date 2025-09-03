<?php

namespace Modules\WMSInventoryCore\Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\WMSInventoryCore\Models\Product;
use Modules\WMSInventoryCore\Models\Purchase;
use Modules\WMSInventoryCore\Models\PurchaseProduct;
use Modules\WMSInventoryCore\Models\Unit;
use Modules\WMSInventoryCore\Models\Vendor;
use Modules\WMSInventoryCore\Models\Warehouse;

class WMSInventoryCoreDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->command->info('Seeding WMSInventoryCore demo data...');

            // Get first user for created_by fields
            $adminUser = User::first();

            if (! $adminUser) {
                $this->command->error('No users found in database. Please run user seeders first.');

                return;
            }

            $userId = $adminUser->id;

            // Seed Vendors
            $this->seedVendors($userId);

            // Seed Purchases
            $this->seedPurchases($userId);

            $this->command->info('WMSInventoryCore demo data seeded successfully!');
        });
    }

    /**
     * Seed vendor demo data
     */
    private function seedVendors($userId): void
    {
        $this->command->info('Creating demo vendors...');

        $vendors = [
            [
                'name' => 'TechSupply Pro',
                'company_name' => 'TechSupply Pro International Ltd.',
                'email' => 'contact@techsupplypro.com',
                'phone_number' => '+1-555-0100',
                'address' => '123 Tech Boulevard',
                'city' => 'San Francisco',
                'state' => 'CA',
                'country' => 'USA',
                'postal_code' => '94105',
                'tax_number' => 'TSP-123456789',
                'website' => 'https://techsupplypro.com',
                'status' => 'active',
                'payment_terms' => 'Net 30',
                'lead_time_days' => 7,
                'minimum_order_value' => 500.00,
                'notes' => 'Premium technology equipment supplier. Reliable delivery and competitive prices.',
                'created_by_id' => $userId,
                'updated_by_id' => $userId,
            ],
            [
                'name' => 'Office Essentials Direct',
                'company_name' => 'Office Essentials Direct Corp.',
                'email' => 'orders@officeessentials.com',
                'phone_number' => '+1-555-0101',
                'address' => '456 Commerce Street',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'USA',
                'postal_code' => '10013',
                'tax_number' => 'OED-987654321',
                'website' => 'https://officeessentials.com',
                'status' => 'active',
                'payment_terms' => 'Net 15',
                'lead_time_days' => 3,
                'minimum_order_value' => 250.00,
                'notes' => 'Fast delivery for office supplies. Volume discounts available.',
                'created_by_id' => $userId,
                'updated_by_id' => $userId,
            ],
            [
                'name' => 'Global Industrial Supplies',
                'company_name' => 'Global Industrial Supplies Inc.',
                'email' => 'sales@globalindustrial.com',
                'phone_number' => '+1-555-0102',
                'address' => '789 Industrial Park',
                'city' => 'Chicago',
                'state' => 'IL',
                'country' => 'USA',
                'postal_code' => '60601',
                'tax_number' => 'GIS-456789123',
                'website' => 'https://globalindustrial.com',
                'status' => 'active',
                'payment_terms' => 'Net 45',
                'lead_time_days' => 14,
                'minimum_order_value' => 1000.00,
                'notes' => 'Heavy machinery and industrial equipment. Bulk orders only.',
                'created_by_id' => $userId,
                'updated_by_id' => $userId,
            ],
            [
                'name' => 'Medical Supplies Plus',
                'company_name' => 'Medical Supplies Plus LLC',
                'email' => 'orders@medsupplyplus.com',
                'phone_number' => '+1-555-0103',
                'address' => '321 Healthcare Avenue',
                'city' => 'Boston',
                'state' => 'MA',
                'country' => 'USA',
                'postal_code' => '02108',
                'tax_number' => 'MSP-789123456',
                'website' => 'https://medsupplyplus.com',
                'status' => 'active',
                'payment_terms' => 'Net 30',
                'lead_time_days' => 5,
                'minimum_order_value' => 300.00,
                'notes' => 'Certified medical equipment supplier. FDA approved products.',
                'created_by_id' => $userId,
                'updated_by_id' => $userId,
            ],
            [
                'name' => 'Food Service Distributors',
                'company_name' => 'Food Service Distributors Co.',
                'email' => 'orders@foodservicedist.com',
                'phone_number' => '+1-555-0104',
                'address' => '654 Culinary Way',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'country' => 'USA',
                'postal_code' => '90001',
                'tax_number' => 'FSD-321654987',
                'website' => 'https://foodservicedist.com',
                'status' => 'active',
                'payment_terms' => 'COD',
                'lead_time_days' => 2,
                'minimum_order_value' => 150.00,
                'notes' => 'Fresh and frozen food products. Daily delivery available.',
                'created_by_id' => $userId,
                'updated_by_id' => $userId,
            ],
            [
                'name' => 'Electronics Wholesale Hub',
                'company_name' => 'Electronics Wholesale Hub Ltd.',
                'email' => 'bulk@electronicshub.com',
                'phone_number' => '+1-555-0105',
                'address' => '987 Circuit Road',
                'city' => 'Austin',
                'state' => 'TX',
                'country' => 'USA',
                'postal_code' => '78701',
                'tax_number' => 'EWH-654321789',
                'website' => 'https://electronicshub.com',
                'status' => 'active',
                'payment_terms' => 'Net 60',
                'lead_time_days' => 21,
                'minimum_order_value' => 2000.00,
                'notes' => 'Wholesale electronics and components. International shipping available.',
                'created_by_id' => $userId,
                'updated_by_id' => $userId,
            ],
            [
                'name' => 'Building Materials Co',
                'company_name' => 'Building Materials Company',
                'email' => 'orders@buildingmaterials.com',
                'phone_number' => '+1-555-0106',
                'address' => '111 Construction Plaza',
                'city' => 'Dallas',
                'state' => 'TX',
                'country' => 'USA',
                'postal_code' => '75201',
                'tax_number' => 'BMC-147258369',
                'website' => 'https://buildingmaterials.com',
                'status' => 'active',
                'payment_terms' => 'Net 30',
                'lead_time_days' => 10,
                'minimum_order_value' => 750.00,
                'notes' => 'Construction materials and tools. Contractor discounts available.',
                'created_by_id' => $userId,
                'updated_by_id' => $userId,
            ],
            [
                'name' => 'Packaging Solutions Ltd',
                'company_name' => 'Packaging Solutions Limited',
                'email' => 'sales@packagingsolutions.com',
                'phone_number' => '+1-555-0107',
                'address' => '222 Packaging Lane',
                'city' => 'Seattle',
                'state' => 'WA',
                'country' => 'USA',
                'postal_code' => '98101',
                'tax_number' => 'PSL-963852741',
                'website' => 'https://packagingsolutions.com',
                'status' => 'active',
                'payment_terms' => 'Net 15',
                'lead_time_days' => 4,
                'minimum_order_value' => 200.00,
                'notes' => 'Eco-friendly packaging materials. Custom printing available.',
                'created_by_id' => $userId,
                'updated_by_id' => $userId,
            ],
            [
                'name' => 'Automotive Parts Direct',
                'company_name' => 'Automotive Parts Direct Inc.',
                'email' => 'parts@autopartsdirect.com',
                'phone_number' => '+1-555-0108',
                'address' => '333 Motor Avenue',
                'city' => 'Detroit',
                'state' => 'MI',
                'country' => 'USA',
                'postal_code' => '48201',
                'tax_number' => 'APD-852963741',
                'website' => 'https://autopartsdirect.com',
                'status' => 'active',
                'payment_terms' => 'Net 30',
                'lead_time_days' => 6,
                'minimum_order_value' => 400.00,
                'notes' => 'OEM and aftermarket auto parts. Express shipping available.',
                'created_by_id' => $userId,
                'updated_by_id' => $userId,
            ],
            [
                'name' => 'Fashion Textiles International',
                'company_name' => 'Fashion Textiles International Corp.',
                'email' => 'orders@fashiontextiles.com',
                'phone_number' => '+1-555-0109',
                'address' => '444 Fashion District',
                'city' => 'Miami',
                'state' => 'FL',
                'country' => 'USA',
                'postal_code' => '33101',
                'tax_number' => 'FTI-159753468',
                'website' => 'https://fashiontextiles.com',
                'status' => 'inactive',
                'payment_terms' => 'Net 45',
                'lead_time_days' => 30,
                'minimum_order_value' => 1500.00,
                'notes' => 'Premium fabrics and textiles. Currently on hold - credit review pending.',
                'created_by_id' => $userId,
                'updated_by_id' => $userId,
            ],
        ];

        foreach ($vendors as $vendorData) {
            Vendor::firstOrCreate(
                ['email' => $vendorData['email']],
                $vendorData
            );
        }

        $this->command->info('Created '.count($vendors).' demo vendors.');
    }

    /**
     * Seed purchase orders demo data
     */
    private function seedPurchases($userId): void
    {
        $this->command->info('Creating demo purchase orders...');

        // Get vendors
        $vendors = Vendor::where('status', 'active')->take(5)->get();

        if ($vendors->isEmpty()) {
            $this->command->warn('No vendors found. Skipping purchase orders.');

            return;
        }

        // Get warehouses
        $warehouses = Warehouse::take(3)->get();

        if ($warehouses->isEmpty()) {
            $this->command->warn('No warehouses found. Skipping purchase orders.');

            return;
        }

        // Get products
        $products = Product::take(10)->get();

        if ($products->isEmpty()) {
            $this->command->warn('No products found. Skipping purchase orders.');

            return;
        }

        // Get units
        $units = Unit::take(3)->get();

        if ($units->isEmpty()) {
            $this->command->warn('No units found. Skipping purchase orders.');

            return;
        }

        $purchaseCounter = 1;

        foreach ($vendors as $vendor) {
            // Create 2-3 purchase orders per vendor
            $numOrders = rand(2, 3);

            for ($i = 0; $i < $numOrders; $i++) {
                $status = $this->getRandomStatus();
                $paymentStatus = $this->getRandomPaymentStatus($status);
                $approvalStatus = $this->getRandomApprovalStatus($status);

                $purchaseDate = Carbon::now()->subDays(rand(1, 60));
                $expectedDeliveryDate = $purchaseDate->copy()->addDays(rand($vendor->lead_time_days, $vendor->lead_time_days + 7));
                $actualDeliveryDate = null;

                if (in_array($status, ['received', 'completed'])) {
                    $actualDeliveryDate = $expectedDeliveryDate->copy()->addDays(rand(-2, 3));
                }

                $subtotal = 0;
                $taxRate = rand(5, 15); // 5-15% tax
                $discountRate = rand(0, 10); // 0-10% discount

                // Calculate subtotal first (we'll create products after)
                $numProducts = rand(3, 8);
                $productSubtotals = [];

                for ($p = 0; $p < $numProducts; $p++) {
                    $quantity = rand(10, 100);
                    $unitCost = rand(10, 500) + (rand(0, 99) / 100);
                    $productSubtotal = $quantity * $unitCost;
                    $productSubtotals[] = $productSubtotal;
                    $subtotal += $productSubtotal;
                }

                $taxAmount = $subtotal * ($taxRate / 100);
                $discountAmount = $subtotal * ($discountRate / 100);
                $shippingCost = rand(10, 100) + (rand(0, 99) / 100);
                $totalAmount = $subtotal + $taxAmount - $discountAmount + $shippingCost;

                $paidAmount = 0;
                if ($paymentStatus === 'paid') {
                    $paidAmount = $totalAmount;
                } elseif ($paymentStatus === 'partial') {
                    $paidAmount = $totalAmount * (rand(30, 70) / 100);
                }

                $purchase = Purchase::create([
                    'date' => $purchaseDate,
                    'code' => 'PO-'.date('Y').'-'.str_pad($purchaseCounter++, 5, '0', STR_PAD_LEFT),
                    'reference_no' => 'REF-'.strtoupper(uniqid()),
                    'invoice_no' => rand(0, 1) ? 'INV-'.rand(10000, 99999) : null,
                    'vendor_id' => $vendor->id,
                    'warehouse_id' => $warehouses->random()->id,
                    'subtotal' => $subtotal,
                    'tax_amount' => $taxAmount,
                    'discount_amount' => $discountAmount,
                    'shipping_cost' => $shippingCost,
                    'total_amount' => $totalAmount,
                    'notes' => $this->getRandomNotes(),
                    'status' => $status,
                    'payment_status' => $paymentStatus,
                    'payment_terms' => $vendor->payment_terms,
                    'payment_due_date' => $purchaseDate->copy()->addDays(30),
                    'paid_amount' => $paidAmount,
                    'expected_delivery_date' => $expectedDeliveryDate,
                    'actual_delivery_date' => $actualDeliveryDate,
                    'shipping_method' => $this->getRandomShippingMethod(),
                    'tracking_number' => rand(0, 1) ? 'TRK'.rand(100000000, 999999999) : null,
                    'approval_status' => $approvalStatus,
                    'approved_by_id' => $approvalStatus === 'approved' ? $userId : null,
                    'approved_at' => $approvalStatus === 'approved' ? $purchaseDate->copy()->addHours(rand(1, 24)) : null,
                    'received_by_id' => in_array($status, ['received', 'completed']) ? $userId : null,
                    'received_at' => in_array($status, ['received', 'completed']) ? $actualDeliveryDate : null,
                    'created_by_id' => $userId,
                    'updated_by_id' => $userId,
                    'created_at' => $purchaseDate,
                    'updated_at' => $purchaseDate,
                ]);

                // Create purchase products
                for ($p = 0; $p < $numProducts; $p++) {
                    $product = $products->random();
                    $unit = $units->random();
                    $quantity = rand(10, 100);
                    $unitCost = $productSubtotals[$p] / $quantity;

                    $productTaxAmount = $unitCost * $quantity * ($taxRate / 100);
                    $productDiscountAmount = $unitCost * $quantity * ($discountRate / 100);

                    $receivedQuantity = 0;
                    $acceptedQuantity = 0;
                    $rejectedQuantity = 0;
                    $isFullyReceived = false;

                    if (in_array($status, ['received', 'completed'])) {
                        $receivedQuantity = $quantity;
                        $acceptedQuantity = rand($quantity * 0.95, $quantity);
                        $rejectedQuantity = $quantity - $acceptedQuantity;
                        $isFullyReceived = true;
                    } elseif ($status === 'partial') {
                        $receivedQuantity = rand($quantity * 0.3, $quantity * 0.7);
                        $acceptedQuantity = $receivedQuantity;
                    }

                    PurchaseProduct::create([
                        'purchase_id' => $purchase->id,
                        'product_id' => $product->id,
                        'quantity' => $quantity,
                        'unit_id' => $unit->id,
                        'weight' => rand(1, 100) + (rand(0, 99) / 100),
                        'unit_cost' => $unitCost,
                        'tax_rate' => $taxRate,
                        'tax_amount' => $productTaxAmount,
                        'discount_rate' => $discountRate,
                        'discount_amount' => $productDiscountAmount,
                        'subtotal' => $productSubtotals[$p],
                        'received_quantity' => $receivedQuantity,
                        'is_fully_received' => $isFullyReceived,
                        'accepted_quantity' => $acceptedQuantity,
                        'rejected_quantity' => $rejectedQuantity,
                        'rejection_reason' => $rejectedQuantity > 0 ? $this->getRandomRejectionReason() : null,
                        'created_by_id' => $userId,
                        'updated_by_id' => $userId,
                        'created_at' => $purchaseDate,
                        'updated_at' => $purchaseDate,
                    ]);
                }
            }
        }

        $totalPurchases = Purchase::count();
        $this->command->info('Created '.$totalPurchases.' demo purchase orders with products.');
    }

    /**
     * Get random purchase status
     */
    private function getRandomStatus(): string
    {
        $statuses = ['draft', 'pending', 'approved', 'ordered', 'partial', 'received', 'completed', 'cancelled'];

        return $statuses[array_rand($statuses)];
    }

    /**
     * Get random payment status based on order status
     */
    private function getRandomPaymentStatus($status): string
    {
        if ($status === 'cancelled') {
            return 'pending';
        }

        if (in_array($status, ['completed', 'received'])) {
            $paymentStatuses = ['paid', 'paid', 'partial']; // More likely to be paid
        } else {
            $paymentStatuses = ['pending', 'pending', 'partial', 'paid'];
        }

        return $paymentStatuses[array_rand($paymentStatuses)];
    }

    /**
     * Get random approval status based on order status
     */
    private function getRandomApprovalStatus($status): string
    {
        if (in_array($status, ['draft', 'cancelled'])) {
            return 'pending';
        }

        if (in_array($status, ['approved', 'ordered', 'partial', 'received', 'completed'])) {
            return 'approved';
        }

        $approvalStatuses = ['pending', 'approved', 'rejected'];

        return $approvalStatuses[array_rand($approvalStatuses)];
    }

    /**
     * Get random shipping method
     */
    private function getRandomShippingMethod(): string
    {
        $methods = ['Standard Ground', 'Express Delivery', 'Next Day Air', 'Freight', 'Local Pickup', '2-Day Shipping'];

        return $methods[array_rand($methods)];
    }

    /**
     * Get random notes for purchase order
     */
    private function getRandomNotes(): ?string
    {
        $notes = [
            'Please deliver to loading dock #3',
            'Contact warehouse manager before delivery',
            'Urgent order - expedite processing',
            'Partial shipment acceptable',
            'Include packing slip with delivery',
            'Delivery hours: 8AM - 5PM only',
            'Request quality certificates for all items',
            'Budget approved by finance department',
            null,
            null, // Some orders without notes
        ];

        return $notes[array_rand($notes)];
    }

    /**
     * Get random rejection reason
     */
    private function getRandomRejectionReason(): string
    {
        $reasons = [
            'Damaged during shipping',
            'Quality not as per specification',
            'Wrong item delivered',
            'Expired products',
            'Packaging damaged',
        ];

        return $reasons[array_rand($reasons)];
    }
}
