# Contributing to Cygnuz ERP

First off, thank you for considering contributing to Cygnuz ERP! It's people like you that will help make Cygnuz ERP a great tool for businesses worldwide.

## Table of Contents

- [Code of Conduct](#code-of-conduct)
- [Getting Started](#getting-started)
- [How Can I Contribute?](#how-can-i-contribute)
- [Development Setup](#development-setup)
- [Coding Standards](#coding-standards)
- [Pull Request Process](#pull-request-process)
- [Module Development](#module-development)
- [Testing](#testing)
- [Documentation](#documentation)
- [Community](#community)

## Code of Conduct

### Our Pledge

We are committed to providing a friendly, safe, and welcoming environment for all contributors, regardless of experience level, gender identity and expression, sexual orientation, disability, personal appearance, body size, race, ethnicity, age, religion, nationality, or any other characteristic.

### Expected Behavior

- Be respectful and inclusive
- Welcome newcomers and help them get started
- Focus on what is best for the community
- Show empathy towards other community members
- Be constructive in your criticism

### Unacceptable Behavior

- Harassment, discrimination, or offensive comments
- Personal attacks or trolling
- Publishing others' private information
- Any conduct which would be inappropriate in a professional setting

## Getting Started

### Prerequisites

Before you begin, ensure you have:

- PHP >= 8.4
- Composer >= 2.0
- Node.js >= 18.x
- MySQL >= 8.0
- Git
- A GitHub account

### Project Status

⚠️ **Important**: Cygnuz ERP is currently in **Alpha stage**. This means:
- Many features are still being developed
- APIs and database schemas may change
- Expect bugs and incomplete functionality
- Your contributions will help shape the project!

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check existing issues to avoid duplicates.

**When reporting bugs, include:**

1. **Clear title and description**
2. **Steps to reproduce**
3. **Expected behavior**
4. **Actual behavior**
5. **Screenshots** (if applicable)
6. **Environment details:**
   - OS and version
   - PHP version
   - Laravel version
   - Browser (for frontend issues)
   - Module versions

**Bug Report Template:**
```markdown
### Description
[Clear description of the bug]

### Steps to Reproduce
1. Go to '...'
2. Click on '....'
3. Scroll down to '....'
4. See error

### Expected Behavior
[What you expected to happen]

### Actual Behavior
[What actually happened]

### Environment
- OS: [e.g., macOS 14.0]
- PHP: [e.g., 8.4.0]
- MySQL: [e.g., 8.0.30]
- Browser: [e.g., Chrome 120]
```

### Suggesting Enhancements

Enhancement suggestions are welcome! Please provide:

1. **Use case**: Why is this enhancement needed?
2. **Proposed solution**: How should it work?
3. **Alternatives considered**: What other solutions did you think about?
4. **Additional context**: Mockups, examples, etc.

### Your First Code Contribution

Unsure where to begin? Look for these tags in our issues:

- `good first issue` - Simple issues perfect for beginners
- `help wanted` - Issues where we need community help
- `documentation` - Help improve our docs
- `alpha-feature` - Features being built for alpha release

## Development Setup

### 1. Fork and Clone

```bash
# Fork the repository on GitHub, then:
git clone https://github.com/YOUR_USERNAME/cygnuz-erp.git
cd cygnuz-erp
git remote add upstream https://github.com/CZ-App-Studio/cygnuz-erp.git
```

### 2. Install Dependencies

```bash
# PHP dependencies
composer install

# JavaScript dependencies
npm install

# Copy environment file
cp .env.example .env

# Generate application key
php artisan key:generate
```

### 3. Database Setup

```bash
# Create database
mysql -u root -p -e "CREATE DATABASE cygnuz_erp"

# Run migrations
php artisan migrate:erp --seed-demo
```

### 4. Start Development Server

```bash
# Laravel server
php artisan serve

# Asset compilation (in another terminal)
npm run dev
```

## Coding Standards

### PHP/Laravel Standards

We follow [PSR-12](https://www.php-fig.org/psr/psr-12/) and Laravel best practices:

```php
<?php

namespace Modules\ModuleName\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExampleController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\View\View
     */
    public function index(): View
    {
        $items = Item::query()
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('modulename::items.index', compact('items'));
    }
}
```

### Code Style Rules

1. **Use Laravel Pint for formatting:**
   ```bash
   ./vendor/bin/pint
   ```

2. **Follow Laravel naming conventions:**
   - Controllers: `UserController` (singular)
   - Models: `User` (singular)
   - Tables: `users` (plural)
   - Pivot tables: `role_user` (alphabetical)
   - Methods: `camelCase()`
   - Variables: `$camelCase`
   - Constants: `CONSTANT_NAME`

3. **Always use:**
   - Type hints for parameters and return types
   - Dependency injection over facades where possible
   - Form Request classes for validation
   - Resource classes for API responses
   - Database transactions for multiple operations

### JavaScript Standards

```javascript
// Use ES6+ syntax
const processData = async (data) => {
    try {
        const response = await $.ajax({
            url: '/api/process',
            method: 'POST',
            data: data,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            }
        });
        
        return response.data;
    } catch (error) {
        console.error('Processing failed:', error);
        throw error;
    }
};
```

### Blade Templates

```blade
@extends('layouts.layoutMaster')

@section('title', __('Page Title'))

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <x-breadcrumb 
        :title="__('Page Title')"
        :items="[
            ['label' => __('Home'), 'url' => route('dashboard')],
            ['label' => __('Current Page')]
        ]"
    />
    
    {{-- Always use translations --}}
    <h4 class="py-3 mb-4">{{ __('Page Heading') }}</h4>
    
    {{-- Use components for reusable elements --}}
    <x-datatable-user :user="$user" />
</div>
@endsection

@push('scripts')
<script>
    $(function() {
        // Page-specific JavaScript
    });
</script>
@endpush
```

## Pull Request Process

### 1. Before Creating a PR

- **Create a feature branch:**
  ```bash
  git checkout -b feature/your-feature-name
  # or
  git checkout -b fix/issue-description
  ```

- **Keep your branch updated:**
  ```bash
  git fetch upstream
  git rebase upstream/main
  ```

- **Test your changes:**
  ```bash
  # Run tests
  php artisan test
  
  # Check code style
  ./vendor/bin/pint
  
  # Test in browser
  npm run build
  ```

### 2. Creating the PR

**PR Title Format:**
- `feat: Add user export functionality`
- `fix: Resolve date formatting in reports`
- `docs: Update installation guide`
- `refactor: Improve query performance in dashboard`

**PR Description Template:**
```markdown
## Description
Brief description of changes

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Tests pass locally
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Comments added for complex code
- [ ] Documentation updated

## Screenshots (if applicable)
[Add screenshots here]

## Related Issues
Fixes #(issue)
```

### 3. PR Review Process

1. Automated checks must pass
2. At least one maintainer review required
3. All feedback must be addressed
4. Final approval from maintainer
5. Squash and merge

## Module Development

### Creating a New Module

```bash
php artisan module:make ModuleName

# Module structure
Modules/ModuleName/
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
│   ├── menu/
│   └── views/
├── routes/
│   ├── api.php
│   └── web.php
├── module.json
└── composer.json
```

### Module Guidelines

1. **Follow modular architecture**
2. **Include seeders for demo data**
3. **Add translations for all text**
4. **Create menu configuration**
5. **Document module dependencies**
6. **Include tests for functionality**

## Testing

### Writing Tests

```php
<?php

namespace Tests\Feature\Modules\ModuleName;

use Tests\TestCase;
use App\Models\User;

class ExampleTest extends TestCase
{
    public function test_authenticated_user_can_access_module(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)
            ->get('/module-route');
        
        $response->assertStatus(200);
    }
}
```

### Running Tests

```bash
# Run all tests
php artisan test

# Run specific test file
php artisan test tests/Feature/Modules/ModuleName/ExampleTest.php

# Run with coverage
php artisan test --coverage
```

## Documentation

### Code Documentation

```php
/**
 * Process the payment for an order.
 *
 * @param  \App\Models\Order  $order
 * @param  array  $paymentData
 * @return \App\Models\Payment
 * @throws \App\Exceptions\PaymentException
 */
public function processPayment(Order $order, array $paymentData): Payment
{
    // Implementation
}
```

### README Updates

Update relevant documentation when you:
- Add new features
- Change requirements
- Modify installation steps
- Add new modules

## Commit Message Guidelines

We follow [Conventional Commits](https://www.conventionalcommits.org/):

- `feat:` - New feature
- `fix:` - Bug fix
- `docs:` - Documentation changes
- `style:` - Code style changes (formatting, etc.)
- `refactor:` - Code refactoring
- `test:` - Test additions or changes
- `chore:` - Build process or auxiliary tool changes

**Examples:**
```bash
git commit -m "feat: add bulk user import functionality"
git commit -m "fix: resolve timezone issue in attendance module"
git commit -m "docs: update API documentation for v2 endpoints"
```

## Community

### Getting Help

- **GitHub Issues**: For bugs and feature requests
- **Discussions**: For general questions and ideas
- **Email**: support@czappstudio.com
- **Website**: [czappstudio.com](https://czappstudio.com)

### Recognition

Contributors will be:
- Listed in our README
- Mentioned in release notes
- Given credit in relevant documentation

## License

By contributing to Cygnuz ERP, you agree that your contributions will be licensed under the MIT License.

---

Thank you for contributing to Cygnuz ERP! Your efforts help make this project better for everyone. 🚀
