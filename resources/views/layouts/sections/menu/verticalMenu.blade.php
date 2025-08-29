@php
  use App\Services\AddonService\IAddonService;
  use Illuminate\Support\Facades\Route;
  use App\Models\UserMenuPreference;
  $configData = Helper::appClasses();
  $addonService = app(IAddonService::class);

  // Get pinned menu items for the current user
  $pinnedMenuItems = [];
  $pinnedMenuSlugs = [];
  $pinnedSubmenuSlugs = []; // New array to track pinned submenu items specifically
  if (auth()->check()) {
    $pinnedMenuPreferences = UserMenuPreference::where('user_id', auth()->id())
      ->where('is_pinned', true)
      ->orderBy('display_order')
      ->get();

    $pinnedMenuSlugs = $pinnedMenuPreferences->pluck('menu_slug')->toArray();

    // Identify which slugs belong to submenu items
    foreach($menuData[0]->menu as $menu) {
      if(isset($menu->submenu)) {
        foreach($menu->submenu as $submenu) {
          $submenuSlug = is_array($submenu->slug) ? $submenu->slug[0] : $submenu->slug;
          if(in_array($submenuSlug, $pinnedMenuSlugs)) {
            $pinnedSubmenuSlugs[] = $submenuSlug;
          }
        }
      }
    }
  }

  // Pre-calculate which headers should be hidden (all children pinned)
  $headersToHide = [];
  $currentHeader = null;
  $visibleItemsUnderHeader = 0;

  foreach ($menuData[0]->menu as $menuItem) {
    if (isset($menuItem->menuHeader)) {
      // Save previous header data before moving to new header
      if ($currentHeader !== null && $visibleItemsUnderHeader === 0) {
        $headersToHide[] = $currentHeader;
      }

      $currentHeader = $menuItem->menuHeader;
      $visibleItemsUnderHeader = 0;
    } elseif ($currentHeader !== null) {
      // Skip items with addon that is not enabled
      if (isset($menuItem->addon) && !$addonService->isAddonEnabled($menuItem->addon)) {
        continue;
      }

      // Count items that aren't pinned (they will be visible under the header)
      $menuSlug = isset($menuItem->slug) ? (is_array($menuItem->slug) ? $menuItem->slug[0] : $menuItem->slug) : '';
      if (!isset($menuItem->menuHeader) && isset($menuItem->slug) && !isset($menuItem->submenu) &&
          !in_array($menuSlug, $pinnedMenuSlugs)) {
        $visibleItemsUnderHeader++;
      }

      // For items with submenu, check if they'll be visible
      if (isset($menuItem->submenu)) {
        $visibleItemsUnderHeader++;
      }
    }
  }

  // Check the last header section
  if ($currentHeader !== null && $visibleItemsUnderHeader === 0) {
    $headersToHide[] = $currentHeader;
  }
