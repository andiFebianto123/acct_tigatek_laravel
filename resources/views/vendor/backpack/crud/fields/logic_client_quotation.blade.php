@php
  $field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
  $field['wrapper']['class'] = $field['wrapper']['class'] ?? "hidden";
  $settings = \App\Models\Setting::first();
  $entry_value = $entry ?? $crud?->entry;
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
        if (!$('#select2-modal-zindex-style').length) {
            $('head').append('<style id="select2-modal-zindex-style">.select2-container--open { z-index: 99999 !important; }</style>');
        }

        if (typeof setInputNumber2 == "undefined") {
            function setInputNumber2(selected, value, currency = 'IDR') {
                let nominal = (typeof window.formatCurrency === 'function') 
                    ? window.formatCurrency(value, currency)
                    : value;
                $(selected).val(nominal).trigger('input');
            }
        }

        /* =========================================================================
         * CLASS KHUSUS PENGELOLA REPEATABLE ITEM QUOTATION
         * ========================================================================= */
        if (typeof window.QuotationRepeatableManager === 'undefined') {
            window.QuotationRepeatableManager = class QuotationRepeatableManager {
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
                    var str = val.toString().trim();
                    if (currency === 'USD') {
                        var cleanStr = str.replace(/,/g, '').replace(/[^\d.-]/g, '');
                        var parts = cleanStr.split('.');
                        if (parts.length > 1) {
                            return parts[0] + '.' + parts[1].substring(0, 2);
                        }
                        return parts[0];
                    }
                    return this.getCleanIdrValue(val, isInitial);
                }

                syncRowCurrency($row, currency, symbol) {
                    var $dropdown = $row.find('select.currency-select-dropdown, select[name*="price_currency"], select[name*="unit_price_currency"], select[name*="currency"]');
                    if ($dropdown.length && $dropdown.val() !== currency) {
                        $dropdown.val(currency).trigger('change');
                    }

                    var $maskedInput = $row.find('input[data-alt="price_masked"], input[data-alt="unit_price_masked"]');
                    var $group = $maskedInput.closest('.input-group');
                    if ($group.length) {
                        $group.find('.input-group-text').first().text(symbol);
                    }
                }

                syncAllPrefixes(currency, symbol) {
                    var self = this;
                    $(this.form).find('.repeatable-element, .repeatable-group, [data-repeatable-holder], div.row, .form-group').each(function() {
                        self.syncRowCurrency($(this), currency, symbol);
                    });
                }

                initHandlers() {
                    var self = this;
                    var curr = $(this.form + ' select[name="currency_code"]').val() || $('select[name="currency_code"]').val() || 'IDR';
                    var symbol = (curr === 'USD' ? '$' : 'Rp');

                    self.syncAllPrefixes(curr, symbol);

                    $(this.form).find('input[data-repeatable-input-name="qty"], input[name*="[qty]"], input[name*="qty"]').each(function() {
                        var val = $(this).val();
                        if (val && val.toString().includes('.')) {
                            var num = parseFloat(val);
                            if (!isNaN(num)) {
                                $(this).val(num);
                            }
                        }
                    });

                    $(this.form).off('input change keyup.quotation_repeat', 'input[data-repeatable-input-name="qty"], input[name*="[qty]"], input[name*="qty"]')
                               .on('input change keyup.quotation_repeat', 'input[data-repeatable-input-name="qty"], input[name*="[qty]"], input[name*="qty"]', function() {
                        if (typeof self.onCalculate === 'function') self.onCalculate();
                    });

                    $(this.form + ' input[data-alt="price_masked"], ' + this.form + ' input[data-alt="unit_price_masked"]').each(function() {
                        var $maskedInput = $(this);
                        var $row = $maskedInput.closest('.repeatable-element, .repeatable-group, [data-repeatable-holder], div.row');
                        var $hiddenInput = $row.find('input[type="hidden"][name*="[price]"], input[type="hidden"][name*="[unit_price]"], input[type="hidden"][name="price"]').last();
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
                            var activeCurrency = $(self.form + ' select[name="currency_code"]').val() || $('select[name="currency_code"]').val() || 'IDR';
                            var activeSymbol = (activeCurrency === 'USD' ? '$' : 'Rp');
                            self.syncRowCurrency($row, activeCurrency, activeSymbol);

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
                    var curr = $(this.form + ' select[name="currency_code"]').val() || $('select[name="currency_code"]').val() || 'IDR';
                    var self = this;

                    $(this.form + ' input[data-alt="price_masked"], ' + this.form + ' input[data-alt="unit_price_masked"]').each(function() {
                        var $masked = $(this);
                        var $row = $masked.closest('.repeatable-element, .repeatable-group, [data-repeatable-holder], div.row');
                        var $hidden = $row.find('input[type="hidden"][name*="[price]"], input[type="hidden"][name*="[unit_price]"], input[type="hidden"][name="price"]').last();
                        if (!$hidden.length) {
                            $hidden = $masked.parent().next('input[type="hidden"]');
                        }

                        var cleanStr = self.cleanValue($masked.val() || $hidden.val() || '0', curr, false);
                        var price_origin = parseFloat(cleanStr) || 0;

                        if ($hidden.length) {
                            $hidden.val(cleanStr);
                        }

                        var qty = Number($row.find('input[data-repeatable-input-name="qty"], input[name*="[qty]"], input[name*="qty"]').val() || 1);

                        total_price += (price_origin * qty);
                    });
                    return total_price;
                }

                convertAllItems(previousCurrency, newCurrency, usdRate) {
                    $(this.form + ' input[data-alt="price_masked"], ' + this.form + ' input[data-alt="unit_price_masked"]').each(function() {
                        var $masked = $(this);
                        var $row = $masked.closest('.repeatable-element, .repeatable-group, [data-repeatable-holder], div.row');
                        var $hidden = $row.find('input[type="hidden"][name*="[price]"], input[type="hidden"][name*="[unit_price]"], input[type="hidden"][name="price"]').last();
                        if (!$hidden.length) {
                            $hidden = $masked.parent().next('input[type="hidden"]');
                        }

                        var rawPrice = parseFloat($hidden.val() || 0);

                        var $dropdown = $row.find('select.currency-select-dropdown, select[name*="price_currency"], select[name*="unit_price_currency"]');
                        if ($dropdown.length) {
                            $dropdown.val(newCurrency).trigger('change');
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

        SIAOPS.setAttribute('logic_client_quotation', function(){
            return {
                form_type : "{{ $crud->getActionMethod() }}",
                withoutPo: function(){
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    var status_po = $(form+' select[name="status"]').val();
                    if(status_po == 'TANPA PO'){
                        $(form+' select[name="client_id"]').attr('disabled', true);
                        $(form+' input[name="job_name"]').attr('disabled', true);
                        $(form+' #rap_value_masked').attr('disabled', true);
                        $(form+' #job_value_masked').attr('disabled', true);
                        $(form+' input[name="tax_ppn"]').attr('disabled', true);
                        $(form+' #start_date_end_date').attr('disabled', true);
                        $(form+' select[name="reimburse_type"]').attr('disabled', true);
                        $(form+' input[name="document_path"]').attr('disabled', true);
                    }else{
                        $(form+' select[name="client_id"]').removeAttr('disabled');
                        $(form+' input[name="job_name"]').removeAttr('disabled');
                        $(form+' #rap_value_masked').removeAttr('disabled');
                        $(form+' #job_value_masked').removeAttr('disabled');
                        $(form+' input[name="tax_ppn"]').removeAttr('disabled');
                        $(form+' #start_date_end_date').removeAttr('disabled');
                        $(form+' select[name="reimburse_type"]').removeAttr('disabled');
                        $(form+' input[name="document_path"]').removeAttr('disabled');
                    }
                },
                logicFormula: function(){
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    var curr = $(form+' select[name="currency_code"]').val() || $('select[name="currency_code"]').val() || 'IDR';
                    var symbol = (curr === 'USD' ? '$' : 'Rp');

                    var $rapCurrencySelect = $(form+' select[name="rap_value_currency"], ' + form + ' select[id="rap_value_currency"]');
                    var $jobCurrencySelect = $(form+' select[name="job_value_currency"], ' + form + ' select[id="job_value_currency"]');

                    if($rapCurrencySelect.length && $rapCurrencySelect.val() !== curr){
                        $rapCurrencySelect.val(curr).trigger('change');
                    }
                    if($jobCurrencySelect.length && $jobCurrencySelect.val() !== curr){
                        $jobCurrencySelect.val(curr).trigger('change');
                    }

                    var $ppnField = $(form+' input[name="job_value_include_ppn"], ' + form + ' #job_value_include_ppn_masked');
                    var $ppnGroup = $ppnField.closest('.input-group');
                    if($ppnGroup.length){
                        var $prefixSpan = $ppnGroup.find('.input-group-text').first();
                        if($prefixSpan.length){
                            $prefixSpan.text(symbol);
                        }
                    }

                    if (this.repeatableManager) {
                        this.repeatableManager.syncAllPrefixes(curr, symbol);
                    }

                    var $jobValMasked = $(form+' #job_value_masked');
                    var $jobValHidden = $(form+' #job_value');
                    
                    var rawJobVal = '';
                    if ($jobValMasked.length && $jobValMasked.val()) {
                        rawJobVal = $jobValMasked.val();
                    } else if ($jobValHidden.length && $jobValHidden.val()) {
                        rawJobVal = $jobValHidden.val();
                    }

                    var nilai_pekerjaan = 0;
                    if (curr === 'USD') {
                        var cleanUsd = rawJobVal.toString().replace(/,/g, '').replace(/[^\d.-]/g, '');
                        nilai_pekerjaan = parseFloat(cleanUsd) || 0;
                    } else {
                        var cleanIdr = rawJobVal.toString().replace(/\./g, '').replace(/[^\d-]/g, '');
                        nilai_pekerjaan = parseFloat(cleanIdr) || 0;
                    }

                    if ($jobValHidden.length) {
                        $jobValHidden.val(curr === 'USD' ? (nilai_pekerjaan > 0 ? nilai_pekerjaan.toFixed(2) : 0) : nilai_pekerjaan);
                    }

                    var ppn = parseFloat($(form+' input[name="tax_ppn"]').val() || 0);

                    var nilai_ppn = (ppn == 0) ? 0 : (nilai_pekerjaan * (ppn / 100));
                    var total = nilai_pekerjaan + nilai_ppn;

                    var formattedTotal = (typeof window.formatCurrency === 'function')
                        ? window.formatCurrency(total, curr)
                        : total;

                    var $ppnHiddenInput = $(form+' input[type="hidden"][name="job_value_include_ppn"]');
                    if ($ppnHiddenInput.length) {
                        $ppnHiddenInput.val(curr === 'USD' ? total.toFixed(2) : total);
                    }
                    $(form+' input[name="job_value_include_ppn"], ' + form + ' #job_value_include_ppn_masked').val(formattedTotal).trigger('change');
                },
                setupWithoutPoCount: function(form){
                    $.ajax({
                        url: "{{ url($crud->route.'/total-without-po') }}",
                        type: 'GET',
                        success: function(response){
                            $(form+' input[name="work_code"]').val(`UMUM-${response.count}`);
                        },
                        error: function(error){
                            console.error(error);
                        }
                    })
                },
                load: function(){
                    var instance = this;
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    var settings = {!! json_encode($settings) !!};
                    var entry = {!! json_encode($entry_value) !!};

                    function populateDeviceStockSelect2() {
                        var details = (entry && (entry.client_quotation_details_edit || entry.client_quotation_details || entry.details)) 
                            ? (entry.client_quotation_details_edit || entry.client_quotation_details || entry.details) 
                            : null;

                        if (!details || !Array.isArray(details)) return;

                        var $rows = $(form).find('.repeatable-element');
                        if (!$rows.length) {
                            $rows = $('.repeatable-element');
                        }

                        $rows.each(function(index, el) {
                            var itemData = details[index];
                            if (!itemData) return;

                            var $select = $(el).find('select[data-init-function="bpFieldInitSelect2AjaxDeviceStock"], select[data-repeatable-input-name="device_stock_id"], select[data-repeatable-input-name="reference_id"], select[name*="device_stock"], select[name*="reference"]').not('.currency-select-dropdown');
                            if (!$select.length) {
                                $select = $(el).find('select').not('.currency-select-dropdown').first();
                            }
                            if ($select.length) {
                                var refId = itemData.device_stock_id || itemData.reference_id || itemData.id;
                                var refName = (itemData.device_stock && itemData.device_stock.name) 
                                    ? itemData.device_stock.name 
                                    : (itemData.item_name || itemData.name);

                                if (refId || refName) {
                                    var targetVal = refId || refName;
                                    var targetText = refName || refId;

                                    var newOption = new Option(targetText, targetVal, true, true);
                                    $select.html(newOption).val(targetVal).trigger('change');

                                    if ($select.hasClass('select2-hidden-accessible')) {
                                        $select.trigger('change.select2');
                                    }
                                }
                            }
                        });
                    }

                    this.repeatableManager = new QuotationRepeatableManager(form, function() {
                        var totalItems = instance.repeatableManager.calculateTotalItems();
                        var $jobValueHidden = $(form + ' #job_value');
                        var $jobValueMasked = $(form + ' #job_value_masked');
                        var activeCurr = $(form + ' select[name="currency_code"]').val() || $('select[name="currency_code"]').val() || 'IDR';

                        var $jobCurrencySelect = $(form + ' select[name="job_value_currency"], ' + form + ' select[id="job_value_currency"]');
                        if ($jobCurrencySelect.length && $jobCurrencySelect.val() !== activeCurr) {
                            $jobCurrencySelect.val(activeCurr).trigger('change');
                        }

                        if ($jobValueHidden.length) {
                            $jobValueHidden.val(activeCurr === 'USD' ? totalItems.toFixed(2) : totalItems);
                        }
                        if ($jobValueMasked.length && typeof window.formatCurrency === 'function') {
                            $jobValueMasked.val(window.formatCurrency(totalItems, activeCurr));
                        }
                        instance.logicFormula();
                    });

                    // Auto-sync currency dropdown pada input nominal utama saat difokuskan atau diketik
                    $(form).on('focus keydown input', '#job_value_masked, #rap_value_masked', function() {
                        var curr = $(form + ' select[name="currency_code"]').val() || $('select[name="currency_code"]').val() || 'IDR';
                        var $jobCurr = $(form + ' select[name="job_value_currency"], ' + form + ' select[id="job_value_currency"]');
                        var $rapCurr = $(form + ' select[name="rap_value_currency"], ' + form + ' select[id="rap_value_currency"]');
                        if ($jobCurr.length && $jobCurr.val() !== curr) {
                            $jobCurr.val(curr).trigger('change');
                        }
                        if ($rapCurr.length && $rapCurr.val() !== curr) {
                            $rapCurr.val(curr).trigger('change');
                        }
                    });

                    // Handler modal open
                    $(document).on('show.bs.modal shown.bs.modal', function(e) {
                        var $modal = $(e.target);
                        if ($modal.find(form).length || $modal.find('.repeatable-element').length) {
                            setTimeout(function() {
                                var activeCurr = $(form + ' select[name="currency_code"]').val() || $('select[name="currency_code"]').val() || 'IDR';
                                var activeSymbol = (activeCurr === 'USD' ? '$' : 'Rp');
                                if (instance.repeatableManager) {
                                    instance.repeatableManager.syncAllPrefixes(activeCurr, activeSymbol);
                                    instance.repeatableManager.initHandlers();
                                }
                                populateDeviceStockSelect2();
                                instance.logicFormula();
                            }, 150);
                        }
                    });

                    // Handler auto-fill harga saat item DeviceStock dipilih dari Select2
                    $(form).off('select2:select.quotation_device')
                           .on('select2:select.quotation_device', 'select[name*="[device_stock_id]"], select[name="device_stock_id"]', function(e) {
                        var selected = e.params ? e.params.data : null;
                        if (selected && selected.sell_price !== undefined) {
                            var $row = $(this).closest('.repeatable-element, .repeatable-group, [data-repeatable-holder], div.row');
                            var $priceHidden = $row.find('input[type="hidden"][name*="[price]"], input[type="hidden"][name="price"]').last();
                            var $priceMasked = $row.find('input[data-alt="price_masked"]');
                            var activeCurr = $(form + ' select[name="currency_code"]').val() || $('select[name="currency_code"]').val() || 'IDR';
                            var activeSymbol = (activeCurr === 'USD' ? '$' : 'Rp');

                            instance.repeatableManager.syncRowCurrency($row, activeCurr, activeSymbol);

                            var priceVal = parseFloat(selected.sell_price) || 0;
                            var cleanPrice = instance.repeatableManager.cleanValue(priceVal, activeCurr, true);
                            if ($priceHidden.length) $priceHidden.val(cleanPrice);
                            if ($priceMasked.length && typeof window.formatCurrency === 'function') {
                                $priceMasked.val(window.formatCurrency(cleanPrice, activeCurr)).trigger('change');
                            }
                            if (typeof instance.repeatableManager.onCalculate === 'function') {
                                instance.repeatableManager.onCalculate();
                            }
                        }
                    });

                    if(entry){
                        if(entry.status == null || entry.status == 'ADA PO'){
                            $(form+' input[name="po_number"]').removeAttr('disabled');
                        }else{
                            $(form+' input[name="po_number"]').attr('disabled', true);
                        }
                        instance.withoutPo();
                        $(form+' select[name="status"]').on('select2:select', function (e) {
                            var data = $(this).val();
                            instance.withoutPo();
                            if(data == 'TANPA PO'){
                                $(form+' input[name="po_number"]').attr('disabled', true);
                            }else{
                                $(form+' input[name="po_number"]').removeAttr('disabled');
                            }
                        });
                        setTimeout(() => {
                            instance.logicFormula();
                            instance.repeatableManager.initHandlers();
                            populateDeviceStockSelect2();
                        }, 200);
                    }else{
                        $(form+' select[name="status"]').on('select2:select', function (e) {
                            var data = $(this).val();
                            instance.withoutPo();
                            if(data == 'TANPA PO'){
                                instance.setupWithoutPoCount(form);
                                $(form+' input[name="po_number"]').attr('disabled', true);
                            }else{
                                $(form+' input[name="po_number"]').removeAttr('disabled');
                                $(form+' input[name="work_code"]').val(settings.work_code_prefix);
                            }
                        });
                    }

                    $(form + ' .add-repeatable-element-button').on('click', function() {
                        setTimeout(function() {
                            var activeCurr = $(form + ' select[name="currency_code"]').val() || $('select[name="currency_code"]').val() || 'IDR';
                            var activeSymbol = (activeCurr === 'USD' ? '$' : 'Rp');
                            if (instance.repeatableManager) {
                                instance.repeatableManager.syncAllPrefixes(activeCurr, activeSymbol);
                                instance.repeatableManager.initHandlers();
                                instance.repeatableManager.onCalculate();
                            }
                        }, 100);
                    });

                    // Direct event listener saat pengguna mengubah Nilai Pekerjaan secara manual
                    $(form).off('keyup.quotation_job_val input.quotation_job_val change.quotation_job_val', '#job_value_masked')
                           .on('keyup.quotation_job_val input.quotation_job_val change.quotation_job_val', '#job_value_masked', function() {
                        instance.logicFormula();
                    });

                    // Direct event listener saat pengguna mengubah % PPN
                    $(form).off('keyup.quotation_tax input.quotation_tax change.quotation_tax', 'input[name="tax_ppn"]')
                           .on('keyup.quotation_tax input.quotation_tax change.quotation_tax', 'input[name="tax_ppn"]', function() {
                        instance.logicFormula();
                    });

                    // Event listener saat baris item dihapus
                    $(document).off('click.quotation_delete', '.delete-element')
                               .on('click.quotation_delete', '.delete-element', function() {
                        setTimeout(function() {
                            if (instance.repeatableManager && typeof instance.repeatableManager.onCalculate === 'function') {
                                instance.repeatableManager.onCalculate();
                            }
                        }, 100);
                    });

                    var previousCurrency = $(form+' select[name="currency_code"]').val() || $('select[name="currency_code"]').val() || 'IDR';

                    function updateFieldValue(fieldName, rawVal, currency) {
                        var $hiddenField = $(form+' #' + fieldName);
                        var $maskedField = $(form+' #' + fieldName + '_masked');

                        if (!$hiddenField.length) return;

                        var formatted = (typeof window.formatCurrency === 'function')
                            ? window.formatCurrency(rawVal, currency)
                            : rawVal;

                        $hiddenField.val(rawVal);
                        $maskedField.val(formatted);
                    }

                    $(form+' select[name="currency_code"]').on('change select2:select', function(){
                        var newCurrency = $(this).val() || 'IDR';
                        var activeSymbol = (newCurrency === 'USD' ? '$' : 'Rp');

                        if (instance.repeatableManager) {
                            instance.repeatableManager.syncAllPrefixes(newCurrency, activeSymbol);
                        }

                        var $jobCurr = $(form + ' select[name="job_value_currency"], ' + form + ' select[id="job_value_currency"]');
                        var $rapCurr = $(form + ' select[name="rap_value_currency"], ' + form + ' select[id="rap_value_currency"]');
                        if ($jobCurr.length) $jobCurr.val(newCurrency).trigger('change');
                        if ($rapCurr.length) $rapCurr.val(newCurrency).trigger('change');

                        if (newCurrency !== previousCurrency) {
                            var usdRate = window.usdRate || 16000;
                            var rawRap = getInputNumber(form+' #rap_value');
                            var rawJob = getInputNumber(form+' #job_value');

                            if (rawRap > 0 && typeof window.convertCurrency === 'function') {
                                var convertedRap = window.convertCurrency(rawRap, previousCurrency, newCurrency, usdRate);
                                updateFieldValue('rap_value', convertedRap, newCurrency);
                            }

                            if (instance.repeatableManager) {
                                instance.repeatableManager.convertAllItems(previousCurrency, newCurrency, usdRate);
                                var newTotalItems = instance.repeatableManager.calculateTotalItems();
                                updateFieldValue('job_value', newTotalItems, newCurrency);
                            } else if (rawJob > 0 && typeof window.convertCurrency === 'function') {
                                var convertedJob = window.convertCurrency(rawJob, previousCurrency, newCurrency, usdRate);
                                updateFieldValue('job_value', convertedJob, newCurrency);
                            }

                            previousCurrency = newCurrency;
                        }

                        instance.logicFormula();
                    });

                    setTimeout(() => {
                        previousCurrency = $(form+' select[name="currency_code"]').val() || $('select[name="currency_code"]').val() || 'IDR';
                        instance.logicFormula();
                        instance.repeatableManager.initHandlers();
                        populateDeviceStockSelect2();
                    }, 100);
                    setTimeout(() => {
                        populateDeviceStockSelect2();
                    }, 300);
                }
            }
        });
        SIAOPS.getAttribute('logic_client_quotation').load();
    </script>
@endpush
