<?php
namespace Modules\Calendar\app\Http\Controllers;

use App\Enums\EventType;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Modules\Calendar\app\Models\Event;

class EventController extends Controller
{
  public function index()
  {
    $users = User::where('status', 'active') // Adjust status field/value if needed
      ->select('id', 'first_name', 'last_name', 'profile_picture') // profile_picture optional for select2
      ->orderBy('first_name')
      ->get();

    // Use the EventType Enum cases
    $eventTypes = EventType::cases();

    // Get available related entity types for polymorphic relationship
    $relatedEntityTypes = [];


    if (class_exists('Modules\CRMCore\app\Models\Company')) {
      $relatedEntityTypes['Modules\CRMCore\app\Models\Company'] = __('Company');
    }
    if (class_exists('Modules\CRMCore\app\Models\Contact')) {
      $relatedEntityTypes['Modules\CRMCore\app\Models\Contact'] = __('Contact');
    }
    if (class_exists('Modules\PMCore\app\Models\Project')) {
      $relatedEntityTypes['Modules\PMCore\app\Models\Project'] = __('Project');
    }

    // Legacy clients for backward compatibility (can be removed once frontend is updated)
    //TODO: Need to map client
    $clients = [];

    // Use a distinct view name for clarity
    return view('calendar::calendar.index', compact('users', 'eventTypes', 'clients', 'relatedEntityTypes'));
  }

  public function eventsAjax(Request $request)
  {
    $request->validate([
      'start' => 'required|date',
      'end' => 'required|date|after_or_equal:start',
    ]);
    $start = $request->input('start');
    $end = $request->input('end');
    $userId = Auth::id();

    // Fetch events created by or assigned to the user
    $events = Event::where(function ($query) use ($userId) {
      $query->where('created_by_id', $userId)
        ->orWhereHas('attendees', function ($q) use ($userId) {
          $q->where('users.id', $userId);
        });
    })
      ->where(function ($query) use ($start, $end) {
        // Standard date range filtering logic
        $query->whereBetween('start', [$start, $end])
          ->orWhereBetween('end', [$start, $end])
          ->orWhere(function ($q) use ($start, $end) {
          $q->where('start', '<=', $start)
            ->where(function ($subQ) use ($start, $end) {
              // Handle events with no end date correctly
              $subQ->whereNull('end')->orWhere('end', '>=', $start);
            })
            // Also handle events that span the whole range
            ->orWhere(function ($spanQ) use ($start, $end) {
              $spanQ->where('start', '<=', $start)->where('end', '>=', $end);
            });
        });
      })
      ->with(['attendees:id', 'related'])// Eager load attendees and related entity
      ->get([
        'id',
        'title',
        'start',
        'end',
        'all_day',
        'color', // Include color if manual override is used
        'event_type',
        'description',
        'location',
        'related_type',
        'related_id',
        'meeting_link'
      ]);

    // Format for FullCalendar, ensuring necessary extendedProps
    $formattedEvents = $events->map(function ($event) {
      // Get default color from Enum if no manual color set
      $defaultColor = $event->event_type ? $event->event_type->defaultColor() : '#6c757d';
      $finalColor = $event->color ?: $defaultColor;

      return [
        'id' => $event->id,
        'title' => $event->title,
        'start' => $event->start->toIso8601String(),
        'end' => $event->end ? $event->end->toIso8601String() : null,
        'allDay' => $event->all_day,
        'color' => $event->color, // Pass manual color if set
        'borderColor' => $finalColor, // Use border/background for better theme compatibility
        'backgroundColor' => $finalColor,
        'extendedProps' => [
          'eventType' => $event->event_type->value, // Send Enum value
          'description' => $event->description,
          'location' => $event->location, // Kept 'location' for physical places
          'meetingLink' => $event->meeting_link, // Added meeting link
          'attendeeIds' => $event->attendees->pluck('id')->toArray(),
          'relatedType' => $event->related_type, // Polymorphic type
          'relatedId' => $event->related_id, // Polymorphic ID
          'relatedName' => $event->related?->name ?? null, // Related entity name (if it has name attribute)
          // Legacy fields for backward compatibility (can be removed later)
          'clientId' => $event->related_type === 'Modules\FieldManager\app\Models\Client' ? $event->related_id : null,
          'clientName' => $event->related_type === 'Modules\FieldManager\app\Models\Client' ? ($event->related?->name ?? null) : null,
        ]
      ];
    });
    return response()->json($formattedEvents);
  }

