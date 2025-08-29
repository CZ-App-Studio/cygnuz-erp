<?php

namespace Modules\CRMCore\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\CRMCore\app\Models\Company;
use Modules\CRMCore\app\Models\Contact;
use Modules\CRMCore\app\Models\Customer;
use Modules\CRMCore\app\Models\CustomerGroup;

class CustomerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get customer groups
        $regularGroup = CustomerGroup::where('code', 'REGULAR')->first();
        $vipGroup = CustomerGroup::where('code', 'VIP')->first();
        $wholesaleGroup = CustomerGroup::where('code', 'WHOLESALE')->first();
        $corporateGroup = CustomerGroup::where('code', 'CORPORATE')->first();

        // Sample customer data
        $customers = [
            // Regular Customers
            [
                'contact' => [
                    'first_name' => 'John',
                    'last_name' => 'Smith',
                    'email_primary' => 'john.smith@example.com',
                    'phone_primary' => '+1234567890',
                    'phone_mobile' => '+1234567891',
                    'address_street' => '123 Main Street',
                    'address_city' => 'New York',
                    'address_state' => 'NY',
                    'address_postal_code' => '10001',
                    'address_country' => 'USA',
                    'is_active' => true,
                ],
                'customer' => [
                    'customer_group_id' => $regularGroup->id ?? null,
                    'payment_terms' => 'cod',
                    'credit_limit' => 0,
                    'discount_percentage' => 0,
                    'lifetime_value' => 1250.50,
                    'purchase_count' => 5,
                    'average_order_value' => 250.10,
                    'first_purchase_date' => now()->subMonths(6),
                    'last_purchase_date' => now()->subDays(10),
                ],
            ],
            [
                'contact' => [
                    'first_name' => 'Sarah',
                    'last_name' => 'Johnson',
                    'email_primary' => 'sarah.j@example.com',
                    'phone_primary' => '+1234567892',
                    'address_street' => '456 Oak Avenue',
                    'address_city' => 'Los Angeles',
                    'address_state' => 'CA',
                    'address_postal_code' => '90001',
                    'address_country' => 'USA',
                    'is_active' => true,
                ],
                'customer' => [
                    'customer_group_id' => $regularGroup->id ?? null,
                    'payment_terms' => 'cod',
                    'credit_limit' => 0,
                    'discount_percentage' => 0,
                    'lifetime_value' => 850.00,
                    'purchase_count' => 3,
                    'average_order_value' => 283.33,
                    'first_purchase_date' => now()->subMonths(3),
                    'last_purchase_date' => now()->subDays(25),
                ],
            ],

            // VIP Customers
            [
                'contact' => [
                    'first_name' => 'Michael',
                    'last_name' => 'Chen',
                    'email_primary' => 'michael.chen@vipmail.com',
                    'phone_primary' => '+1234567893',
                    'phone_mobile' => '+1234567894',
                    'address_street' => '789 Executive Plaza',
                    'address_city' => 'San Francisco',
                    'address_state' => 'CA',
                    'address_postal_code' => '94105',
                    'address_country' => 'USA',
                    'job_title' => 'CEO',
                    'is_active' => true,
                ],
                'customer' => [
                    'customer_group_id' => $vipGroup->id ?? null,
                    'payment_terms' => 'net30',
                    'credit_limit' => 50000,
                    'discount_percentage' => 10,
                    'lifetime_value' => 125000.00,
                    'purchase_count' => 50,
                    'average_order_value' => 2500.00,
                    'first_purchase_date' => now()->subYears(2),
                    'last_purchase_date' => now()->subDays(3),
                ],
            ],
            [
                'contact' => [
                    'first_name' => 'Emma',
                    'last_name' => 'Williams',
                    'email_primary' => 'emma.w@luxuryshop.com',
                    'phone_primary' => '+1234567895',
                    'address_street' => '321 Diamond Street',
                    'address_city' => 'Miami',
                    'address_state' => 'FL',
                    'address_postal_code' => '33101',
                    'address_country' => 'USA',
                    'is_active' => true,
                ],
                'customer' => [
                    'customer_group_id' => $vipGroup->id ?? null,
                    'payment_terms' => 'net30',
                    'credit_limit' => 30000,
                    'discount_percentage' => 10,
                    'lifetime_value' => 45000.00,
                    'purchase_count' => 25,
                    'average_order_value' => 1800.00,
                    'first_purchase_date' => now()->subMonths(18),
                    'last_purchase_date' => now()->subDays(7),
                ],
            ],

            // Wholesale Customers
            [
                'contact' => [
                    'first_name' => 'Robert',
                    'last_name' => 'Anderson',
                    'email_primary' => 'robert@wholesaledist.com',
                    'phone_primary' => '+1234567896',
                    'phone_office' => '+1234567897',
                    'address_street' => '555 Industrial Blvd',
                    'address_city' => 'Chicago',
                    'address_state' => 'IL',
                    'address_postal_code' => '60601',
                    'address_country' => 'USA',
                    'job_title' => 'Purchasing Manager',
                    'is_active' => true,
                ],
                'customer' => [
                    'customer_group_id' => $wholesaleGroup->id ?? null,
                    'payment_terms' => 'net60',
                    'credit_limit' => 100000,
                    'discount_percentage' => 15,
                    'lifetime_value' => 350000.00,
                    'purchase_count' => 100,
                    'average_order_value' => 3500.00,
                    'first_purchase_date' => now()->subYears(3),
                    'last_purchase_date' => now()->subDays(2),
                    'tax_exempt' => true,
                    'tax_number' => 'TX-123456789',
                ],
            ],

            // Corporate Customers with Companies
            [
                'company' => [
                    'name' => 'TechCorp Industries',
                    'industry' => 'Technology',
                    'website' => 'https://techcorp.example.com',
                    'email_office' => 'info@techcorp.example.com',
                    'phone_office' => '+1234567898',
                    'address_street' => '1000 Tech Park Drive',
                    'address_city' => 'Seattle',
                    'address_state' => 'WA',
                    'address_postal_code' => '98101',
                    'address_country' => 'USA',
                    'description' => 'Leading technology solutions provider',
                    'is_active' => true,
                ],
                'contact' => [
                    'first_name' => 'David',
                    'last_name' => 'Thompson',
                    'email_primary' => 'david.thompson@techcorp.com',
                    'phone_primary' => '+1234567899',
                    'phone_office' => '+1234567900',
                    'job_title' => 'Chief Procurement Officer',
                    'department' => 'Procurement',
                    'is_primary_contact_for_company' => true,
                    'is_active' => true,
                ],
                'customer' => [
                    'customer_group_id' => $corporateGroup->id ?? null,
                    'payment_terms' => 'net60',
                    'credit_limit' => 500000,
                    'discount_percentage' => 20,
                    'lifetime_value' => 1500000.00,
                    'purchase_count' => 200,
                    'average_order_value' => 7500.00,
                    'first_purchase_date' => now()->subYears(5),
                    'last_purchase_date' => now()->subDays(1),
                    'tax_exempt' => true,
                    'tax_number' => 'CORP-987654321',
                    'business_registration' => 'BR-123456',
                    'notes' => 'Key corporate account. Requires approval for orders over $50,000.',
                ],
            ],
            [
                'company' => [
                    'name' => 'Global Retail Solutions',
                    'industry' => 'Retail',
                    'website' => 'https://globalretail.example.com',
                    'email_office' => 'procurement@globalretail.com',
                    'phone_office' => '+1234567901',
                    'address_street' => '2000 Commerce Center',
                    'address_city' => 'Dallas',
                    'address_state' => 'TX',
                    'address_postal_code' => '75201',
                    'address_country' => 'USA',
                    'description' => 'Global retail chain with 500+ locations',
                    'is_active' => true,
                ],
                'contact' => [
                    'first_name' => 'Jennifer',
                    'last_name' => 'Martinez',
                    'email_primary' => 'jennifer.m@globalretail.com',
                    'phone_primary' => '+1234567902',
                    'phone_office' => '+1234567903',
                    'job_title' => 'VP of Operations',
                    'department' => 'Operations',
                    'is_primary_contact_for_company' => true,
                    'is_active' => true,
                ],
                'customer' => [
                    'customer_group_id' => $corporateGroup->id ?? null,
                    'payment_terms' => 'net60',
                    'credit_limit' => 750000,
                    'credit_used' => 125000,
                    'discount_percentage' => 20,
                    'lifetime_value' => 2500000.00,
                    'purchase_count' => 300,
                    'average_order_value' => 8333.33,
                    'first_purchase_date' => now()->subYears(7),
                    'last_purchase_date' => now()->subHours(12),
                    'tax_exempt' => true,
                    'tax_number' => 'GRS-456789123',
                    'business_registration' => 'TX-BR-789456',
                    'preferred_payment_method' => 'wire_transfer',
                    'preferred_delivery_method' => 'freight',
                    'notes' => 'Preferred partner. Expedited shipping available.',
                ],
            ],

            // Inactive Customer
            [
                'contact' => [
                    'first_name' => 'Thomas',
                    'last_name' => 'Brown',
                    'email_primary' => 'thomas.brown@oldcustomer.com',
                    'phone_primary' => '+1234567904',
                    'address_street' => '999 Old Road',
                    'address_city' => 'Boston',
                    'address_state' => 'MA',
                    'address_postal_code' => '02101',
                    'address_country' => 'USA',
                    'is_active' => false,
                ],
                'customer' => [
                    'customer_group_id' => $regularGroup->id ?? null,
                    'payment_terms' => 'cod',
                    'credit_limit' => 0,
                    'discount_percentage' => 0,
                    'lifetime_value' => 500.00,
                    'purchase_count' => 2,
                    'average_order_value' => 250.00,
                    'first_purchase_date' => now()->subYears(2),
                    'last_purchase_date' => now()->subMonths(18),
                    'is_active' => false,
                    'notes' => 'Customer inactive - moved to competitor.',
                ],
            ],

            // Blacklisted Customer
            [
                'contact' => [
                    'first_name' => 'Mark',
                    'last_name' => 'Wilson',
                    'email_primary' => 'mark.wilson@badpayer.com',
                    'phone_primary' => '+1234567905',
                    'address_street' => '666 Problem Street',
                    'address_city' => 'Las Vegas',
                    'address_state' => 'NV',
                    'address_postal_code' => '89101',
                    'address_country' => 'USA',
                    'is_active' => true,
                ],
                'customer' => [
                    'customer_group_id' => $regularGroup->id ?? null,
                    'payment_terms' => 'cod',
                    'credit_limit' => 0,
                    'discount_percentage' => 0,
                    'lifetime_value' => 5000.00,
                    'purchase_count' => 10,
                    'average_order_value' => 500.00,
                    'first_purchase_date' => now()->subMonths(12),
                    'last_purchase_date' => now()->subMonths(6),
                    'is_active' => true,
                    'is_blacklisted' => true,
                    'blacklist_reason' => 'Multiple payment defaults and chargebacks. Do not extend credit.',
                ],
            ],
        ];

        // Create customers
        foreach ($customers as $data) {
            DB::beginTransaction();
            try {
                // Create company if provided
                $companyId = null;
                if (isset($data['company'])) {
                    $company = Company::firstOrCreate(
                        ['name' => $data['company']['name']],
                        array_merge($data['company'], [
                            'tenant_id' => 1,
                            'created_by_id' => 1,
                        ])
                    );
                    $companyId = $company->id;
                }

                // Create contact
                $contactData = array_merge($data['contact'], [
                    'tenant_id' => 1,
                    'created_by_id' => 1,
                ]);

                if ($companyId) {
                    $contactData['company_id'] = $companyId;
                    // Set address from company if not provided
                    if (! isset($contactData['address_street']) && isset($data['company']['address_street'])) {
                        $contactData['address_street'] = $data['company']['address_street'];
                        $contactData['address_city'] = $data['company']['address_city'];
                        $contactData['address_state'] = $data['company']['address_state'];
                        $contactData['address_postal_code'] = $data['company']['address_postal_code'];
                        $contactData['address_country'] = $data['company']['address_country'];
                    }
                }

                $contact = Contact::create($contactData);

                // Create customer
                $customerData = array_merge($data['customer'], [
                    'contact_id' => $contact->id,
                    // Code will be auto-generated by HasCRMCode trait
                    'tenant_id' => 1,
                    'created_by_id' => 1,
                ]);

                // Calculate average order value if not provided
                if (! isset($customerData['average_order_value']) &&
                    isset($customerData['lifetime_value']) &&
                    isset($customerData['purchase_count']) &&
                    $customerData['purchase_count'] > 0) {
                    $customerData['average_order_value'] = $customerData['lifetime_value'] / $customerData['purchase_count'];
                }

                Customer::create($customerData);

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->command->error('Error creating customer: '.$e->getMessage());
            }
        }

        $this->command->info('Customer seeder completed successfully.');
    }
}
