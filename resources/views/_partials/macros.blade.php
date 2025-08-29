 @if(isset($settings->brand_logo_light) && $settings->brand_logo_light)
          <img src="{{ asset($settings->brand_logo_light) }}" alt="{{ $settings->brand_name ?? 'Logo' }}" width="27">
        @else
          <img src="{{ asset('assets/img/logo.png') }}" alt="Logo" width="27">
        @endif
