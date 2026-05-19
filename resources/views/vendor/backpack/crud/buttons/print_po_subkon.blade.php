@if ($crud->hasAccess('show', $entry))
  <a href="{{ backpack_url('/vendor/purchase-order/' . $entry->getKey() . '/print') }}" class="btn btn-sm btn-success" target="_blank" title="Cetak PO Subkon">
    <i class="la la-print"></i>
  </a>
@endif
