<div class="btn-group" id="filterPaidUnpaid">
  <button class="btn btn-primary dropdown-toggle filter-btn" type="button" id="defaultDropdown" data-bs-toggle="dropdown" data-bs-auto-close="true" aria-expanded="false">
    <i class="la la-filter"></i>
  </button>
  <ul class="dropdown-menu" aria-labelledby="defaultDropdown">
    @php
        $get_all_year = \App\Http\Helpers\CustomHelper::getPaidOptions();
    @endphp
    <li><a class="dropdown-item active" href="javascript:void(0)" data-value="all">{{ trans('backpack::crud.filter.all_paid') }}</a></li>
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
        if(typeof filterSelectPaid != 'function'){
            function filterSelectPaid(){
                $('#filterPaidUnpaid .dropdown-menu li').each(function(){
                    $(this).children().click(function(e){
                        e.preventDefault();
                        $('#filterPaidUnpaid .dropdown-menu li a').removeClass('active');
                        let value = $(this).data('value');
                        $(this).addClass('active');
                        // var route = "{!! url($crud->route.'/search') !!}";
                        // route += "?filter_paid_status="+value;
                        // crud.table.ajax.url(route).load();
                        window.filter_tables.filter_paid_status = value;
                        forEachFlexible(SIAOPS.getAllAttributes(), function(key, item){
                            if(key.includes("crudTable")){
                                var filter_paid_status = $('#filterPaidUnpaid .dropdown-menu li a.active').data('value');
                                var url_route = item.route;
                                forEachFlexible(window.filter_tables, function(key, value){
                                    var url_sign = "?";
                                    if(url_route.includes("?")){
                                        url_sign = "&";
                                    }
                                    url_route += url_sign+key+"="+value;
                                });
                                item.table.ajax.url(url_route).load();
                            }else if(key.includes("SETUP_ALL_FILTER")){
                                var setupAllFilter = SIAOPS.getAttribute(key);
                                setupAllFilter.filterValues.filter_paid_status = value;
                            }
                        });
                    });
                });
            }
        }
        filterSelectPaid();
    </script>
@endpush
