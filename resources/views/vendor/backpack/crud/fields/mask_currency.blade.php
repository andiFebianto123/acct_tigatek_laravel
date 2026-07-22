@php
    $settings = \App\Models\Setting::first();
    $usd_rate = $settings?->usd_rate ?? 16000;

    $name = $field['name'];
    $currency_name = $field['currency_name'] ?? $name . '_currency';

    // Ambil nilai nominal (prioritas: old() -> entry model -> value -> default)
    $amount_value = old($name) ?? $entry->{$name} ?? $field['value'] ?? $field['default'] ?? '';
    $amount_value = preg_replace('/\.00$/', '', (string)$amount_value);

    // Ambil nilai currency (prioritas: old() -> entry model -> currency_code -> default_currency)
    $currency_value = old($currency_name) ?? $entry->{$currency_name} ?? $entry->currency_code ?? $field['default_currency'] ?? 'IDR';

    // Opsi pilihan currency
    $currency_options = $field['currency_options'] ?? [
        'IDR' => 'IDR (Rp)',
        'USD' => 'USD ($)',
        'EUR' => 'EUR (€)',
    ];

    $field['type'] = $field['type'] ?? 'text';
    $field['attributes']['id'] = $field['attributes']['id'] ?? $name . '_masked';
    $hidden_input_id = $name;
    $currency_select_id = $currency_name;
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')
    
    <div class="input-group">
        {{-- Dropdown Pilihan Currency (Read-Only / Terkunci dari Manual Click) --}}
        <select 
            name="{{ $currency_name }}" 
            id="{{ $currency_select_id }}" 
            class="form-select bg-light fw-bold currency-select-dropdown"
            tabindex="-1"
            style="width: auto; min-width: 90px; max-width: 115px; cursor: not-allowed; pointer-events: none; z-index: 2; padding-left: 0.75rem; padding-right: 0.75rem !important; font-size: 0.9rem; -webkit-appearance: none !important; -moz-appearance: none !important; appearance: none !important;"
        >
            @foreach($currency_options as $key => $optionLabel)
                <option value="{{ $key }}" {{ $currency_value == $key ? 'selected' : '' }}>
                    {{ $optionLabel }}
                </option>
            @endforeach
        </select>

        {{-- Input Visible yang di-mask --}}
        <input
            type="text"
            data-alt="{{ $field['attributes']['id'] }}"
            data-init-function="bpFieldInitMaskCurrencyElement"
            value="{{ $amount_value }}"
            @include('crud::fields.inc.attributes')
        >
    </div>

    {{-- Input hidden untuk dikirim ke server (nominal angka murni) --}}
    <input type="hidden" name="{{ $name }}" id="{{ $hidden_input_id }}" value="{{ $amount_value }}">

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

