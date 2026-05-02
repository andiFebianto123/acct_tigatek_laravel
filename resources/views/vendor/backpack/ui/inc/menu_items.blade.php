@php
    $user = backpack_user();
    $roles = $user->getRoles();
    $permissions = $user->getAllPermissions();
    // "AKSES SEMUA VIEW PROJECT
@endphp

{{-- <x-menu-item-custom
    title="andi test"
    :link="backpack_url('dashboard')"
    :logo_url="asset('storage/logos/menu/logo-dashboard.png')"
/>

<x-menu-group-custom
    title="Group Item"
    :link="backpack_url('vendor')"
    :logo_url="asset('')"
>
    <x-menu-group-item-custom title="Group Item Sub 1" :link="backpack_url('vendor/purchase-order-tai')" :logo_url="asset('vendor-purchase-order-logo.png')" />
    <x-menu-group-item-custom title="Group Item Sub 1" :link="backpack_url('vendor/purchase-order')" :logo_url="asset('vendor-purchase-order-logo.png')" />
</x-menu-group-custom> --}}


@if($permissions->whereIn('name', [
            // 'AKSES SEMUA VIEW ACCOUNTING',
            // 'AKSES SEMUA MENU ACCOUNTING',
            'MENU INDEX DASHBOARD',
        ])->count() > 0)
    <x-menu-item-custom
        title="{{trans('backpack::crud.menu.dashboard')}}"
        :logo_url="asset('storage/logos/menu/logo-dashboard.png')"
        :link="backpack_url('dashboard')" />
@endif

@if($permissions->whereIn('name', [
            // 'AKSES SEMUA VIEW ACCOUNTING',
            // 'AKSES SEMUA MENU ACCOUNTING',
            // 'AKSES MENU VENDOR'
            'MENU INDEX VENDOR DAFTAR SUBKON',
            'MENU INDEX VENDOR PO',
            'MENU INDEX VENDOR SPK'
        ])->count() > 0)
<x-menu-group-custom
    title="{{trans('backpack::crud.menu.vendor_subkon')}}"
    :logo_url="asset('storage/logos/menu/logo-vendor.png')"
    :link="backpack_url('vendor')" >
    @if($permissions->whereIn('name', [
            'MENU INDEX VENDOR DAFTAR SUBKON',
        ])->count() > 0)
        <x-menu-group-item-custom title="{{trans('backpack::crud.menu.list_subkon')}}" icon="la la-circle-notch" :link="backpack_url('vendor/subkon')" />
    @endif
    @if($permissions->whereIn('name', [
            'MENU INDEX VENDOR PO',
        ])->count() > 0)
        <x-menu-group-item-custom title="{{ trans('backpack::crud.menu.po') }}" icon="la la-circle-notch" :link="backpack_url('vendor/purchase-order')" />
    @endif
    @if($permissions->whereIn('name', [
            'MENU INDEX VENDOR SPK',
        ])->count() > 0)
        <x-menu-group-item-custom title="{{ trans('backpack::crud.menu.spk') }}" icon="la la-circle-notch" :link="backpack_url('vendor/spk-trans')" />
    @endif
</x-menu-group-custom>
@endif

@if($permissions->whereIn('name', [
            // 'AKSES SEMUA VIEW ACCOUNTING',
            // 'AKSES SEMUA MENU ACCOUNTING',
            // 'AKSES MENU CLIENT'
            'MENU INDEX CLIENT DAFTAR CLIENT',
            'MENU INDEX CLIENT PO',
        ])->count() > 0)
<x-menu-group-custom
    title="{{trans('backpack::crud.menu.client')}}"
    :logo_url="asset('storage/logos/menu/logo-client.png')"
    :link="backpack_url('client')" >
    @if($permissions->contains('name', 'MENU INDEX CLIENT DAFTAR CLIENT'))
        <x-menu-group-item-custom title="{{trans('backpack::crud.menu.list_client')}}" icon="la la-circle-notch" :link="backpack_url('client/client-list')" />
    @endif
    @if($permissions->contains('name', 'MENU INDEX CLIENT PO'))
        <x-menu-group-item-custom title="{{trans('backpack::crud.menu.client_po')}}" icon="la la-circle-notch" :link="backpack_url('client/po')" />
    @endif
