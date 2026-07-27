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
                    this.baseUrl = '{{ backpack_url("client/delivery-note") }}';
                    this.getClientAddressUrl = '{{ backpack_url("client/delivery-note/client-address") }}';

                    // Map tipe referensi ke endpoint AJAX select2 dan detail
                    this.referenceConfig = {
                        'quotation': {
                            select2Url:  this.baseUrl + '/select2-quotation',
                            detailsUrl:  this.baseUrl + '/get-quotation-details',
                        },
                        'proforma_invoice': {
                            select2Url:  this.baseUrl + '/select2-proforma-invoice',
                            detailsUrl:  this.baseUrl + '/get-proforma-invoice-details',
                        },
                        'client_po': {
                            select2Url:  this.baseUrl + '/select2-po',
                            detailsUrl:  this.baseUrl + '/get-client-po-details',
                        },
                        'invoice_client': {
                            select2Url:  this.baseUrl + '/select2-invoice',
                            detailsUrl:  this.baseUrl + '/get-invoice-details',
                        },
                    };
                }

                init() {
                    var self = this;
                    var form = this.form;

                    // --- Event: Jenis Referensi berubah ---
                    $(form + ' select[name="reference_type"]').off('change.dn_reftype').on('change.dn_reftype', function () {
                        var type = $(this).val();

                        // Reset pilihan reference_id
                        var $refSelect = $(form + ' select[name="reference_id"]');
                        $refSelect.val(null).trigger('change');
                        if ($refSelect.hasClass('select2-hidden-accessible')) {
                            $refSelect.select2('destroy');
                        }

                        self.resetInvoiceItemsTable();

                        if (!type || !self.referenceConfig[type]) return;

                        // Update data_source Select2 reference_id sesuai reference_type
                        var newUrl = self.referenceConfig[type].select2Url;
                        self._reinitSelect2ReferenceId($refSelect, newUrl);
                    });

                    // --- Event: No. Dokumen Referensi dipilih ---
                    $(form).off('select2:select.dn_refid', 'select[name="reference_id"]')
                        .on('select2:select.dn_refid', 'select[name="reference_id"]', function (e) {
                            var refId   = e.params.data.id;
                            var refType = $(form + ' select[name="reference_type"]').val();
                            if (refId && refType && self.referenceConfig[refType]) {
                                self.fetchReferenceDetails(refType, refId);
                            }
                        });

                    // --- Event: Client berubah (prefill alamat) ---
                    $(form + ' select[name="client_id"]').off('select2:select change.dn_client').on('select2:select change.dn_client', function (e) {
                        var clientId = $(this).val();
                        if (clientId) {
                            $.ajax({
                                url: self.getClientAddressUrl,
                                method: 'GET',
                                data: { client_id: clientId },
                                success: function (res) {
                                    if (res && res.address) {
                                        $(form + ' textarea[name="address"]').val(res.address);
                                    }
                                }
                            });
                        }
                    });

                    // --- Prefill saat Edit dibuka: restore reference_id select2 & tabel items ---
                    var currentType = $(form + ' select[name="reference_type"]').val();
                    var currentRefId = '{{ $entry?->reference_id ?? "" }}';
                    var currentRefNum = '{!! $entry?->reference_number ?? "" !!}';

                    if (currentType && self.referenceConfig[currentType]) {
                        var $refSelect = $(form + ' select[name="reference_id"]');
                        self._reinitSelect2ReferenceId($refSelect, self.referenceConfig[currentType].select2Url);

                        if (currentRefId) {
                            // Prefill opsi teks awal untuk Select2 agar tidak kosong saat baru dibuka
                            var textToShow = currentRefNum ? currentRefNum : ('ID: ' + currentRefId);
                            var newOption = new Option(textToShow, currentRefId, true, true);
                            $refSelect.append(newOption).trigger('change.select2');

                            setTimeout(function () {
                                self.fetchReferenceDetails(currentType, currentRefId);
                            }, 400);
                        }
                    }
                }

                /**
                 * Fetch detail dokumen referensi (client, address, description, items).
                 * Endpoint menerima parameter reference_id.
                 */
                fetchReferenceDetails(refType, refId) {
                    var self = this;
                    var form = this.form;
                    var config = this.referenceConfig[refType];
                    if (!config) return;

                    $.ajax({
                        url: config.detailsUrl,
                        method: 'GET',
                        data: { reference_id: refId },
                        success: function (res) {
                            if (res && res.success) {
                                // Prefill Client
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

                                // Prefill Alamat
                                if (res.address) {
                                    $(form + ' textarea[name="address"]').val(res.address);
                                }

                                // Prefill Deskripsi (hanya jika kosong)
                                if (res.description && !$(form + ' input[name="description"]').val()) {
                                    $(form + ' input[name="description"]').val(res.description);
                                }

                                // Render tabel items
                                self.renderInvoiceItemsTable(res.items || []);
                            } else {
                                self.resetInvoiceItemsTable();
                            }
                        },
                        error: function () {
                            self.resetInvoiceItemsTable();
                        }
                    });
                }

                /**
                 * Reinitialize Select2 reference_id dengan data_source baru.
                 */
                _reinitSelect2ReferenceId($select, newUrl) {
                    if (!$select.length) return;

                    var form = this.form;
                    var companyId     = $(form + ' select[name="company_id"]').val() || '';
                    var referenceType = $(form + ' select[name="reference_type"]').val() || '';

                    // Tentukan dropdownParent: cari modal terdekat agar dropdown tidak keluar modal
                    var $modal = $select.closest('.modal');
                    var dropdownParent = $modal.length ? $modal : $(document.body);

                    try {
                        if ($select.hasClass('select2-hidden-accessible')) {
                            $select.select2('destroy');
                        }

                        $select.select2({
                            ajax: {
                                url: newUrl,
                                dataType: 'json',
                                delay: 250,
                                data: function (params) {
                                    return {
                                        q: params.term || '',
                                        company_id: $(form + ' select[name="company_id"]').val() || '',
                                        reference_type: $(form + ' select[name="reference_type"]').val() || '',
                                    };
                                },
                                processResults: function (data) {
                                    return { results: data.results || [] };
                                },
                                cache: true,
                            },
                            placeholder: '{{ trans("backpack::crud.delivery_note.field.reference_id.placeholder") }}',
                            allowClear: true,
                            width: '100%',
                            dropdownParent: dropdownParent,
                        });
                    } catch (e) {
                        console.warn('[DeliveryNote] select2 reinit error:', e);
                    }
                }

                renderInvoiceItemsTable(items) {
                    var $tbody = $('#table-invoice-items tbody');
                    if (!$tbody.length) {
                        $tbody = $('#delivery_note_invoice_items_container table tbody');
                    }
                    if (!$tbody.length) return;

                    $tbody.empty();

                    if (!items || items.length === 0) {
                        $tbody.append('<tr><td colspan="3" class="text-center text-muted py-3">Tidak ada detail barang pada dokumen ini</td></tr>');
                        return;
                    }

                    $.each(items, function (index, item) {
                        var rowHtml = '<tr>' +
                            '<td class="text-center" style="width:40px;">' + (index + 1) + '</td>' +
                            '<td>' + ($('<div>').text(item.name || '-').html()) + '</td>' +
                            '<td class="text-center" style="width:80px;"><strong>' + (item.qty || 1) + '</strong></td>' +
                            '</tr>';
                        $tbody.append(rowHtml);
                    });
                }

                resetInvoiceItemsTable() {
                    var $tbody = $('#table-invoice-items tbody');
                    if ($tbody.length) {
                        $tbody.html('<tr><td colspan="3" class="text-center text-muted py-3"><i class="la la-info-circle"></i> Pilih Jenis Referensi dan No. Dokumen terlebih dahulu</td></tr>');
                    }
                }
            };
        }

        (function () {
            var form_type = "{{ $crud->getActionMethod() }}";
            var form = (form_type == 'create') ? '#form-create' : '#form-edit';

            if (typeof window.DeliveryNoteLogicManager !== 'undefined') {
                var logicMgr = new window.DeliveryNoteLogicManager(form);
                logicMgr.init();
            }
        })();
    </script>
@endpush
