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
                    if (isInitial && str.includes('.')) {
                        var num = parseFloat(str);
                        if (!isNaN(num)) {
                            return Math.round(num).toString();
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

                    // Delegated listener untuk seluruh input QTY
                    $(this.form).off('input change keyup', 'input[data-repeatable-input-name="qty"], input[name*="[qty]"], input[name*="qty"]')
                               .on('input change keyup', 'input[data-repeatable-input-name="qty"], input[name*="[qty]"], input[name*="qty"]', function() {
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
                    var total_price = 0;
                    $(this.form + ' input[data-alt="price_masked"]').each(function() {
                        var price_origin_field = $(this).parent().next();
                        if (!price_origin_field.length) {
                            price_origin_field = $(this).closest('.repeatable-element, .repeatable-group, [data-repeatable-holder], div.row')
                                                      .find('input[type="hidden"][name*="[price]"], input[type="hidden"][name="price"]').last();
                        }
                        var price_origin = Number(price_origin_field.val() || 0);

                        var row = $(this).closest('.repeatable-element, .repeatable-group, [data-repeatable-holder], div.row');
                        var qty = Number(row.find('input[data-repeatable-input-name="qty"], input[name*="[qty]"], input[name*="qty"]').val() || 1);

                        total_price += (price_origin * qty);
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
                            instance.total_price = entry.nominal_exclude_ppn;
                            instance.formManager.populateFormData(entry);
                            instance.logicFormulaNoPO();
                        }, 300);
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

                    // Inisialisasi Repeatable Handlers
                    if (form == '#form-edit' || (form == '#form-create' && hasNotificationId && entry != null)) {
                        countTotalPrice();
                        setTimeout(() => {
                            instance.repeatableManager.initHandlers();
                        }, 100);
                    } else {
                        setTimeout(() => {
                            instance.repeatableManager.initHandlers();
                        }, 100);
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

                }
            }
        });
        SIAOPS.getAttribute('logic_invoice_client').load();
    </script>
@endpush
