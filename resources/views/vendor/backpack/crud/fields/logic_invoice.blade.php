@php
	// if not otherwise specified, the hidden input should take up no space in the form
    $field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
    $field['wrapper']['class'] = $field['wrapper']['class'] ?? "hidden";
    $set_value = (isset($entry)) ? $entry : null;
@endphp

{{-- hidden input --}}
@include('crud::fields.inc.wrapper_start')
    <input
        type="hidden"
        name="{{ $field['name'] }}"
        value="{{ old_empty_or_null($field['name'], '') ??  $field['value'] ?? $field['default'] ?? '' }}"
        @include('crud::fields.inc.attributes')
    >
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
    <script>
        if (typeof setInputNumberCurrency === "undefined") {
            function setInputNumberCurrency(selected, value, curr = 'IDR') {
                let num = parseFloat(value) || 0;
                let cleanVal = Number(num.toFixed(2));
                let nominal = (typeof window.formatCurrency === 'function')
                    ? window.formatCurrency(cleanVal, curr)
                    : (curr === 'USD' ? Number(cleanVal).toFixed(2) : formatIdr(cleanVal));
                $(selected).val(nominal).trigger('input');
            }
        }

        /* =========================================================================
         * MODUL 1: GLOBAL UTILITY HELPERS (Format Currency)
         * ========================================================================= */
        if (typeof setInputNumber2 == "undefined") {
            function setInputNumber2(selected, value, currency = 'IDR') {
                let num = parseFloat(value) || 0;
                let formattedNum = Number(num.toFixed(2));
                let nominal = (typeof window.formatCurrency === 'function') 
                    ? window.formatCurrency(formattedNum, currency)
                    : formattedNum;
                $(selected).val(nominal).trigger('input');
            }
        }

        /* =========================================================================
         * MODUL 2: CLASS KHUSUS PENGELOLA REPEATABLE ITEM (ProformaRepeatableManager)
         * ========================================================================= */
        if (typeof window.ProformaRepeatableManager === 'undefined') {
            window.ProformaRepeatableManager = class ProformaRepeatableManager {
                constructor(formSelector, onCalculateCallback) {
                    this.form = formSelector;
                    this.onCalculate = onCalculateCallback;
                }

                getCleanIdrValue(val, isInitial = false) {
                    if (!val && val !== 0) return '';
                    let str = val.toString().trim();
                    let isNegative = str.startsWith('-');
                    str = str.replace(/^-/, '');

                    let integerPart = '';
                    let decimalPart = null;

                    if (str.includes(',')) {
                        let clean = str.replace(/\./g, '');
                        let parts = clean.split(',');
                        integerPart = parts[0].replace(/[^\d]/g, '');
                        decimalPart = parts.length > 1 ? parts[1].replace(/[^\d]/g, '').substring(0, 2) : null;
                    } else if (/^\d+\.\d+$/.test(str) && !/^\d{1,3}(\.\d{3})+$/.test(str)) {
                        let parts = str.split('.');
                        integerPart = parts[0].replace(/[^\d]/g, '');
                        decimalPart = parts.length > 1 ? parts[1].replace(/[^\d]/g, '').substring(0, 2) : null;
                    } else {
                        integerPart = str.replace(/[^\d]/g, '');
                    }

                    if (!integerPart && (decimalPart === null || decimalPart === '')) {
                        return '';
                    }

                    let raw = (integerPart || '0');
                    if (decimalPart !== null && decimalPart !== '') {
                        raw += '.' + decimalPart;
                    }
                    return (isNegative ? '-' : '') + raw;
                }

                cleanValue(val, currency, isInitial = false) {
                    if (!val && val !== 0) return '';
                    if (currency === 'USD') {
                        var parts = val.toString().replace(/,/g, '').replace(/[^\d.-]/g, '').split('.');
                        return parts[0] ? parts[0] + (parts.length > 1 ? '.' + parts[1].substring(0, 2) : '') : '';
                    }
                    return this.getCleanIdrValue(val, isInitial);
                }

                syncRowCurrency($row, currency, symbol) {
                    var $dropdown = $row.find('select.currency-select-dropdown, select[name*="price_currency"]');
                    if ($dropdown.length && $dropdown.val() !== currency) {
                        $dropdown.val(currency);
                    }

                    var $maskedInput = $row.find('input[data-alt="price_masked"]');
                    var $group = $maskedInput.closest('.input-group');
                    if ($group.length) {
                        $group.find('.input-group-text').first().text(symbol);
                    }
                }

                syncAllPrefixes(currency, symbol) {
                    var self = this;
                    $(this.form + ' input[data-alt="price_masked"]').each(function() {
                        var $row = $(this).closest('.repeatable-element, .repeatable-group, [data-repeatable-holder], div.row');
                        self.syncRowCurrency($row, currency, symbol);
                    });
                }

                initHandlers() {
                    var self = this;
                    var curr = $(this.form + ' select[name="currency_code"]').val() || 'IDR';
                    var symbol = (curr === 'USD' ? '$' : 'Rp');

                    // Delegated listener untuk seluruh input QTY dan Price
                    $(this.form).off('input.calc change.calc keyup.calc', 'input[data-repeatable-input-name="qty"], input[name*="[qty]"], input[name*="qty"], input[data-alt="price_masked"], input[name*="[price]"]')
                               .on('input.calc change.calc keyup.calc', 'input[data-repeatable-input-name="qty"], input[name*="[qty]"], input[name*="qty"], input[data-alt="price_masked"], input[name*="[price]"]', function() {
                        if (typeof self.onCalculate === 'function') self.onCalculate();
                    });

                    $(this.form + ' input[data-alt="price_masked"]').each(function() {
                        var $maskedInput = $(this);
                        var $row = $maskedInput.closest('.repeatable-element, .repeatable-group, [data-repeatable-holder], div.row');
                        var $hiddenInput = $row.find('input[type="hidden"][name*="[price]"], input[type="hidden"][name="price"]').last();
                        if (!$hiddenInput.length) {
                            $hiddenInput = $maskedInput.parent().next('input[type="hidden"]');
                        }

                        self.syncRowCurrency($row, curr, symbol);

                        var initialVal = $hiddenInput.val() || $maskedInput.val() || '';
                        if (initialVal && !$maskedInput.val()) {
                            var cleanInitial = self.cleanValue(initialVal, curr, true);
                            $hiddenInput.val(cleanInitial);
                            if (typeof window.formatCurrency === 'function') {
                                $maskedInput.val(window.formatCurrency(cleanInitial, curr));
                            }
                        }
                    });
                }

                calculateTotalItems() {
                    var self = this;
                    var total_price = 0;
                    var curr = $(this.form + ' select[name="currency_code"]').val() || 'IDR';

                    $(this.form + ' [data-repeatable-holder]').children().each(function() {
                        var $row = $(this);
                        var $masked = $row.find('input[data-alt="price_masked"]');
                        var $hidden = $row.find('input[type="hidden"][name*="[price]"], input[type="hidden"][name="price"]').last();
                        if (!$hidden.length) {
                            $hidden = $masked.parent().next('input[type="hidden"]');
                        }

                        var priceVal = 0;
                        var rawValue = ($hidden.length && $hidden.val() !== '') ? $hidden.val() : ($masked.length ? $masked.val() : '0');
                        priceVal = parseFloat(self.cleanValue(rawValue, curr, false)) || 0;

                        var qtyVal = parseFloat($row.find('input[data-repeatable-input-name="qty"], input[name*="[qty]"], input[name*="qty"]').val() || 1) || 1;

                        total_price += (priceVal * qtyVal);
                    });
                    return Number(total_price.toFixed(2));
                }

                convertAllItems(previousCurrency, newCurrency, usdRate) {
                    $(this.form + ' input[data-alt="price_masked"]').each(function() {
                        var $masked = $(this);
                        var $row = $masked.closest('.repeatable-element, .repeatable-group, [data-repeatable-holder], div.row');
                        var $hidden = $row.find('input[type="hidden"][name*="[price]"], input[type="hidden"][name="price"]').last();
                        if (!$hidden.length) {
                            $hidden = $masked.parent().next('input[type="hidden"]');
                        }

                        var rawPrice = parseFloat($hidden.val() || 0);

                        var $dropdown = $row.find('select.currency-select-dropdown, select[name*="price_currency"]');
                        if ($dropdown.length) {
                            $dropdown.val(newCurrency);
                        }

                        if (rawPrice > 0 && typeof window.convertCurrency === 'function') {
                            var convertedPrice = window.convertCurrency(rawPrice, previousCurrency, newCurrency, usdRate);
                            $hidden.val(convertedPrice);
                            $masked.val(window.formatCurrency(convertedPrice, newCurrency));
                        } else {
                            $masked.val(window.formatCurrency($hidden.val() || '', newCurrency));
                        }
                    });
                }
            };
        }

        /* =========================================================================
         * MODUL 3: CLASS KHUSUS PENGELOLA FORM & PAJAK (ProformaFormManager)
         * ========================================================================= */
        if (typeof window.ProformaFormManager === 'undefined') {
            window.ProformaFormManager = class ProformaFormManager {
                constructor(formSelector) {
                    this.form = formSelector;
                }

                // Populate data awal ke input form secara efisien
                populateFormData(entry) {
                    if (!entry) return;
                    var form = this.form;
                    var curr = entry.currency_code || $(form + ' select[name="currency_code"]').val() || 'IDR';

                    if (entry.currency_code) {
                        $(form + ' select[name="currency_code"]').val(entry.currency_code);
                    }

                    var rawExc = entry.nominal_exclude_ppn || 0;
                    var $excHidden = $(form + ' #nominal_exclude_ppn');
                    var $excMasked = $(form + ' #nominal_exclude_ppn_masked');
                    if ($excHidden.length) $excHidden.val(rawExc);
                    if ($excMasked.length) {
                        // console.log('lokasi nominal_exclude_ppn_masked edit');
                        // $excMasked.val(typeof window.formatCurrency === 'function' ? window.formatCurrency(rawExc, curr) : rawExc);
                        // $excMasked.val(rawExc);
                    }

                    var rawDpp = entry.price_dpp || entry.dpp_other || 0;
                    var $dppHidden = $(form + ' #dpp_other');
                    var $dppMasked = $(form + ' #dpp_other_masked');
                    if ($dppHidden.length) $dppHidden.val(rawDpp);
                    if ($dppMasked.length) {
                        $dppMasked.val(typeof window.formatCurrency === 'function' ? window.formatCurrency(rawDpp, curr) : rawDpp);
                    }

                    $(form + ' input[name="tax_ppn"]').val(entry.tax_ppn || 0);
                    $(form + ' input[name="pph"]').val(entry.pph || 0);

                    if (entry.company_id) $(form + ' select[name="company_id"]').val(entry.company_id).trigger('change');
                    if (entry.client_po_id) $(form + ' input[name="client_po_id"]').val(entry.client_po_id);
                    if (entry.address_po) $(form + ' input[name="address_po"]').val(entry.address_po);
                    if (entry.description) $(form + ' textarea[name="description"], ' + form + ' input[name="description"]').val(entry.description);
                    if (entry.withholding_agent) $(form + ' select[name="withholding_agent"]').val(entry.withholding_agent).trigger('change');
                    if (entry.account_source_id) $(form + ' select[name="account_source_id"]').val(entry.account_source_id).trigger('change');
                    if (entry.type_device) $(form + ' select[name="type_device"]').val(entry.type_device).trigger('change');
                    if (entry.kdp) $(form + ' input[name="kdp"]').val(entry.kdp);

                    // Populate & select Subkon (Select2 AJAX)
                    if (entry.subkon) {
                        var $subkonSelect = $(form + ' select[name="subkon_id"]');
                        if ($subkonSelect.length) {
                            var subkonName = (entry.subkon && entry.subkon.name) ? entry.subkon.name : (entry.subkon_name || entry.subkon_id);
                            var newOption = new Option(subkonName, entry.subkon_id, true, true);
                            $subkonSelect.append(newOption).trigger('change');
                        }
                    }

                    // Populate & select Client (Select2 AJAX)
                    if (entry.client_id || entry.client) {
                        var $cSelect = $(form + ' select[name="client_id"]');
                        if ($cSelect.length) {
                            var cId = entry.client_id || entry.client.id;
                            var cText = entry.client ? entry.client.name : cId;
                            if ($cSelect.find("option[value='" + cId + "']").length === 0) {
                                var cOpt = new Option(cText, cId, true, true);
                                $cSelect.append(cOpt).trigger('change');
                            } else {
                                $cSelect.val(cId).trigger('change');
                            }
                        }
                    }

                    // Populate & select Client Quotation (Select2 AJAX)
                    if (entry.client_quotation_id || entry.client_quotation) {
                        var $quotationSelect = $(form + ' select[name="client_quotation_id"], ' + form + ' select#client_quotation_id_select');
                        if ($quotationSelect.length) {
                            var qId = entry.client_quotation_id || entry.client_quotation.id;
                            var qText = entry.client_quotation ? (entry.client_quotation.po_number + ' - ' + entry.client_quotation.job_name) : qId;
                            if ($quotationSelect.find("option[value='" + qId + "']").length === 0) {
                                var qOption = new Option(qText, qId, true, true);
                                $quotationSelect.append(qOption).trigger('change');
                            } else {
                                $quotationSelect.val(qId).trigger('change');
                            }
                        }
                    }
                }

                // Memperbarui UI status nominal_information (Hijau / Netral / Merah)
                updateNominalInformationUI(priceBetween, curr) {
                    var formattedBetween = (typeof window.formatCurrency === 'function')
                        ? window.formatCurrency(priceBetween, curr)
                        : (curr === 'USD' ? Number(priceBetween).toFixed(2) : priceBetween.toLocaleString('id-ID'));

                    var $infoInput = $(this.form + ' input[name="nominal_information"]');
                    $infoInput.val(formattedBetween);

                    if (Math.abs(priceBetween) < 0.01) {
                        $infoInput.addClass('is-valid').removeClass('is-invalid');
                    } else if (priceBetween > 0) {
                        $infoInput.removeClass('is-invalid').removeClass('is-valid');
                    } else if (priceBetween < 0) {
                        $infoInput.removeClass('is-valid').addClass('is-invalid');
                    }
                }
            };
        }

        /* =========================================================================
         * MODUL 4: DEKLARASI MODUL LOGIKA FORM UTAMA (SIAOPS)
         * ========================================================================= */
        SIAOPS.setAttribute('logic_invoice', function() {
            return {
                form_type: "{{ $crud->getActionMethod() }}",
                total_price: 0,

                logicFormulaNoPO: function() {
                    var instance = this;
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    var curr = $(form + ' select[name="currency_code"]').val() || 'IDR';
                    var symbol = (curr === 'USD' ? '$' : 'Rp');

                    // Sync currency dropdown & prefix Include PPN
                    var $excCurrencySelect = $(form + ' select[name="nominal_exclude_ppn_currency"]');
                    if ($excCurrencySelect.length && $excCurrencySelect.val() !== curr) {
                        $excCurrencySelect.val(curr).trigger('change');
                    }

                    var $incGroup = $(form + ' input[name="nominal_include_ppn"]').closest('.input-group');
                    if ($incGroup.length && $incGroup.find('.input-group-text').length) {
                        $incGroup.find('.input-group-text').first().text(symbol);
                    }

                    var $pphGroup = $(form + ' input[name="discount_pph"]').closest('.input-group');
                    if ($pphGroup.length && $pphGroup.find('.input-group-text').length) {
                        $pphGroup.find('.input-group-text').first().text(symbol);
                    }

                    var $infoGroup = $(form + ' input[name="nominal_information"]').closest('.input-group');
                    if ($infoGroup.length && $infoGroup.find('.input-group-text').length) {
                        $infoGroup.find('.input-group-text').first().text(symbol);
                    }

                    // Delegasikan sync prefix ke RepeatableManager
                    if (this.repeatableManager) {
                        this.repeatableManager.syncAllPrefixes(curr, symbol);
                    }

                    // Kalkulasi PPN & Diskon PPh
                    var $excHidden = $(form + ' input[type="hidden"]#nominal_exclude_ppn, ' + form + ' input[type="hidden"][name="nominal_exclude_ppn"]').last();
                    var nominal_exclude_ppn = 0;

                    if ($excHidden.length && $excHidden.val() !== '') {
                        nominal_exclude_ppn = parseFloat($excHidden.val()) || 0;
                    } else {
                        var maskedVal = $(form + ' #nominal_exclude_ppn_masked').val() || '';
                        if (curr === 'USD') {
                            nominal_exclude_ppn = parseFloat(maskedVal.replace(/,/g, '')) || 0;
                        } else {
                            nominal_exclude_ppn = parseFloat(maskedVal.replace(/\./g, '').replace(/,/g, '.')) || 0;
                        }
                    }

                    var tax_ppn = parseFloat($(form + ' input[name="tax_ppn"]').val() || 0);
                    var nilai_ppn = (tax_ppn == 0) ? 0 : (nominal_exclude_ppn * (tax_ppn / 100));
                    var total = Number((nominal_exclude_ppn + nilai_ppn).toFixed(2));

                    setInputNumber2(form + ' input[name="nominal_include_ppn"]', total, curr);
                    instance.total_price = nominal_exclude_ppn;

                    var pph = parseFloat($(form + ' input[name="pph"]').val() || 0);
                    var diskon_pph = (pph == 0) ? 0 : Number((nominal_exclude_ppn * (pph / 100)).toFixed(2));

                    setInputNumber2(form + ' input[name="discount_pph"]', diskon_pph, curr);
                },

                convertInvoiceTotals: function(previousCurrency, newCurrency, usdRate) {
                    var instance = this;
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    var rate = usdRate || window.usdRate || 16000;

                    // 1. Ambil nilai murni nominal_exclude_ppn
                    var $hiddenExc = $(form + ' input[type="hidden"][name="nominal_exclude_ppn"], ' + form + ' #nominal_exclude_ppn');
                    var rawExc = parseFloat($hiddenExc.val() || 0);

                    // 2. Konversi nominal_exclude_ppn jika ada
                    if (rawExc > 0 && typeof window.convertCurrency === 'function') {
                        var convertedExc = window.convertCurrency(rawExc, previousCurrency, newCurrency, rate);
                        $hiddenExc.val(convertedExc);
                        var $maskedExc = $(form + ' #nominal_exclude_ppn_masked, ' + form + ' input[data-alt="nominal_exclude_ppn_masked"]');
                        if ($maskedExc.length) {
                            $maskedExc.val(window.formatCurrency(convertedExc, newCurrency));
                        }
                    }

                    // 3. Konversi seluruh item repeatable
                    if (this.repeatableManager) {
                        this.repeatableManager.convertAllItems(previousCurrency, newCurrency, rate);
                    }

                    // 4. Hitung ulang & update Nominal Include PPn serta Nominal PPh secara otomatis
                    this.logicFormulaNoPO();
                },

                loadNotificationPrefill: function(entry, form) {
                    var instance = this;
                    if (entry != null) {
                        setTimeout(() => {
                            instance.total_price = entry.nominal_exclude_ppn;
                            if (instance.formManager) instance.formManager.populateFormData(entry);

                            if (entry.invoice_client_details) {
                                var curr = entry.currency_code || 'IDR';
                                $(form + ' input[data-alt="price_masked"]').each(function(index) {
                                    if (entry.invoice_client_details[index]) {
                                        var rawPrice = entry.invoice_client_details[index].price;
                                        var hiddenInput = $(this).parent().next('input[type="hidden"]');
                                        if (!hiddenInput.length) {
                                            hiddenInput = $(this).closest('.repeatable-element, .repeatable-group, [data-repeatable-holder], div.row')
                                                                   .find('input[type="hidden"][name*="[price]"], input[type="hidden"][name="price"]').last();
                                        }
                                        hiddenInput.val(rawPrice);
                                        var formattedPrice = (typeof window.formatCurrency === 'function')
                                            ? window.formatCurrency(rawPrice, curr)
                                            : rawPrice;
                                        $(this).val(formattedPrice);
                                    }
                                });
                            }
                            instance.logicFormulaNoPO();
                        }, 300);
                    }
                },

                load: function() {
                    var instance = this;
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';

                    // Inisialisasi Manager
                    this.formManager = new ProformaFormManager(form);
                    this.repeatableManager = new ProformaRepeatableManager(form, function() {
                        countTotalPrice();
                    });

                    var entry = {!! json_encode($set_value) !!};
                    var hasNotificationId = {!! request()->has('notification_id') ? 'true' : 'false' !!};

                    if (hasNotificationId && entry != null) {
                        this.loadNotificationPrefill(entry, form);
                    } else if (entry != null) {
                        setTimeout(() => {
                            instance.total_price = entry.nominal_exclude_ppn;
                            instance.formManager.populateFormData(entry);
                            instance.logicFormulaNoPO();
                        }, 300);
                    }

                    // AJAX Listener Client Quotation
                    $(form + ' select[name="client_quotation_id"], ' + form + ' select#client_quotation_id_select').off('select2:select change').on('select2:select change', function(e) {
                        var quotationId = $(this).val();
                        if (!quotationId && e && e.params && e.params.data) {
                            quotationId = e.params.data.id;
                        }
                        if (!quotationId) return;

                        $.ajax({
                            url: '{!! backpack_url("client/proforma-invoice/get-client-quotation-details") !!}',
                            method: 'GET',
                            data: { id: quotationId },
                            success: function(res) {
                                if (!res || !res.status) return;

                                var curr = res.currency_code || 'IDR';
                                $(form + ' select[name="currency_code"]').val(curr).trigger('change');
                                previousCurrency = curr;

                                var rawExc = res.nominal_exclude_ppn || 0;
                                var $excHidden = $(form + ' input[type="hidden"][name="nominal_exclude_ppn"], ' + form + ' #nominal_exclude_ppn');
                                var $excMasked = $(form + ' #nominal_exclude_ppn_masked');
                                if ($excHidden.length) $excHidden.val(rawExc);
                                if ($excMasked.length && typeof window.formatCurrency === 'function') {
                                    $excMasked.val(window.formatCurrency(rawExc, curr));
                                }

                                if (res.tax_ppn !== undefined) {
                                    $(form + ' input[name="tax_ppn"]').val(res.tax_ppn);
                                }
                                if (res.address) {
                                    $(form + ' input[name="address_po"]').val(res.address);
                                }
                                if (res.description) {
                                    $(form + ' textarea[name="description"], ' + form + ' input[name="description"]').val(res.description);
                                }
                                if (res.pic) {
                                    $(form + ' input[name="pic"]').val(res.pic);
                                }
                                if (res.client_id) {
                                    var $clientSelect = $(form + ' select[name="client_id"]');
                                    if ($clientSelect.length) {
                                        var clientName = res.client_name || res.client_id;
                                        if ($clientSelect.find("option[value='" + res.client_id + "']").length === 0) {
                                            var newClientOpt = new Option(clientName, res.client_id, true, true);
                                            $clientSelect.append(newClientOpt).trigger('change');
                                        } else {
                                            $clientSelect.val(res.client_id).trigger('change');
                                        }
                                    }
                                }

                                // Populate item repeatable
                                if (res.items && Array.isArray(res.items) && res.items.length > 0) {
                                    var $container = $(form + ' [data-repeatable-holder]');
                                    if ($container.length) {
                                        $container.empty();

                                        var $btn = $(form + ' [data-repeatable-element-name="proforma_invoice_client_details"], ' +
                                                        form + ' [data-repeatable-element-name="proforma_invoice_client_details_edit"]');
                                        if (!$btn.length) {
                                            $btn = $(form + ' .btn-repeatable-add, ' + form + ' button.add-repeatable-element-button');
                                        }

                                        res.items.forEach(function(item, idx) {
                                            if ($btn.length) {
                                                $btn.trigger('click');
                                            }
                                            setTimeout(function() {
                                                var $lastRow = $container.children().eq(idx);
                                                if (!$lastRow.length) {
                                                    $lastRow = $container.children().last();
                                                }

                                                $lastRow.find('input[data-repeatable-input-name="name"], input[name*="[name]"]').val(item.name).trigger('change');
                                                $lastRow.find('input[data-repeatable-input-name="qty"], input[name*="[qty]"]').val(item.qty).trigger('change');
                                                if (item.device_stock_id) {
                                                    $lastRow.find('input[name*="[device_stock_id]"]').val(item.device_stock_id);
                                                }

                                                var $maskedInput = $lastRow.find('input[data-alt="price_masked"]');
                                                var $hiddenInput = $lastRow.find('input[type="hidden"][name*="[price]"]').last();
                                                if ($hiddenInput.length) {
                                                    $hiddenInput.val(item.price);
                                                }
                                                if ($maskedInput.length) {
                                                    var formattedPrice = (typeof window.formatCurrency === 'function')
                                                        ? window.formatCurrency(item.price, curr)
                                                        : item.price;
                                                    $maskedInput.val(formattedPrice).trigger('input').trigger('change');
                                                }

                                                if (idx === res.items.length - 1) {
                                                    setTimeout(function() {
                                                        instance.repeatableManager.initHandlers();
                                                        instance.logicFormulaNoPO();
                                                        countTotalPrice();
                                                    }, 100);
                                                }
                                            }, (idx + 1) * 80);
                                        });
                                    }
                                } else {
                                    instance.logicFormulaNoPO();
                                    countTotalPrice();
                                }
                            }
                        });
                    });

                    // AJAX Listener Subkon
                    $(form + ' select[name="subkon_id"]').off('select2:select').on('select2:select', function(e) {
                        var id = e.params.data.id;
                        $.ajax({
                            url: '{!! backpack_url("vendor/proforma-invoice/get-subkon-details") !!}',
                            method: 'GET',
                            data: { subkon_id: id },
                            success: function(response) {
                                if (response && response.address) {
                                    $(form + ' input[name="address_po"]').val(response.address);
                                }
                            }
                        });
                    });

                    // Event listener Multi-Currency utama
                    var previousCurrency = $(form + ' select[name="currency_code"]').val() || 'IDR';

                    // function updateFieldValue(fieldName, rawVal, currency) {
                    //     var $hiddenField = $(form + ' #' + fieldName);
                    //     var $maskedField = $(form + ' #' + fieldName + '_masked');
                    //     if (!$hiddenField.length) return;
                    //     var formatted = (typeof window.formatCurrency === 'function')
                    //         ? window.formatCurrency(rawVal, currency)
                    //         : rawVal;
                    //     $hiddenField.val(rawVal);
                    //     $maskedField.val(formatted);
                    // }

                    $(form + ' select[name="currency_code"]').on('change select2:select', function() {
                        var newCurrency = $(this).val() || 'IDR';

                        if (newCurrency !== previousCurrency) {
                            var usdRate = window.usdRate || 16000;
                            instance.convertInvoiceTotals(previousCurrency, newCurrency, usdRate);
                            previousCurrency = newCurrency;
                        }

                        countTotalPrice();
                    });

                    // Event listener nominal utama, PPN, dan PPh
                    $(form + ' #nominal_exclude_ppn_masked').on('keyup input change', function() {
                        instance.logicFormulaNoPO();
                        countTotalPrice();
                    });

                    $(form + ' input[name="tax_ppn"]').on('keyup input change', function() {
                        instance.logicFormulaNoPO();
                        countTotalPrice();
                    });

                    $(form + ' input[name="pph"]').on('keyup input change', function() {
                        instance.logicFormulaNoPO();
                    });

                    // Kalkulasi Real-time Selisih Item
                    var countTotalPrice = function() {
                        var curr = $(form + ' select[name="currency_code"]').val() || 'IDR';
                        var total_items = instance.repeatableManager.calculateTotalItems();
                        var price_between = instance.total_price - total_items;

                        instance.formManager.updateNominalInformationUI(price_between, curr);
                    }

                    function populateDeviceStockSelect2() {
                        var details = (entry && (entry.proforma_invoice_details_edit || entry.proforma_invoice_details || entry.proforma_invoice_client_details || entry.proforma_invoice_client_details_edit)) ? (entry.proforma_invoice_details_edit || entry.proforma_invoice_details || entry.proforma_invoice_client_details || entry.proforma_invoice_client_details_edit) : null;
                        if (details && Array.isArray(details)) {
                            $(form + ' [data-repeatable-holder]').children().each(function(index, el) {
                                var itemData = details[index];
                                if (itemData) {
                                    var $select = $(el).find('select[data-repeatable-input-name="reference_id"], select[name*="[reference_id]"]');
                                    if ($select.length) {
                                        var refId = itemData.reference_id || itemData.device_stock_id;
                                        var refName = (itemData.device_stock && itemData.device_stock.name) ? itemData.device_stock.name : itemData.name;
                                        if (refId && refName) {
                                            if ($select.find("option[value='" + refId + "']").length === 0) {
                                                var newOption = new Option(refName, refId, true, true);
                                                $select.append(newOption);
                                                $select.val(refId).trigger('change.select2');
                                            }
                                        } else if (refName) {
                                            if ($select.find("option[value='" + refName + "']").length === 0) {
                                                var newOption = new Option(refName, refName, true, true);
                                                $select.append(newOption);
                                                $select.val(refName).trigger('change.select2');
                                            }
                                        }
                                    }

                                    // Preservasi dan format harga dari DB (itemData.price) tanpa tertimpa harga master
                                    if (itemData.price !== undefined && itemData.price !== null) {
                                        var $priceMasked = $(el).find('input[data-alt="price_masked"]');
                                        var $priceHidden = $(el).find('input[type="hidden"][name*="[price]"], input[type="hidden"][name="price"]').last();
                                        if (!$priceHidden.length) {
                                            $priceHidden = $priceMasked.parent().next('input[type="hidden"]');
                                        }
                                        var activeCurr = $(form + ' select[name="currency_code"]').val() || 'IDR';
                                        var rawPriceFromDb = parseFloat(itemData.price || 0);
                                        rawPriceFromDb = Number(rawPriceFromDb.toFixed(2));
                                        $priceHidden.val(rawPriceFromDb);
                                        if ($priceMasked.length && typeof window.formatCurrency === 'function') {
                                            $priceMasked.val(window.formatCurrency(rawPriceFromDb, activeCurr));
                                        }
                                    }
                                }
                            });
                        }
                    }

                    $(form).off('select2:select.device_stock', 'select[data-repeatable-input-name="reference_id"], select[name*="[reference_id]"]')
                           .on('select2:select.device_stock', 'select[data-repeatable-input-name="reference_id"], select[name*="[reference_id]"]', function(e) {
                        var data = e.params ? e.params.data : null;
                        if (data && (data.sell_price !== undefined || data.buy_price !== undefined)) {
                            var $row = $(this).closest('.repeatable-element, .repeatable-group, [data-repeatable-holder], div.row');
                            var $priceMasked = $row.find('input[data-alt="price_masked"]');
                            var $priceHidden = $row.find('input[type="hidden"][name*="[price]"], input[type="hidden"][name="price"]').last();
                            if (!$priceHidden.length) {
                                $priceHidden = $priceMasked.parent().next('input[type="hidden"]');
                            }
                            var activeCurr = $(form + ' select[name="currency_code"]').val() || 'IDR';
                            var priceVal = (data.sell_price !== undefined && data.sell_price !== null) 
                                ? parseFloat(data.sell_price) 
                                : (data.buy_price !== undefined ? parseFloat(data.buy_price) : 0);
                            priceVal = Number(priceVal.toFixed(2));
                            if (priceVal > 0) {
                                $priceHidden.val(priceVal);
                                if (typeof window.formatCurrency === 'function') {
                                    $priceMasked.val(window.formatCurrency(priceVal, activeCurr));
                                } else {
                                    $priceMasked.val(priceVal);
                                }
                            }
                            countTotalPrice();
                        }
                    });

                    // Inisialisasi Repeatable Handlers
                    if (form == '#form-edit' || (form == '#form-create' && hasNotificationId && entry != null)) {
                        countTotalPrice();
                        setTimeout(() => {
                            instance.repeatableManager.initHandlers();
                            populateDeviceStockSelect2();
                        }, 150);
                    } else {
                        setTimeout(() => {
                            instance.repeatableManager.initHandlers();
                            populateDeviceStockSelect2();
                        }, 150);
                    }

                    // Listener Hapus & Tambah Item
                    $(document).on("click", ".delete-element", function() {
                        countTotalPrice();
                    });

                    $(form + ' .add-repeatable-element-button').on('click', function() {
                        setTimeout(() => {
                            instance.repeatableManager.initHandlers();
                            instance.logicFormulaNoPO();
                            countTotalPrice();
                        }, 200);
                    });

                    // MODUL 5: DYNAMIC DEVICE STOCK SELECT2 MANAGER FOR PROFORMA INVOICE
                    if (typeof window.ProformaDeviceStockManager === 'undefined') {
                        window.ProformaDeviceStockManager = class ProformaDeviceStockManager {
                            constructor(formSelector) {
                                this.form = formSelector;
                                this.ajaxUrl = '{{ backpack_url("invoice-client/select2-device-stock") }}';
                                this.deviceStockType = 'App\\Models\\DeviceStock';
                                this._isDeviceStock = false;
                            }

                            isDeviceStockMode() {
                                // Hanya aktif jika type_device adalah Persediaan (App\Models\DeviceStock)
                                var currentType = $(this.form + ' select[name="type_device"]').val();
                                return currentType === this.deviceStockType;
                            }

                            activateDeviceStockMode() {
                                var self = this;
                                var form = this.form;
                                this._isDeviceStock = true;

                                $(form + ' .repeatable-element').each(function() {
                                    self._convertRowToSelect2($(this));
                                });
                            }

                            deactivateDeviceStockMode() {
                                var self = this;
                                var form = this.form;
                                this._isDeviceStock = false;

                                $(form + ' .repeatable-element').each(function() {
                                    self._convertRowToText($(this));
                                });
                            }

                            _convertRowToSelect2($row) {
                                var self = this;
                                var $textInput = $row.find('input[data-repeatable-input-name="name"], input[name*="[name]"], input[name="name"]').filter('input[type="text"]');
                                if (!$textInput.length) return;

                                if ($row.find('.proforma-device-select2').length) return;

                                var currentVal = $textInput.val() || '';
                                var $hiddenDeviceStockId = $row.find('input[data-repeatable-input-name="device_stock_id"], input[name*="[device_stock_id]"], input[name="device_stock_id"]');
                                var deviceStockIdVal = $hiddenDeviceStockId.val() || '';

                                var rowIndex = $row.index();
                                var details = (entry && (entry.proforma_invoice_details_edit || entry.proforma_invoice_details || entry.proforma_invoice_client_details || entry.proforma_invoice_client_details_edit)) ? (entry.proforma_invoice_details_edit || entry.proforma_invoice_details || entry.proforma_invoice_client_details || entry.proforma_invoice_client_details_edit) : null;
                                if (details && details[rowIndex]) {
                                    if (!currentVal) {
                                        currentVal = details[rowIndex].name || (details[rowIndex].device_stock ? details[rowIndex].device_stock.name : '');
                                        $textInput.val(currentVal);
                                    }
                                    if (!deviceStockIdVal) {
                                        deviceStockIdVal = details[rowIndex].device_stock_id || details[rowIndex].reference_id || '';
                                        if (deviceStockIdVal && $hiddenDeviceStockId.length) {
                                            $hiddenDeviceStockId.val(deviceStockIdVal);
                                        }
                                    }
                                }

                                $textInput.hide();

                                var $select2Container = $('<div class="proforma-device-select2-wrapper" style="flex:1;min-width:0;"></div>');
                                var $select2 = $('<select class="form-control proforma-device-select2" style="width:100%"></select>');

                                if (currentVal) {
                                    var optVal = deviceStockIdVal || currentVal;
                                    var initialOption = new Option(currentVal, optVal, true, true);
                                    $select2.append(initialOption);
                                }

                                $select2Container.append($select2);
                                $textInput.closest('.form-group').append($select2Container);

                                $select2.select2({
                                    ajax: {
                                        url: self.ajaxUrl,
                                        dataType: 'json',
                                        delay: 300,
                                        data: function(params) {
                                            return {
                                                q: params.term || '',
                                                company_id: $(self.form + ' select[name="company_id"]').val() || ''
                                            };
                                        },
                                        processResults: function(data) {
                                            return { results: data.results };
                                        },
                                        cache: true
                                    },
                                    placeholder: 'Pilih Nama Barang',
                                    minimumInputLength: 0,
                                    allowClear: true,
                                    dropdownParent: $(self.form),
                                    templateResult: function(data) {
                                        if (!data.id) return data.text;
                                        return $('<span><strong>' + data.name + '</strong> <small class="text-muted">(Stok: ' + (data.qty_available || 0) + ')</small></span>');
                                    },
                                    templateSelection: function(data) {
                                        return data.name || data.text;
                                    }
                                });

                                $select2.on('select2:select', function(e) {
                                    var selected = e.params.data;
                                    $textInput.val(selected.name || selected.text);
                                    if ($hiddenDeviceStockId.length) {
                                        $hiddenDeviceStockId.val(selected.id);
                                    }

                                    if (selected.sell_price !== undefined || selected.buy_price !== undefined) {
                                        var $priceHidden = $row.find('input[type="hidden"][data-repeatable-input-name="price"], input[type="hidden"][name*="[price]"]').last();
                                        var $priceMasked = $row.find('input[data-alt="price_masked"]');
                                        var activeCurrency = $(self.form + ' select[name="currency_code"]').val() || 'IDR';

                                        var priceVal = (selected.sell_price !== undefined && selected.sell_price !== null) 
                                            ? parseFloat(selected.sell_price) 
                                            : (selected.buy_price !== undefined ? parseFloat(selected.buy_price) : 0);
                                        priceVal = Number(priceVal.toFixed(2));
                                        if (priceVal > 0) {
                                            if ($priceHidden.length) $priceHidden.val(priceVal);
                                            if ($priceMasked.length && typeof window.formatCurrency === 'function') {
                                                $priceMasked.val(window.formatCurrency(priceVal, activeCurrency)).trigger('change');
                                            }
                                        }
                                        countTotalPrice();
                                    }
                                });

                                $select2.on('select2:clear', function() {
                                    $textInput.val('');
                                    if ($hiddenDeviceStockId.length) {
                                        $hiddenDeviceStockId.val('');
                                    }
                                });
                            }

                            _convertRowToText($row) {
                                var $select2Wrapper = $row.find('.proforma-device-select2-wrapper');
                                var $select2 = $row.find('.proforma-device-select2');
                                var $textInput = $row.find('input[data-repeatable-input-name="name"], input[name*="[name]"], input[name="name"]').filter('input[type="text"]');
                                var $hiddenDeviceStockId = $row.find('input[data-repeatable-input-name="device_stock_id"], input[name*="[device_stock_id]"], input[name="device_stock_id"]');

                                var selectedText = '';
                                if ($select2.length) {
                                    try {
                                        var sData = $select2.select2('data');
                                        if (sData && sData[0]) {
                                            selectedText = sData[0].name || sData[0].text || '';
                                        }
                                    } catch(e) {}
                                    $select2.select2('destroy');
                                }
                                $select2Wrapper.remove();

                                if (selectedText) {
                                    $textInput.val(selectedText);
                                }
                                $textInput.show();
                                if ($hiddenDeviceStockId.length) $hiddenDeviceStockId.val('');
                            }

                            init() {
                                var self = this;
                                var form = this.form;

                                setTimeout(function() {
                                    var details = (entry && (entry.proforma_invoice_details_edit || entry.proforma_invoice_details || entry.proforma_invoice_client_details || entry.proforma_invoice_client_details_edit)) ? (entry.proforma_invoice_details_edit || entry.proforma_invoice_details || entry.proforma_invoice_client_details || entry.proforma_invoice_client_details_edit) : null;
                                    if (details && Array.isArray(details)) {
                                        $(form + ' .repeatable-element').each(function(idx) {
                                            var $text = $(this).find('input[data-repeatable-input-name="name"], input[name*="[name]"], input[name="name"]').filter('input[type="text"]');
                                            if ($text.length && !$text.val() && details[idx]) {
                                                var itemName = details[idx].name || (details[idx].device_stock ? details[idx].device_stock.name : '');
                                                if (itemName) {
                                                    $text.val(itemName);
                                                }
                                            }
                                            var $hiddenStock = $(this).find('input[data-repeatable-input-name="device_stock_id"], input[name*="[device_stock_id]"], input[name="device_stock_id"]');
                                            if ($hiddenStock.length && !$hiddenStock.val() && details[idx]) {
                                                var stockId = details[idx].device_stock_id || details[idx].reference_id || '';
                                                if (stockId) {
                                                    $hiddenStock.val(stockId);
                                                }
                                            }
                                        });
                                    }

                                    if (self.isDeviceStockMode()) {
                                        self.activateDeviceStockMode();
                                    }
                                }, 400);

                                $(form + ' select[name="type_device"]').off('change.type_device').on('change.type_device', function() {
                                    if (self.isDeviceStockMode()) {
                                        self.activateDeviceStockMode();
                                    } else {
                                        self.deactivateDeviceStockMode();
                                    }
                                });

                                $(form + ' .add-repeatable-element-button').off('click.type_device').on('click.type_device', function() {
                                    if (self._isDeviceStock) {
                                        setTimeout(function() {
                                            $(form + ' .repeatable-element').each(function() {
                                                if (!$(this).find('.proforma-device-select2').length) {
                                                    self._convertRowToSelect2($(this));
                                                }
                                            });
                                        }, 300);
                                    }
                                });
                            }
                        };
                    }

                    var proformaMgr = new window.ProformaDeviceStockManager(form);
                    proformaMgr.init();

                }
            }
        });
        SIAOPS.getAttribute('logic_invoice').load();
    </script>
@endpush