  public function getEventAjax($id)
  {
    $event = Event::with([
      'attendees:id,first_name,last_name,email,code,profile_picture',
      'related'
    ])->find($id);
    if (!$event) {
      return response()->json(['message' => 'Event not found.'], 404);
    }
    // Optional: Add authorization check if needed

    $attendeeHtmlList = $event->attendees->map(function ($user) {
      try {
        return view('_partials._profile-avatar', ['user' => $user])->render();
      } catch (\Exception $e) {
        Log::error("Error rendering profile avatar partial for user ID {$user->id}: " . $e->getMessage());
        // Fallback display if rendering fails
        return '<li class="text-danger mb-2">Error loading attendee</li>';
      }
    })->all();

    // Generate preview data for stacked avatars
    $attendeePreviews = $event->attendees->take(4)->map(function ($user) {
      return [
        'id' => $user->id,
        'name' => $user->getFullName(),
        'avatar' => $user->getProfilePicture(),
        'initials' => $user->getInitials(),
        'background_color' => $this->generateInitialBackground($user->id)
      ];
    })->toArray();

    // Prepare data matching the frontend modal form field names/IDs
    $eventData = [
      'id' => $event->id,
      'event_title' => $event->title,
      'event_type' => $event->event_type->value, // Send Enum value
      'event_start' => $event->start->format('Y-m-d\TH:i:s'),
      'event_end' => $event->end ? $event->end->format('Y-m-d\TH:i:s') : null,
      'all_day' => $event->all_day,
      'attendee_ids' => $event->attendees->pluck('id')->toArray(),
      'attendeePreviews' => $attendeePreviews,
      'attendees_html' => $attendeeHtmlList,
      'event_location' => $event->location, // Physical location/room
      'event_description' => $event->description,
      'color' => $event->color, // Manually set color override
      'related_type' => $event->related_type, // Polymorphic type
      'related_id' => $event->related_id, // Polymorphic ID
      'related_name' => $event->related?->name ?? null, // Related entity name
      // Legacy fields for backward compatibility
      'client_id' => $event->related_type === 'Modules\FieldManager\app\Models\Client' ? $event->related_id : null,
      'client_name' => $event->related_type === 'Modules\FieldManager\app\Models\Client' ? ($event->related?->name ?? null) : null,
      'meeting_link' => $event->meeting_link, // Added
    ];
    return response()->json($eventData);
  }

  public function store(Request $request)
  {
    $validator = Validator::make($request->all(), [
      'event_title' => 'required|string|max:255',
      'event_type' => ['required', Rule::in(array_column(EventType::cases(), 'value'))],
      'event_start' => 'required|date',
      'event_end' => 'nullable|date|after_or_equal:event_start',
      'all_day' => 'required|boolean', // Expect 0 or 1
      'attendee_ids' => 'nullable|array',
      'attendee_ids.*' => 'exists:users,id',
      'event_location' => 'nullable|string|max:255',
      'event_description' => 'nullable|string|max:1000',
      'color' => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
      'related_type' => 'nullable|string|in:App\Models\Client,App\Models\Company,App\Models\Contact,Modules\PMCore\app\Models\Project',
      'related_id' => 'nullable|integer|min:1',
      // Legacy client_id for backward compatibility (can be removed later)
      'client_id' => [
        'nullable',
        'exists:clients,id',
        Rule::requiredIf(fn() => $request->input('event_type') === EventType::CLIENT_APPOINTMENT->value)
      ],
      'meeting_link' => 'nullable|url|max:500', // Validate meeting link
    ]);

    if ($validator->fails()) {
      return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
    }
    $validatedData = $validator->validated();
    $userId = Auth::id();

    Log::info('Store Data: ' . json_encode($validatedData));

    try {
      // Handle polymorphic relationship data
      $relatedType = $validatedData['related_type'] ?? null;
      $relatedId = $validatedData['related_id'] ?? null;

      // Legacy support: if client_id is provided but no related fields, use it
      if (!$relatedType && !$relatedId && isset($validatedData['client_id'])) {
        $relatedType = 'Modules\FieldManager\app\Models\Client';
        $relatedId = $validatedData['client_id'];
      }

      DB::transaction(function () use ($validatedData, $userId, $relatedType, $relatedId) {
        $event = Event::create([
          'title' => $validatedData['event_title'],
          'event_type' => $validatedData['event_type'], // Already an Enum instance via validation
          'start' => $validatedData['event_start'],
          'end' => $validatedData['event_end'] ?? null,
          'all_day' => $validatedData['all_day'],
          'location' => $validatedData['event_location'] ?? null,
          'description' => $validatedData['event_description'] ?? null,
          'color' => $validatedData['color'] ?: null,
          'related_type' => $relatedType,
          'related_id' => $relatedId,
          'meeting_link' => $validatedData['meeting_link'] ?? null, // Save meeting link
          'created_by_id' => $userId,
          'updated_by_id' => $userId,
        ]);
        $attendeeIds = $validatedData['attendee_ids'] ?? [];
        if (!in_array($userId, $attendeeIds)) {
          $attendeeIds[] = $userId;
        }
        $event->attendees()->sync($attendeeIds);
      });
      return response()->json(['message' => 'Event created successfully.'], 201);
    } catch (\Exception $e) {
      Log::error("Error creating event: " . $e->getMessage());
      return response()->json(['message' => 'Failed to create event.'], 500);
    }
  }

