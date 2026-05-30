@php
    $field['wrapper'] = $field['wrapper'] ?? [];
    $field['wrapper']['class'] = $field['wrapper']['class'] ?? 'hidden';
@endphp

{{-- This field holds no visible input, just triggers the script --}}
@include('crud::fields.inc.wrapper_start')
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
<script>
    $(function () {
        SIAOPS.setAttribute('logic_delivery_note', function () {
            return {
                form_type: "{{ $crud->getActionMethod() }}",

                load: function () {
                    var instance = this;
                    var form = (this.form_type === 'create') ? '#form-create' : '#form-edit';

                    // // Widen parent modal
                    // let modal = window.parent.$(".modal-dialog");
                    // if (modal.length) {
                    //     modal.addClass("modal-xl").css("max-width", "90%");
                    // }
                    // $(".modal-dialog", window.parent.document).addClass("modal-xl").css("max-width", "90%");

                    // Event listener for autofilling client address when client changes
                    $(form + ' select[name="client_id"]').on('change', function() {
                        let clientId = $(this).val();
                        if (clientId) {
                            $.ajax({
                                url: "{{ backpack_url('client/delivery-note/client-address') }}",
                                type: "GET",
                                data: { client_id: clientId },
                                success: function(response) {
                                    if (response && response.address) {
                                        $(form + ' textarea[name="address"]').val(response.address);
                                    }
                                }
                            });
                        }
                    });

                    // Event listener for autofilling client, address and description based on Client PO
                    $(form + ' select[name="client_po_id"]').on('change', function() {
                        let poId = $(this).val();
                        if (poId) {
                            $.ajax({
                                url: "{{ backpack_url('client/delivery-note/get-po-details') }}",
                                type: "GET",
                                data: { po_id: poId },
                                success: function(response) {
                                    if (response) {
                                        if (response.client_id) {
                                            let selectClient = $(form + ' select[name="client_id"]');
                                            if (selectClient.find("option[value='" + response.client_id + "']").length === 0) {
                                                let newOption = new Option(response.client_name, response.client_id, true, true);
                                                selectClient.append(newOption).trigger("change");
                                            } else {
                                                selectClient.val(response.client_id).trigger("change");
                                            }
                                        }
                                        if (response.address) {
                                            $(form + ' textarea[name="address"]').val(response.address);
                                        }
                                        if (response.job_name) {
                                            $(form + ' input[name="description"]').val(response.job_name);
                                        }
                                    }
                                }
                            });
                        }
                    });
                }
            };
        });

        SIAOPS.getAttribute('logic_delivery_note').load();
    });
</script>
@endpush