<script>
    window.usdRate = {{ (float)$usd_rate }};

    // Fungsi Pemformatan Tunggal Global untuk Currency
    if (typeof window.formatCurrency === 'undefined') {
        window.formatCurrency = function(val, currency = 'IDR') {
            if (!val && val !== 0) return '';
            val = val.toString();
            let isNegative = val.startsWith('-');

            if (currency === 'IDR') {
                let digits = val.replace(/[^\d]/g, '');
                if (!digits) return '';
                let sisa = digits.length % 3;
                let rupiah = digits.substr(0, sisa);
                let ribuan = digits.substr(sisa).match(/\d{3}/g);
                if (ribuan) {
                    let separator = sisa ? '.' : '';
                    rupiah += separator + ribuan.join('.');
                }
                return (isNegative ? '-' : '') + rupiah;

            } else if (currency === 'EUR') {
                let hasCommaDecimal = val.includes(',');
                let clean = val.replace(/\./g, '').replace(',', '.');
                let cleanDigits = clean.replace(/[^\d.]/g, '');
                let parts = cleanDigits.split('.');

                let integerPart = parts[0] || '';
                let sisa = integerPart.length % 3;
                let formattedInt = integerPart.substr(0, sisa);
                let ribuan = integerPart.substr(sisa).match(/\d{3}/g);
                if (ribuan) {
                    let separator = sisa ? '.' : '';
                    formattedInt += separator + ribuan.join('.');
                }

                if (!formattedInt && !hasCommaDecimal) return '';

                if (parts.length > 1) {
                    let decimalPart = parts[1].substring(0, 2);
                    return (isNegative ? '-' : '') + (formattedInt || '0') + ',' + decimalPart;
                } else if (hasCommaDecimal) {
                    return (isNegative ? '-' : '') + (formattedInt || '0') + ',';
                }

                return (isNegative ? '-' : '') + formattedInt;

            } else { // USD / Default
                let hasDecimalPoint = val.includes('.');
                let clean = val.replace(/[^\d.]/g, '');
                let parts = clean.split('.');
                
                let integerPart = parts[0] || '';
                let sisa = integerPart.length % 3;
                let formattedInt = integerPart.substr(0, sisa);
                let ribuan = integerPart.substr(sisa).match(/\d{3}/g);
                if (ribuan) {
                    let separator = sisa ? ',' : '';
                    formattedInt += separator + ribuan.join(',');
                }

                if (!formattedInt && !hasDecimalPoint) return '';

                if (parts.length > 1) {
                    let decimalPart = parts[1].substring(0, 2);
                    return (isNegative ? '-' : '') + (formattedInt || '0') + '.' + decimalPart;
                } else if (hasDecimalPoint) {
                    return (isNegative ? '-' : '') + (formattedInt || '0') + '.';
                }

                return (isNegative ? '-' : '') + formattedInt;
            }
        };

        window.formatIdr = function(val) {
            return window.formatCurrency(val, 'IDR');
        };

        window.formatUsd = function(val) {
            return window.formatCurrency(val, 'USD');
        };

        /**
         * Helper Konversi Universal (Alur 2-Langkah: Nominal -> IDR Base -> Target Currency)
         */
        window.convertCurrency = function(amount, fromCurrency = 'IDR', toCurrency = 'IDR', usdRate = null) {
            let num = parseFloat(amount);
            if (isNaN(num) || num === 0) return '0';

            let rate = usdRate || window.usdRate || 16000;
            let from = (fromCurrency || 'IDR').toUpperCase();
            let to = (toCurrency || 'IDR').toUpperCase();

            if (from === to) {
                return from === 'USD' ? num.toFixed(2) : Math.round(num).toString();
            }

            // Langkah 1: Konversi Nominal Asal ke IDR Base
            let baseIdr = num;
            if (from === 'USD') {
                baseIdr = num * rate;
            }

            // Langkah 2: Konversi IDR Base ke Mata Uang Tujuan (IDR / USD)
            if (to === 'USD') {
                return (baseIdr / rate).toFixed(2);
            }

            return Math.round(baseIdr).toString();
        };
    }

    if (typeof bpFieldInitMaskCurrencyElement === 'undefined') {
        function bpFieldInitMaskCurrencyElement(element){
            var $maskedInput = $(element);
            var $container = $maskedInput.closest('.form-group');
            var $hiddenInput = $container.find('input[type="hidden"]').last();
            var $currencySelect = $container.find('select.currency-select-dropdown');

            // Ekstrak angka murni format standar (titik desimal) untuk DB/Server
            function getCleanValue(val, currency) {
                if (!val && val !== 0) return '';
                val = val.toString().trim();

                if (currency === 'IDR') {
                    // IDR: Titik adalah pemisah ribuan, hapus titik dan semua non-digit
                    return val.replace(/[^\d-]/g, '');
                } else if (currency === 'EUR') {
                    // EUR: Titik adalah ribuan, koma adalah desimal
                    let clean = val.replace(/\./g, '').replace(',', '.');
                    let parts = clean.replace(/[^\d.-]/g, '').split('.');
                    if (parts.length > 1) {
                        return parts[0] + '.' + parts[1].substring(0, 2);
                    }
                    return parts[0];
                } else {
                    // USD / Default: Koma adalah ribuan, titik adalah desimal
                    let clean = val.replace(/,/g, '');
                    let parts = clean.replace(/[^\d.-]/g, '').split('.');
                    if (parts.length > 1) {
                        return parts[0] + '.' + parts[1].substring(0, 2);
                    }
                    return parts[0];
                }
            }

            // Inisialisasi awal
            var initialCurrency = $currencySelect.val() || 'IDR';
            var initialRaw = getCleanValue($maskedInput.val(), initialCurrency);
            if (!initialRaw && $hiddenInput.val()) {
                initialRaw = $hiddenInput.val();
            }
            $maskedInput.val(window.formatCurrency(initialRaw, initialCurrency));
            $hiddenInput.val(initialRaw);

            // Ketika dropdown currency diganti: gunakan angka murni dari $hiddenInput
            $currencySelect.off('change input').on('change input', function(e) {
                e.stopPropagation();
                var newCurrency = $(this).val() || 'IDR';
                var pureRawNumber = $hiddenInput.val() || '';
                $maskedInput.val(window.formatCurrency(pureRawNumber, newCurrency));
            });

            // Ketika pengguna mengetik di input tampak: update angka murni ke $hiddenInput
            $maskedInput.off('input change keyup').on('input change keyup', function () {
                var currentCurrency = $currencySelect.val() || 'IDR';
                var currentRaw = getCleanValue($(this).val(), currentCurrency);
                $(this).val(window.formatCurrency($(this).val(), currentCurrency));
                $hiddenInput.val(currentRaw);
            });
        }
    }
</script>
