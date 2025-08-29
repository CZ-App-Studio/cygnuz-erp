<?php

namespace Modules\Announcement\app\Console;

use Illuminate\Console\Command;
use Modules\Announcement\app\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementPublished;
use Illuminate\Support\Facades\Notification;

class SendAnnouncementNotifications extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'announcements:send-notifications 
                            {--announcement= : Specific announcement ID to send notifications for}
                            {--limit=10 : Limit number of announcements to process}';

    /**
     * The console command description.
     */
    protected $description = 'Send notifications for published announcements';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $announcementId = $this->option('announcement');
        $limit = $this->option('limit');
        
        if ($announcementId) {
            // Send for specific announcement
            $announcement = Announcement::find($announcementId);
            if (!$announcement) {
                $this->error("Announcement with ID {$announcementId} not found.");
                return 1;
            }
            
            $this->sendNotificationsForAnnouncement($announcement);
        } else {
            // Send for all published announcements that need notifications
            $announcements = Announcement::where('status', 'published')
                ->where('send_notification', true)
                ->orderBy('priority', 'desc')
                ->limit($limit)
                ->get();
            
            if ($announcements->isEmpty()) {
                $this->info('No published announcements found that require notifications.');
                return 0;
            }
            
            $this->info("Processing {$announcements->count()} announcements...");
            
            foreach ($announcements as $announcement) {
                $this->sendNotificationsForAnnouncement($announcement);
            }
        }
        
        $this->info('Notification sending completed!');
        return 0;
    }
    
    /**
     * Send notifications for an announcement
     */
    protected function sendNotificationsForAnnouncement(Announcement $announcement)
    {
        $this->info("Processing: {$announcement->title}");
        
        // Get target users based on audience
        $users = collect();
        
        switch ($announcement->target_audience) {
            case 'all':
                $users = User::all();
                break;
                
            case 'departments':
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
        
        if ($users->isEmpty()) {
            $this->warn("  No target users found for this announcement.");
            return;
        }
        
        // Determine notification type
        $notificationType = $announcement->priority === 'urgent' ? 'urgent' : 'new';
        
        try {
            // Send in chunks for better performance
            $userChunks = $users->chunk(25);
            $totalUsers = $users->count();
            $sentCount = 0;
            
            foreach ($userChunks as $userChunk) {
                Notification::send($userChunk, new AnnouncementPublished($announcement, $notificationType));
                $sentCount += $userChunk->count();
                $this->info("  Sent {$sentCount}/{$totalUsers} notifications...");
            }
            
            $this->info("  ✓ Completed: Sent to {$totalUsers} users");
        } catch (\Exception $e) {
            $this->error("  ✗ Failed: " . $e->getMessage());
        }
    }
}