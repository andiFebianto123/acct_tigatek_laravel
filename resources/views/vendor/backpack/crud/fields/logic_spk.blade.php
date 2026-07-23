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

        SIAOPS.setAttribute('logic_spk', function(){
            return {
                form_type : "{{ $crud->getActionMethod() }}",

                calculateTotalWithTax: function() {
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    var curr = $(form + ' select[name="currency_code"]').val() || 'IDR';

                    // Synchronize dropdown mask_currency
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
                }
            }
        });
        SIAOPS.getAttribute('logic_spk').load();
    </script>
@endpush
