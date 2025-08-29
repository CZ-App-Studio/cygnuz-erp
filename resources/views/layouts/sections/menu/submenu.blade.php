@php
  use App\Services\AddonService\IAddonService;
  use Illuminate\Support\Facades\Route;
  $addonService = app(IAddonService::class);

  // Access pinnedMenuItems from the parent scope
  $pinnedMenuItems = $pinnedMenuItems ?? [];
@endphp

<ul class="menu-sub">
  @if (isset($menu))
    @foreach ($menu as $submenu)
      {{-- Check billing menu permissions if available --}}
      @if(isset($canAccessBillingMenu) && isset($submenu->slug))
        @php
          $submenuSlug = is_array($submenu->slug) ? $submenu->slug[0] : $submenu->slug;
          if (!$canAccessBillingMenu($submenuSlug)) {
            continue;
          }
        @endphp
      @endif

      @if(isset($submenu->addon))
        @php
          if(!$addonService->isAddonEnabled($submenu->addon)){
            continue;
          }
        @endphp
      @endif
      @if(isset($submenu->standardAddon))
        @php
          if(!$addonService->isAddonEnabled($submenu->standardAddon,true)){
            continue;
          }
        @endphp
      @endif

      {{-- active menu method --}}
      @php
        $activeClass = null;
        $active = $configData["layout"] === 'vertical' ? 'active open':'active';
        $currentRouteName =  Route::currentRouteName();

        // Function to recursively check if any nested submenu is active
        $isNestedSubmenuActive = function($submenuItems) use ($currentRouteName, &$isNestedSubmenuActive) {
          foreach ($submenuItems as $item) {
            if (isset($item->slug)) {
              if (is_array($item->slug)) {
                foreach($item->slug as $slug) {
                  if ($currentRouteName === $slug || str_starts_with($currentRouteName, $slug . '.')) {
                    return true;
                  }
                }
              } else {
                if ($currentRouteName === $item->slug || str_starts_with($currentRouteName, $item->slug . '.')) {
                  return true;
                }
              }
            }
            // Check nested submenus
            if (isset($item->submenu) && $isNestedSubmenuActive($item->submenu)) {
              return true;
            }
          }
          return false;
        };

        // Check if current submenu or its slug matches
        if ($currentRouteName === $submenu->slug) {
            $activeClass = 'active';
        }
        elseif (isset($submenu->submenu)) {
          $isActive = false;
          
          // Check if current submenu's slug matches
          if (isset($submenu->slug)) {
            if (is_array($submenu->slug)) {
              foreach($submenu->slug as $slug){
                if ($currentRouteName === $slug || str_starts_with($currentRouteName, $slug . '.')) {
                  $isActive = true;
                  break;
                }
              }
            } else {
              if ($currentRouteName === $submenu->slug || str_starts_with($currentRouteName, $submenu->slug . '.')) {
                $isActive = true;
              }
            }
          }
          
          // Also check if any nested submenu is active
          if (!$isActive && $isNestedSubmenuActive($submenu->submenu)) {
            $isActive = true;
          }
          
          if ($isActive) {
            $activeClass = $active;
          }
        }

        // Determine if this submenu is pinnable (has a slug and doesn't have a submenu)
        $isPinnable = isset($submenu->slug) && !isset($submenu->submenu);
        $menuSlug = '';

        if ($isPinnable) {
          $menuSlug = is_array($submenu->slug) ? $submenu->slug[0] : $submenu->slug;
          $isPinned = in_array($menuSlug, $pinnedMenuItems);
        }
      @endphp

      <li class="menu-item {{$activeClass}}" @if($isPinnable) data-menu-slug="{{ $menuSlug }}" @endif>
        <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}"
           class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
           @if (isset($submenu->target) and !empty($submenu->target)) target="_blank" @endif>
          @if (isset($submenu->icon))
            <i class="{{ $submenu->icon }}"></i>
          @endif
          <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>
          @isset($submenu->badge)
            <div class="badge bg-{{ $submenu->badge[0] }} rounded-pill ms-auto">{{ $submenu->badge[1] }}</div>
          @endisset

          <!-- Add pin icon only to leaf menu items (no submenu) -->
          @if($isPinnable)
            <div class="menu-pin-icon" data-menu-slug="{{ $menuSlug }}">
              <i class="bx {{ $isPinned ? 'bxs-pin text-primary' : 'bx-pin' }} pin-icon"></i>
            </div>
          @endif
        </a>

        {{-- submenu --}}
        @if (isset($submenu->submenu))
          @include('layouts.sections.menu.submenu',['menu' => $submenu->submenu, 'pinnedMenuItems' => $pinnedMenuItems])
        @endif
      </li>
    @endforeach
  @endif
</ul>
