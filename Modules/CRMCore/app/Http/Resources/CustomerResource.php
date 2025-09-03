<?php

namespace Modules\CRMCore\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // Get contact information if loaded
        $contact = $this->relationLoaded('contact') ? $this->contact : null;
        $company = $contact && $contact->relationLoaded('company') ? $contact->company : null;

        return [
            'id' => $this->id,
            'customer_code' => $this->code,
            'name' => $contact ? $contact->first_name.' '.$contact->last_name : null,
            'email' => $contact ? $contact->email_primary : null,
            'phone' => $contact ? $contact->phone_primary : null,
            'mobile' => $contact ? $contact->phone_mobile : null,
            'customer_type' => $this->customer_type,
            'company_name' => $company ? $company->name : null,
            'tax_number' => $this->tax_number,
            'tax_exempt' => $this->tax_exempt,
            'business_registration' => $this->business_registration,

            // Address information from contact
            'billing_address' => $contact ? $contact->address_street : null,
            'billing_city' => $contact ? $contact->address_city : null,
            'billing_state' => $contact ? $contact->address_state : null,
            'billing_country' => $contact ? $contact->address_country : null,
            'billing_postal_code' => $contact ? $contact->address_postal_code : null,

            // Use same as billing for shipping (can be customized later)
            'shipping_address' => $contact ? $contact->address_street : null,
            'shipping_city' => $contact ? $contact->address_city : null,
            'shipping_state' => $contact ? $contact->address_state : null,
            'shipping_country' => $contact ? $contact->address_country : null,
            'shipping_postal_code' => $contact ? $contact->address_postal_code : null,

            // Customer specific fields
            'credit_limit' => $this->credit_limit,
            'credit_used' => $this->credit_used,
            'payment_terms' => $this->payment_terms,
            'discount_percentage' => $this->discount_percentage,
            'preferred_payment_method' => $this->preferred_payment_method,
            'preferred_delivery_method' => $this->preferred_delivery_method,

            // Purchase history
            'lifetime_value' => $this->lifetime_value,
            'first_purchase_date' => $this->first_purchase_date,
            'last_purchase_date' => $this->last_purchase_date,
            'purchase_count' => $this->purchase_count,
            'average_order_value' => $this->average_order_value,

            // Status fields
            'is_active' => $this->is_active,
            'is_blacklisted' => $this->is_blacklisted,
            'blacklist_reason' => $this->blacklist_reason,
            'notes' => $this->notes,
            'status' => $this->is_blacklisted ? 'blocked' : ($this->is_active ? 'active' : 'inactive'),

            // Relationships
            'customer_group' => $this->whenLoaded('customerGroup', function () {
                return $this->customerGroup ? [
                    'id' => $this->customerGroup->id,
                    'name' => $this->customerGroup->name,
                    'discount_percentage' => $this->customerGroup->discount_percentage,
                ] : null;
            }),
            'contact' => $this->whenLoaded('contact', function () {
                return $this->contact ? new ContactResource($this->contact) : null;
            }),
            'company' => $company ? [
                'id' => $company->id,
                'name' => $company->name,
                'website' => $company->website,
            ] : null,
            'assigned_to' => $contact && $contact->relationLoaded('assignedToUser') && $contact->assignedToUser ? [
                'id' => $contact->assignedToUser->id,
                'name' => $contact->assignedToUser->name,
                'email' => $contact->assignedToUser->email,
            ] : null,
            'tags' => $this->tags,
            'created_by' => $this->whenLoaded('createdBy', function () {
                return $this->createdBy ? [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                ] : null;
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