</x-menu-group-custom>
@endif

@if($permissions->whereIn('name', [
            // 'AKSES SEMUA VIEW ACCOUNTING',
            // 'AKSES SEMUA MENU ACCOUNTING',
            // 'AKSES MENU FA'
            'MENU INDEX FA VOUCHER',
            'MENU INDEX FA PEMBAYARAN',
        ])->count() > 0)
<x-menu-group-custom
    title="{{trans('backpack::crud.menu.fa')}}"
    :logo_url="asset('storage/logos/menu/logo-fa.png')"
    :link="backpack_url('fa')" >
    @if($permissions->contains('name', 'MENU INDEX FA VOUCHER'))
    <x-menu-group-item-custom title="{{trans('backpack::crud.menu.voucher')}}" icon="la la-circle-notch" :link="backpack_url('fa/voucher')" />
    @endif
    @if($permissions->contains('name', 'MENU INDEX FA PEMBAYARAN'))
    <x-menu-group-item-custom title="{{trans('backpack::crud.menu.voucher_payment')}}" icon="la la-circle-notch" :link="backpack_url('fa/voucher-payment')" />
    @endif
</x-menu-group-custom>
@endif

@if($permissions->contains('name', 'MENU INDEX RENCANA PEMBAYARAN'))
    <x-menu-item-custom
        title="{{trans('backpack::crud.menu.voucher_payment_plan')}}"
        :logo_url="asset('storage/logos/menu/logo-fa.png')"
        :link="backpack_url('voucher-payment-plan')" />
@endif

@if($permissions->whereIn('name', [
            // 'AKSES SEMUA VIEW ACCOUNTING',
            // 'AKSES SEMUA MENU ACCOUNTING'
            'MENU INDEX ARUS REKENING KAS',
            'MENU INDEX ARUS REKENING PINJAMAN',
        ])->count() > 0)
<x-menu-group-custom
    title="{{trans('backpack::crud.menu.cash_flow')}}"
    :logo_url="asset('storage/logos/menu/logo-arusrek.png')"
    :link="backpack_url('cash-flow')" >
    @if($permissions->contains('name', 'MENU INDEX ARUS REKENING KAS'))
    <x-menu-group-item-custom title="{{trans('backpack::crud.menu.cash_flow_cash')}}" icon="la la-circle-notch" :link="backpack_url('cash-flow/cast-accounts')" />
    @endif
    @if($permissions->contains('name', 'MENU INDEX ARUS REKENING PINJAMAN'))
    <x-menu-group-item-custom title="{{trans('backpack::crud.menu.cash_flow_loan')}}" icon="la la-circle-notch" :link="backpack_url('cash-flow/cast-account-loan')" />
    @endif
</x-menu-group-custom>
@endif

@if($permissions->whereIn('name', [
            'MENU INDEX LAPORAN KEUANGAN COA',
            'MENU INDEX LAPORAN KEUANGAN LABA RUGI',
            'MENU INDEX LAPORAN KEUANGAN NERACA',
            'MENU INDEX LAPORAN KEUANGAN DAFTAR ASET',
        ])->count() > 0)
