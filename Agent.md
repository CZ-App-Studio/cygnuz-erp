# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

This is a Laravel-based ERP system built on top of Open Core HR V4.1.2 by CZ App Studio. The application uses a modular architecture with Laravel Modules package and supports multi-tenancy. It includes 51 modules covering HR, CRM, Accounting, Project Management, Warehouse Management, and more.

## Development Commands

### Setup & Installation

```bash
# Install dependencies
composer update --ignore-platform-reqs

# Database setup
php artisan migrate:erp
php artisan db:seed

# Development server
You don't need to run this as I am already running
npm run serve
```

### Demo Data Seeding

```bash
php artisan module:seed WMSInventoryCore
```

### Testing & Code Quality

```bash
# Run PHPUnit tests
php artisan test
./vendor/bin/phpunit
./vendor/bin/phpunit tests/Unit/ChatControllerTest.php

# Code formatting with Laravel Pint
./vendor/bin/pint
```

### Module Management

```bash
php artisan module:list                    # List all modules
php artisan module:enable [ModuleName]     # Enable a module
php artisan module:disable [ModuleName]    # Disable a module
php artisan module:make [ModuleName]       # Create new module
php artisan module:migrate [ModuleName]    # Run module migrations
php artisan module:seed [ModuleName]       # Run module seeders
```

### Asset Building

NO need to run npm run or dev I'll do the running and testing

## Architecture Overview

### Core Technologies

- **Laravel 11.x** with PHP 8.2+
- **Bootstrap 5.3.3** UI framework
- **jQuery** for DOM manipulation
- **Vite** for asset bundling
- **MySQL** database with soft deletes

### Modular Structure

- **Core Application**: `app/` directory with standard Laravel MVC
- **Modules**: `Modules/` directory with 51 self-contained modules
- **Module Status**: Tracked in `modules_statuses.json`
- **Dynamic Loading**: `vite-module-loader.js` handles module assets
- **External Apps**: `projects/` directory containing standalone applications:
  - **desktop-tracker**: Electron-based desktop time tracking application
  - **cygnuz-pos**: React/TypeScript POS (Point of Sale) application

### Key Modules

- **HRCore**: Human resources management
- **AccountingCore**: Financial management
- **CRMCore**: Customer relationship management
- **PMCore**: Project management
- **WMSInventoryCore**: Warehouse management
- **SystemCore**: System-wide functionality
- **FormBuilder**: Dynamic form creation

### Authentication & API

- **JWT Authentication**: `tymon/jwt-auth` for API authentication
- **Laravel Sanctum**: API token management
- **Multi-tenancy**: Tenant-based data isolation
- **RESTful APIs**: Using Laravel Orion for resource endpoints

## Response Standards

### AJAX Response Format

All AJAX endpoints must use standardized response classes:

**Success Response:**

```php
return Success::response([
    'message' => 'Operation completed successfully',
    'data' => $result
]);
```

**Error Response:**

```php
return Error::response('Error message');
// or with additional data
return Error::response([
    'message' => 'Validation failed',
    'errors' => $validator->errors()
]);
```

### JavaScript Response Handling

```javascript
$.ajax({
  success: function (response) {
    if (response.status === 'success') {
      // Access data via response.data and it will be in camelCase if you use
      let message = response.data.message;
    } else {
      // Handle error
      let error = response.data || 'Unknown error';
    }
  }
});
```

## Development Guidelines

### Module Development

Each module follows this structure:

```
Modules/[ModuleName]/
├── app/
│   ├── Http/Controllers/
│   ├── Models/
│   └── Providers/
├── config/
├── database/
│   ├── migrations/
│   └── seeders/
├── resources/
│   ├── assets/
│   ├── lang/
│   └── views/
|   |__ menu/
├── routes/
│   ├── api.php
│   └── web.php
├── module.json
├── composer.json
├── package.json
└── vite.config.js
```

### Controller Standards

- **Resourceful Controllers**: Use `php artisan module:make-controller ... -r`
- **Data Listing**: `index()` returns view, `getDataAjax()` handles DataTables
- **Form Patterns**:
  - Full pages for complex models (Companies, Contacts)
  - Full pages with jQuery Repeater for documents (Invoices, Proposals)
  - Bootstrap Offcanvas for simple records (Leads, Tasks, Settings)
