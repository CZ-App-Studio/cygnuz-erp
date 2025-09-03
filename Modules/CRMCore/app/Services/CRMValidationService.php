<?php

namespace Modules\CRMCore\app\Services;

use App\Services\Settings\ModuleSettingsService;
use Illuminate\Validation\Rule;
use Modules\CRMCore\app\Models\Contact;

class CRMValidationService
{
    protected ModuleSettingsService $settingsService;

    public function __construct(ModuleSettingsService $settingsService)
    {
        $this->settingsService = $settingsService;
    }

    /**
     * Get validation rules for Contact creation/update
     */
    public function getContactValidationRules(?int $contactId = null): array
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'department' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'date_of_birth' => 'nullable|date',
            'lead_source_name' => 'nullable|string|max:255',
            'address_street' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:100',
            'address_state' => 'nullable|string|max:100',
            'address_postal_code' => 'nullable|string|max:20',
            'address_country' => 'nullable|string|max:100',
            'do_not_email' => 'boolean',
            'do_not_call' => 'boolean',
            'is_primary_contact_for_company' => 'boolean',
            'is_active' => 'boolean',
        ];

        // Email validation - temporarily disable unique constraint due to existing duplicates
        // TODO: Clean up duplicate contacts before re-enabling unique validation
        $rules['email_primary'] = 'nullable|email|max:255';
        $rules['email_secondary'] = 'nullable|email|max:255';

        /* Temporarily disabled due to existing duplicates in database
        $emailUniqueRule = Rule::unique('contacts', 'email_primary');
        if ($contactId) {
            $emailUniqueRule->ignore($contactId);
        }
        $rules['email_primary'] = ['nullable', 'email', 'max:255', $emailUniqueRule];
        */

        // Phone validation - temporarily disable unique constraint due to existing duplicates
        // TODO: Clean up duplicate contacts before re-enabling unique validation
        $rules['phone_primary'] = 'nullable|string|max:50';

        /* Temporarily disabled due to existing duplicates in database
        if ($this->settingsService->get('CRMCore', 'enable_contact_duplicate_detection', true)) {
            $detectionFields = $this->settingsService->get('CRMCore', 'duplicate_detection_fields', 'email_and_phone');

            if (in_array($detectionFields, ['phone', 'email_and_phone', 'email_or_phone'])) {
                $phoneUniqueRule = Rule::unique('contacts', 'phone_primary');
                if ($contactId) {
                    $phoneUniqueRule->ignore($contactId);
                }
                $rules['phone_primary'] = ['nullable', 'string', 'max:50', $phoneUniqueRule];
            } else {
                $rules['phone_primary'] = 'nullable|string|max:50';
            }
        } else {
            $rules['phone_primary'] = 'nullable|string|max:50';
        }
        */

        $rules['phone_mobile'] = 'nullable|string|max:50';
        $rules['phone_office'] = 'nullable|string|max:50';

        // Company requirement setting
        if ($this->settingsService->get('CRMCore', 'require_company_for_contacts', false)) {
            $rules['company_id'] = 'required|exists:companies,id';
        } else {
            $rules['company_id'] = 'nullable|exists:companies,id';
        }

        // Assignment requirement setting
        if ($this->settingsService->get('CRMCore', 'require_contact_assignment', false)) {
            $rules['assigned_to_user_id'] = 'required|exists:users,id';
        } else {
            $rules['assigned_to_user_id'] = 'nullable|exists:users,id';
        }

        // Code validation if provided
        if ($this->settingsService->get('CRMCore', 'auto_generate_contact_codes', false)) {
            $codeUniqueRule = Rule::unique('contacts', 'code');
            if ($contactId) {
                $codeUniqueRule->ignore($contactId);
            }
            $rules['code'] = ['nullable', 'string', 'max:20', $codeUniqueRule];
        }

        return $rules;
    }

    /**
     * Get validation rules for Company creation/update
     */
    public function getCompanyValidationRules(?int $companyId = null): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'website' => 'nullable|url|max:255',
            'phone_office' => 'nullable|string|max:50',
            'address_street' => 'nullable|string|max:255',
            'address_city' => 'nullable|string|max:100',
            'address_state' => 'nullable|string|max:100',
            'address_postal_code' => 'nullable|string|max:20',
            'address_country' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ];

        // Industry field validation
        if ($this->settingsService->get('CRMCore', 'enable_company_industry_categories', true)) {
            $rules['industry'] = 'nullable|string|max:100';
        }

        // Email uniqueness
        $emailUniqueRule = Rule::unique('companies', 'email_office');
        if ($companyId) {
            $emailUniqueRule->ignore($companyId);
        }
        $rules['email_office'] = ['nullable', 'email', 'max:255', $emailUniqueRule];

        // Assignment requirement
        if ($this->settingsService->get('CRMCore', 'require_company_assignment', false)) {
            $rules['assigned_to_user_id'] = 'required|exists:users,id';
        } else {
            $rules['assigned_to_user_id'] = 'nullable|exists:users,id';
        }

        // Code validation if provided
        if ($this->settingsService->get('CRMCore', 'auto_generate_company_codes', true)) {
            $codeUniqueRule = Rule::unique('companies', 'code');
            if ($companyId) {
                $codeUniqueRule->ignore($companyId);
            }
            $rules['code'] = ['nullable', 'string', 'max:20', $codeUniqueRule];
        }

        return $rules;
    }

    /**
     * Get validation rules for Lead creation/update
     */
    public function getLeadValidationRules(?int $leadId = null): array
    {
        $rules = [
            'first_name' => 'required|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'company_name' => 'nullable|string|max:255',
            'job_title' => 'nullable|string|max:255',
            'lead_source_id' => 'nullable|exists:lead_sources,id',
            'lead_status_id' => 'required|exists:lead_statuses,id',
            'description' => 'nullable|string',
            'value' => 'nullable|numeric|min:0',
        ];

        // Assignment requirement
        if ($this->settingsService->get('CRMCore', 'require_lead_assignment', true)) {
            $rules['assigned_to_user_id'] = 'required|exists:users,id';
        } else {
            $rules['assigned_to_user_id'] = 'nullable|exists:users,id';
        }

        // Code validation if provided
        if ($this->settingsService->get('CRMCore', 'auto_generate_lead_codes', true)) {
            $codeUniqueRule = Rule::unique('leads', 'code');
            if ($leadId) {
                $codeUniqueRule->ignore($leadId);
            }
            $rules['code'] = ['nullable', 'string', 'max:20', $codeUniqueRule];
        }

        return $rules;
    }

    /**
     * Get validation rules for Deal creation/update
     */
    public function getDealValidationRules(?int $dealId = null): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'value' => 'required|numeric|min:0',
            'probability' => 'nullable|integer|min:0|max:100',
            'close_date' => 'nullable|date|after_or_equal:today',
            'pipeline_id' => 'required|exists:deal_pipelines,id',
            'stage_id' => 'required|exists:deal_stages,id',
            'lead_id' => 'nullable|exists:leads,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'company_id' => 'nullable|exists:companies,id',
        ];

        // Assignment requirement
        if ($this->settingsService->get('CRMCore', 'require_deal_assignment', true)) {
            $rules['assigned_to_user_id'] = 'required|exists:users,id';
        } else {
            $rules['assigned_to_user_id'] = 'nullable|exists:users,id';
        }

        // Code validation if provided
        if ($this->settingsService->get('CRMCore', 'auto_generate_deal_codes', true)) {
            $codeUniqueRule = Rule::unique('deals', 'code');
            if ($dealId) {
                $codeUniqueRule->ignore($dealId);
            }
            $rules['code'] = ['nullable', 'string', 'max:20', $codeUniqueRule];
        }

        return $rules;
    }

    /**
     * Get validation rules for Task creation/update
     */
    public function getTaskValidationRules(?int $taskId = null): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'due_date' => 'nullable|date',
            'task_status_id' => 'required|exists:task_statuses,id',
            'task_priority_id' => 'nullable|exists:task_priorities,id',
            'taskable_id' => 'nullable|integer',
            'taskable_type' => 'nullable|string|max:255',
            'estimated_hours' => 'nullable|numeric|min:0',
            'parent_task_id' => 'nullable|exists:crm_tasks,id',
            'is_milestone' => 'boolean',
        ];

        // Assignment requirement
        if ($this->settingsService->get('CRMCore', 'require_task_assignment', true)) {
            $rules['assigned_to_user_id'] = 'required|exists:users,id';
        } else {
            $rules['assigned_to_user_id'] = 'nullable|exists:users,id';
        }

        // Time tracking fields
        if ($this->settingsService->get('CRMCore', 'enable_task_time_tracking', true)) {
            $rules['actual_hours'] = 'nullable|numeric|min:0';
        }

        // Code validation if provided
        if ($this->settingsService->get('CRMCore', 'auto_generate_task_codes', false)) {
            $codeUniqueRule = Rule::unique('crm_tasks', 'code');
            if ($taskId) {
                $codeUniqueRule->ignore($taskId);
            }
            $rules['code'] = ['nullable', 'string', 'max:20', $codeUniqueRule];
        }

        return $rules;
    }

    /**
     * Get validation rules for Customer creation/update
     */
    public function getCustomerValidationRules(?int $customerId = null): array
    {
        $rules = [
            'contact_id' => 'required|exists:contacts,id',
            'credit_limit' => 'nullable|numeric|min:0',
            'payment_terms' => 'required|in:cod,net15,net30,net60,net90,prepaid',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'tax_exempt' => 'boolean',
            'is_active' => 'boolean',
            'customer_group_id' => 'nullable|exists:customer_groups,id',
        ];

        // Code validation if provided
        if ($this->settingsService->get('CRMCore', 'auto_generate_customer_codes', true)) {
            $codeUniqueRule = Rule::unique('customers', 'code');
            if ($customerId) {
                $codeUniqueRule->ignore($customerId);
            }
            $rules['code'] = ['nullable', 'string', 'max:20', $codeUniqueRule];
        }

        return $rules;
    }

    /**
     * Check for duplicate contacts based on settings
     */
    public function checkContactDuplicates(array $data, ?int $excludeId = null): array
    {
        if (! $this->settingsService->get('CRMCore', 'enable_contact_duplicate_detection', true)) {
            return [];
        }

        $detectionFields = $this->settingsService->get('CRMCore', 'duplicate_detection_fields', 'email_and_phone');
        $duplicates = [];

        $query = Contact::query();
        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        switch ($detectionFields) {
            case 'email':
                if (! empty($data['email_primary'])) {
                    $duplicate = $query->where('email_primary', $data['email_primary'])->first();
                    if ($duplicate) {
                        $duplicates[] = [
                            'field' => 'email_primary',
                            'value' => $data['email_primary'],
                            'contact' => $duplicate,
                        ];
                    }
                }
                break;

            case 'phone':
                if (! empty($data['phone_primary'])) {
                    $duplicate = $query->where('phone_primary', $data['phone_primary'])->first();
                    if ($duplicate) {
                        $duplicates[] = [
                            'field' => 'phone_primary',
                            'value' => $data['phone_primary'],
                            'contact' => $duplicate,
                        ];
                    }
                }
                break;

            case 'email_and_phone':
                if (! empty($data['email_primary']) && ! empty($data['phone_primary'])) {
                    $duplicate = $query->where('email_primary', $data['email_primary'])
                        ->where('phone_primary', $data['phone_primary'])
                        ->first();
                    if ($duplicate) {
                        $duplicates[] = [
                            'field' => 'email_and_phone',
                            'value' => $data['email_primary'].' / '.$data['phone_primary'],
                            'contact' => $duplicate,
                        ];
                    }
                }
                break;

            case 'email_or_phone':
                if (! empty($data['email_primary'])) {
                    $duplicate = $query->where('email_primary', $data['email_primary'])->first();
                    if ($duplicate) {
                        $duplicates[] = [
                            'field' => 'email_primary',
                            'value' => $data['email_primary'],
                            'contact' => $duplicate,
                        ];
                    }
                }
                if (! empty($data['phone_primary']) && empty($duplicates)) {
                    $duplicate = $query->where('phone_primary', $data['phone_primary'])->first();
                    if ($duplicate) {
                        $duplicates[] = [
                            'field' => 'phone_primary',
                            'value' => $data['phone_primary'],
                            'contact' => $duplicate,
                        ];
                    }
                }
                break;
        }

        return $duplicates;
    }

    /**
     * Get default values for new records based on settings
     */
    public function getEntityDefaults(string $entity): array
    {
        $defaults = [];

        switch ($entity) {
            case 'contact':
                $defaults['is_active'] = $this->settingsService->get('CRMCore', 'default_contact_active_status', true);
                break;

            case 'customer':
                $defaults['credit_limit'] = $this->settingsService->get('CRMCore', 'default_credit_limit', 1000);
                $defaults['payment_terms'] = $this->settingsService->get('CRMCore', 'default_payment_terms', 'cod');
                $defaults['customer_group_id'] = $this->settingsService->get('CRMCore', 'default_customer_group_id', null);
                break;

            case 'company':
                if ($this->settingsService->get('CRMCore', 'enable_company_industry_categories', true)) {
                    $defaults['industry'] = $this->settingsService->get('CRMCore', 'default_company_industry', '');
                }
                break;

            case 'deal':
                $defaults['probability'] = $this->settingsService->get('CRMCore', 'default_deal_probability', 10);
                $defaults['pipeline_id'] = $this->settingsService->get('CRMCore', 'default_pipeline_id', null);
                break;

            case 'lead':
                $defaults['lead_source_id'] = $this->settingsService->get('CRMCore', 'default_lead_source_id', null);
                $defaults['lead_status_id'] = $this->settingsService->get('CRMCore', 'default_lead_status_id', null);
                break;

            case 'task':
                $defaults['task_priority_id'] = $this->settingsService->get('CRMCore', 'default_task_priority_id', null);
                $defaults['task_status_id'] = $this->settingsService->get('CRMCore', 'default_task_status_id', null);
                break;
        }

        return $defaults;
    }
}
