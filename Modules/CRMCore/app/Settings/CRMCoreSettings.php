<?php

namespace Modules\CRMCore\app\Settings;

use App\Services\Settings\BaseModuleSettings;

class CRMCoreSettings extends BaseModuleSettings
{
    protected string $module = 'CRMCore';

    /**
     * Define module settings
     */
    protected function define(): array
    {
        return [
            'company' => [
                'company_code_prefix' => [
                    'label' => __('Company Code Prefix'),
                    'type' => 'text',
                    'default' => 'COM',
                    'validation' => 'nullable|string|max:10',
                    'help' => __('Prefix for auto-generated company codes (e.g., COM-001)'),
                ],
                'company_code_start_number' => [
                    'label' => __('Starting Company Number'),
                    'type' => 'number',
                    'default' => '1000',
                    'validation' => 'required|integer|min:1',
                    'attributes' => ['min' => '1'],
                    'help' => __('Starting number for company code sequence'),
                ],
                'auto_generate_company_codes' => [
                    'label' => __('Auto Generate Company Codes'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Automatically generate unique codes for new companies'),
                ],
                'require_company_assignment' => [
                    'label' => __('Require Company Assignment'),
                    'type' => 'switch',
                    'default' => false,
                    'validation' => 'boolean',
                    'help' => __('Require all companies to be assigned to a user'),
                ],
                'enable_company_industry_categories' => [
                    'label' => __('Enable Industry Categories'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Enable predefined industry categories for companies'),
                ],
                'default_company_industry' => [
                    'label' => __('Default Company Industry'),
                    'type' => 'text',
                    'default' => '',
                    'validation' => 'nullable|string|max:100',
                    'help' => __('Default industry for new companies if not specified'),
                ],
            ],
            'contacts' => [
                'contact_code_prefix' => [
                    'label' => __('Contact Code Prefix'),
                    'type' => 'text',
                    'default' => 'CNT',
                    'validation' => 'nullable|string|max:10',
                    'help' => __('Prefix for auto-generated contact codes (e.g., CNT-001)'),
                ],
                'contact_code_start_number' => [
                    'label' => __('Starting Contact Number'),
                    'type' => 'number',
                    'default' => '1000',
                    'validation' => 'required|integer|min:1',
                    'attributes' => ['min' => '1'],
                    'help' => __('Starting number for contact code sequence'),
                ],
                'auto_generate_contact_codes' => [
                    'label' => __('Auto Generate Contact Codes'),
                    'type' => 'switch',
                    'default' => false,
                    'validation' => 'boolean',
                    'help' => __('Automatically generate unique codes for new contacts'),
                ],
                'require_contact_assignment' => [
                    'label' => __('Require Contact Assignment'),
                    'type' => 'switch',
                    'default' => false,
                    'validation' => 'boolean',
                    'help' => __('Require all contacts to be assigned to a user'),
                ],
                'enable_contact_duplicate_detection' => [
                    'label' => __('Enable Duplicate Detection'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Check for duplicate contacts by email and phone'),
                ],
                'duplicate_detection_fields' => [
                    'label' => __('Duplicate Detection Fields'),
                    'type' => 'select',
                    'default' => 'email_and_phone',
                    'validation' => 'required|in:email,phone,email_and_phone,email_or_phone',
                    'options' => [
                        'email' => __('Email Only'),
                        'phone' => __('Phone Only'),
                        'email_and_phone' => __('Email AND Phone'),
                        'email_or_phone' => __('Email OR Phone'),
                    ],
                    'help' => __('Fields to check for duplicates'),
                ],
                'require_company_for_contacts' => [
                    'label' => __('Require Company Assignment'),
                    'type' => 'switch',
                    'default' => false,
                    'validation' => 'boolean',
                    'help' => __('Require all contacts to be associated with a company'),
                ],
                'default_contact_active_status' => [
                    'label' => __('Default Active Status'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Default active status for new contacts'),
                ],
            ],
            'customers' => [
                'customer_code_prefix' => [
                    'label' => __('Customer Code Prefix'),
                    'type' => 'text',
                    'default' => 'CUS',
                    'validation' => 'nullable|string|max:10',
                    'help' => __('Prefix for auto-generated customer codes (e.g., CUS-001)'),
                ],
                'customer_code_start_number' => [
                    'label' => __('Starting Customer Number'),
                    'type' => 'number',
                    'default' => '1000',
                    'validation' => 'required|integer|min:1',
                    'attributes' => ['min' => '1'],
                    'help' => __('Starting number for customer code sequence'),
                ],
                'auto_generate_customer_codes' => [
                    'label' => __('Auto Generate Customer Codes'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Automatically generate unique codes for new customers'),
                ],
                'default_customer_group_id' => [
                    'label' => __('Default Customer Group'),
                    'type' => 'select',
                    'default' => null,
                    'validation' => 'nullable|exists:customer_groups,id',
                    'options_source' => 'customer_groups',
                    'help' => __('Default group for new customers'),
                ],
                'default_payment_terms' => [
                    'label' => __('Default Payment Terms'),
                    'type' => 'select',
                    'default' => 'cod',
                    'validation' => 'required|in:cod,net15,net30,net60,net90,prepaid',
                    'options' => [
                        'cod' => __('Cash on Delivery'),
                        'net15' => __('Net 15 Days'),
                        'net30' => __('Net 30 Days'),
                        'net60' => __('Net 60 Days'),
                        'net90' => __('Net 90 Days'),
                        'prepaid' => __('Prepaid'),
                    ],
                    'help' => __('Default payment terms for new customers'),
                ],
                'default_credit_limit' => [
                    'label' => __('Default Credit Limit'),
                    'type' => 'number',
                    'default' => '1000',
                    'validation' => 'required|numeric|min:0',
                    'attributes' => ['min' => '0', 'step' => '0.01'],
                    'help' => __('Default credit limit for new customers'),
                ],
                'enable_customer_blacklist' => [
                    'label' => __('Enable Customer Blacklist'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Enable blacklisting functionality for customers'),
                ],
                'auto_create_customer_on_first_purchase' => [
                    'label' => __('Auto Create Customer Record'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Automatically create customer record on first purchase'),
                ],
            ],
            'leads' => [
                'lead_code_prefix' => [
                    'label' => __('Lead Code Prefix'),
                    'type' => 'text',
                    'default' => 'LD',
                    'validation' => 'nullable|string|max:10',
                    'help' => __('Prefix for auto-generated lead codes (e.g., LD-001)'),
                ],
                'lead_code_start_number' => [
                    'label' => __('Starting Lead Number'),
                    'type' => 'number',
                    'default' => '1000',
                    'validation' => 'required|integer|min:1',
                    'attributes' => ['min' => '1'],
                    'help' => __('Starting number for lead code sequence'),
                ],
                'auto_generate_lead_codes' => [
                    'label' => __('Auto Generate Lead Codes'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Automatically generate unique codes for new leads'),
                ],
                'require_lead_assignment' => [
                    'label' => __('Require Lead Assignment'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Require all leads to be assigned to a user'),
                ],
                'auto_convert_qualified_leads' => [
                    'label' => __('Auto Convert Qualified Leads'),
                    'type' => 'switch',
                    'default' => false,
                    'validation' => 'boolean',
                    'help' => __('Automatically convert leads when marked as qualified'),
                ],
                'default_lead_source_id' => [
                    'label' => __('Default Lead Source'),
                    'type' => 'select',
                    'default' => null,
                    'validation' => 'nullable|exists:lead_sources,id',
                    'options_source' => 'lead_sources',
                    'help' => __('Default source for new leads if not specified'),
                ],
                'default_lead_status_id' => [
                    'label' => __('Default Lead Status'),
                    'type' => 'select',
                    'default' => null,
                    'validation' => 'nullable|exists:lead_statuses,id',
                    'options_source' => 'lead_statuses',
                    'help' => __('Default status for new leads'),
                ],
            ],
            'deals' => [
                'deal_code_prefix' => [
                    'label' => __('Deal Code Prefix'),
                    'type' => 'text',
                    'default' => 'DL',
                    'validation' => 'nullable|string|max:10',
                    'help' => __('Prefix for auto-generated deal codes (e.g., DL-001)'),
                ],
                'deal_code_start_number' => [
                    'label' => __('Starting Deal Number'),
                    'type' => 'number',
                    'default' => '1000',
                    'validation' => 'required|integer|min:1',
                    'attributes' => ['min' => '1'],
                    'help' => __('Starting number for deal code sequence'),
                ],
                'auto_generate_deal_codes' => [
                    'label' => __('Auto Generate Deal Codes'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Automatically generate unique codes for new deals'),
                ],
                'require_deal_assignment' => [
                    'label' => __('Require Deal Assignment'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Require all deals to be assigned to a user'),
                ],
                'default_deal_probability' => [
                    'label' => __('Default Deal Probability (%)'),
                    'type' => 'number',
                    'default' => '10',
                    'validation' => 'required|integer|min:0|max:100',
                    'attributes' => ['min' => '0', 'max' => '100'],
                    'help' => __('Default probability percentage for new deals'),
                ],
                'default_pipeline_id' => [
                    'label' => __('Default Pipeline'),
                    'type' => 'select',
                    'default' => null,
                    'validation' => 'nullable|exists:deal_pipelines,id',
                    'options_source' => 'deal_pipelines',
                    'help' => __('Default pipeline for new deals'),
                ],
                'notify_deal_stage_changes' => [
                    'label' => __('Notify Deal Stage Changes'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Send notifications when deal stages change'),
                ],
                'auto_close_won_deals' => [
                    'label' => __('Auto Close Won Deals'),
                    'type' => 'switch',
                    'default' => false,
                    'validation' => 'boolean',
                    'help' => __('Automatically set close date when deal is marked as won'),
                ],
            ],
            'tasks' => [
                'task_code_prefix' => [
                    'label' => __('Task Code Prefix'),
                    'type' => 'text',
                    'default' => 'TSK',
                    'validation' => 'nullable|string|max:10',
                    'help' => __('Prefix for auto-generated task codes (e.g., TSK-001)'),
                ],
                'task_code_start_number' => [
                    'label' => __('Starting Task Number'),
                    'type' => 'number',
                    'default' => '1000',
                    'validation' => 'required|integer|min:1',
                    'attributes' => ['min' => '1'],
                    'help' => __('Starting number for task code sequence'),
                ],
                'auto_generate_task_codes' => [
                    'label' => __('Auto Generate Task Codes'),
                    'type' => 'switch',
                    'default' => false,
                    'validation' => 'boolean',
                    'help' => __('Automatically generate unique codes for new tasks'),
                ],
                'require_task_assignment' => [
                    'label' => __('Require Task Assignment'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Require all tasks to be assigned to a user'),
                ],
                'default_task_priority_id' => [
                    'label' => __('Default Task Priority'),
                    'type' => 'select',
                    'default' => null,
                    'validation' => 'nullable|exists:task_priorities,id',
                    'options_source' => 'task_priorities',
                    'help' => __('Default priority for new tasks'),
                ],
                'default_task_status_id' => [
                    'label' => __('Default Task Status'),
                    'type' => 'select',
                    'default' => null,
                    'validation' => 'nullable|exists:task_statuses,id',
                    'options_source' => 'task_statuses',
                    'help' => __('Default status for new tasks'),
                ],
                'enable_task_reminders' => [
                    'label' => __('Enable Task Reminders'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Send reminder notifications for upcoming tasks'),
                ],
                'task_reminder_hours' => [
                    'label' => __('Task Reminder Hours'),
                    'type' => 'number',
                    'default' => '24',
                    'validation' => 'required|integer|min:1|max:168',
                    'attributes' => ['min' => '1', 'max' => '168'],
                    'help' => __('Hours before due date to send reminders (max 7 days)'),
                ],
                'enable_task_time_tracking' => [
                    'label' => __('Enable Time Tracking'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Enable time tracking features for tasks'),
                ],
            ],
            'notifications' => [
                'notify_task_assignments' => [
                    'label' => __('Notify Task Assignments'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Send notifications when tasks are assigned'),
                ],
                'notify_lead_assignments' => [
                    'label' => __('Notify Lead Assignments'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Send notifications when leads are assigned'),
                ],
                'notify_deal_assignments' => [
                    'label' => __('Notify Deal Assignments'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Send notifications when deals are assigned'),
                ],
                'notify_status_changes' => [
                    'label' => __('Notify Status Changes'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Send notifications when record statuses change'),
                ],
                'email_notification_frequency' => [
                    'label' => __('Email Notification Frequency'),
                    'type' => 'select',
                    'default' => 'immediate',
                    'validation' => 'required|in:immediate,daily,weekly,disabled',
                    'options' => [
                        'immediate' => __('Immediate'),
                        'daily' => __('Daily Digest'),
                        'weekly' => __('Weekly Digest'),
                        'disabled' => __('Disabled'),
                    ],
                    'help' => __('How often to send email notifications'),
                ],
            ],
            'display' => [
                'default_items_per_page' => [
                    'label' => __('Items Per Page'),
                    'type' => 'select',
                    'default' => '25',
                    'validation' => 'required|in:10,25,50,100',
                    'options' => [
                        '10' => '10',
                        '25' => '25',
                        '50' => '50',
                        '100' => '100',
                    ],
                    'help' => __('Default number of items to display per page in lists'),
                ],
                'default_view_mode' => [
                    'label' => __('Default View Mode'),
                    'type' => 'select',
                    'default' => 'list',
                    'validation' => 'required|in:list,kanban,card',
                    'options' => [
                        'list' => __('List View'),
                        'kanban' => __('Kanban Board'),
                        'card' => __('Card View'),
                    ],
                    'help' => __('Default view mode for records where applicable'),
                ],
                'enable_activity_timeline' => [
                    'label' => __('Enable Activity Timeline'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Show activity timeline on record detail pages'),
                ],
                'show_record_numbers' => [
                    'label' => __('Show Record Numbers'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Display auto-generated record numbers in lists'),
                ],
                'currency_symbol' => [
                    'label' => __('Default Currency Symbol'),
                    'type' => 'text',
                    'default' => '$',
                    'validation' => 'required|string|max:5',
                    'help' => __('Currency symbol for deal values and amounts'),
                ],
                'enable_kanban_drag_drop' => [
                    'label' => __('Enable Kanban Drag & Drop'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Allow dragging deals and tasks between stages'),
                ],
            ],
            'integration' => [
                'enable_api_access' => [
                    'label' => __('Enable API Access'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Allow external systems to access CRM data via API'),
                ],
                'api_rate_limit' => [
                    'label' => __('API Rate Limit (requests/minute)'),
                    'type' => 'number',
                    'default' => '60',
                    'validation' => 'required|integer|min:10|max:1000',
                    'attributes' => ['min' => '10', 'max' => '1000'],
                    'help' => __('Maximum API requests per minute per user'),
                ],
                'webhook_notifications' => [
                    'label' => __('Enable Webhook Notifications'),
                    'type' => 'switch',
                    'default' => false,
                    'validation' => 'boolean',
                    'help' => __('Send webhook notifications for CRM events'),
                ],
                'webhook_url' => [
                    'label' => __('Webhook URL'),
                    'type' => 'text',
                    'default' => '',
                    'validation' => 'nullable|url',
                    'help' => __('URL to receive webhook notifications'),
                ],
                'enable_lead_import' => [
                    'label' => __('Enable Lead Import'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Allow importing leads from CSV/Excel files'),
                ],
                'enable_contact_sync' => [
                    'label' => __('Enable Contact Sync'),
                    'type' => 'switch',
                    'default' => false,
                    'validation' => 'boolean',
                    'help' => __('Sync contacts with external systems (e.g., email providers)'),
                ],
            ],
            'security' => [
                'enable_field_level_permissions' => [
                    'label' => __('Enable Field-Level Permissions'),
                    'type' => 'switch',
                    'default' => false,
                    'validation' => 'boolean',
                    'help' => __('Control field visibility based on user permissions'),
                ],
                'enable_data_encryption' => [
                    'label' => __('Enable Data Encryption'),
                    'type' => 'switch',
                    'default' => false,
                    'validation' => 'boolean',
                    'help' => __('Encrypt sensitive customer data at rest'),
                ],
                'mask_sensitive_data' => [
                    'label' => __('Mask Sensitive Data'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Mask phone numbers and emails in lists for unauthorized users'),
                ],
                'require_approval_for_deletion' => [
                    'label' => __('Require Approval for Deletion'),
                    'type' => 'switch',
                    'default' => false,
                    'validation' => 'boolean',
                    'help' => __('Require manager approval before deleting important records'),
                ],
                'enable_audit_logging' => [
                    'label' => __('Enable Audit Logging'),
                    'type' => 'switch',
                    'default' => true,
                    'validation' => 'boolean',
                    'help' => __('Log all changes to CRM records for compliance'),
                ],
                'data_retention_days' => [
                    'label' => __('Data Retention (Days)'),
                    'type' => 'number',
                    'default' => '2555', // ~7 years
                    'validation' => 'required|integer|min:30|max:3650',
                    'attributes' => ['min' => '30', 'max' => '3650'],
                    'help' => __('Days to retain deleted records before permanent deletion'),
                ],
            ],
        ];
    }

    /**
     * Get module display name
     */
    public function getModuleName(): string
    {
        return __('CRM Settings');
    }

    /**
     * Get module description
     */
    public function getModuleDescription(): string
    {
        return __('Configure customer relationship management settings for leads, deals, tasks, and more');
    }

    /**
     * Get module icon
     */
    public function getModuleIcon(): string
    {
        return 'bx bx-user-check';
    }

    /**
     * Get settings sections
     */
    public function getSections(): array
    {
        return [
            [
                'id' => 'company',
                'title' => __('Company Settings'),
                'icon' => 'bx bx-buildings',
                'description' => __('Configure company management settings'),
            ],
            [
                'id' => 'contacts',
                'title' => __('Contact Settings'),
                'icon' => 'bx bx-user',
                'description' => __('Configure contact management and duplicate detection'),
            ],
            [
                'id' => 'customers',
                'title' => __('Customer Settings'),
                'icon' => 'bx bx-group',
                'description' => __('Configure customer management and payment settings'),
            ],
            [
                'id' => 'leads',
                'title' => __('Lead Settings'),
                'icon' => 'bx bx-user-plus',
                'description' => __('Configure lead management settings'),
            ],
            [
                'id' => 'deals',
                'title' => __('Deal Settings'),
                'icon' => 'bx bx-trophy',
                'description' => __('Configure deal pipeline and management settings'),
            ],
            [
                'id' => 'tasks',
                'title' => __('Task Settings'),
                'icon' => 'bx bx-task',
                'description' => __('Configure task management and tracking settings'),
            ],
            [
                'id' => 'notifications',
                'title' => __('Notification Settings'),
                'icon' => 'bx bx-bell',
                'description' => __('Configure notification preferences'),
            ],
            [
                'id' => 'display',
                'title' => __('Display Settings'),
                'icon' => 'bx bx-desktop',
                'description' => __('Configure display preferences and defaults'),
            ],
            [
                'id' => 'integration',
                'title' => __('Integration Settings'),
                'icon' => 'bx bx-cog',
                'description' => __('Configure API access and external integrations'),
            ],
            [
                'id' => 'security',
                'title' => __('Security & Privacy'),
                'icon' => 'bx bx-shield',
                'description' => __('Configure security settings and data protection'),
            ],
        ];
    }

    /**
     * Get validation rules
     */
    public function getValidationRules(): array
    {
        return [
            'company_code_prefix' => 'nullable|string|max:10',
            'company_code_start_number' => 'required|integer|min:1',
            'auto_generate_company_codes' => 'boolean',
            'require_company_assignment' => 'boolean',
            'enable_company_industry_categories' => 'boolean',
            'default_company_industry' => 'nullable|string|max:100',
            'contact_code_prefix' => 'nullable|string|max:10',
            'contact_code_start_number' => 'required|integer|min:1',
            'auto_generate_contact_codes' => 'boolean',
            'require_contact_assignment' => 'boolean',
            'enable_contact_duplicate_detection' => 'boolean',
            'duplicate_detection_fields' => 'required|in:email,phone,email_and_phone,email_or_phone',
            'require_company_for_contacts' => 'boolean',
            'default_contact_active_status' => 'boolean',
            'customer_code_prefix' => 'nullable|string|max:10',
            'customer_code_start_number' => 'required|integer|min:1',
            'auto_generate_customer_codes' => 'boolean',
            'default_customer_group_id' => 'nullable|exists:customer_groups,id',
            'default_payment_terms' => 'required|in:cod,net15,net30,net60,net90,prepaid',
            'default_credit_limit' => 'required|numeric|min:0',
            'enable_customer_blacklist' => 'boolean',
            'auto_create_customer_on_first_purchase' => 'boolean',
            'lead_code_prefix' => 'nullable|string|max:10',
            'lead_code_start_number' => 'required|integer|min:1',
            'auto_generate_lead_codes' => 'boolean',
            'require_lead_assignment' => 'boolean',
            'auto_convert_qualified_leads' => 'boolean',
            'default_lead_source_id' => 'nullable|exists:lead_sources,id',
            'default_lead_status_id' => 'nullable|exists:lead_statuses,id',
            'deal_code_prefix' => 'nullable|string|max:10',
            'deal_code_start_number' => 'required|integer|min:1',
            'auto_generate_deal_codes' => 'boolean',
            'require_deal_assignment' => 'boolean',
            'default_deal_probability' => 'required|integer|min:0|max:100',
            'default_pipeline_id' => 'nullable|exists:deal_pipelines,id',
            'notify_deal_stage_changes' => 'boolean',
            'auto_close_won_deals' => 'boolean',
            'task_code_prefix' => 'nullable|string|max:10',
            'task_code_start_number' => 'required|integer|min:1',
            'auto_generate_task_codes' => 'boolean',
            'require_task_assignment' => 'boolean',
            'default_task_priority_id' => 'nullable|exists:task_priorities,id',
            'default_task_status_id' => 'nullable|exists:task_statuses,id',
            'enable_task_reminders' => 'boolean',
            'task_reminder_hours' => 'required|integer|min:1|max:168',
            'enable_task_time_tracking' => 'boolean',
            'notify_task_assignments' => 'boolean',
            'notify_lead_assignments' => 'boolean',
            'notify_deal_assignments' => 'boolean',
            'notify_status_changes' => 'boolean',
            'email_notification_frequency' => 'required|in:immediate,daily,weekly,disabled',
            'default_items_per_page' => 'required|in:10,25,50,100',
            'default_view_mode' => 'required|in:list,kanban,card',
            'enable_activity_timeline' => 'boolean',
            'show_record_numbers' => 'boolean',
            'currency_symbol' => 'required|string|max:5',
            'enable_kanban_drag_drop' => 'boolean',
            'enable_api_access' => 'boolean',
            'api_rate_limit' => 'required|integer|min:10|max:1000',
            'webhook_notifications' => 'boolean',
            'webhook_url' => 'nullable|url',
            'enable_lead_import' => 'boolean',
            'enable_contact_sync' => 'boolean',
            'enable_field_level_permissions' => 'boolean',
            'enable_data_encryption' => 'boolean',
            'mask_sensitive_data' => 'boolean',
            'require_approval_for_deletion' => 'boolean',
            'enable_audit_logging' => 'boolean',
            'data_retention_days' => 'required|integer|min:30|max:3650',
        ];
    }

    /**
     * Get default values
     */
    public function getDefaults(): array
    {
        return [
            'company_code_prefix' => 'COM',
            'company_code_start_number' => '1000',
            'auto_generate_company_codes' => true,
            'require_company_assignment' => false,
            'enable_company_industry_categories' => true,
            'default_company_industry' => '',
            'contact_code_prefix' => 'CNT',
            'contact_code_start_number' => '1000',
            'auto_generate_contact_codes' => false,
            'require_contact_assignment' => false,
            'enable_contact_duplicate_detection' => true,
            'duplicate_detection_fields' => 'email_and_phone',
            'require_company_for_contacts' => false,
            'default_contact_active_status' => true,
            'customer_code_prefix' => 'CUS',
            'customer_code_start_number' => '1000',
            'auto_generate_customer_codes' => true,
            'default_customer_group_id' => null,
            'default_payment_terms' => 'cod',
            'default_credit_limit' => '1000',
            'enable_customer_blacklist' => true,
            'auto_create_customer_on_first_purchase' => true,
            'lead_code_prefix' => 'LD',
            'lead_code_start_number' => '1000',
            'auto_generate_lead_codes' => true,
            'require_lead_assignment' => true,
            'auto_convert_qualified_leads' => false,
            'default_lead_source_id' => null,
            'default_lead_status_id' => null,
            'deal_code_prefix' => 'DL',
            'deal_code_start_number' => '1000',
            'auto_generate_deal_codes' => true,
            'require_deal_assignment' => true,
            'default_deal_probability' => '10',
            'default_pipeline_id' => null,
            'notify_deal_stage_changes' => true,
            'auto_close_won_deals' => false,
            'task_code_prefix' => 'TSK',
            'task_code_start_number' => '1000',
            'auto_generate_task_codes' => false,
            'require_task_assignment' => true,
            'default_task_priority_id' => null,
            'default_task_status_id' => null,
            'enable_task_reminders' => true,
            'task_reminder_hours' => '24',
            'enable_task_time_tracking' => true,
            'notify_task_assignments' => true,
            'notify_lead_assignments' => true,
            'notify_deal_assignments' => true,
            'notify_status_changes' => true,
            'email_notification_frequency' => 'immediate',
            'default_items_per_page' => '25',
            'default_view_mode' => 'list',
            'enable_activity_timeline' => true,
            'show_record_numbers' => true,
            'currency_symbol' => '$',
            'enable_kanban_drag_drop' => true,
            'enable_api_access' => true,
            'api_rate_limit' => '60',
            'webhook_notifications' => false,
            'webhook_url' => '',
            'enable_lead_import' => true,
            'enable_contact_sync' => false,
            'enable_field_level_permissions' => false,
            'enable_data_encryption' => false,
            'mask_sensitive_data' => true,
            'require_approval_for_deletion' => false,
            'enable_audit_logging' => true,
            'data_retention_days' => '2555',
        ];
    }
}