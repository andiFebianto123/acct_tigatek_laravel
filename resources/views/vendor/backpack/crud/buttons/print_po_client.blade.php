@if ($crud->hasAccess('show', $entry))
  <a href="{{ backpack_url('/client/po/' . $entry->getKey() . '/print') }}" class="btn btn-sm btn-success" target="_blank">
    <i class="la la-print"></i> 
  </a>
@endif
