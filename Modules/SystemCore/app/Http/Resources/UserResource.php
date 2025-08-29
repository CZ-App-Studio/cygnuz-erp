<?php

namespace Modules\SystemCore\app\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Modules\HRCore\app\Http\Resources\DesignationResource;
use Modules\HRCore\app\Http\Resources\DepartmentResource;
use Modules\HRCore\app\Http\Resources\ShiftResource;
use Modules\HRCore\app\Http\Resources\TeamResource;
use Modules\HRCore\app\Http\Resources\ManagerResource;

class UserResource extends JsonResource
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
      'employee_code' => $this->code,
      'first_name' => $this->first_name,
      'last_name' => $this->last_name,
      'full_name' => $this->getFullName(),
      'email' => $this->email,
      'phone' => $this->phone,
      'profile_picture' => $this->when(method_exists($this->resource, 'getProfilePicture'), function () {
        try {
          return $this->getProfilePicture();
        } catch (\Exception $e) {
          return null;
        }
      }),
      'gender' => $this->gender,
      'date_of_birth' => $this->dob,
      'date_of_joining' => $this->date_of_joining,
      'status' => $this->status,
      'role' => $this->whenLoaded('roles', function () {
        return $this->roles->first()?->name;
      }),
      'roles' => $this->whenLoaded('roles', function () {
        return $this->roles->pluck('name');
      }),
      'designation' => $this->whenLoaded('designation', function () {
        return new DesignationResource($this->designation);
      }),
      'department' => $this->whenLoaded('department', function () {
        return $this->designation ? new DepartmentResource($this->designation->department) : null;
      }),
      'shift' => $this->whenLoaded('shift', function () {
        return new ShiftResource($this->shift);
      }),
      'team' => $this->whenLoaded('team', function () {
        return new TeamResource($this->team);
      }),
      'reporting_to' => $this->whenLoaded('reportingTo', function () {
        return new ManagerResource($this->reportingTo);
      }),
      'address' => $this->address,
      'is_under_probation' => method_exists($this->resource, 'isUnderProbation') ? $this->isUnderProbation() : false,
      'probation_end_date' => $this->probation_end_date ?? null,
      'probation_status' => method_exists($this->resource, 'getProbationStatusDisplayAttribute') ? $this->getProbationStatusDisplayAttribute() : null,
    ];
  }
}
