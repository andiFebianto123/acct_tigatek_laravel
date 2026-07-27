@php
  $field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
  $field['wrapper']['class'] = $field['wrapper']['class'] ?? "hidden";
  $entry_value = $crud?->entry;
@endphp

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
        if (typeof setInputNumber2 == "undefined") {
            function setInputNumber2(selected, value, currency = 'IDR') {
                let nominal = (typeof window.formatCurrency === 'function') 
                    ? window.formatCurrency(value, currency)
                    : value;
                $(selected).val(nominal).trigger('input');
            }
        }

        SIAOPS.setAttribute('logic_purchase_order', function(){
            return {
                form_type : "{{ $crud->getActionMethod() }}",

                toggleFormFields: function(){
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    var po_type = $(form + ' select[name="po_type"]').val();

                    var workCodeWrapper = $(form + ' input[name="work_code"]').closest('.form-group');
                    var workCodeInput = $(form + ' input[name="work_code"]');

                    var jobNameWrapper = $(form + ' input[name="job_name"]').closest('.form-group');
                    var jobNameLabel = jobNameWrapper.find('label');
                    var jobNameInput = $(form + ' input[name="job_name"]');

                    var jobDescWrapper = $(form + ' [name="job_description"]').closest('.form-group');
                    var jobDescInput = $(form + ' [name="job_description"]');

                    var itemsWrapper = $(form + ' [name="purchase_order_details"], ' + form + ' [name="purchase_order_details_edit"]').closest('.form-group');
                    if (!itemsWrapper.length) {
                        itemsWrapper = $(form + ' [data-repeatable-holder]').closest('.form-group');
                    }

                    if (po_type === 'supplier') {
                        workCodeWrapper.hide();
                        workCodeInput.attr('disabled', true);

                        jobNameLabel.text("{{ trans('backpack::crud.po.field.job_name.label_supplier') }}");
                        jobNameInput.attr('placeholder', "{{ trans('backpack::crud.po.field.job_name.placeholder_supplier') }}");

                        jobDescWrapper.hide();
                        jobDescInput.attr('disabled', true);

                        itemsWrapper.show();
                    } else {
                        workCodeWrapper.show();
                        workCodeInput.removeAttr('disabled');

                        jobNameLabel.text("{{ trans('backpack::crud.po.field.job_name.label') }}");
                        jobNameInput.attr('placeholder', "{{ trans('backpack::crud.po.field.job_name.placeholder') }}");

                        jobDescWrapper.show();
                        jobDescInput.removeAttr('disabled');

                        itemsWrapper.hide();
                    }
                },

                calculateTotalWithTax: function() {
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    var curr = $(form + ' select[name="currency_code"]').val() || 'IDR';

                    // Update currency dropdown di job_value_currency agar mengikutsertakan masking IDR/USD
                    var $jobCurrencySelect = $(form + ' select[name="job_value_currency"]');
                    if ($jobCurrencySelect.length && $jobCurrencySelect.val() !== curr) {
                        $jobCurrencySelect.val(curr).trigger('change');
                    }

                    // Synchronize dropdown mask_currency secara umum
                    $(form + ' select.currency-select-dropdown').val(curr);

                    // Synchronize prefix text pada input group total_value_with_tax
                    var $totalField = $(form + ' input[name="total_value_with_tax"]');
                    var $totalGroup = $totalField.closest('.input-group');
                    if ($totalGroup.length) {
                        var $prefixSpan = $totalGroup.find('.input-group-text').first();
                        if ($prefixSpan.length) {
                            $prefixSpan.text(curr === 'USD' ? '$' : 'Rp');
                        }
                    }

                    // Menggunakan helper getInputNumber agar presisi membaca float desimal murni dari input
                    var rawJobValue = (typeof getInputNumber === 'function')
                        ? getInputNumber(form + ' #job_value')
                        : parseFloat($(form + ' #job_value').val() || 0);

                    var taxPpn = (typeof getInputNumber === 'function')
                        ? getInputNumber(form + ' input[name="tax_ppn"]')
                        : parseFloat($(form + ' input[name="tax_ppn"]').val() || 0);

                    var nilai_ppn = (taxPpn == 0) ? 0 : (rawJobValue * (taxPpn / 100));
                    var totalWithTax = rawJobValue + nilai_ppn;

                    setInputNumber2(form + ' input[name="total_value_with_tax"]', totalWithTax, curr);
                },

                load: function(){
                    var instance = this;
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';

                    // Initial state toggle
                    instance.toggleFormFields();

                    // Initial calculation & sync
                    setTimeout(() => {
                        var initialCurr = $(form + ' select[name="currency_code"]').val() || 'IDR';
                        $(form + ' select.currency-select-dropdown').val(initialCurr);

                        var rawJobVal = $(form + ' #job_value').val() || '';
                        if (rawJobVal && typeof window.formatCurrency === 'function') {
                            $(form + ' #job_value_masked').val(window.formatCurrency(rawJobVal, initialCurr));
                        }

                        instance.calculateTotalWithTax();
                    }, 100);

                    // Event listener pergantian po_type
                    $(form + ' select[name="po_type"]').on('change select2:select', function() {
                        instance.toggleFormFields();
                    });

                    // Melacak mata uang sebelumnya untuk konversi otomatis saat user mengubah dropdown utama
                    var previousCurrency = $(form + ' select[name="currency_code"]').val() || 'IDR';

                    $(form + ' select[name="currency_code"]').on('change select2:select', function() {
                        var newCurrency = $(this).val() || 'IDR';

                        if (newCurrency !== previousCurrency) {
                            var usdRate = window.usdRate || 16000;
                            var rawJobVal = (typeof getInputNumber === 'function')
                                ? getInputNumber(form + ' #job_value')
                                : parseFloat($(form + ' #job_value').val() || 0);

                            if (rawJobVal > 0 && typeof window.convertCurrency === 'function') {
                                var convertedJobVal = window.convertCurrency(rawJobVal, previousCurrency, newCurrency, usdRate);

                                $(form + ' #job_value').val(convertedJobVal);
                                if (typeof window.formatCurrency === 'function') {
                                    $(form + ' #job_value_masked').val(window.formatCurrency(convertedJobVal, newCurrency));
                                }
                            } else {
                                var currentRaw = $(form + ' #job_value').val() || '';
                                if (typeof window.formatCurrency === 'function') {
                                    $(form + ' #job_value_masked').val(window.formatCurrency(currentRaw, newCurrency));
                                }
                            }

                            // Sync dropdown tampilan pada mask_currency
                            $(form + ' select.currency-select-dropdown').val(newCurrency);

                            previousCurrency = newCurrency;
                        }

                        instance.calculateTotalWithTax();
                    });

                    // Event listener pengetikan job_value & tax_ppn
                    $(form + ' #job_value_masked').on('keyup input change', function() {
                        instance.calculateTotalWithTax();
                    });

                    $(form + ' input[name="tax_ppn"]').on('keyup input change', function() {
                        instance.calculateTotalWithTax();
                    });

                    var entry = @json($entry_value ?? null);

                    function populateDeviceStockSelect2() {
                        var details = (entry && (entry.purchase_order_details_edit || entry.purchase_order_details)) ? (entry.purchase_order_details_edit || entry.purchase_order_details) : null;
                        if (details && Array.isArray(details)) {
                            $(form + ' [data-repeatable-holder]').children().each(function(index, el) {
                                var itemData = details[index];
                                if (itemData) {
                                    var $select = $(el).find('select[data-repeatable-input-name="reference_id"], select[name*="[reference_id]"]');
                                    if ($select.length) {
                                        var refId = itemData.reference_id;
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

                                        // Preservasi dan format harga dari DB (itemData.price) tanpa tertimpa harga master
                                        if (itemData.price !== undefined && itemData.price !== null) {
                                            var $priceMasked = $(el).find('input[data-alt="price_masked"]');
                                            var $priceHidden = $(el).find('input[type="hidden"][name*="[price]"], input[type="hidden"][name="price"]').last();
                                            if (!$priceHidden.length) {
                                                $priceHidden = $priceMasked.parent().next('input[type="hidden"]');
                                            }
                                            var activeCurr = $(form + ' select[name="currency_code"]').val() || 'IDR';
                                            var rawPriceFromDb = parseFloat(itemData.price || 0);
                                            if (activeCurr === 'IDR') {
                                                rawPriceFromDb = Math.round(rawPriceFromDb);
                                            }
                                            $priceHidden.val(rawPriceFromDb);
                                            if ($priceMasked.length && typeof window.formatCurrency === 'function') {
                                                $priceMasked.val(window.formatCurrency(rawPriceFromDb, activeCurr));
                                            }
                                        }
                                    }
                                }
                            });
                        }
                    }

                    $(form).off('select2:select.device_stock', 'select[data-repeatable-input-name="reference_id"], select[name*="[reference_id]"]')
                           .on('select2:select.device_stock', 'select[data-repeatable-input-name="reference_id"], select[name*="[reference_id]"]', function(e) {
                        var data = e.params ? e.params.data : null;
                        if (data) {
                            var $row = $(this).closest('.repeatable-element, .repeatable-group, [data-repeatable-holder], div.row, tr');
                            var $priceMasked = $row.find('input[data-alt="price_masked"]');
                            var $priceHidden = $row.find('input[type="hidden"][name*="[price]"], input[type="hidden"][name="price"]').last();
                            if (!$priceHidden.length) {
                                $priceHidden = $priceMasked.parent().next('input[type="hidden"]');
                            }
                            var activeCurr = $(form + ' select[name="currency_code"]').val() || 'IDR';
                            
                            // Gunakan buy_price (Harga Beli) dari Master Device Stock untuk PO Supplier
                            var priceVal = (data.buy_price !== undefined && data.buy_price !== null) 
                                ? parseFloat(data.buy_price) 
                                : (data.sell_price !== undefined ? parseFloat(data.sell_price) : 0);

                            if (activeCurr === 'IDR') {
                                priceVal = Math.round(priceVal);
                            }

                            if (priceVal > 0) {
                                $priceHidden.val(priceVal);
                                if (typeof window.formatCurrency === 'function') {
                                    $priceMasked.val(window.formatCurrency(priceVal, activeCurr));
                                } else {
                                    $priceMasked.val(priceVal);
                                }
                            }
                        }
                    });

                    setTimeout(() => {
                        populateDeviceStockSelect2();
                    }, 150);
                }
            }
        });
        SIAOPS.getAttribute('logic_purchase_order').load();
    </script>
@endpush
