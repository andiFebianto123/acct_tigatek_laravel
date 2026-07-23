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
        SIAOPS.setAttribute('logic_device', function(){
            return {
                form_type : "{{ $crud->getActionMethod() }}",

                syncCurrencyDropdowns: function(curr) {
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    
                    var $sellCurrency = $(form + ' select[name="sell_price_currency"]');
                    if ($sellCurrency.length && $sellCurrency.val() !== curr) {
                        $sellCurrency.val(curr).trigger('change');
                    }

                    var $buyCurrency = $(form + ' select[name="buy_price_currency"]');
                    if ($buyCurrency.length && $buyCurrency.val() !== curr) {
                        $buyCurrency.val(curr).trigger('change');
                    }

                    $(form + ' select.currency-select-dropdown').val(curr);
                },

                load: function(){
                    var instance = this;
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';

                    // Initial sync
                    setTimeout(() => {
                        var initialCurr = $(form + ' select[name="currency_code"]').val() || 'IDR';
                        instance.syncCurrencyDropdowns(initialCurr);

                        var rawSell = $(form + ' #sell_price').val() || '';
                        if (rawSell && typeof window.formatCurrency === 'function') {
                            $(form + ' #sell_price_masked').val(window.formatCurrency(rawSell, initialCurr));
                        }

                        var rawBuy = $(form + ' #buy_price').val() || '';
                        if (rawBuy && typeof window.formatCurrency === 'function') {
                            $(form + ' #buy_price_masked').val(window.formatCurrency(rawBuy, initialCurr));
                        }
                    }, 100);

                    // Melacak mata uang sebelumnya untuk konversi otomatis
                    var previousCurrency = $(form + ' select[name="currency_code"]').val() || 'IDR';

                    $(form + ' select[name="currency_code"]').on('change select2:select', function() {
                        var newCurrency = $(this).val() || 'IDR';

                        if (newCurrency !== previousCurrency) {
                            var usdRate = window.usdRate || 16000;

                            // Convert Sell Price
                            var rawSell = (typeof getInputNumber === 'function')
                                ? getInputNumber(form + ' #sell_price')
                                : parseFloat($(form + ' #sell_price').val() || 0);

                            if (rawSell > 0 && typeof window.convertCurrency === 'function') {
                                var convertedSell = window.convertCurrency(rawSell, previousCurrency, newCurrency, usdRate);
                                $(form + ' #sell_price').val(convertedSell);
                                if (typeof window.formatCurrency === 'function') {
                                    $(form + ' #sell_price_masked').val(window.formatCurrency(convertedSell, newCurrency));
                                }
                            } else {
                                var currentRawSell = $(form + ' #sell_price').val() || '';
                                if (typeof window.formatCurrency === 'function') {
                                    $(form + ' #sell_price_masked').val(window.formatCurrency(currentRawSell, newCurrency));
                                }
                            }

                            // Convert Buy Price
                            var rawBuy = (typeof getInputNumber === 'function')
                                ? getInputNumber(form + ' #buy_price')
                                : parseFloat($(form + ' #buy_price').val() || 0);

                            if (rawBuy > 0 && typeof window.convertCurrency === 'function') {
                                var convertedBuy = window.convertCurrency(rawBuy, previousCurrency, newCurrency, usdRate);
                                $(form + ' #buy_price').val(convertedBuy);
                                if (typeof window.formatCurrency === 'function') {
                                    $(form + ' #buy_price_masked').val(window.formatCurrency(convertedBuy, newCurrency));
                                }
                            } else {
                                var currentRawBuy = $(form + ' #buy_price').val() || '';
                                if (typeof window.formatCurrency === 'function') {
                                    $(form + ' #buy_price_masked').val(window.formatCurrency(currentRawBuy, newCurrency));
                                }
                            }

                            instance.syncCurrencyDropdowns(newCurrency);
                            previousCurrency = newCurrency;
                        }
                    });
                }
            }
        });
        SIAOPS.getAttribute('logic_device').load();
    </script>
@endpush
