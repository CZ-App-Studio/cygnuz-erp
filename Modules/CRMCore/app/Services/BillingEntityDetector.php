<?php

namespace Modules\CRMCore\app\Services;

use Modules\CRMCore\app\Models\Company;
use Modules\CRMCore\app\Models\Contact;
use Modules\CRMCore\app\Models\Customer;

class BillingEntityDetector
{
    /**
     * Detect the billing entity based on available data
     * Priority: Customer > Company > Contact
     * 
     * @param array $data Array containing possible entity IDs
     * @return object|null
     */
    public function detect(array $data)
    {
        // Check for customer_id first (highest priority)
        if (!empty($data['customer_id'])) {
            $customer = Customer::find($data['customer_id']);
            if ($customer) {
                return [
                    'entity' => $customer,
                    'type' => 'customer',
                    'is_b2b' => $customer->isB2B(),
                    'billing_info' => $this->getCustomerBillingInfo($customer)
                ];
            }
        }
        
        // Check for company_id (B2B scenario)
        if (!empty($data['company_id'])) {
            $company = Company::find($data['company_id']);
            if ($company) {
                return [
                    'entity' => $company,
                    'type' => 'company',
                    'is_b2b' => true,
                    'billing_info' => $this->getCompanyBillingInfo($company)
                ];
            }
        }
        
        // Check for contact_id (individual scenario)
        if (!empty($data['contact_id'])) {
            $contact = Contact::find($data['contact_id']);
            if ($contact) {
                // Check if contact has a customer record
                if ($contact->customer) {
                    return [
                        'entity' => $contact->customer,
                        'type' => 'customer',
                        'is_b2b' => $contact->customer->isB2B(),
                        'billing_info' => $this->getCustomerBillingInfo($contact->customer)
                    ];
                }
                
                // Return contact if no customer record exists yet
                return [
                    'entity' => $contact,
                    'type' => 'contact',
                    'is_b2b' => $contact->company_id !== null,
                    'billing_info' => $this->getContactBillingInfo($contact)
                ];
            }
        }
        
        // Handle walk-in customer data
        if (!empty($data['customer_name']) || !empty($data['customer_email'])) {
            return [
                'entity' => null,
                'type' => 'walk_in',
                'is_b2b' => false,
                'billing_info' => [
                    'name' => $data['customer_name'] ?? 'Walk-in Customer',
                    'email' => $data['customer_email'] ?? '',
                    'phone' => $data['customer_phone'] ?? '',
                    'address' => $this->formatWalkInAddress($data),
                    'tax_number' => $data['tax_number'] ?? '',
                    'tax_exempt' => $data['tax_exempt'] ?? false,
                ]
            ];
        }
        
        return null;
    }
    
    /**
     * Detect billing entity from a field order
     */
    public function detectFromFieldOrder($fieldOrder)
    {
        return $this->detect([
            'customer_id' => $fieldOrder->customer_id,
            'company_id' => $fieldOrder->company_id,
            'contact_id' => $fieldOrder->contact_id,
            'customer_name' => $fieldOrder->customer_name,
            'customer_email' => $fieldOrder->customer_email,
            'customer_phone' => $fieldOrder->customer_phone,
            'billing_address' => $fieldOrder->billing_address,
            'billing_city' => $fieldOrder->billing_city,
            'billing_state' => $fieldOrder->billing_state,
            'billing_postal_code' => $fieldOrder->billing_postal_code,
            'billing_country' => $fieldOrder->billing_country,
            'tax_number' => $fieldOrder->tax_number,
            'tax_exempt' => $fieldOrder->tax_exempt,
        ]);
    }
    
    /**
     * Get billing information for a customer
     */
    protected function getCustomerBillingInfo(Customer $customer)
    {
        $contact = $customer->contact;
        $company = $contact ? $contact->company : null;
        
        return [
            'name' => $customer->getDisplayNameAttribute(),
            'email' => $customer->getEmailAttribute(),
            'phone' => $customer->getPhoneAttribute(),
            'address' => $customer->getAddressAttribute(),
            'company_name' => $company ? $company->name : null,
            'tax_number' => $customer->tax_number,
            'tax_exempt' => $customer->tax_exempt,
            'payment_terms' => $customer->payment_terms,
            'credit_limit' => $customer->credit_limit,
            'discount_percentage' => $customer->discount_percentage,
        ];
    }
    
