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
        SIAOPS.setAttribute('logic_bast', function () {
            return {
                form_type: "{{ $crud->getActionMethod() }}",

                load: function () {
                    var instance = this;
                    var form = (this.form_type === 'create') ? '#form-create' : '#form-edit';

                    function toggleFields() {
                        let refType = $(form + ' select[name="reference_type"]').val();
                        let poWrapper = $(form + ' select[name="client_po_id"]').closest('.form-group');
                        let proformaWrapper = $(form + ' select[name="proforma_invoice_client_id"]').closest('.form-group');

                        if (refType === 'client_po') {
                            poWrapper.show();
                            proformaWrapper.hide();
                            $(form + ' select[name="proforma_invoice_client_id"]').val(null).trigger('change');
                        } else if (refType === 'proforma_invoice') {
                            proformaWrapper.show();
                            poWrapper.hide();
                            $(form + ' select[name="client_po_id"]').val(null).trigger('change');
                        } else {
                            poWrapper.hide();
                            proformaWrapper.hide();
                        }
                    }

                    // Trigger toggle on load
                    setTimeout(toggleFields, 100);

                    // Toggle on reference_type change
                    $(form + ' select[name="reference_type"]').on('change', function() {
                        toggleFields();
                    });

                    // Event listener for autofilling client address when client changes
                    $(form + ' select[name="client_id"]').on('change', function() {
                        let clientId = $(this).val();
                        if (clientId) {
                            $.ajax({
                                url: "{{ backpack_url('client/bast/client-address') }}",
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

                    // Event listener for autofilling description based on Client PO
                    $(form + ' select[name="client_po_id"]').on('change', function() {
                        let poId = $(this).val();
                        if (poId) {
                            $.ajax({
                                url: "{{ backpack_url('client/bast/get-po-details') }}",
                                type: "GET",
                                data: { po_id: poId },
                                success: function(response) {
                                    if (response) {
                                        if (response.client_id) {
                                            let clientSelect = $(form + ' select[name="client_id"]');
                                            if (clientSelect.find("option[value='" + response.client_id + "']").length) {
                                                clientSelect.val(response.client_id).trigger('change');
                                            } else {
                                                let newOption = new Option(response.client_name, response.client_id, true, true);
                                                clientSelect.append(newOption).trigger('change');
                                            }
                                        }
                                        if (response.address) {
                                            $(form + ' textarea[name="address"]').val(response.address);
                                        }
                                        let currentPoPic = $(form + ' input[name="pic"]').val();
                                        if ((!currentPoPic || currentPoPic.trim() === '') && response.pic) {
                                            $(form + ' input[name="pic"]').val(response.pic);
                                        }
                                        if (response.job_name) {
                                            $(form + ' input[name="description"]').val(response.job_name);
                                        }
                                    }
                                }
                            });
                        }
                    });

                    // Event listener for autofilling based on Proforma Invoice Client
                    $(form + ' select[name="proforma_invoice_client_id"]').on('change', function() {
                        let proformaId = $(this).val();
                        if (proformaId) {
                            $.ajax({
                                url: "{{ backpack_url('client/bast/get-proforma-details') }}",
                                type: "GET",
                                data: { proforma_id: proformaId },
                                success: function(response) {
                                    if (response) {
                                        if (response.client_id) {
                                            let clientSelect = $(form + ' select[name="client_id"]');
                                            if (clientSelect.find("option[value='" + response.client_id + "']").length) {
                                                clientSelect.val(response.client_id).trigger('change');
                                            } else {
                                                let newOption = new Option(response.client_name, response.client_id, true, true);
                                                clientSelect.append(newOption).trigger('change');
                                            }
                                        }
                                        if (response.address) {
                                            $(form + ' textarea[name="address"]').val(response.address);
                                        }
                                        let currentProformaPic = $(form + ' input[name="pic"]').val();
                                        if ((!currentProformaPic || currentProformaPic.trim() === '') && response.pic) {
                                            $(form + ' input[name="pic"]').val(response.pic);
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

        SIAOPS.getAttribute('logic_bast').load();
    });
</script>
@endpush