<x-menu-group-custom
    title="{{trans('backpack::crud.menu.finance_report')}}"
    :logo_url="asset('storage/logos/menu/logo-lapkeu.png')"
    :link="backpack_url('finance-report')" >
    @if($permissions->contains('name', 'MENU INDEX LAPORAN KEUANGAN COA'))
    <x-menu-group-item-custom title="{{trans('backpack::crud.menu.expense_account')}}" icon="la la-circle-notch" :link="backpack_url('finance-report/expense-account')" />
    @endif
    @if($permissions->contains('name', 'MENU INDEX LAPORAN KEUANGAN LABA RUGI'))
    <x-menu-group-item-custom title="{{trans('backpack::crud.menu.profit_lost')}}" icon="la la-circle-notch" :link="backpack_url('finance-report/profit-lost')" />
    @endif
    @if($permissions->contains('name', 'MENU INDEX LAPORAN KEUANGAN NERACA'))
    <x-menu-group-item-custom title="{{trans('backpack::crud.menu.balance_sheet')}}" icon="la la-circle-notch" :link="backpack_url('finance-report/balance-sheet')" />
    @endif
    @if($permissions->contains('name', 'MENU INDEX LAPORAN KEUANGAN DAFTAR ASET'))
    <x-menu-group-item-custom title="{{trans('backpack::crud.menu.asset')}}" icon="la la-circle-notch" :link="backpack_url('finance-report/list-asset')" />
    @endif
</x-menu-group-custom>
@endif

@if($permissions->whereIn('name', [
            "MENU INDEX INVOICE",
        ])->count() > 0)
    <x-menu-item-custom
        title="{{ trans('backpack::crud.menu.invoice_client') }}"
        :logo_url="asset('storage/logos/menu/logo-invoice.png')"
        :link="backpack_url('invoice-client')" />
@endif

@if($permissions->whereIn('name', [
            // 'AKSES SEMUA VIEW PROJECT',
            // 'AKSES SEMUA MENU PROJECT',
            // 'AKSES SEMUA DATA PROYEKSI PEKERJAAN PROJECT',
            // 'AKSES SEMUA STATUS PENAWARAN PROJECT',
            // 'AKSES SEMUA DAFTAR PENAWARAN PROJECT',
            'MENU INDEX MONITORING PROYEK STATUS PROYEK',
            'MENU INDEX MONITORING PROYEK STATUS PENAWARAN',
            'MENU INDEX MONITORING PROYEK PROYEKSI PEKERJAAN',
            'MENU INDEX MONITORING PROYEK DAFTAR PROYEK',
            'MENU INDEX MONITORING PROYEK DAFTAR PENAWARAN',
            'MENU INDEX MONITORING PROYEK PROYEK REPORT',
            'MENU INDEX MONITORING PROYEK PROYEK SYSTEM SETUP',
        ])->count() > 0)
