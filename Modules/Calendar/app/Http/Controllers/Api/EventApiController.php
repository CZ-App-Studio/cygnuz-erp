<?php

namespace Modules\Calendar\app\Http\Controllers\Api;

use App\ApiClasses\Success;
use App\Enums\EventType;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Calendar\app\Models\Event;

// Assuming this class exists for standard responses

// For avatar URLs if needed

class EventApiController extends Controller
{
  /**
   * Fetch events for the authenticated user.
   * Response uses camelCase keys.
   */
  public function getAll(Request $request)
  {
    $skip = $request->input('skip', 0);
    $take = $request->input('take', 20); // Increased default take
    $userId = Auth::id();

    if (!$userId) {
      return response()->json(['message' => 'Unauthenticated.'], 401);
    }

    $query = Event::where(function ($query) use ($userId) {
      $query->where('created_by_id', $userId)
        ->orWhereHas('attendees', function ($q) use ($userId) {
          $q->where('users.id', $userId);
        });
    });

    // Date range filtering
    if ($request->filled('start') && $request->filled('end')) {
      $start = $request->start;
      $end = $request->end;
      $query->where(function ($q) use ($start, $end) {
        $q->whereBetween('start', [$start . ' 00:00:00', $end . ' 23:59:59']) // Check start date falls in range
        ->orWhereBetween('end', [$start . ' 00:00:00', $end . ' 23:59:59']) // Check end date falls in range
        ->orWhere(function ($sub) use ($start, $end) { // Check events that span the entire range
          $sub->where('start', '<=', $start . ' 00:00:00')
            ->where('end', '>=', $end . ' 23:59:59');
        });
        // Add check for events starting before range but ending within or after (handles multi-day correctly)
        $q->orWhere(function ($sub) use ($start, $end) {
          $sub->where('start', '<', $start . ' 00:00:00')
            ->where(function ($subQ) use ($start) {
              $subQ->whereNull('end')->orWhere('end', '>=', $start . ' 00:00:00'); // Also handles null end dates
            });
        });

      });
    }


    $totalCount = $query->count();

    $events = $query->with([
      'creator:id,first_name,last_name',
      'attendees:id,first_name,last_name,email,profile_picture',
      'client:id,name'
    ])
      ->skip($skip)
      ->take($take)
      ->orderBy('start', 'desc')
      ->get();

    // Manual serialization to camelCase
    $result = $events->map(function ($event) {
      $attendeeDetails = $event->attendees->map(function ($user) {
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        // Assuming User model has getProfilePicture method returning full URL or null
        $avatarUrl = $user->getProfilePicture(); // Simplify if method exists
        return [
          'id' => $user->id,
          'name' => $fullName ?: 'N/A',
          'email' => $user->email ?: 'N/A',
          'avatar' => $avatarUrl // Send null if no picture
        ];
      });

      return [
        'id' => $event->id,
        'title' => $event->title,
        'description' => $event->description,
        'start' => $event->start->toISOString(), // Use ISO 8601 format
        'end' => $event->end ? $event->end->toISOString() : null,
        'allDay' => $event->all_day,
        'color' => $event->color,
        'eventType' => $event->event_type->value, // Send Enum value
        'location' => $event->location,
        'meetingLink' => $event->meeting_link, // Added
        'clientId' => $event->client_id,       // Added
        'clientName' => $event->client?->name, // Added (null safe)
        'createdAt' => $event->created_at->toISOString(),
        'updatedAt' => $event->updated_at->toISOString(),
        'createdBy' => $event->creator ? ['id' => $event->creator->id, 'name' => trim(($event->creator->first_name ?? '') . ' ' . ($event->creator->last_name ?? ''))] : null,
        'attendees' => $attendeeDetails,
        'tenantId' => $event->tenant_id,
      ];
    });


    return Success::response(['totalCount' => $totalCount, 'values' => $result]);
  }

