<div class="row" bp-section="crud-operation-list">
    @if (!isset($hide_title))
        <h5>{{$title}}</h5>
    @endif
    {{-- THE ACTUAL CONTENT --}}
    <div class="{{ $crud->getListContentClass() }}">

        <div class="row mb-2 align-items-center">
        <div class="col-sm-9 datatable-widget-stack">
            {{-- @if ( $crud->buttons()->where('stack', 'top')->count() ||  $crud->exportButtons())
            <div class="d-print-none {{ $crud->hasAccess('create')?'with-border':'' }}">

                @include('crud::inc.button_stack', ['stack' => 'top'])

            </div>
            @endif --}}
        </div>
        @if($crud->getOperationSetting('searchableTable'))
        <div class="col-sm-3">
            <div id="datatable_search_stack" class="mt-sm-0 mt-2 d-print-none">
            <div class="input-icon">
                <span class="input-icon-addon">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0"></path><path d="M21 21l-6 -6"></path></svg>
                </span>
                <input type="search" class="form-control" placeholder="{{ trans('backpack::crud.search') }}..."/>
            </div>
            </div>
        </div>
        @endif
        </div>

        {{-- Backpack List Filters --}}
        @if ($crud->filtersEnabled())
            @if (isset($filter_table))
                @include('crud::inc.filters_navbar', [
                    'filter_table' => $filter_table,
                    'table_name' => "crudTable-".$name,
                ])
            @endif
        @endif

        <div class="{{ backpack_theme_config('classes.tableWrapper') }} andi">
            <div id="panel-{{$name}}">
            </div>
            <table
            id="crudTable-{{$name}}"
            class="{{ backpack_theme_config('classes.table') ?? 'table table-hover nowrap rounded card-table table-vcenter card d-table shadow-xs border-xs' }}"
            data-responsive-table="{{ (int) $crud->getOperationSetting('responsiveTable') }}"
            data-has-details-row="{{ (int) $crud->getOperationSetting('detailsRow') }}"
            data-has-bulk-actions="{{ (int) $crud->getOperationSetting('bulkActions') }}"
            data-has-line-buttons-as-dropdown="{{ (int) $crud->getOperationSetting('lineButtonsAsDropdown') }}"
            data-line-buttons-as-dropdown-minimum="{{ (int) $crud->getOperationSetting('lineButtonsAsDropdownMinimum') }}"
            data-line-buttons-as-dropdown-show-before-dropdown="{{ (int) $crud->getOperationSetting('lineButtonsAsDropdownShowBefore') }}"
            cellspacing="0">
            <thead>
            <tr>
                {{-- Table columns --}}
                @foreach ($columns as $column)
                    @php
                    $column['orderable'] = $column['orderable'] ?? true;
                    $column['priority'] = $column['priority'] ?? 1;
                    $column['visibleInTable'] = $column['visibleInTable'] ?? true;
                    $column['visibleInModal'] = $column['visibleInModal'] ?? true;
                    $column['visibleInExport'] = $column['visibleInExport'] ?? true;
                    $column['forceExport'] = $column['forceExport'] ?? false;
                    $column['exportOnlyColumn'] = $column['exportOnlyColumn'] ?? false;

                    $exportOnlyColumn = $column['exportOnlyColumn'] ?? false;
                    $visibleInTable = $column['visibleInTable'] ?? ($exportOnlyColumn ? false : true);
                    $visibleInModal = $column['visibleInModal'] ?? ($exportOnlyColumn ? false : true);
                    $visibleInExport = $column['visibleInExport'] ?? true;
                    $forceExport = $column['forceExport'] ?? (isset($column['exportOnlyColumn']) ? true : false);
                    @endphp
                        @if ($column['type'] == 'action')
                            <th data-orderable="false"
                            data-priority="1"
                            data-visible-in-export="false"
                            data-action-column="true"
                            >{{ trans('backpack::crud.actions') }}</th>
                        @else
                            <th
                                data-orderable="{{ var_export($column['orderable'], true) }}"
                                data-priority="{{ $column['priority'] }}"
                                data-column-name="{{ $column['name'] }}"
                                {{--
                                data-visible-in-table => if developer forced column to be in the table with 'visibleInTable => true'
                                data-visible => regular visibility of the column
                                data-can-be-visible-in-table => prevents the column to be visible into the table (export-only)
                                data-visible-in-modal => if column appears on responsive modal
                                data-visible-in-export => if this column is exportable
                                data-force-export => force export even if columns are hidden
                                --}}

                                data-visible="{{ $exportOnlyColumn ? 'false' : var_export($visibleInTable) }}"
                                data-visible-in-table="{{ var_export($visibleInTable) }}"
                                data-can-be-visible-in-table="{{ $exportOnlyColumn ? 'false' : 'true' }}"
                                data-visible-in-modal="{{ var_export($visibleInModal) }}"
                                data-visible-in-export="{{ $exportOnlyColumn ? 'true' : ($visibleInExport ? 'true' : 'false') }}"
                                data-force-export="{{ var_export($forceExport) }}"
                            >
                                {{-- Bulk checkbox --}}
                                @if ($column['name'] == 'bulk_actions')
                                    {!! View::make('crud::columns.inc.bulk_actions_checkbox')->render() !!}
                                @else
                                    {!! $column['label'] !!}
                                @endif
                                {{-- @if($loop->first && $crud->getOperationSetting('bulkActions'))
                                @endif --}}
                            </th>
                        @endif
                    @endforeach
            </tr>
            </thead>
            <tbody>
            </tbody>
        </table>
        </div>

        @if ( $crud->buttons()->where('stack', 'bottom')->count() )
            <div id="bottom_buttons" class="d-print-none text-sm-left">
                @include('crud::inc.button_stack', ['stack' => 'bottom'])
                <div id="datatable_button_stack" class="float-right float-end text-right hidden-xs"></div>
            </div>
        @endif

    </div>

