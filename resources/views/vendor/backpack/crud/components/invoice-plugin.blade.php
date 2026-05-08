@push('after_scripts')
<script>
    $(function(){
        SIAOPS.setAttribute('invoice_plugin', function(){
            return {
                name: 'invoice_plugin',
                accounts_compact:[],
                eventLoader: async function(){
                    var instance = this;
                    eventEmitter.on("crudTable-filter_invoice_plugin_load", function(data){
                        instance.refresh();
                    });
                },
                filterParameters: function(){
                    if(window.filter_tables){
                        return window.filter_tables;
                    }
                    return {};
                },
                refresh: function(){
                    var instance = this;
                    setTimeout(() => {
                        @if(!backpack_user()->hasRole('Super Admin'))
                            $("#crudTable-invoice thead tr.filters th").eq(4).children('input').remove();
                            $("#crudTable-invoice thead tr.filters th").eq(6).children('input').remove();
                            $("#crudTable-invoice thead tr.filters th").eq(11).children('input').remove();
                            $("#crudTable-invoice thead tr.filters th").eq(12).children('input').remove();
                            $("#crudTable-invoice thead tr.filters th").eq(13).children('input').remove();
                        @else
                            $("#crudTable-invoice thead tr.filters th").eq(6).children('input').remove();
                            $("#crudTable-invoice thead tr.filters th").eq(8).children('input').remove();
                            $("#crudTable-invoice thead tr.filters th").eq(13).children('input').remove();
                            $("#crudTable-invoice thead tr.filters th").eq(14).children('input').remove();
                            $("#crudTable-invoice thead tr.filters th").eq(15).children('input').remove();
                        @endif
                    }, 400);
                    $.ajax({
                        url: "{{ url($crud->route.'/total') }}",
                        type: 'GET',
                        data: {
                            search: (SIAOPS.getAttribute('SETUP_ALL_FILTER_invoice')) ? SIAOPS.getAttribute('SETUP_ALL_FILTER_invoice').searchValues : [],
                            ...(SIAOPS.getAttribute('SETUP_ALL_FILTER_invoice') ? SIAOPS.getAttribute('SETUP_ALL_FILTER_invoice').filterValues : {} ),
                            // ...instance.filterParameters()
                        },
                        typeData: 'json',
                        success: function (result) {
                            $('#panel-invoice').html(`
                                <div class="d-flex justify-content-around">
                                    <div class="p-2 bd-highlight"><strong class='fs-6'>{{trans('backpack::crud.voucher.total_exclude_ppn')}} : ${result.total_price_exclude_ppn}</strong></div>
                                    <div class="p-2 bd-highlight"><strong class='fs-6'>{{trans('backpack::crud.voucher.total_include_ppn')}} : ${result.total_price_include_ppn}</strong></div>
                                    <div class="p-2 bd-highlight"><strong class='fs-6'>{{trans('backpack::crud.invoice_client.column.discount_pph')}} : ${result.total_discount_pph}</strong></div>
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
                    instance.eventLoader()
                    // instance.refresh();
                }
            }
        });

        SIAOPS.getAttribute('invoice_plugin').load();

    });
</script>
@endpush
