@php
	// if not otherwise specified, the hidden input should take up no space in the form
  $field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
  $field['wrapper']['class'] = $field['wrapper']['class'] ?? "hidden";
  $set_value = (isset($entry)) ? $entry : null;
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
        if(typeof setInputNumber2 == "undefined"){
            function formatIdr(angka){
                const formatter = new Intl.NumberFormat('id-ID', {
                    style: 'currency',
                    currency: 'IDR'
                });

                let hasilFormat = formatter.format(angka);
                let tanpaRp = hasilFormat.replace('Rp', '').trim();

                return tanpaRp;
            }
            function setInputNumber2(selected, value){
                let nominal = formatIdr(value);
                $(selected).val(nominal).trigger('input');
            }
        }
        SIAOPS.setAttribute('logic_invoice', function(){
            return {
                form_type : "{{ $crud->getActionMethod() }}",
                total_price: 0,
                logicFormulaNoPO: function(){
                    var instance = this;
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    var nominal_exclude_ppn = getInputNumber(form + ' input[name="nominal_exclude_ppn"]');
                    var tax_ppn = getInputNumber(form + ' input[name="tax_ppn"]');
                    var nilai_ppn = (tax_ppn == 0) ? 0 : (nominal_exclude_ppn * (tax_ppn / 100));
                    var total = nominal_exclude_ppn + nilai_ppn;
                    // setInputNumber(form + ' #nominal_include_ppn_masked', total);
                    setInputNumber2(form+' input[name="nominal_include_ppn"]', total);
                    instance.total_price = Number($(form + ' input[name="nominal_exclude_ppn"]').val());

                    var pph = getInputNumber(form + ' input[name="pph"]');
                    var diskon_pph = (pph == 0) ? 0 : nominal_exclude_ppn * (pph / 100);
                    setInputNumber2(form+' input[name="discount_pph"]', diskon_pph);
                },
                load: function(){
                    var instance = this;
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';

                    var entry = {!! json_encode($set_value) !!};
                    if(entry != null){
                        setTimeout(() => {
                            instance.total_price = entry.nominal_exclude_ppn;
                            setInputNumber(form + ' #nominal_exclude_ppn_masked', entry.nominal_exclude_ppn || 0);
                            setInputNumber(form+' #dpp_other_masked', entry.price_dpp || 0);
                            $(form+' input[name="tax_ppn"]').val(entry.tax_ppn || 0);
                            setInputNumber(form+' #nominal_include_ppn_masked', entry.price_total_include_ppn || 0);
                            
                            $(form+' input[name="pph"]').val(entry.pph || 0);
                            setInputNumber2(form+' input[name="discount_pph"]', entry.discount_pph || 0);
                            instance.logicFormulaNoPO();
                        }, 300);
                    }

                    $(form+ ' select[name="client_po_id"]').off('select2:select').on('select2:select', function (e) {
                        var id = e.params.data.id;
                        $.ajax({
                            url: '{!! backpack_url("invoice-client/get-client-po") !!}',
                            method: 'GET',
                            data: {
                                id: id,
                            },
                            success: function(response) {
                                var respon = response.result;
                                setInputNumber(form + ' #nominal_exclude_ppn_masked', respon.job_value || 0);
                                instance.total_price = Number($(form + ' input[name="nominal_exclude_ppn"]').val());
                                $(form+' input[name="tax_ppn"]').val(respon.tax_ppn);
                                instance.logicFormulaNoPO();
                                $(form+' input[name="kdp"]').val(respon.work_code);
                                $(form+' input[name="client_name"]').val(respon.client.name);
                                $(form+" input[name='po_date']").val(respon.date_po_str);
                                countTotalPrice();
                            }
                        });
                    });

                    $(form+' #nominal_exclude_ppn_masked').on('keyup', function(){
                        instance.logicFormulaNoPO();
                        countTotalPrice();
                    });

                    $(form+' input[name="tax_ppn"]').on('keyup', function(){
                        instance.logicFormulaNoPO();
                        countTotalPrice();
                    });

                    $(form + ' input[name="pph"]').on('keyup', function(){
                        instance.logicFormulaNoPO();
                    });

                    var countTotalPrice = function(){
                        var total_price = 0;
                         $(form+' input[data-alt="price_masked"]').each(function(){
                            var price_origin_field = $(this).parent().next();
                            var price_origin = Number(price_origin_field.val() || 0);
                            
                            // Find sibling qty input inside the same repeatable row
                            var row = $(this).closest('.repeatable-group, .row');
                            var qty = Number(row.find('input[data-repeatable-input-name="qty"]').val() || 1);
                            
                            total_price += (price_origin * qty);
                        });
                        console.log(total_price);
                        var price_between = instance.total_price - total_price;
                        var price_between_rupiah = price_between.toLocaleString('id-ID');
                        $(form+' input[name="nominal_information"]').val(price_between_rupiah);
                        if(price_between == 0){
                            $(form+' input[name="nominal_information"]').addClass('is-valid').removeClass('is-invalid');
                        }else if(price_between > 0){
                            $(form+' input[name="nominal_information"]').removeClass('is-invalid').removeClass('is-valid');
                        }
                        else if(price_between < 0){
                            $(form+' input[name="nominal_information"]').removeClass('is-valid').addClass('is-invalid');
                        }
                    }

                    if(form == '#form-edit'){
                        countTotalPrice();
                        setTimeout(() => {
                            $(form+' input[data-alt="price_masked"], ' + form + ' input[data-repeatable-input-name="qty"]').each(function(){
                                $(this).off('keyup change').on('keyup change', function(){
                                    countTotalPrice();
                                });
                            });
                        }, 100);
                    }

                    $(document).on("click", ".delete-element", function() {
                        countTotalPrice();
                    });

                    $(form+' .add-repeatable-element-button').on('click', function(){
                        setTimeout(() => {
                            $(form+' input[data-alt="price_masked"], ' + form + ' input[data-repeatable-input-name="qty"]').each(function(){
                                $(this).off('keyup change').on('keyup change', function(){
                                    countTotalPrice();
                                });
                            });
                        }, 100);
                    });

                }
            }
        });
        SIAOPS.getAttribute('logic_invoice').load();
    </script>
@endpush
