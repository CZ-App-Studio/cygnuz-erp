<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\ApiClasses\Success;
use App\ApiClasses\Error;
use App\Services\Menu\MenuAggregator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Queue;
use Exception;

class SystemStatusController extends Controller
{
  /**
   * Display the system status page
   */
  public function index()
  {
    $systemInfo = $this->getSystemInformation();
    $healthChecks = $this->performHealthChecks();
    $serverInfo = $this->getServerInformation();
    $storageInfo = $this->getStorageInformation();

    return view('admin.system-status.index', compact(
      'systemInfo',
      'healthChecks',
      'serverInfo',
      'storageInfo'
    ));
  }

  /**
   * Get system status via AJAX
   */
  public function getSystemStatus()
  {
    try {
      $healthChecks = $this->performHealthChecks();
      $overallStatus = $this->determineOverallStatus($healthChecks);

      return response()->json([
        'status' => $overallStatus,
        'message' => $this->getStatusMessage($overallStatus),
        'details' => $healthChecks,
        'timestamp' => now()->toISOString()
      ]);

    } catch (Exception $e) {
      Log::error('System status check failed: ' . $e->getMessage());

      return response()->json([
        'status' => 'error',
        'message' => 'System status check failed',
        'timestamp' => now()->toISOString()
      ], 500);
    }
  }

  /**
   * Clear application cache
   */
  public function clearCache(Request $request)
  {
    try {
      $cacheTypes = [];

      // Application cache
      if (Cache::flush()) {
        $cacheTypes[] = 'application';
      }

      // Configuration cache
      try {
        Artisan::call('config:clear');
        $cacheTypes[] = 'config';
      } catch (Exception $e) {
        Log::warning('Failed to clear config cache: ' . $e->getMessage());
      }

      // Route cache
      try {
        Artisan::call('route:clear');
        $cacheTypes[] = 'routes';
      } catch (Exception $e) {
        Log::warning('Failed to clear route cache: ' . $e->getMessage());
      }

      // View cache
      try {
        Artisan::call('view:clear');
        $cacheTypes[] = 'views';
      } catch (Exception $e) {
        Log::warning('Failed to clear view cache: ' . $e->getMessage());
      }

      // Clear compiled services
      try {
        Artisan::call('clear-compiled');
        $cacheTypes[] = 'compiled';
      } catch (Exception $e) {
        Log::warning('Failed to clear compiled cache: ' . $e->getMessage());
      }

      // Log the cache clear action
      Log::info('Cache cleared by user: ' . (auth()->user()->name ?? 'Unknown'), [
        'user_id' => auth()->id(),
        'cache_types' => $cacheTypes,
        'ip_address' => $request->ip()
      ]);

      return Success::response(
        'Cache cleared successfully. Types cleared: ' . implode(', ', $cacheTypes)
      );

    } catch (Exception $e) {
      Log::error('Cache clear failed: ' . $e->getMessage(), [
        'user_id' => auth()->id(),
        'ip_address' => $request->ip()
      ]);

      return Error::response('Failed to clear cache: ' . $e->getMessage());
    }
  }

  /**
   * Refresh menu cache
   */
  public function refreshMenuCache(Request $request)
  {
    try {
      $menuAggregator = app(MenuAggregator::class);
      
      // Clear existing cache
      $menuAggregator->clearCache();
      
      // Force refresh menus
      $verticalMenu = $menuAggregator->getMenu('vertical', true);
      $horizontalMenu = $menuAggregator->getMenu('horizontal', true);
      
      // Count menu items
      $verticalCount = count($verticalMenu['menu'] ?? []);
      $horizontalCount = count($horizontalMenu['menu'] ?? []);
      
      // Log the action
      Log::info('Menu cache refreshed by user: ' . (auth()->user()->name ?? 'Unknown'), [
        'user_id' => auth()->id(),
        'vertical_items' => $verticalCount,
        'horizontal_items' => $horizontalCount,
        'ip_address' => $request->ip()
      ]);
      
      return Success::response([
        'message' => 'Menu cache refreshed successfully',
        'data' => [
          'vertical_items' => $verticalCount,
          'horizontal_items' => $horizontalCount
        ]
      ]);
      
    } catch (Exception $e) {
      Log::error('Menu cache refresh failed: ' . $e->getMessage(), [
        'user_id' => auth()->id(),
        'ip_address' => $request->ip()
      ]);
      
      return Error::response('Failed to refresh menu cache: ' . $e->getMessage());
    }
  }

