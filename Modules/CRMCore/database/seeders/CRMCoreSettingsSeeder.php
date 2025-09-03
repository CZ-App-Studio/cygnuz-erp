<?php

namespace Modules\CRMCore\database\seeders;

use App\Models\ModuleSetting;
use Illuminate\Database\Seeder;

class CRMCoreSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // Company Management Settings
            'company_code_prefix' => 'COM',
            'company_code_start_number' => '1000',
            'auto_generate_company_codes' => true,
            'require_company_assignment' => false,
            'enable_company_industry_categories' => true,
            'default_company_industry' => '',

            // Contact Management Settings
            'contact_code_prefix' => 'CNT',
            'contact_code_start_number' => '1000',
            'auto_generate_contact_codes' => false,
            'require_contact_assignment' => false,
            'enable_contact_duplicate_detection' => true,
            'duplicate_detection_fields' => 'email_and_phone',
            'require_company_for_contacts' => false,
            'default_contact_active_status' => true,

            // Customer Management Settings
            'customer_code_prefix' => 'CUS',
            'customer_code_start_number' => '1000',
            'auto_generate_customer_codes' => true,
            'default_customer_group_id' => null,
            'default_payment_terms' => 'cod',
            'default_credit_limit' => '1000',
            'enable_customer_blacklist' => true,
            'auto_create_customer_on_first_purchase' => true,

            // Lead Management Settings
            'lead_code_prefix' => 'LD',
            'lead_code_start_number' => '1000',
            'auto_generate_lead_codes' => true,
            'require_lead_assignment' => true,
            'auto_convert_qualified_leads' => false,
            'default_lead_source_id' => null,
            'default_lead_status_id' => null,

            // Deal Management Settings
            'deal_code_prefix' => 'DL',
            'deal_code_start_number' => '1000',
            'auto_generate_deal_codes' => true,
            'require_deal_assignment' => true,
            'default_deal_probability' => '10',
            'default_pipeline_id' => null,
            'notify_deal_stage_changes' => true,
            'auto_close_won_deals' => false,

            // Task Management Settings
            'task_code_prefix' => 'TSK',
            'task_code_start_number' => '1000',
            'auto_generate_task_codes' => false,
            'require_task_assignment' => true,
            'default_task_priority_id' => null,
            'default_task_status_id' => null,
            'enable_task_reminders' => true,
            'task_reminder_hours' => '24',
            'enable_task_time_tracking' => true,

            // Notification Settings
            'notify_task_assignments' => true,
            'notify_lead_assignments' => true,
            'notify_deal_assignments' => true,
            'notify_status_changes' => true,
            'email_notification_frequency' => 'immediate',

            // Display Settings
            'default_items_per_page' => '25',
            'default_view_mode' => 'list',
            'enable_activity_timeline' => true,
            'show_record_numbers' => true,
            'currency_symbol' => '$',
            'enable_kanban_drag_drop' => true,

            // Integration Settings
            'enable_api_access' => true,
            'api_rate_limit' => '60',
            'webhook_notifications' => false,
            'webhook_url' => '',
            'enable_lead_import' => true,
            'enable_contact_sync' => false,

            // Security & Privacy Settings
            'enable_field_level_permissions' => false,
            'enable_data_encryption' => false,
            'mask_sensitive_data' => true,
            'require_approval_for_deletion' => false,
            'enable_audit_logging' => true,
            'data_retention_days' => '2555',
        ];

        foreach ($settings as $key => $value) {
            ModuleSetting::firstOrCreate(
                [
                    'module' => 'CRMCore',
                    'key' => $key,
                ],
                [
                    'value' => is_bool($value) ? ($value ? '1' : '0') : (string) $value,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
