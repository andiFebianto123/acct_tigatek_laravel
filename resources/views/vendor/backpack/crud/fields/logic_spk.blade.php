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
                let num = parseFloat(value) || 0;
                let formattedNum = Number(num.toFixed(2));
                let nominal = (typeof window.formatCurrency === 'function') 
                    ? window.formatCurrency(formattedNum, currency)
                    : formattedNum;
                $(selected).val(nominal).trigger('input');
            }
        }

        SIAOPS.setAttribute('logic_spk', function(){
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

                calculateTotalWithTax: function() {
                    var $form = this.getForm();
                    var curr = $form.find('select[name="currency_code"]').val() || 'IDR';

                    // Synchronize dropdown mask_currency
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

                    // Ambil angka murni dari input hidden job_value
                    var $jobHidden = $form.find('input[type="hidden"]#job_value, input[type="hidden"][name="job_value"]').last();
                    var rawJobValue = 0;

                    if ($jobHidden.length && $jobHidden.val() !== '') {
                        rawJobValue = parseFloat($jobHidden.val()) || 0;
                    } else {
                        var maskedVal = $form.find('#job_value_masked').val() || '';
                        if (curr === 'USD') {
                            rawJobValue = parseFloat(maskedVal.replace(/,/g, '')) || 0;
                        } else {
                            rawJobValue = parseFloat(maskedVal.replace(/\./g, '').replace(/,/g, '.')) || 0;
                        }
                    }

                    var $taxInput = $form.find('input[name="tax_ppn"]');
                    var taxPpn = parseFloat($taxInput.val() || 0);

                    var nilai_ppn = (taxPpn == 0) ? 0 : (rawJobValue * (taxPpn / 100));
                    var totalWithTax = rawJobValue + nilai_ppn;
                    totalWithTax = Number(totalWithTax.toFixed(2));

                    setInputNumber2($totalField, totalWithTax, curr);
                },

                load: function(){
                    var instance = this;

                    // Initial calculation & sync
                    setTimeout(() => {
                        var $form = instance.getForm();
                        var initialCurr = $form.find('select[name="currency_code"]').val() || 'IDR';
                        $form.find('select.currency-select-dropdown').val(initialCurr);

                        var $hidden = $form.find('input[type="hidden"]#job_value, input[type="hidden"][name="job_value"]').last();
                        var rawJobVal = $hidden.val() || '';
                        if (rawJobVal !== '') {
                            var numVal = parseFloat(rawJobVal);
                            if (!isNaN(numVal) && typeof window.formatCurrency === 'function') {
                                $form.find('#job_value_masked').val(window.formatCurrency(numVal, initialCurr));
                            }
                        }

                        instance.calculateTotalWithTax();
                    }, 50);

                    // Melacak mata uang sebelumnya untuk konversi otomatis saat user mengubah dropdown utama
                    var previousCurrency = $('select[name="currency_code"]').val() || 'IDR';

                    $(document).off('change.spk_curr select2:select.spk_curr', 'select[name="currency_code"]')
                               .on('change.spk_curr select2:select.spk_curr', 'select[name="currency_code"]', function() {
                        var $form = instance.getForm();
                        var newCurrency = $(this).val() || 'IDR';

                        if (newCurrency !== previousCurrency) {
                            var usdRate = window.usdRate || 16000;
                            var rawJobVal = parseFloat($form.find('input[type="hidden"]#job_value, input[type="hidden"][name="job_value"]').last().val() || 0);

                            if (rawJobVal > 0 && typeof window.convertCurrency === 'function') {
                                var convertedJobVal = window.convertCurrency(rawJobVal, previousCurrency, newCurrency, usdRate);

                                $form.find('input[type="hidden"]#job_value, input[type="hidden"][name="job_value"]').last().val(convertedJobVal);
                                if (typeof window.formatCurrency === 'function') {
                                    $form.find('#job_value_masked').val(window.formatCurrency(parseFloat(convertedJobVal), newCurrency));
                                }
                            } else {
                                var currentRaw = $form.find('input[type="hidden"]#job_value, input[type="hidden"][name="job_value"]').last().val() || '';
                                if (typeof window.formatCurrency === 'function') {
                                    $form.find('#job_value_masked').val(window.formatCurrency(parseFloat(currentRaw) || currentRaw, newCurrency));
                                }
                            }

                            // Sync dropdown tampilan pada mask_currency
                            $form.find('select.currency-select-dropdown').val(newCurrency);

                            previousCurrency = newCurrency;
                        }

                        instance.calculateTotalWithTax();
                    });

                    // Event listener pengetikan job_value & tax_ppn
                    $(document).off('keyup.spk_val input.spk_val change.spk_val', '#job_value_masked, input[name="tax_ppn"]')
                               .on('keyup.spk_val input.spk_val change.spk_val', '#job_value_masked, input[name="tax_ppn"]', function() {
                        instance.calculateTotalWithTax();
                    });
                }
            }
        });
        SIAOPS.getAttribute('logic_spk').load();
    </script>
@endpush
