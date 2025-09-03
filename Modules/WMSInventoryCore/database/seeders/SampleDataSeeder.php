<?php

namespace Modules\WMSInventoryCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\WMSInventoryCore\Models\Adjustment;
use Modules\WMSInventoryCore\Models\AdjustmentProduct;
use Modules\WMSInventoryCore\Models\AdjustmentType;
use Modules\WMSInventoryCore\Models\Category;
use Modules\WMSInventoryCore\Models\Inventory;
use Modules\WMSInventoryCore\Models\Product;
use Modules\WMSInventoryCore\Models\Transfer;
use Modules\WMSInventoryCore\Models\TransferProduct;
use Modules\WMSInventoryCore\Models\Unit;
use Modules\WMSInventoryCore\Models\Warehouse;

class SampleDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            UnitsSeeder::class,
            CategoriesSeeder::class,
            AdjustmentTypesSeeder::class,
            WarehousesSeeder::class,
            ProductsSeeder::class,
            InventorySeeder::class,
            AdjustmentsSeeder::class,
            TransfersSeeder::class,
        ]);
    }
}

class UnitsSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'Piece', 'code' => 'PCS', 'status' => 'active'],
            ['name' => 'Kilogram', 'code' => 'KG', 'status' => 'active'],
            ['name' => 'Gram', 'code' => 'G', 'status' => 'active'],
            ['name' => 'Liter', 'code' => 'L', 'status' => 'active'],
            ['name' => 'Milliliter', 'code' => 'ML', 'status' => 'active'],
            ['name' => 'Meter', 'code' => 'M', 'status' => 'active'],
            ['name' => 'Centimeter', 'code' => 'CM', 'status' => 'active'],
            ['name' => 'Box', 'code' => 'BOX', 'status' => 'active'],
            ['name' => 'Pack', 'code' => 'PACK', 'status' => 'active'],
            ['name' => 'Dozen', 'code' => 'DOZ', 'status' => 'active'],
        ];

        foreach ($units as $unit) {
            Unit::firstOrCreate(['code' => $unit['code']], $unit);
        }
    }
}

class CategoriesSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Electronics',
                'code' => 'ELEC',
                'description' => 'Electronic devices and accessories',
            ],
            [
                'name' => 'Clothing',
                'code' => 'CLOTH',
                'description' => 'Clothing, shoes, and fashion accessories',
            ],
            [
                'name' => 'Books',
                'code' => 'BOOKS',
                'description' => 'Books, magazines, and literature',
            ],
            [
                'name' => 'Sports & Outdoors',
                'code' => 'SPORT',
                'description' => 'Sports equipment and outdoor gear',
            ],
            [
                'name' => 'Home & Garden',
                'code' => 'HOME',
                'description' => 'Home improvement, furniture, and garden supplies',
            ],
            [
                'name' => 'Health & Beauty',
                'code' => 'HEALTH',
                'description' => 'Health products, cosmetics, and personal care',
            ],
            [
                'name' => 'Food & Beverages',
                'code' => 'FOOD',
                'description' => 'Food items, beverages, and grocery products',
            ],
            [
                'name' => 'Automotive',
                'code' => 'AUTO',
                'description' => 'Car parts, accessories, and automotive supplies',
            ],
            [
                'name' => 'Office Supplies',
                'code' => 'OFFICE',
                'description' => 'Office equipment, stationery, and supplies',
            ],
            [
                'name' => 'Tools & Hardware',
                'code' => 'TOOLS',
                'description' => 'Tools, hardware, and construction supplies',
            ],
        ];

        foreach ($categories as $category) {
            Category::firstOrCreate(['code' => $category['code']], $category);
        }

        // Create some subcategories
        $electronics = Category::where('name', 'Electronics')->first();
        if ($electronics) {
            Category::firstOrCreate(
                ['code' => 'SMARTPHONES'],
                [
                    'name' => 'Smartphones',
                    'code' => 'SMARTPHONES',
                    'parent_id' => $electronics->id,
                    'description' => 'Mobile phones and accessories',
                ]
            );
            Category::firstOrCreate(
                ['code' => 'LAPTOPS'],
                [
                    'name' => 'Laptops',
                    'code' => 'LAPTOPS',
                    'parent_id' => $electronics->id,
                    'description' => 'Laptop computers and accessories',
                ]
            );
            Category::firstOrCreate(
                ['code' => 'ACCESSORIES'],
                [
                    'name' => 'Accessories',
                    'code' => 'ACCESSORIES',
                    'parent_id' => $electronics->id,
                    'description' => 'Electronic accessories and peripherals',
                ]
            );
        }
    }
}