@endphp

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">

  <!-- ! Hide app brand if navbar-full -->
  @if(!isset($navbarFull))
    <div class="app-brand demo">
      <a href="{{url('/')}}" class="app-brand-link">
        <span
          class="app-brand-logo demo">
           @if(isset($settings->brand_logo_light) && $settings->brand_logo_light)
             <img src="{{ asset($settings->brand_logo_light) }}" alt="{{ $settings->brand_name ?? 'Logo' }}" width="27">
           @else
             <img src="{{asset('assets/img/logo.png')}}" alt="Logo" width="27">
           @endif
        </span>
        <span class="app-brand-text demo menu-text fw-bold ms-2">{{ $settings->brand_name ?? config('variables.templateName') }}</span>
      </a>

      <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
        <i class="bx bx-chevron-left bx-sm align-middle"></i>
      </a>
    </div>
  @endif

  <div class="menu-inner-shadow"></div>

  <ul class="menu-inner py-1">
    <!-- Pinned Menu Section -->
    @if(count($pinnedMenuSlugs) > 0)
      <li class="menu-header small text-uppercase">
        <span class="menu-header-text">{{ __('PINNED') }}</span>
      </li>

      <!-- Wrap pinned items in proper li elements -->
      @foreach($menuData[0]->menu as $pinnedMenu)
        <!-- Check top-level menu items -->
        @if(!isset($pinnedMenu->menuHeader) && isset($pinnedMenu->slug) && in_array(is_array($pinnedMenu->slug) ? $pinnedMenu->slug[0] : $pinnedMenu->slug, $pinnedMenuSlugs) && !isset($pinnedMenu->submenu))
          @php
            $isMenuActive = false;
            $menuSlug = is_array($pinnedMenu->slug) ? $pinnedMenu->slug[0] : $pinnedMenu->slug;

            // Check if the menu is active
            if(isset($pinnedMenu->slug)) {
              if(is_array($pinnedMenu->slug)) {
                foreach($pinnedMenu->slug as $slug) {
                  if(str_contains(Route::currentRouteName(), $slug)) {
                    $isMenuActive = true;
                    break;
                  }
                }
              } else {
                $isMenuActive = str_contains(Route::currentRouteName(), $pinnedMenu->slug) ? true : false;
              }
            }

            // Skip if it has an addon that is not enabled
            if(isset($pinnedMenu->addon) && !$addonService->isAddonEnabled($pinnedMenu->addon)) {
              continue;
            }
          @endphp

          <li class="menu-item {{ $isMenuActive ? 'active' : '' }} pinned-item" data-menu-slug="{{ $menuSlug }}">
            <a href="{{ isset($pinnedMenu->url) ? url($pinnedMenu->url) : 'javascript:void(0)' }}" class="{{ isset($pinnedMenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}">
              @if(isset($pinnedMenu->icon))
                <i class="{{ $pinnedMenu->icon }}"></i>
              @endif
              <div>{{ isset($pinnedMenu->name) ? __($pinnedMenu->name) : '' }}</div>

              <div class="menu-pin-icon" data-menu-slug="{{ $menuSlug }}">
                <i class="bx bxs-pin text-primary pin-icon"></i>
              </div>
            </a>
          </li>
        @endif

        <!-- Check for pinned submenu items -->
        @if(isset($pinnedMenu->submenu))
          @foreach($pinnedMenu->submenu as $submenu)
            @php
              if(isset($submenu->addon) && !$addonService->isAddonEnabled($submenu->addon)) {
                continue;
              }

              if(isset($submenu->standardAddon) && !$addonService->isAddonEnabled($submenu->standardAddon, true)) {
                continue;
              }

              $submenuSlug = is_array($submenu->slug) ? $submenu->slug[0] : $submenu->slug;

              // Skip if this submenu item is not pinned
              if(!in_array($submenuSlug, $pinnedMenuSlugs)) {
                continue;
              }

              // Important: Skip if this is a parent menu (has its own submenu)
              if(isset($submenu->submenu)) {
                continue;
              }

              $isSubmenuActive = false;
              if(isset($submenu->slug)) {
                if(is_array($submenu->slug)) {
                  foreach($submenu->slug as $slug) {
                    if(str_contains(Route::currentRouteName(), $slug)) {
                      $isSubmenuActive = true;
                      break;
                    }
                  }
                } else {
                  $isSubmenuActive = str_contains(Route::currentRouteName(), $submenu->slug) ? true : false;
                }
              }
            @endphp

            <li class="menu-item {{ $isSubmenuActive ? 'active' : '' }} pinned-item" data-menu-slug="{{ $submenuSlug }}">
              <a href="{{ isset($submenu->url) ? url($submenu->url) : 'javascript:void(0)' }}" class="{{ isset($submenu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}">
                @if(isset($submenu->icon))
                  <i class="{{ $submenu->icon }}"></i>
                @elseif(isset($pinnedMenu->icon))
                  <i class="{{ $pinnedMenu->icon }}"></i>
                @endif
                <div>{{ isset($submenu->name) ? __($submenu->name) : '' }}</div>

                <div class="menu-pin-icon" data-menu-slug="{{ $submenuSlug }}">
                  <i class="bx bxs-pin text-primary pin-icon"></i>
                </div>
              </a>
            </li>
          @endforeach
        @endif
      @endforeach
    @endif

    @php
      $currentHeader = null;
    @endphp

    @foreach ($menuData[0]->menu as $menu)
      @if(isset($menu->addon))
        @php
          if(!$addonService->isAddonEnabled($menu->addon)){
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

      {{-- Skip menu items that are already pinned --}}
      @if(!isset($menu->menuHeader) && isset($menu->slug) && !isset($menu->submenu) && in_array(is_array($menu->slug) ? $menu->slug[0] : $menu->slug, $pinnedMenuSlugs))
        @continue
      @endif

      {{-- menu headers - only show if not in hidden headers list --}}
      @if (isset($menu->menuHeader))
        @php
          $currentHeader = $menu->menuHeader;
        @endphp

        @if(!in_array($currentHeader, $headersToHide))
          <li class="menu-header small text-uppercase">
            <span class="menu-header-text">{{ __($menu->menuHeader) }}</span>
          </li>
        @endif
      @else
        {{-- Check billing menu permissions if available --}}
        @if(isset($canAccessBillingMenu) && isset($menu->slug))
          @php
            $checkSlug = is_array($menu->slug) ? $menu->slug[0] : $menu->slug;
            if (!$canAccessBillingMenu($checkSlug)) {
              continue;
            }
          @endphp
        @endif

        {{-- active menu method --}}
        @php
          $activeClass = null;
          $currentRouteName = Route::currentRouteName();

          // Function to recursively check if any submenu is active
          $isSubmenuActive = function($submenuItems) use ($currentRouteName, &$isSubmenuActive) {
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
              if (isset($item->submenu) && $isSubmenuActive($item->submenu)) {
                return true;
              }
            }
            return false;
          };

          if ($currentRouteName === $menu->slug) {
            $activeClass = 'active';
          }
          elseif (isset($menu->submenu)) {
            // Check if the current menu's slug matches
            $isActive = false;
            if (isset($menu->slug)) {
              if (is_array($menu->slug)) {
                foreach($menu->slug as $slug){
                  if ($currentRouteName === $slug || str_starts_with($currentRouteName, $slug . '.')) {
                    $isActive = true;
                    break;
                  }
                }
              } else {
                if ($currentRouteName === $menu->slug || str_starts_with($currentRouteName, $menu->slug . '.')) {
                  $isActive = true;
                }
              }
            }
            
            // Also check if any submenu is active (recursively)
            if (!$isActive && $isSubmenuActive($menu->submenu)) {
              $isActive = true;
            }
            
            if ($isActive) {
              $activeClass = 'active open';
            }
          }

          // Get the menu slug for checking if it's pinned
          $menuSlug = isset($menu->slug) ? (is_array($menu->slug) ? $menu->slug[0] : $menu->slug) : '';
          $isPinned = in_array($menuSlug, $pinnedMenuSlugs);
        @endphp

        {{-- main menu - only show if the current header is not hidden --}}
        @if(!isset($currentHeader) || !in_array($currentHeader, $headersToHide))
          <li class="menu-item {{$activeClass}}" @if(isset($menu->slug) && !isset($menu->submenu)) data-menu-slug="{{ $menuSlug }}" @endif>
            <a href="{{ isset($menu->url) ? url($menu->url) : 'javascript:void(0);' }}"
               class="{{ isset($menu->submenu) ? 'menu-link menu-toggle' : 'menu-link' }}"
               @if (isset($menu->target) and !empty($menu->target)) target="_blank" @endif>
              @isset($menu->icon)
                <i class="{{ $menu->icon }}"></i>
              @endisset
              <div>{{ isset($menu->name) ? __($menu->name) : '' }}</div>
              @isset($menu->badge)
                <div class="badge bg-{{ $menu->badge[0] }} rounded-pill ms-auto">{{ $menu->badge[1] }}</div>
              @endisset

              <!-- Pin icon for top-level menu items that don't have submenus -->
              @if(!isset($menu->menuHeader) && isset($menu->slug) && !isset($menu->submenu))
                <div class="menu-pin-icon" data-menu-slug="{{ $menuSlug }}">
                  <i class="bx {{ $isPinned ? 'bxs-pin text-primary' : 'bx-pin' }} pin-icon"></i>
                </div>
              @endif
            </a>

            {{-- submenu --}}
            @isset($menu->submenu)
              @include('layouts.sections.menu.submenu',['menu' => $menu->submenu, 'pinnedMenuItems' => $pinnedMenuSlugs, 'canAccessBillingMenu' => $canAccessBillingMenu ?? null])
            @endisset
          </li>
        @endif
      @endif
    @endforeach
  </ul>

  <!-- Minimal Footer Resources -->
  <div class="menu-footer mt-auto p-2 border-top d-flex justify-content-between align-items-center">
    <div class="d-flex gap-2">
      <a href="#" title="{{ __('Documentation') }}" class="text-muted">
        <i class="bx bx-book-open"></i>
      </a>
      <a href="#" title="{{ __('Support') }}" class="text-muted">
        <i class="bx bx-support"></i>
      </a>
      <a href="{{ route('admin.system-status.index') }}" title="{{ __('System Status') }}" class="text-muted">
        <i class="bx bx-server"></i>
        <span class="status-dot bg-success"></span>
      </a>
    </div>
    <small class="text-muted">v{{ config('app.version', '1.0') }}</small>
  </div>

</aside>