  /**
   * Optimize the application
   */
  public function optimize(Request $request)
  {
    try {
      $optimizations = [];

      // Config cache
      try {
        Artisan::call('config:cache');
        $optimizations[] = 'config';
      } catch (Exception $e) {
        Log::warning('Failed to cache config: ' . $e->getMessage());
      }

      // Route cache
      try {
        Artisan::call('route:cache');
        $optimizations[] = 'routes';
      } catch (Exception $e) {
        Log::warning('Failed to cache routes: ' . $e->getMessage());
      }

      // View cache
      try {
        Artisan::call('view:cache');
        $optimizations[] = 'views';
      } catch (Exception $e) {
        Log::warning('Failed to cache views: ' . $e->getMessage());
      }

      // Log the optimization action
      Log::info('System optimized by user: ' . (auth()->user()->name ?? 'Unknown'), [
        'user_id' => auth()->id(),
        'optimizations' => $optimizations,
        'ip_address' => $request->ip()
      ]);

      return Success::response(
        'System optimized successfully. Optimizations: ' . implode(', ', $optimizations)
      );

    } catch (Exception $e) {
      Log::error('System optimization failed: ' . $e->getMessage(), [
        'user_id' => auth()->id(),
        'ip_address' => $request->ip()
      ]);

      return Error::response('Failed to optimize system: ' . $e->getMessage());
    }
  }

  /**
   * Get detailed system information
   */
  private function getSystemInformation()
  {
    return [
      'app' => [
        'name' => config('app.name'),
        'version' => config('app.version', '1.0.0'),
        'environment' => config('app.env'),
        'debug' => config('app.debug'),
        'url' => config('app.url'),
        'timezone' => config('app.timezone'),
        'locale' => config('app.locale'),
      ],
      'laravel' => [
        'version' => app()->version(),
        'php_version' => PHP_VERSION,
      ],
      'database' => [
        'driver' => config('database.default'),
        'connection' => config('database.connections.' . config('database.default') . '.driver'),
      ],
      'cache' => [
        'driver' => config('cache.default'),
      ],
      'queue' => [
        'driver' => config('queue.default'),
      ],
      'session' => [
        'driver' => config('session.driver'),
        'lifetime' => config('session.lifetime'),
      ],
      'mail' => [
        'driver' => config('mail.default'),
        'from' => config('mail.from.address'),
      ]
    ];
  }

  /**
   * Perform health checks
   */
  private function performHealthChecks()
  {
    $checks = [];

    // Database check
    $checks['database'] = $this->checkDatabase();

    // Cache check
    $checks['cache'] = $this->checkCache();

    // Storage check
    $checks['storage'] = $this->checkStorage();

    // Queue check
    $checks['queue'] = $this->checkQueue();

    // Memory check
    $checks['memory'] = $this->checkMemory();

    // Disk space check
    $checks['disk'] = $this->checkDiskSpace();

    // Menu cache check
    $checks['menu_cache'] = $this->checkMenuCache();

    return $checks;
  }

  /**
   * Check database connection
   */
  private function checkDatabase()
  {
    try {
      DB::connection()->getPdo();
      $tableCount = DB::select("SELECT COUNT(*) as count FROM information_schema.tables WHERE table_schema = DATABASE()")[0]->count ?? 0;

      return [
        'status' => 'healthy',
        'message' => 'Database connection successful',
        'details' => [
          'driver' => config('database.default'),
          'tables' => $tableCount
        ]
      ];
    } catch (Exception $e) {
      return [
        'status' => 'error',
        'message' => 'Database connection failed: ' . $e->getMessage(),
        'details' => []
      ];
    }
  }

  /**
   * Check cache system
   */
  private function checkCache()
  {
    try {
      $testKey = 'system_health_check_' . time();
      $testValue = 'test_value';

      Cache::put($testKey, $testValue, 60);
      $retrieved = Cache::get($testKey);
      Cache::forget($testKey);

      if ($retrieved === $testValue) {
        return [
          'status' => 'healthy',
          'message' => 'Cache system working properly',
          'details' => [
            'driver' => config('cache.default')
          ]
        ];
      } else {
        return [
          'status' => 'warning',
          'message' => 'Cache system not working properly',
          'details' => []
        ];
      }
    } catch (Exception $e) {
      return [
        'status' => 'error',
        'message' => 'Cache system error: ' . $e->getMessage(),
        'details' => []
      ];
    }
  }

