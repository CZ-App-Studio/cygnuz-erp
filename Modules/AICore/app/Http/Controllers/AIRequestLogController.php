<?php

namespace Modules\AICore\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\AICore\Models\AIRequestLog;
use Modules\AICore\Models\AIModel;
use App\Models\User;
use Yajra\DataTables\DataTables;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AIRequestLogController extends Controller
{
  /**
   * Display a listing of the AI request logs.
   */
  public function index()
  {
    // Check admin permission
    if (!auth()->user()->hasRole('super_admin')) {
      abort(403, 'Unauthorized access');
    }

    $modules = AIRequestLog::distinct('module_name')->pluck('module_name');
    $models = AIModel::with('provider')->get();
    $users = User::select('id', 'name')->get();

    return view('aicore::logs.index', compact('modules', 'models', 'users'));
  }

  /**
   * Get AI request logs data for DataTables.
   */
  public function indexAjax(Request $request)
  {
    if (!auth()->user()->hasRole('super_admin')) {
      abort(403);
    }

    $query = AIRequestLog::with(['user', 'model.provider', 'reviewer']);

    // Apply filters
    if ($request->filled('module')) {
      $query->where('module_name', $request->module);
    }

    if ($request->filled('user_id')) {
      $query->where('user_id', $request->user_id);
    }

    if ($request->filled('model_id')) {
      $query->where('model_id', $request->model_id);
    }

    if ($request->filled('status')) {
      $query->where('status', $request->status);
    }

    if ($request->filled('is_flagged')) {
      $query->where('is_flagged', $request->is_flagged);
    }

    if ($request->filled('date_from')) {
      $query->where('created_at', '>=', $request->date_from);
    }

    if ($request->filled('date_to')) {
      $query->where('created_at', '<=', $request->date_to . ' 23:59:59');
    }

    return DataTables::of($query)
      ->editColumn('id', function ($log) {
        return '#' . str_pad($log->id, 5, '0', STR_PAD_LEFT);
      })
      ->addColumn('user_display', function ($log) {
        return $log->user ?
          '<div class="d-flex align-items-center">
                        <div class="avatar avatar-sm me-2">
                            <span class="avatar-initial rounded-circle bg-label-primary">' .
          substr($log->user->name, 0, 2) .
          '</span>
                        </div>
                        <div>
                            <span class="fw-medium">' . $log->user->name . '</span>
                            <small class="text-muted d-block">' . $log->user->email . '</small>
                        </div>
                    </div>' :
          '<span class="text-muted">System</span>';
      })
      ->addColumn('model_display', function ($log) {
        if ($log->model) {
          return '<span class="badge bg-label-info">' .
            $log->model->provider->name . ' - ' . $log->model->name .
            '</span>';
        }
        return '<span class="badge bg-label-secondary">' .
          ($log->model_name ?: 'Unknown') .
          '</span>';
      })
      ->addColumn('prompt_preview', function ($log) {
        return '<span class="text-truncate d-inline-block" style="max-width: 300px;" title="' .
          htmlspecialchars($log->request_prompt) . '">' .
          Str::limit($log->request_prompt, 50) .
          '</span>';
      })
      ->addColumn('response_preview', function ($log) {
        if ($log->response_content) {
          return '<span class="text-truncate d-inline-block" style="max-width: 300px;" title="' .
            htmlspecialchars($log->response_content) . '">' .
            Str::limit($log->response_content, 50) .
            '</span>';
        }
        return '<span class="text-muted">No response</span>';
      })
      ->addColumn('status_badge', function ($log) {
        $statusClass = [
          'success' => 'bg-label-success',
          'error' => 'bg-label-danger',
          'pending' => 'bg-label-warning',
          'timeout' => 'bg-label-secondary'
        ][$log->status] ?? 'bg-label-secondary';

        return '<span class="badge ' . $statusClass . '">' .
          ucfirst($log->status) .
          '</span>';
      })
      ->addColumn('tokens_display', function ($log) {
        return '<div class="text-nowrap">
                    <small class="text-muted">P:</small> ' . $log->prompt_tokens . '<br>
                    <small class="text-muted">C:</small> ' . $log->completion_tokens . '<br>
                    <small class="text-muted">T:</small> <strong>' . $log->total_tokens . '</strong>
                </div>';
      })
      ->addColumn('cost_display', function ($log) {
        return '$' . number_format($log->cost, 4);
      })
      ->addColumn('flag_status', function ($log) {
        $checked = $log->is_flagged ? 'checked' : '';
        return '<div class="form-check form-switch mb-0 d-flex justify-content-center">
                    <input class="form-check-input flag-toggle" type="checkbox" role="switch"
                           id="flag-' . $log->id . '"
                           data-id="' . $log->id . '" ' . $checked . '>
                </div>';
      })
      ->addColumn('review_status', function ($log) {
        if ($log->reviewed_at) {
          return '<span class="badge bg-label-success">
                        <i class="bx bx-check"></i> Reviewed
                    </span>';
        }
        if ($log->needsReview()) {
          return '<span class="badge bg-label-warning">
                        <i class="bx bx-time"></i> Needs Review
                    </span>';
        }
        return '<span class="badge bg-label-secondary">-</span>';
      })
      ->editColumn('created_at', function ($log) {
        return $log->created_at->format('Y-m-d H:i:s');
      })
      ->addColumn('actions', function ($log) {
        return '<div class="dropdown">
                    <button class="btn btn-sm btn-icon" type="button" data-bs-toggle="dropdown">
                        <i class="bx bx-dots-vertical-rounded"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <a class="dropdown-item view-details" href="javascript:void(0);"
                               data-id="' . $log->id . '">
                                <i class="bx bx-show me-2"></i> View Details
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item review-log" href="javascript:void(0);"
                               data-id="' . $log->id . '">
                                <i class="bx bx-check-circle me-2"></i> Review
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item copy-prompt" href="javascript:void(0);"
                               data-prompt="' . htmlspecialchars($log->request_prompt) . '">
                                <i class="bx bx-copy me-2"></i> Copy Prompt
                            </a>
                        </li>
                    </ul>
                </div>';
      })
      ->rawColumns([
        'user_display',
        'model_display',
        'prompt_preview',
        'response_preview',
        'status_badge',
        'tokens_display',
        'cost_display',
        'flag_status',
        'review_status',
        'actions'
      ])
      ->make(true);
  }

  /**
   * Show the details of a specific log entry.
   */
  public function show($id)
  {
    if (!auth()->user()->hasRole('super_admin')) {
      abort(403);
    }

    $log = AIRequestLog::with(['user', 'model.provider', 'reviewer'])->findOrFail($id);

    return response()->json([
      'success' => true,
      'log' => $log,
      'html' => view('aicore::logs.show', compact('log'))->render()
    ]);
  }

  /**
   * Toggle the flag status of a log entry.
   */
  public function toggleFlag($id)
  {
    if (!auth()->user()->hasRole('super_admin')) {
      abort(403);
    }

    $log = AIRequestLog::findOrFail($id);
    $log->toggleFlag();

    return response()->json([
      'success' => true,
      'is_flagged' => $log->is_flagged
    ]);
  }

  /**
   * Mark a log entry as reviewed.
   */
  public function review(Request $request, $id)
  {
    if (!auth()->user()->hasRole('super_admin')) {
      abort(403);
    }

    $request->validate([
      'notes' => 'nullable|string|max:1000'
    ]);

    $log = AIRequestLog::findOrFail($id);
    $log->markAsReviewed(auth()->id(), $request->notes);

    return response()->json([
      'success' => true,
      'message' => 'Log entry marked as reviewed'
    ]);
  }

  /**
   * Get statistics for the dashboard.
   */
  public function statistics(Request $request)
  {
    if (!auth()->user()->hasRole('super_admin')) {
      abort(403);
    }

    $dateFrom = $request->filled('date_from') ? $request->date_from : Carbon::now()->subDays(30)->startOfDay();
    $dateTo = $request->filled('date_to') ? $request->date_to : Carbon::now()->endOfDay();

    $stats = [
      'total_requests' => AIRequestLog::whereBetween('created_at', [$dateFrom, $dateTo])->count(),
      'successful_requests' => AIRequestLog::whereBetween('created_at', [$dateFrom, $dateTo])
        ->where('status', 'success')->count(),
      'error_requests' => AIRequestLog::whereBetween('created_at', [$dateFrom, $dateTo])
        ->where('status', 'error')->count(),
      'total_cost' => (float) (AIRequestLog::whereBetween('created_at', [$dateFrom, $dateTo])
        ->sum('cost') ?? 0),
      'total_tokens' => (int) (AIRequestLog::whereBetween('created_at', [$dateFrom, $dateTo])
        ->sum('total_tokens') ?? 0),
      'flagged_count' => AIRequestLog::whereBetween('created_at', [$dateFrom, $dateTo])
        ->where('is_flagged', true)->count(),
      'unreviewed_count' => AIRequestLog::whereBetween('created_at', [$dateFrom, $dateTo])
        ->whereNull('reviewed_at')
        ->where(function ($q) {
          $q->where('status', 'error')
            ->orWhere('cost', '>', 1.0)
            ->orWhere('total_tokens', '>', 4000)
            ->orWhere('is_flagged', true);
        })->count()
    ];

    // Top modules
    $stats['top_modules'] = AIRequestLog::whereBetween('created_at', [$dateFrom, $dateTo])
      ->select('module_name', DB::raw('COUNT(*) as count'), DB::raw('SUM(cost) as total_cost'))
      ->groupBy('module_name')
      ->orderBy('count', 'desc')
      ->limit(5)
      ->get();

    // Top users
    $stats['top_users'] = AIRequestLog::whereBetween('created_at', [$dateFrom, $dateTo])
      ->with('user')
      ->select('user_id', DB::raw('COUNT(*) as count'), DB::raw('SUM(cost) as total_cost'))
      ->groupBy('user_id')
      ->orderBy('count', 'desc')
      ->limit(5)
      ->get();

    return response()->json($stats);
  }

  /**
   * Export logs to CSV.
   */
  public function export(Request $request)
  {
    if (!auth()->user()->hasRole('super_admin')) {
      abort(403);
    }

    $query = AIRequestLog::with(['user', 'model.provider']);

    // Apply same filters as index
    if ($request->has('module') && $request->module) {
      $query->where('module_name', $request->module);
    }
    // ... (apply other filters)

    $logs = $query->get();

    $csvData = "ID,User,Module,Model,Status,Tokens,Cost,Created At,Reviewed\n";
    foreach ($logs as $log) {
      $csvData .= implode(',', [
        $log->id,
        $log->user ? $log->user->name : 'System',
        $log->module_name,
        $log->model ? $log->model->name : 'Unknown',
        $log->status,
        $log->total_tokens,
        $log->cost,
        $log->created_at->format('Y-m-d H:i:s'),
        $log->reviewed_at ? 'Yes' : 'No'
      ]) . "\n";
    }

    return response($csvData)
      ->header('Content-Type', 'text/csv')
      ->header('Content-Disposition', 'attachment; filename="ai_request_logs_' . date('Y-m-d') . '.csv"');
  }
}
