{{-- text input --}}
@php
    $field_language = isset($field['date_picker_options']['language']) ? $field['date_picker_options']['language'] : \App::getLocale();
    $field_id = $field['name'].'_'.rand(1, 9999);
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <style>
        .table-responsive {
            overflow-x: auto !important;
        }
    </style>

    <div class="table-responsive">
        <table id="{{ $field_id }}" class="table table-striped table-hover nowrap" style="width:100%">
            <thead>
                <tr>
                    <th>No</th>
                    <th>
                        <input type="checkbox" class="form-check-input select-all-vouchers">
                    </th>
                    @if (backpack_user()->hasRole('Super Admin'))
                        <th>{{ trans('backpack::crud.subkon.column.company') }}</th>
                    @endif
                    <th>{{ trans('backpack::crud.voucher.column.voucher.no_voucher.label') }}</th>
                    <th>{{ trans('backpack::crud.voucher.column.voucher.date_voucher.label') }}</th>
                    <th>A/N Rekening</th>
                    <th>{{ trans('backpack::crud.voucher.column.voucher.payment_transfer.label_2') }}</th>
                    <th>{{ trans('backpack::crud.voucher.column.voucher.payment_description.label') }}</th>
                    <th>{{ trans('backpack::crud.voucher.column.voucher.no_po_spk.label') }}</th>
                    <th>{{ trans('backpack::crud.voucher.column.voucher.due_date.label_2') }}</th>
                    <th>{{ trans('backpack::crud.voucher.column.voucher.payment_type.label') }}</th>
                    @if ($field['name'] == 'voucher_payment')
                        <th>{{ trans('backpack::crud.actions') }}</th>
                    @endif
                </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
    </div>

    {{-- Hidden input to store selected IDs for form submission --}}
    <div id="selected-vouchers-container-{{ $field_id }}"></div>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif

    @if ($field['name'] == 'voucher_payment')
        {{-- MODAL FOR INDIVIDUAL PAYMENT --}}
        <div class="modal fade" id="modal2" style="margin-top: 20px;" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="modal2Label">{{trans('backpack::crud.title_modal_voucher_payment')}}</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group col-md-12" element="div" bp-field-wrapper="true" bp-field-name="no_payment" bp-field-type="text" bp-section="crud-field">
                                <label>{{trans('backpack::crud.voucher.field.no_voucher.label')}}</label>
                                <div id="no_voucher_payment"></div>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group col-md-12" element="div" bp-field-wrapper="true" bp-field-name="no_payment" bp-field-type="text" bp-section="crud-field">
                                <label>{{trans('backpack::crud.voucher.field.payment_transfer.label')}}</label>
                                <div id="price_payment"></div>
                                <input type="hidden" name="id_voucher" id="id_voucher_payment">
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group col-md-12 required" element="div" bp-field-wrapper="true" bp-field-name="no_payment" bp-field-type="text" bp-section="crud-field">
                                <input type="hidden" class="form-control" name="date_payment">
                                <label>{{trans('backpack::crud.voucher_payment.field.payment_date.label')}}</label>
                                <div class="input-group date">
                                    <input 
                                        type="text" 
                                        id="date_payment" 
                                        class="form-control">
                                    <span class="input-group-text" id="basic-addon1"><span class="la la-calendar"></span></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="submit_manual_voucher_payment">Kirim Pembayaran</button>
                </div>
                </div>
            </div>
        </div>
    @endif
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
    @if ($field['name'] == 'voucher_payment')
        <script>
            SIAOPS.loadCSS("{{ asset('packages/bootstrap-datepicker/dist/css/bootstrap-datepicker3.css') }}");
            SIAOPS.loadScript([
                    "{{ asset('packages/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"
            ], {
                async: false,
                defer: true,
            });
            @if ($field_language !== 'en')
                SIAOPS.loadScript([
                    "{{ asset('packages/bootstrap-datepicker/dist/locales/bootstrap-datepicker.'.$field_language.'.min.js') }}"
                ], {
                    async: false,
                    defer: true,
                });
            @endif
        </script>
        <script>
            var dateFormat=function(){var a=/d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g,b=/\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g,c=/[^-+\dA-Z]/g,d=function(a,b){for(a=String(a),b=b||2;a.length<b;)a="0"+a;return a};return function(e,f,g){var h=dateFormat;if(1!=arguments.length||"[object String]"!=Object.prototype.toString.call(e)||/\d/.test(e)||(f=e,e=void 0),e=e?new Date(e):new Date,isNaN(e))throw SyntaxError("invalid date");f=String(h.masks[f]||f||h.masks.default),"UTC:"==f.slice(0,4)&&(f=f.slice(4),g=!0);var i=g?"getUTC":"get",j=e[i+"Date"](),k=e[i+"Day"](),l=e[i+"Month"](),m=e[i+"FullYear"](),n=e[i+"Hours"](),o=e[i+"Minutes"](),p=e[i+"Seconds"](),q=e[i+"Milliseconds"](),r=g?0:e.getTimezoneOffset(),s={d:j,dd:d(j),ddd:h.i18n.dayNames[k],dddd:h.i18n.dayNames[k+7],m:l+1,mm:d(l+1),mmm:h.i18n.monthNames[l],mmmm:h.i18n.monthNames[l+12],yy:String(m).slice(2),yyyy:m,h:n%12||12,hh:d(n%12||12),H:n,HH:d(n),M:o,MM:d(o),s:p,ss:d(p),l:d(q,3),L:d(q>99?Math.round(q/10):q),t:n<12?"a":"p",tt:n<12?"am":"pm",T:n<12?"A":"P",TT:n<12?"AM":"PM",Z:g?"UTC":(String(e).match(b)||[""]).pop().replace(c,""),o:(r>0?"-":"+")+d(100*Math.floor(Math.abs(r)/60)+Math.abs(r)%60,4),S:["th","st","nd","rd"][j%10>3?0:(j%100-j%10!=10)*j%10]};return f.replace(a,function(a){return a in s?s[a]:a.slice(1,a.length-1)})}}();dateFormat.masks={default:"ddd mmm dd yyyy HH:MM:ss",shortDate:"m/d/yy",mediumDate:"mmm d, yyyy",longDate:"mmmm d, yyyy",fullDate:"dddd, mmmm d, yyyy",shortTime:"h:MM TT",mediumTime:"h:MM:ss TT",longTime:"h:MM:ss TT Z",isoDate:"yyyy-mm-dd",isoTime:"HH:MM:ss",isoDateTime:"yyyy-mm-dd'T'HH:MM:ss",isoUtcDateTime:"UTC:yyyy-mm-dd'T'HH:MM:ss'Z'"},dateFormat.i18n={dayNames:["Sun","Mon","Tue","Wed","Thu","Fri","Sat","Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],monthNames:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec","January","February","March","April","May","June","July","August","September","October","November","December"]},Date.prototype.format=function(a,b){return dateFormat(this,a,b)};
            
            async function bpFieldInitDatePickerElement(element) {
                await new Promise((resolve) => { setTimeout(() => { resolve(1); }, 100); });
                var $fake = element;
                if (jQuery.ui) {
                    var datepicker = $.fn.datepicker.noConflict();
                    $.fn.bootstrapDP = datepicker;
                } else {
                    $.fn.bootstrapDP = $.fn.datepicker;
                }
                $field = $fake.closest('.input-group').parent().children('input[type="hidden"]');
                $customConfig = $.extend({ format: 'dd/mm/yyyy', autoclose: true, }, $fake.data('bs-datepicker'));
                $picker = $fake.bootstrapDP($customConfig);
                $picker.on('show hide change', function(e){
                    var sqlDate = e.date ? e.format('yyyy-mm-dd') : null;
                    if (!sqlDate) {
                        try {
                            sqlDate = $fake.val();
                            if( $customConfig.format === 'dd/mm/yyyy' && sqlDate ){
                                sqlDate = new Date(sqlDate.split('/')[2], sqlDate.split('/')[1] - 1, sqlDate.split('/')[0]).format('yyyy-mm-dd');
                            }
                        } catch(e) {}
                    }
                    $fake.closest('.input-group').parent().children('input[name="date_payment"]').val(sqlDate);
                });
            }
        </script>
    @endif

    <script>
        $(document).ready(function() {
            @if ($field['name'] == 'voucher_payment')
                bpFieldInitDatePickerElement($('#date_payment'));
            @endif

            let fieldId = "{{ $field_id }}";
            let fieldName = "{{ $field['name'] }}";
            let selectedVouchers = new Set();
            let $container = $('#selected-vouchers-container-' + fieldId);

            $('#' + fieldId).closest('.modal-dialog').addClass('modal-xl');

            function updateHiddenInputs() {
                $container.empty();
                selectedVouchers.forEach(id => {
                    $container.append(`<input type="hidden" name="voucher[]" value="${id}">`);
                });
            }

            let datatable_columns = [
                { 
                    data: null, 
                    orderable: false, 
                    searchable: false,
                    render: function (data, type, row, meta) {
                        return meta.row + meta.settings._iDisplayStart + 1;
                    }
                },
                { 
                    data: 'id', 
                    orderable: false, 
                    searchable: false,
                    render: function(data, type, row) {
                        let checked = selectedVouchers.has(data.toString()) ? 'checked' : '';
                        return `<input type="checkbox" class="form-check-input voucher-checkbox" value="${data}" ${checked}>`;
                    }
                },
                @if (backpack_user()->hasRole('Super Admin'))
                { data: 'company_name', orderable: false },
                @endif
                { data: 'no_voucher', orderable: false },
                { data: 'date_voucher', name: 'date_voucher', orderable: true },
                { data: 'subkon_name', orderable: false },
                { data: 'payment_transfer', orderable: false },
                { 
                    data: 'payment_description',
                    orderable: false,
                    render: function(data, type, row) {
                        return `<div style="white-space: normal; min-width: 300px;">${data || ''}</div>`;
                    }
                },
                { data: 'reference_no', orderable: false },
                { data: 'due_date', orderable: false },
                { data: 'payment_type', name: 'payment_type', orderable: true }
            ];

            if (fieldName === 'voucher_payment') {
                datatable_columns.push({ 
                    data: null,
                    orderable: false,
                    searchable: false,
                    render: function(data, type, row) {
                        return `<a href="javascript:void(0)" onclick="goPaymentAjax('${row.no_voucher}', '${row.payment_transfer}', '${row.id}')" bp-button="update" class="btn btn-sm btn-primary">
                                    <i class="la la-send"></i>
                                </a>`;
                    }
                });
            }

            let table = $('#' + fieldId).DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ url($crud->route.'/datatable-voucher') }}",
                    type: "GET"
                },
                columns: datatable_columns,
                order: [[3, 'desc']],
                drawCallback: function() {
                    let totalCheckboxes = $('.voucher-checkbox').length;
                    let checkedCheckboxes = $('.voucher-checkbox:checked').length;
                    $('.select-all-vouchers').prop('checked', totalCheckboxes > 0 && totalCheckboxes === checkedCheckboxes);
                }
            });

            $(document).on('change', '.voucher-checkbox', function() {
                let id = $(this).val();
                if ($(this).is(':checked')) { selectedVouchers.add(id); } else { selectedVouchers.delete(id); }
                updateHiddenInputs();
            });

            $('.select-all-vouchers').on('change', function() {
                let isChecked = $(this).is(':checked');
                $('.voucher-checkbox').each(function() {
                    $(this).prop('checked', isChecked);
                    let id = $(this).val();
                    if (isChecked) { selectedVouchers.add(id); } else { selectedVouchers.delete(id); }
                });
                updateHiddenInputs();
            });

            @if ($field['name'] == 'voucher_payment')
                // Global function for the Action button
                window.goPaymentAjax = function(no_voucher, price, id) {
                    $('#no_voucher_payment').html(no_voucher);
                    $('#price_payment').html(price);
                    $('#id_voucher_payment').val(id);
                    $('#date_payment').val("");
                    $('input[name="date_payment"]').val("");
                    
                    let modal2 = new bootstrap.Modal(document.getElementById('modal2'), { backdrop: false });
                    modal2.show();

                    let backdrop = document.createElement('div');
                    backdrop.className = 'modal-backdrop fade show second-backdrop';
                    document.body.appendChild(backdrop);
                    document.getElementById('modal2').addEventListener('hidden.bs.modal', function () {
                        backdrop.remove();
                    }, { once: true });
                };

                $('#submit_manual_voucher_payment').on('click', function(e){
                    setLoadingButton("#submit_manual_voucher_payment", true);
                    $.ajax({
                        url: "{{ url($crud->route.'/single-store') }}",
                        type: 'POST',
                        data: {
                            id: $('#id_voucher_payment').val(),
                            date: $('input[name="date_payment"]').val(),
                        },
                        success: function (data) {
                            setLoadingButton("#submit_manual_voucher_payment", false);
                            if(data.success){
                                swal({ title: "Success", text: "{!! trans('backpack::crud.insert_success') !!}", icon: "success", timer: 4000, buttons: false, });
                                $('#modal2').modal('hide');
                                if(window.crud && window.crud.table){ window.crud.table.ajax.reload(); }
                                table.ajax.reload(); // Reload the internal table
                                if(data.events){
                                    forEachFlexible(data.events, function(eventname, data){ eventEmitter.emit(eventname, data); });
                                }
                            } else {
                                swal({ title: "Error", text: data.error, icon: "error", timer: 4000, buttons: false, });
                            }
                        },
                        error: function (xhr) {
                            setLoadingButton("#submit_manual_voucher_payment", false);
                            swal({ title: "Error", text: "{{ trans('backpack::crud.insert_error') }}", icon: "error", timer: 4000, buttons: false, });
                        }
                    });
                });
            @endif
        });
    </script>
@endpush
