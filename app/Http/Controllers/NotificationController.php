<?php

namespace App\Http\Controllers;

use App\ApiClasses\Success;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationController extends Controller
{

  public function index()
  {
    $notifications = Auth::user()->notifications()->paginate(15);
    $unreadCount = Auth::user()->unreadNotifications()->count();

    return view('notifications.index', compact('notifications', 'unreadCount'));
  }

  public function markAsRead($id = null)
  {
    if ($id) {
      $notification = Auth::user()->notifications()->findOrFail($id);
      $notification->markAsRead();
      
      return redirect()->back()->with('success', 'Notification marked as read.');
    }
    
    Auth::user()->unreadNotifications->markAsRead();
    return redirect()->back()->with('success', 'All notifications marked as read.');
  }
  
  public function markAllAsRead()
  {
    Auth::user()->unreadNotifications->markAsRead();
    return redirect()->back()->with('success', 'All notifications marked as read.');
  }
  
  public function delete($id)
  {
    $notification = Auth::user()->notifications()->findOrFail($id);
    $notification->delete();
    
    return redirect()->back()->with('success', 'Notification deleted.');
  }

  public function getNotificationsAjax()
  {
    $notifications = Auth::user()->notifications()->latest()->take(10)->get();
    $unreadCount = Auth::user()->unreadNotifications()->count();
    
    return Success::response([
      'notifications' => $notifications,
      'unreadCount' => $unreadCount
    ]);
  }

  public function myNotifications(Request $request)
  {
    $query = Auth::user()->notifications();
    
    // Filter by read status
    if ($request->has('status')) {
      if ($request->status === 'unread') {
        $query->whereNull('read_at');
      } elseif ($request->status === 'read') {
        $query->whereNotNull('read_at');
      }
    }
    
    // Filter by type
    if ($request->has('type') && $request->type !== 'all') {
      $query->where('type', $request->type);
    }
    
    $notifications = $query->latest()->paginate(20);
    $unreadCount = Auth::user()->unreadNotifications()->count();
    $totalCount = Auth::user()->notifications()->count();
    
    // Get unique notification types for filter
    $notificationTypes = DB::table('notifications')
      ->where('notifiable_type', get_class(Auth::user()))
      ->where('notifiable_id', Auth::id())
      ->select('type')
      ->distinct()
      ->pluck('type');
    
    return view('notifications.myNotifications', compact('notifications', 'unreadCount', 'totalCount', 'notificationTypes'));
  }
}
