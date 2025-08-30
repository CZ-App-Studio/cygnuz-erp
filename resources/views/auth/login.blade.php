@php
  $customizerHidden = 'customizer-hide';
  $configData = Helper::appClasses();
@endphp

@extends('layouts/layoutMaster')

@section('title', 'Login')

@section('vendor-style')
  @vite([
    'resources/assets/vendor/libs/@form-validation/form-validation.scss'
  ])
@endsection

@section('page-style')
  @vite([
    'resources/assets/vendor/scss/pages/page-auth.scss'
  ])
@endsection

@section('vendor-script')
  @vite([
    'resources/assets/vendor/libs/@form-validation/popular.js',
    'resources/assets/vendor/libs/@form-validation/bootstrap5.js',
    'resources/assets/vendor/libs/@form-validation/auto-focus.js'
  ])
  @php
    $addonService = app(\App\Services\AddonService\IAddonService::class);
    $isReCaptchaEnabled = $addonService->isAddonEnabled('GoogleReCAPTCHA');
  @endphp
  @if($isReCaptchaEnabled)
    @include('googlerecaptcha::components.script')
  @endif
  <script>
    function demoLogin(email) {
      document.getElementById('email').value = email;
      document.getElementById('password').value = '123456';
      document.getElementById('formAuthentication').submit();
    }

    // Legacy functions for compatibility
    function customerLogin() {
      demoLogin('admin@demo.com');
    }
    function hrLogin(){
      demoLogin('hr.manager@demo.com');
    }
    function superAdminLogin() {
      demoLogin('superadmin@demo.com');
    }
  </script>
@endsection

@section('page-script')
  @vite([
    'resources/assets/js/pages-auth.js'
  ])
@endsection

