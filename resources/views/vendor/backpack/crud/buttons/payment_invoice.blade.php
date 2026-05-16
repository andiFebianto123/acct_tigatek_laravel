@if ($entry->status != 'Paid' && $crud->hasAccess('update', $entry))
    <a href="javascript:void(0)" 
        onclick="paymentInvoice(this)" 
        data-bs-toggle="modal"
        data-bs-target="#modalCreate"
        data-route="{{ url(config('backpack.base.route_prefix').'/invoice-client/create?type=payment&id='.$entry->getKey()) }}" 
        class="btn btn-sm btn-success" 
        title="{{ trans('backpack::crud.payment.title') }}"
        style="color: white; font-weight: bold;">
        <i class="la la-money"></i> {{ trans('backpack::crud.payment.payment') }}
    </a>
@endif

@push('after_scripts') @if (request()->ajax()) @endpush @endif
<script>
    if (typeof paymentInvoice != 'function') {
        function paymentInvoice(button) {
            var btn = $(button);
            var route = btn.attr('data-route');
            var title = btn.attr('title');
            
            if (typeof OpenCreateFormModal === 'function') {
                OpenCreateFormModal({
                    route: route,
                    modal: {
                        id: '#modalCreate',
                        title: title,
                        // action: route // The action is already set in the controller via setRoute
                    }
                });
            } else {
                // Fallback to manual AJAX if OpenCreateFormModal is not available

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
                            swal("Error", "Could not load payment form", "error");
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

    // Success event listener is already handled by create.blade.php if we use #modalCreate
    // but we can add specific logic here if needed.
    if (typeof eventEmitter !== 'undefined') {
        eventEmitter.on('crudTable-invoice_payment_success', function() {
            // Additional logic after success if needed
            swal({
                    title: "Success",
                    text: "{!! trans('backpack::crud.invoice_client.field.invoice_payment_success') !!}",
                    icon: "success",
                    timer: 4000,
                    buttons: false,
                });
        });
    }
</script>
@if (!request()->ajax()) @endpush @endif
