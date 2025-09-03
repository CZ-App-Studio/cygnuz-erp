<?php

namespace Modules\Announcement\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Announcement\app\Models\Announcement;
use Modules\HRCore\app\Models\Department;
use Modules\HRCore\app\Models\Team;

class AnnouncementController extends Controller
{
    /**
     * Display a listing of the announcements.
     */
    public function index(Request $request)
    {
        // Handle AJAX request for DataTables
        if ($request->ajax()) {
            $query = Announcement::with(['creator', 'departments', 'teams', 'users', 'reads']);

            // Apply filters
            if ($request->has('status') && ! empty($request->status)) {
                $query->where('status', $request->status);
            }

            if ($request->has('priority') && ! empty($request->priority)) {
                $query->where('priority', $request->priority);
            }

            if ($request->has('type') && ! empty($request->type)) {
                $query->where('type', $request->type);
            }

            // Apply search
            if ($request->has('search') && ! empty($request->search['value'])) {
                $search = $request->search['value'];
                $query->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Apply ordering
            if ($request->has('order')) {
                $orderColumn = $request->columns[$request->order[0]['column']]['data'];
                $orderDirection = $request->order[0]['dir'];

                if ($orderColumn && $orderColumn !== '') {
                    $query->orderBy($orderColumn, $orderDirection);
                }
            } else {
                $query->orderBy('is_pinned', 'desc')
                    ->byPriority()
                    ->orderBy('created_at', 'desc');
            }

            $totalRecords = Announcement::count();
            $filteredRecords = $query->count();

            $announcements = $query->skip($request->start ?? 0)
                ->take($request->length ?? 10)
                ->get();

            // Format data for DataTables
            $data = $announcements->map(function ($announcement) {
                return [
                    'id' => $announcement->id,
                    'title' => $announcement->title,
                    'description' => $announcement->description,
                    'type' => $announcement->type,
                    'priority' => $announcement->priority,
                    'target_audience' => $announcement->target_audience,
                    'status' => $announcement->status,
                    'is_pinned' => $announcement->is_pinned,
                    'publish_date' => $announcement->publish_date ? $announcement->publish_date->format('M d, Y') : null,
                    'read_percentage' => $announcement->read_percentage,
                    'departments_count' => $announcement->departments->count(),
                    'teams_count' => $announcement->teams->count(),
                    'users_count' => $announcement->users->count(),
                    'creator_name' => $announcement->creator->name ?? 'Unknown',
                    'created_at' => $announcement->created_at->format('Y-m-d H:i:s'),
                    'action' => '',
                ];
            });

            return response()->json([
                'draw' => intval($request->draw ?? 1),
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $data,
            ]);
        }

        // Regular page load
        $query = Announcement::with(['creator', 'departments', 'teams', 'users', 'reads'])
            ->orderBy('is_pinned', 'desc')
            ->byPriority()
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->has('status') && ! empty($request->status)) {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && ! empty($request->type)) {
            $query->where('type', $request->type);
        }

        if ($request->has('priority') && ! empty($request->priority)) {
            $query->where('priority', $request->priority);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $announcements = $query->paginate(10);

        // Get statistics
        $stats = [
            'total' => Announcement::count(),
            'published' => Announcement::where('status', 'published')->count(),
            'scheduled' => Announcement::where('status', 'scheduled')->count(),
            'draft' => Announcement::where('status', 'draft')->count(),
            'pinned' => Announcement::where('is_pinned', true)->count(),
        ];

        return view('announcement::index', compact('announcements', 'stats'));
    }

    /**
     * Show the form for creating a new announcement.
     */
    public function create()
    {
        $departments = Department::orderBy('name')->get();
        $teams = Team::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('announcement::create', compact('departments', 'teams', 'users'));
    }

    /**
     * Store a newly created announcement in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'content' => 'nullable|string',
            'priority' => 'required|in:low,normal,high,urgent',
            'type' => 'required|in:general,important,event,policy,update',
            'target_audience' => 'required|in:all,departments,teams,specific_users',
            'departments' => 'required_if:target_audience,departments|array',
            'departments.*' => 'exists:departments,id',
            'teams' => 'required_if:target_audience,teams|array',
            'teams.*' => 'exists:teams,id',
            'users' => 'required_if:target_audience,specific_users|array',
            'users.*' => 'exists:users,id',
            'send_email' => 'boolean',
            'send_notification' => 'boolean',
            'is_pinned' => 'boolean',
            'requires_acknowledgment' => 'boolean',
            'publish_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:publish_date',
            'status' => 'required|in:draft,published,scheduled',
            'attachment' => 'nullable|file|max:10240', // 10MB max
        ]);

        DB::beginTransaction();

        try {
            // Handle file upload
            if ($request->hasFile('attachment')) {
                $validated['attachment'] = $request->file('attachment')->store('announcements', 'public');
            }

            // Create announcement
            $announcement = Announcement::create($validated);

            // Attach relationships based on target audience
            if ($validated['target_audience'] === 'departments' && isset($validated['departments'])) {
                $announcement->departments()->attach($validated['departments']);
            } elseif ($validated['target_audience'] === 'teams' && isset($validated['teams'])) {
                $announcement->teams()->attach($validated['teams']);
            } elseif ($validated['target_audience'] === 'specific_users' && isset($validated['users'])) {
                $announcement->users()->attach($validated['users']);
            }

            // Send notifications if required
            if ($validated['status'] === 'published') {
                $this->sendNotifications($announcement);
            }

            DB::commit();

            return redirect()->route('announcements.index')
                ->with('success', 'Announcement created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            // Delete uploaded file if exists
            if (isset($validated['attachment'])) {
                Storage::disk('public')->delete($validated['attachment']);
            }

            return back()->withInput()
                ->with('error', 'Failed to create announcement: '.$e->getMessage());
        }
    }

    /**
     * Display the specified announcement.
     */
    public function show($id)
    {
        $announcement = Announcement::with(['creator', 'departments', 'teams', 'users', 'reads.user'])
            ->findOrFail($id);

        // Mark as read for current user
        if (auth()->check()) {
            $announcement->markAsReadBy(auth()->user());
        }

        // Get read statistics
        $readStats = [
            'total_targets' => $announcement->getTotalTargetUsers(),
            'total_reads' => $announcement->reads()->count(),
            'read_percentage' => $announcement->read_percentage,
            'acknowledged_count' => $announcement->reads()->where('acknowledged', true)->count(),
        ];

        return view('announcement::show', compact('announcement', 'readStats'));
    }

    /**
     * Show the form for editing the specified announcement.
     */
    public function edit($id)
    {
        $announcement = Announcement::with(['departments', 'teams', 'users'])->findOrFail($id);

        // Check if user can edit
        if ($announcement->created_by !== auth()->id() && ! auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized to edit this announcement.');
        }

        $departments = Department::orderBy('name')->get();
        $teams = Team::orderBy('name')->get();
        $users = User::orderBy('name')->get();

        return view('announcement::edit', compact('announcement', 'departments', 'teams', 'users'));
    }

    /**
     * Update the specified announcement in storage.
     */
    public function update(Request $request, $id)
    {
        $announcement = Announcement::findOrFail($id);

        // Check if user can update
        if ($announcement->created_by !== auth()->id() && ! auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized to update this announcement.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'content' => 'nullable|string',
            'priority' => 'required|in:low,normal,high,urgent',
            'type' => 'required|in:general,important,event,policy,update',
            'target_audience' => 'required|in:all,departments,teams,specific_users',
            'departments' => 'required_if:target_audience,departments|array',
            'departments.*' => 'exists:departments,id',
            'teams' => 'required_if:target_audience,teams|array',
            'teams.*' => 'exists:teams,id',
            'users' => 'required_if:target_audience,specific_users|array',
            'users.*' => 'exists:users,id',
            'send_email' => 'boolean',
            'send_notification' => 'boolean',
            'is_pinned' => 'boolean',
            'requires_acknowledgment' => 'boolean',
            'publish_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:publish_date',
            'status' => 'required|in:draft,published,scheduled,expired,archived',
            'attachment' => 'nullable|file|max:10240',
        ]);

        DB::beginTransaction();

        try {
            // Handle file upload
            if ($request->hasFile('attachment')) {
                // Delete old attachment
                if ($announcement->attachment) {
                    Storage::disk('public')->delete($announcement->attachment);
                }
                $validated['attachment'] = $request->file('attachment')->store('announcements', 'public');
            }

            // Update announcement
            $announcement->update($validated);

            // Update relationships
            $announcement->departments()->detach();
            $announcement->teams()->detach();
            $announcement->users()->detach();

            if ($validated['target_audience'] === 'departments' && isset($validated['departments'])) {
                $announcement->departments()->attach($validated['departments']);
            } elseif ($validated['target_audience'] === 'teams' && isset($validated['teams'])) {
                $announcement->teams()->attach($validated['teams']);
            } elseif ($validated['target_audience'] === 'specific_users' && isset($validated['users'])) {
                $announcement->users()->attach($validated['users']);
            }

            // Send notifications if status changed to published
            $oldStatus = $announcement->getOriginal('status');
            if ($oldStatus !== 'published' && $validated['status'] === 'published') {
                $this->sendNotifications($announcement);
            }

            DB::commit();

            return redirect()->route('announcements.index')
                ->with('success', 'Announcement updated successfully.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'Failed to update announcement: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified announcement from storage.
     */
    public function destroy($id)
    {
        $announcement = Announcement::findOrFail($id);

        // Check if user can delete
        if ($announcement->created_by !== auth()->id() && ! auth()->user()->hasRole('admin')) {
            abort(403, 'Unauthorized to delete this announcement.');
        }

        try {
            // Delete attachment if exists
            if ($announcement->attachment) {
                Storage::disk('public')->delete($announcement->attachment);
            }

            $announcement->delete();

            return redirect()->route('announcements.index')
                ->with('success', 'Announcement deleted successfully.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete announcement: '.$e->getMessage());
        }
    }

    /**
     * Mark announcement as acknowledged by current user.
     */
    public function acknowledge($id)
    {
        $announcement = Announcement::findOrFail($id);

        if (auth()->check()) {
            $announcement->markAsAcknowledgedBy(auth()->user());

            return response()->json([
                'success' => true,
                'message' => 'Announcement acknowledged successfully.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'User not authenticated.',
        ], 401);
    }

    /**
     * Toggle pin status of announcement.
     */
    public function togglePin($id)
    {
        $announcement = Announcement::findOrFail($id);

        // Check if user can pin/unpin
        if (! auth()->user()->hasRole(['admin', 'manager'])) {
            abort(403, 'Unauthorized to pin/unpin announcements.');
        }

        $announcement->is_pinned = ! $announcement->is_pinned;
        $announcement->save();

        return response()->json([
            'success' => true,
            'is_pinned' => $announcement->is_pinned,
            'message' => $announcement->is_pinned ? 'Announcement pinned.' : 'Announcement unpinned.',
        ]);
    }

    /**
     * Get announcements for current user.
     */
    public function myAnnouncements()
    {
        $user = auth()->user();

        $announcements = Announcement::forUser($user)
            ->active()
            ->with(['creator', 'reads' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            }])
            ->orderBy('is_pinned', 'desc')
            ->byPriority()
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('announcement::my-announcements', compact('announcements'));
    }

    /**
     * Send notifications for an announcement.
     */
    protected function sendNotifications(Announcement $announcement)
    {
        // Get target users based on audience
        $users = collect();

        switch ($announcement->target_audience) {
            case 'all':
                $users = User::all();
                break;

            case 'departments':
                // Get users whose designation belongs to the selected departments
                $departmentIds = $announcement->departments->pluck('id');
                $users = User::whereHas('designation', function ($q) use ($departmentIds) {
                    $q->whereIn('department_id', $departmentIds);
                })->get();
                break;

            case 'teams':
                $users = User::whereIn('team_id', $announcement->teams->pluck('id'))->get();
                break;

            case 'specific_users':
                $users = $announcement->users;
                break;
        }

        foreach ($users as $user) {
            // Send in-app notification using Laravel's notification system
            if ($announcement->send_notification) {
                try {
                    $user->notify(new \App\Notifications\AnnouncementPublished($announcement));
                } catch (\Exception $e) {
                    \Log::error('Failed to send announcement notification to user '.$user->id.': '.$e->getMessage());
                }
            }

            // Email notifications are handled through the AnnouncementPublished notification class
            // if send_email is true, the notification will include 'mail' channel
        }
    }
}