@section('content')
  <div class="authentication-wrapper authentication-cover">
    <!-- Logo -->
    <a href="{{url('/')}}" class="auth-cover-brand d-flex align-items-center gap-2">
      <span class="app-brand-logo demo">
        @if(isset($settings->brand_logo_light) && $settings->brand_logo_light)
          <img src="{{ asset($settings->brand_logo_light) }}" alt="{{ $settings->brand_name ?? 'Logo' }}" width="27">
        @else
          <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" width="27">
        @endif
      </span>
      <span
        class="app-brand-text demo text-heading fw-semibold">{{ $settings->brand_name ?? config('variables.templateFullName') }}</span>
    </a>
    <!-- /Logo -->
    <div class="authentication-inner row m-0">
      <!-- /Left Text -->
      <div class="d-none d-lg-flex col-lg-7 col-xl-8 align-items-center p-5">
        <div class="w-100">
          <div class="d-flex justify-content-center">
            <img src="{{asset('assets/img/login_bg.png')}}"
                 class="img-fluid" alt="Login image" width="700">
          </div>

          {{-- Version Information --}}
          <div class="text-center mt-5 pt-4">
            <small class="text-muted">
              <strong>Cygnuz ERP</strong> v{{ config('app.version') }}
              @if(config('app.version_codename'))
                <span class="text-primary">({{ config('app.version_codename') }})</span>
              @endif
              @if(config('app.version_stage') && config('app.version_stage') !== 'stable')
                <span class="badge bg-{{ config('app.version_stage') === 'alpha' ? 'danger' : (config('app.version_stage') === 'beta' ? 'warning' : 'info') }} ms-1">
                  {{ ucfirst(config('app.version_stage')) }}
                </span>
              @endif
              <br>
              <small>
                Laravel {{ app()->version() }}
                <span class="mx-1">•</span>
                PHP {{ PHP_VERSION }}
                <span class="mx-1">•</span>
                Released: {{ config('app.version_date', date('Y-m-d')) }}
              </small>
              <br>
              <span class="text-primary">© {{ date('Y') }} CZ App Studio</span>
            </small>
          </div>
        </div>
      </div>
      <!-- /Left Text -->

      <!-- Login -->
      <div class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg p-sm-12 p-6">
        <div class="w-px-400 mx-auto mt-12 pt-5">
          <h4 class="mb-1">@lang('Welcome to') {{config('variables.templateFullName')}}! 👋</h4>
          <p class="mb-6">@lang('Login Short Description')</p>

          <form id="formAuthentication" class="mb-6" action="{{route('auth.loginPost')}}" method="POST">
            @csrf
            <div class="mb-6">
              <label for="email" class="form-label">@lang('Email')</label>
              <input type="text" class="form-control" id="email" name="email" placeholder="@lang('Enter your email')"
                     autofocus>
            </div>
            <div class="mb-6 form-password-toggle">
              <label class="form-label" for="password">@lang('Password')</label>
              <div class="input-group input-group-merge">
                <input type="password" id="password" class="form-control" name="password"
                       placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                       aria-describedby="password"/>
                <span class="input-group-text cursor-pointer"><i class="bx bx-hide"></i></span>
              </div>
            </div>
            <div class="mb-8">
              <div class="d-flex justify-content-between mt-8">
                <div class="form-check mb-0 ms-2">
                  <input class="form-check-input" type="checkbox" id="rememberMe" name="rememberMe">
                  <label class="form-check-label" for="rememberMe">
                    @lang('Remember Me')
                  </label>
                </div>
                <a href="{{route('password.request')}}">
                  <span>@lang('Forgot Your Password?')</span>
                </a>
              </div>
            </div>
            @if($isReCaptchaEnabled ?? false)
              @include('googlerecaptcha::components.recaptcha')
            @endif
            <div class="mb-6">
              <button class="btn btn-primary d-grid w-100" type="submit">@lang('Login')</button>
            </div>
          </form>
          @php
            $addonService = app(\App\Services\AddonService\IAddonService::class);
            $isMultiTenancyEnabled = $addonService->isAddonEnabled('MultiTenancyCore');
          @endphp

          @if($isMultiTenancyEnabled)
            <p class="text-center mb-6">
              <span>@lang('New on our platform?')</span>
              <a href="{{ route('auth.register') }}">
                <span>@lang('Create an account')</span>
              </a>
            </p>
          @endif

          @if(env('APP_DEMO') || env('APP_TEST_MODE'))
            <div class="divider my-6">
              <div class="divider-text">@lang('Quick Demo Login')</div>
            </div>

            {{-- Primary Demo Buttons --}}
            <div class="d-grid gap-2 mb-4">
              <button type="button" class="btn btn-primary" onclick="demoLogin('superadmin@demo.com')">
                <i class="bx bx-crown me-2"></i>@lang('Super Admin')
              </button>
              <button type="button" class="btn btn-info" onclick="demoLogin('admin@demo.com')">
                <i class="bx bx-shield-alt-2 me-2"></i>@lang('Admin')
              </button>
            </div>

            {{-- Collapsible Demo Accounts --}}
            <div class="text-center mb-3">
              <a class="btn btn-sm btn-label-secondary" data-bs-toggle="collapse" href="#allDemoAccounts" role="button" aria-expanded="false" aria-controls="allDemoAccounts">
                <i class="bx bx-chevron-down me-1"></i>@lang('All Demo Accounts')
              </a>
            </div>

            <div class="collapse" id="allDemoAccounts">
              {{-- Management Roles --}}
              <h6 class="text-muted mb-2">@lang('Management Roles')</h6>
              <div class="row g-2 mb-3">
                <div class="col-6">
                  <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="demoLogin('hr.manager@demo.com')">
                    <i class="bx bx-user-check me-1"></i>@lang('HR Manager')
                  </button>
                </div>
                <div class="col-6">
                  <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="demoLogin('accounting.manager@demo.com')">
                    <i class="bx bx-calculator me-1"></i>@lang('Accounting')
                  </button>
                </div>
                <div class="col-6">
                  <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="demoLogin('crm.manager@demo.com')">
                    <i class="bx bx-group me-1"></i>@lang('CRM Manager')
                  </button>
                </div>
                <div class="col-6">
                  <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="demoLogin('project.manager@demo.com')">
                    <i class="bx bx-briefcase me-1"></i>@lang('Project')
                  </button>
                </div>
                <div class="col-6">
                  <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="demoLogin('inventory.manager@demo.com')">
                    <i class="bx bx-package me-1"></i>@lang('Inventory')
                  </button>
                </div>
                <div class="col-6">
                  <button type="button" class="btn btn-sm btn-outline-primary w-100" onclick="demoLogin('sales.manager@demo.com')">
                    <i class="bx bx-trending-up me-1"></i>@lang('Sales')
                  </button>
                </div>
              </div>

              {{-- Executive Roles --}}
              <h6 class="text-muted mb-2">@lang('Executive Roles')</h6>
              <div class="row g-2 mb-3">
                <div class="col-6">
                  <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="demoLogin('hr.executive@demo.com')">
                    @lang('HR Executive')
                  </button>
                </div>
                <div class="col-6">
                  <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="demoLogin('accounting.executive@demo.com')">
                    @lang('Accounting Exec')
                  </button>
                </div>
                <div class="col-6">
                  <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="demoLogin('sales.executive@demo.com')">
                    @lang('Sales Executive')
                  </button>
                </div>
                <div class="col-6">
                  <button type="button" class="btn btn-sm btn-outline-secondary w-100" onclick="demoLogin('team.leader@demo.com')">
                    @lang('Team Leader')
                  </button>
                </div>
              </div>

              {{-- Employee & Client Roles --}}
              <h6 class="text-muted mb-2">@lang('Other Roles')</h6>
              <div class="row g-2 mb-3">
                <div class="col-6">
                  <button type="button" class="btn btn-sm btn-outline-success w-100" onclick="demoLogin('employee@demo.com')">
                    <i class="bx bx-user me-1"></i>@lang('Employee')
                  </button>
                </div>
                <div class="col-6">
                  <button type="button" class="btn btn-sm btn-outline-success w-100" onclick="demoLogin('field.employee@demo.com')">
                    <i class="bx bx-map-pin me-1"></i>@lang('Field Employee')
                  </button>
                </div>
                <div class="col-6">
                  <button type="button" class="btn btn-sm btn-outline-warning w-100" onclick="demoLogin('client@demo.com')">
                    <i class="bx bx-building-house me-1"></i>@lang('Client Portal')
                  </button>
                </div>
                <div class="col-6">
                  <button type="button" class="btn btn-sm btn-outline-info w-100" onclick="demoLogin('tenant@demo.com')">
                    <i class="bx bx-store me-1"></i>@lang('Tenant Portal')
                  </button>
                </div>
              </div>
            </div>

            {{-- Password Notice --}}
            <div class="alert alert-info alert-dismissible mt-3" role="alert">
              <div class="d-flex align-items-center">
                <i class="bx bx-info-circle me-2"></i>
                <div>
                  <strong>@lang('Demo Password'):</strong> 123456
                  <br>
                  <small class="text-muted">@lang('All demo accounts use the same password')</small>
                </div>
              </div>
            </div>
          @endif
        </div>
      </div>
      <!-- /Login -->
    </div>
  </div>
@endsection
