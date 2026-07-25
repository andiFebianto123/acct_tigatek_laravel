<div>
    <p style="font-size: 15px;">
        <span style="">{{trans('backpack::crud.modal.bank_name')}}</span>: <span class="bank_name fw-bold"></span> |
        <span>{{ trans('backpack::crud.modal.no_account') }} :</span> <span class="no_account fw-bold"></span>
    </p>
    <p class="mb-4">
        <strong><span style="font-size: 20px;">{{ trans('backpack::crud.cash_account.field_transfer.balance.label') }}</span></strong>
        <span class="total_saldo fs-4 fw-bold text-dark"></span>
    </p>
    <div class="col-md-12">
        <form action="{{ url($crud->route) }}-move-transaction" id="form-transfer-balance-cast-account">
            <div class="row">
                <input type="hidden" name="cast_account_id" class="cast_account_id">
                <input type="hidden" class="balance" name="balance">
                <div class="form-group col-md-6 required" element="div" bp-field-wrapper="true" bp-field-name="currency_code" bp-field-type="select_from_array" bp-section="crud-field">
                    <label>{{ trans('backpack::crud.client_quotation.field.currency_code.label') ?? 'Mata Uang' }}</label>
                    <select name="currency_code" id="transfer_currency_code" class="form-control form-select">
                        <option value="IDR">IDR (Rp)</option>
                        <option value="USD">USD ($)</option>
                    </select>
                </div>
                <div class="form-group col-md-6 required" element="div" bp-field-wrapper="true" bp-field-name="nominal_transaction" bp-field-type="mask_currency" bp-section="crud-field">
                    <label>{{trans('backpack::crud.cash_account.field_transfer.nominal_transfer.label')}}</label>
                    <div class="input-group">
                        <select name="nominal_transfer_currency" id="nominal_transfer_currency" class="form-select bg-light fw-bold currency-select-dropdown" tabindex="-1" style="width: auto; min-width: 90px; max-width: 115px; cursor: not-allowed; pointer-events: none; z-index: 2; padding-left: 0.75rem; padding-right: 0.75rem !important; font-size: 0.9rem; -webkit-appearance: none !important; -moz-appearance: none !important; appearance: none !important;">
                            <option value="IDR">IDR (Rp)</option>
                            <option value="USD">USD ($)</option>
                        </select>
                        <input
                            type="text"
                            data-alt="nominal_transaction_masked"
                            value="" placeholder="000.000"
                            id="nominal_transfer_masked"
                            class="form-control"
                            data-initialized="true" maxlength="23">
                    </div>
                    <input type="hidden" name="nominal_transfer" id="nominal_transaction" value="">
                </div>
                <div class="form-group col-md-6" element="div" bp-field-wrapper="true" bp-field-name="description" bp-field-type="textarea" bp-section="crud-field">
                    <label>{{trans('backpack::crud.cash_account.field_transfer.description.label')}}</label>
                    <textarea name="description" placeholder="{{trans('backpack::crud.cash_account.field_transfer.description.placeholder')}}" class="form-control"></textarea>
                </div>
                <div class="form-group col-md-6">
                </div>
                <div class="form-group col-md-6">
                    <input type="hidden" class="form-control" name="date_move_balance">
                    <label>
                        {{trans('backpack::crud.cash_account.field_transfer.date.label')}}
                    </label>
                    <div class="input-group date">
                        <input
                            id="date_move_balance"
                            data-bs-name="date_move_balance"
                            type="text"
                            data-init-datepicker="bpFieldInitDatePickerElement"
                            @include('crud::fields.inc.attributes')
                            >
                        <span class="input-group-text" id="basic-addon1"><span class="la la-calendar"></span></span>
                    </div>
                </div>
                <script>
                    SIAOPS.loadCSS("{{ asset('packages/bootstrap-datepicker/dist/css/bootstrap-datepicker3.css') }}");
                </script>
                <script>
                    SIAOPS.loadScript([
                        "{{ asset('packages/bootstrap-datepicker/dist/js/bootstrap-datepicker.min.js') }}"
                    ], {
                        async: false, // Load secara synchronous
                        defer: true,
                    });
                </script>
                <script>
                    var dateFormat=function(){var a=/d{1,4}|m{1,4}|yy(?:yy)?|([HhMsTt])\1?|[LloSZ]|"[^"]*"|'[^']*'/g,b=/\b(?:[PMCEA][SDP]T|(?:Pacific|Mountain|Central|Eastern|Atlantic) (?:Standard|Daylight|Prevailing) Time|(?:GMT|UTC)(?:[-+]\d{4})?)\b/g,c=/[^-+\dA-Z]/g,d=function(a,b){for(a=String(a),b=b||2;a.length<b;)a="0"+a;return a};return function(e,f,g){var h=dateFormat;if(1!=arguments.length||"[object String]"!=Object.prototype.toString.call(e)||/\d/.test(e)||(f=e,e=void 0),e=e?new Date(e):new Date,isNaN(e))throw SyntaxError("invalid date");f=String(h.masks[f]||f||h.masks.default),"UTC:"==f.slice(0,4)&&(f=f.slice(4),g=!0);var i=g?"getUTC":"get",j=e[i+"Date"](),k=e[i+"Day"](),l=e[i+"Month"](),m=e[i+"FullYear"](),n=e[i+"Hours"](),o=e[i+"Minutes"](),p=e[i+"Seconds"](),q=e[i+"Milliseconds"](),r=g?0:e.getTimezoneOffset(),s={d:j,dd:d(j),ddd:h.i18n.dayNames[k],dddd:h.i18n.dayNames[k+7],m:l+1,mm:d(l+1),mmm:h.i18n.monthNames[l],mmmm:h.i18n.monthNames[l+12],yy:String(m).slice(2),yyyy:m,h:n%12||12,hh:d(n%12||12),H:n,HH:d(n),M:o,MM:d(o),s:p,ss:d(p),l:d(q,3),L:d(q>99?Math.round(q/10):q),t:n<12?"a":"p",tt:n<12?"am":"pm",T:n<12?"A":"P",TT:n<12?"AM":"PM",Z:g?"UTC":(String(e).match(b)||[""]).pop().replace(c,""),o:(r>0?"-":"+")+d(100*Math.floor(Math.abs(r)/60)+Math.abs(r)%60,4),S:["th","st","nd","rd"][j%10>3?0:(j%100-j%10!=10)*j%10]};return f.replace(a,function(a){return a in s?s[a]:a.slice(1,a.length-1)})}}();dateFormat.masks={default:"ddd mmm dd yyyy HH:MM:ss",shortDate:"m/d/yy",mediumDate:"mmm d, yyyy",longDate:"mmmm d, yyyy",fullDate:"dddd, mmmm d, yyyy",shortTime:"h:MM TT",mediumTime:"h:MM:ss TT",longTime:"h:MM:ss TT Z",isoDate:"yyyy-mm-dd",isoTime:"HH:MM:ss",isoDateTime:"yyyy-mm-dd'T'HH:MM:ss",isoUtcDateTime:"UTC:yyyy-mm-dd'T'HH:MM:ss'Z'"},dateFormat.i18n={dayNames:["Sun","Mon","Tue","Wed","Thu","Fri","Sat","Sunday","Monday","Tuesday","Wednesday","Thursday","Friday","Saturday"],monthNames:["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec","January","February","March","April","May","June","July","August","September","October","November","December"]},Date.prototype.format=function(a,b){return dateFormat(this,a,b)};
                    async function bpFieldInitDatePickerElement(element) {
                        await new Promise((resolve) => {
                            setTimeout(() => {
                                resolve(1);
                            }, 100);
                        });
                        var $fake = element;
                        if (jQuery.ui) {
                                var datepicker = $.fn.datepicker.noConflict();
                                $.fn.bootstrapDP = datepicker;
                            } else {
                                $.fn.bootstrapDP = $.fn.datepicker;
                            }

                            $field = $fake.closest('.input-group').parent().children('input[type="hidden"]');

                            $customConfig = $.extend({
                                format: 'dd/mm/yyyy',
                            }, $fake.data('bs-datepicker'));
                            $picker = $fake.bootstrapDP($customConfig);

                            var $existingVal = $field.val();

                            if( $existingVal && $existingVal.length ){
                                var parts = $existingVal.split('-');
                                var year = parts[0];
                                var month = parts[1] - 1; // Date constructor expects a zero-indexed month
                                var day = parts[2];
                                preparedDate = new Date(year, month, day).format($customConfig.format);
                                $fake.val(preparedDate);
                                $picker.bootstrapDP('update', preparedDate);
                            }

                            $picker.on('show hide change', function(e){
                                if( e.date ){
                                    var sqlDate = e.format('yyyy-mm-dd');
                                } else {
                                    try {
                                        var sqlDate = $fake.val();

                                        if( $customConfig.format === 'dd/mm/yyyy' ){
                                            sqlDate = new Date(sqlDate.split('/')[2], sqlDate.split('/')[1] - 1, sqlDate.split('/')[0]).format('yyyy-mm-dd');
                                        }
                                    } catch(e){
                                        if( $fake.val() ){
                                                new Noty({
                                                    type: "error",
                                                    text: "<strong>Whoops!</strong><br>Sorry we did not recognise that date format, please make sure it uses a yyyy mm dd combination"
                                                }).show();
                                            }
                                        }
                                }
                                $field = $fake.closest('.input-group').parent().children('input[type="hidden"]');
                                $field.val(sqlDate);
                            });
                    }
                    $('input[data-init-datepicker]').each(function() {
                        var initFunction = $(this).data('init-datepicker');
                        window[initFunction]($(this));
                    });
                </script>
                <script>
                    SIAOPS.loadScript([
                        "{{ asset('packages/jquery-mask-plugin-master/dist/jquery.mask.min.js') }}"
                    ], {
                        async: false, // Load secara synchronous
                        defer: false,
                    });
                    function bpFieldInitMaskElement(element){
                        var $maskedInput = element;
                        var $hiddenInput = $('#nominal_transaction');
                        var $currencySelect = $('#nominal_transfer_currency');

                        function getCleanValue(val, curr) {
                            if (curr === 'USD') {
                                var parts = val.split('.');
                                if (parts.length > 2) {
                                    val = parts[0] + '.' + parts.slice(1).join('');
                                }
                                return val.replace(/[^\d.]/g, '');
                            }
                            return val.replace(/[^\d]/g, '');
                        }

                        function applyMask() {
                            var curr = $currencySelect.val() || 'IDR';
                            if (typeof $maskedInput.unmask === 'function') {
                                $maskedInput.unmask();
                            }
                            if (typeof $maskedInput.mask === 'function') {
                                if (curr === 'USD') {
                                    $maskedInput.mask('#,##0.00', { reverse: true });
                                } else {
                                    $maskedInput.mask('000.000.000.000.000', { reverse: true });
                                }
                            }
                            $hiddenInput.val(getCleanValue($maskedInput.val(), curr));
                        }

                        $maskedInput.off('input change keyup').on('input change keyup', function () {
                            var curr = $currencySelect.val() || 'IDR';
                            $hiddenInput.val(getCleanValue($(this).val(), curr));
                        });

                        $currencySelect.off('change').on('change', function() {
                            applyMask();
                        });

                        $(document).off('change', '#transfer_currency_code').on('change', '#transfer_currency_code', function() {
                            var curr = $(this).val() || 'IDR';
                            $('#nominal_transfer_currency').val(curr).trigger('change');
                        });

                        applyMask();
                    }
                    bpFieldInitMaskElement($('#nominal_transfer_masked'));
                </script>
                <script>
                    SIAOPS.loadCSS("{{ asset('packages/select2/dist/css/select2.min.css') }}");
                    SIAOPS.loadCSS("{{ asset('packages/select2-bootstrap-theme/dist/select2-bootstrap.min.css') }}");
                    SIAOPS.loadScript([
                        "{{ asset('packages/select2/dist/js/select2.full.min.js') }}"
                    ], {
                        async: false,
                        defer: true,
                    });
                </script>
                <div class="col-md-6"></div>
                <div class="form-group col-md-6 required" element="div" bp-field-wrapper="true" bp-field-name="status" bp-field-type="select_from_array" bp-section="crud-field">
                    <label>{{trans("backpack::crud.cash_account.field_transfer.to_account.label")}}</label>
                    <select name="to_account" class="form-control form-select">
                    </select>
                </div>
            </div>
        </form>
    </div>
