@php
	// if not otherwise specified, the hidden input should take up no space in the form
  $field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
  $field['wrapper']['class'] = $field['wrapper']['class'] ?? "hidden";
  $entry_value = (isset($entry)) ? $entry : null;
  $invoice_value = (isset($invoice)) ? $invoice : null;
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
        SIAOPS.setAttribute('{{ $field["name"] }}', function(){
            return {
                form_type : "{{ $crud->getActionMethod() }}",
                getRawValue: function(val) {
                    if (!val) return 0;
                    if (typeof val !== 'string') val = String(val);
                    return parseFloat(val.replace(/[^\d]/g, '')) || 0;
                },
                formatIdr: function(angka){
                    const formatter = new Intl.NumberFormat('id-ID', {
                        style: 'currency',
                        currency: 'IDR'
                    });

                    let hasilFormat = formatter.format(angka);
                    let tanpaRp = hasilFormat.replace('Rp', '').trim();

                    return tanpaRp;
                },
                setInputMasked: function(form, name, value) {
                    var $hidden = $(form + ' [name="' + name + '"]');
                    var $masked = $(form + ' #' + name + '_masked');
                    
                    $hidden.val(value).trigger('change');
                    if ($masked.length) {
                        $masked.val(value).trigger('input').trigger('change').trigger('keyup');
                    } else {
                        $hidden.val(this.formatIdr(value)).trigger('change');
                    }
                },
                toggleCalculationFields: function(show, form) {
                    var fields = [
                        'withholding_agent_status',
                        'tax_ppn_nominal',
                        'pph_nominal',
                        'total_nominal_transfer'
                    ];
                    fields.forEach(function(name) {
                        var $el = $(form + ' [name="' + name + '"]');
                        if (show) {
                            $el.closest('.form-group').show();
                        } else {
                            $el.closest('.form-group').hide();
                        }
                    });
                },
                load: function(){
                    var instance = this;
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    
                    if ('{{ $field["name"] }}' == 'logic_invoice_payment') {
                        this.loadInvoiceLogic(form);
                    } else {
                        this.loadCastAccountLogic(form);
                    }
                },
                loadInvoiceLogic: function(form) {
                    var instance = this;
                    var invoice = {!! json_encode($invoice_value ?? $entry_value) !!};

                    if (!invoice) return;

                    // 1. Show calculation fields
                    this.toggleCalculationFields(true, form);

                    // 2. Company field: readonly
                    $(form + ' [name="company_id"]').prop('disabled', true);
                    
                    // 3. field keluar/masuk readonly dan default pilih masuk
                    $(form + ' [name="status"]').val('enter').trigger('change');
                    $(form + ' [name="status"]').css('pointer-events', 'none').attr('readonly', true);
                    
                    // 4 & 5. KDP & No Invoice - Set value and Disable (Readonly for Select2)
                    if ($(form+' select[name="no_invoice"]').length && invoice.invoice_number) {
                        var selectedOption = new Option(invoice.invoice_number, invoice.id, true, true);
                        $(form+' select[name="no_invoice"]').append(selectedOption).trigger('change').prop('disabled', true);
                    }

                    if ($(form+' select[name="kdp"]').length) {
                        var kdpText = invoice.work_code || invoice.kdp || invoice.invoice_number;
                        var selectedOptionKdp = new Option(kdpText, invoice.id, true, true);
                        $(form+' select[name="kdp"]').append(selectedOptionKdp).trigger('change').prop('disabled', true);
                    }

                    // 6. field akun dibuat disabled
                    $(form + ' [name="account_id"]').prop('disabled', true);

                    // Calculations
                    instance.tax_ppn_percent = parseFloat(invoice.tax_ppn) || 0;
                    instance.pph_percent = parseFloat(invoice.pph) || parseFloat(invoice.discount_pph_percent) || 0;
                    instance.withholding_agent = invoice.withholding_agent || "NON WAPU";
                    instance.is_wapu = instance.withholding_agent === "WAPU";

                    // 7. Set initial nominal and trigger calculation
                    var initialNominal = parseFloat(invoice.price_total_exclude_ppn) || 0;
                    this.setInputMasked(form, 'nominal_transaction', initialNominal);

                    var calculate = function() {
                        var excl_ppn = instance.getRawValue($(form + ' #nominal_transaction_masked').val());
                        var ppn_percent = instance.tax_ppn_percent || 0;
                        var pph_percent = instance.pph_percent || 0;
                        var is_wapu = instance.is_wapu;
                        
                        var ppn_nominal = (excl_ppn * ppn_percent) / 100;
                        var pph_nominal = (excl_ppn * pph_percent) / 100;
                        
                        var total_transfer = is_wapu ? (excl_ppn - pph_nominal) : (excl_ppn + ppn_nominal - pph_nominal);
                        
                        instance.setInputMasked(form, 'tax_ppn_nominal', ppn_nominal);
                        instance.setInputMasked(form, 'pph_nominal', pph_nominal);
                        instance.setInputMasked(form, 'total_nominal_transfer', total_transfer);
                        $(form + ' [name="withholding_agent_status"]').val(instance.withholding_agent);
                    };

                    $(form + ' #nominal_transaction_masked').on('change keyup input', calculate);
                    calculate();
                },
                loadCastAccountLogic: function(form) {
                    var instance = this;

                    if(form == '#form-edit'){
                        var entry = {!! json_encode($entry_value) !!};
                        if(entry && entry.reference_type == "App\\Models\\InvoiceClient"){
                            var invoice = {!! json_encode($invoice_value) !!};
                            var selectedOption = new Option(invoice.invoice_number, invoice.id, true, true);
                            $(form+' select[name="no_invoice"]').append(selectedOption).trigger('change');

                            var selectedOptionKdp = new Option(invoice.kdp, invoice.id, true, true);
                            $(form+' select[name="kdp"]').append(selectedOptionKdp).trigger('change');
                            
                            calculateTransaction(invoice.id, 'kdp');
                        }
                    }

                    function toggleCalculationFieldsInternal(show) {
                        instance.toggleCalculationFields(show, form);
                        
                        var $accountId = $(form + ' [name="account_id"]');
                        var $msg = $accountId.closest('.form-group').find('.invoice-account-msg');
                        if (show) {
                            $accountId.prop('disabled', true).val(null).trigger('change');
                            $msg.removeClass('d-none');
                        } else {
                            $accountId.prop('disabled', false).trigger('change');
                            $msg.addClass('d-none');
                        }
                    }

                    // Prepend message to account_id field
                    var $accountIdField = $(form + ' [name="account_id"]');
                    var $accountIdWrapper = $accountIdField.closest('.form-group');
                    if ($accountIdWrapper.length && !$accountIdWrapper.find('.invoice-account-msg').length) {
                        $accountIdWrapper.append('<small class="invoice-account-msg text-danger d-none">{{ trans("backpack::crud.cash_account.field_transaction.account_id.hint_invoice_payment") }}</small>');
                    }

                    toggleCalculationFieldsInternal(false);

                    instance.tax_ppn_percent = 0;
                    instance.pph_percent = 0;
                    instance.withholding_agent = "NON WAPU";
                    instance.is_wapu = false;

                    function calculateTransaction(id, source_field) {
                        if (!id) {
                            toggleCalculationFieldsInternal(false);
                            instance.tax_ppn_percent = 0;
                            instance.pph_percent = 0;
                            return;
                        }
                        
                        $.ajax({
                            url: "{{ url($crud->route) }}/get-invoice?id=" + id,
                            type: 'GET',
                            dataType: 'json',
                            success: function (data) {
                                if (data) {
                                    toggleCalculationFieldsInternal(true);
                                    var id = data.id;
                                    var invoice_number = data.invoice_number;
                                    var kdp = data.kdp;
                                    var job_name = data.job_name;

                                    var excl_ppn = parseFloat(data.price_total_exclude_ppn) || 0;
                                    var tax_ppn_percent = parseFloat(data.tax_ppn) || 0;
                                    var pph_percent = parseFloat(data.pph) || 0;

                                    instance.tax_ppn_percent = tax_ppn_percent;
                                    instance.pph_percent = pph_percent;
                                    instance.withholding_agent = data.withholding_agent || "NON WAPU";
                                    instance.is_wapu = instance.withholding_agent === "WAPU";

                                    var ppn_nominal = (excl_ppn * tax_ppn_percent) / 100;
                                    var pph_nominal = (excl_ppn * pph_percent) / 100;
                                    
                                    var total_transfer = 0;
                                    
                                    if (instance.is_wapu) {
                                        total_transfer = excl_ppn - pph_nominal;
                                    } else {
                                        total_transfer = excl_ppn + ppn_nominal - pph_nominal;
                                    }

                                    instance.setInputMasked(form, 'nominal_transaction', excl_ppn);
                                    $(form + ' [name="tax_ppn_nominal"]').val(instance.formatIdr(ppn_nominal));
                                    $(form + ' [name="pph_nominal"]').val(instance.formatIdr(pph_nominal));
                                    $(form + ' [name="total_nominal_transfer"]').val(instance.formatIdr(total_transfer));
                                    $(form + ' [name="withholding_agent_status"]').val(instance.withholding_agent);
                                    
                                    if (job_name) {
                                        $(form + ' input[name="job_name"]').val(job_name);
                                    }

                                    // Sync other select
                                    var otherSelect = (source_field == 'kdp') ? 'no_invoice' : 'kdp';
                                    var otherText = (otherSelect == 'no_invoice') ? invoice_number : kdp;
                                    
                                    if ($(form+' select[name="'+otherSelect+'"]').val() != id) {
                                        var selectedOption = new Option(otherText, id, true, true);
                                        $(form+' select[name="'+otherSelect+'"]').append(selectedOption).trigger('change');
                                    }
                                }
                            }
                        });
                    }

                    function calculateFromNominal() {
                        var $masked = $(form + ' #nominal_transaction_masked');
                        var excl_ppn = instance.getRawValue($masked.val());
                        var ppn_percent = instance.tax_ppn_percent || 0;
                        var pph_percent = instance.pph_percent || 0;
                        var is_wapu = instance.is_wapu;
                        
                        var ppn_nominal = (excl_ppn * ppn_percent) / 100;
                        var pph_nominal = (excl_ppn * pph_percent) / 100;
                        
                        var total_transfer = is_wapu ? (excl_ppn - pph_nominal) : (excl_ppn + ppn_nominal - pph_nominal);
                        
                        instance.setInputMasked(form, 'tax_ppn_nominal', ppn_nominal);
                        instance.setInputMasked(form, 'pph_nominal', pph_nominal);
                        instance.setInputMasked(form, 'total_nominal_transfer', total_transfer);
                    }

                    $(form + ' #nominal_transaction_masked').on('change keyup input', function(){
                        calculateFromNominal();
                    });

                    $(form+' select[name="kdp"]').off('select2:select').on('select2:select', function (e) {
                        calculateTransaction(e.params.data.id, 'kdp');
                    });

                    $(form+' select[name="no_invoice"]').off('select2:select').on('select2:select', function (e) {
                        calculateTransaction(e.params.data.id, 'no_invoice');
                    });

                    $(form+' select[name="kdp"], ' + form + ' select[name="no_invoice"]').on('change', function() {
                        if (!$(this).val()) {
                            toggleCalculationFieldsInternal(false);
                            $(form + ' input[name="job_name"]').val('');
                            
                            // Clear peer select if it still has value to avoid mismatch
                            var name = $(this).attr('name');
                            var other = (name == 'kdp') ? 'no_invoice' : 'kdp';
                            var otherSelect = $(form + ' select[name="' + other + '"]');
                            if (otherSelect.val()) {
                                otherSelect.val(null).trigger('change');
                            }
                        }
                    });
                }
            }
        });
        SIAOPS.getAttribute('{{ $field["name"] }}').load();
    </script>
@endpush
