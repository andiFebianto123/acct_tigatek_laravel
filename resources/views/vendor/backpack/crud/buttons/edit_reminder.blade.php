<a href="javascript:void(0)"
   onclick="editReminder(this)"
   data-bs-toggle="modal"
   data-bs-target="#modalEdit"
   data-route="{{ url(config('backpack.base.route_prefix').'/billing/billing-reminder/'.$entry->id.'/edit?code_billing='.urlencode($entry->code_billing).'&company_id='.$entry->company_id) }}"
   class="btn btn-sm btn-primary"
   title="{{ trans('backpack::crud.billing_reminder.title_modal_edit') }}">
   <i class="la la-pen"></i>
</a>

@push('after_scripts') @if (request()->ajax()) @endpush @endif
<script>
    if (typeof editReminder != 'function') {
        function editReminder(button) {
            var btn = $(button);
            var route = btn.attr('data-route');
            var title = "{{ trans('backpack::crud.billing_reminder.title_modal_edit') }}";

            $('#modalEdit .modal-title').html(title);
            $('#modalEdit .modal-body').html('<div class="text-center p-3"><span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...</div>');

            $.ajax({
                url: route,
                type: 'GET',
                dataType: 'json',
                success: function(result) {
                    if (result.html) {
                        $('#modalEdit .modal-body').html(result.html);
                    } else {
                        swal("Error", "Could not load reminder form", "error");
                    }
                },
                error: function(xhr) {
                    swal("Error", "An error occurred while loading the form", "error");
                }
            });
        }
    }
</script>
@if (!request()->ajax()) @endpush @endif
