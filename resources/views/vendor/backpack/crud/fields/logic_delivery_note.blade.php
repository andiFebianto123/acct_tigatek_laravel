@php
    $field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
    $field['wrapper']['class'] = $field['wrapper']['class'] ?? "hidden";
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
                    this.select2StockUrl = '{{ backpack_url("client/delivery-note/select2-device-stock") }}';

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

                    this.itemRowIndex = 0;
                }

                init(initialData) {
                    initialData = initialData || {};
                    var self = this;
                    var form = this.form;

                    // Clear any existing table rows from previous modal opens
                    $('#table-invoice-items tbody').empty();
                    this.itemRowIndex = 0;

                    // --- Event: Tombol Tambah Baris ---
                    $(form).off('click.dn_add_item', '#btn-add-dn-item').on('click.dn_add_item', '#btn-add-dn-item', function (e) {
                        e.preventDefault();
                        self.addItemRow({});
                    });

                    // --- Event: Tombol Hapus Baris ---
                    $(form).off('click.dn_remove_item', '.btn-remove-dn-row').on('click.dn_remove_item', '.btn-remove-dn-row', function (e) {
                        e.preventDefault();
                        $(this).closest('tr').remove();
                        self.reindexRows();
                    });

                    // --- Event: Jenis Referensi berubah ---
                    $(form + ' select[name="reference_type"]').off('change.dn_reftype').on('change.dn_reftype', function () {
                        var type = $(this).val();

                        // Reset pilihan reference_id
                        var $refSelect = $(form + ' select[name="reference_id"]');
                        $refSelect.val(null).trigger('change');
                        if ($refSelect.hasClass('select2-hidden-accessible')) {
                            $refSelect.select2('destroy');
                        }

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

                    // --- Prefill saat Edit atau awal dibuka ---
                    var existing = initialData.existingDetails || [];
                    if (existing && existing.length > 0) {
                        self.renderInvoiceItemsTable(existing);
                    } else {
                        // Jika tidak ada item awal, tambahkan 1 baris kosong
                        if ($('#table-invoice-items tbody tr').length === 0) {
                            self.addItemRow({});
                        }
                    }

                    // Restore Select2 reference_id jika ada nilai awal
                    var currentType = $(form + ' select[name="reference_type"]').val() || initialData.refType;
                    var currentRefId = initialData.refId || '';
                    var currentRefNum = initialData.refNum || '';

                    if (currentType && self.referenceConfig[currentType]) {
                        var $refSelect = $(form + ' select[name="reference_id"]');
                        self._reinitSelect2ReferenceId($refSelect, self.referenceConfig[currentType].select2Url);

                        if (currentRefId) {
                            var textToShow = currentRefNum ? currentRefNum : ('ID: ' + currentRefId);
                            var newOption = new Option(textToShow, currentRefId, true, true);
                            $refSelect.append(newOption);
                        }
                    }
                }

                /**
                 * Tambah 1 baris item ke tabel.
                 */
                addItemRow(itemData) {
                    itemData = itemData || {};
                    var idx = this.itemRowIndex++;
                    var form = this.form;
                    var $tbody = $('#table-invoice-items tbody');
                    if (!$tbody.length) return;

                    var selectStockName = 'delivery_note_details[' + idx + '][device_stock_id]';
                    var descName        = 'delivery_note_details[' + idx + '][description]';
                    var qtyName         = 'delivery_note_details[' + idx + '][qty]';

                    var descVal = itemData.description || itemData.name || '';
                    var qtyVal  = itemData.qty || 1;

                    var rowHtml = '<tr data-row-index="' + idx + '">' +
                        '<td class="text-center row-number-cell" style="vertical-align:middle; width:40px;">' + ($tbody.find('tr').length + 1) + '</td>' +
                        '<td style="width:35%;">' +
                            '<select name="' + selectStockName + '" class="form-control select2-stock-item" style="width:100%;">' +
                                '<option></option>' +
                            '</select>' +
                        '</td>' +
                        '<td>' +
                            '<input type="text" name="' + descName + '" class="form-control form-control-sm" value="' + ($('<div>').text(descVal).html()) + '" placeholder="' + '{{ trans("backpack::crud.delivery_note.field.items.description_placeholder") }}' + '">' +
                        '</td>' +
                        '<td class="text-center" style="width:100px;">' +
                            '<input type="number" name="' + qtyName + '" class="form-control form-control-sm text-center" value="' + qtyVal + '" min="1">' +
                        '</td>' +
                        '<td class="text-center" style="vertical-align:middle; width:60px;">' +
                            '<button type="button" class="btn btn-sm btn-outline-danger btn-remove-dn-row" title="Hapus"><i class="la la-trash"></i></button>' +
                        '</td>' +
                    '</tr>';

                    var $row = $(rowHtml);
                    $tbody.append($row);

                    // Inisialisasi Select2 AJAX untuk barang persediaan pada baris ini
                    var $selectStock = $row.find('select.select2-stock-item');
                    this._initSelect2Stock($selectStock);

                    // Prefill pilihan barang persediaan jika ada
                    if (itemData.device_stock_id) {
                        var optionText = itemData.device_stock_text || itemData.name || ('ID: ' + itemData.device_stock_id);
                        var newOption = new Option(optionText, itemData.device_stock_id, true, true);
                        $selectStock.append(newOption).trigger('change.select2');
                    } else {
                        $selectStock.val(null).trigger('change.select2');
                    }

                    // Auto-fill deskripsi saat barang persediaan dipilih
                    $selectStock.on('select2:select', function (e) {
                        var selectedData = e.params.data;
                        var $descInput = $row.find('input[name="' + descName + '"]');
                        if (selectedData && selectedData.name && !$descInput.val()) {
                            $descInput.val(selectedData.name);
                        }
                    });

                    this.reindexRows();
                }

                _initSelect2Stock($select) {
                    var form = this.form;
                    var $modal = $select.closest('.modal');
                    var dropdownParent = $modal.find('.modal-body').length ? $modal.find('.modal-body') : ($modal.length ? $modal : $(document.body));

                    try {
                        if ($select.hasClass('select2-hidden-accessible')) {
                            $select.select2('destroy');
                        }

                        $select.select2({
                            ajax: {
                                url: this.select2StockUrl,
                                dataType: 'json',
                                delay: 250,
                                data: function (params) {
                                    return {
                                        q: params.term || '',
                                    };
                                },
                                processResults: function (data) {
                                    return { results: data.results || [] };
                                },
                                cache: true,
                            },
                            placeholder: '{{ trans("backpack::crud.delivery_note.field.items.select_stock_placeholder") }}',
                            allowClear: true,
                            width: '100%',
                            dropdownParent: dropdownParent,
                        });
                    } catch (e) {
                        console.warn('[DeliveryNote] stock select2 init error:', e);
                    }
                }

                reindexRows() {
                    $('#table-invoice-items tbody tr').each(function (i) {
                        $(this).find('.row-number-cell').text(i + 1);
                    });
                }

                /**
                 * Fetch detail dokumen referensi (client, address, description, items).
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

                                // Prefill Deskripsi Header (hanya jika kosong)
                                if (res.description && !$(form + ' input[name="description"]').val()) {
                                    $(form + ' input[name="description"]').val(res.description);
                                }

                                // Render tabel items (editable)
                                self.renderInvoiceItemsTable(res.items || []);
                            }
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
                    var self = this;
                    var $tbody = $('#table-invoice-items tbody');
                    if (!$tbody.length) return;

                    $tbody.empty();

                    if (!items || items.length === 0) {
                        self.addItemRow({});
                        return;
                    }

                    $.each(items, function (index, item) {
                        self.addItemRow(item);
                    });
                }
            };
        }

        (function () {
            var form_type = "{{ $crud->getActionMethod() }}";
            var form = (form_type == 'create') ? '#form-create' : '#form-edit';

            @php
                $entry = $entry ?? $crud?->entry;
                $existingDetails = [];
                if (isset($entry) && $entry->details && $entry->details->count() > 0) {
                    foreach ($entry->details as $d) {
                        $existingDetails[] = [
                            'device_stock_id'   => $d->device_stock_id,
                            'device_stock_text' => $d->device_stock ? ($d->device_stock->name . ' (' . $d->device_stock->code . ') (Stok: ' . $d->device_stock->qty . ')') : null,
                            'description'       => $d->description,
                            'qty'               => (float) $d->qty,
                        ];
                    }
                }
            @endphp

            var currentExistingDetails = {!! json_encode($existingDetails) !!};
            var currentEntryId = '{{ $entry?->id ?? "" }}';
            var currentRefId = '{{ $entry?->reference_id ?? "" }}';
            var currentRefNum = '{!! $entry?->reference_number ?? "" !!}';
            var currentRefType = '{{ $entry?->reference_type ?? "" }}';

            if (typeof window.DeliveryNoteLogicManager !== 'undefined') {
                var logicMgr = new window.DeliveryNoteLogicManager(form);
                logicMgr.init({
                    existingDetails: currentExistingDetails,
                    entryId: currentEntryId,
                    refId: currentRefId,
                    refNum: currentRefNum,
                    refType: currentRefType
                });
            }
        })();
    </script>
@endpush
