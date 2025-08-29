<?php

namespace App\Console\Commands;

use App\Services\HRNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ProcessDailyHRNotifications extends Command
{
  /**
   * The name and signature of the console command.
   */
  protected $signature = 'hrcore:process-daily-notifications {--dry-run : Run without sending notifications}';

  /**
   * The console command description.
   */
  protected $description = 'Process daily HR notifications including upcoming leaves, holidays, and missing attendance';

  /**
   * Execute the console command.
   */
  public function handle(): int
  {
    $dryRun = $this->option('dry-run');
    
    if ($dryRun) {
      $this->info('Running in dry-run mode. No notifications will be sent.');
    }

    $this->info('Processing daily HR notifications...');

    try {
      $notificationService = app(HRNotificationService::class);
      
      if (!$dryRun) {
        $notificationService->processDailyNotifications();
        $this->info('✅ Daily HR notifications processed successfully.');
      } else {
        $this->info('✅ Dry run completed. Would have processed daily notifications.');
      }

      return Command::SUCCESS;
    } catch (\Exception $e) {
      Log::error('Failed to process daily HR notifications: ' . $e->getMessage());
      $this->error('❌ Failed to process daily HR notifications: ' . $e->getMessage());
      
      return Command::FAILURE;
    }
  }
}