  /**
   * Create a new event via API.
   * Expects camelCase keys in request body.
   */
  public function create(Request $request)
  {
    $userId = Auth::id();
    if (!$userId) { return response()->json(['message' => 'Unauthenticated.'], 401); }

    // Validate incoming camelCase keys
    $validator = Validator::make($request->all(), [
      'eventTitle'      => 'required|string|max:255',
      'eventType'       => 'required|string|max:50',
      'eventStart'      => 'required|date', // Flexible date validation
      'eventEnd'        => 'nullable|date|after_or_equal:eventStart',
      'allDay'          => 'required|boolean', // Validates true, false, 1, 0
      'color'           => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'], // Optional hex color
      'attendeeIds'     => 'nullable|array',
      'attendeeIds.*'   => 'exists:users,id', // Validate attendee IDs exist
      'clientId'          => [
        'nullable',
        'exists:clients,id',
        Rule::requiredIf(fn () => $request->input('eventType') === EventType::CLIENT_APPOINTMENT->value)
      ],
      'eventLocation'     => 'nullable|string|max:255',
      'eventDescription'  => 'nullable|string|max:1000',
      'meetingLink'       => 'nullable|url|max:500',
    ]);

    if ($validator->fails()) {
      return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
    }
    $validatedData = $validator->validated();

    try {
      $event = null;
      DB::transaction(function () use ($validatedData, $userId, &$event) {
        // Map validated camelCase data to snake_case model attributes
        $event = Event::create([
          'title'        => $validatedData['eventTitle'],
          'event_type'   => $validatedData['eventType'],
          'start'        => $validatedData['eventStart'],
          'end'          => $validatedData['eventEnd'] ?? null,
          'all_day'      => $validatedData['allDay'],
          'color'        => $validatedData['color'] ?: null, // Store null if empty/default
          'location'     => $validatedData['eventLocation'] ?? null,
          'description'  => $validatedData['eventDescription'] ?? null,
          'client_id'    => $validatedData['clientId'] ?? null, // Save client_id
          'meeting_link' => $validatedData['meetingLink'] ?? null, // Save meeting link
          'created_by_id'=> $userId,
          'updated_by_id'=> $userId,
          // 'tenant_id' => $tenantId, // Set if using multi-tenancy
        ]);

        // Sync attendees using validated 'attendeeIds'
        $attendeeIds = $validatedData['attendeeIds'] ?? [];
        if (!in_array($userId, $attendeeIds)) { $attendeeIds[] = $userId; }
        $event->attendees()->sync($attendeeIds);
      });

      // Reload relations for consistent response structure
      $event->load(['creator:id,first_name,last_name', 'attendees:id,first_name,last_name,email,profile_picture', 'client:id,name']);

      // Manually serialize response to camelCase
      // Manually serialize response to camelCase
      $attendeeDetails = $event->attendees->map(function ($user) {
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $avatarUrl = $user->getProfilePicture();
        return ['id' => $user->id, 'name' => $fullName ?: 'N/A', 'email' => $user->email ?: 'N/A', 'avatar' => $avatarUrl];
      });

      $responseData = [
        'id' => $event->id, 'title' => $event->title, 'description' => $event->description,
        'start' => $event->start->toISOString(), 'end' => $event->end ? $event->end->toISOString() : null,
        'allDay' => $event->all_day, 'color' => $event->color, 'eventType' => $event->event_type->value,
        'location' => $event->location, 'meetingLink' => $event->meeting_link,
        'clientId' => $event->client_id, 'clientName' => $event->client?->name,
        'createdAt' => $event->created_at->toISOString(), 'updatedAt' => $event->updated_at->toISOString(),
        'createdBy' => $event->creator ? ['id' => $event->creator->id, 'name' => trim(($event->creator->first_name ?? '').' '.($event->creator->last_name ?? ''))] : null,
        'attendees' => $attendeeDetails,
      ];
      return Success::response($responseData);

    } catch (\Exception $e) {
      Log::error("API Error creating event: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
      return response()->json(['message' => 'Failed to create event.'], 500);
    }
  }

  /**
   * Update an existing event via API.
   * Expects camelCase keys in request body.
   */
  public function update(Request $request, $id)
  {
    $event = Event::find($id);
    if (!$event) { return response()->json(['message' => 'Event not found.'], 404); }
    $userId = Auth::id();
    if ($event->created_by_id !== $userId /* && !Auth::user()->isAdmin() */) { // Add admin check if needed
      return response()->json(['message' => 'Unauthorized action.'], 403);
    }

    // Validate incoming camelCase keys
    $validator = Validator::make($request->all(), [
      'eventTitle'      => 'required|string|max:255',
      'eventType'       => 'required|string|max:50',
      'eventStart'      => 'required|date',
      'eventEnd'        => 'nullable|date|after_or_equal:eventStart',
      'allDay'          => 'required|boolean',
      'color'           => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
      'attendeeIds'     => 'nullable|array',
      'attendeeIds.*'   => 'exists:users,id',
      'clientId'         => [
        'nullable',
        'exists:clients,id',
        Rule::requiredIf(fn () => $request->input('eventType') === EventType::CLIENT_APPOINTMENT->value)
      ],
      'eventLocation'     => 'nullable|string|max:255',
      'eventDescription'  => 'nullable|string|max:1000',
      'meetingLink'       => 'nullable|url|max:500',
    ]);

    if ($validator->fails()) { return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422); }
    $validatedData = $validator->validated();

    try {
      DB::transaction(function () use ($event, $validatedData, $userId) {
        // Map validated camelCase data to snake_case model attributes
        $event->update([
          'title'        => $validatedData['eventTitle'],
          'event_type'   => $validatedData['eventType'],
          'start'        => $validatedData['eventStart'],
          'end'          => $validatedData['eventEnd'] ?? null,
          'all_day'      => $validatedData['allDay'],
          'color'        => $validatedData['color'] ?: null,
          'location'     => $validatedData['eventLocation'] ?? null,
          'description'  => $validatedData['eventDescription'] ?? null,
          'client_id'   => $validatedData['clientId'] ?? null,
          'meeting_link'=> $validatedData['meetingLink'] ?? null,
          'updated_by_id'=> $userId,
        ]);
        // Sync attendees using validated 'attendeeIds'
        $attendeeIds = $validatedData['attendeeIds'] ?? [];
        $event->attendees()->sync($attendeeIds);
      });

      $event->load(['creator:id,first_name,last_name', 'attendees:id,first_name,last_name,email,profile_picture', 'client:id,name']);
      // Serialize response to camelCase
      $attendeeDetails = $event->attendees->map(function ($user) {
        $fullName = trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? ''));
        $avatarUrl = $user->getProfilePicture();
        return ['id' => $user->id, 'name' => $fullName ?: 'N/A', 'email' => $user->email ?: 'N/A', 'avatar' => $avatarUrl];
      });
      $responseData = [
        'id' => $event->id, 'title' => $event->title, 'description' => $event->description,
        'start' => $event->start->toISOString(), 'end' => $event->end ? $event->end->toISOString() : null,
        'allDay' => $event->all_day, 'color' => $event->color, 'eventType' => $event->event_type->value,
        'location' => $event->location, 'meetingLink' => $event->meeting_link,
        'clientId' => $event->client_id, 'clientName' => $event->client?->name,
        'createdAt' => $event->created_at->toISOString(), 'updatedAt' => $event->updated_at->toISOString(),
        'createdBy' => $event->creator ? ['id' => $event->creator->id, 'name' => trim(($event->creator->first_name ?? '').' '.($event->creator->last_name ?? ''))] : null,
        'attendees' => $attendeeDetails, 'tenantId' => $event->tenant_id,
      ];
      return Success::response($responseData);

    } catch (\Exception $e) {
      Log::error("API Error updating event ID {$id}: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
      return response()->json(['message' => 'Failed to update event.'], 500);
    }
  }

  /**
   * Delete an event via API.
   */
  public function delete($id)
  {
    $event = Event::find($id);
    if (!$event) { return response()->json(['message' => 'Event not found.'], 404); }
    $userId = Auth::id();
    if ($event->created_by_id !== $userId /* && !Auth::user()->isAdmin() */) {
      return response()->json(['message' => 'Unauthorized action.'], 403);
    }
    try {
      $event->delete(); // Use soft delete if enabled on model
      return Success::response(['message' => 'Event deleted successfully.']);
    } catch (\Exception $e) {
      Log::error("API Error deleting event ID {$id}: " . $e->getMessage());
      return response()->json(['message' => 'Failed to delete event.'], 500);
    }
  }
}
