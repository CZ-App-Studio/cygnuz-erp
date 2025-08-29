<?php

namespace Modules\AccountingCore\app\Settings;

use App\Services\Settings\BaseModuleSettings;

class AccountingCoreSettings extends BaseModuleSettings
{
    protected string $module = 'AccountingCore';

    /**
     * Define module settings
     */
    protected function define(): array
    {
        return [
            'transactions' => [
                'transaction_prefix' => [
                    'label' => __('Transaction Number Prefix'),
                    'type' => 'text',
                    'default' => 'TXN',
                    'validation' => 'nullable|string|max:10',
                    'help' => __('Prefix for transaction numbers (e.g., TXN-001)'),
                ],
                'transaction_start_number' => [
                    'label' => __('Starting Transaction Number'),
                    'type' => 'number',
                    'default' => '1000',
                    'validation' => 'required|integer|min:1',
                    'attributes' => ['min' => '1'],
                    'help' => __('Starting number for transaction sequence'),
                ],
                'allow_future_dates' => [
                    'label' => __('Allow Future Dates'),
                    'type' => 'switch',
                    'default' => false,
                    'validation' => 'boolean',
                    'help' => __('Allow transactions to be created with future dates'),
                ],
                'require_attachments' => [
                    'label' => __('Require Attachments'),
                    'type' => 'switch',
                    'default' => false,
                    'validation' => 'boolean',
                    'help' => __('Require receipts or documents for all transactions'),
                ],
            ],
            'categories' => [
                'allow_custom_categories' => [
                    'label' => __('Allow Custom Categories'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Allow users to create custom transaction categories'),
                ],
                'category_hierarchy_levels' => [
                    'label' => __('Category Hierarchy Levels'),
                    'type' => 'number',
                    'default' => '2',
                    'validation' => 'required|integer|min:1|max:5',
                    'attributes' => ['min' => '1', 'max' => '5'],
                    'help' => __('Maximum levels of category hierarchy (parent/child)'),
                ],
            ],
        ];
    }

    /**
     * Get module display name
     */
    public function getModuleName(): string
    {
        return __('Accounting Settings');
    }

    /**
     * Get module description
     */
    public function getModuleDescription(): string
    {
        return __('Configure basic accounting settings for income and expense tracking');
    }

    /**
     * Get module icon
     */
    public function getModuleIcon(): string
    {
        return 'bx bx-calculator';
    }

    /**
     * Get settings sections
     */
    public function getSections(): array
    {
        return [
            [
                'id' => 'transactions',
                'title' => __('Transaction Settings'),
                'icon' => 'bx bx-transfer',
                'description' => __('Configure transaction-related settings'),
                'fields' => [
                    [
                        'name' => 'transaction_prefix',
                        'label' => __('Transaction Number Prefix'),
                        'type' => 'text',
                        'default' => 'TXN',
                        'required' => false,
                        'help' => __('Prefix for transaction numbers (e.g., TXN-001)'),
                    ],
                    [
                        'name' => 'transaction_start_number',
                        'label' => __('Starting Transaction Number'),
                        'type' => 'number',
                        'default' => '1000',
                        'min' => '1',
                        'required' => true,
                        'help' => __('Starting number for transaction sequence'),
                    ],
                    [
                        'name' => 'allow_future_dates',
                        'label' => __('Allow Future Dates'),
                        'type' => 'switch',
                        'default' => false,
                        'help' => __('Allow transactions to be created with future dates'),
                    ],
                    [
                        'name' => 'require_attachments',
                        'label' => __('Require Attachments'),
                        'type' => 'switch',
                        'default' => false,
                        'help' => __('Require receipts or documents for all transactions'),
                    ],
                ],
            ],
            [
                'id' => 'categories',
                'title' => __('Category Settings'),
                'icon' => 'bx bx-category',
                'description' => __('Configure transaction category settings'),
                'fields' => [
                    [
                        'name' => 'allow_custom_categories',
                        'label' => __('Allow Custom Categories'),
                        'type' => 'switch',
                        'default' => true,
                        'help' => __('Allow users to create custom transaction categories'),
                    ],
                    [
                        'name' => 'category_hierarchy_levels',
                        'label' => __('Category Hierarchy Levels'),
                        'type' => 'number',
                        'default' => '2',
                        'min' => '1',
                        'max' => '5',
                        'required' => true,
                        'help' => __('Maximum levels of category hierarchy (parent/child)'),
                    ],
                ],
            ],
        ];
    }

    /**
     * Get validation rules
     */
    public function getValidationRules(): array
    {
        return [
            'transaction_prefix' => 'nullable|string|max:10',
            'transaction_start_number' => 'required|integer|min:1',
            'allow_future_dates' => 'boolean',
            'require_attachments' => 'boolean',
            'allow_custom_categories' => 'boolean',
            'category_hierarchy_levels' => 'required|integer|min:1|max:5',
        ];
    }

    /**
     * Get default values
     */
    public function getDefaults(): array
    {
        return [
            'transaction_prefix' => 'TXN',
            'transaction_start_number' => '1000',
            'allow_future_dates' => false,
            'require_attachments' => false,
            'allow_custom_categories' => true,
            'category_hierarchy_levels' => '2',
        ];
    }
}
