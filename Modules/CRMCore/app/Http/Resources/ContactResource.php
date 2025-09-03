<?php

namespace Modules\CRMCore\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ContactResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->first_name.' '.$this->last_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'mobile' => $this->mobile,
            'title' => $this->title,
            'department' => $this->department,
            'company' => $this->whenLoaded('company', function () {
                return new CompanyResource($this->company);
            }),
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'website' => $this->website,
            'linkedin' => $this->linkedin,
            'twitter' => $this->twitter,
            'facebook' => $this->facebook,
            'instagram' => $this->instagram,
            'description' => $this->description,
            'tags' => $this->tags,
            'is_primary' => $this->is_primary,
            'created_by' => $this->whenLoaded('createdBy', function () {
                return [
                    'id' => $this->createdBy->id,
                    'name' => $this->createdBy->name,
                ];
            }),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
