@push('after_scripts')
<script>
    $(function(){
        SIAOPS.setAttribute('client_quotation_plugin', function(){
            return {
                name: 'client_quotation_plugin',
                accounts_compact:[],
                eventLoader: async function(){
                    var instance = this;
                    eventEmitter.on("crudTable-filter_client_quotation_plugin_load", function(data){
                        instance.refresh();
                    });
                },
                filterParametersOld: function(){
                    var getI = SIAOPS.getAttribute('crudTable-client_quotation');
                    var get_url = getI.table.ajax.url();
                    const params = new URL(get_url).searchParams;
                    const obj = Object.fromEntries(params.entries());
                    return obj;
                },
                filterParameters: function(){
                    var setupAllFilter = SIAOPS.getAttribute("SETUP_ALL_FILTER_client_quotation");
                    if(setupAllFilter){
                        return {
                            search: setupAllFilter.searchValues,
                            ...setupAllFilter.filterValues,
                        };
                    }else{
                        var getI = SIAOPS.getAttribute('crudTable-quotation');
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
                    setTimeout(() => {
                        $("#crudTable-client_quotation thead tr.filters th").eq(10).children('input').remove();
                        $("#crudTable-client_quotation thead tr.filters th").eq(12).children('input').remove();
                    }, 400);
                    $.ajax({
                        url: "{{ url($crud->route.'/total') }}",
                        type: 'GET',
                        data: instance.filterParameters(),
                        typeData: 'json',
                        success: function (result) {
                            $('#panel-client_quotation').html(`
                                <div class="d-flex justify-content-between">
                                    <div class="p-2 bd-highlight"><strong class='fs-6'>{{trans('backpack::crud.client_quotation.count_exclude_ppn')}} : ${result.total_job_value}</strong></div>
                                    <div class="p-2 bd-highlight"><strong class='fs-6'>{{trans('backpack::crud.client_quotation.count_include_ppn')}} : ${result.total_job_value_ppn}</strong></div>
                                    <div class="p-2 bd-highlight"></div>
                                </div>
                            `);
                        },
                        error: function (xhr, status, error) {
                            console.error(xhr);
                        }
                    });
                },
                load: function(){
                    var instance = this;
                    instance.eventLoader()
                    // instance.refresh();
                }
            }
        });

        SIAOPS.getAttribute('client_quotation_plugin').load();

    });
</script>
@endpush