  /**
   * Check storage permissions
   */
  private function checkStorage()
  {
    try {
      $checks = [];
      $status = 'healthy';

      // Check storage directory
      $storageWritable = is_writable(storage_path());
      $checks['storage_writable'] = $storageWritable;

      // Check logs directory
      $logsWritable = is_writable(storage_path('logs'));
      $checks['logs_writable'] = $logsWritable;

      // Check app directory
      $appWritable = is_writable(storage_path('app'));
      $checks['app_writable'] = $appWritable;

      if (!$storageWritable || !$logsWritable || !$appWritable) {
        $status = 'warning';
      }

      return [
        'status' => $status,
        'message' => $status === 'healthy' ? 'Storage directories are writable' : 'Some storage directories are not writable',
        'details' => $checks
      ];
    } catch (Exception $e) {
      return [
        'status' => 'error',
        'message' => 'Storage check failed: ' . $e->getMessage(),
        'details' => []
      ];
    }
  }

  /**
   * Check queue system
   */
  private function checkQueue()
  {
    try {
      $details = [];
      $status = 'healthy';
      $message = 'Queue system operational';

      if (config('queue.default') === 'database') {
        $failedJobs = DB::table('failed_jobs')->count();
        $details['failed_jobs'] = $failedJobs;

        if ($failedJobs > 10) {
          $status = 'warning';
          $message = "Multiple failed jobs detected ({$failedJobs})";
        } elseif ($failedJobs > 0) {
          $message = "Some failed jobs detected ({$failedJobs})";
        }
      }

      return [
        'status' => $status,
        'message' => $message,
        'details' => $details
      ];
    } catch (Exception $e) {
      return [
        'status' => 'error',
        'message' => 'Queue check failed: ' . $e->getMessage(),
        'details' => []
      ];
    }
  }

  /**
   * Check memory usage
   */
  private function checkMemory()
  {
    $memoryUsage = memory_get_usage(true);
    $peakMemoryUsage = memory_get_peak_usage(true);
    $memoryLimit = ini_get('memory_limit');

    // Convert memory limit to bytes
    $memoryLimitBytes = $this->convertToBytes($memoryLimit);
    $usagePercentage = ($memoryUsage / $memoryLimitBytes) * 100;

    $status = 'healthy';
    if ($usagePercentage > 80) {
      $status = 'warning';
    } elseif ($usagePercentage > 90) {
      $status = 'error';
    }

    return [
      'status' => $status,
      'message' => "Memory usage: {$this->formatBytes($memoryUsage)} ({$usagePercentage}%)",
      'details' => [
        'current' => $this->formatBytes($memoryUsage),
        'peak' => $this->formatBytes($peakMemoryUsage),
        'limit' => $memoryLimit,
        'percentage' => round($usagePercentage, 2)
      ]
    ];
  }

  /**
   * Check disk space
   */
  private function checkDiskSpace()
  {
    $totalSpace = disk_total_space(base_path());
    $freeSpace = disk_free_space(base_path());
    $usedSpace = $totalSpace - $freeSpace;
    $usagePercentage = ($usedSpace / $totalSpace) * 100;

    $status = 'healthy';
    if ($usagePercentage > 80) {
      $status = 'warning';
    } elseif ($usagePercentage > 90) {
      $status = 'error';
    }

    return [
      'status' => $status,
      'message' => "Disk usage: {$this->formatBytes($usedSpace)} ({$usagePercentage}%)",
      'details' => [
        'total' => $this->formatBytes($totalSpace),
        'used' => $this->formatBytes($usedSpace),
        'free' => $this->formatBytes($freeSpace),
        'percentage' => round($usagePercentage, 2)
      ]
    ];
  }

