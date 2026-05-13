<div>
    <div><h5>{{trans('backpack::crud.card.cast_account_card.name_rekening')}} : {{ $detail->name }}</h5></div>
    <div class="d-flex justify-content-between">
        <div class="left-buttons d-flex gap-2">
            {{ trans('backpack::crud.card.cast_account_card.name_bank') }} : <b>{{ $detail->bank_name }}</b> | {{ trans("backpack::crud.card.cast_account_card.no_rekening") }} : <b>{{$detail->no_account}}</b>
        </div>
        <div class="right-buttons d-flex gap-2">
            @if ($crud->hasAccess('create'))
                <button
                id="btn-{{$name}}-edit"
                data-route="{{$route_edit}}"
                data-bs-toggle="modal"
                data-bs-target="#modalEdit"
                data-title="Ubah Nama Rekening"
                class="btn btn-sm btn-primary"><i class="la la-edit"></i> Edit Nama Rekening</button>
                @push('after_scripts')
                    <script>
                        $(function(){
                            $("#btn-{{$name}}-edit").unbind('click').on('click', function (e) {
                                e.preventDefault();
                                var route = "{!! $route_edit !!}";
                                OpenEditFormModal({
                                    route: route,
                                    modal: {
                                        id: '#modalEdit',
                                        title: "Ubah Nama Rekening",
                                        action: "{!! $route_update !!}",
                                    }
                                });
                            });
                        });
                    </script>
                @endpush
            @endif
            @if ($detail->status != \App\Models\CastAccount::LOAN)
                <button class="btn btn-sm btn-secondary"
                id="btn-{{$name}}-info"
                data-bs-toggle="modal"
                data-bs-target="#modal_info_cast_account"
                ><i class="la la-eye"></i></button>
            @endif

            @if ($detail->status == \App\Models\CastAccount::LOAN)
                @if ($crud->hasAccess('create'))
                    <button
                    id="btn-{{$name}}-move-balance"
                    data-route="{{url($crud->route.'/create?_id='.$detail->id."&type=move")}}"
                    data-bs-toggle="modal"
                    data-bs-target="#modalCreate"
                    data-title="{{ trans('backpack::crud.modal.transfer_balance') }}"
                    class="btn btn-sm btn-primary"><i class="la la-hand-holding-usd"></i></button>
                @endif
            @else
                @if ($access->where('id', 3)->first())
                    @if ($crud->hasAccess('create'))
                        <button
                            id="btn-{{$name}}-transfer-balance"
                            data-bs-toggle="modal"
                            data-bs-target="#modal_transfer_balance"
                            class="btn btn-sm btn-primary">
                            <i class="la la-exchange-alt"></i></button>
                    @endif
                @endif
            @endif
            @if ($crud->hasAccessToAll(['create', 'delete']))
                @if ($detail->status == \App\Models\CastAccount::LOAN)
                    <button
                        id="btn-{{$name}}-add"
                        data-route="{{url($crud->route.'/create?_id='.$detail->id)}}"
                        data-bs-toggle="modal"
                        data-bs-target="#modalCreate"
                        data-title="{{ trans('backpack::crud.add').' '.trans('backpack::crud.cash_account.title_modal_create_transaction_loan') }}"
                        class="btn btn-sm btn-primary"><i class="la la-plus"></i></button>
                @else
                    <button
                        id="btn-{{$name}}-add"
                        data-route="{{url($crud->route.'/create?_id='.$detail->id)}}"
                        data-bs-toggle="modal"
                        data-bs-target="#modalCreate"
                        data-title="{{ trans('backpack::crud.add').' '.trans('backpack::crud.cash_account.title_modal_create_transaction') }}"
                        class="btn btn-sm btn-primary"><i class="la la-plus"></i></button>
                @endif
                <button
                    id="btn-{{$name}}"
                    {{-- onclick="deleteEntryCardAccount(this)" --}}
                    data-route="{{ url($crud->route.'/'.$detail->id) }}"
                    data-name="{{ $detail->name }}"
                    data-namecard="{{$name}}"
                    data-button-type="delete"
                    data-bs-toggle="modal"
                    data-bs-target="#modalDelete"
                    class="btn btn-sm btn-danger">
                    <i class="la la-trash"></i>
                </button>
            @endif
        </div>
    </div>
    <div>
        @if ($detail->status == \App\Models\CastAccount::LOAN)
            {{ trans('backpack::crud.cash_account_loan.field.account.label') }} : <b>{{ $detail->account->code }} - {{ $detail->account->name }}</b><br>
        @endif
        <strong>{{ trans('backpack::crud.card.cast_account_card.balance') }} : </strong><span class="saldo-str">{!! \App\Http\Helpers\CustomHelper::formatRupiahWithCurrency($detail->saldo) !!}</span>
    </div>
    @if($detail->status == \App\Models\CastAccount::LOAN)
    <div>
        <center>
            <table class="info-cast-account table text-center">
                <thead class="text-center">
                    <tr>
                    <th>{{trans('backpack::crud.cash_account.field_transaction.kode.label')}}</th>
                    <th>{{trans('backpack::crud.cash_account.field_transaction.date.label')}}</th>
                    <th>{{trans('backpack::crud.cash_account.field_transaction.loan.label')}}</th>
                    <th>{{trans('backpack::crud.cash_account.field_transaction.nominal.label')}}</th>
                    <th>{{trans('backpack::crud.cash_account.field_transaction.description.label')}}</th>
                    <th>{{trans('backpack::crud.cash_account.field_transaction.status_loan.label')}}</th>
                    </tr>
                </thead>
                <tbody class="text-center">
                </tbody>
            </table>

            <!-- Load More Section -->
            <div class="load-more-wrapper text-center" style="display:none;">
                <button class="btn btn-outline-primary btn-load-more">
                    <span class="btn-text"><i class="la la-angle-down"></i> {{trans('backpack::crud.card.cast_account_card.btn_load_more')}}</span>
                    <i class="la la-spinner la-spin d-none btn-spinner"></i>
                </button>
                <div class="mt-2 loaded-info">
                    {{trans('backpack::crud.card.cast_account_card.loaded_count')}} <span class="loaded-count">0</span> {{trans('backpack::crud.card.cast_account_card.total_count')}} <span class="total-count">0</span> {{trans('backpack::crud.card.cast_account_card.transaction')}}
                </div>
            </div>
        </center>
    </div>
    @endif