    /**
     * Get billing information for a company
     */
    protected function getCompanyBillingInfo(Company $company)
    {
        return [
            'name' => $company->name,
            'email' => $company->email_office,
            'phone' => $company->phone_office,
            'address' => $this->formatCompanyAddress($company),
            'company_name' => $company->name,
            'tax_number' => null, // Companies might have tax info in different field
            'tax_exempt' => false,
            'payment_terms' => 'net30', // Default for B2B
            'credit_limit' => null,
            'discount_percentage' => 0,
        ];
    }
    
    /**
     * Get billing information for a contact
     */
    protected function getContactBillingInfo(Contact $contact)
    {
        return [
            'name' => $contact->getFullNameAttribute(),
            'email' => $contact->email_primary,
            'phone' => $contact->phone_primary ?: $contact->phone_mobile,
            'address' => $this->formatContactAddress($contact),
            'company_name' => $contact->company ? $contact->company->name : null,
            'tax_number' => null,
            'tax_exempt' => false,
            'payment_terms' => 'cod', // Default for new contacts
            'credit_limit' => 0,
            'discount_percentage' => 0,
        ];
    }
    
    /**
     * Format company address
     */
    protected function formatCompanyAddress(Company $company)
    {
        $parts = array_filter([
            $company->address_street,
            $company->address_city,
            $company->address_state,
            $company->address_postal_code,
            $company->address_country,
        ]);
        
        return implode(', ', $parts);
    }
    
    /**
     * Format contact address
     */
    protected function formatContactAddress(Contact $contact)
    {
        $parts = array_filter([
            $contact->address_street,
            $contact->address_city,
            $contact->address_state,
            $contact->address_postal_code,
            $contact->address_country,
        ]);
        
        return implode(', ', $parts);
    }
    
    /**
     * Format walk-in customer address
     */
    protected function formatWalkInAddress(array $data)
    {
        $parts = array_filter([
            $data['billing_address'] ?? $data['delivery_address'] ?? '',
            $data['billing_city'] ?? $data['delivery_city'] ?? '',
            $data['billing_state'] ?? $data['delivery_state'] ?? '',
            $data['billing_postal_code'] ?? $data['delivery_postal_code'] ?? '',
            $data['billing_country'] ?? $data['delivery_country'] ?? '',
        ]);
        
        return implode(', ', $parts);
    }
    
    /**
     * Create or get customer from billing entity
     */
    public function ensureCustomerExists($billingEntity)
    {
        // If already a customer, return it
        if ($billingEntity['type'] === 'customer') {
            return $billingEntity['entity'];
        }
        
        // If contact without customer, create customer
        if ($billingEntity['type'] === 'contact' && $billingEntity['entity']) {
            $contact = $billingEntity['entity'];
            
            // Check if customer already exists
            if ($contact->customer) {
                return $contact->customer;
            }
            
            // Create new customer
            return Customer::createFromFirstPurchase($contact->id);
        }
        
        // For walk-in, create contact and customer
        if ($billingEntity['type'] === 'walk_in') {
            $billingInfo = $billingEntity['billing_info'];
            
            // Create contact
            $nameParts = explode(' ', $billingInfo['name']);
            $contact = Contact::create([
                'first_name' => $nameParts[0] ?? $billingInfo['name'],
                'last_name' => $nameParts[1] ?? '',
                'email_primary' => $billingInfo['email'],
                'phone_primary' => $billingInfo['phone'],
                'is_active' => true,
            ]);
            
            // Create customer
            return Customer::createFromFirstPurchase($contact->id);
        }
        
        // For company, find or create associated customer
        if ($billingEntity['type'] === 'company' && $billingEntity['entity']) {
            $company = $billingEntity['entity'];
            
            // Find primary contact for company
            $primaryContact = Contact::where('company_id', $company->id)
                ->where('is_primary_contact_for_company', true)
                ->first();
            
            if (!$primaryContact) {
                // Find any contact for this company
                $primaryContact = Contact::where('company_id', $company->id)->first();
            }
            
            if ($primaryContact) {
                if ($primaryContact->customer) {
                    return $primaryContact->customer;
                }
                
                return Customer::createFromFirstPurchase($primaryContact->id);
            }
        }
        
        return null;
    }
}