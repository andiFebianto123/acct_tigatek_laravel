{{-- @push('inline_scripts')
    @once
        <style>
            #crudTable-voucher_wrapper .dataTables_scrollHead table thead tr th {
                background-color: #FCD72D !important;
            }
        </style>
    @endonce
@endpush --}}

@push('after_scripts')
<script>
    $(function(){
        SIAOPS.setAttribute('profit_lost_plugin', function(){
            return {
                name: 'profit_lost_plugin',
                accounts_compact:[],
                eventLoader: async function(){
                    var instance = this;
                    eventEmitter.on("crudTable-filter_profit_lost_plugin_load", function(data){
                        instance.refresh();
                    });

                    // Refresh saat berpindah tab
                    $('li[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
                        instance.refresh();
                    });
                },
                refresh: function(){
                    var instance = this;

                    // 1. Refresh Tab Proyek
                    var projectTable = SIAOPS.getAttribute('crudTable-project');
                    if (projectTable && projectTable.table) {
                        const paramsProject = new URLSearchParams(projectTable.table.ajax.url());
                        var category = paramsProject.get('category');
                        var year = paramsProject.get('filter_year');

                        $.ajax({
                            url: "{{ url($crud->route.'/total') }}",
                            type: 'GET',
                            data: {
                                tab: 'project',
                                search: window.filterValues,
                                category: category,
                                filter_year: year,
                            },
                            dataType: 'json',
                            success: function (result) {
                                $('#panel-project').html(`
                                    <div class="d-flex justify-content-between mb-2">
                                        <div class="p-2 bd-highlight"><strong>{{trans('backpack::crud.voucher.total_exclude_ppn')}} : ${result.total_price_exlude_ppn}</strong></div>
                                        <div class="p-2 bd-highlight"><strong>{{trans('backpack::crud.profit_lost.total_profit_lost')}} : ${result.total_price_prift_lost_finals}</strong></div>
                                        <div class="p-2 bd-highlight"></div>
                                    </div>
                                `);
                            },
                            error: function (xhr, status, error) {
                                console.error(xhr);
                            }
                        });
                    }

                    // 2. Refresh Tab Supplier
                    var supplierTable = SIAOPS.getAttribute('crudTable-supplier');
                    if (supplierTable && supplierTable.table) {
                        const paramsSupplier = new URLSearchParams(supplierTable.table.ajax.url());
                        var supplierYear = paramsSupplier.get('filter_year');

                        $.ajax({
                            url: "{{ url($crud->route.'/total') }}",
                            type: 'GET',
                            data: {
                                tab: 'supplier',
                                filter_year: supplierYear,
                            },
                            dataType: 'json',
                            success: function (result) {
                                $('#panel-supplier').html(`
                                    <div class="d-flex justify-content-between mb-2">
                                        <div class="p-2 bd-highlight"><strong>{{trans('backpack::crud.voucher.total_exclude_ppn')}} : ${result.total_price_exlude_ppn}</strong></div>
                                        <div class="p-2 bd-highlight"><strong>{{trans('backpack::crud.profit_lost.total_profit_lost')}} : ${result.total_price_prift_lost_finals}</strong></div>
                                        <div class="p-2 bd-highlight"></div>
                                    </div>
                                `);
                            },
                            error: function (xhr, status, error) {
                                console.error(xhr);
                            }
                        });
                    }
                },
                load: function(){
                    var instance = this;
                    instance.eventLoader();
                }
            }
        });
        SIAOPS.getAttribute('profit_lost_plugin').load();
    });
</script>
@endpush
