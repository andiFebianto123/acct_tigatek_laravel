@php
	// if not otherwise specified, the hidden input should take up no space in the form
  $field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
  $field['wrapper']['class'] = $field['wrapper']['class'] ?? "hidden";
  $settings = \App\Models\Setting::first();
  $entry_value = $crud?->entry;
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
            function setInputNumber2(selected, value, currency = 'IDR'){
                let nominal = (typeof window.formatCurrency === 'function') 
                    ? window.formatCurrency(value, currency)
                    : value;
                $(selected).val(nominal).trigger('input');
            }
        }
        SIAOPS.setAttribute('logic_client_quotation', function(){
            return {
                form_type : "{{ $crud->getActionMethod() }}",
                withoutPo: function(){
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    var status_po = $(form+' select[name="status"]').val();
                    if(status_po == 'TANPA PO'){
                        $(form+' select[name="client_id"]').attr('disabled', true);
                        $(form+' input[name="job_name"]').attr('disabled', true);
                        $(form+' #rap_value_masked').attr('disabled', true);
                        $(form+' #job_value_masked').attr('disabled', true);
                        $(form+' input[name="tax_ppn"]').attr('disabled', true);
                        $(form+' #start_date_end_date').attr('disabled', true);
                        $(form+' select[name="reimburse_type"]').attr('disabled', true);
                        $(form+' input[name="document_path"]').attr('disabled', true);
                    }else{
                        $(form+' select[name="client_id"]').removeAttr('disabled');
                        $(form+' input[name="job_name"]').removeAttr('disabled');
                        $(form+' #rap_value_masked').removeAttr('disabled');
                        $(form+' #job_value_masked').removeAttr('disabled');
                        $(form+' input[name="tax_ppn"]').removeAttr('disabled');
                        $(form+' #start_date_end_date').removeAttr('disabled');
                        $(form+' select[name="reimburse_type"]').removeAttr('disabled');
                        $(form+' input[name="document_path"]').removeAttr('disabled');
                    }
                },
                logicFormula: function(){
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    var curr = $(form+' select[name="currency_code"]').val() || 'IDR';

                    // Update currency dropdown di rap_value dan job_value agar mengikutsertakan masking IDR/USD
                    var $rapCurrencySelect = $(form+' select[name="rap_value_currency"]');
                    var $jobCurrencySelect = $(form+' select[name="job_value_currency"]');

                    if($rapCurrencySelect.length && $rapCurrencySelect.val() !== curr){
                        $rapCurrencySelect.val(curr).trigger('change');
                    }
                    if($jobCurrencySelect.length && $jobCurrencySelect.val() !== curr){
                        $jobCurrencySelect.val(curr).trigger('change');
                    }

                    // Update prefix icon/text pada input group Nilai Pekerjaan Include PPn
                    var $ppnField = $(form+' input[name="job_value_include_ppn"]');
                    var $ppnGroup = $ppnField.closest('.input-group');
                    if($ppnGroup.length){
                        var $prefixSpan = $ppnGroup.find('.input-group-text').first();
                        if($prefixSpan.length){
                            $prefixSpan.text(curr === 'USD' ? '$' : 'Rp');
                        }
                    }

                    var nilai_pekerjaan = getInputNumber(form+' #job_value');
                    var ppn = getInputNumber(form+' input[name="tax_ppn"]');

                    var nilai_ppn = (ppn == 0) ? 0 : (nilai_pekerjaan * (ppn / 100));
                    var total = nilai_pekerjaan + nilai_ppn;
                    setInputNumber2(form+' input[name="job_value_include_ppn"]', total, curr);
                },
                setupWithoutPoCount: function(form){
                    $.ajax({
                        url: "{{ url($crud->route.'/total-without-po') }}",
                        type: 'GET',
                        success: function(response){
                            $(form+' input[name="work_code"]').val(`UMUM-${response.count}`);
                        },
                        error: function(error){
                            console.error(error);
                        }
                    })
                },
                load: function(){
                    var instance = this;
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    var settings = {!! json_encode($settings) !!};
                    var entry = {!! json_encode($entry_value) !!};

                    if(entry){
                        if(entry.status == null || entry.status == 'ADA PO'){
                            $(form+' input[name="po_number"]').removeAttr('disabled');
                        }else{
                            $(form+' input[name="po_number"]').attr('disabled', true);
                        }
                        instance.withoutPo();
                        $(form+' select[name="status"]').on('select2:select', function (e) {
                            var data = $(this).val();
                            instance.withoutPo();
                            if(data == 'TANPA PO'){
                                $(form+' input[name="po_number"]').attr('disabled', true);
                            }else{
                                $(form+' input[name="po_number"]').removeAttr('disabled');
                            }
                        });
                        setTimeout(() => {
                            instance.logicFormula();
                        }, 200);
                    }else{
                        $(form+' select[name="status"]').on('select2:select', function (e) {
                            var data = $(this).val();
                            instance.withoutPo();
                            if(data == 'TANPA PO'){
                                instance.setupWithoutPoCount(form);
                                $(form+' input[name="po_number"]').attr('disabled', true);
                            }else{
                                $(form+' input[name="po_number"]').removeAttr('disabled');
                                $(form+' input[name="work_code"]').val(settings.work_code_prefix);
                            }
                        });
                    }

                    // Simpan mata uang sebelumnya untuk deteksi perubahan pengguna
                    var previousCurrency = $(form+' select[name="currency_code"]').val() || 'IDR';

                    // Helper untuk update nominal ter-mask dan hidden input
                    function updateFieldValue(fieldName, rawVal, currency) {
                        var $hiddenField = $(form+' #' + fieldName);
                        var $maskedField = $(form+' #' + fieldName + '_masked');

                        if (!$hiddenField.length) return;

                        var formatted = (typeof window.formatCurrency === 'function')
                            ? window.formatCurrency(rawVal, currency)
                            : rawVal;

                        $hiddenField.val(rawVal);
                        $maskedField.val(formatted);
                    }

                    // Listener saat dropdown Mata Uang (currency_code) berubah
                    $(form+' select[name="currency_code"]').on('change select2:select', function(){
                        var newCurrency = $(this).val() || 'IDR';

                        if (newCurrency !== previousCurrency) {
                            var usdRate = window.usdRate || 16000;
                            var rawRap = getInputNumber(form+' #rap_value');
                            var rawJob = getInputNumber(form+' #job_value');

                            if (rawRap > 0 && typeof window.convertCurrency === 'function') {
                                var convertedRap = window.convertCurrency(rawRap, previousCurrency, newCurrency, usdRate);
                                updateFieldValue('rap_value', convertedRap, newCurrency);
                            }
                            if (rawJob > 0 && typeof window.convertCurrency === 'function') {
                                var convertedJob = window.convertCurrency(rawJob, previousCurrency, newCurrency, usdRate);
                                updateFieldValue('job_value', convertedJob, newCurrency);
                            }

                            previousCurrency = newCurrency;
                        }

                        instance.logicFormula();
                    });

                    $(form+' #job_value_masked').on('keyup input change', function(){
                        instance.logicFormula();
                    });
                    $(form+' input[name="tax_ppn"]').on('keyup input change', function(){
                        instance.logicFormula();
                    });

                    // Trigger awal
                    setTimeout(() => {
                        previousCurrency = $(form+' select[name="currency_code"]').val() || 'IDR';
                        instance.logicFormula();
                    }, 100);
                }
            }
        });
        SIAOPS.getAttribute('logic_client_quotation').load();
    </script>
@endpush