- **Transactions**: Use `DB::transaction()` for multiple database operations

### Model Standards

Required traits:

```php
use HasFactory;
use SoftDeletes;
use UserActionsTrait;      // for created_by_id, updated_by_id
use Auditable;            // for audit trail
```

Model properties:

```php
protected $table = 'table_name';
protected $fillable = [...];
protected $casts = [
    'status' => StatusEnum::class,
    'is_active' => 'boolean',
];
```

### Database Standards

- **Migrations**: Module migrations in `Modules/[Module]/database/migrations/`
- **Foreign Keys**: Use `unsignedBigInteger()->nullable()` for module-to-core relationships
- **Seeders**: Use factories for demo data, `firstOrCreate()` for lookup data

### Frontend Standards

#### View Standards

- **Layout**: All pages must extend `layouts.layoutMaster`
- **Localization**: Use `__()` for ALL user-facing text
- **Breadcrumbs**: Use `<x-breadcrumb>` component with flexible home URL
- **Icons**: Use **Boxicons** (bx) exclusively - e.g., `<i class="bx bx-user"></i>`
- **Export/Import**: Do NOT add export buttons - handled by DataImportExport addon

#### DataTable Standards

- **User Display**: Use `<x-datatable-user>` component for consistent user display
- **Actions**: Use `<x-datatable-actions>` component for action dropdowns
- **Server-side Rendering**: Render HTML components in controller, not JavaScript

Example in controller:

```php
->addColumn('user', function ($model) {
    return view('components.datatable-user', ['user' => $model->user])->render();
})
->addColumn('actions', function ($model) {
    return view('components.datatable-actions', [
        'id' => $model->id,
        'actions' => [
            ['label' => __('Edit'), 'icon' => 'bx bx-edit', 'onclick' => "editRecord({$model->id})"]
        ]
    ])->render();
})
->rawColumns(['user', 'actions'])
```

#### JavaScript Patterns

```javascript
$(function () {
  // CSRF setup
  $.ajaxSetup({
    headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
  });

  // Use pageData for backend values
  const urls = pageData.urls;
  const labels = pageData.labels;
});
```

#### Key JavaScript Libraries

- **DataTables**: Server-side processing for lists
- **SweetAlert2**: Confirmations and alerts
- **Select2**: Enhanced dropdowns with AJAX
- **Flatpickr**: Date/datetime inputs (REQUIRED for all date inputs)
- **jQuery Repeater**: Dynamic form rows
- **ApexCharts**: Dashboard charts

#### AJAX Endpoint Requirements

Ensure all JavaScript AJAX calls have corresponding controller methods:

```javascript
// If your JS calls these URLs, the controller methods MUST exist:
pageData.urls.datatable; // Controller: indexAjax()
pageData.urls.statistics; // Controller: statistics()
pageData.urls.webCheckIn; // Controller: webCheckIn()
```

### Form Display Standards

**IMPORTANT: Form UI Pattern Rules**

- **Right-side Offcanvas**: Use for forms with less data (< 10 fields)
- **Full Page**: Use for forms with many fields or complex layouts
- **NEVER use center modals** for any forms in the application

Example offcanvas implementation:

```blade
<div class="offcanvas offcanvas-end" tabindex="-1" id="formOffcanvas">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title">{{ __('Form Title') }}</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form id="form">
            <!-- Form fields -->
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">{{ __('Save') }}</button>
                <button type="button" class="btn btn-label-secondary flex-fill" data-bs-dismiss="offcanvas">{{ __('Cancel') }}</button>
            </div>
        </form>
    </div>
</div>
```

JavaScript for offcanvas:

```javascript
// Show offcanvas
const offcanvas = new bootstrap.Offcanvas(document.getElementById('formOffcanvas'));
offcanvas.show();

// Hide offcanvas
const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('formOffcanvas'));
offcanvas.hide();
```

#### Checkbox/Switch Handling

Always convert checkbox values for AJAX submissions:

```javascript
$('#form').on('submit', function (e) {
  e.preventDefault();
  const formData = new FormData(this);

  // Fix checkbox value
  const isChecked = $('#checkbox_id').is(':checked');
  formData.delete('field_name');
  formData.append('field_name', isChecked ? '1' : '0');

  // Submit via AJAX...
});
```

### Translation Standards

