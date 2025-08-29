@php
  use Illuminate\Support\Facades\Session;
  $containerFooter = (isset($configData['contentLayout']) && $configData['contentLayout'] === 'compact') ? 'container-xxl' : 'container-fluid';
@endphp

  <!-- Footer-->
<footer class="content-footer footer bg-footer-theme">
  <div class="{{ $containerFooter }}">
    <div class="footer-container d-flex align-items-center justify-content-between py-4 flex-md-row flex-column">
      <div class="text-body">
        ©
        <script>document.write(new Date().getFullYear());</script>
        @if(isset($settings->footer_text) && $settings->footer_text)
          {!! $settings->footer_text !!}
        @else
          , madesss made with with ❤️ by <a href="{{ (!empty(config('variables.creatorUrl')) ? config('variables.creatorUrl') : '') }}"
                             target="_blank"
                             class="footer-link">{{ (!empty(config('variables.creatorName')) ? config('variables.creatorName') : '') }}</a>
        @endif

        {{-- Mobile Version Display --}}
        <span class="d-inline-block d-lg-none ms-2 text-muted">
          | v{{ config('app.version', config('variables.templateVersion')) }}
        </span>
      </div>
      <div class="d-none d-lg-inline-block">
        {{-- Version Display --}}
        <span class="footer-link me-4 text-muted">
          v{{ config('app.version', config('variables.templateVersion')) }}
          @if(config('app.version_stage') && config('app.version_stage') !== 'stable')
            <span class="badge bg-label-{{ config('app.version_stage') === 'alpha' ? 'danger' : (config('app.version_stage') === 'beta' ? 'warning' : 'info') }} ms-1">
              {{ ucfirst(config('app.version_stage')) }}
            </span>
          @endif
        </span>

        @if(!isset($settings->show_footer_links) || $settings->show_footer_links)
          <a href="{{ config('variables.licenseUrl') ? config('variables.licenseUrl') : '#' }}" class="footer-link me-4" target="_blank">License</a>
          <a href="{{ config('variables.moreThemes') ? config('variables.moreThemes') : '#' }}" target="_blank" class="footer-link me-4">More Themes</a>
          <a href="{{ config('variables.documentation') ? config('variables.documentation').'/laravel-introduction.html' : '#' }}" target="_blank" class="footer-link me-4">Documentation</a>
          <a href="{{ config('variables.support') ? config('variables.support') : '#' }}" target="_blank" class="footer-link d-none d-sm-inline-block">Support</a>
        @endif

        <!-- License Status with icon-->
        @if(config('custom.custom.activationService'))
          <a href="{{route('activation.index')}}"
             data-bs-toggle="tooltip"
             class="footer-link me-4"
             title="You're running a genuine copy.">
            <span class="footer-link-text">License Status</span>
          </a>
        @endif
      </div>
    </div>
    {{-- Development Warning for Alpha/Beta --}}
    @if(config('app.version_stage') && in_array(config('app.version_stage'), ['alpha', 'beta']))
      <div class="text-center py-2 bg-{{ config('app.version_stage') === 'alpha' ? 'danger' : 'warning' }} bg-opacity-10">
        <small class="text-{{ config('app.version_stage') === 'alpha' ? 'danger' : 'warning' }}">
          <i class="bx bx-error-circle me-1"></i>
          @if(config('app.version_stage') === 'alpha')
            <strong>Alpha Version:</strong> Under heavy development - NOT for production use
          @else
            <strong>Beta Version:</strong> Testing phase - Use with caution
          @endif
        </small>
      </div>
    @endif
  </div>
</footer>
<!--/ Footer-->
