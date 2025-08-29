<?php

namespace Modules\CRMCore\app\Services;

use App\Services\Settings\ModuleSettingsService;
use Modules\CRMCore\app\Settings\CRMCoreSettings;

class CRMCoreSettingsHandler
{
    protected ModuleSettingsService $settingsService;

    protected CRMCoreSettings $settings;

    protected string $module = 'CRMCore';

    public function __construct(ModuleSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
        $this->settings = app(CRMCoreSettings::class);
    }

    /**
     * Get the settings definition for the module
     */
    public function getSettingsDefinition(): array
    {
        return [
            'companies' => [
                'auto_generate_company_codes' => [
                    'label' => __('Enable Company Code'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Automatically generate unique codes for companies'),
                ],
                'company_code_prefix' => [
                    'label' => __('Company Code Prefix'),
                    'type' => 'text',
                    'default' => 'COM',
                    'help' => __('Prefix for company codes (e.g., COM)'),
                ],
                'company_code_start_number' => [
                    'label' => __('Company Code Starting Number'),
                    'type' => 'number',
                    'default' => 1000,
                    'help' => __('Starting number for company codes'),
                ],
                'enable_company_industry_categories' => [
                    'label' => __('Enable Industry Categories'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Allow categorizing companies by industry'),
                ],
                'require_company_assignment' => [
                    'label' => __('Require Company Assignment'),
                    'type' => 'toggle',
                    'default' => false,
                    'help' => __('Make user assignment mandatory for companies'),
                ],
            ],
            'contacts' => [
                'auto_generate_contact_codes' => [
                    'label' => __('Enable Contact Code'),
                    'type' => 'toggle',
                    'default' => false,
                    'help' => __('Automatically generate unique codes for contacts'),
                ],
                'contact_code_prefix' => [
                    'label' => __('Contact Code Prefix'),
                    'type' => 'text',
                    'default' => 'CNT',
                    'help' => __('Prefix for contact codes (e.g., CNT)'),
                ],
                'contact_code_start_number' => [
                    'label' => __('Contact Code Starting Number'),
                    'type' => 'number',
                    'default' => 1000,
                    'help' => __('Starting number for contact codes'),
                ],
                'enable_contact_duplicate_detection' => [
                    'label' => __('Enable Duplicate Detection'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Check for duplicate contacts when creating new ones'),
                ],
                'duplicate_detection_fields' => [
                    'label' => __('Duplicate Detection Method'),
                    'type' => 'select',
                    'options' => [
                        'email' => __('Email Only'),
                        'phone' => __('Phone Only'),
                        'email_and_phone' => __('Email and Phone (both must match)'),
                        'email_or_phone' => __('Email or Phone (either matches)'),
                    ],
                    'default' => 'email_and_phone',
                    'help' => __('Fields to check when detecting duplicate contacts'),
                ],
                'require_company_for_contacts' => [
                    'label' => __('Require Company for Contacts'),
                    'type' => 'toggle',
                    'default' => false,
                    'help' => __('Make company field mandatory when creating contacts'),
                ],
                'require_contact_assignment' => [
                    'label' => __('Require Contact Assignment'),
                    'type' => 'toggle',
                    'default' => false,
                    'help' => __('Make user assignment mandatory for contacts'),
                ],
            ],
            'customers' => [
                'auto_generate_customer_codes' => [
                    'label' => __('Enable Customer Code'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Automatically generate unique codes for customers'),
                ],
                'customer_code_prefix' => [
                    'label' => __('Customer Code Prefix'),
                    'type' => 'text',
                    'default' => 'CUS',
                    'help' => __('Prefix for customer codes (e.g., CUS)'),
                ],
                'customer_code_start_number' => [
                    'label' => __('Customer Code Starting Number'),
                    'type' => 'number',
                    'default' => 1000,
                    'help' => __('Starting number for customer codes'),
                ],
                'auto_create_customer_on_first_purchase' => [
                    'label' => __('Auto Create Customer on First Purchase'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Automatically create customer record on first purchase'),
                ],
                'default_credit_limit' => [
                    'label' => __('Default Credit Limit'),
                    'type' => 'number',
                    'default' => 1000,
                    'help' => __('Default credit limit for new customers'),
                    'prefix' => '$',
                ],
                'default_payment_terms' => [
                    'label' => __('Default Payment Terms'),
                    'type' => 'select',
                    'options' => [
                        'cod' => __('Cash on Delivery'),
                        'net15' => __('Net 15 Days'),
                        'net30' => __('Net 30 Days'),
                        'net60' => __('Net 60 Days'),
                        'net90' => __('Net 90 Days'),
                        'prepaid' => __('Prepaid'),
                    ],
                    'default' => 'cod',
                    'help' => __('Default payment terms for new customers'),
                ],
            ],
            'leads' => [
                'auto_generate_lead_codes' => [
                    'label' => __('Enable Lead Code'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Automatically generate unique codes for leads'),
                ],
                'lead_code_prefix' => [
                    'label' => __('Lead Code Prefix'),
                    'type' => 'text',
                    'default' => 'LD',
                    'help' => __('Prefix for lead codes (e.g., LD)'),
                ],
                'lead_code_start_number' => [
                    'label' => __('Lead Code Starting Number'),
                    'type' => 'number',
                    'default' => 1000,
                    'help' => __('Starting number for lead codes'),
                ],
                'require_lead_assignment' => [
                    'label' => __('Lead Assignment Required'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Require leads to be assigned to a user'),
                ],
                'lead_auto_convert_days' => [
                    'label' => __('Auto Convert Qualified Leads (Days)'),
                    'type' => 'number',
                    'default' => 30,
                    'help' => __('Automatically convert qualified leads after X days (0 to disable)'),
                ],
            ],
            'deals' => [
                'auto_generate_deal_codes' => [
                    'label' => __('Enable Deal Code'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Automatically generate unique codes for deals'),
                ],
                'deal_code_prefix' => [
                    'label' => __('Deal Code Prefix'),
                    'type' => 'text',
                    'default' => 'DL',
                    'help' => __('Prefix for deal codes (e.g., DL)'),
                ],
                'deal_code_start_number' => [
                    'label' => __('Deal Code Starting Number'),
                    'type' => 'number',
                    'default' => 1000,
                    'help' => __('Starting number for deal codes'),
                ],
                'deal_auto_close_days' => [
                    'label' => __('Auto Close Stale Deals (Days)'),
                    'type' => 'number',
                    'default' => 90,
                    'help' => __('Automatically close deals with no activity after X days (0 to disable)'),
                ],
                'require_deal_assignment' => [
                    'label' => __('Require Deal Assignment'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Make user assignment mandatory for deals'),
                ],
                'default_deal_probability' => [
                    'label' => __('Default Deal Probability'),
                    'type' => 'number',
                    'default' => 10,
                    'help' => __('Default probability percentage for new deals'),
                    'min' => 0,
                    'max' => 100,
                ],
            ],
            'tasks' => [
                'auto_generate_task_codes' => [
                    'label' => __('Enable Task Code'),
                    'type' => 'toggle',
                    'default' => false,
                    'help' => __('Automatically generate unique codes for tasks'),
                ],
                'task_code_prefix' => [
                    'label' => __('Task Code Prefix'),
                    'type' => 'text',
                    'default' => 'TSK',
                    'help' => __('Prefix for task codes (e.g., TSK)'),
                ],
                'task_code_start_number' => [
                    'label' => __('Task Code Starting Number'),
                    'type' => 'number',
                    'default' => 1000,
                    'help' => __('Starting number for task codes'),
                ],
                'task_reminder_enabled' => [
                    'label' => __('Enable Task Reminders'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Send reminders for upcoming tasks'),
                ],
                'task_reminder_before_hours' => [
                    'label' => __('Task Reminder Hours'),
                    'type' => 'number',
                    'default' => 24,
                    'help' => __('Send reminder X hours before task due date'),
                ],
                'require_task_assignment' => [
                    'label' => __('Require Task Assignment'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Make user assignment mandatory for tasks'),
                ],
                'enable_task_time_tracking' => [
                    'label' => __('Enable Time Tracking'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Track estimated and actual hours for tasks'),
                ],
            ],
            'notifications' => [
                'notify_lead_assignment' => [
                    'label' => __('Notify on Lead Assignment'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Send notification when lead is assigned'),
                ],
                'notify_deal_stage_change' => [
                    'label' => __('Notify on Deal Stage Change'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Send notification when deal stage changes'),
                ],
                'notify_task_due' => [
                    'label' => __('Notify on Task Due'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Send notification when task is due'),
                ],
            ],
            'display' => [
                'items_per_page' => [
                    'label' => __('Items Per Page'),
                    'type' => 'select',
                    'options' => [
                        '10' => '10',
                        '25' => '25',
                        '50' => '50',
                        '100' => '100',
                    ],
                    'default' => '25',
                    'help' => __('Default number of items per page in listings'),
                ],
                'enable_kanban_view' => [
                    'label' => __('Enable Kanban View'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Enable kanban board view for deals and tasks'),
                ],
                'show_activity_timeline' => [
                    'label' => __('Show Activity Timeline'),
                    'type' => 'toggle',
                    'default' => true,
                    'help' => __('Show activity timeline in entity detail views'),
                ],
            ],
        ];
    }

    /**
     * Get current values for all settings
     */
    public function getCurrentValues(): array
    {
        $values = [];
        $definitions = $this->getSettingsDefinition();

        foreach ($definitions as $category => $settings) {
            foreach ($settings as $key => $setting) {
                $values[$key] = $this->settingsService->get($this->module, $key, $setting['default']);
            }
        }

        return $values;
    }

    /**
     * Get default values for all settings
     */
    public function getDefaultValues(): array
    {
        $defaults = [];
        $definitions = $this->getSettingsDefinition();

        foreach ($definitions as $category => $settings) {
            foreach ($settings as $key => $setting) {
                $defaults[$key] = $setting['default'];
            }
        }

        return $defaults;
    }

    /**
     * Validate settings
     */
    public function validateSettings(array $data): array
    {
        $rules = [];
        $messages = [];
        $definitions = $this->getSettingsDefinition();

        foreach ($definitions as $category => $settings) {
            foreach ($settings as $key => $setting) {
                if ($setting['type'] === 'toggle') {
                    $rules[$key] = 'boolean';
                } elseif ($setting['type'] === 'number') {
                    $rules[$key] = 'nullable|numeric|min:0';
                } elseif ($setting['type'] === 'text') {
                    $rules[$key] = 'nullable|string|max:255';
                } elseif ($setting['type'] === 'select') {
                    $rules[$key] = 'nullable|in:'.implode(',', array_keys($setting['options']));
                }
            }
        }

        $validator = validator($data, $rules, $messages);

        return [
            'valid' => ! $validator->fails(),
            'errors' => $validator->errors()->toArray(),
        ];
    }

    /**
     * Save settings
     */
    public function saveSettings(array $data): bool
    {
        $validation = $this->validateSettings($data);

        if (! $validation['valid']) {
            return false;
        }

        foreach ($data as $key => $value) {
            // Handle checkbox/toggle values
            if (in_array($value, ['true', 'false'], true)) {
                $value = $value === 'true';
            }

            $this->settingsService->set($this->module, $key, $value);
        }

        return true;
    }

    /**
     * Get the view for displaying settings
     */
    public function getSettingsView(): string
    {
        return 'crmcore::crm-settings.form';
    }

    /**
     * Get the module name
     */
    public function getModuleName(): string
    {
        return $this->module;
    }
}
