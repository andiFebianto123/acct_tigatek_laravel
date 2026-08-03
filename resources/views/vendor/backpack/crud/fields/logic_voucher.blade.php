@php
	// if not otherwise specified, the hidden input should take up no space in the form
  $field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
  $field['wrapper']['class'] = $field['wrapper']['class'] ?? "hidden";
  $set_value = (isset($entry)) ? $entry?->reference : null;
@endphp

{{-- hidden input --}}
@include('crud::fields.inc.wrapper_start')
  <input type="hidden" name="no_type" />
  <input type="hidden" name="bussines_entity_name" />
  <input type="hidden" name="type" />
  <input
  	type="hidden"
    name="{{ $field['name'] }}"
    value="{{ old_empty_or_null($field['name'], '') ??  $field['value'] ?? $field['default'] ?? '' }}"
    @include('crud::fields.inc.attributes')
  	>
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
    <script>
        /**
         * Global Helper Utility untuk Format Nominal
         */
        if (typeof setInputNumberCurrency === "undefined") {
            function setInputNumberCurrency(selected, value, curr = 'IDR') {
                let cleanVal = (curr === 'IDR') ? Math.round(parseFloat(value) || 0) : value;
                let nominal = (typeof window.formatCurrency === 'function')
                    ? window.formatCurrency(cleanVal, curr)
                    : (curr === 'USD' ? Number(cleanVal).toFixed(2) : formatIdr(cleanVal));
                $(selected).val(nominal).trigger('input');
            }
        }

        if (typeof setInputNumber2 === "undefined") {
            function formatIdr(angka) {
                const formatter = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR'
                });
                return formatter.format(angka).replace('Rp', '').trim();
            }

            function setInputNumber2(selector, value) {
                let nominal = formatIdr(value);
                $(selector).val(nominal).trigger('input');
            }
        }

        /**
         * Voucher Logic Module (Modular & Clean Code Pattern)
         */
        SIAOPS.setAttribute('logic_asset', function() {
            return {
                formType: "{{ $crud->getActionMethod() }}",
                get formSelector() {
                    return this.formType === 'create' ? '#form-create' : '#form-edit';
                },
                getEl: function(name) {
                    return $(this.formSelector + ' ' + name);
                },

                /**
                 * Kalkulasi & Formula Voucher
                 */
                logicFormula: function() {
                    var form = this.formSelector;
                    var curr = this.getEl('select[name="currency_code"]').val() || 'IDR';
                    var symbol = (curr === 'USD' ? '$' : 'Rp');

                    // Update simbol input-group-text prefix untuk seluruh field nominal
                    ['total_price_ppn', 'total', 'discount_pph_23', 'discount_pph_4', 'discount_pph_21', 'payment_transfer'].forEach(function(fieldName) {
                        var $group = $(form + ' input[name="' + fieldName + '"]').closest('.input-group');
                        if ($group.length && $group.find('.input-group-text').length) {
                            $group.find('.input-group-text').first().text(symbol);
                        }
                    });

                    var billValue = getInputNumber(form + ' #bill_value');
                    var ppnPercent = getInputNumber(form + ' input[name="tax_ppn"]');

                    var nilaiPpn = (ppnPercent === 0) ? 0 : (billValue * (ppnPercent / 100));
                    if (curr === 'IDR') nilaiPpn = Math.round(nilaiPpn);
                    setInputNumberCurrency(form + ' input[name="total_price_ppn"]', nilaiPpn, curr);

                    var total = billValue + nilaiPpn;
                    if (curr === 'IDR') total = Math.round(total);
                    setInputNumberCurrency(form + ' input[name="total"]', total, curr);

                    var pph23Percent = getInputNumber(form + ' input[name="pph_23"]');
                    var diskonPph23 = (pph23Percent === 0) ? 0 : (billValue * (pph23Percent / 100));
                    if (curr === 'IDR') diskonPph23 = Math.round(diskonPph23);
                    setInputNumberCurrency(form + ' input[name="discount_pph_23"]', diskonPph23, curr);

                    var pph4Percent = getInputNumber(form + ' input[name="pph_4"]');
                    var diskonPph4 = (pph4Percent === 0) ? 0 : (billValue * (pph4Percent / 100));
                    if (curr === 'IDR') diskonPph4 = Math.round(diskonPph4);
                    setInputNumberCurrency(form + ' input[name="discount_pph_4"]', diskonPph4, curr);

                    var pph21Percent = getInputNumber(form + ' input[name="pph_21"]');
                    var diskonPph21 = (pph21Percent === 0) ? 0 : (billValue * (pph21Percent / 100));
                    if (curr === 'IDR') diskonPph21 = Math.round(diskonPph21);
                    setInputNumberCurrency(form + ' input[name="discount_pph_21"]', diskonPph21, curr);

                    var paymentTransfer = total - diskonPph23 - diskonPph4 - diskonPph21;
                    if (curr === 'IDR') paymentTransfer = Math.round(paymentTransfer);
                    setInputNumberCurrency(form + ' input[name="payment_transfer"]', paymentTransfer, curr);
                },

                /**
                 * Konversi Nominal Otomatis saat Pengguna Mengganti Currency (IDR <-> USD)
                 */
                convertVoucherTotals: function(previousCurrency, newCurrency, usdRate) {
                    var form = this.formSelector;
                    var rate = usdRate || window.usdRate || 16000;

                    var $hiddenBill = this.getEl('#bill_value');
                    var rawBill = parseFloat($hiddenBill.val() || 0);

                    if (rawBill > 0 && typeof window.convertCurrency === 'function') {
                        var convertedBill = window.convertCurrency(rawBill, previousCurrency, newCurrency, rate);
                        $hiddenBill.val(convertedBill);
                        var $maskedBill = this.getEl('#bill_value_masked');
                        if ($maskedBill.length) {
                            $maskedBill.val(window.formatCurrency(convertedBill, newCurrency));
                        }
                    }

                    this.logicFormula();
                },

                /**
                 * Dynamic Form Toggle berdasarkan Jenis Voucher (po_type)
                 */
                togglePoType: function(isInit = false) {
                    var poType = this.getEl('select[name="po_type"]').val();
                    var clientPoWrapper = this.getEl('select[name="client_po_id"]').closest('.form-group');
                    var invoiceClientWrapper = this.getEl('select[name="invoice_client_id"]').closest('.form-group');
                    var referenceWrapper = this.getEl('select[name="reference_id"]').closest('.form-group');
                    var jobNameWrapper = this.getEl('input[name="job_name_disabled"]').closest('.form-group');

                    referenceWrapper.show();
                    this.getEl('select[name="reference_id"]').removeAttr('disabled');

                    if (poType === 'supplier') {
                        clientPoWrapper.hide();
                        this.getEl('select[name="client_po_id"]').attr('disabled', true);
                        
                        invoiceClientWrapper.show();
                        this.getEl('select[name="invoice_client_id"]').removeAttr('disabled');
                        
                        jobNameWrapper.find('label').text("Deskripsi Pesanan");
                    } else {
                        // subkon
                        clientPoWrapper.hide();
                        this.getEl('select[name="client_po_id"]').attr('disabled', true);
                        
                        invoiceClientWrapper.show();
                        this.getEl('select[name="invoice_client_id"]').removeAttr('disabled');
                        
                        jobNameWrapper.find('label').text("Nama Pekerjaan");
                    }

                    if (!isInit) {
                        this.getEl('select[name="client_po_id"]').val(null).trigger('change');
                        this.getEl('select[name="invoice_client_id"]').val(null).trigger('change');
                        this.getEl('select[name="reference_id"]').val(null).trigger('change');
                        this.getEl('select[name="subkon_id"]').val(null).trigger('change');
                        this.getEl('input[name="job_name"]').val(null);
                        this.getEl('input[name="job_name_disabled"]').val(null);
                    }
                },

                /**
                 * Event Listeners Binding
                 */
                bindEvents: function() {
                    var self = this;
                    var form = this.formSelector;
                    var previousCurrency = this.getEl('select[name="currency_code"]').val() || 'IDR';

                    // Toggle PO Type
                    this.getEl('select[name="po_type"]').off('change select2:select').on('change select2:select', function() {
                        self.togglePoType(false);
                    });

                    // Main Currency Selection & Auto Conversion Listener
                    this.getEl('select[name="currency_code"]').off('change select2:select').on('change select2:select', function() {
                        var newCurrency = $(this).val() || 'IDR';
                        
                        // Sync dropdown terkunci pada mask_currency
                        self.getEl('select.currency-select-dropdown').val(newCurrency).trigger('change');

                        if (newCurrency !== previousCurrency) {
                            var usdRate = window.usdRate || 16000;
                            self.convertVoucherTotals(previousCurrency, newCurrency, usdRate);
                            previousCurrency = newCurrency;
                        }
                    });

                    // Reference ID (PO/SPK) Selection
                    this.getEl('select[name="reference_id"]').off('select2:select').on('select2:select', function(e) {
                        self.fetchReferenceDetail(e.params.data.id, e.params.data.type);
                    });

                    // Client PO Selection
                    this.getEl('select[name="client_po_id"]').off('select2:select').on('select2:select', function(e) {
                        self.fetchClientPoDetail(e.params.data.id, e.params.data.type);
                    });

                    // Invoice Client Selection
                    this.getEl('select[name="invoice_client_id"]').off('select2:select').on('select2:select', function(e) {
                        self.fetchInvoiceClientDetail(e.params.data.id);
                    });

                    // Faktur Status Toggle
                    this.getEl('select[name="factur_status"]').off('select2:select').on('select2:select', function(e) {
                        var status = e.params.data.id;
                        var isReadOnly = (status === 'TIDAK ADA' || status === 'AKAN ADA');
                        
                        self.getEl('input[name="no_factur"]').attr('readonly', isReadOnly);
                        self.getEl('#date_factur').attr('disabled', isReadOnly);
                    });

                    // Subkon Account Source Selection
                    this.getEl('select[name="subkon_id"]').off('select2:select').on('select2:select', function(e) {
                        self.fetchSubkonAccountDetail(e.params.data.id);
                    });

                    // Trigger Calculation on Keyup / Input Change
                    $(form + ' #bill_value_masked, ' +
                      form + ' input[name="bill_value"], ' +
                      form + ' input[name="tax_ppn"], ' +
                      form + ' input[name="pph_23"], ' +
                      form + ' input[name="pph_4"], ' +
                      form + ' input[name="pph_21"]'
                    ).off('keyup.logicVoucher input.logicVoucher').on('keyup.logicVoucher input.logicVoucher', function() {
                        self.logicFormula();
                    });
                },

                /**
                 * AJAX API Fetch Handlers
                 */
                fetchReferenceDetail: function(id, type) {
                    var self = this;
                    $.ajax({
                        url: "{{ url($crud->route) }}/get_client_selected_ajax?id=" + id + "&type=" + type,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            if (data.company) {
                                var subkonOption = new Option(data.company.name, data.company.id, true, true);
                                self.getEl('select[name="subkon_id"]').append(subkonOption).trigger('change');
                                self.getEl('input[name="bank_name"]').val(data.company.bank_name || null);
                                self.getEl('input[name="no_account"]').val(data.company.bank_account || null);
                                self.getEl('input[name="account_holder_name"]').val(data.company.account_holder_name || null);
                            } else {
                                self.getEl('select[name="subkon_id"]').val(null).trigger('change');
                                self.getEl('input[name="bank_name"]').val(null);
                                self.getEl('input[name="no_account"]').val(null);
                                self.getEl('input[name="account_holder_name"]').val(null);
                            }

                            self.getEl('input[name="date_po_spk"]').val(data.date_po || null);
                            if (data.po) {
                                self.getEl('input[name="type"]').val(data.po.type);
                            }
                        }
                    });
                },

                fetchClientPoDetail: function(id, type) {
                    var self = this;
                    $.ajax({
                        url: "{{ url($crud->route) }}/get_client_selected_ajax?id=" + id + "&type=" + type,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            if (data.company) {
                                var subkonOption = new Option(data.company.name, data.company.id, true, true);
                                self.getEl('select[name="subkon_id"]').append(subkonOption).trigger('change');
                                self.getEl('input[name="bank_name"]').val(data.company.bank_name || null);
                                self.getEl('input[name="no_account"]').val(data.company.bank_account || null);
                            }
                            if (data.po) {
                                self.getEl('input[name="job_name"]').val(data.po.job_name);
                                self.getEl('input[name="job_name_disabled"]').val(data.po.job_name);
                            }
                        }
                    });
                },

                fetchInvoiceClientDetail: function(id) {
                    var self = this;
                    $.ajax({
                        url: "{{ url($crud->route) }}/get_invoice_selected_ajax?id=" + id,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            if (data.company) {
                                var subkonOption = new Option(data.company.name, data.company.id, true, true);
                                self.getEl('select[name="subkon_id"]').append(subkonOption).trigger('change');
                                self.getEl('input[name="bank_name"]').val(data.company.bank_name || null);
                                self.getEl('input[name="no_account"]').val(data.company.bank_account || null);
                            }
                            if (data.po) {
                                self.getEl('input[name="job_name"]').val(data.po.job_name);
                                self.getEl('input[name="job_name_disabled"]').val(data.po.job_name);
                            }
                        }
                    });
                },

                fetchSubkonAccountDetail: function(id) {
                    var self = this;
                    $.ajax({
                        url: "{{ url($crud->route) }}/get_account_source_selected_ajax?id=" + id,
                        type: 'GET',
                        dataType: 'json',
                        success: function(data) {
                            self.getEl('input[name="bank_name"]').val(data.bank_name || null);
                            self.getEl('input[name="no_account"]').val(data.bank_account || null);
                        }
                    });
                },

                /**
                 * Pre-fill state pada Mode Edit Form
                 */
                initEditMode: function() {
                    @if (isset($entry))
                        var dataPoSpk = {!! json_encode($set_value) !!};
                        var dataEntry = {!! json_encode($entry) !!};

                        if (dataPoSpk != null) {
                            var noPoSpk = (dataPoSpk.type === 'spk') ? dataEntry.reference.no_spk : dataEntry.reference.po_number;
                            this.getEl('input[name="bussines_entity_name"]').val(dataPoSpk.name_company);
                            this.getEl('input[name="type"]').val(dataPoSpk.type);

                            var selectedOption = new Option(noPoSpk, dataEntry.reference.id, true, true);
                            this.getEl('select[name="reference_id"]').append(selectedOption).trigger('change');
                        }

                        if (dataEntry.invoice_client) {
                            var selectedOptionInvoice = new Option(dataEntry.invoice_client.invoice_number, dataEntry.invoice_client.id, true, true);
                            this.getEl('select[name="invoice_client_id"]').append(selectedOptionInvoice).trigger('change');
                        }

                        this.getEl('input[name="job_name_disabled"]').val(dataEntry.job_name);

                        var self = this;
                        setTimeout(function() {
                            self.logicFormula();
                        }, 200);
                    @endif
                },

                /**
                 * Module Boot Initialization
                 */
                load: function() {
                    this.togglePoType(true);
                    this.bindEvents();
                    this.initEditMode();
                }
            };
        });

        // Exec Module
        SIAOPS.getAttribute('logic_asset').load();
    </script>
@endpush
