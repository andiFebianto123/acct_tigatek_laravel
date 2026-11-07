@if ($crud->hasAccess('delete'))
    <button type="button" class="btn btn-danger d-none" id="btn-bulk-delete-simcard">
        <i class="la la-trash"></i> <span>{{ trans('backpack::crud.delete') }}</span> (<span id="bulk-delete-simcard-count">0</span>)
    </button>
@endif