  public function update(Request $request, $id)
  {
    $event = Event::findOrFail($id);
    $userId = Auth::id();
    /*   if ($event->created_by_id !== $userId && !Auth::user()->isAdmin()) { // Example Authorization
         return response()->json(['message' => 'Unauthorized.'], 403);
       }*/

    $validator = Validator::make($request->all(), [
      'event_title' => 'required|string|max:255',
      'event_type' => ['required', Rule::in(array_column(EventType::cases(), 'value'))],
      'event_start' => 'required|date',
      'event_end' => 'nullable|date|after_or_equal:event_start',
      'all_day' => 'required|boolean',
      'attendee_ids' => 'nullable|array',
      'attendee_ids.*' => 'exists:users,id',
      'event_location' => 'nullable|string|max:255',
      'event_description' => 'nullable|string|max:1000',
      'color' => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'],
      'related_type' => 'nullable|string|in:App\Models\Client,App\Models\Company,App\Models\Contact,Modules\PMCore\app\Models\Project',
      'related_id' => 'nullable|integer|min:1',
      // Legacy client_id for backward compatibility (can be removed later)
      'client_id' => [
        'nullable',
        'exists:clients,id',
        Rule::requiredIf(fn() => $request->input('event_type') === EventType::CLIENT_APPOINTMENT->value)
      ],
      'meeting_link' => 'nullable|url|max:500',
    ]);

    if ($validator->fails()) {
      return response()->json(['message' => 'Validation failed.', 'errors' => $validator->errors()], 422);
    }
    $validatedData = $validator->validated();

    try {
      // Handle polymorphic relationship data
      $relatedType = $validatedData['related_type'] ?? null;
      $relatedId = $validatedData['related_id'] ?? null;

      // Legacy support: if client_id is provided but no related fields, use it
      if (!$relatedType && !$relatedId && isset($validatedData['client_id'])) {
        $relatedType = 'Modules\FieldManager\app\Models\Client';
        $relatedId = $validatedData['client_id'];
      }

      DB::transaction(function () use ($event, $validatedData, $userId, $relatedType, $relatedId) {
        $event->update([
          'title' => $validatedData['event_title'],
          'event_type' => $validatedData['event_type'],
          'start' => $validatedData['event_start'],
          'end' => $validatedData['event_end'] ?? null,
          'all_day' => $validatedData['all_day'],
          'location' => $validatedData['event_location'] ?? null,
          'description' => $validatedData['event_description'] ?? null,
          'color' => $validatedData['color'] ?: null,
          'related_type' => $relatedType,
          'related_id' => $relatedId,
          'meeting_link' => $validatedData['meeting_link'] ?? null,
          'updated_by_id' => $userId,
        ]);
        $attendeeIds = $validatedData['attendee_ids'] ?? [];
        // Decide if creator must remain an attendee on update
        // if (!in_array($userId, $attendeeIds)) { $attendeeIds[] = $userId; }
        $event->attendees()->sync($attendeeIds);
      });
      return response()->json(['message' => 'Event updated successfully.']);
    } catch (\Exception $e) {
      Log::error("Error updating event ID {$id}: " . $e->getMessage());
      return response()->json(['message' => 'Failed to update event.'], 500);
    }
  }