  /**
   * Get server information
   */
  private function getServerInformation()
  {
    return [
      'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
      'php_version' => PHP_VERSION,
      'php_sapi' => PHP_SAPI,
      'memory_limit' => ini_get('memory_limit'),
      'max_execution_time' => ini_get('max_execution_time'),
      'upload_max_filesize' => ini_get('upload_max_filesize'),
      'post_max_size' => ini_get('post_max_size'),
      'max_input_vars' => ini_get('max_input_vars'),
      'server_time' => now()->format('Y-m-d H:i:s T'),
      'uptime' => $this->getSystemUptime()
    ];
  }

  /**
   * Get storage information
   */
  private function getStorageInformation()
  {
    $storageInfo = [];

    // Check different storage disks
    $disks = ['local', 'public'];

    foreach ($disks as $disk) {
      try {
        if (Storage::disk($disk)->exists('.')) {
          $storageInfo[$disk] = [
            'status' => 'available',
            'driver' => config("filesystems.disks.{$disk}.driver"),
            'root' => config("filesystems.disks.{$disk}.root")
          ];
        } else {
          $storageInfo[$disk] = [
            'status' => 'unavailable',
            'driver' => config("filesystems.disks.{$disk}.driver"),
            'root' => config("filesystems.disks.{$disk}.root")
          ];
        }
      } catch (Exception $e) {
        $storageInfo[$disk] = [
          'status' => 'error',
          'error' => $e->getMessage()
        ];
      }
    }

    return $storageInfo;
  }

  /**
   * Determine overall system status
   */
  private function determineOverallStatus($healthChecks)
  {
    $statuses = array_column($healthChecks, 'status');

    if (in_array('error', $statuses)) {
      return 'error';
    } elseif (in_array('warning', $statuses)) {
      return 'warning';
    } else {
      return 'healthy';
    }
  }

  /**
   * Get status message
   */
  private function getStatusMessage($status)
  {
    switch ($status) {
      case 'healthy':
        return 'All systems operational';
      case 'warning':
        return 'Some systems need attention';
      case 'error':
        return 'System errors detected';
      default:
        return 'Unknown system status';
    }
  }

  /**
   * Convert memory limit to bytes
   */
  private function convertToBytes($size)
  {
    $unit = strtolower(substr($size, -1));
    $value = (int) $size;

    switch ($unit) {
      case 'g':
        $value *= 1024;
      case 'm':
        $value *= 1024;
      case 'k':
        $value *= 1024;
    }

    return $value;
  }

  /**
   * Format bytes to human readable
   */
  private function formatBytes($bytes, $precision = 2)
  {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];

    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
      $bytes /= 1024;
    }

    return round($bytes, $precision) . ' ' . $units[$i];
  }

  /**
   * Get system uptime (simplified)
   */
  private function getSystemUptime()
  {
    if (function_exists('sys_getloadavg')) {
      return 'Available';
    }

    return 'Not available';
  }

  /**
   * Check menu cache status
   */
  private function checkMenuCache()
  {
    try {
      $menuAggregator = app(MenuAggregator::class);
      
      // Check if menu cache exists
      $verticalCached = Cache::has('menu.aggregated.vertical');
      $horizontalCached = Cache::has('menu.aggregated.horizontal');
      
      // Get menu counts
      $verticalMenu = $menuAggregator->getMenu('vertical');
      $horizontalMenu = $menuAggregator->getMenu('horizontal');
      
      $verticalCount = count($verticalMenu['menu'] ?? []);
      $horizontalCount = count($horizontalMenu['menu'] ?? []);
      
      $status = 'healthy';
      $message = "Menu cache active ({$verticalCount} vertical, {$horizontalCount} horizontal items)";
      
      if (!$verticalCached || !$horizontalCached) {
        $status = 'warning';
        $message = 'Menu cache not fully populated';
      }
      
      if ($verticalCount === 0 && $horizontalCount === 0) {
        $status = 'error';
        $message = 'No menu items loaded';
      }
      
      return [
        'status' => $status,
        'message' => $message,
        'details' => [
          'vertical_cached' => $verticalCached,
          'horizontal_cached' => $horizontalCached,
          'vertical_items' => $verticalCount,
          'horizontal_items' => $horizontalCount
        ]
      ];
    } catch (Exception $e) {
      return [
        'status' => 'error',
        'message' => 'Menu cache check failed: ' . $e->getMessage(),
        'details' => []
      ];
    }
  }
}
