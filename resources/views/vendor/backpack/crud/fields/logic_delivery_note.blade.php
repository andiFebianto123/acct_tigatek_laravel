@php
    $field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
    $field['wrapper']['class'] = $field['wrapper']['class'] ?? "hidden";
    $set_value = (isset($entry)) ? $entry : null;
@endphp

{{-- hidden input --}}
@include('crud::fields.inc.wrapper_start')
    <input
        type="hidden"
        name="{{ $field['name'] }}"
        value="{{ old_empty_or_null($field['name'], '') ?? $field['value'] ?? $field['default'] ?? '' }}"
        @include('crud::fields.inc.attributes')
    >
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
    <script>
        if (typeof window.DeliveryNoteLogicManager === 'undefined') {
            window.DeliveryNoteLogicManager = class DeliveryNoteLogicManager {
                constructor(formSelector) {
                    this.form = formSelector;
                    this.getInvoiceDetailsUrl = '{{ backpack_url("client/delivery-note/get-invoice-details") }}';
                    this.getClientAddressUrl = '{{ backpack_url("client/delivery-note/client-address") }}';
                }

                init() {
                    var self = this;
                    var form = this.form;

                    // Event saat No. Invoice dipilih via Select2
                    $(form + ' select[name="invoice_client_id"]').off('select2:select change.dn_logic').on('select2:select change.dn_logic', function(e) {
                        var invoiceId = $(this).val();
                        if (invoiceId) {
                            self.fetchInvoiceDetails(invoiceId);
                        } else {
                            self.resetInvoiceItemsTable();
                        }
                    });

                    // Event saat Client dipilih
                    $(form + ' select[name="client_id"]').off('select2:select change.dn_client').on('select2:select change.dn_client', function(e) {
                        var clientId = $(this).val();
                        if (clientId) {
                            $.ajax({
                                url: self.getClientAddressUrl,
                                method: 'GET',
                                data: { client_id: clientId },
                                success: function(res) {
                                    if (res && res.address) {
                                        $(form + ' textarea[name="address"]').val(res.address);
                                    }
                                }
                            });
                        }
                    });

                    // Prefill data saat halaman edit dibuka
                    var currentInvoiceId = $(form + ' select[name="invoice_client_id"]').val();
                    if (currentInvoiceId) {
                        setTimeout(function() {
                            self.fetchInvoiceDetails(currentInvoiceId);
                        }, 300);
                    }
                }

                fetchInvoiceDetails(invoiceId) {
                    var self = this;
                    var form = this.form;

                    $.ajax({
                        url: self.getInvoiceDetailsUrl,
                        method: 'GET',
                        data: { invoice_id: invoiceId },
                        success: function(res) {
                            if (res && res.success) {
                                // Set Client ID jika ada
                                if (res.client_id) {
                                    var $clientSelect = $(form + ' select[name="client_id"]');
                                    if ($clientSelect.length) {
                                        if (!$clientSelect.find("option[value='" + res.client_id + "']").length) {
                                            var newOption = new Option(res.client_name || res.client_id, res.client_id, true, true);
                                            $clientSelect.append(newOption);
                                        }
                                        $clientSelect.val(res.client_id).trigger('change.select2');
                                    }
                                }

                                // Set Alamat jika kosong / diisi dari invoice
                                if (res.address) {
                                    $(form + ' textarea[name="address"]').val(res.address);
                                }

                                // Set Deskripsi / Pekerjaan jika kosong
                                if (res.description && !$(form + ' input[name="description"]').val()) {
                                    $(form + ' input[name="description"]').val(res.description);
                                }

                                // Render tabel items
                                self.renderInvoiceItemsTable(res.items || []);
                            }
                        },
                        error: function() {
                            self.resetInvoiceItemsTable();
                        }
                    });
                }

                renderInvoiceItemsTable(items) {
                    var $tbody = $('#table-invoice-items tbody');
                    if (!$tbody.length) {
                        $tbody = $('#delivery_note_invoice_items_container table tbody');
                    }
                    if (!$tbody.length) return;

                    $tbody.empty();

                    if (!items || items.length === 0) {
                        $tbody.append('<tr><td colspan="3" class="text-center text-muted">Tidak ada detail barang pada Invoice ini</td></tr>');
                        return;
                    }

                    $.each(items, function(index, item) {
                        var rowHtml = '<tr>' +
                            '<td class="text-center" style="width: 50px;">' + (index + 1) + '</td>' +
                            '<td>' + (item.name || '-') + '</td>' +
                            '<td class="text-center" style="width: 100px;"><strong>' + (item.qty || 1) + '</strong></td>' +
                            '</tr>';
                        $tbody.append(rowHtml);
                    });
                }

                resetInvoiceItemsTable() {
                    var $tbody = $('#table-invoice-items tbody');
                    if ($tbody.length) {
                        $tbody.html('<tr><td colspan="3" class="text-center text-muted">Pilih No. Invoice terlebih dahulu</td></tr>');
                    }
                }
            };
        }

        (function() {
            var form_type = "{{ $crud->getActionMethod() }}";
            var form = (form_type == 'create') ? '#form-create' : '#form-edit';

            if (typeof window.DeliveryNoteLogicManager !== 'undefined') {
                var logicMgr = new window.DeliveryNoteLogicManager(form);
                logicMgr.init();
            }
        })();
    </script>
@endpush
