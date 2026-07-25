@push('crud_fields_scripts')
    <script>
        function loadLogicCastAccount() {
            var $form = $('form');

            function syncCurrency() {
                var curr = $form.find('[name="currency_code"]').val() || 'IDR';
                var $currencySelect = $form.find('[name="total_saldo_currency"]');
                if ($currencySelect.length) {
                    $currencySelect.val(curr).trigger('change');
                }
            }

            $(document).off('change', 'form [name="currency_code"]').on('change', 'form [name="currency_code"]', function() {
                syncCurrency();
            });

            syncCurrency();
        }

        $(document).ready(function() {
            loadLogicCastAccount();
        });
    </script>
@endpush
