@php
	// if not otherwise specified, the hidden input should take up no space in the form
  $field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
  $field['wrapper']['class'] = $field['wrapper']['class'] ?? "hidden";
  $set_value = (isset($entry)) ? $entry?->reference : null;
@endphp

{{-- hidden input --}}
@include('crud::fields.inc.wrapper_start')
  <input type="hidden" name="no_type" />
  <input type="hidden" name="bussines_entity_name" />
  <input type="hidden" name="type" />
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

        SIAOPS.setAttribute('logic_asset', function(){
            return {
                form_type : "{{ $crud->getActionMethod() }}",
                logicFormula: function(){
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    var bill_value = getInputNumber(form+' #bill_value');
                    var ppn = getInputNumber(form+' input[name="tax_ppn"]');

                    var nilai_ppn = (ppn == 0) ? 0 : (bill_value * (ppn / 100));
                    setInputNumber2(form+' input[name="total_price_ppn"]', nilai_ppn);

                    var total = bill_value + nilai_ppn;
                    setInputNumber2(form+ ' input[name="total"]', total);

                    var pph_23 = getInputNumber(form + ' input[name="pph_23"]');
                    var diskon_pph_23 = (pph_23 == 0) ? 0 : bill_value * (pph_23 / 100);
                    setInputNumber2(form+' input[name="discount_pph_23"]', diskon_pph_23);

                    var pph_4 = getInputNumber(form + ' input[name="pph_4"]');
                    var diskon_pph_4 = (pph_4 == 0) ? 0 : bill_value * (pph_4 / 100);
                    setInputNumber2(form+' input[name="discount_pph_4"]', diskon_pph_4);

                    var pph_21 = getInputNumber(form+' input[name="pph_21"]');
                    var diskon_pph_21 = (pph_21 == 0) ? 0 : bill_value * (pph_21 / 100);
                    setInputNumber2(form+' input[name="discount_pph_21"]', diskon_pph_21);

                    var payment_transfer = total - diskon_pph_23 - diskon_pph_4 - diskon_pph_21;
                    setInputNumber2(form+' input[name="payment_transfer"]', payment_transfer);

                },
                togglePoType: function(isInit = false){
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    var po_type = $(form+' select[name="po_type"]').val();
                    
                    var clientPoWrapper = $(form+' select[name="client_po_id"]').closest('.form-group');
                    var invoiceClientWrapper = $(form+' select[name="invoice_client_id"]').closest('.form-group');
                    var referenceWrapper = $(form+' select[name="reference_id"]').closest('.form-group');
                    
                    var jobNameWrapper = $(form+' input[name="job_name_disabled"]').closest('.form-group');
                    var jobNameLabel = jobNameWrapper.find('label');
                    
                    if (po_type === 'supplier') {
                        clientPoWrapper.hide();
                        $(form+' select[name="client_po_id"]').attr('disabled', true);
                        
                        invoiceClientWrapper.hide();
                        $(form+' select[name="invoice_client_id"]').attr('disabled', true);
                        
                        referenceWrapper.show();
                        $(form+' select[name="reference_id"]').removeAttr('disabled');
                        
                        jobNameLabel.text("Deskripsi Pesanan");
                    } else {
                        // subkon
                        clientPoWrapper.hide();
                        $(form+' select[name="client_po_id"]').attr('disabled', true);
                        
                        referenceWrapper.hide();
                        $(form+' select[name="reference_id"]').attr('disabled', true);
                        
                        invoiceClientWrapper.show();
                        $(form+' select[name="invoice_client_id"]').removeAttr('disabled');
                        
                        jobNameLabel.text("Nama Pekerjaan");
                    }
                    
                    if (!isInit) {
                        $(form+' select[name="client_po_id"]').val(null).trigger('change');
                        $(form+' select[name="invoice_client_id"]').val(null).trigger('change');
                        $(form+' select[name="reference_id"]').val(null).trigger('change');
                        $(form+' select[name="subkon_id"]').val(null).trigger('change');
                        $(form+' input[name="job_name"]').val(null);
                        $(form+' input[name="job_name_disabled"]').val(null);
                    }
                },
                load: function(){
                    var instance = this;
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';

                    instance.togglePoType(true);
                    $(form+' select[name="po_type"]').on('change select2:select', function() {
                        instance.togglePoType(false);
                    });

                    @if (isset($entry))
                        var data_po_spk = {!! json_encode($set_value) !!};
                        var data_entry = {!! json_encode($entry) !!};

                        if(data_po_spk != null){
                            var no_po_spk = "";
                            if(data_po_spk.type == 'spk'){
                                no_po_spk = data_entry.reference.no_spk;
                            }else{
                                no_po_spk = data_entry.reference.po_number;
                            }

                            $(form+ ' input[name="bussines_entity_name"]').val(data_po_spk.name_company);
                            $(form+' input[name="type"]').val(data_po_spk.type);

                            var selectedOptionw = new Option(no_po_spk, data_entry.reference.id, true, true);
                            $(form+' select[name="reference_id"]').append(selectedOptionw).trigger('change');
                        }

                        if (data_entry.invoice_client) {
                            var selectedOptionInvoice = new Option(data_entry.invoice_client.invoice_number, data_entry.invoice_client.id, true, true);
                            $(form+ ' select[name="invoice_client_id"]').append(selectedOptionInvoice).trigger('change');
                        }

                        $(form+' input[name="job_name_disabled"]').val(data_entry.job_name);
                        // $(form+ ' input[name="date_po_spk"]').val(data_po_spk.date_po_spk_str);
                        // $(form+ ' input[name="bank_name"]').val(data_po_spk.bank_name);
                        // $(form+' input[name="no_account"]').val(data_po_spk.bank_account);
                        // $(form+' input[name="no_type"]').val(data_po_spk.type);

                        // setTimeout(() => {
                        //      $(form+ ' input[name="date_po_spk"]').val(data_po_spk.date_po_spk_str);
                        // }, 500);

                        setTimeout(() => {
                            instance.logicFormula();
                        }, 200);
                    @endif

                    $(form+ ' select[name="reference_id"]').off('select2:select').on('select2:select', function (e) {
                        var id = e.params.data.id;
                        var type = e.params.data.type;
                        $.ajax({
                            url: "{{ url($crud->route) }}/get_client_selected_ajax?id=" + id + "&type=" + type,
                            type: 'GET',
                            dataType: 'json',
                            success: function (data) {

                                var po = data.po;
                                var account = data.account;

                                if(data.company){
                                    var subkon_option = new Option(data.company.name, data.company.id, true, true);
                                    $(form+' select[name="subkon_id"]').append(subkon_option).trigger('change');
                                    if(data.company.bank_name){
                                        $(form+' input[name="bank_name"]').val(data.company.bank_name);
                                    }
                                    if(data.company.bank_account){
                                        $(form+' input[name="no_account"]').val(data.company.bank_account);
                                    }
                                    if(data.company.account_holder_name){
                                        $(form+' input[name="account_holder_name"]').val(data.company.account_holder_name);
                                    }
                                }else{
                                    $(form+' select[name="subkon_id"]').val(null).trigger('change');
                                    $(form+' input[name="bank_name"]').val(null);
                                    $(form+' input[name="no_account"]').val(null);
                                    $(form+' input[name="account_holder_name"]').val(null);
                                }

                                if(data.date_po){
                                    $(form+' input[name="date_po_spk"]').val(data.date_po);
                                }else{
                                    $(form+' input[name="date_po_spk"]').val(null);
                                }

                                // var account_text = `${account.code} - ${account.name}`;
                                // var account_option = new Option(account_text, account.id, true, true);
                                // $(form+ ' select[name="account_id"]').append(account_option).trigger('change');

                                var work_code_text = `${po.work_code} (${po.type})`;

                                // var work_option = new Option(work_code_text, po.id, true, true);
                                // $(form+ ' select[name="reference_id"]').append(work_option).trigger('change');
                                // $(form+' input[name="job_name"]').val(po.job_name);
                                // setInputNumber(form+' #bill_value_masked', po.price_total);
                                // setInputNumber(form+' input[name="tax_ppn"]', po.ppn);
                                $(form+' input[name="type"]').val(po.type);
                                // instance.logicFormula();
                            }
                        })
                    });

                    $(form+' select[name="client_po_id"]').off('select2:select').on('select2:select', function (e) {
                        var id = e.params.data.id;
                        var type = e.params.data.type;
                        $.ajax({
                            url: "{{ url($crud->route) }}/get_client_selected_ajax?id=" + id + "&type=" + type,
                            type: 'GET',
                            dataType: 'json',
                            success: function (data) {

                                var po = data.po;
                                var account = data.account;


                                // var account_text = `${account.code} - ${account.name}`;
                                // var account_option = new Option(account_text, account.id, true, true);
                                // $(form+ ' select[name="account_id"]').append(account_option).trigger('change');

                                var po_number_text = `${po.po_number} (${po.type})`;

                                if(po.status){
                                    if(po.status == 'TANPA PO'){
                                        po_number_text = "Tanpa PO";
                                    }
                                }

                                if(data.company){
                                    var subkon_option = new Option(data.company.name, data.company.id, true, true);
                                    $(form+' select[name="subkon_id"]').append(subkon_option).trigger('change');
                                    if(data.company.bank_name){
                                        $(form+' input[name="bank_name"]').val(data.company.bank_name);
                                    }
                                    if(data.company.bank_account){
                                        $(form+' input[name="no_account"]').val(data.company.bank_account);
                                    }
                                }else{
                                    // $(form+' select[name="subkon_id"]').val(null).trigger('change');
                                    // $(form+' input[name="bank_name"]').val(null);
                                    // $(form+' input[name="no_account"]').val(null);
                                }

                                // if(data.date_po){
                                //     $(form+' input[name="date_po_spk"]').val(data.date_po);
                                // }else{
                                //     $(form+' input[name="date_po_spk"]').val(null);
                                // }

                                // var po_number_option = new Option(po_number_text, po.id, true, true);
                                // $(form+ ' select[name="client_po_id"]').append(po_number_option).trigger('change');

                                $(form+' input[name="job_name"]').val(po.job_name);
                                $(form+' input[name="job_name_disabled"]').val(po.job_name);
                                // setInputNumber(form+' #bill_value_masked', po.price_total);
                                // setInputNumber(form+' input[name="tax_ppn"]', po.ppn);
                                // $(form+' input[name="type"]').val(po.type);
                                // instance.logicFormula();
                            }
                        })
                    });

                    $(form+' select[name="invoice_client_id"]').off('select2:select').on('select2:select', function (e) {
                        var id = e.params.data.id;
                        $.ajax({
                            url: "{{ url($crud->route) }}/get_invoice_selected_ajax?id=" + id,
                            type: 'GET',
                            dataType: 'json',
                            success: function (data) {
                                var po = data.po;
                                if(data.company){
                                    var subkon_option = new Option(data.company.name, data.company.id, true, true);
                                    $(form+' select[name="subkon_id"]').append(subkon_option).trigger('change');
                                    if(data.company.bank_name){
                                        $(form+' input[name="bank_name"]').val(data.company.bank_name);
                                    }
                                    if(data.company.bank_account){
                                        $(form+' input[name="no_account"]').val(data.company.bank_account);
                                    }
                                }
                                $(form+' input[name="job_name"]').val(po.job_name);
                                $(form+' input[name="job_name_disabled"]').val(po.job_name);
                            }
                        })
                    });

                    $(form+' select[name="factur_status"]').off('select2:select').on('select2:select', function (e) {
                        if(e.params.data.id == 'TIDAK ADA' || e.params.data.id == "AKAN ADA"){
                            $(form+' input[name="no_factur"]').attr('readonly', true);
                            $(form+' #date_factur').attr('disabled', true);
                        }else{
                            $(form+' input[name="no_factur"]').removeAttr('readonly');
                            $(form+' #date_factur').removeAttr('disabled');
                        }
                    });

                    $(form+' select[name="subkon_id"]').off('select2:select').on('select2:select', function (e) {
                        var id = e.params.data.id;
                        console.log('selected');
                        $.ajax({
                            url: "{{ url($crud->route) }}/get_account_source_selected_ajax?id=" + id,
                            type: 'GET',
                            dataType: 'json',
                            success: function (data) {
                                $(form+' input[name="bank_name"]').val(data.bank_name);
                                $(form+' input[name="no_account"]').val(data.bank_account);
                            }
                        })
                    });

                    $(form+' #bill_value_masked').on('keyup', function(){
                        instance.logicFormula();
                    });

                    $(form+' input[name="tax_ppn"]').on('keyup', function(){
                        instance.logicFormula();
                    });

                    $(form + ' input[name="pph_23"]').on('keyup', function(){
                        instance.logicFormula();
                    });

                    $(form + ' input[name="pph_4"]').on('keyup', function(){
                        instance.logicFormula();
                    });

                    $(form+' input[name="pph_21"]').on('keyup', function(){
                        instance.logicFormula();
                    });

                }
            }
        });
        SIAOPS.getAttribute('logic_asset').load();
    </script>
@endpush
