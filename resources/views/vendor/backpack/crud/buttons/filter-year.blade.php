<div class="btn-group" id="filterYear">
  <button class="btn btn-primary dropdown-toggle filter-btn" type="button" id="defaultDropdown" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
    <i class="la la-filter"></i>
  </button>
  <ul class="dropdown-menu" aria-labelledby="defaultDropdown">
    @php
        $get_all_year = $crud->year_options ?? \App\Http\Helpers\CustomHelper::getYearOptions();
    @endphp
    <li><a class="dropdown-item active" href="javascript:void(0)" data-value="all">{{ trans('backpack::crud.filter.all_year') }}</a></li>
    @foreach ($get_all_year as $year)
        <li><a class="dropdown-item" href="javascript:void(0)" data-value="{{ $year }}">{{ $year }}</a></li>
    @endforeach
  </ul>
</div>

@push('after_scripts')
    <script>
        if(window.filter_tables == undefined){
            window.filter_tables = {};
        }
        if(typeof filterSelectYear != 'function'){
            function filterSelectYear(){
                $('#filterYear .dropdown-menu li').each(function(){
                    $(this).children().click(function(e){
                        e.preventDefault();
                        $('#filterYear .dropdown-menu li a').removeClass('active');
                        let value = $(this).data('value');
                        $(this).addClass('active');
                        // var route = "{!! url($crud->route.'/search') !!}";
                        // route += "?filter_year="+value;
                        // crud.table.ajax.url(route).load();
                        forEachFlexible(SIAOPS.getAllAttributes(), function(key, item){
                            if(key.includes("crudTable")){
                                var url_route = item.table.ajax.url();

                                // Helper to update query string
                                var url = new URL(url_route.startsWith('http') ? url_route : window.location.origin + (url_route.startsWith('/') ? '' : '/') + url_route);
                                if (value === 'all') {
                                    url.searchParams.delete('filter_year');
                                } else {
                                    url.searchParams.set('filter_year', value);
                                }
                                
                                var final_url = url_route.startsWith('http') ? url.toString() : url.pathname + url.search;
                                item.table.ajax.url(final_url).load();
                                window.filter_tables.filter_year = value;
                            }else if(key.includes("SETUP_ALL_FILTER")){
                                var setupAllFilter = SIAOPS.getAttribute(key);
                                setupAllFilter.filterValues.filter_year = value;
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
        filterSelectYear();
    </script>
@endpush
