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
                let cleanVal = (curr === 'IDR') ? Math.round(parseFloat(value) || 0) : value;
                let nominal = (typeof window.formatCurrency === 'function')
                    ? window.formatCurrency(cleanVal, curr)
                    : (curr === 'USD' ? Number(cleanVal).toFixed(2) : formatIdr(cleanVal));
                $(selected).val(nominal).trigger('input');
            }
        }

        /* =========================================================================
         * MODUL 1: GLOBAL UTILITY HELPERS (Fallback Format IDR)
         * ========================================================================= */
        if (typeof setInputNumber2 == "undefined") {
            function formatIdr(angka) {
                const formatter = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR'
                });
                return formatter.format(angka).replace('Rp', '').trim();
            }

            function setInputNumber2(selected, value) {
                let nominal = formatIdr(value);
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
                    var str = val.toString().trim();
                    // Jika data awal dari DB mengandung format desimal murni .00 atau .0000 (tanpa pemisah ribuan)
                    if (isInitial && str.indexOf('.') !== -1 && !str.includes(',')) {
                        var parts = str.split('.');
                        if (parts.length === 2 && /^0+$/.test(parts[1])) {
                            str = parts[0];
                        }
                    }
                    return str.replace(/[^\d-]/g, '');
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

                    // Delegated listener terpusat pada form untuk QTY dan Price (masked maupun hidden)
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
                        if (initialVal) {
                            var cleanInitial = self.cleanValue(initialVal, curr, true);
                            $hiddenInput.val(cleanInitial);
                            if (typeof window.formatCurrency === 'function') {
                                $maskedInput.val(window.formatCurrency(cleanInitial, curr));
                            }
                        }

                        $maskedInput.off('input change keyup.custom_repeat').on('input change keyup.custom_repeat', function() {
                            var activeCurrency = $(self.form + ' select[name="currency_code"]').val() || 'IDR';
                            var rawVal = $(this).val() || '';
                            var clean = self.cleanValue(rawVal, activeCurrency, false);

                            $hiddenInput.val(clean);
                            if (typeof window.formatCurrency === 'function') {
                                $(this).val(window.formatCurrency(clean, activeCurrency));
                            }
                            if (typeof self.onCalculate === 'function') self.onCalculate();
                        });
                    });
                }

                calculateTotalItems() {
                    var self = this;
                    var total_price = 0;
                    var curr = $(this.form + ' select[name="currency_code"]').val() || 'IDR';

                    // Cari seluruh baris repeatable item (baik invoice_client_details maupun invoice_client_details_edit)
                    $(this.form + ' [data-repeatable-holder]').children().each(function() {
                        var $row = $(this);
                        var $masked = $row.find('input[data-alt="price_masked"]');
                        var $hidden = $row.find('input[type="hidden"][name*="[price]"], input[type="hidden"][name="price"]').last();
                        
                        var priceVal = 0;
                        var rawValue = ($hidden.length && $hidden.val() !== '') ? $hidden.val() : ($masked.length ? $masked.val() : '0');
                        priceVal = parseFloat(self.cleanValue(rawValue, curr, false)) || 0;

                        var qtyVal = parseFloat($row.find('input[data-repeatable-input-name="qty"], input[name*="[qty]"], input[name*="qty"]').val() || 1) || 1;

                        total_price += (priceVal * qtyVal);
                    });

                    return total_price;
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
                        $excMasked.val(typeof window.formatCurrency === 'function' ? window.formatCurrency(rawExc, curr) : rawExc);
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
                    if (entry.client_po_id) {
                        var $poSelect = $(form + ' select[name="client_po_id"]');
                        if ($poSelect.length) {
                            var poNumber = (entry.client_po && entry.client_po.po_number)
                                ? entry.client_po.po_number
                                : (entry.client_po_number || ('ID: ' + entry.client_po_id));
                            var poOption = new Option(poNumber, entry.client_po_id, true, true);
                            $poSelect.empty().append(poOption);
                        } else {
                            $(form + ' input[name="client_po_id"]').val(entry.client_po_id);
                        }
                    }
                    if (entry.address_po) $(form + ' input[name="address_po"]').val(entry.address_po);
                    if (entry.description) $(form + ' textarea[name="description"], ' + form + ' input[name="description"]').val(entry.description);
                    if (entry.withholding_agent) $(form + ' select[name="withholding_agent"]').val(entry.withholding_agent).trigger('change');
                    if (entry.account_source_id) $(form + ' select[name="account_source_id"]').val(entry.account_source_id).trigger('change');
                    if (entry.type_device) $(form + ' select[name="type_device"]').val(entry.type_device).trigger('change');
                    if (entry.kdp) $(form + ' input[name="kdp"]').val(entry.kdp);

                    if (entry.pic) $(form + ' input[name="pic"]').val(entry.pic);

                    // Populate & select Client (Select2 AJAX)
                    if (entry.client_id) {
                        var $clientSelect = $(form + ' select[name="client_id"]');
                        if ($clientSelect.length) {
                            var clientName = (entry.client && entry.client.name) 
                                ? entry.client.name 
                                : (entry.client_name || ('ID: ' + entry.client_id));
                            var clientOption = new Option(clientName, entry.client_id, true, true);
                            $clientSelect.empty().append(clientOption);
                        }
                    }

                    // Populate & select Delivery Note / Surat Jalan (Select2 AJAX)
                    if (entry.delivery_note_id) {
                        var $dnSelect = $(form + ' select[name="delivery_note_id"]');
                        if ($dnSelect.length) {
                            var dnNumber = (entry.delivery_note && entry.delivery_note.number) 
                                ? entry.delivery_note.number 
                                : (entry.delivery_note_number || ('ID: ' + entry.delivery_note_id));
                            var dnOption = new Option(dnNumber, entry.delivery_note_id, true, true);
                            $dnSelect.empty().append(dnOption);
                        }
                    }

                    // Populate & select Subkon (Select2 AJAX)
                    if (entry.subkon) {
                        var $subkonSelect = $(form + ' select[name="subkon_id"]');
                        if ($subkonSelect.length) {
                            var subkonName = (entry.subkon && entry.subkon.name) ? entry.subkon.name : (entry.subkon_name || entry.subkon_id);
                            var newOption = new Option(subkonName, entry.subkon_id, true, true);
                            $subkonSelect.append(newOption).trigger('change');
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
         * MODUL 4: DEKLARASI MODUL LOGIKA FORM UTAMA (SIAOPS) - INVOICE CLIENT
         * ========================================================================= */
        SIAOPS.setAttribute('logic_invoice_client', function() {
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
                    var nominal_exclude_ppn = getInputNumber(form + ' input[name="nominal_exclude_ppn"]');
                    var tax_ppn = getInputNumber(form + ' input[name="tax_ppn"]');
                    var nilai_ppn = (tax_ppn == 0) ? 0 : (nominal_exclude_ppn * (tax_ppn / 100));
                    var total = nominal_exclude_ppn + nilai_ppn;

                    if (curr === 'IDR') {
                        total = Math.round(total);
                    }

                    setInputNumberCurrency(form + ' input[name="nominal_include_ppn"]', total, curr);
                    instance.total_price = Number($(form + ' input[name="nominal_exclude_ppn"]').val());

                    var pph = getInputNumber(form + ' input[name="pph"]');
                    var diskon_pph = (pph == 0) ? 0 : nominal_exclude_ppn * (pph / 100);

                    if (curr === 'IDR') {
                        diskon_pph = Math.round(diskon_pph);
                    }

                    setInputNumberCurrency(form + ' input[name="discount_pph"]', diskon_pph, curr);
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
                            instance.formManager.populateFormData(entry);
                            instance.repeatableManager.initHandlers();
                            countTotalPrice();
                        }, 100);
                    }

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

                    function updateFieldValue(fieldName, rawVal, currency) {
                        var $hiddenField = $(form + ' #' + fieldName);
                        var $maskedField = $(form + ' #' + fieldName + '_masked');
                        if (!$hiddenField.length) return;
                        var formatted = (typeof window.formatCurrency === 'function')
                            ? window.formatCurrency(rawVal, currency)
                            : rawVal;
                        $hiddenField.val(rawVal);
                        $maskedField.val(formatted);
                    }

                    $(form + ' select[name="currency_code"]').on('change select2:select', function() {
                        var newCurrency = $(this).val() || 'IDR';

                        if (newCurrency !== previousCurrency) {
                            var usdRate = window.usdRate || 16000;
                            instance.convertInvoiceTotals(previousCurrency, newCurrency, usdRate);
                            previousCurrency = newCurrency;
                        }

                        countTotalPrice();
                    });

                    // Event listener client_po_id untuk auto-fill Mata Uang, Nominal Exclude PPn, dan PPn
                    $(form).off('select2:select.po_select change.po_select', 'select[name="client_po_id"]')
                           .on('select2:select.po_select change.po_select', 'select[name="client_po_id"]', function(e) {
                        var poId = $(this).val();
                        if (!poId && e && e.params && e.params.data) {
                            poId = e.params.data.id;
                        }
                        if (!poId) return;

                        $.ajax({
                            url: '{!! backpack_url("invoice-client/get-client-po") !!}',
                            method: 'GET',
                            data: { id: poId },
                            success: function(res) {
                                if (!res || !res.result) return;
                                var poData = res.result;

                                // 1. Update Mata Uang jika ada
                                var newCurr = poData.currency_code || 'IDR';
                                var $currSelect = $(form + ' select[name="currency_code"]');
                                if ($currSelect.length && $currSelect.val() !== newCurr) {
                                    $currSelect.val(newCurr).trigger('change');
                                    previousCurrency = newCurr;
                                }

                                var activeCurr = $(form + ' select[name="currency_code"]').val() || newCurr;

                                // 2. Auto-select Client (client_id)
                                if (poData.client_id) {
                                    var $clientSelect = $(form + ' select[name="client_id"]');
                                    if ($clientSelect.length) {
                                        var clientName = poData.client_name || (poData.client ? poData.client.name : ('ID: ' + poData.client_id));
                                        if ($clientSelect.find('option[value="' + poData.client_id + '"]').length) {
                                            $clientSelect.val(poData.client_id);
                                        } else {
                                            var clientOpt = new Option(clientName, poData.client_id, true, true);
                                            $clientSelect.append(clientOpt);
                                        }
                                    }
                                }

                                // 3. Update Nominal Exclude PPn dari job_value
                                var rawJobValue = (poData.job_value !== undefined && poData.job_value !== null) ? poData.job_value : 0;
                                var cleanJobValue = (activeCurr === 'IDR') ? Math.round(parseFloat(rawJobValue) || 0) : (parseFloat(rawJobValue) || 0);

                                var $hiddenExc = $(form + ' #nominal_exclude_ppn, ' + form + ' input[name="nominal_exclude_ppn"]');
                                var $maskedExc = $(form + ' #nominal_exclude_ppn_masked, ' + form + ' input[data-alt="nominal_exclude_ppn_masked"]');

                                if ($hiddenExc.length) $hiddenExc.val(cleanJobValue);
                                if ($maskedExc.length) {
                                    $maskedExc.val(typeof window.formatCurrency === 'function' ? window.formatCurrency(cleanJobValue, activeCurr) : cleanJobValue);
                                }

                                // 4. Update PPn
                                if (poData.tax_ppn !== undefined && poData.tax_ppn !== null) {
                                    $(form + ' input[name="tax_ppn"]').val(poData.tax_ppn);
                                }

                                // 5. Update Deskripsi / Job Name jika kosong
                                if (poData.job_name) {
                                    var $desc = $(form + ' textarea[name="description"], ' + form + ' input[name="description"]');
                                    if ($desc.length && !$desc.val()) {
                                        $desc.val(poData.job_name);
                                    }
                                }

                                // 6. Update Alamat jika ada
                                if (poData.address || poData.address_po) {
                                    $(form + ' input[name="address_po"]').val(poData.address || poData.address_po);
                                }

                                // 7. Hitung ulang total include PPn & Diskon PPh
                                instance.logicFormulaNoPO();
                            },
                            error: function(xhr) {
                                console.warn('[InvoiceClient] Error fetching Client PO details:', xhr);
                            }
                        });
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

                    // Kalkulasi Real-time Akumulasi Item ke Nominal Exclude PPN
                    var countTotalPrice = function() {
                        var curr = $(form + ' select[name="currency_code"]').val() || 'IDR';
                        var total_items = instance.repeatableManager.calculateTotalItems();
                        
                        var $maskedExc = $(form + ' #nominal_exclude_ppn_masked, ' + form + ' input[data-alt="nominal_exclude_ppn_masked"]');
                        var $hiddenExc = $(form + ' #nominal_exclude_ppn, ' + form + ' input[name="nominal_exclude_ppn"]');

                        var formattedVal = (typeof window.formatCurrency === 'function')
                            ? window.formatCurrency(total_items, curr)
                            : total_items;

                        if ($maskedExc.length) {
                            $maskedExc.val(formattedVal);
                        }
                        if ($hiddenExc.length) {
                            $hiddenExc.val(total_items);
                        }

                        instance.logicFormulaNoPO();
                    }

                    // Inisialisasi Repeatable Handlers
                    instance.repeatableManager.initHandlers();
                    countTotalPrice();
                    setTimeout(() => {
                        instance.repeatableManager.initHandlers();
                        countTotalPrice();
                    }, 200);

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

                }
            }
        });
        /* =========================================================================
         * MODUL 5: DYNAMIC DEVICE STOCK SELECT2 MANAGER
         * Mengubah field `name` pada repeatable invoice item menjadi Select2 AJAX
         * saat type_device = 'App\Models\DeviceStock' (Persediaan), dan mengembalikan
         * ke text input saat type_device lainnya dipilih.
         * ========================================================================= */
        if (typeof window.InvoiceDeviceStockManager === 'undefined') {
            window.InvoiceDeviceStockManager = class InvoiceDeviceStockManager {
                constructor(formSelector) {
                    this.form = formSelector;
                    this.ajaxUrl = '{{ backpack_url("invoice-client/select2-device-stock") }}';
                    this.deviceStockType = 'App\\Models\\DeviceStock';
                    this._isDeviceStock = false;
                }

                isDeviceStockMode() {
                    return $(this.form + ' select[name="type_device"]').val() === this.deviceStockType;
                }

                getNameLabel(form) {
                    // Ambil label dari row pertama (jika ada)
                    var $firstLabel = $(form + ' .repeatable-element:first .form-group:first label');
                    return ($firstLabel.length && $firstLabel.text().trim())
                        ? $firstLabel.text().trim()
                        : '{{ trans("backpack::crud.invoice_client.field.item.items.name.label") ?: "Nama Item" }}';
                }

                /**
                 * Update status tampilan dan validasi client_po_id berdasarkan mode type_device.
                 * Field client_po_id selalu ditampilkan (show) agar fleksibel.
                 */
                syncClientPoField() {
                    var form = this.form;
                    var isStock = this.isDeviceStockMode();
                    var $poWrapper = $(form + ' select[name="client_po_id"], ' + form + ' input[name="client_po_id"]').closest('.form-group');
                    var $poSelect = $(form + ' select[name="client_po_id"]');

                    $poWrapper.show();
                    if (isStock) {
                        $poSelect.removeAttr('required');
                    } else {
                        $poSelect.attr('required', 'required');
                    }
                }

                /**
                 * Konversi semua text input `name` pada repeatable menjadi Select2 AJAX.
                 */
                activateDeviceStockMode() {
                    var self = this;
                    var form = this.form;
                    this._isDeviceStock = true;

                    this.syncClientPoField();

                    $(form + ' .repeatable-element').each(function() {
                        self._convertRowToSelect2($(this));
                    });
                }

                /**
                 * Kembalikan semua Select2 AJAX pada repeatable ke text input biasa.
                 */
                deactivateDeviceStockMode() {
                    var self = this;
                    var form = this.form;
                    this._isDeviceStock = false;

                    this.syncClientPoField();

                    $(form + ' .repeatable-element').each(function() {
                        self._convertRowToText($(this));
                    });
                }

                /**
                 * Ubah satu baris repeatable: text input → Select2 AJAX.
                 */
                _convertRowToSelect2($row) {
                    var self = this;
                    var $textInput = $row.find('input[data-repeatable-input-name="name"], input[name*="[name]"], input[name="name"]').filter('input[type="text"]');
                    if (!$textInput.length) return;

                    // Jika sudah ada Select2, skip
                    if ($row.find('.invoice-device-select2').length) return;

                    var currentVal = $textInput.val();
                    var currentStockId = $row.find('input[data-repeatable-input-name="device_stock_id"], input[name*="[device_stock_id]"], input[name="device_stock_id"]').val();

                    // Buat element <select> pengganti
                    var $select = $('<select class="form-control select2_field invoice-device-select2" style="width: 100%;"></select>');
                    $select.attr('placeholder', '{{ trans("backpack::crud.invoice_client.field.item.items.name.placeholder") ?: "Pilih Barang Persediaan" }}');

                    // Sembunyikan text input
                    $textInput.hide();
                    $textInput.after($select);

                    // Inisialisasi Select2 AJAX
                    $select.select2({
                        theme: 'bootstrap',
                        placeholder: 'Pilih Barang Persediaan',
                        allowClear: true,
                        ajax: {
                            url: self.ajaxUrl,
                            dataType: 'json',
                            delay: 250,
                            data: function(params) {
                                return {
                                    q: params.term || '',
                                    page: params.page || 1,
                                    company_id: $(self.form + ' select[name="company_id"]').val() || ''
                                };
                            },
                            processResults: function(data, params) {
                                params.page = params.page || 1;
                                return {
                                    results: data.results || [],
                                    pagination: {
                                        more: (params.page * 20) < (data.total || 0)
                                    }
                                };
                            },
                            cache: true
                        },
                        minimumInputLength: 0
                    });

                    // Event saat barang dipilih dari Select2
                    $select.on('select2:select', function(e) {
                        var selectedData = e.params.data;
                        var stockName = selectedData.name || selectedData.text || '';
                        var sellPrice = parseFloat(selectedData.sell_price || 0);

                        $textInput.val(stockName).trigger('change');

                        // Set device_stock_id hidden field
                        var $stockIdField = $row.find('input[data-repeatable-input-name="device_stock_id"], input[name*="[device_stock_id]"], input[name="device_stock_id"]');
                        if ($stockIdField.length) {
                            $stockIdField.val(selectedData.id);
                        }

                        // Auto-fill harga jual ke field price
                        var activeCurr = $(self.form + ' select[name="currency_code"]').val() || 'IDR';
                        var cleanPrice = (activeCurr === 'IDR') ? Math.round(sellPrice) : sellPrice;
                        var formattedPrice = (typeof window.formatCurrency === 'function')
                            ? window.formatCurrency(cleanPrice, activeCurr)
                            : cleanPrice;

                        var $priceMasked = $row.find('input[data-alt="price_masked"]');
                        var $priceHidden = $row.find('input[type="hidden"][name*="[price]"], input[type="hidden"][name="price"]').last();

                        if ($priceHidden.length) $priceHidden.val(cleanPrice);
                        if ($priceMasked.length) {
                            $priceMasked.val(formattedPrice).trigger('input');
                        }

                        // Trigger kalkulasi total invoice
                        $(self.form + ' #nominal_exclude_ppn_masked').trigger('change');
                    });

                    // Event saat barang di-clear
                    $select.on('select2:clear', function() {
                        $textInput.val('').trigger('change');
                        $row.find('input[data-repeatable-input-name="device_stock_id"], input[name*="[device_stock_id]"], input[name="device_stock_id"]').val('');
                    });

                    // Set nilai awal jika sudah ada (misal pada form Edit atau prefill)
                    if (currentStockId) {
                        var initialText = currentVal || ('ID: ' + currentStockId);
                        var opt = new Option(initialText, currentStockId, true, true);
                        $select.append(opt).trigger('change');
                    } else if (currentVal) {
                        // Coba cari option dengan teks yang sama atau set sebagai text option sementara
                        var opt = new Option(currentVal, '', true, true);
                        $select.append(opt);
                    }
                }

                /**
                 * Kembalikan satu baris repeatable: Select2 AJAX → text input biasa.
                 */
                _convertRowToText($row) {
                    var $select2 = $row.find('.invoice-device-select2');
                    var $textInput = $row.find('input[data-repeatable-input-name="name"], input[name*="[name]"], input[name="name"]').filter('input[type="text"]');

                    if ($select2.length) {
                        $select2.select2('destroy');
                        $select2.remove();
                    }

                    if ($textInput.length) {
                        $textInput.show();
                    }

                    // Kosongkan device_stock_id jika bukan mode Persediaan
                    $row.find('input[data-repeatable-input-name="device_stock_id"], input[name*="[device_stock_id]"], input[name="device_stock_id"]').val('');
                }

                /**
                 * Inisialisasi handler perubahan type_device.
                 * Dipanggil dari SIAOPS load().
                 */
                init() {
                    var self = this;
                    var form = this.form;

                    // Jalankan mode awal berdasarkan nilai type_device saat load
                    setTimeout(function() {
                        if (self.isDeviceStockMode()) {
                            self.activateDeviceStockMode();
                        }
                    }, 400);

                    // Listener change pada type_device
                    $(form + ' select[name="type_device"]').on('change', function() {
                        if (self.isDeviceStockMode()) {
                            self.activateDeviceStockMode();
                        } else {
                            self.deactivateDeviceStockMode();
                        }
                    });

                    // Handler untuk baris baru yang ditambah via tombol "Tambah Item"
                    $(form + ' .add-repeatable-element-button').on('click', function() {
                        if (self._isDeviceStock) {
                            setTimeout(function() {
                                $(form + ' .repeatable-element').each(function() {
                                    if (!$(this).find('.invoice-device-select2').length) {
                                        self._convertRowToSelect2($(this));
                                    }
                                });
                            }, 300);
                        }
                    });
                }
            };
        }

        /* =========================================================================
         * MODUL 6: DYNAMIC DELIVERY NOTE MANAGER
         * Mengisi form client, alamat, dan item barang secara otomatis
         * saat Surat Jalan (delivery_note_id) dipilih.
         * ========================================================================= */
        if (typeof window.InvoiceDeliveryNoteManager === 'undefined') {
            window.InvoiceDeliveryNoteManager = class InvoiceDeliveryNoteManager {
                constructor(formSelector, logicInstance) {
                    this.form = formSelector;
                    this.logicInstance = logicInstance;
                    this.detailsUrl = '{{ backpack_url("invoice-client/get-delivery-note-details") }}';
                }

                init() {
                    var self = this;
                    var form = this.form;

                    $(form).off('select2:select.dn_select', 'select[name="delivery_note_id"]')
                           .on('select2:select.dn_select', 'select[name="delivery_note_id"]', function() {
                        var deliveryNoteId = $(this).val();
                        if (!deliveryNoteId) return;

                        $.ajax({
                            url: self.detailsUrl,
                            type: 'GET',
                            data: { delivery_note_id: deliveryNoteId },
                            success: function(res) {
                                if (!res || !res.success) return;

                                // 1. Prefill Client
                                if (res.client_id) {
                                    var $clientSelect = $(form + ' select[name="client_id"]');
                                    var $dnSelect = $(form + ' select[name="delivery_note_id"]');
                                    var currentDnVal = $dnSelect.val() || deliveryNoteId;
                                    var currentDnText = $dnSelect.find('option:selected').text() || '';

                                    if ($clientSelect.length) {
                                        if ($clientSelect.find('option[value="' + res.client_id + '"]').length) {
                                            $clientSelect.val(res.client_id);
                                        } else if (res.client_name) {
                                            var opt = new Option(res.client_name, res.client_id, true, true);
                                            $clientSelect.append(opt);
                                        }
                                    }

                                    // Restore pilihan delivery_note_id yang baru terpilih agar tidak tereset oleh dependency select2
                                    if (currentDnVal && $dnSelect.length) {
                                        setTimeout(function() {
                                            if (!$dnSelect.find('option[value="' + currentDnVal + '"]').length) {
                                                var dnOpt = new Option(currentDnText || ('ID: ' + currentDnVal), currentDnVal, true, true);
                                                $dnSelect.empty().append(dnOpt);
                                            } else {
                                                $dnSelect.val(currentDnVal);
                                            }
                                        }, 150);
                                    }
                                }

                                // 2. Prefill Client PO jika Surat Jalan terhubung ke Client PO
                                if (res.client_po_id) {
                                    var $poSelect = $(form + ' select[name="client_po_id"]');
                                    var poText = res.client_po_number || ('PO ID: ' + res.client_po_id);
                                    if ($poSelect.length) {
                                        var poOpt = new Option(poText, res.client_po_id, true, true);
                                        $poSelect.empty().append(poOpt).trigger('change');
                                    } else {
                                        $(form + ' input[name="client_po_id"]').val(res.client_po_id);
                                    }
                                }

                                // 3. Prefill Alamat & Deskripsi
                                if (res.address) {
                                    $(form + ' input[name="address_po"]').val(res.address);
                                }
                                if (res.description) {
                                    var $desc = $(form + ' textarea[name="description"], ' + form + ' input[name="description"]');
                                    if ($desc.length && !$desc.val()) {
                                        $desc.val(res.description);
                                    }
                                }

                                // 4. Set Tipe Barang -> DeviceStock (Persediaan)
                                var $typeDevice = $(form + ' select[name="type_device"]');
                                var isTypeDeviceChanged = false;
                                if ($typeDevice.length && $typeDevice.val() !== 'App\\Models\\DeviceStock') {
                                    $typeDevice.val('App\\Models\\DeviceStock').trigger('change');
                                    isTypeDeviceChanged = true;
                                }

                                // 5. Prefill items ke form repeatable setelah mode persediaan aktif
                                var delayMs = isTypeDeviceChanged ? 150 : 0;
                                setTimeout(function() {
                                    if (res.items && res.items.length) {
                                        self.populateItems(res.items);
                                    }
                                }, delayMs);
                            },
                            error: function(xhr) {
                                console.warn('[InvoiceClient] Error fetching DeliveryNote details:', xhr);
                            }
                        });
                    });
                }

                populateItems(items) {
                    var self = this;
                    var form = this.form;

                    items.forEach(function(item, idx) {
                        var $rows = $(form + ' .repeatable-element');
                        var $targetRow = $rows.eq(idx);

                        if (!$targetRow.length) {
                            $(form + ' .add-repeatable-element-button').trigger('click');
                            $rows = $(form + ' .repeatable-element');
                            $targetRow = $rows.eq(idx);
                        }

                        var $nameInput = $targetRow.find('input[data-repeatable-input-name="name"], input[name*="[name]"], input[name="name"]').filter('input[type="text"]');
                        var $stockIdInput = $targetRow.find('input[data-repeatable-input-name="device_stock_id"], input[name*="[device_stock_id]"], input[name="device_stock_id"]');
                        var $dnDetailIdInput = $targetRow.find('input[data-repeatable-input-name="delivery_note_detail_id"], input[name*="[delivery_note_detail_id]"], input[name="delivery_note_detail_id"]');
                        var $qtyInput = $targetRow.find('input[data-repeatable-input-name="qty"], input[name*="[qty]"], input[name="qty"]');
                        var $priceMasked = $targetRow.find('input[data-alt="price_masked"]');
                        var $priceHidden = $targetRow.find('input[type="hidden"][name*="[price]"], input[type="hidden"][name="price"]').last();

                        if ($nameInput.length) $nameInput.val(item.name || item.description || '');
                        if ($stockIdInput.length) $stockIdInput.val(item.device_stock_id || '');
                        if ($dnDetailIdInput.length) $dnDetailIdInput.val(item.delivery_note_detail_id || '');
                        if ($qtyInput.length) $qtyInput.val(item.qty || 1);

                        var activeCurr = $(form + ' select[name="currency_code"]').val() || 'IDR';
                        var priceVal = parseFloat(item.price || 0);
                        var cleanPrice = (activeCurr === 'IDR') ? Math.round(priceVal) : priceVal;
                        var formattedPrice = (typeof window.formatCurrency === 'function') ? window.formatCurrency(cleanPrice, activeCurr) : cleanPrice;
                        
                        if ($priceHidden.length) $priceHidden.val(cleanPrice);
                        if ($priceMasked.length) {
                            $priceMasked.val(formattedPrice);
                        }

                        var $select2 = $targetRow.find('.invoice-device-select2');
                        if ($select2.length && item.device_stock_id) {
                            var stockText = item.device_stock_name || item.name || ('ID: ' + item.device_stock_id);
                            var opt = new Option(stockText, item.device_stock_id, true, true);
                            $select2.empty().append(opt);
                        }
                    });

                    setTimeout(function() {
                        items.forEach(function(item, idx) {
                            var $rows = $(form + ' .repeatable-element');
                            var $targetRow = $rows.eq(idx);
                            if ($targetRow.length) {
                                var $priceMasked = $targetRow.find('input[data-alt="price_masked"]');
                                var $priceHidden = $targetRow.find('input[type="hidden"][name*="[price]"], input[type="hidden"][name="price"]').last();
                                var activeCurr = $(form + ' select[name="currency_code"]').val() || 'IDR';
                                var priceVal = parseFloat(item.price || 0);
                                var cleanPrice = (activeCurr === 'IDR') ? Math.round(priceVal) : priceVal;
                                var formattedPrice = (typeof window.formatCurrency === 'function') ? window.formatCurrency(cleanPrice, activeCurr) : cleanPrice;
                                if ($priceHidden.length) $priceHidden.val(cleanPrice);
                                if ($priceMasked.length) $priceMasked.val(formattedPrice);
                            }
                        });

                        if (self.logicInstance && self.logicInstance.repeatableManager) {
                            var totalItems = self.logicInstance.repeatableManager.calculateTotalItems();
                            var $nominalExcHidden = $(form + ' #nominal_exclude_ppn, ' + form + ' input[name="nominal_exclude_ppn"]');
                            var $nominalExcMasked = $(form + ' #nominal_exclude_ppn_masked');
                            var activeCurr = $(form + ' select[name="currency_code"]').val() || 'IDR';

                            if (totalItems > 0) {
                                if ($nominalExcHidden.length) $nominalExcHidden.val(totalItems);
                                if ($nominalExcMasked.length && typeof window.formatCurrency === 'function') {
                                    $nominalExcMasked.val(window.formatCurrency(totalItems, activeCurr));
                                }
                            }
                            if (typeof self.logicInstance.logicFormulaNoPO === 'function') {
                                self.logicInstance.logicFormulaNoPO();
                            }
                        }
                    }, 300);
                }
            };
        }

        SIAOPS.getAttribute('logic_invoice_client').load();

        // Init Modul 5 & 6 setelah SIAOPS load
        (function() {
            var form_type = "{{ $crud->getActionMethod() }}";
            var form = (form_type == 'create') ? '#form-create' : '#form-edit';
            var logicAttr = SIAOPS.getAttribute('logic_invoice_client');

            if (typeof window.InvoiceDeviceStockManager !== 'undefined') {
                var deviceStockMgr = new window.InvoiceDeviceStockManager(form);
                deviceStockMgr.init();
            }

            if (typeof window.InvoiceDeliveryNoteManager !== 'undefined') {
                var dnMgr = new window.InvoiceDeliveryNoteManager(form, logicAttr);
                dnMgr.init();
            }
        })();
    </script>
@endpush
