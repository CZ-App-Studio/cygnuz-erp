<?php

namespace Modules\CRMCore\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DealResource extends JsonResource
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
            'title' => $this->title,
            'description' => $this->description,
            'value' => $this->value,
            'currency' => $this->currency,
            'expected_close_date' => $this->expected_close_date,
            'closed_at' => $this->closed_at,
            'probability' => $this->probability,
            'pipeline' => $this->whenLoaded('pipeline', function () {
                return [
                    'id' => $this->pipeline->id,
                    'name' => $this->pipeline->name,
                    'is_active' => $this->pipeline->is_active,
                ];
            }),
            'stage' => $this->whenLoaded('stage', function () {
                return [
                    'id' => $this->stage->id,
                    'name' => $this->stage->name,
                    'color' => $this->stage->color,
                    'position' => $this->stage->position,
                    'probability' => $this->stage->probability,
                ];
            }),
            'contact' => $this->whenLoaded('contact', function () {
                return new ContactResource($this->contact);
            }),
            'company' => $this->whenLoaded('company', function () {
                return new CompanyResource($this->company);
            }),
            'lead' => $this->whenLoaded('lead', function () {
                return new LeadResource($this->lead);
            }),
            'assigned_to' => $this->whenLoaded('assignedTo', function () {
                return [
                    'id' => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                    'email' => $this->assignedTo->email,
                ];
            }),
            'tags' => $this->tags,
            'won_at' => $this->won_at,
            'lost_at' => $this->lost_at,
            'lost_reason' => $this->lost_reason,
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