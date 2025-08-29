# Versioning Guide for Cygnuz ERP

## 📋 Version Format

We follow [Semantic Versioning](https://semver.org/) with development stages:

```
MAJOR.MINOR.PATCH-STAGE
```

### Examples:
- `0.1.0-alpha` - Alpha release
- `0.5.0-beta` - Beta release  
- `1.0.0-rc.1` - Release candidate
- `1.0.0` - Stable release
- `1.1.0` - Feature release
- `1.1.1` - Bug fix release

## 🏷️ Version Stages

| Stage | Description | Version Example |
|-------|-------------|-----------------|
| **alpha** | Early development, major changes expected | `0.x.x-alpha` |
| **beta** | Feature complete, testing phase | `0.x.x-beta` |
| **rc** | Release candidate, final testing | `1.0.0-rc.1` |
| **stable** | Production ready | `1.0.0` |

## 📍 Where to Update Version

When releasing a new version, update these locations:

### 1. **config/app.php** (Primary)
```php
'version' => env('APP_VERSION', '0.1.0-alpha'),
'version_date' => '2025-01-01',
'version_codename' => 'Genesis',
'version_stage' => 'alpha',
```

### 2. **config/variables.php**
```php
"templateVersion" => "0.1.0-alpha",
```

### 3. **.env** (Optional - for override)
```env
APP_VERSION=0.1.0-alpha
```

### 4. **composer.json**
```json
{
    "version": "0.1.0-alpha"
}
```

### 5. **package.json**
```json
{
    "version": "0.1.0-alpha"
}
```

## 🎯 Where Version is Displayed

### 1. **Footer** - All pages
Create/update: `resources/views/layouts/sections/footer/footer.blade.php`
```blade
<small>v{{ config('app.version') }}</small>
```

### 2. **Admin Dashboard**
```blade
<div class="app-version">
    Version: {{ config('app.version') }} 
    ({{ config('app.version_codename') }})
</div>
```

### 3. **System Info Page**
```blade
<tr>
    <td>System Version</td>
    <td>{{ config('app.version') }}</td>
</tr>
<tr>
    <td>Release Date</td>
    <td>{{ config('app.version_date') }}</td>
</tr>
<tr>
    <td>Stage</td>
    <td>{{ ucfirst(config('app.version_stage')) }}</td>
</tr>
```

### 4. **API Response Headers**
```php
// In middleware or base controller
$response->header('X-App-Version', config('app.version'));
```

### 5. **Login Page**
```blade
<div class="text-center text-muted">
    Cygnuz ERP {{ config('app.version') }}
</div>
```

## 🚀 Release Process

### 1. **Update Version Numbers**
```bash
# Example: Moving from 0.1.0-alpha to 0.2.0-alpha
```

Update in order:
1. `config/app.php`
2. `config/variables.php`
3. `composer.json`
4. `package.json`

### 2. **Create Git Tag**
```bash
git add .
git commit -m "chore: bump version to 0.2.0-alpha"
git tag -a v0.2.0-alpha -m "Release version 0.2.0-alpha"
git push origin main
git push origin v0.2.0-alpha
```

### 3. **Create GitHub Release**
1. Go to GitHub repository
2. Click "Releases" → "Create a new release"
3. Choose tag: `v0.2.0-alpha`
4. Release title: `v0.2.0-alpha - Codename`
5. Add release notes (see template below)

## 📝 Release Notes Template

```markdown
# Version 0.2.0-alpha - Genesis

Released: January 15, 2025

## 🎯 Highlights
- Brief summary of major changes

## ✨ New Features
- Feature 1
- Feature 2

## 🐛 Bug Fixes
- Fix 1
- Fix 2

## 🔧 Improvements
- Improvement 1
- Improvement 2

## 💔 Breaking Changes
- Change 1 (migration required)

## 📦 Dependencies
- Updated Laravel to 11.x
- Added package XYZ

## 🔄 Migration Guide
If upgrading from previous version:
1. Run `composer update`
2. Run `php artisan migrate`
3. Clear cache: `php artisan cache:clear`

## 📊 Statistics
- Commits: 150
- Contributors: 5
- Files changed: 200

## 🙏 Contributors
- @username1
- @username2

---
**Full Changelog**: https://github.com/CZ-App-Studio/cygnuz-erp/compare/v0.1.0...v0.2.0
```

## 🔢 Version Roadmap

| Version | Stage | Target Date | Codename | Focus |
|---------|-------|------------|----------|-------|
| 0.1.0 | Alpha | Jan 2025 | Genesis | Core architecture |
| 0.2.0 | Alpha | Feb 2025 | Foundation | Basic modules |
| 0.3.0 | Alpha | Mar 2025 | Builder | AI integration |
| 0.4.0 | Alpha | Apr 2025 | Connect | Mobile apps |
| 0.5.0 | Beta | Jun 2025 | Pioneer | Feature complete |
| 0.6.0 | Beta | Jul 2025 | Refine | Bug fixes |
| 0.7.0 | Beta | Aug 2025 | Polish | UI/UX improvements |
| 0.8.0 | Beta | Sep 2025 | Optimize | Performance |
| 0.9.0 | RC | Oct 2025 | Candidate | Final testing |
| 1.0.0 | Stable | Nov 2025 | Launch | Production ready |

## 🏷️ Codename Suggestions

### Alpha Series (Building)
- Genesis, Foundation, Builder, Connect, Evolve

### Beta Series (Refining)
- Pioneer, Refine, Polish, Optimize, Enhance

### Stable Series (Growing)
- Launch, Horizon, Summit, Pinnacle, Zenith

## 🔄 Version Helper Functions

Create `app/Helpers/VersionHelper.php`:

```php
<?php

namespace App\Helpers;

class VersionHelper
{
    public static function getVersion(): string
    {
        return config('app.version', '0.0.0');
    }
    
    public static function getVersionWithCodename(): string
    {
        $version = config('app.version');
        $codename = config('app.version_codename');
        return $codename ? "{$version} ({$codename})" : $version;
    }
    
    public static function isAlpha(): bool
    {
        return config('app.version_stage') === 'alpha';
    }
    
    public static function isBeta(): bool
    {
        return config('app.version_stage') === 'beta';
    }
    
    public static function isStable(): bool
    {
        return config('app.version_stage') === 'stable';
    }
    
    public static function getVersionBadge(): string
    {
        $stage = config('app.version_stage', 'alpha');
        $colors = [
            'alpha' => 'danger',
            'beta' => 'warning',
            'rc' => 'info',
            'stable' => 'success'
        ];
        
        $color = $colors[$stage] ?? 'secondary';
        return "<span class='badge bg-{$color}'>" . ucfirst($stage) . "</span>";
    }
}
```

## 🎨 Displaying Version in Blade

### Simple Version
```blade
{{ config('app.version') }}
```

### With Codename
```blade
{{ config('app.version') }} - {{ config('app.version_codename') }}
```

### With Badge
```blade
<span class="app-version">
    v{{ config('app.version') }}
    @if(config('app.version_stage') !== 'stable')
        <span class="badge bg-warning">{{ ucfirst(config('app.version_stage')) }}</span>
    @endif
</span>
```

### In Footer
```blade
<footer class="footer">
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-6">
                {{ date('Y') }} © Cygnuz ERP
            </div>
            <div class="col-sm-6">
                <div class="text-sm-end">
                    Version {{ config('app.version') }}
                    @if(config('app.version_stage') === 'alpha')
                        <span class="text-danger">(Alpha - Not for production)</span>
                    @elseif(config('app.version_stage') === 'beta')
                        <span class="text-warning">(Beta - Testing)</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
</footer>
```

## 📊 Version API Endpoint

Create an API endpoint to check version:

```php
// routes/api.php
Route::get('/version', function () {
    return response()->json([
        'version' => config('app.version'),
        'codename' => config('app.version_codename'),
        'stage' => config('app.version_stage'),
        'date' => config('app.version_date'),
        'php_version' => PHP_VERSION,
        'laravel_version' => app()->version(),
    ]);
});
```

## 🔔 Version Change Notifications

When updating version, notify users through:
1. Release notes on GitHub
2. Changelog in documentation
3. Email to registered users (for major releases)
4. Dashboard notification (for logged-in users)

---

**Remember**: Always update version numbers before creating a release!