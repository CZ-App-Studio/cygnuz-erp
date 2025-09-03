<?php

namespace Modules\AccountingCore\Database\Seeders;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\AccountingCore\app\Models\BasicTransaction;
use Modules\AccountingCore\app\Models\BasicTransactionCategory;

class AccountingCoreDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedCategories();
            $this->seedTransactions();
        });

        $this->command->info('AccountingCore demo data seeded successfully!');
    }

    /**
     * Seed transaction categories.
     */
    private function seedCategories(): void
    {
        $this->command->info('Seeding transaction categories...');

        $incomeCategories = [
            ['name' => 'Sales Revenue', 'type' => 'income', 'icon' => 'bx-cart', 'color' => '#28a745'],
            ['name' => 'Service Income', 'type' => 'income', 'icon' => 'bx-briefcase', 'color' => '#20c997'],
            ['name' => 'Interest Income', 'type' => 'income', 'icon' => 'bx-trending-up', 'color' => '#17a2b8'],
            ['name' => 'Rental Income', 'type' => 'income', 'icon' => 'bx-home', 'color' => '#6610f2'],
            ['name' => 'Other Income', 'type' => 'income', 'icon' => 'bx-dots-horizontal-rounded', 'color' => '#6c757d'],
        ];

        $expenseCategories = [
            ['name' => 'Salaries & Wages', 'type' => 'expense', 'icon' => 'bx-group', 'color' => '#dc3545'],
            ['name' => 'Rent & Utilities', 'type' => 'expense', 'icon' => 'bx-building', 'color' => '#e83e8c'],
            ['name' => 'Office Supplies', 'type' => 'expense', 'icon' => 'bx-paperclip', 'color' => '#fd7e14'],
            ['name' => 'Travel & Transportation', 'type' => 'expense', 'icon' => 'bx-car', 'color' => '#ffc107'],
            ['name' => 'Marketing & Advertising', 'type' => 'expense', 'icon' => 'bx-megaphone', 'color' => '#6f42c1'],
            ['name' => 'Professional Services', 'type' => 'expense', 'icon' => 'bx-briefcase-alt', 'color' => '#795548'],
            ['name' => 'Equipment & Maintenance', 'type' => 'expense', 'icon' => 'bx-wrench', 'color' => '#607d8b'],
            ['name' => 'Insurance', 'type' => 'expense', 'icon' => 'bx-shield', 'color' => '#343a40'],
            ['name' => 'Taxes & Licenses', 'type' => 'expense', 'icon' => 'bx-receipt', 'color' => '#007bff'],
            ['name' => 'Miscellaneous', 'type' => 'expense', 'icon' => 'bx-dots-horizontal-rounded', 'color' => '#6c757d'],
        ];

        foreach ($incomeCategories as $category) {
            BasicTransactionCategory::firstOrCreate(
                ['name' => $category['name'], 'type' => $category['type']],
                array_merge($category, [
                    'is_active' => true,
                    'created_by_id' => 1,
                    'updated_by_id' => 1,
                ])
            );
        }

        foreach ($expenseCategories as $category) {
            BasicTransactionCategory::firstOrCreate(
                ['name' => $category['name'], 'type' => $category['type']],
                array_merge($category, [
                    'is_active' => true,
                    'created_by_id' => 1,
                    'updated_by_id' => 1,
                ])
            );
        }
    }

    /**
     * Seed transactions with realistic data patterns.
     */
    private function seedTransactions(): void
    {
        $this->command->info('Seeding transactions...');

        $incomeCategories = BasicTransactionCategory::where('type', 'income')->pluck('id', 'name');
        $expenseCategories = BasicTransactionCategory::where('type', 'expense')->pluck('id', 'name');

        // Get a user for created_by
        $user = User::first();
        if (! $user) {
            $this->command->warn('No users found, skipping transaction seeding.');

            return;
        }

        $currentYear = now()->year;
        $startDate = Carbon::create($currentYear, 1, 1);
        $endDate = now();

        // Income patterns
        $incomePatterns = [
            'Sales Revenue' => [
                'base' => 50000,
                'variance' => 15000,
                'frequency' => 'daily',
                'growth' => 0.02, // 2% monthly growth
            ],
            'Service Income' => [
                'base' => 30000,
                'variance' => 10000,
                'frequency' => 'weekly',
                'growth' => 0.015,
            ],
            'Interest Income' => [
                'base' => 2000,
                'variance' => 500,
                'frequency' => 'monthly',
                'growth' => 0,
            ],
            'Rental Income' => [
                'base' => 15000,
                'variance' => 0,
                'frequency' => 'monthly',
                'growth' => 0,
            ],
            'Other Income' => [
                'base' => 5000,
                'variance' => 3000,
                'frequency' => 'random',
                'growth' => 0.01,
            ],
        ];

        // Expense patterns
        $expensePatterns = [
            'Salaries & Wages' => [
                'base' => 45000,
                'variance' => 5000,
                'frequency' => 'monthly',
                'growth' => 0.01,
            ],
            'Rent & Utilities' => [
                'base' => 8000,
                'variance' => 1000,
                'frequency' => 'monthly',
                'growth' => 0.005,
            ],
            'Office Supplies' => [
                'base' => 2000,
                'variance' => 1000,
                'frequency' => 'weekly',
                'growth' => 0,
            ],
            'Travel & Transportation' => [
                'base' => 3000,
                'variance' => 2000,
                'frequency' => 'random',
                'growth' => 0.01,
            ],
            'Marketing & Advertising' => [
                'base' => 10000,
                'variance' => 5000,
                'frequency' => 'monthly',
                'growth' => 0.02,
            ],
            'Professional Services' => [
                'base' => 5000,
                'variance' => 3000,
                'frequency' => 'quarterly',
                'growth' => 0,
            ],
            'Equipment & Maintenance' => [
                'base' => 4000,
                'variance' => 3000,
                'frequency' => 'random',
                'growth' => 0,
            ],
            'Insurance' => [
                'base' => 3000,
                'variance' => 0,
                'frequency' => 'quarterly',
                'growth' => 0.01,
            ],
            'Taxes & Licenses' => [
                'base' => 5000,
                'variance' => 2000,
                'frequency' => 'quarterly',
                'growth' => 0,
            ],
            'Miscellaneous' => [
                'base' => 1000,
                'variance' => 800,
                'frequency' => 'random',
                'growth' => 0,
            ],
        ];

        // Generate income transactions
        foreach ($incomePatterns as $categoryName => $pattern) {
            if (! isset($incomeCategories[$categoryName])) {
                continue;
            }

            $this->generateTransactions(
                'income',
                $incomeCategories[$categoryName],
                $pattern,
                $startDate,
                $endDate,
                $user->id
            );
        }

        // Generate expense transactions
        foreach ($expensePatterns as $categoryName => $pattern) {
            if (! isset($expenseCategories[$categoryName])) {
                continue;
            }

            $this->generateTransactions(
                'expense',
                $expenseCategories[$categoryName],
                $pattern,
                $startDate,
                $endDate,
                $user->id
            );
        }

        // Add some special transactions
        $this->addSpecialTransactions($incomeCategories, $expenseCategories, $user->id);
    }

    /**
     * Generate transactions based on patterns.
     */
    private function generateTransactions($type, $categoryId, $pattern, $startDate, $endDate, $userId)
    {
        $current = $startDate->copy();
        $monthsPassed = 0;

        while ($current <= $endDate) {
            $shouldCreate = false;

            switch ($pattern['frequency']) {
                case 'daily':
                    $shouldCreate = true;
                    $nextDate = $current->copy()->addDay();
                    break;

                case 'weekly':
                    $shouldCreate = $current->dayOfWeek === Carbon::MONDAY;
                    $nextDate = $current->copy()->addDay();
                    break;

                case 'monthly':
                    $shouldCreate = $current->day === 1;
                    $nextDate = $current->copy()->addDay();
                    break;

                case 'quarterly':
                    $shouldCreate = $current->day === 1 && in_array($current->month, [1, 4, 7, 10]);
                    $nextDate = $current->copy()->addDay();
                    break;

                case 'random':
                    $shouldCreate = rand(1, 10) <= 3; // 30% chance
                    $nextDate = $current->copy()->addDays(rand(1, 7));
                    break;

                default:
                    $nextDate = $current->copy()->addDay();
            }

            if ($shouldCreate) {
                // Calculate amount with growth and variance
                $growthFactor = 1 + ($pattern['growth'] * $monthsPassed);
                $baseAmount = $pattern['base'] * $growthFactor;
                $variance = $pattern['variance'] * (rand(-100, 100) / 100);
                $amount = max(100, $baseAmount + $variance);

                // Generate appropriate descriptions
                $descriptions = $this->getDescriptions($type, $categoryId);
                $description = $descriptions[array_rand($descriptions)];

                // Payment methods
                $paymentMethods = ['bank_transfer', 'cash', 'credit_card', 'check', 'other'];
                $paymentMethod = $paymentMethods[array_rand($paymentMethods)];

                // Create transaction
                BasicTransaction::create([
                    'type' => $type,
                    'amount' => round($amount, 2),
                    'category_id' => $categoryId,
                    'description' => str_replace('{date}', $current->format('F Y'), $description),
                    'transaction_date' => $current->toDateString(),
                    'reference_number' => 'REF-'.strtoupper(uniqid()),
                    'payment_method' => $paymentMethod,
                    'tags' => $this->generateTags($type),
                    'created_by_id' => $userId,
                    'updated_by_id' => $userId,
                    'created_at' => $current->copy()->addHours(rand(8, 18)),
                ]);
            }

            // Update month counter
            if ($current->month !== $startDate->copy()->addMonths($monthsPassed)->month) {
                $monthsPassed++;
            }

            $current = $nextDate;
        }
    }

    /**
     * Get appropriate descriptions based on type and category.
     */
    private function getDescriptions($type, $categoryId)
    {
        $category = BasicTransactionCategory::find($categoryId);

        $descriptions = [
            'Sales Revenue' => [
                'Product sales for {date}',
                'Online store revenue - {date}',
                'Retail sales income for {date}',
                'Wholesale order #'.rand(1000, 9999),
                'E-commerce sales - {date}',
            ],
            'Service Income' => [
                'Consulting services - Client #'.rand(100, 999),
                'Professional services rendered',
                'Service contract payment - {date}',
                'Project milestone payment',
                'Maintenance service income',
            ],
            'Interest Income' => [
                'Bank interest for {date}',
                'Investment returns - {date}',
                'Savings account interest',
                'Fixed deposit interest',
            ],
            'Rental Income' => [
                'Property rent for {date}',
                'Equipment rental income',
                'Office space sublease - {date}',
                'Parking space rental',
            ],
            'Other Income' => [
                'Miscellaneous income',
                'Refund received',
                'Commission earned',
                'Cashback rewards',
            ],
            'Salaries & Wages' => [
                'Monthly payroll - {date}',
                'Employee salaries for {date}',
                'Contractor payments',
                'Overtime compensation',
                'Performance bonuses',
            ],
            'Rent & Utilities' => [
                'Office rent - {date}',
                'Electricity bill payment',
                'Water and sewage charges',
                'Internet service payment',
                'Gas utility payment',
            ],
            'Office Supplies' => [
                'Stationery purchase',
                'Printer supplies',
                'Office furniture',
                'Computer accessories',
                'Cleaning supplies',
            ],
            'Travel & Transportation' => [
                'Business trip expenses',
                'Local transportation',
                'Flight tickets - Conference',
                'Hotel accommodation',
                'Fuel expenses',
            ],
            'Marketing & Advertising' => [
                'Social media advertising',
                'Google Ads campaign',
                'Print media advertisement',
                'Marketing materials',
                'SEO services payment',
            ],
            'Professional Services' => [
                'Legal consultation fees',
                'Accounting services',
                'IT support payment',
                'Business consulting',
                'Audit fees',
            ],
            'Equipment & Maintenance' => [
                'Computer equipment purchase',
                'Machinery maintenance',
                'Software licenses',
                'Equipment repair',
                'Tool purchases',
            ],
            'Insurance' => [
                'Business liability insurance',
                'Property insurance premium',
                'Vehicle insurance',
                'Health insurance payment',
            ],
            'Taxes & Licenses' => [
                'Business license renewal',
                'Property tax payment',
                'Sales tax remittance',
                'Professional license fees',
            ],
            'Miscellaneous' => [
                'Bank charges',
                'Subscription fees',
                'Membership dues',
                'Small purchases',
                'Other expenses',
            ],
        ];

        return $descriptions[$category->name] ?? ['Transaction for '.$category->name];
    }

    /**
     * Generate relevant tags for transactions.
     */
    private function generateTags($type)
    {
        $commonTags = ['business', 'operations', date('Y')];

        $typeTags = [
            'income' => ['revenue', 'earnings', 'sales'],
            'expense' => ['cost', 'expenditure', 'overhead'],
        ];

        $tags = array_merge($commonTags, $typeTags[$type] ?? []);

        // Randomly select 2-4 tags
        $selectedTags = array_rand(array_flip($tags), rand(2, min(4, count($tags))));

        return is_array($selectedTags) ? $selectedTags : [$selectedTags];
    }

    /**
     * Add special one-time transactions.
     */
    private function addSpecialTransactions($incomeCategories, $expenseCategories, $userId)
    {
        // Large equipment purchase
        BasicTransaction::create([
            'type' => 'expense',
            'amount' => 25000,
            'category_id' => $expenseCategories['Equipment & Maintenance'] ?? $expenseCategories->first(),
            'description' => 'Server infrastructure upgrade',
            'transaction_date' => now()->subMonths(3),
            'reference_number' => 'REF-SPECIAL-001',
            'payment_method' => 'bank_transfer',
            'tags' => ['capital-expense', 'infrastructure', 'IT'],
            'created_by_id' => $userId,
            'updated_by_id' => $userId,
        ]);

        // Annual bonus payment
        BasicTransaction::create([
            'type' => 'expense',
            'amount' => 50000,
            'category_id' => $expenseCategories['Salaries & Wages'] ?? $expenseCategories->first(),
            'description' => 'Annual employee bonuses',
            'transaction_date' => Carbon::create(now()->year - 1, 12, 25),
            'reference_number' => 'REF-BONUS-'.(now()->year - 1),
            'payment_method' => 'bank_transfer',
            'tags' => ['bonus', 'year-end', 'compensation'],
            'created_by_id' => $userId,
            'updated_by_id' => $userId,
        ]);

        // Large contract payment
        BasicTransaction::create([
            'type' => 'income',
            'amount' => 100000,
            'category_id' => $incomeCategories['Service Income'] ?? $incomeCategories->first(),
            'description' => 'Major project completion - Enterprise Client',
            'transaction_date' => now()->subMonths(2),
            'reference_number' => 'CONTRACT-2024-001',
            'payment_method' => 'bank_transfer',
            'tags' => ['major-project', 'enterprise', 'milestone'],
            'created_by_id' => $userId,
            'updated_by_id' => $userId,
        ]);

        // Tax payment
        BasicTransaction::create([
            'type' => 'expense',
            'amount' => 15000,
            'category_id' => $expenseCategories['Taxes & Licenses'] ?? $expenseCategories->first(),
            'description' => 'Quarterly tax payment',
            'transaction_date' => now()->subMonths(1)->startOfQuarter(),
            'reference_number' => 'TAX-Q'.now()->quarter.'-'.now()->year,
            'payment_method' => 'bank_transfer',
            'tags' => ['tax', 'quarterly', 'compliance'],
            'created_by_id' => $userId,
            'updated_by_id' => $userId,
        ]);
    }
}