class AdjustmentTypesSeeder extends Seeder
{
    public function run(): void
    {
        $adjustmentTypes = [
            ['name' => 'Damage', 'code' => 'DAMAGE', 'effect' => 'decrease', 'description' => 'Items damaged during handling or storage', 'status' => 'active'],
            ['name' => 'Loss', 'code' => 'LOSS', 'effect' => 'decrease', 'description' => 'Items lost or missing', 'status' => 'active'],
            ['name' => 'Found', 'code' => 'FOUND', 'effect' => 'increase', 'description' => 'Items found during inventory count', 'status' => 'active'],
            ['name' => 'Return', 'code' => 'RETURN', 'effect' => 'increase', 'description' => 'Items returned from customers', 'status' => 'active'],
            ['name' => 'Expired', 'code' => 'EXPIRED', 'effect' => 'decrease', 'description' => 'Items that have expired', 'status' => 'active'],
            ['name' => 'Quality Control', 'code' => 'QC', 'effect' => 'decrease', 'description' => 'Items rejected by quality control', 'status' => 'active'],
            ['name' => 'Correction', 'code' => 'CORRECTION', 'effect' => 'increase', 'description' => 'Inventory correction after count', 'status' => 'active'],
            ['name' => 'Theft', 'code' => 'THEFT', 'effect' => 'decrease', 'description' => 'Items stolen or missing due to theft', 'status' => 'active'],
        ];

        foreach ($adjustmentTypes as $type) {
            AdjustmentType::firstOrCreate(['code' => $type['code']], $type);
        }
    }
}

class WarehousesSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = [
            [
                'name' => 'Main Warehouse',
                'code' => 'WH001',
                'address' => '123 Industrial Drive',
                'city' => 'Springfield',
                'state' => 'IL',
                'country' => 'USA',
                'postal_code' => '62701',
                'phone_number' => '+1-555-0001',
                'email' => 'main@warehouse.com',
                'contact_person' => 'John Smith',
                'total_area' => 10000.00,
                'storage_capacity' => 50000.00,
                'warehouse_type' => 'distribution',
                'is_main' => true,
                'status' => 'active',
            ],
            [
                'name' => 'East Coast Warehouse',
                'code' => 'WH002',
                'address' => '456 Commerce Blvd',
                'city' => 'New York',
                'state' => 'NY',
                'country' => 'USA',
                'postal_code' => '10001',
                'phone_number' => '+1-555-0002',
                'email' => 'east@warehouse.com',
                'contact_person' => 'Jane Doe',
                'total_area' => 8000.00,
                'storage_capacity' => 40000.00,
                'warehouse_type' => 'regional',
                'is_main' => false,
                'status' => 'active',
            ],
            [
                'name' => 'West Coast Warehouse',
                'code' => 'WH003',
                'address' => '789 Pacific Ave',
                'city' => 'Los Angeles',
                'state' => 'CA',
                'country' => 'USA',
                'postal_code' => '90001',
                'phone_number' => '+1-555-0003',
                'email' => 'west@warehouse.com',
                'contact_person' => 'Mike Johnson',
                'total_area' => 12000.00,
                'storage_capacity' => 60000.00,
                'warehouse_type' => 'distribution',
                'is_main' => false,
                'status' => 'active',
            ],
            [
                'name' => 'Returns Processing Center',
                'code' => 'WH004',
                'address' => '321 Return St',
                'city' => 'Chicago',
                'state' => 'IL',
                'country' => 'USA',
                'postal_code' => '60601',
                'phone_number' => '+1-555-0004',
                'email' => 'returns@warehouse.com',
                'contact_person' => 'Sarah Wilson',
                'total_area' => 5000.00,
                'storage_capacity' => 25000.00,
                'warehouse_type' => 'returns',
                'is_main' => false,
                'status' => 'active',
            ],
        ];

        foreach ($warehouses as $warehouse) {
            Warehouse::firstOrCreate(['code' => $warehouse['code']], $warehouse);
        }
    }
}

