<div>
    <div class="d-flex justify-content-between align-items-center mb-2">
        <h5 class="mb-0">{{trans('backpack::crud.profit_lost.consolidation_income_statement')}}</h5>
        <div class="d-flex align-items-center gap-2">
            <select id="filter-company-{{$name}}" class="form-select form-select-sm" style="width: auto; min-width: 180px;">
                @if(isset($canAccessAllCompanies) && $canAccessAllCompanies)
                    <option value="all" selected>{{ trans('backpack::crud.filter.all_company') ?? 'Semua Perusahaan' }}</option>
                @endif
                @if(isset($companies))
                    @foreach($companies as $company)
                        <option value="{{ $company->id }}">{{ $company->name }}</option>
                    @endforeach
                @endif
            </select>
            <button id="btn-export-consolidation-pdf" class="btn btn-sm btn-primary">
                <i class="la la-file-download"></i> PDF
            </button>
            <button id="btn-export-consolidation-excel" class="btn btn-sm btn-primary">
                <i class="la la-file-download"></i> Excel
            </button>
        </div>
    </div>
    <div class="table-responsive">
        <table id="table-account-{{$name}}" class="info-cast-account table">
            <thead class="text-left">
                <tr>
                <th style="width: 40%;">{{-- trans('backpack::crud.expense_account.column.code') --}}</th>
                <th style="width: 10%;">{{-- trans('backpack::crud.expense_account.column.name') --}}</th>
                <th style="width: 20%;">{{-- trans('backpack::crud.expense_account.column.balance') --}}</th>
                <th>{{-- trans('backpack::crud.expense_account.column.action') --}}</th>
                </tr>
            </thead>
            <tbody class="text-left">

            </tbody>
        </table>
    </div>

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
            .info-cast-account th:nth-child(1),
            .info-cast-account td:nth-child(1) { width: 15%; }

            .info-cast-account th:nth-child(2),
            .info-cast-account td:nth-child(2) { width: 45%; }

            .info-cast-account th:nth-child(3),
            .info-cast-account td:nth-child(3) { width: 25%; }

            .info-cast-account th:nth-child(4),
            .info-cast-account td:nth-child(4) { width: 15%; }
        </style>
        <style>
            .btn-danger {
                background-color: #e55353 !important;
            }
        </style>
    @endonce
@endpush

@push('after_scripts')
    <script>
        if(SIAOPS.getAttribute('accounts') == null){
            SIAOPS.setAttribute('accounts', function(){
                return {
                    name: 'accounts',
                    accounts_compact:[],
                    eventLoader: async function(){
                        eventEmitter.on("account_create_success", async function(data){
                            if(data.component_name != undefined){
                                await SIAOPS.getAttribute(data.component_name).load();
                            }else{
                                window.location.href = location.href;
                            }
                        });
                    },
                    addAccount: function(instanceAccount){
                        var instance = this;
                        instance.accounts_compact.push(instanceAccount);
                    },
                    load:async function(){
                        this.eventLoader();
                        for (const callAccount of this.accounts_compact) {
                            await callAccount.load();
                        }
                    }
                }
            });
        }

        SIAOPS.setAttribute("{{$name}}", function(){
            return {
                name: "{{$name}}",
                url: "{{$route}}",
                table: "table-account-{{$name}}",
                load: async function(){
                    var instance = this;
                    var selectedCompany = $('#filter-company-{{$name}}').val();
                    return new Promise((resolve, reject) => {
                        $.ajax({
                            url: instance.url,
                            data: {
                                filter_year: filter_tables.filter_year,
                                company_id: selectedCompany,
                            },
                            type: 'GET',
                            typeData: 'json',
                            success: function (result) {
                                $('#'+instance.table+' tbody').empty();
                                var html = '';
                                result.forEach(function(item){
                                    html += `<tr>
                                        <td><strong>${item.name}</strong></td>
                                        <td></td>
                                        <td><strong>${item.total}</strong></td>
                                    </tr>`;
                                    item.item.forEach(function(subitem){
                                       html += `
                                        <tr>
                                            <td>${subitem.name}</td>
                                            <td><div>${subitem.total}</div></td>
                                            <td></td>
                                        </tr>
                                       `;
                                    });
                                });
                                 $('#'+instance.table+' tbody').html(html);
                                resolve(result);
                            },
                            error: function (xhr, status, error) {
                                console.error(xhr);
                                reject(xhr);
                                alert('An error occurred while loading the create form.');
                            }
                        });
                    });
                }
            }
        });

        SIAOPS.getAttribute('accounts').addAccount(
            SIAOPS.getAttribute("{{$name}}"));

        $('#filter-company-{{$name}}').change(async function(){
            await SIAOPS.getAttribute("{{$name}}").load();
        });

        eventEmitter.on("{{$name}}_update_success", async function(data){
            // if(data.level == 2){
            window.location.href = location.href;
            // }
            // await SIAOPS.getAttribute("{{$name}}").load();
        });

        eventEmitter.on("crudTable-filter_{{$name}}_load", async function(data){
            await SIAOPS.getAttribute("{{$name}}").load();
        });
    </script>