</div>

@push('inline_scripts')
    @once
        <style>
            .saldo-str {
                font-size: 20px;
                font-weight: 700;
                padding-top: 200px;
            }
        </style>
        <style>
            .info-cast-account tbody,
            .info-cast-account td,
            .info-cast-account tfoot,
            .info-cast-account th,
            .info-cast-account thead,
            .info-cast-account tr {
                border-color: transparent;
            }

            .infor-cast-account {
                width: auto;
                table-layout: auto;
            }
            .info-cast-account th, .info-cast-account td{
                /* text-align: left; */
                white-space: nowrap;
            }

            /* Load More Stylings */
            .load-more-wrapper {
                margin-top: 20px;
                margin-bottom: 10px;
                padding: 10px;
                border-top: 1px solid rgba(0,0,0,0.05);
                width: 100%;
            }
            .btn-load-more {
                padding: 6px 20px;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                border-radius: 50px;
                transition: all 0.3s ease;
                box-shadow: 0 2px 5px rgba(0,0,0,0.05);
                border: 1px solid #007bff !important; /* Tegaskan border di sini */
            }
            .btn-load-more:hover {
                transform: translateY(-1px);
                border: 1px solid #007bff !important; /* Pastikan border tetap 1px solid saat hover */
            }

            .loaded-info {
                font-size: 11px;
                color: #888;
                font-weight: 500;
            }
        </style>
    @endonce
@endpush