  /**
   * Delete an event
   */
  public function destroy($id)
  {
    try {
      $event = Event::findOrFail($id);
      $userId = Auth::id();

      // Optional: Add authorization check if needed
      /* if ($event->created_by_id !== $userId && !Auth::user()->isAdmin()) {
         return response()->json(['message' => 'Unauthorized.'], 403);
       }*/

      DB::transaction(function () use ($event) {
        // First detach all attendees
        $event->attendees()->detach();
        // Then delete the event
        $event->delete();
      });

      return response()->json(['message' => 'Event deleted successfully.']);
    } catch (\Exception $e) {
      Log::error("Error deleting event ID {$id}: " . $e->getMessage());
      return response()->json(['message' => 'Failed to delete event.'], 500);
    }
  }

  /**
   * Search for clients via AJAX for Select2.
   */
  public function searchClientsAjax(Request $request): JsonResponse
  {
    $searchTerm = $request->input('q', ''); // Select2 search term parameter is usually 'q'
    $page = $request->input('page', 1); // Select2 pagination parameter
    $resultsPerPage = 15; // Number of results per page

    //TODO: Need to map client
    $clients = [];

    // Format for Select2 AJAX response
    $formattedClients = $clients->map(function ($client) {
      return [
        'id' => $client->id,
        'text' => $client->name . ($client->email ? " ({$client->email})" : '') // Display format in dropdown
      ];
    });

    return response()->json([
      'results' => $formattedClients,
      'pagination' => [
        'more' => $clients->hasMorePages() // Indicate if there are more results
      ]
    ]);
  }

  /**
   * Search for related entities via AJAX for polymorphic relationship.
   */
  public function searchRelatedEntities(Request $request): JsonResponse
  {
    $entityType = $request->input('type', '');
    $searchTerm = $request->input('q', '');
    $limit = $request->input('limit', 20);

    $results = [];

    try {
      switch ($entityType) {

        case 'Modules\CRMCore\app\Models\Company':
          if (class_exists('Modules\CRMCore\app\Models\Company')) {
            $results = \Modules\CRMCore\app\Models\Company::where('name', 'LIKE', "%{$searchTerm}%")
              ->select('id', 'name')
              ->limit($limit)
              ->get()
              ->toArray();
          }
          break;

        case 'Modules\CRMCore\app\Models\Contact':
          if (class_exists('Modules\CRMCore\app\Models\Contact')) {
            $results = \Modules\CRMCore\app\Models\Contact::where('is_active', true)
              ->where(function ($query) use ($searchTerm) {
                $query->where('first_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
                  ->orWhereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ["%{$searchTerm}%"]);
              })
              ->select('id', \DB::raw("CONCAT(first_name, ' ', last_name) as name"))
              ->limit($limit)
              ->get()
              ->toArray();
          }
          break;

        case 'Modules\PMCore\app\Models\Project':
          if (class_exists('Modules\PMCore\app\Models\Project')) {
            $results = \Modules\PMCore\app\Models\Project::where('name', 'LIKE', "%{$searchTerm}%")
              ->select('id', 'name')
              ->limit($limit)
              ->get()
              ->toArray();
          }
          break;

        default:
          return response()->json(['error' => 'Invalid entity type'], 400);
      }

      return response()->json($results);
    } catch (\Exception $e) {
      Log::error("Error searching related entities: " . $e->getMessage());
      return response()->json(['error' => 'Search failed', 'details' => $e->getMessage()], 500);
    }
  }

  /**
   * Generate a consistent background color for user initials based on user ID
   */
  private function generateInitialBackground($userId)
  {
    $colors = [
      '#007bff',
      '#28a745',
      '#dc3545',
      '#ffc107',
      '#17a2b8',
      '#6f42c1',
      '#fd7e14',
      '#20c997',
      '#6610f2',
      '#e83e8c'
    ];

    return $colors[$userId % count($colors)];
  }
}
