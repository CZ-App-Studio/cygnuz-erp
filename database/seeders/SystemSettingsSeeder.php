<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // General Settings (Date, Time, Currency, Language)
            'general' => [
                // Date & Time Settings
                'default_timezone' => ['value' => 'Asia/Kolkata', 'type' => 'string', 'description' => 'Default timezone'],
                'date_format' => ['value' => 'd-m-Y', 'type' => 'string', 'description' => 'Date display format'],
                'time_format' => ['value' => 'h:i A', 'type' => 'string', 'description' => 'Time display format'],

                // Currency Settings
                'default_currency' => ['value' => 'USD', 'type' => 'string', 'description' => 'Default currency code'],
                'currency_symbol' => ['value' => '$', 'type' => 'string', 'description' => 'Currency symbol'],
                'currency_position' => ['value' => 'left', 'type' => 'string', 'description' => 'Currency symbol position (left or right)'],
                'decimal_places' => ['value' => 2, 'type' => 'integer', 'description' => 'Number of decimal places'],
                'thousand_separator' => ['value' => ',', 'type' => 'string', 'description' => 'Thousand separator'],
                'decimal_separator' => ['value' => '.', 'type' => 'string', 'description' => 'Decimal separator'],

                // System Preferences
                'default_language' => ['value' => 'en', 'type' => 'string', 'description' => 'Default language code'],
                'is_helper_text_enabled' => ['value' => true, 'type' => 'boolean', 'description' => 'Enable helper text throughout the application'],
            ],

            // Email Configuration
            'email' => [
                'mail_mailer' => ['value' => 'smtp', 'type' => 'string', 'description' => 'Mail driver (smtp, sendmail, mailgun, ses, postmark, log)'],
                'mail_host' => ['value' => 'smtp.gmail.com', 'type' => 'string', 'description' => 'SMTP server address'],
                'mail_port' => ['value' => '587', 'type' => 'string', 'description' => 'SMTP port'],
                'mail_encryption' => ['value' => 'tls', 'type' => 'string', 'description' => 'Email encryption method'],
                'mail_username' => ['value' => '', 'type' => 'string', 'description' => 'SMTP authentication username'],
                'mail_password' => ['value' => '', 'type' => 'string', 'description' => 'SMTP authentication password'],
                'mail_from_address' => ['value' => 'noreply@example.com', 'type' => 'string', 'description' => 'Default sender email address'],
                'mail_from_name' => ['value' => config('app.name', 'ERP System'), 'type' => 'string', 'description' => 'Default sender name'],
            ],

            // Company Settings
            'company' => [
                'company_name' => ['value' => 'CZ App Studio', 'type' => 'string', 'description' => 'Company name'],
                'company_email' => ['value' => 'support@czappstudio.com', 'type' => 'string', 'description' => 'Company email address'],
                'company_phone' => ['value' => '+91 85902 11605', 'type' => 'string', 'description' => 'Company phone number'],
                'company_website' => ['value' => 'https://czappstudio.com', 'type' => 'string', 'description' => 'Company website URL'],
                'company_address' => ['value' => '48/111, 2nd Floor, F-block, 2nd Street, Thanikachalam Nagar, Ponniammanmedu, Chennai 600110', 'type' => 'string', 'description' => 'Company address'],
                'company_registration_number' => ['value' => '', 'type' => 'string', 'description' => 'Company registration number'],
                'company_tax_number' => ['value' => '', 'type' => 'string', 'description' => 'Company tax ID'],
                'company_vat_number' => ['value' => '', 'type' => 'string', 'description' => 'Company VAT number'],
                'company_logo' => ['value' => '', 'type' => 'string', 'description' => 'Company logo filename'],
                'company_icon' => ['value' => '', 'type' => 'string', 'description' => 'Company icon/favicon'],
            ],

            // Attendance Settings
            'attendance' => [
                'enable_web_checkin' => ['value' => true, 'type' => 'boolean', 'description' => 'Allow employees to check-in via web'],
                'allow_multiple_checkin' => ['value' => false, 'type' => 'boolean', 'description' => 'Allow employees to check-in/out multiple times per day'],
                'grace_time_minutes' => ['value' => 15, 'type' => 'integer', 'description' => 'Minutes allowed after shift start'],
                'early_checkin_minutes' => ['value' => 30, 'type' => 'integer', 'description' => 'Minutes allowed before shift start'],
                'auto_checkout_hours' => ['value' => 24, 'type' => 'integer', 'description' => 'Automatically checkout after hours (0 = disabled)'],
                'week_start_day' => ['value' => 'monday', 'type' => 'string', 'description' => 'First day of attendance week'],
                'enable_overtime' => ['value' => true, 'type' => 'boolean', 'description' => 'Calculate overtime hours automatically'],
                'overtime_after_hours' => ['value' => '8', 'type' => 'string', 'description' => 'Hours before overtime starts'],
                'overtime_rate' => ['value' => '1.5', 'type' => 'string', 'description' => 'Multiplier for overtime pay calculation'],
                'require_overtime_approval' => ['value' => true, 'type' => 'boolean', 'description' => 'Manager approval needed for overtime'],
            ],

            // Maps & Location Settings
            'maps' => [
                'map_provider' => ['value' => 'google', 'type' => 'string', 'description' => 'Map provider (always google)'],
                'google_maps_api_key' => ['value' => '', 'type' => 'string', 'description' => 'Google Maps API key'],
                'default_latitude' => ['value' => '0', 'type' => 'string', 'description' => 'Default map center latitude'],
                'default_longitude' => ['value' => '0', 'type' => 'string', 'description' => 'Default map center longitude'],
                'default_zoom_level' => ['value' => '13', 'type' => 'string', 'description' => 'Default map zoom level'],
                'distance_unit' => ['value' => 'km', 'type' => 'string', 'description' => 'Unit for distance calculations (km, mi, m, ft)'],
            ],

            // Branding Settings
            'branding' => [
                'brand_name' => ['value' => 'Cygnuz ERP', 'type' => 'string', 'description' => 'Brand name displayed throughout the application'],
                'brand_tagline' => ['value' => 'Enterprise Resource Planning', 'type' => 'string', 'description' => 'Brand tagline or slogan'],
                'brand_logo_light' => ['value' => '', 'type' => 'string', 'description' => 'Light theme logo'],
                'brand_logo_dark' => ['value' => '', 'type' => 'string', 'description' => 'Dark theme logo'],
                'brand_favicon' => ['value' => '', 'type' => 'string', 'description' => 'Browser favicon'],
                'primary_color' => ['value' => '#f7ac19', 'type' => 'string', 'description' => 'Primary brand color'],
                'footer_text' => ['value' => '', 'type' => 'string', 'description' => 'Custom footer text'],
                'show_powered_by' => ['value' => true, 'type' => 'boolean', 'description' => 'Show powered by text in footer'],
                'show_footer_links' => ['value' => true, 'type' => 'boolean', 'description' => 'Show footer links'],
            ],
        ];

        foreach ($settings as $category => $items) {
            foreach ($items as $key => $data) {
                SystemSetting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $data['value'],
                        'type' => $data['type'],
                        'category' => $category,
                        'description' => $data['description'],
                        'is_public' => false,
                    ]
                );
            }
        }
    }
}
