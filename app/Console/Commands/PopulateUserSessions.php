<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PopulateUserSessions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sessions:populate-users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Populate user_id in existing sessions based on session payload';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (config('session.driver') !== 'database') {
            $this->error('Session driver must be set to "database" in .env file');
            $this->info('Current driver: ' . config('session.driver'));
            $this->info('Please set SESSION_DRIVER=database in .env and restart your application');
            return 1;
        }

        $sessions = DB::table('sessions')->whereNull('user_id')->get();
        
        if ($sessions->isEmpty()) {
            $this->info('No sessions found without user_id');
            return 0;
        }

        $updated = 0;
        $this->info('Found ' . $sessions->count() . ' sessions without user_id');

        foreach ($sessions as $session) {
            $payload = unserialize(base64_decode($session->payload));
            
            // Check for the login session key (Laravel's default auth guard)
            $loginKey = 'login_web_' . sha1('Illuminate\Auth\SessionGuard');
            
            if (isset($payload[$loginKey])) {
                $userId = $payload[$loginKey];
                
                DB::table('sessions')
                    ->where('id', $session->id)
                    ->update(['user_id' => $userId]);
                    
                $updated++;
                $this->info("Updated session {$session->id} with user_id: {$userId}");
            }
        }

        $this->info("Successfully updated {$updated} sessions with user_id");
        
        return 0;
    }
}