</div>

@push('inline_scripts')
    @once
        @basset('https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css')
        @basset('https://cdn.datatables.net/fixedheader/3.3.1/css/fixedHeader.dataTables.min.css')
        @basset('https://cdn.datatables.net/responsive/2.4.0/css/responsive.dataTables.min.css')
    @endonce
@endpush

@push('inline_scripts')
    <style>
        #crudTable-{{$name}}_processing.dataTables_processing.card {
            all: unset;
            position: absolute;
            background: rgba(255, 255, 255, 0.9);
            height: calc(100% - 6px);
            width: calc(100% - 20px);
            top: 0;
            left: 10px;
            z-index: 999;
            border-radius: 5px;
        }
        #crudTable-{{$name}}_processing.dataTables_processing.card > img {
            margin: 0;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        #crudTable-{{$name}}_processing.dataTables_processing.card > div {
            display: none !important;
        }
    </style>
@endpush

@push('after_scripts')
    @once
        {{-- @include('crud::inc.datatables_logic') --}}
        @basset('https://cdn.datatables.net/1.13.1/js/jquery.dataTables.min.js')
        @basset('https://cdn.datatables.net/1.13.1/js/dataTables.bootstrap5.min.js')
        @basset('https://cdn.datatables.net/responsive/2.4.0/js/dataTables.responsive.min.js')
        @basset('https://cdn.datatables.net/responsive/2.4.0/css/responsive.dataTables.min.css')
        @basset('https://cdn.datatables.net/fixedheader/3.3.1/js/dataTables.fixedHeader.min.js')
        @basset('https://cdn.datatables.net/fixedheader/3.3.1/css/fixedHeader.dataTables.min.css')
        @basset(base_path('vendor/backpack/crud/src/resources/assets/img/spinner.svg'), false)
        <script>
            var $dtDefaultPageLength = {{ $crud->getDefaultPageLength() }};
            let $pageLength = @json($crud->getPageLengthMenu());
            window.crud = {
                ...window.crud,
                exportButtons: JSON.parse('{!! json_encode($crud->get('list.export_buttons')) !!}'),
                functionsToRunOnDataTablesDrawEvent: [],
                addFunctionToDataTablesDrawEventQueue: function (functionName) {
                    if (this.functionsToRunOnDataTablesDrawEvent.indexOf(functionName) == -1) {
                    this.functionsToRunOnDataTablesDrawEvent.push(functionName);
                    }
                },
                responsiveToggle: function(dt) {
                    $(dt.table().header()).find('th').toggleClass('all');
                    dt.responsive.rebuild();
                    dt.responsive.recalc();
                },
                executeFunctionByName: function(str, args) {
                    var arr = str.split('.');
                    var fn = window[ arr[0] ];

                    for (var i = 1; i < arr.length; i++)
                    { fn = fn[ arr[i] ]; }
                    fn.apply(window, args);
                },
                updateUrl : function (url) {
                    let urlStart = "{{ url($crud->route) }}";
                    let urlEnd = url.replace(urlStart, '');
                    urlEnd = urlEnd.replace('/search', '');
                    let newUrl = urlStart + urlEnd;
                    let tmpUrl = newUrl.split("?")[0],
                    params_arr = [],
                    queryString = (newUrl.indexOf("?") !== -1) ? newUrl.split("?")[1] : false;

                    // exclude the persistent-table parameter from url
                    if (queryString !== false) {
                        params_arr = queryString.split("&");
                        for (let i = params_arr.length - 1; i >= 0; i--) {
                            let param = params_arr[i].split("=")[0];
                            if (param === 'persistent-table') {
                                params_arr.splice(i, 1);
                            }
                        }
                        newUrl = params_arr.length ? tmpUrl + "?" + params_arr.join("&") : tmpUrl;
                    }
                    window.history.pushState({}, '', newUrl);
                    @if ($crud->getPersistentTable())
                        // localStorage.setItem('{{ Str::slug($crud->getRoute()) }}_list_url', newUrl);
                    @endif
                },
                dataTableConfiguration: {
                    bInfo: {{ var_export($crud->getOperationSetting('showEntryCount') ?? true) }},
                    @if ($crud->getResponsiveTable())
                    responsive: {
                        details: {
                            display: $.fn.dataTable.Responsive.display.modal( {
                                header: function ( row ) {
                                    // show the content of the first column
                                    // as the modal header
                                    // var data = row.data();
                                    // return data[0];
                                    return '';
                                }
                            }),
                            type: 'none',
                            target: '.dtr-control',
                            renderer: function ( api, rowIdx, columns ) {
                                var data = $.map( columns, function ( col, i ) {
                                    var instance = SIAOPS.getAttribute('crudTable-{{$name}}');
                                    var columnHeading = crud.table.columns().header()[col.columnIndex];
                                    // hide columns that have VisibleInModal false
                                    if ($(columnHeading).attr('data-visible-in-modal') == 'false') {
                                        return '';
                                    }

                                    if (col.data.indexOf('crud_bulk_actions_checkbox') !== -1) {
                                        col.data = col.data.replace('crud_bulk_actions_checkbox', 'crud_bulk_actions_checkbox d-none');
                                    }

                                    let colTitle = '';
                                    if (col.title) {
                                        let tempDiv = document.createElement('div');
                                        tempDiv.innerHTML = col.title;

                                        let checkboxSpan = tempDiv.querySelector('.crud_bulk_actions_checkbox');
                                        if (checkboxSpan) {
                                            checkboxSpan.remove();
                                        }

                                        colTitle = tempDiv.textContent.trim();
                                    } else {
                                        colTitle = '';
                                    }

                                    return '<tr data-dt-row="'+col.rowIndex+'" data-dt-column="'+col.columnIndex+'">'+
                                            '<td style="vertical-align:top; border:none;"><strong>'+colTitle+':'+'<strong></td> '+
                                            '<td style="padding-left:10px;padding-bottom:10px; border:none;">'+col.data+'</td>'+
                                            '</tr>';
                                }).join('');

                                return data ?
                                    $('<table class="table table-striped mb-0">').append( '<tbody>' + data + '</tbody>' ) :
                                    false;
                            },
                        }
                    },
                    fixedHeader: true,
                    @else
                    responsive: false,
                    scrollX: true,
                    @endif

                    @if ($crud->getPersistentTable())
                    stateSave: false,
                    /*
                        if developer forced field into table 'visibleInTable => true' we make sure when saving datatables state
                        that it reflects the developer decision.
                    */

                    stateSaveParams: function(settings, data) {

                        // localStorage.setItem('{{ Str::slug($crud->getRoute()) }}_list_url_time', data.time);

                        data.columns.forEach(function(item, index) {
                            var columnHeading = crud.table.columns().header()[index];
                            if ($(columnHeading).attr('data-visible-in-table') == 'true') {
                                return item.visible = true;
                            }
                        });
                    },
                    @if($crud->getPersistentTableDuration())
                    stateLoadParams: function(settings, data) {
                        var $saved_time = new Date(data.time);
                        var $current_date = new Date();

                        $saved_time.setMinutes($saved_time.getMinutes() + {{$crud->getPersistentTableDuration()}});

                        //if the save time as expired we force datatabled to clear localStorage
                        if($saved_time < $current_date) {
                            // if (localStorage.getItem('{{ Str::slug($crud->getRoute())}}_list_url')) {
                            //     localStorage.removeItem('{{ Str::slug($crud->getRoute()) }}_list_url');
                            // }
                            // if (localStorage.getItem('{{ Str::slug($crud->getRoute())}}_list_url_time')) {
                            //     localStorage.removeItem('{{ Str::slug($crud->getRoute()) }}_list_url_time');
                            }
                        return false;
                        }
                    },
                    @endif
                    @endif
                    autoWidth: false,
                    pageLength: $dtDefaultPageLength,
                    lengthMenu: $pageLength,
                    /* Disable initial sort */
                    aaSorting: [],
                    language: {
                        "emptyTable":     "{{ trans('backpack::crud.emptyTable') }}",
                        "info":           "{{ trans('backpack::crud.info') }}",
                        "infoEmpty":      "{{ trans('backpack::crud.infoEmpty') }}",
                        "infoFiltered":   "{{ trans('backpack::crud.infoFiltered') }}",
                        "infoPostFix":    "{{ trans('backpack::crud.infoPostFix') }}",
                        "thousands":      "{{ trans('backpack::crud.thousands') }}",
                        "lengthMenu":     "{{ trans('backpack::crud.lengthMenu') }}",
                        "loadingRecords": "{{ trans('backpack::crud.loadingRecords') }}",
                        "processing":     "<img src='{{ Basset::getUrl('vendor/backpack/crud/src/resources/assets/img/spinner.svg') }}' alt='{{ trans('backpack::crud.processing') }}'>",
                        "search": "_INPUT_",
                        "searchPlaceholder": "{{ trans('backpack::crud.search') }}...",
                        "zeroRecords":    "{{ trans('backpack::crud.zeroRecords') }}",
                        "paginate": {
                            "first":      "{{ trans('backpack::crud.paginate.first') }}",
                            "last":       "{{ trans('backpack::crud.paginate.last') }}",
                            //   "next":       '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M8 5l5 5l-5 5"></path></svg>',
                            //   "previous":   '<svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path d="M15 5l-5 5l5 5"></path></svg>'
                        },
                        "aria": {
                            "sortAscending":  "{{ trans('backpack::crud.aria.sortAscending') }}",
                            "sortDescending": "{{ trans('backpack::crud.aria.sortDescending') }}"
                        },
                        "buttons": {
                            "copy":   "{{ trans('backpack::crud.export.copy') }}",
                            "excel":  "{{ trans('backpack::crud.export.excel') }}",
                            "csv":    "{{ trans('backpack::crud.export.csv') }}",
                            "pdf":    "{{ trans('backpack::crud.export.pdf') }}",
                            "print":  "{{ trans('backpack::crud.export.print') }}",
                            "colvis": "{{ trans('backpack::crud.export.column_visibility') }}"
                        },
                    },
                    processing: true,
                    serverSide: true,
                    searchDelay: {{ $crud->getOperationSetting('searchDelay') }},
                    @if($crud->getOperationSetting('showEntryCount') === false)
                        pagingType: "simple",
                    @endif
                    searching: @json($crud->getOperationSetting('searchableTable') ?? true),
                    ajax: {
                        "url":  "{!! $route !!}",
                        "type": "POST",
                        "data": {
                            "totalEntryCount": "{{$crud->getOperationSetting('totalEntryCount') ?? false}}"
                        },
                    },
                    //   dom:
                    //     "<'row hidden'<'col-sm-6'i><'col-sm-6 d-print-none'f>>" +
                    //     "<'table-content row'<'col-sm-12'tr>>" +
                    //     "<'table-footer row mt-2 d-print-none align-items-center '<'col-sm-12 col-md-4'l><'col-sm-0 col-md-4 text-center'B><'col-sm-12 col-md-4 'p>>",
                    dom:
                        "<'row hidden'<'col-sm-6'l><'col-sm-6 d-print-none'f>>" +
                        "<'table-content row'<'col-sm-12'tr>>" +
                        "<'table-footer row mt-2 d-print-none align-items-center '<'col-sm-12 col-md-4'p><'col-sm-0 col-md-4 text-center'B><'col-sm-12 col-md-4 'i>>",
                }
            };
        </script>
    @endonce