class ProductsSeeder extends Seeder
{
    public function run(): void
    {
        $units = Unit::all()->keyBy('code');
        $categories = Category::all()->keyBy('name');

        $products = [
            // Electronics
            [
                'name' => 'iPhone 15 Pro',
                'code' => 'PROD001',
                'sku' => 'IPH15PRO',
                'barcode' => '1234567890123',
                'description' => 'Latest iPhone with advanced features',
                'unit_id' => $units['PCS']->id,
                'category_id' => $categories['Electronics']->id,
                'cost_price' => 800.00,
                'selling_price' => 1199.00,
                'min_stock_level' => 10,
                'max_stock_level' => 100,
                'reorder_point' => 20,
                'status' => 'active',
            ],
            [
                'name' => 'Samsung Galaxy S24',
                'code' => 'PROD002',
                'sku' => 'SAMS24',
                'barcode' => '1234567890124',
                'description' => 'Samsung flagship smartphone',
                'unit_id' => $units['PCS']->id,
                'category_id' => $categories['Electronics']->id,
                'cost_price' => 750.00,
                'selling_price' => 1099.00,
                'min_stock_level' => 8,
                'max_stock_level' => 80,
                'reorder_point' => 15,
                'status' => 'active',
            ],
            [
                'name' => 'MacBook Pro 16"',
                'code' => 'PROD003',
                'sku' => 'MBP16',
                'barcode' => '1234567890125',
                'description' => 'Professional laptop for power users',
                'unit_id' => $units['PCS']->id,
                'category_id' => $categories['Electronics']->id,
                'cost_price' => 2000.00,
                'selling_price' => 2999.00,
                'min_stock_level' => 5,
                'max_stock_level' => 50,
                'reorder_point' => 10,
                'status' => 'active',
            ],
            [
                'name' => 'Wireless Mouse',
                'code' => 'PROD004',
                'sku' => 'WMOUSE01',
                'barcode' => '1234567890126',
                'description' => 'Ergonomic wireless mouse',
                'unit_id' => $units['PCS']->id,
                'category_id' => $categories['Electronics']->id,
                'cost_price' => 15.00,
                'selling_price' => 29.99,
                'min_stock_level' => 50,
                'max_stock_level' => 500,
                'reorder_point' => 100,
                'status' => 'active',
            ],
            [
                'name' => 'USB-C Cable',
                'code' => 'PROD005',
                'sku' => 'USBCABLE',
                'barcode' => '1234567890127',
                'description' => 'High-speed USB-C charging cable',
                'unit_id' => $units['PCS']->id,
                'category_id' => $categories['Electronics']->id,
                'cost_price' => 5.00,
                'selling_price' => 12.99,
                'min_stock_level' => 100,
                'max_stock_level' => 1000,
                'reorder_point' => 200,
                'status' => 'active',
            ],

            // Clothing
            [
                'name' => 'Cotton T-Shirt',
                'code' => 'PROD006',
                'sku' => 'TSHIRT01',
                'barcode' => '1234567890128',
                'description' => '100% cotton casual t-shirt',
                'unit_id' => $units['PCS']->id,
                'category_id' => $categories['Clothing']->id,
                'cost_price' => 8.00,
                'selling_price' => 19.99,
                'min_stock_level' => 20,
                'max_stock_level' => 200,
                'reorder_point' => 50,
                'status' => 'active',
            ],
            [
                'name' => 'Denim Jeans',
                'code' => 'PROD007',
                'sku' => 'JEANS01',
                'barcode' => '1234567890129',
                'description' => 'Classic fit denim jeans',
                'unit_id' => $units['PCS']->id,
                'category_id' => $categories['Clothing']->id,
                'cost_price' => 25.00,
                'selling_price' => 59.99,
                'min_stock_level' => 15,
                'max_stock_level' => 150,
                'reorder_point' => 30,
                'status' => 'active',
            ],

            // Books
            [
                'name' => 'Programming Book',
                'code' => 'PROD008',
                'sku' => 'BOOK01',
                'barcode' => '1234567890130',
                'description' => 'Learn programming fundamentals',
                'unit_id' => $units['PCS']->id,
                'category_id' => $categories['Books']->id,
                'cost_price' => 20.00,
                'selling_price' => 39.99,
                'min_stock_level' => 10,
                'max_stock_level' => 100,
                'reorder_point' => 20,
                'status' => 'active',
            ],

            // Sports & Outdoors
            [
                'name' => 'Basketball',
                'code' => 'PROD009',
                'sku' => 'BBALL01',
                'barcode' => '1234567890131',
                'description' => 'Official size basketball',
                'unit_id' => $units['PCS']->id,
                'category_id' => $categories['Sports & Outdoors']->id,
                'cost_price' => 12.00,
                'selling_price' => 24.99,
                'min_stock_level' => 20,
                'max_stock_level' => 200,
                'reorder_point' => 40,
                'status' => 'active',
            ],

            // Home & Garden
            [
                'name' => 'Garden Hose',
                'code' => 'PROD010',
                'sku' => 'HOSE01',
                'barcode' => '1234567890132',
                'description' => '50ft flexible garden hose',
                'unit_id' => $units['PCS']->id,
                'category_id' => $categories['Home & Garden']->id,
                'cost_price' => 18.00,
                'selling_price' => 34.99,
                'min_stock_level' => 10,
                'max_stock_level' => 100,
                'reorder_point' => 25,
                'status' => 'active',
            ],
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(['sku' => $product['sku']], $product);
        }
    }
}

