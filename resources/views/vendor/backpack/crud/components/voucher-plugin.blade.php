@push('inline_scripts')
    @once
        <style>
            #crudTable-voucher_wrapper .dataTables_scrollHead table thead tr th {
                background-color: #FCD72D !important;
            }
        </style>
    @endonce
@endpush

@push('after_scripts')
<script>
    $(function(){
        SIAOPS.setAttribute('voucher_plugin', function(){
            return {
                name: 'voucher_plugin',
                accounts_compact:[],
                eventLoader: async function(){
                    var instance = this;
                    eventEmitter.on("crudTable-filter_voucher_plugin_load", function(data){
                        instance.refresh();
                    });
                },
                filterParameters: function(){
                    var setupAllFilter = SIAOPS.getAttribute("SETUP_ALL_FILTER_voucher");
                    if(setupAllFilter){
                        return {
                            search: setupAllFilter.searchValues,
                            ...setupAllFilter.filterValues,
                        };
                    }else{
                        var getI = SIAOPS.getAttribute('crudTable-voucher');
                        var get_url = getI.table.ajax.url();
                        const params = new URL(get_url).searchParams;
                        const obj = Object.fromEntries(params.entries());
                        return {
                            search: window.filterValues,
                            ...obj
                        }
                    }
                },
                refresh: function(){
                    var instance = this;
                    $.ajax({
                        url: "{{ url($crud->route.'/total') }}",
                        type: 'GET',
                        data: instance.filterParameters(),
                        typeData: 'json',
                        success: function (result) {
                            $('#panel-voucher').html(`
                                <div class="d-flex justify-content-between">
                                    <div class="p-2 bd-highlight"><strong>{{trans('backpack::crud.voucher.total_exclude_ppn')}} : ${result.total_exclude_ppn}</strong></div>
                                    <div class="p-2 bd-highlight"><strong>{{trans('backpack::crud.voucher.total_include_ppn')}} : ${result.total_include_ppn}</strong></div>
                                    <div class="p-2 bd-highlight"><strong>{{trans('backpack::crud.voucher.total_transfer_value')}} : ${result.total_nilai_transfer}</strong></div>
                                </div>
                            `);
                        },
                        error: function (xhr, status, error) {
                            console.error(xhr);
                            alert('An error occurred while loading the create form.');
                        }
                    });
                },
                load: function(){
                    var instance = this;
                    instance.eventLoader();
                    instance.refresh();
                }
            }
        });

        SIAOPS.getAttribute('voucher_plugin').load();

    });
</script>
@endpush
