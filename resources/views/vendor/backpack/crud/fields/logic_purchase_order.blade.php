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

                getForm: function() {
                    var formContainer = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    var $form = $(formContainer);
                    if (!$form.length || !$form.is(':visible')) {
                        $form = $('.modal.show form, .modal form, form').filter(':visible').first();
                    }
                    if (!$form.length) {
                        $form = $('form').first();
                    }
                    return $form;
                },

                toggleFormFields: function(){
                    var $form = this.getForm();
                    var po_type = $form.find('select[name="po_type"]').val() || $('select[name="po_type"]').val();

                    var workCodeInput = $form.find('input[name="work_code"]');
                    var workCodeWrapper = workCodeInput.closest('.form-group');

                    var jobNameInput = $form.find('input[name="job_name"]');
                    var jobNameWrapper = jobNameInput.closest('.form-group');
                    var jobNameLabel = jobNameWrapper.find('label');

                    var jobDescInput = $form.find('[name="job_description"]');
                    var jobDescWrapper = jobDescInput.closest('.form-group');

                    var jobValueMasked = $form.find('#job_value_masked, input[data-alt="job_value_masked"]');
                    var jobValueHidden = $form.find('#job_value, input[name="job_value"]');
                    var jobValueWrapper = jobValueMasked.closest('.form-group');
                    if (!jobValueWrapper.length) {
                        jobValueWrapper = jobValueHidden.closest('.form-group');
                    }
                    var jobValueLabel = jobValueWrapper.find('label');

                    var itemsWrapper = $form.find('[name="purchase_order_details"], [name="purchase_order_details_edit"]').closest('.form-group');
                    if (!itemsWrapper.length) {
                        itemsWrapper = $form.find('[data-repeatable-holder]').closest('.form-group');
                    }

                    if (po_type === 'supplier') {
                        workCodeWrapper.hide();
                        workCodeInput.prop('disabled', true);

                        // Sembunyikan field Nama Barang / Pekerjaan untuk PO Supplier
                        jobNameWrapper.hide();
                        jobNameInput.prop('disabled', true);

                        jobDescWrapper.hide();
                        jobDescInput.prop('disabled', true);

                        // Ganti label Nilai Pekerjaan -> Grand Total dan buat Readonly
                        if (jobValueLabel.length) {
                            jobValueLabel.text("Grand Total");
                        }
                        jobValueMasked.prop('readonly', true).css({
                            'background-color': '#e9ecef',
                            'pointer-events': 'none'
                        });

                        itemsWrapper.show();
                        this.calculateItemsTotal();
                    } else {
                        workCodeWrapper.show();
                        workCodeInput.prop('disabled', false);

                        // Tampilkan kembali field Nama Barang / Pekerjaan untuk PO Subkon
                        jobNameWrapper.show();
                        jobNameInput.prop('disabled', false);
                        if (jobNameLabel.length) {
                            jobNameLabel.text("{{ trans('backpack::crud.po.field.job_name.label') }}");
                        }
                        jobNameInput.attr('placeholder', "{{ trans('backpack::crud.po.field.job_name.placeholder') }}");

                        jobDescWrapper.show();
                        jobDescInput.prop('disabled', false);

                        if (jobValueLabel.length) {
                            jobValueLabel.text("{{ trans('backpack::crud.po.column.job_value') }}");
                        }
                        jobValueMasked.prop('readonly', false).css({
                            'background-color': '',
                            'pointer-events': ''
                        });

                        itemsWrapper.hide();
                        this.calculateTotalWithTax();
                    }
                },

                calculateItemsTotal: function() {
                    var $form = this.getForm();
                    var po_type = $form.find('select[name="po_type"]').val() || $('select[name="po_type"]').val();

                    if (po_type === 'supplier') {
                        var totalSum = 0;
                        var activeCurr = $form.find('select[name="currency_code"]').val() || $('select[name="currency_code"]').val() || 'IDR';

                        $form.find('[data-repeatable-holder]').children().each(function() {
                            var $row = $(this);
                            var qtyInput = $row.find('input[data-repeatable-input-name="qty"], input[name*="[qty]"]').val();
                            var qty = parseFloat(qtyInput || 0);

                            var $priceMasked = $row.find('input[data-alt="price_masked"]');
                            var $priceHidden = $row.find('input[type="hidden"][name*="[price]"], input[type="hidden"][name="price"]').last();
                            if (!$priceHidden.length) {
                                $priceHidden = $priceMasked.parent().next('input[type="hidden"]');
                            }
                            var price = parseFloat($priceHidden.val() || 0);
                            if (!price && $priceMasked.length) {
                                var str = $priceMasked.val() || '';
                                if (activeCurr === 'USD') {
                                    price = parseFloat(str.replace(/,/g, '')) || 0;
                                } else {
                                    price = parseFloat(str.replace(/\./g, '').replace(/,/g, '.')) || 0;
                                }
                            }
                            totalSum += (qty * (price || 0));
                        });

                        if (activeCurr === 'IDR') {
                            totalSum = Math.round(totalSum);
                        }

                        var $jobValueHidden = $form.find('#job_value, input[name="job_value"]');
                        var $jobValueMasked = $form.find('#job_value_masked, input[data-alt="job_value_masked"]');

                        $jobValueHidden.val(totalSum);
                        if (typeof window.formatCurrency === 'function') {
                            $jobValueMasked.val(window.formatCurrency(totalSum, activeCurr));
                        } else {
                            $jobValueMasked.val(totalSum);
                        }

                        this.calculateTotalWithTax();
                    }
                },

                calculateTotalWithTax: function() {
                    var $form = this.getForm();
                    var curr = $form.find('select[name="currency_code"]').val() || $('select[name="currency_code"]').val() || 'IDR';

                    // Update currency dropdown di job_value_currency agar mengikutsertakan masking IDR/USD
                    var $jobCurrencySelect = $form.find('select[name="job_value_currency"]');
                    if ($jobCurrencySelect.length && $jobCurrencySelect.val() !== curr) {
                        $jobCurrencySelect.val(curr).trigger('change');
                    }

                    // Synchronize dropdown mask_currency secara umum
                    $form.find('select.currency-select-dropdown').val(curr);

                    // Synchronize prefix text pada input group total_value_with_tax
                    var $totalField = $form.find('input[name="total_value_with_tax"]');
                    var $totalGroup = $totalField.closest('.input-group');
                    if ($totalGroup.length) {
                        var $prefixSpan = $totalGroup.find('.input-group-text').first();
                        if ($prefixSpan.length) {
                            $prefixSpan.text(curr === 'USD' ? '$' : 'Rp');
                        }
                    }

                    var $jobValInput = $form.find('#job_value, input[name="job_value"]');
                    var rawJobValue = (typeof getInputNumber === 'function' && $jobValInput.length)
                        ? getInputNumber($jobValInput[0])
                        : parseFloat($jobValInput.val() || 0);

                    var $taxInput = $form.find('input[name="tax_ppn"]');
                    var taxPpn = (typeof getInputNumber === 'function' && $taxInput.length)
                        ? getInputNumber($taxInput[0])
                        : parseFloat($taxInput.val() || 0);

                    var nilai_ppn = (taxPpn == 0) ? 0 : (rawJobValue * (taxPpn / 100));
                    var totalWithTax = rawJobValue + nilai_ppn;

                    setInputNumber2($totalField, totalWithTax, curr);
                },

                load: function(){
                    var instance = this;

                    // Initial state toggle & calculation
                    setTimeout(() => {
                        var $form = instance.getForm();
                        instance.toggleFormFields();

                        var initialCurr = $form.find('select[name="currency_code"]').val() || 'IDR';
                        $form.find('select.currency-select-dropdown').val(initialCurr);

                        var rawJobVal = $form.find('#job_value, input[name="job_value"]').val() || '';
                        if (rawJobVal && typeof window.formatCurrency === 'function') {
                            $form.find('#job_value_masked, input[data-alt="job_value_masked"]').val(window.formatCurrency(rawJobVal, initialCurr));
                        }

                        instance.calculateTotalWithTax();
                    }, 100);

                    // Event listener pergantian po_type
                    $(document).off('change.po_type select2:select.po_type', 'select[name="po_type"]')
                               .on('change.po_type select2:select.po_type', 'select[name="po_type"]', function() {
                        instance.toggleFormFields();
                    });

                    // Event listener perubahan pada rincian item repeatable
                    $(document).off('keyup.po_items input.po_items change.po_items', '[data-repeatable-holder] input')
                               .on('keyup.po_items input.po_items change.po_items', '[data-repeatable-holder] input', function() {
                        instance.calculateItemsTotal();
                    });

                    $(document).off('click.po_items_btn', '[data-button-type="add"], .delete-element, .delete-button, button.close')
                               .on('click.po_items_btn', '[data-button-type="add"], .delete-element, .delete-button, button.close', function() {
                        setTimeout(function() {
                            instance.calculateItemsTotal();
                        }, 120);
                    });

                    // Melacak mata uang sebelumnya untuk konversi otomatis saat user mengubah dropdown utama
                    var previousCurrency = $('select[name="currency_code"]').val() || 'IDR';

                    $(document).off('change.po_curr select2:select.po_curr', 'select[name="currency_code"]')
                               .on('change.po_curr select2:select.po_curr', 'select[name="currency_code"]', function() {
                        var $form = instance.getForm();
                        var newCurrency = $(this).val() || 'IDR';

                        if (newCurrency !== previousCurrency) {
                            var usdRate = window.usdRate || 16000;
                            var $jobValInput = $form.find('#job_value, input[name="job_value"]');
                            var rawJobVal = (typeof getInputNumber === 'function' && $jobValInput.length)
                                ? getInputNumber($jobValInput[0])
                                : parseFloat($jobValInput.val() || 0);

                            if (rawJobVal > 0 && typeof window.convertCurrency === 'function') {
                                var convertedJobVal = window.convertCurrency(rawJobVal, previousCurrency, newCurrency, usdRate);

                                $jobValInput.val(convertedJobVal);
                                if (typeof window.formatCurrency === 'function') {
                                    $form.find('#job_value_masked, input[data-alt="job_value_masked"]').val(window.formatCurrency(convertedJobVal, newCurrency));
                                }
                            } else {
                                var currentRaw = $jobValInput.val() || '';
                                if (typeof window.formatCurrency === 'function') {
                                    $form.find('#job_value_masked, input[data-alt="job_value_masked"]').val(window.formatCurrency(currentRaw, newCurrency));
                                }
                            }

                            // Sync dropdown tampilan pada mask_currency
                            $form.find('select.currency-select-dropdown').val(newCurrency);

                            previousCurrency = newCurrency;
                        }

                        instance.calculateTotalWithTax();
                    });

                    // Event listener pengetikan job_value & tax_ppn
                    $(document).off('keyup.po_val input.po_val change.po_val', '#job_value_masked, input[data-alt="job_value_masked"], input[name="tax_ppn"]')
                               .on('keyup.po_val input.po_val change.po_val', '#job_value_masked, input[data-alt="job_value_masked"], input[name="tax_ppn"]', function() {
                        instance.calculateTotalWithTax();
                    });

                    var entry = @json($entry_value ?? null);

                    function populateDeviceStockSelect2() {
                        var $form = instance.getForm();
                        var details = (entry && (entry.purchase_order_details_edit || entry.purchase_order_details)) ? (entry.purchase_order_details_edit || entry.purchase_order_details) : null;
                        if (details && Array.isArray(details)) {
                            $form.find('[data-repeatable-holder]').children().each(function(index, el) {
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
                                            var activeCurr = $form.find('select[name="currency_code"]').val() || 'IDR';
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

                    $(document).off('select2:select.device_stock', 'select[data-repeatable-input-name="reference_id"], select[name*="[reference_id]"]')
                               .on('select2:select.device_stock', 'select[data-repeatable-input-name="reference_id"], select[name*="[reference_id]"]', function(e) {
                        var $form = instance.getForm();
                        var data = e.params ? e.params.data : null;
                        if (data) {
                            var $row = $(this).closest('.repeatable-element, .repeatable-group, [data-repeatable-holder], div.row, tr');
                            var $priceMasked = $row.find('input[data-alt="price_masked"]');
                            var $priceHidden = $row.find('input[type="hidden"][name*="[price]"], input[type="hidden"][name="price"]').last();
                            if (!$priceHidden.length) {
                                $priceHidden = $priceMasked.parent().next('input[type="hidden"]');
                            }
                            var activeCurr = $form.find('select[name="currency_code"]').val() || 'IDR';
                            
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

                            setTimeout(function() {
                                instance.calculateItemsTotal();
                            }, 100);
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