<x-menu-group-custom
    title="{{trans('backpack::crud.menu.monitoring_project')}}"
    :logo_url="asset('storage/logos/menu/logo-monitoring.png')"
    :link="backpack_url('monitoring')" >

    @if($permissions->whereIn('name', [
                // 'AKSES SEMUA VIEW PROJECT',
                // 'AKSES SEMUA MENU PROJECT',
                'MENU INDEX MONITORING PROYEK STATUS PROYEK'
            ])->count() > 0)
    <x-menu-group-item-custom title="{{trans('backpack::crud.project_status.title_header')}}" icon="la la-circle-notch" :link="backpack_url('monitoring/project-status')" />
    @endif

    @if($permissions->whereIn('name', [
            // 'AKSES SEMUA VIEW PROJECT',
            // 'AKSES SEMUA MENU PROJECT',
            // 'AKSES SEMUA STATUS PENAWARAN PROJECT'
            'MENU INDEX MONITORING PROYEK STATUS PENAWARAN',
        ])->count() > 0)
        <x-menu-group-item-custom title="{{trans('backpack::crud.menu.quotation_status')}}" icon="la la-circle-notch" :link="backpack_url('monitoring/quotation-status')" />
    @endif

    @if($permissions->whereIn('name', [
            // 'AKSES SEMUA VIEW PROJECT',
            // 'AKSES SEMUA MENU PROJECT',
            // 'AKSES SEMUA DATA PROYEKSI PEKERJAAN PROJECT'
            'MENU INDEX MONITORING PROYEK PROYEKSI PEKERJAAN',
        ])->count() > 0)
        <x-menu-group-item-custom title="{{trans('backpack::crud.quotation_check.title_header')}}" icon="la la-circle-notch" :link="backpack_url('monitoring/quotation-check')" />
    @endif


    @if($permissions->whereIn('name', [
            // 'AKSES SEMUA VIEW PROJECT',
            // 'AKSES SEMUA MENU PROJECT',
            // 'EDIT KOLOM PROGRES DAN KETERANGAN DAFTAR PROJECT'
            'MENU INDEX MONITORING PROYEK DAFTAR PROYEK'
        ])->count() > 0)
        <x-menu-group-item-custom title="{{trans('backpack::crud.menu.project_list')}}" icon="la la-circle-notch" :link="backpack_url('monitoring/project-list')" />
    @endif

    @if($permissions->whereIn('name', [
            // 'AKSES SEMUA VIEW PROJECT',
            // 'AKSES SEMUA MENU PROJECT',
            // 'AKSES SEMUA DAFTAR PENAWARAN PROJECT'
            'MENU INDEX MONITORING PROYEK DAFTAR PENAWARAN',
        ])->count() > 0)
        <x-menu-group-item-custom title="{{trans('backpack::crud.menu.list_quotation')}}" icon="la la-circle-notch" :link="backpack_url('monitoring/quotation')" />
    @endif

    @if($permissions->whereIn('name', [
                // 'AKSES SEMUA VIEW PROJECT',
                // 'AKSES SEMUA MENU PROJECT',
                'MENU INDEX MONITORING PROYEK PROYEK REPORT',
            ])->count() > 0)
        <x-menu-group-item-custom title="{{trans('backpack::crud.menu.project_report')}}" icon="la la-circle-notch" :link="backpack_url('monitoring/project-report')" />
    @endif

    @if($permissions->whereIn('name', [
                // 'AKSES SEMUA VIEW PROJECT',
                // 'AKSES SEMUA MENU PROJECT',
                'MENU INDEX MONITORING TRACKER',
            ])->count() > 0)
        <x-menu-group-item-custom title="{{trans('backpack::crud.menu.tracker')}}" icon="la la-circle-notch" :link="backpack_url('monitoring/tracker')" />
    @endif

    @if($permissions->whereIn('name', [
            // 'AKSES SEMUA VIEW PROJECT',
            // 'AKSES SEMUA MENU PROJECT',
            'MENU INDEX MONITORING PROYEK PROYEK SYSTEM SETUP',
        ])->count() > 0)
        <x-menu-group-item-custom title="{{trans('backpack::crud.menu.project_system_setup')}}" icon="la la-circle-notch" :link="backpack_url('monitoring/project-system-setup')" />
    @endif
</x-menu-group-custom>
@endif

{{-- @if($permissions->whereIn('name', [
            'MENU INDEX PENGATURAN USER',
            'MENU INDEX PENGATURAN ROLE',
            'MENU INDEX PENGATURAN PERMISSION',
            'MENU INDEX PENGATURAN SISTEM',
            'MENU INDEX PENGATURAN AKUN',
        ])->count() > 0) --}}
<x-menu-group-custom
    title="{{trans('backpack::crud.menu.setting')}}"
    :logo_url="asset('storage/logos/menu/logo-settings.png')"
    :link="backpack_url('setting')">
@if($roles->whereIn('name', ['Super Admin'])->count() > 0)
    <x-menu-group-item-custom title="Users" icon="la la-circle-notch" :link="backpack_url('setting/user')" />
    <x-menu-group-item-custom title="Roles" icon="la la-circle-notch" :link="backpack_url('setting/role')" />
    <x-menu-group-item-custom title="Permissions" icon="la la-circle-notch" :link="backpack_url('setting/permission')" />
    <x-menu-group-item-custom title="Pengaturan Sistem" icon="la la-circle-notch" :link="backpack_url('setting/system')" />
