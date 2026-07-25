@php
	// if not otherwise specified, the hidden input should take up no space in the form
  $field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
  $field['wrapper']['class'] = $field['wrapper']['class'] ?? "hidden";
@endphp

{{-- hidden input --}}
@include('crud::fields.inc.wrapper_start')
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
<script>
    SIAOPS.setAttribute('logic_cast_loan', function(){
        return {
            form_type : "{{ $crud->getActionMethod() }}",
            load: function(){

                var instance = this;
                var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';

                $(form+' #balance_information').hide();

                $(form+' select[name="loan_transaction_flag_id"]').change(function(){
                    $.ajax({
                        url: "{{ url('admin/cash-flow/cast-account-loan/get-loan-balance') }}",
                        type: "GET",
                        data: {
                            loan_transaction_flag_id: $(form+' select[name="loan_transaction_flag_id"]').val()
                        },
                        success: function(response){
                            if(response.status == false){
                                $(form+' #balance_information').hide();
                            }else{
                                $(form+' #balance_information').show();
                                $(form+' .total_saldo').html(response.remaining_balance);
                            }
                        }
                    });
                });

                function syncCurrency() {
                    var curr = $(form + ' [name="currency_code"]').val() || 'IDR';
                    var $ts = $(form + ' [name="total_saldo_currency"]');
                    if ($ts.length) $ts.val(curr).trigger('change');
                    var $nt = $(form + ' [name="nominal_transaction_currency"]');
                    if ($nt.length) $nt.val(curr).trigger('change');
                    var $pp = $(form + ' [name="payment_price_currency"]');
                    if ($pp.length) $pp.val(curr).trigger('change');
                }

                $(document).off('change', form + ' [name="currency_code"]').on('change', form + ' [name="currency_code"]', function() {
                    syncCurrency();
                });

                syncCurrency();
            }
        }
    });
    SIAOPS.getAttribute('logic_cast_loan').load();
</script>
@endpush
