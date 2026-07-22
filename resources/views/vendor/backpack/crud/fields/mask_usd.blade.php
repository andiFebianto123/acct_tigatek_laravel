@php
    $name = $field['name'];

    // Ambil nilai nominal (prioritas: old() -> entry model -> value -> default)
    $amount_value = old($name) ?? $entry->{$name} ?? $field['value'] ?? $field['default'] ?? '';
    $amount_value = preg_replace('/\.00$/', '', (string)$amount_value);

    $field['type'] = $field['type'] ?? 'text';
    $field['attributes']['id'] = $field['attributes']['id'] ?? $name . '_masked';
    $hidden_input_id = $name;
    $prefix_symbol = $field['prefix'] ?? '$';
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')
    
    <div class="input-group">
        {{-- Prefix $ untuk USD --}}
        <span class="input-group-text bg-light fw-bold text-dark">{{ $prefix_symbol }}</span>

        {{-- Input Visible yang di-mask --}}
        <input
            type="text"
            data-alt="{{ $field['attributes']['id'] }}"
            data-init-function="bpFieldInitMaskUsdElement"
            value="{{ $amount_value }}"
            @include('crud::fields.inc.attributes')
        >
    </div>

    {{-- Input hidden untuk dikirim ke server (nominal angka murni format decimal) --}}
    <input type="hidden" name="{{ $name }}" id="{{ $hidden_input_id }}" value="{{ $amount_value }}">

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

<script>
    if (typeof bpFieldInitMaskUsdElement === 'undefined') {
        function bpFieldInitMaskUsdElement(element){
            var $maskedInput = $(element);
            var $container = $maskedInput.closest('.form-group');
            var $hiddenInput = $container.find('input[type="hidden"]').last();

            // Ekstrak angka murni format standar (titik desimal) untuk DB/Server (misal: 1234.56)
            function getCleanValue(val) {
                if (!val && val !== 0) return '';
                val = val.toString();
                let isNegative = val.startsWith('-');
                let clean = val.replace(/,/g, '');
                let parts = clean.replace(/[^\d.-]/g, '').split('.');
                
                let result = parts[0] || '';
                if (parts.length > 1) {
                    result += '.' + parts[1].substring(0, 2);
                }
                return (isNegative && !result.startsWith('-') ? '-' : '') + result;
            }

            // Pemformatan visual USD di kotak input UI (misal: 1,234.56)
            function formatUsd(val) {
                if (!val && val !== 0) return '';
                val = val.toString();
                let isNegative = val.startsWith('-');
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

            // Inisialisasi awal
            var initialRaw = getCleanValue($maskedInput.val());
            if (!initialRaw && $hiddenInput.val()) {
                initialRaw = $hiddenInput.val();
            }
            $maskedInput.val(formatUsd(initialRaw));
            $hiddenInput.val(initialRaw);

            // Ketika pengguna mengetik di input tampak: update angka murni ke $hiddenInput
            $maskedInput.off('input change keyup').on('input change keyup', function () {
                var currentRaw = getCleanValue($(this).val());
                $(this).val(formatUsd($(this).val()));
                $hiddenInput.val(currentRaw);
            });
        }
    }

    if (typeof window.convertCurrency === 'undefined') {
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
</script>