Module translations in `Modules/[Module]/resources/lang/`:

```
├── en.json    // English translations
└── ar.json    // Arabic translations
```

Usage:

```blade
{{ __('Project Management') }}   // ✅ Correct
{{ __('pmcore::Project') }}      // ❌ Wrong - no module prefix
```

Pass to JavaScript via pageData:

```blade
<script>
const pageData = {
    labels: {
        confirmDelete: @json(__('Are you sure?')),
        success: @json(__('Success!'))
    }
};
</script>
```

### WMS Inventory Integration

Use global search endpoints for product/warehouse selection:

- **Products**: `/inventory/products/search`
- **Warehouses**: `/inventory/warehouses/search`

Product selection pattern:

```javascript
$('.product-select').select2({
  ajax: {
    url: pageData.urls.productSearch,
    data: function (params) {
      return {
        search: params.term,
        warehouse_id: $('#warehouse_id').val()
      };
    }
  }
});
```

## Key Service Classes

### AddonService

Check module status:

```php
$addonService = app(AddonService::class);
if ($addonService->isAddonEnabled(ModuleConstants::INVOICE')) {
    // Module-specific code
}
```

### FormattingHelper

Consistent formatting:

```php
FormattingHelper::formatCurrency($amount);
FormattingHelper::formatDate($date);
FormattingHelper::formatDateTime($datetime);
```

## File Locations

### Configuration

- **Environment**: `.env` for environment settings
- **Modules Status**: `modules_statuses.json` for enabled/disabled modules
- **Module Config**: `Modules/[Module]/config/`

### Routes

- **Core Routes**: `routes/web.php`, `routes/api.php`
- **Module Routes**: `Modules/[Module]/routes/`

### Route Standards

```php
// Module routes must follow this pattern:
Route::prefix('modulename')->name('modulename.')->middleware(['auth', 'web'])->group(function () {
    Route::prefix('resource')->name('resource.')->group(function () {
        Route::get('/', [Controller::class, 'index'])->name('index');
        Route::get('/datatable', [Controller::class, 'indexAjax'])->name('datatable');
        Route::get('/statistics', [Controller::class, 'statistics'])->name('statistics');
        // ... other routes
    });
});
```

- Use lowercase module prefix
- Follow `module.resource.action` naming convention
- Group related routes together

### Assets

- **Core Assets**: `resources/assets/`
- **Module Assets**: `Modules/[Module]/resources/assets/`
- **Build Config**: `vite.config.js` and module-specific configs

## Seeder Standards

### DatabaseSeeder Organization

The DatabaseSeeder must call seeders in the correct dependency order:

```php
public function run(): void
{
    Artisan::call('cache:clear');

    // 1. Permissions and Roles (MUST come first)
    $this->call(ERPPermissionSeeder::class);

    // 2. Core data (departments, shifts, etc.)

    // 3. Demo users (depends on roles existing)
    $this->call(DemoSeeder::class);
}
```

### Demo User Standards

All demo users follow consistent patterns:

- **Email format**: role.type@demo.com (e.g., hr.manager@demo.com)
- **Password**: Always '123456' for demo accounts
- **Phone**: Sequential '100000000X' format
- **Employee codes**: Sequential 'EMP-XXX' format
- **Hierarchy**: Proper reporting_to_id relationships

Example demo accounts:

- superadmin@demo.com - Super Admin
- admin@demo.com - Admin
- hr.manager@demo.com - HR Manager
- accounting.manager@demo.com - Accounting Manager
- employee@demo.com - Regular Employee

===

<laravel-boost-guidelines>
=== boost rules ===

## Laravel Boost

- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan

- Use the `list-artisan-commands` tool when you need to call an Artisan command to double check the available parameters.

## URLs

- Whenever you share a project URL with the user you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain / IP, and port.

## Tinker / Debugging

- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool

- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)

- Boost comes with a powerful `search-docs` tool you should use before any other approaches. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation specific for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The 'search-docs' tool is perfect for all Laravel related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel-ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.

### Available Search Syntax

- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit"
3. Quoted Phrases (Exact Position) - query="infinite scroll - Words must be adjacent and in that order
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit"
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database

- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation

- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues

- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization

- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration

- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] <name>` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v11 rules ===

## Laravel 11

- Use the `search-docs` tool to get version specific documentation.
- Laravel 11 brought a new streamlined file structure which this project now uses.

### Laravel 11 Structure

- No middleware files in `app/Http/Middleware/`.
- `bootstrap/app.php` is the file to register middleware, exceptions, and routing files.
- `bootstrap/providers.php` contains application specific service providers.
- **No app\Console\Kernel.php** - use `bootstrap/app.php` or `routes/console.php` for console configuration.
- **Commands auto-register** - files in `app/Console/Commands/` are automatically available and do not require manual registration.

### Database

- When modifying a column, the migration must include all of the attributes that were previously defined on the column. Otherwise, they will be dropped and lost.
- Laravel 11 allows limiting eagerly loaded records natively, without external packages: `$query->latest()->limit(10);`.

### Models

- Casts can and likely should be set in a `casts()` method on a model rather than the `$casts` property. Follow existing conventions from other models.

### New Artisan Commands

- List Artisan commands using Boost's MCP tool, if available. New commands available in Laravel 11:
  - `php artisan make:enum`
  - `php artisan make:class`
  - `php artisan make:interface`

=== livewire/core rules ===

## Livewire Core

- Use the `search-docs` tool to find exact version specific documentation for how to write Livewire & Livewire tests.
- Use the `php artisan make:livewire [Posts\\CreatePost]` artisan command to create new components
- State should live on the server, with the UI reflecting it.
- All Livewire requests hit the Laravel backend, they're like regular HTTP requests. Always validate form data, and run authorization checks in Livewire actions.

## Livewire Best Practices

- Livewire components require a single root element.
- Use `wire:loading` and `wire:dirty` for delightful loading states.
- Add `wire:key` in loops:

  ```blade
  @foreach ($items as $item)
      <div wire:key="item-{{ $item->id }}">
          {{ $item->name }}
      </div>
  @endforeach
  ```

- Prefer lifecycle hooks like `mount()`, `updatedFoo()`) for initialization and reactive side effects:

<code-snippet name="Lifecycle hook examples" lang="php">
    public function mount(User $user) { $this->user = $user; }
    public function updatedSearch() { $this->resetPage(); }
</code-snippet>

## Testing Livewire

<code-snippet name="Example Livewire component test" lang="php">
    Livewire::test(Counter::class)
        ->assertSet('count', 0)
        ->call('increment')
        ->assertSet('count', 1)
        ->assertSee(1)
        ->assertStatus(200);
</code-snippet>

    <code-snippet name="Testing a Livewire component exists within a page" lang="php">
        $this->get('/posts/create')
        ->assertSeeLivewire(CreatePost::class);
    </code-snippet>

=== livewire/v3 rules ===

## Livewire 3

### Key Changes From Livewire 2

- These things changed in Livewire 2, but may not have been updated in this application. Verify this application's setup to ensure you conform with application conventions.
  - Use `wire:model.live` for real-time updates, `wire:model` is now deferred by default.
  - Components now use the `App\Livewire` namespace (not `App\Http\Livewire`).
  - Use `$this->dispatch()` to dispatch events (not `emit` or `dispatchBrowserEvent`).
  - Use the `components.layouts.app` view as the typical layout path (not `layouts.app`).

### New Directives

- `wire:show`, `wire:transition`, `wire:cloak`, `wire:offline`, `wire:target` are available for use. Use the documentation to find usage examples.

### Alpine

- Alpine is now included with Livewire, don't manually include Alpine.js.
- Plugins included with Alpine: persist, intersect, collapse, and focus.

### Lifecycle Hooks

- You can listen for `livewire:init` to hook into Livewire initialization, and `fail.status === 419` for the page expiring:

<code-snippet name="livewire:load example" lang="js">
document.addEventListener('livewire:init', function () {
    Livewire.hook('request', ({ fail }) => {
        if (fail && fail.status === 419) {
            alert('Your session expired');
        }
    });

    Livewire.hook('message.failed', (message, component) => {
        console.error(message);
    });

});
</code-snippet>

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test`, simply run `vendor/bin/pint` to fix any formatting issues.

=== tests rules ===

## Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test` with a specific filename or filter.
  </laravel-boost-guidelines>

## Task Master AI Instructions

**Import Task Master's development workflow commands and guidelines, treat as if import is in the main CLAUDE.md file.**
@./.taskmaster/CLAUDE.md