@endpush

@push('after_scripts')
    @once
        <script>
            $(function(){
                // SIAOPS.getAttribute('accounts').load();

                $('#btn-export-consolidation-pdf').click(async function(){
                    setLoadingButton("#btn-export-consolidation-pdf", true);
                    var selectedCompany = $('#filter-company-report_profit_lost').val() || '';
                    var get_url_export = "{{url($crud->route)}}/export-consolidation-pdf?export=1";
                    var get_title_export = "Laporan_laba_rugi_konsolidasi.pdf";
                    var params_obj = Object.assign({}, window.filter_tables || {});
                    if (selectedCompany && selectedCompany !== 'all') {
                        params_obj.company_id = selectedCompany;
                    }
                    var params_url = MakeParamUrl(params_obj);

                    var url_export_with_params = get_url_export + params_url;

                    if(get_url_export == ''){
                        setLoadingButton("#btn-export-consolidation-pdf", false);
                        swal({
                            title: "Error",
                            text: "Internet server error",
                            icon: "error",
                            timer: 4000,
                            buttons: false,
                        });
                        return;
                    }

                    const {response, errors} = await API_REQUEST("DOWNLOAD", url_export_with_params);

                    if(errors){
                        var errorResponse = await errors;
                        swal({
                            title: "Error",
                            text: "Internet server error",
                            icon: "error",
                            timer: 4000,
                            buttons: false,
                        });
                        setLoadingButton("#btn-export-consolidation-pdf", false);
                    }else if(response){
                        let result = await response;
                        setLoadingButton("#btn-export-consolidation-pdf", false);

                        const url = window.URL.createObjectURL(result);
                        const a = document.createElement('a');
                        a.href = url;

                        a.download = get_title_export;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(url);
                    }
                });

                $('#btn-export-consolidation-excel').click(async function(){
                    setLoadingButton("#btn-export-consolidation-excel", true);
                    var selectedCompany = $('#filter-company-report_profit_lost').val() || '';
                    var get_url_export = "{{url($crud->route)}}/export-consolidation-excel?export=1";
                    var get_title_export = "Laporan_laba_rugi_konsolidasi.xlsx";
                    var params_obj = Object.assign({}, window.filter_tables || {});
                    if (selectedCompany && selectedCompany !== 'all') {
                        params_obj.company_id = selectedCompany;
                    }
                    var params_url = MakeParamUrl(params_obj);

                    var url_export_with_params = get_url_export + params_url;

                    if(get_url_export == ''){
                        setLoadingButton("#btn-export-consolidation-excel", false);
                        swal({
                            title: "Error",
                            text: "Internet server error",
                            icon: "error",
                            timer: 4000,
                            buttons: false,
                        });
                        return;
                    }

                    const {response, errors} = await API_REQUEST("DOWNLOAD", url_export_with_params);
                    if(errors){
                        var errorResponse = await errors;
                        swal({
                            title: "Error",
                            text: "Internet server error",
                            icon: "error",
                            timer: 4000,
                            buttons: false,
                        });
                        setLoadingButton("#btn-export-consolidation-excel", false);
                    }else if(response){
                        let result = await response;
                        setLoadingButton("#btn-export-consolidation-excel", false);

                        const url = window.URL.createObjectURL(result);
                        const a = document.createElement('a');
                        a.href = url;

                        // Nama file default - kamu bisa set manual atau ambil dari response header (opsional)
                        a.download = get_title_export;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(url);

                    }
                });
            });
        </script>
    @endonce
@endpush