@endif
<x-menu-group-item-custom title="Pengaturan Akun" icon="la la-circle-notch" :link="backpack_url('setting/account')" />
</x-menu-group-custom>
{{-- @endif --}}



{{-- <li class="nav-group" aria-expanded="false"><a class="nav-link nav-group-toggle" href="#">
    <i class="la la-home nav-icon"></i> Hallo andi </a>
  <ul class="nav-group-items compact" style="height: 0px;">
    <li class="nav-item"><a class="nav-link" href="base/accordion.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Accordion</a></li>
    <li class="nav-item"><a class="nav-link" href="base/breadcrumb.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Breadcrumb</a></li>
    <li class="nav-item"><a class="nav-link" href="https://coreui.io/bootstrap/docs/components/calendar/" target="_blank"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Calendar
        <svg class="icon icon-sm ms-2">
          <use xlink:href="vendors/@coreui/icons/svg/free.svg#cil-external-link"></use>
        </svg><span class="badge badge-sm bg-danger ms-auto">PRO</span></a></li>
    <li class="nav-item"><a class="nav-link" href="base/cards.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Cards</a></li>
    <li class="nav-item"><a class="nav-link" href="base/carousel.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Carousel</a></li>
    <li class="nav-item"><a class="nav-link" href="base/collapse.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Collapse</a></li>
    <li class="nav-item"><a class="nav-link" href="base/list-group.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> List group</a></li>
    <li class="nav-item"><a class="nav-link" href="base/navs-tabs.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Navs &amp; Tabs</a></li>
    <li class="nav-item"><a class="nav-link" href="base/pagination.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Pagination</a></li>
    <li class="nav-item"><a class="nav-link" href="base/placeholders.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Placeholders</a></li>
    <li class="nav-item"><a class="nav-link" href="base/popovers.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Popovers</a></li>
    <li class="nav-item"><a class="nav-link" href="base/progress.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Progress</a></li>
    <li class="nav-item"><a class="nav-link" href="base/spinners.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Spinners</a></li>

    <li class="nav-group" aria-expanded="false"><a class="nav-link nav-group-toggle" href="#">
        <i class="nav-icon la la-puzzle-piece"></i> Icons</a>
      <ul class="nav-group-items compact" style="height: 0px;">
        <li class="nav-item"><a class="nav-link" href="icons/coreui-icons-free.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> CoreUI Free</a></li>
        <li class="nav-item"><a class="nav-link" href="icons/coreui-icons-brand.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> CoreUI Brand</a></li>
        <li class="nav-item"><a class="nav-link" href="icons/coreui-icons-flag.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> CoreUI Flag</a></li>

        <li class="nav-group" aria-expanded="false"><a class="nav-link nav-group-toggle" href="#">
            <i class="nav-icon la la-puzzle-piece"></i> Icons</a>
            <ul class="nav-group-items compact" style="height: 0px;">
                <li class="nav-item"><a class="nav-link" href="icons/coreui-icons-free.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> CoreUI Free</a></li>
                <li class="nav-item"><a class="nav-link" href="icons/coreui-icons-brand.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> CoreUI Brand</a></li>
            </ul>
        </li>
        <li class="nav-item"><a class="nav-link" href="icons/coreui-icons-flag.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> CoreUI Flag</a></li>
        <li class="nav-item"><a class="nav-link" href="icons/brands.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Brands</a></li>
      </ul>
    </li>

    <li class="nav-item"><a class="nav-link" href="base/tables.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Tables</a></li>
    <li class="nav-item"><a class="nav-link" href="base/tooltips.html"><span class="nav-icon"><span class="nav-icon-bullet"></span></span> Tooltips</a></li>
  </ul>
</li>


{{-- <x-backpack::menu-item title="Tags" icon="la la-question" :link="backpack_url('tag')" />
<x-backpack::menu-item title="Roles" icon="la la-question" :link="backpack_url('role')" /> --}}