@if ($crud->hasAccess('show', $entry))
  <a href="{{ backpack_url('/client/delivery-note/' . $entry->getKey() . '/print') }}" class="btn btn-sm btn-success" target="_blank" title="Cetak Surat Jalan">
    <i class="la la-print"></i>
  </a>
@endif
