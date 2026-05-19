@if ($crud->hasAccess('show', $entry))
  <a href="{{ backpack_url('/client/quotation/' . $entry->getKey() . '/print') }}" class="btn btn-sm btn-success" target="_blank" title="Cetak Quotation">
    <i class="la la-print"></i>
  </a>
@endif
