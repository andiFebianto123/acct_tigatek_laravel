@php
    $settings = App\Models\Setting::first();
@endphp
@if (backpack_auth()->check())
<div class="{{ backpack_theme_config('classes.sidebar') }}" id="sidebar">
    <div class="sidebar-brand d-none d-md-flex">
        <a class="navbar-brand fs-6 text-decoration-none text-uppercase" href="{{ url(backpack_theme_config('home_link')) }}" title="{{ backpack_theme_config('project_name') }}">
            {{-- {!! backpack_theme_config('project_logo') !!} --}}
            @if ($settings?->logo_light != null)
            <img src="{{ asset("storage/logos/".$settings->logo_light) }}" width="200" height="100" class="sidebar-brand-full" />
            @else
            <img src="{{ asset('kp-logo.png') }}" width="200" height="100" class="sidebar-brand-full" />
            @endif
            {{-- Accounting App --}}
        </a>
    </div>
    <ul class="sidebar-nav" data-coreui="navigation" data-simplebar="init">
        <div class="simplebar-mask">
            <div class="simplebar-content-wrapper" tabindex="0" role="region" aria-label="scrollable content">
                <div class="simplebar-content">
                    @include(backpack_view('inc.sidebar_content'))
                </div>
            </div>
        </div>
    </ul>
</div>
@endif

@push('before_scripts')
  <script type="text/javascript">
    // Save default sidebar class
    let sidebar = document.querySelector('.sidebar');

    // Recover sidebar state
    sidebar.classList.toggle('hide', sessionStorage.getItem('sidebar-collapsed') === '1');
  </script>
@endpush

@push('after_scripts')
  <script>
      // Store sidebar state
      document.querySelector('.header-toggler').addEventListener('click', function() {
        sessionStorage.setItem('sidebar-collapsed', Number(sidebar.classList.contains('hide')));
        crud?.table?.fixedHeader.adjust();
      });

      $(document).ready(function() {
          function fetchBillingNotificationCount() {
              $.ajax({
                  url: "{{ backpack_url('billing/billing-notification/count') }}",
                  type: 'GET',
                  dataType: 'json',
                  success: function(response) {
                      let count = response.count || 0;
                      
                      // 1. Update the submenu badge
                      let submenuBadge = $('#menu-item-billing-notification .badge');
                      if (submenuBadge.length) {
                          if (count > 0) {
                              submenuBadge.text(count).show();
                          } else {
                              submenuBadge.hide();
                          }
                      }

                      // 2. Update the parent menu group badge dot
                      let groupBadgeDot = $('#menu-group-billing .group-badge-dot');
                      if (groupBadgeDot.length) {
                          if (count > 0) {
                              groupBadgeDot.addClass('show-dot');
                          } else {
                              groupBadgeDot.removeClass('show-dot');
                          }
                      }
                  }
              });
          }

          // Initial load
          fetchBillingNotificationCount();

          // Poll every 30 seconds for realtime count update
          setInterval(fetchBillingNotificationCount, 30000);
      });
  </script>
@endpush