@endpush

@push('after_scripts')
    <script>
        if(SIAOPS.getAttribute("SETUP_ALL_FILTER_"+"{{$name}}") == null){
            SIAOPS.setAttribute("SETUP_ALL_FILTER_"+"{{$name}}", function(){
                return {
                    searchValues: [],
                    filterValues: {},
                }
            });
        }
    </script>
    <script>
        window.filterValues = [];
        function setupFilterInputs(tableId) {
            const $table = $(tableId);
            const totalColumns = $(tableId+' thead tr th').length;

            $table.find('thead').append('<tr class="filters"></tr>');

            $table.find('thead tr:first th').each(function (i) {
                if (i === 0 || i === totalColumns - 1) {
                    $table.find('thead tr.filters').append('<th><input type="hidden" /></th>');
                } else {
                    $table.find('thead tr.filters').append(
                        `<th><input type="text" class="form-control form-control-sm" placeholder="" /></th>`
                    );
                }
            });

            $table.find('thead tr.filters th input').each(function (i) {
                if (filterValues[i] !== undefined) {
                    $(this).val(filterValues[i]);
                }
            });
        }

        function bindFilterEvents(tableInstance, tableId) {
            const totalColumns = $(tableId).find('thead tr:first th').length;

            tableInstance.columns().every(function (i) {
                if (i !== 0 && i !== totalColumns - 1) {
                    const column = this;
                    const input = $(`${tableId} thead tr.filters th`).eq(i).find('input');
                    var tabFilter = SIAOPS.getAttribute("SETUP_ALL_FILTER_{{$name}}");
                    // tabFilter.searchValues[i] = null;
                    input.on('change clear', function () {
                        filterValues[i] = this.value;
                        column.search(this.value).draw();
                        var tabFilter = SIAOPS.getAttribute("SETUP_ALL_FILTER_{{$name}}");
                        tabFilter.searchValues[i] = this.value;
                    });
                }
            });
        }
    </script>
    <script>
        SIAOPS.setAttribute('crudTable-{{$name}}', function() {
            return {
                id: $('#crudTable-{{$name}}'),
                table: null,
                route: '{!! $route !!}',
                eventLoader: function(){

                    var instance = this;

                    eventEmitter.on("project_create_success", function(data){
                        instance.table.ajax.reload();
                    });

                    eventEmitter.on("crudTable-{{$name}}_create_success", function(data){
                        instance.table.ajax.reload();
                    });

                    eventEmitter.on("crudTable-{{$name}}_updated_success", function(data){
                        instance.table.ajax.reload();
                    });

                    instance.table = $('#crudTable-{{$name}}').DataTable({
                        ...window.crud.dataTableConfiguration,
                        bInfo: true,
                        responsive: false,
                        scrollX: true,
                        stateSave: false,
                        stateSaveParams: function(settings, data) {
                            // localStorage.setItem('adminauthpermission_list_url_time', data.time);
                            data.columns.forEach(function(item, index) {
                                var columnHeading = instance.table.columns().header()[index];
                                if ($(columnHeading).attr('data-visible-in-table') == 'true') {
                                    return item.visible = true;
                                }
                            });
                        },
                        autoWidth: false,
                        pageLength: $dtDefaultPageLength,
                        lengthMenu: $pageLength,
                        aaSorting: [],
                        processing: true,
                        serverSide: true,
                        searching: 400,
                        ajax: {
                            "url":  "{!! $route !!}",
                            "type": "POST",
                            // "data": {
                            //     "totalEntryCount": "{{$crud->getOperationSetting('totalEntryCount') ?? false}}"
                            // },
                            data: function(d){
                                d.totalEntryCount = "{{$crud->getOperationSetting('totalEntryCount') ?? false}}";
                                const totalColumns = $('#crudTable-{{$name}}').find('thead tr:first th').length;
                                $('#crudTable-{{$name}} thead tr.filters th').each(function (i) {
                                    if (i !== 0 && i !== totalColumns - 1) {
                                        const input = $(this).find('input');
                                        if (input.length) {
                                            d.columns[i].search.value = input.val();
                                        }
                                    }
                                });
                            },
                        },
                        initComplete: function () {
                            @if (isset($filter))
                                setupFilterInputs('#crudTable-{{$name}}');
                                bindFilterEvents(this.api(), '#crudTable-{{$name}}');
                            @endif
                        },
                    });
                    // window.crud.updateUrl(location.href);
                    $('#crudTable-{{$name}}_length').appendTo($('#{{$name}} .datatable-widget-stack'));

                    $("#{{$name}} #datatable_search_stack input").remove();
                    $("#crudTable-{{$name}}_filter input").appendTo($('#{{$name}} #datatable_search_stack .input-icon'));
                    $("#{{$name}} #datatable_search_stack input").removeClass('form-control-sm');
                    $("#crudTable-{{$name}}_filter").remove();

                    // remove btn-secondary from export and column visibility buttons
                    $("#crudTable-{{$name}}_wrapper .table-footer .btn-secondary").removeClass('btn-secondary');

                    // remove forced overflow on load
                    $(".navbar.navbar-filters + div").css('overflow','initial');
                    @if($crud->getSubheading())
                    $('#crudTable-{{$name}}_info').hide();
                    @else
                    //   $("#datatable_info_stack").html($('#crudTable_info')).css('display','inline-flex').addClass('animated fadeIn');
                    //   $('#crudTable_info').appendTo($("#crudTable_wrapper .table-footer > div"));
                        setTimeout(function(){
                            // $('#crudTable_info').appendTo($(".dataTables_wrapper .table-footer div").first());
                            // $('#crudTable_paginate').appendTo($(".dataTables_wrapper .table-footer div").first());
                            // $('#crudTable_info').appendTo($(".dataTables_wrapper .table-footer div").last());
                        }, 100);
                    @endif

                    @if($crud->getOperationSetting('resetButton') ?? true)
                        // create the reset button
                        // var crudTableResetButton = '<a href="{{url($crud->route)}}" class="ml-1 ms-1" id="crudTable_reset_button">{{ trans('backpack::crud.reset') }}</a>';

                        // $('#datatable_info_stack').append(crudTableResetButton);

                        // // when clicking in reset button we clear the localStorage for datatables.
                        // $('#crudTable-{{$name}}_reset_button').on('click', function() {

                        // //clear the filters
                        // if (localStorage.getItem('{{ Str::slug($crud->getRoute())}}_list_url')) {
                        //     localStorage.removeItem('{{ Str::slug($crud->getRoute()) }}_list_url');
                        // }
                        // if (localStorage.getItem('{{ Str::slug($crud->getRoute())}}_list_url_time')) {
                        //     localStorage.removeItem('{{ Str::slug($crud->getRoute()) }}_list_url_time');
                        // }

                        // //clear the table sorting/ordering/visibility
                        // if(localStorage.getItem('DataTables_crudTable_/{{ $crud->getRoute() }}')) {
                        //     localStorage.removeItem('DataTables_crudTable_/{{ $crud->getRoute() }}');
                        // }
                        // });
                    @endif
                    $("#bottom_buttons").insertBefore($('#crudTable-{{$name}}_wrapper .row:last-child' ));
                    $.fn.dataTable.ext.errMode = 'none';
                    $('#crudTable-{{$name}}').on('error.dt', function(e, settings, techNote, message) {
                        new Noty({
                            type: "error",
                            text: "<strong>{{ trans('backpack::crud.ajax_error_title') }}</strong><br>{{ trans('backpack::crud.ajax_error_text') }}"
                        }).show();
                    });
                    // $('#crudTable-{{$name}}').on( 'length.dt', function ( e, settings, len ) {
                    //     localStorage.setItem('DataTables_crudTable_/{{$crud->getRoute()}}_pageLength', len);
                    // });

                    // $('#crudTable-{{$name}}').on( 'page.dt', function () {
                    //     localStorage.setItem('page_changed', true);
                    // });

                    $('#crudTable-{{$name}}').on( 'draw.dt',   function () {
                        var $table = $('#crudTable-{{$name}}');
                       @if (isset($filter))
                            setupFilterInputs('#crudTable-{{$name}}');
                            bindFilterEvents(SIAOPS.getAttribute('crudTable-{{$name}}').table, '#crudTable-{{$name}}');
                        @endif
                        // crud.functionsToRunOnDataTablesDrawEvent.forEach(function(functionName) {
                        //     crud.executeFunctionByName(functionName);
                        // });
                        // if ($('#crudTable-{{$name}}').data('has-line-buttons-as-dropdown')) {
                        // formatActionColumnAsDropdown();
                        // }

                        // if (! instance.table.responsive.hasHidden()) {
                        //     instance.table.columns().header()[0].style.paddingLeft = '0.6rem';
                        // }

                        // if (instance.table.responsive.hasHidden()) {
                        //     $('.dtr-control').removeClass('d-none');
                        //     $('.dtr-control').addClass('d-inline');
                        //     $("#crudTable-{{$name}}").removeClass('has-hidden-columns').addClass('has-hidden-columns');
                        // }

                        // memanggil plugin apapun dalam SIAOPS yang mengandung crudTable-filter
                        forEachFlexible(eventEmitter.events, function(key, data){
                            if(key.includes("crudTable-filter")){
                                eventEmitter.emit(key, true);
                            }
                        });

                    }).dataTable();

                    $('#crudTable-{{$name}}').on( 'column-visibility.dt',   function (event) {
                        console.log('column-visibility.dt');
                        instance.table.responsive.rebuild();
                    } ).dataTable();

                    @if ($crud->getResponsiveTable())
                        // when columns are hidden by reponsive plugin,
                        // the table should have the has-hidden-columns class
                        instance.table.on( 'responsive-resize', function ( e, datatable, columns ) {
                            if (instance.table.responsive.hasHidden()) {
                                $('.dtr-control').each(function() {
                                    var $this = $(this);
                                    var $row = $this.closest('tr');
                                    var $firstVisibleColumn = $row.find('td').filter(function() {
                                        return $(this).css('display') !== 'none';
                                    }).first();
                                    $this.prependTo($firstVisibleColumn);
                                });

                                $('.dtr-control').removeClass('d-none');
                                $('.dtr-control').addClass('d-inline');
                                $("#crudTable-{{$name}}").removeClass('has-hidden-columns').addClass('has-hidden-columns');
                            } else {
                                $('.dtr-control').removeClass('d-none').removeClass('d-inline').addClass('d-none');
                                $("#crudTable-{{$name}}").removeClass('has-hidden-columns');
                            }
                        });
                    @else
                        // make sure the column headings have the same width as the actual columns
                        // after the user manually resizes the window
                        @if (!isset($filter))
                            var resizeTimer;
                            function resizeCrudTableColumnWidths() {
                            clearTimeout(resizeTimer);
                            resizeTimer = setTimeout(function() {
                                // Run code here, resizing has "stopped"
                                instance.table.columns.adjust();
                            }, 250);
                            }
                            $(window).on('resize', function(e) {
                            resizeCrudTableColumnWidths();
                            });
                            $('.sidebar-toggler').click(function() {
                            resizeCrudTableColumnWidths();
                            });
                        @endif
                    @endif

                },
                filter: function(){
                    var instance = this;
                    $("#remove_filters_button_crudTable-{{$name}}").click(function(e) {
                        e.preventDefault();

                        var new_url = '{{ $route }}';
                        $(".crudTable-{{$name}}-filters li[filter-name]").trigger('filter:clear');
                    });

                    $(".crudTable-{{$name}}-filters li[filter-name]").on('filter:clear', function() {
                        var anyActiveFilters = false;
                        // console.log($(".crudTable-{{$name}}-filters li[filter-name]"));
                        $(".crudTable-{{$name}}-filters li[filter-name]").each(function () {
                            if ($(this).hasClass('active')) {
                                anyActiveFilters = true;
                            }
                        });

                        if (anyActiveFilters == false) {
                            $('#remove_filters_button_crudTable-{{$name}}').addClass('invisible');
                        }
                    });

                },
                load: function(){
                    var instance = this;
                    instance.eventLoader();
                    @if (isset($filter_table))
                        instance.filter();
                    @endif                        
                }
            }
        });

        $(function(){
            SIAOPS.getAttribute('crudTable-{{$name}}').load();
        });
    </script>
@endpush