@push('after_scripts')
    <script>
        SIAOPS.setAttribute("{{$name}}", function(){
            return {
                id: "{{$detail->id}}",
                btnDelete:$("#btn-{{$name}}"),
                route: "{{url($crud->route)}}",
                btnAdd: $("#btn-{{$name}}-add"),
                @if($detail->status == \App\Models\CastAccount::LOAN)
                    currentPage: 1,
                    perPage: 10,
                    loadedCount: 0,

                    loadTransactionLoan: function(append = false){
                        var instance = this;
                        if (!append) instance.currentPage = 1;

                        var $btn = $('#{{$name}} .btn-load-more');
                        var $text = $btn.find('.btn-text');
                        var $spinner = $btn.find('.btn-spinner');

                        // Start Loading State
                        $btn.prop('disabled', true);
                        $text.addClass('d-none');
                        $spinner.removeClass('d-none');

                        var urlParams = new URLSearchParams(window.location.search);
                        var filterYear = urlParams.get('filter_year') || 'all';

                        $.ajax({
                            url: instance.route+'-show?_id='+instance.id+'&page='+instance.currentPage+'&per_page='+instance.perPage+'&filter_year='+filterYear,
                            type: 'GET',
                            typeData: 'json',
                            success: function (data) {
                                // End Loading State
                                $btn.prop('disabled', false);
                                $text.removeClass('d-none');
                                $spinner.addClass('d-none');

                                var table = $("#{{$name}} .info-cast-account tbody");
                                if (!append) {
                                    table.empty();
                                    instance.loadedCount = 0;
                                }

                                forEachFlexible(data.result.detail, function(key, value){
                                    table.append(`
                                    <tr>
                                        <td>${value.kode_str}</td>
                                        <td>${value.date_str}</td>
                                        <td>${value.loan_str}</td>
                                        <td>${value.nominal_str}</td>
                                        <td>${value.description}</td>
                                        <td>${value.status_str}</td>
                                    </tr>`);
                                    instance.loadedCount++;
                                });

                                $('#{{$name}} .loaded-count').text(instance.loadedCount);
                                $('#{{$name}} .total-count').text(data.result.total);

                                if (data.result.has_more) {
                                    $('#{{$name}} .load-more-wrapper').show();
                                } else {
                                    $('#{{$name}} .load-more-wrapper').hide();
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error(xhr);
                                $btn.prop('disabled', false);
                                $text.removeClass('d-none');
                                $spinner.addClass('d-none');
                                alert('Terjadi kesalahan saat memuat data.');
                            }
                        });
                    },
                @endif

                deleteEntryCardAccount: function(button) {
                    // ask for confirmation before deleting an item
                    // e.preventDefault();
                    var route = $(button).attr('data-route');
                    var title = $(button).data('name');

                    var nameCard = $(button).data('namecard');

                    var textBody = "{{ trans('backpack::crud.delete_confirm_2') }} "+title+' ?';

                    $('#modalDelete .modal-body').html(textBody);

                    $('#btn-delete').off('click').on('click', function(e){

                        var btn = $(this);
                        btn.attr('disabled', true);
                        btn.find('.btn-text').html("Loading...");
                        btn.find('.btn-spinner').removeClass('d-none');
                        $.ajax({
                            url: route,
                            type: 'DELETE',
                            success: function(result) {
                                btn.attr('disabled', false);
                                btn.find('.btn-text').html("{{ trans('backpack::crud.delete') }}");
                                btn.find('.btn-spinner').addClass('d-none');

                                hideModal('modalDelete');

                                if (result == 1) {
                                    // Redraw the table
                                    if (typeof crud != 'undefined' && typeof crud.table != 'undefined') {
                                        // Move to previous page in case of deleting the only item in table
                                        if(crud.table.rows().count() === 1) {
                                        crud.table.page("previous");
                                        }

                                        crud.table.draw(false);
                                    }

                                    // Show a success notification bubble
                                    new Noty({
                                    type: "success",
                                    text: "{!! '<strong>'.trans('backpack::crud.delete_confirmation_title').'</strong><br>'.trans('backpack::crud.delete_confirmation_message') !!}"
                                    }).show();

                                    // Hide the modal, if any
                                    $('.modal').modal('hide');
                                    $('#'+nameCard).hide();
                                } else {
                                    // if the result is an array, it means
                                    // we have notification bubbles to show
                                    if (result instanceof Object) {
                                    // trigger one or more bubble notifications
                                    Object.entries(result).forEach(function(entry, index) {
                                        var type = entry[0];
                                        // entry[1].forEach(function(message, i) {
                                        //     new Noty({
                                        //     type: type,
                                        //     text: message
                                        //     }).show();
                                        // });
                                        if(type != 'events'){
                                            entry[1].forEach(function(message, i) {
                                                swal({
                                                    title: "Success",
                                                    text: message,
                                                    icon: "success",
                                                    timer: 4000,
                                                    buttons: false,
                                                });

                                            });
                                        }
                                    });
                                    if(result.events){
                                        forEachFlexible(result.events, function(eventname, data){
                                            eventEmitter.emit(eventname, data);
                                        });
                                    }
                                    } else {// Show an error alert
                                        swal({
                                        title: "{!! trans('backpack::crud.delete_confirmation_not_title') !!}",
                                        text: "{!! trans('backpack::crud.delete_confirmation_not_message') !!}",
                                        icon: "error",
                                        timer: 4000,
                                        buttons: false,
                                        });
                                    }
                                }
                            },
                            error: function(result) {
                                btn.attr('disabled', false);
                                btn.find('.btn-text').html("{{ trans('backpack::crud.delete') }}");
                                btn.find('btn-spinner').addClass('d-none');
                                // Show an alert with the result
                                swal({
                                title: "{!! trans('backpack::crud.delete_confirmation_not_title') !!}",
                                text: "{!! trans('backpack::crud.delete_confirmation_not_message') !!}",
                                icon: "error",
                                timer: 4000,
                                buttons: false,
                                });
                            }
                        });
                    });

                },
                eventLoader: function(){
                    // event when create success

                    eventEmitter.on("cast_account_store_success", function(){
                        window.location.href = location.href;
                    });

                    eventEmitter.on("{{$name}}_create_success", function(data){
                        $('#{{$name}} .saldo-str').html(data.new_saldo);
                    });

                    @if($detail->status == \App\Models\CastAccount::LOAN)
                        eventEmitter.on("{{$name}}_store_move_success", function(data){
                            $('#{{$name}} .saldo-str').html(data.saldo);
                             SIAOPS.getAttribute("{{$name}}").loadTransactionLoan();
                        });
                    @endif

                    // event when delete success
                    // eventEmitter.on("{{$name}}_delete_success", function(data){
                    //     $('#{{$name}} .saldo-str').html(data.new_saldo);
                    // });

                    // event when update success
                    // eventEmitter.on("{{$name}}_update_success", function(data){
                    //     $('#{{$name}} .saldo-str').html(data.new_saldo);
                    // });
                },
                load:function(){
                    var instance = this;

                    // load event for component
                    instance.eventLoader();

                    $(instance.btnDelete).off('click').click(function(e){
                        e.preventDefault();
                        instance.deleteEntryCardAccount(this);
                    });

                    $(instance.btnAdd).off('click').click(function(e){
                        e.preventDefault();
                        var btn = $(this);
                        OpenCreateFormModal({
                            route: btn.data('route'),
                            modal: {
                                id: '#modalCreate',
                                title: btn.data('title'),
                                action: instance.route+'-transaction'
                            }
                        });
                    });

                    $('#btn-{{$name}}-move-balance').off('click').click(function(e){
                        e.preventDefault();
                        var btn = $(this);
                        OpenCreateFormModal({
                            route: btn.data('route'),
                            modal: {
                                id: '#modalCreate',
                                title: btn.data('title'),
                                action: instance.route+'-move-transaction'
                            }
                        });
                    });

                    $("#btn-{{$name}}-info").off('click').click(function(e){
                        SIAOPS.getAttribute('modal_info_cast_account')
                        .loadData(instance.id);
                    });

                    $("#btn-{{$name}}-transfer-balance").off('click').click(function(e){
                        SIAOPS.getAttribute('modal_transfer_balance')
                        .loadData(instance.id);
                    });

                    if(instance.loadTransactionLoan){
                        instance.loadTransactionLoan();
                    }

                    // Event handler Load More
                    $('#{{$name}} .btn-load-more').off('click').click(function(e){
                        e.preventDefault();
                        instance.currentPage++;
                        instance.loadTransactionLoan(true);
                    });

                }
            }
        });
        window.addEventListener('load', function () {
            SIAOPS.getAttribute("{{$name}}").load();
        });
    </script>
@endpush
