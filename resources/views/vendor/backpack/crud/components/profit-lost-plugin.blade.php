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
        // Listen to DataTables XHR responses to synchronize aggregate totals in real time
        $(document).on('xhr.dt', function (e, settings, json, xhr) {
            if (!json) return;
            const tableId = settings.sTableId;
            if (tableId === 'crudTable-project' && json.total_price_exlude_ppn !== undefined) {
                $('#panel-project').html(`
                    <div class="d-flex justify-content-between mb-2">
                        <div class="p-2 bd-highlight"><strong>{{trans('backpack::crud.voucher.total_exclude_ppn')}} : ${json.total_price_exlude_ppn}</strong></div>
                        <div class="p-2 bd-highlight"><strong>{{trans('backpack::crud.profit_lost.total_profit_lost')}} : ${json.total_price_prift_lost_finals}</strong></div>
                        <div class="p-2 bd-highlight"></div>
                    </div>
                `);
            } else if (tableId === 'crudTable-supplier' && json.total_price_exlude_ppn !== undefined) {
                $('#panel-supplier').html(`
                    <div class="d-flex justify-content-between mb-2">
                        <div class="p-2 bd-highlight"><strong>{{trans('backpack::crud.voucher.total_exclude_ppn')}} : ${json.total_price_exlude_ppn}</strong></div>
                        <div class="p-2 bd-highlight"><strong>{{trans('backpack::crud.profit_lost.total_profit_lost')}} : ${json.total_price_prift_lost_finals}</strong></div>
                        <div class="p-2 bd-highlight"></div>
                    </div>
                `);
            }
        });

        // Trigger change on keyup with debounce (or Enter immediately) for filter inputs
        var searchDebounceTimer = null;
        $(document).on('keyup', '#crudTable-project thead tr.filters th input, #crudTable-supplier thead tr.filters th input', function(e) {
            const input = $(this);
            clearTimeout(searchDebounceTimer);
            if (e.key === 'Enter') {
                e.preventDefault();
                input.trigger('change');
            } else {
                searchDebounceTimer = setTimeout(function() {
                    input.trigger('change');
                }, 500);
            }
        });

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
                    // Totals are dynamically updated via DataTables xhr.dt event handler above
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
