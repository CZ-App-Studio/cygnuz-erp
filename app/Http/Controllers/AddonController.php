<?php

namespace App\Http\Controllers;

use ZipArchive;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Nwidart\Modules\Facades\Module;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use ModuleConstants;

class AddonController extends Controller
{

  public function index()
  {
    // Get all available modules
    $modules = Module::all();

    // Initialize categories with counts
    $categories = ModuleConstants::MODULE_CATEGORIES;
    $categoryData = [];
    $allModulesData = [];

    // Process each category
    foreach ($categories as $categoryName => $categoryInfo) {
      $categoryData[$categoryName] = [
        'info' => $categoryInfo,
        'modules' => [],
        'count' => 0
      ];
    }

    // Process all modules
    foreach ($modules as $module) {
      $moduleJson = json_decode(file_get_contents($module->getPath() . '/module.json'), true);
      
      // Determine category - use getModuleCategory for consistent categorization
      $category = $moduleJson['category'] ?? null;
      
      // Map "payment" category to "Payment Gateways"
      if ($category === 'payment') {
        $category = 'Payment Gateways';
      }
      
      if (!$category) {
        $category = $this->getModuleCategory($module->getName());
      }

      // Add runtime data
      $moduleJson['enabled'] = $module->isEnabled();
      $moduleJson['name'] = $module->getName();
      $moduleJson['path'] = $module->getPath();
      $moduleJson['installed'] = true;

      // Add to category
      if (isset($categoryData[$category])) {
        $categoryData[$category]['modules'][] = $moduleJson;
        $categoryData[$category]['count']++;
      }

      // Add to all modules list
      $allModulesData[$module->getName()] = $moduleJson;
    }

    // Add marketplace modules to categories
    foreach (ModuleConstants::ALL_ADDONS_ARRAY as $addonKey => $addon) {
      if (!isset($allModulesData[$addonKey])) {
        // Determine category
        $category = $this->getModuleCategory($addonKey);

        if (isset($categoryData[$category])) {
          $addon['name'] = $addon['name'] ?? $addonKey;
          $addon['key'] = $addonKey;
          $addon['installed'] = false;
          $addon['enabled'] = false;
          
          // Add application-specific fields
          if (isset($addon['type']) && $addon['type'] === 'application') {
            $addon['isApplication'] = true;
            $addon['platform'] = $addon['platform'] ?? 'Web';
            $addon['technology'] = $addon['technology'] ?? '';
          }
          
          $categoryData[$category]['modules'][] = $addon;
          $categoryData[$category]['count']++;
        }
      }
    }

    // Sort categories by order
    uasort($categoryData, function ($a, $b) {
      return ($a['info']['order'] ?? 999) - ($b['info']['order'] ?? 999);
    });

    return view('addons.index', [
      'categoryData' => $categoryData,
      'isDemo' => env('APP_DEMO', false)
    ]);
  }

  private function getModuleCategory($moduleName)
  {
    // Check if module ends with 'Core' - these belong to Core category
    if (str_ends_with($moduleName, 'Core')) {
      return 'Core';
    }
    
    // Map modules to categories
    $categoryMap = [
      'Payroll' => 'Human Resources',
      'LoanManagement' => 'Human Resources',
      'Recruitment' => 'Human Resources',
      'DisciplinaryActions' => 'Human Resources',
      'HRPolicies' => 'Human Resources',
      'BreakSystem' => 'Human Resources',
      'ShiftPlus' => 'Human Resources',
      'QRAttendance' => 'Attendance & Time Management',
      'DynamicQrAttendance' => 'Attendance & Time Management',
      'FaceAttendance' => 'Attendance & Time Management',
      'IpAddressAttendance' => 'Attendance & Time Management',
      'GeofenceSystem' => 'Attendance & Time Management',
      'SiteAttendance' => 'Attendance & Time Management',
      'FieldManager' => 'Field Operations',
      'TaskSystem' => 'Field Operations',
      'OfflineTracking' => 'Field Operations',
      'DigitalIdCard' => 'Field Operations',
      'AccountingPro' => 'Finance & Accounting',
      'Billing' => 'Finance & Accounting',
      'MultiCurrency' => 'Finance & Accounting',
      'PaymentCollection' => 'Finance & Accounting',
      'SubscriptionManagement' => 'Finance & Accounting',
      'PayPalGateway' => 'Payment Gateways',
      'StripeGateway' => 'Payment Gateways',
      'RazorpayGateway' => 'Payment Gateways',
      'SalesTarget' => 'Sales & CRM',
      'ProductOrder' => 'Sales & CRM',
      'AgoraCall' => 'Communication & Collaboration',
      'AiChat' => 'Communication & Collaboration',
      'CommunicationCenter' => 'Communication & Collaboration',
      'NoticeBoard' => 'Communication & Collaboration',
      'Notes' => 'Communication & Collaboration',
      'Calendar' => 'Communication & Collaboration',
      'DocumentManagement' => 'Document & Data Management',
      'DataImportExport' => 'Document & Data Management',
      'FormBuilder' => 'Document & Data Management',
      'Approvals' => 'System & Administration',
      'AuditLog' => 'System & Administration',
      'GoogleReCAPTCHA' => 'System & Administration',
      'UidLogin' => 'System & Administration',
      'LandingPage' => 'System & Administration',
      'Assets' => 'Asset Management',
      'LMS' => 'Learning & Development',
      'SearchPlus' => 'Productivity & Tools',
      'DesktopTracker' => 'Employee Monitoring',
      'SOS' => 'Field Operations',
      'AICore' => 'Artificial Intelligence',
      'ClaudeAIProvider' => 'Artificial Intelligence',
      'GeminiAIProvider' => 'Artificial Intelligence',
      'AzureOpenAIProvider' => 'Artificial Intelligence',
      'CygnuzPOS' => 'Applications',
      'CygnuzESS' => 'Applications',
    ];

    return $categoryMap[$moduleName] ?? 'Other';
  }

