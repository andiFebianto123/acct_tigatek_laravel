<div class="btn-group" id="filterPaidUnpaid">
  <button class="btn btn-primary dropdown-toggle filter-btn" type="button" id="defaultDropdownPaid" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false" title="Filter Status Bayar">
    <i class="la la-money-check-alt"></i> <span class="d-none d-md-inline ms-1" id="filterPaidStatusLabel">Status Bayar</span>
  </button>
  <ul class="dropdown-menu" aria-labelledby="defaultDropdownPaid">
    <li><a class="dropdown-item active" href="javascript:void(0)" data-value="all">{{ trans('backpack::crud.filter.all_paid') ?? 'Semua' }}</a></li>
    <li><a class="dropdown-item" href="javascript:void(0)" data-value="Paid">Lunas</a></li>
    <li><a class="dropdown-item" href="javascript:void(0)" data-value="Unpaid">Belum Bayar</a></li>
  </ul>
</div>

@push('after_scripts')
    <script>
        if(window.filter_tables == undefined){
            window.filter_tables = {};
        }
        if(typeof filterSelectPaid != 'function'){
            function filterSelectPaid(){
                $('#filterPaidUnpaid .dropdown-menu li').each(function(){
                    $(this).children().click(function(e){
                        e.preventDefault();
                        $('#filterPaidUnpaid .dropdown-menu li a').removeClass('active');
                        let value = $(this).data('value');
                        $(this).addClass('active');

                        let labelText = 'Status Bayar';
                        if (value === 'Paid') {
                            labelText = 'Status: Lunas';
                        } else if (value === 'Unpaid') {
                            labelText = 'Status: Belum Bayar';
                        }
                        $('#filterPaidStatusLabel').text(labelText);

                        window.filter_tables.filter_paid_status = value;
                        forEachFlexible(SIAOPS.getAllAttributes(), function(key, item){
                            if(key.includes("crudTable")){
                                var url_route = item.table ? item.table.ajax.url() : item.route;
                                var url = new URL(url_route.startsWith('http') ? url_route : window.location.origin + (url_route.startsWith('/') ? '' : '/') + url_route);
                                if (value === 'all' || !value) {
                                    url.searchParams.delete('filter_paid_status');
                                } else {
                                    url.searchParams.set('filter_paid_status', value);
                                }
                                var final_url = url_route.startsWith('http') ? url.toString() : url.pathname + url.search;
                                if (item.table) {
                                    item.table.ajax.url(final_url).load();
                                }
                            }else if(key.includes("SETUP_ALL_FILTER")){
                                var setupAllFilter = SIAOPS.getAttribute(key);
                                setupAllFilter.filterValues.filter_paid_status = value;
                            }
                        });

                        forEachFlexible(eventEmitter.events, function(key, data){
                            if(key.includes("crudTable-filter")){
                                eventEmitter.emit(key, true);
                            }
                        });
                    });
                });
            }
        }
        filterSelectPaid();
    </script>
@endpush