class InventorySeeder extends Seeder
{
    public function run(): void
    {
        $products = Product::all();
        $warehouses = Warehouse::all();

        foreach ($products as $product) {
            foreach ($warehouses as $warehouse) {
                // Create random stock levels for each product in each warehouse
                $stockLevel = rand(0, 150);

                // Some products might be out of stock in some warehouses
                if (rand(1, 10) <= 2) { // 20% chance of being out of stock
                    $stockLevel = 0;
                }

                Inventory::firstOrCreate(
                    [
                        'product_id' => $product->id,
                        'warehouse_id' => $warehouse->id,
                    ],
                    [
                        'stock_level' => $stockLevel,
                        'unit_id' => $product->unit_id,
                        'reserved_quantity' => rand(0, min(10, $stockLevel)),
                        'cost_price' => $product->cost_price,
                        'last_count_date' => now()->subDays(rand(1, 30)),
                        'last_movement_date' => now()->subDays(rand(1, 7)),
                    ]
                );
            }
        }
    }
}

class AdjustmentsSeeder extends Seeder
{
    public function run(): void
    {
        $adjustmentTypes = AdjustmentType::all();
        $warehouses = Warehouse::all();
        $products = Product::take(5)->get(); // Only use first 5 products for adjustments

        // Create 10 sample adjustments
        for ($i = 1; $i <= 10; $i++) {
            $warehouse = $warehouses->random();
            $adjustmentType = $adjustmentTypes->random();
            $date = now()->subDays(rand(1, 30));

            $adjustment = Adjustment::create([
                'date' => $date,
                'code' => 'ADJ-'.str_pad($i, 6, '0', STR_PAD_LEFT),
                'reference_no' => 'REF-ADJ-'.str_pad($i, 6, '0', STR_PAD_LEFT),
                'warehouse_id' => $warehouse->id,
                'adjustment_type_id' => $adjustmentType->id,
                'reason' => 'Sample adjustment reason #'.$i,
                'notes' => 'Sample adjustment #'.$i,
                'total_amount' => 0, // Will be calculated from products
                'status' => $i <= 7 ? 'approved' : 'pending', // First 7 approved, rest pending
                'created_by_id' => 1, // Assuming user ID 1 exists
                'updated_by_id' => 1,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            // Add 1-3 products to each adjustment
            $adjustmentProducts = $products->random(rand(1, 3));
            $totalAmount = 0;

            foreach ($adjustmentProducts as $product) {
                $quantity = rand(1, 10);
                $unitCost = rand(10, 100); // Random unit cost between 10-100
                $subtotal = $quantity * $unitCost;
                $totalAmount += $subtotal;

                AdjustmentProduct::create([
                    'adjustment_id' => $adjustment->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_cost' => $unitCost,
                    'subtotal' => $subtotal,
                    'reason' => 'Stock adjustment for '.$product->name,
                    'created_by_id' => 1,
                    'updated_by_id' => 1,
                ]);
            }

            // Update the total amount for the adjustment
            $adjustment->update(['total_amount' => $totalAmount]);
        }
    }
}

class TransfersSeeder extends Seeder
{
    public function run(): void
    {
        $warehouses = Warehouse::all();
        $products = Product::take(5)->get(); // Only use first 5 products for transfers

        // Create 8 sample transfers
        for ($i = 1; $i <= 8; $i++) {
            $sourceWarehouse = $warehouses->random();
            $destinationWarehouse = $warehouses->where('id', '!=', $sourceWarehouse->id)->random();
            $date = now()->subDays(rand(1, 20));

            $transfer = Transfer::create([
                'transfer_date' => $date,
                'code' => 'TRF-'.str_pad($i, 6, '0', STR_PAD_LEFT),
                'reference_no' => 'REF-TRF-'.str_pad($i, 6, '0', STR_PAD_LEFT),
                'source_warehouse_id' => $sourceWarehouse->id,
                'destination_warehouse_id' => $destinationWarehouse->id,
                'notes' => 'Sample transfer #'.$i,
                'status' => $i <= 5 ? 'completed' : ($i <= 7 ? 'in_transit' : 'draft'),
                'created_by_id' => 1,
                'updated_by_id' => 1,
                'created_at' => $date,
                'updated_at' => $date,
            ]);

            // Add 1-3 products to each transfer
            $transferProducts = $products->random(rand(1, 3));

            foreach ($transferProducts as $product) {
                $quantity = rand(5, 25);

                TransferProduct::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_id' => $product->unit_id,
                ]);
            }
        }
    }
}
