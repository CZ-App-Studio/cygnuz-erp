@php
    $footerSettings = [];
    if (class_exists('\Modules\MultiTenancyCore\app\Models\SaasSetting')) {
        $footerSettings = [
            'company_name' => \Modules\MultiTenancyCore\app\Models\SaasSetting::get('footer_company_name', config('app.name')),
            'copyright_text' => \Modules\MultiTenancyCore\app\Models\SaasSetting::get('footer_copyright_text', 'All rights reserved'),
            'description' => \Modules\MultiTenancyCore\app\Models\SaasSetting::get('footer_description', config('variables.templateDescription')),
            'facebook_url' => \Modules\MultiTenancyCore\app\Models\SaasSetting::get('footer_facebook_url', ''),
            'twitter_url' => \Modules\MultiTenancyCore\app\Models\SaasSetting::get('footer_twitter_url', ''),
            'linkedin_url' => \Modules\MultiTenancyCore\app\Models\SaasSetting::get('footer_linkedin_url', ''),
            'instagram_url' => \Modules\MultiTenancyCore\app\Models\SaasSetting::get('footer_instagram_url', ''),
            'privacy_url' => \Modules\MultiTenancyCore\app\Models\SaasSetting::get('footer_privacy_url', ''),
            'terms_url' => \Modules\MultiTenancyCore\app\Models\SaasSetting::get('footer_terms_url', ''),
            'support_url' => \Modules\MultiTenancyCore\app\Models\SaasSetting::get('footer_support_url', ''),
            'docs_url' => \Modules\MultiTenancyCore\app\Models\SaasSetting::get('footer_docs_url', ''),
            'show_credits' => \Modules\MultiTenancyCore\app\Models\SaasSetting::get('footer_show_credits', true),
            'creator_name' => \Modules\MultiTenancyCore\app\Models\SaasSetting::get('footer_creator_name', config('variables.creatorName')),
            'creator_url' => \Modules\MultiTenancyCore\app\Models\SaasSetting::get('footer_creator_url', ''),
        ];
    }
@endphp

<!-- Footer: Start -->
<footer class="landing-footer bg-body footer-text">
  <div class="footer-top position-relative overflow-hidden z-1">
    <img src="{{asset('assets/img/front-pages/backgrounds/footer-bg.png')}}" alt="footer bg"
         class="footer-bg banner-bg-img z-n1" />
    <div class="container">
      <div class="row gx-0 gy-6 g-lg-10">
        <div class="col-lg-5">
          <a href="/" class="app-brand-link mb-6">
            <span class="app-brand-logo demo">
              <img src="{{asset('assets/img/logo.png')}}" alt="Logo" width="27">
            </span>
            <span class="app-brand-text demo text-white fw-bold ms-2 ps-1">{{ $footerSettings['company_name'] ?? config('app.name') }}</span>
          </a>
          <p class="footer-text footer-logo-description mb-6">
            {{ $footerSettings['description'] ?? config('variables.templateDescription') }}
          </p>
          @if(!empty($footerSettings['facebook_url']) || !empty($footerSettings['twitter_url']) || !empty($footerSettings['linkedin_url']) || !empty($footerSettings['instagram_url']))
          <div class="footer-social-icons">
            @if(!empty($footerSettings['facebook_url']))
              <a href="{{ $footerSettings['facebook_url'] }}" target="_blank" class="btn btn-icon btn-sm btn-facebook me-2">
                <i class="bx bxl-facebook"></i>
              </a>
            @endif
            @if(!empty($footerSettings['twitter_url']))
              <a href="{{ $footerSettings['twitter_url'] }}" target="_blank" class="btn btn-icon btn-sm btn-twitter me-2">
                <i class="bx bxl-twitter"></i>
              </a>
            @endif
            @if(!empty($footerSettings['linkedin_url']))
              <a href="{{ $footerSettings['linkedin_url'] }}" target="_blank" class="btn btn-icon btn-sm btn-linkedin me-2">
                <i class="bx bxl-linkedin"></i>
              </a>
            @endif
            @if(!empty($footerSettings['instagram_url']))
              <a href="{{ $footerSettings['instagram_url'] }}" target="_blank" class="btn btn-icon btn-sm btn-instagram">
                <i class="bx bxl-instagram"></i>
              </a>
            @endif
          </div>
          @endif
        </div>
        
        @if(!empty($footerSettings['privacy_url']) || !empty($footerSettings['terms_url']) || !empty($footerSettings['support_url']) || !empty($footerSettings['docs_url']))
        <div class="col-lg-3 col-md-6">
          <h6 class="footer-title mb-4">{{ __('Quick Links') }}</h6>
          <ul class="list-unstyled mb-0">
            @if(!empty($footerSettings['privacy_url']))
              <li class="mb-3">
                <a href="{{ $footerSettings['privacy_url'] }}" class="footer-link">{{ __('Privacy Policy') }}</a>
              </li>
            @endif
            @if(!empty($footerSettings['terms_url']))
              <li class="mb-3">
                <a href="{{ $footerSettings['terms_url'] }}" class="footer-link">{{ __('Terms of Service') }}</a>
              </li>
            @endif
            @if(!empty($footerSettings['support_url']))
              <li class="mb-3">
                <a href="{{ $footerSettings['support_url'] }}" class="footer-link">{{ __('Support') }}</a>
              </li>
            @endif
            @if(!empty($footerSettings['docs_url']))
              <li class="mb-3">
                <a href="{{ $footerSettings['docs_url'] }}" class="footer-link">{{ __('Documentation') }}</a>
              </li>
            @endif
          </ul>
        </div>
        @endif
      </div>
    </div>
  </div>
  <div class="footer-bottom py-3 py-md-5">
    <div class="container d-flex flex-wrap justify-content-between flex-md-row flex-column text-center text-md-start">
      <div class="mb-2 mb-md-0">
        <span class="footer-bottom-text">©
          <script>
          document.write(new Date().getFullYear());
          </script>
        </span>
        <span class="footer-bottom-text">{{ $footerSettings['company_name'] ?? config('app.name') }}. {{ $footerSettings['copyright_text'] ?? 'All rights reserved' }}</span>
        @if($footerSettings['show_credits'] ?? true)
          <span class="footer-bottom-text"> Made with ❤️ by 
            @if(!empty($footerSettings['creator_url']))
              <a href="{{ $footerSettings['creator_url'] }}" target="_blank" class="text-white">{{ $footerSettings['creator_name'] ?? config('variables.creatorName') }}</a>
            @else
              <span class="text-white">{{ $footerSettings['creator_name'] ?? config('variables.creatorName') }}</span>
            @endif
          </span>
        @endif
      </div>
    </div>
  </div>
</footer>
<!-- Footer: End -->