@php
    $isExpired = false;
    if ($entry->billable && $entry->billable->expired_date) {
        $isExpired = \Carbon\Carbon::parse($entry->billable->expired_date)->startOfDay()->lt(\Carbon\Carbon::today());
    }

    $hasInvoice = !empty($entry->has_invoice_this_month);
@endphp

@if (!$isExpired && !$hasInvoice)
<a href="javascript:void(0)" 
    onclick="recurringInvoice(this)" 
    data-bs-toggle="modal"
    data-bs-target="#modalCreate"
    data-route="{{ url(config('backpack.base.route_prefix').'/invoice-client/create?notification_id='.$entry->getKey()) }}" 
    class="btn btn-sm btn-info" 
    title="Buat Recurring Invoice"
    style="color: white; font-weight: bold;">
    <i class="la la-refresh"></i> Recurring Invoice
</a>
@endif

@push('after_scripts') @if (request()->ajax()) @endpush @endif
<script>
    if (typeof recurringInvoice != 'function') {
        function recurringInvoice(button) {
            var btn = $(button);
            var route = btn.attr('data-route');
            var title = btn.attr('title');
            
            if (typeof OpenCreateFormModal === 'function') {
                OpenCreateFormModal({
                    route: route,
                    modal: {
                        id: '#modalCreate',
                        title: title,
                    }
                });
            } else {
                swal({
                    title: "{{ trans('backpack::crud.loading') }}",
                    text: "{{ trans('backpack::crud.please_wait') }}",
                    icon: "info",
                    buttons: false,
                    closeOnClickOutside: false,
                    closeOnEsc: false,
                });

                $.ajax({
                    url: route,
                    type: 'GET',
                    success: function(result) {
                        swal.close();
                        if (result.html) {
                            $('#modalCreate .modal-title').html(title);
                            $('#modalCreate .modal-body').html(result.html);
                            $('#modalCreate').modal('show');
                        } else {
                            swal("Error", "Could not load recurring form", "error");
                        }
                    },
                    error: function(xhr) {
                        swal.close();
                        swal("Error", "An error occurred while loading the form", "error");
                    }
                });
            }
        }
    }
</script>
@if (!request()->ajax()) @endpush @endif
