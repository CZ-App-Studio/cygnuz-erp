<?php

namespace Modules\CRMCore\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaskResource extends JsonResource
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
            'due_date' => $this->due_date,
            'completed_at' => $this->completed_at,
            'reminder_at' => $this->reminder_at,
            'taskable_type' => $this->taskable_type,
            'taskable_id' => $this->taskable_id,
            'taskable' => $this->when($this->taskable, function () {
                $type = class_basename($this->taskable_type);
                switch ($type) {
                    case 'Lead':
                        return new LeadResource($this->taskable);
                    case 'Deal':
                        return new DealResource($this->taskable);
                    case 'Contact':
                        return new ContactResource($this->taskable);
                    case 'Company':
                        return new CompanyResource($this->taskable);
                    default:
                        return $this->taskable;
                }
            }),
            'status' => $this->whenLoaded('status', function () {
                return [
                    'id' => $this->status->id,
                    'name' => $this->status->name,
                    'color' => $this->status->color,
                ];
            }),
            'priority' => $this->whenLoaded('priority', function () {
                return [
                    'id' => $this->priority->id,
                    'name' => $this->priority->name,
                    'color' => $this->priority->color,
                ];
            }),
            'assigned_to' => $this->whenLoaded('assignedTo', function () {
                return [
                    'id' => $this->assignedTo->id,
                    'name' => $this->assignedTo->name,
                    'email' => $this->assignedTo->email,
                ];
            }),
            'project_id' => $this->project_id,
            'project_task_id' => $this->project_task_id,
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