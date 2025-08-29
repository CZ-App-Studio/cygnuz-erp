<?php

namespace Modules\PMCore\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\PMCore\app\Models\Project;
use Modules\PMCore\app\Models\ProjectStatus;
use Modules\PMCore\app\Enums\ProjectStatus as ProjectStatusEnum;
use Modules\PMCore\app\Enums\ProjectType;
use Modules\PMCore\app\Enums\ProjectPriority;
use App\Models\User;

class PMCoreDatabaseSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    $this->call([
      PMCorePermissionSeeder::class,
      ProjectStatusSeeder::class,
      ProjectSeeder::class,
      ProjectTaskSeeder::class,
      TimesheetSeeder::class,
      ResourceAllocationSeeder::class,
    ]);
  }
}