</div>


@push('after_scripts')
    @once
        <script>
            SIAOPS.setAttribute("{{$name}}", function(){
                return {
                    dom: $('#{{$name}}'),
                    route: "{{url($crud->route)}}",
                    eventLoader: function(){
                        // event when create success
                        // eventEmitter.on("{{$name}}_create_success", function(data){
                        //     $('#{{$name}} .saldo-str').html(data.new_saldo);
                        // });

                        // event when delete success
                        // eventEmitter.on("{{$name}}_delete_success", function(data){
                        //     $('#{{$name}} .saldo-str').html(data.new_saldo);
                        // });

                        // event when update success
                        // eventEmitter.on("{{$name}}_update_success", function(data){
                        //     $('#{{$name}} .saldo-str').html(data.new_saldo);
                        // });
                    },
                    show: function(){
                        var instance = this;
                        return this;
                    },
                    loadData: function(id){
                        var instance = this;
                        $.ajax({
                            url: instance.route+'-show?_id='+id,
                            type: 'GET',
                            typeData: 'json',
                            success: function (response) {
                                instance.refreshBody(response);
                            },
                            error: function (xhr, status, error) {
                                console.error(xhr);
                                alert('An error occurred while loading the create form.');
                            }
                        });
                        return this;
                    },
                    refreshBody: function(data){
                        var header = data.result.cast_account;
                        $("#{{$name}} .modal-title").html("{{trans('backpack::crud.modal.title_transfer_balance')}}");
                        $("#{{$name}} .bank_name").html(header.bank_name);
                        $('#{{$name}} .no_account').html(header.no_account);
                        $('#{{$name}} .total_saldo').html(header.total_saldo_str);

                        $('#{{$name}} .cast_account_id').val(header.id);

                        $('#{{$name}} #form-transfer-balance-cast-account .balance').val(header.total_saldo);
                    },
                    createMoveTransfer:function(){
                        var url = $('#form-transfer-balance-cast-account').attr('action');
                        var formData = new FormData($('#form-transfer-balance-cast-account')[0]);
                        normalizeShowMessage('form-transfer-balance-cast-account');
                        setLoadingButton('.btn-transfer-balance', true);
                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            typeData: 'json',
                            success: function (data) {
                                setLoadingButton('.btn-transfer-balance', false);
                                if(data.success){
                                    $('#form-transfer-balance-cast-account')[0].reset();
                                    if($('#date_move_balance').length){
                                        $('#date_move_balance').datepicker('clearDates');
                                    }
                                    swal({
                                        title: "{{ trans('backpack::crud.reorder_success_title') }}",
                                        text: "{!! trans('backpack::crud.insert_success') !!}",
                                        icon: "success",
                                        timer: 4000,
                                        buttons: false,
                                    });
                                    // $('#{{$name}}').modal('hide');
                                    $('.btn-close').click();
                                    if(window.crud){
                                        window.crud.table.ajax.reload();
                                    }
                                    if(data.events){
                                        forEachFlexible(data.events, function(eventname, data){
                                            eventEmitter.emit(eventname, data);
                                        });
                                    }
                                }else{
                                    swal({
                                        title: "{!! trans('backpack::crud.delete_confirmation_not_title') !!}",
                                        text: data.error,
                                        icon: "error",
                                        timer: 4000,
                                        buttons: false,
                                    });
                                }
                            },
                            error: function (xhr, status, error) {
                                // console.error(xhr);
                                setLoadingButton('.btn-transfer-balance', false);
                                errorShowMessage('form-transfer-balance-cast-account', xhr.responseJSON.errors);
                            }
                        });
                    },
                    load:function(){
                        var instance = this;
                        const myModalEl = document.getElementById('{{$name}}');
                        myModalEl.addEventListener('show.bs.modal', (event) => {
                            var $select = $('#form-transfer-balance-cast-account select[name="to_account"]');
                            if (!$select.hasClass("select2-hidden-accessible")) {
                                $select.select2({
                                    theme: "bootstrap",
                                    dropdownParent: $('#{{$name}}')
                                });
                            }

                           // load select2
                            normalizeShowMessage('form-transfer-balance-cast-account');
                            $.ajax({
                                url: instance.route+'-select-to-account',
                                type: 'GET',
                                typeData: 'json',
                                success: function (response) {
                                    // var option = `<option value="" selected="">{{trans("backpack::crud.cash_account.field_transfer.to_account.placeholder")}}</option>`;
                                    var option = "<option value=''>{{ trans('backpack::crud.modal.choose_account') }}</option>";
                                    response.result.forEach((value) => {
                                        option += `<option value='${value.id}'>${value.name}</option>`;
                                    });
                                    $select.html(option).trigger('change');
                                },
                                error: function (xhr, status, error) {
                                    console.error(xhr);
                                    alert('An error occurred while loading the create form.');
                                }
                            });
                        });
                        $('.btn-transfer-balance').off('click').click(function(){
                            instance.createMoveTransfer();
                        });
                    }
                }
            });
            window.addEventListener('load', function () {
                SIAOPS.getAttribute("{{$name}}").load();
            });
        </script>
    @endonce
@endpush