  // Enable a module (addon)
  public function activate(Request $request)
  {
    if (env('APP_DEMO')) {
      return redirect()->back()->with('error', 'This feature is disabled in the demo.');
    }

    $moduleName = $request->input('module');

    // Enable the module using Artisan
    Artisan::call('module:enable', ['module' => $moduleName]);

    return redirect()->back()->with('success', 'Module enabled successfully.');
  }

  // Disable a module (addon)
  public function deactivate(Request $request)
  {
    if (env('APP_DEMO')) {
      return redirect()->back()->with('error', 'This feature is disabled in the demo.');
    }

    $moduleName = $request->input('module');

    // Disable the module using Artisan
    Artisan::call('module:disable', ['module' => $moduleName]);

    return redirect()->back()->with('success', 'Module disabled successfully.');
  }

  // Upload and install a new module (addon)
  public function upload(Request $request)
  {
    if (env('APP_DEMO')) {
      return redirect()->back()->with('error', 'This feature is disabled in the demo.');
    }

    // Validate the file input, ensuring it is a zip file
    $request->validate([
      'module' => 'required|file|mimes:zip|max:20480', // Limit file size to 20MB
    ]);

    // Store the uploaded file temporarily
    $file = $request->file('module');
    $fileName = $file->getClientOriginalName();
    $tempPath = storage_path('modules/' . $fileName);
    $file->move(storage_path('modules'), $fileName);

    // Extract the zip file to a temporary location
    $zip = new ZipArchive();
    if ($zip->open($tempPath) === TRUE) {
      // Get the base filename without any extension or extra path
      $moduleFolderName = pathinfo($fileName, PATHINFO_FILENAME);

      // Define the extraction path using just the module name
      $extractPath = storage_path('modules/extracted/');

      // Extract the zip to the extraction path
      $zip->extractTo($extractPath);
      $zip->close();
    } else {
      return redirect()->back()->with('error', 'Failed to extract the module.');
    }

    // Validate that the extracted directory contains module.json (and possibly other expected files)
    if (!File::exists($extractPath . $moduleFolderName . '/module.json')) {
      // If no module.json is found, delete extracted files and return an error
      File::deleteDirectory($extractPath);
      //Delete the zip file
      File::delete($tempPath);
      return redirect()->back()->with('error', 'Invalid addon: module.json not found.');
    }

    //Check if the same module is already installed
    if (Module::find($moduleFolderName)) {
      // If the module is already installed, delete extracted files and return an error
      File::deleteDirectory($extractPath);
      //Delete the zip file
      File::delete($tempPath);
      return redirect()->back()->with('error', 'Module already installed.');
    }

    // Move the extracted module to the Modules directory
    File::moveDirectory($extractPath . $moduleFolderName, base_path('Modules/' . pathinfo($fileName, PATHINFO_FILENAME)));

    // Clean up: delete the uploaded zip file
    File::delete($tempPath);

    return redirect()->back()->with('success', 'Module uploaded successfully.');

  }

  public function uninstall(Request $request)
  {

    try {

      if (env('APP_DEMO')) {
        return redirect()->back()->with('error', 'This feature is disabled in the demo.');
      }

      $moduleName = $request->input('module');

      // Disable the module before uninstalling
      Artisan::call('module:disable', ['module' => $moduleName]);

      // Remove the module's directory
      $modulePath = base_path('Modules/' . $moduleName);
      if (File::exists($modulePath)) {
        File::deleteDirectory($modulePath);
      }

      // You might also want to clean up any module-specific database tables here.
      // Example: \Artisan::call('module:migrate-rollback', ['module' => $moduleName]);

    } catch (\Exception $e) {
      Log::error($e->getMessage());
    }

    return redirect()->back()->with('success', 'Module uninstalled successfully.');
  }

  public function update(Request $request)
  {
    if (env('APP_DEMO')) {
      return redirect()->back()->with('error', 'This feature is disabled in the demo.');
    }

    $moduleName = $request->input('module');

    // Logic for updating the module
    // This could involve downloading the latest version of the module and replacing the old files

    return redirect()->back()->with('success', 'Module updated successfully.');
  }

}
