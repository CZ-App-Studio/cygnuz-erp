<!-- BEGIN: Vendor JS-->

@vite([
  'resources/assets/vendor/libs/jquery/jquery.js',
  'resources/assets/vendor/libs/popper/popper.js',
  'resources/assets/vendor/js/bootstrap.js',
  'resources/assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.js',
  'resources/assets/vendor/libs/hammer/hammer.js',
  'resources/assets/vendor/libs/typeahead-js/typeahead.js',
  'resources/assets/vendor/js/menu.js'
])

@yield('vendor-script')
<!-- END: Page Vendor JS-->
<!-- BEGIN: Theme JS-->
@vite([
  'resources/assets/js/main.js',
  'resources/assets/js/menu-search.js'
])

<!-- END: Theme JS-->

<!-- BEGIN: Menu Pin JS-->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.14.0/Sortable.min.js"></script>
<link href="{{ asset('assets/css/menu-pin.css') }}" rel="stylesheet">
<script src="{{ asset('assets/js/menu-pin.js') }}"></script>
<!-- END: Menu Pin JS-->

<!-- BEGIN: Sidebar Status JS-->
<script>
// System status URL for AJAX calls
@auth
  @if(auth()->user()->hasRole('admin'))
    var systemStatusUrl = "{{ route('admin.system-status.ajax') }}";
  @endif
@endauth
</script>
@vite(['resources/assets/js/app/sidebar-status.js'])
<!-- END: Sidebar Status JS-->
{{--<!-- Pricing Modal JS-->
@stack('pricing-script')
<!-- END: Pricing Modal JS-->--}}


{{--<!-- BEGIN: Firebase JS-->
<script type="module" src="{{asset('assets/js/firebase-messaging-sw.js')}}"></script>
<!-- END: Firebase JS-->--}}

<!-- BEGIN: Page JS-->
@yield('page-script')
<!-- END: Page JS-->

<!-- BEGIN: SearchPlus Integration -->
@php
  $addonService = app(\App\Services\AddonService\IAddonService::class);
@endphp

@if($addonService->isAddonEnabled('SearchPlus'))
  @include('searchplus::components.search-modal')
  @include('searchplus::components.first-time-notification')
  @vite([
    'resources/assets/js/searchplus-compatibility.js',
    'Modules/SearchPlus/resources/assets/js/searchplus.js',
    'Modules/SearchPlus/resources/assets/css/searchplus.css'
  ])
@endif
<!-- END: SearchPlus Integration -->

<!-- BEGIN: AutoDescriptionAI Integration -->
@if($addonService->isAddonEnabled('AutoDescriptionAI'))
  @vite(['Modules/AutoDescriptionAI/resources/assets/js/auto-description-ai.js'])
@endif
<!-- END: AutoDescriptionAI Integration -->

@include('layouts.sections.toaster')
