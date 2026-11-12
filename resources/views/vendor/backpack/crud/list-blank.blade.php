@extends(backpack_view('blank'))

@php
  $defaultBreadcrumbs = [
    trans('backpack::crud.admin') => url(config('backpack.base.route_prefix'), 'dashboard'),
    $crud->entity_name_plural => url($crud->route),
    trans('backpack::crud.list') => false,
  ];

  // if breadcrumbs aren't defined in the CrudController, use the default breadcrumbs
  $breadcrumbs = $breadcrumbs ?? [];
@endphp

@section('header')
    <section class="header-operation container-fluid animated fadeIn d-flex mb-2 align-items-baseline d-print-none mt-3" bp-section="page-header">
        <h1 class="text-capitalize mb-0" bp-section="page-heading">{!! $crud->getHeading() ?? $crud->entity_name_plural !!}</h1>
        <p class="ms-2 ml-2 mb-0" id="datatable_info_stack" bp-section="page-subheading">{!! $crud->getSubheading() ?? '' !!}</p>
    </section>
    @if (backpack_theme_config('breadcrumbs') && isset($breadcrumbs) && is_array($breadcrumbs))
        <nav aria-label="breadcrumb" class="d-none d-lg-block">
            <div class="d-flex justify-content-between">
                <ol class="breadcrumb bg-transparent p-0 mx-3">
                    @foreach ($breadcrumbs as $label => $link)
                        @if ($loop->last)
                            <li class="breadcrumb-item text-capitalize active" aria-current="page">{{ $label }}</li>
                        @else
                            <li class="breadcrumb-item text-capitalize"><a href="{{ $link }}">{{ $label }}</a></li>
                        @endif
                        {{-- @if ($link)
                            <li class="breadcrumb-item text-capitalize"><a href="{{ $link }}">{{ $label }}</a></li>
                        @else
                            <li class="breadcrumb-item text-capitalize active" aria-current="page">{{ $label }}</li>
                        @endif --}}
                    @endforeach
                </ol>
                <div class="d-print-none mb-2 pe-3 {{ $crud->hasAccess('create')?'with-border':'' }}">
                    @include('crud::inc.button_stack', ['stack' => 'top'])
                </div>
            </div>
        </nav>
    @endif
@endsection

@section('content')
    @if (isset($cards))
        <div class="row">
            @foreach ($cards->getCards()->where('line', 'top')->all() as $card)
                @if (isset($card['parent_view']))
                    @include($card['parent_view'], ['card' => $card])
                @else
                    @include('vendor.backpack.crud.components.card', ['card' => $card])
                @endif
            @endforeach
        </div>
    @endif

    @if (isset($cards))
        <div class="row">
            @foreach ($cards->getCards()->where('line', 'bottom')->all() ?? [] as $card)
                @if (isset($card['parent_view']))
                    @include($card['parent_view'], ['card' => $card])
                @else
                    @include('vendor.backpack.crud.components.card', ['card' => $card])
                @endif
            @endforeach
        </div>
    @endif

@endsection

@section('after_styles')
  {{-- DATA TABLES --}}
  {{-- @basset('https://cdn.datatables.net/1.13.1/css/dataTables.bootstrap5.min.css') --}}
  {{-- @basset('https://cdn.datatables.net/fixedheader/3.3.1/css/fixedHeader.dataTables.min.css') --}}
  {{-- @basset('https://cdn.datatables.net/responsive/2.4.0/css/responsive.dataTables.min.css') --}}

  {{-- CRUD LIST CONTENT - crud_list_styles stack --}}
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
  @stack('crud_list_styles')
@endsection

@section('after_scripts')
  {{-- @include('crud::inc.datatables_logic') --}}

  {{-- CRUD LIST CONTENT - crud_list_scripts stack --}}
  @stack('crud_list_scripts')
@endsection


@push('after_scripts')
    <!-- Modal -->
    <div class="modal fade" id="modalCreate" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header justify-content-center">
                    <h5 class="modal-title text-center w-100" id="modalTitleCentered">{!! $crud->getSubheading() ?? trans('backpack::crud.add').' '.$title_modal_create !!}</h5>
                    <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Loading...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('backpack::crud.cancel_submit') }}</button>
                    <button type="button" id="btn-submit-create" class="btn btn-primary save-block">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        {{ trans('backpack::crud.save_submit') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalEdit" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header justify-content-center">
                    <h5 class="modal-title text-center w-100" id="modalTitleCentered">{!! $crud->getSubheading() ?? trans('backpack::crud.edit').' '.$title_modal_edit !!}</h5>
                    <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Loading...
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('backpack::crud.cancel_submit') }}</button>
                    <button type="button" id="btn-submit-edit" class="btn btn-primary save-block">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        {{ trans('backpack::crud.save_submit') }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="modalShow" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header justify-content-center">
                    <h5 class="modal-title text-center w-100" id="modalTitleCentered">{!! $crud->getSubheading() ?? trans('backpack::crud.preview').' '.$title_modal_edit !!}</h5>
                    <button type="button" class="btn-close position-absolute end-0 me-3" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Loading...
                </div>
            </div>
        </div>
    </div>
    <!-- Modal -->
    <div class="modal fade" id="modalDelete" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalDeleteLabel">{{ trans('backpack::crud.delete') }} {{$title_modal_delete}}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                    {{ trans('backpack::crud.delete_confirm_2') }} {{$title_modal_delete}} ?
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="btn-delete" class="btn btn-danger">
                        <span class="btn-spinner spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <span class="btn-text">{{ trans('backpack::crud.delete') }}</span>
                    </button>
                </div>

            </div>
        </div>
    </div>
    <div class="modal fade" id="modalApproval" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalApprovalLabel"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Body -->
                <div class="modal-body">
                </div>

                <!-- Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ trans('backpack::crud.cancel_submit') }}</button>
                    <button type="button" id="btn-approve" class="btn btn-primary">
                        <span class="btn-spinner spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <span class="btn-text">{{ trans('backpack::crud.approve_submit') }}</span>
                    </button>
                </div>

            </div>
        </div>
    </div>
    <script>
        function btnLoader(btn_id, enabled = true){
            var idbtn = $('#'+btn_id);
            if(enabled){
                idbtn.removeAttr('disabled');
                idbtn.html("{{trans('backpack::crud.save_submit')}}");
            }else{
                idbtn.attr('disabled', 'disabled');
                idbtn.html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');
            }
        }

        var btnCreate = document.getElementById('btn-open-create');
        if (btnCreate) {
            btnCreate.addEventListener('click', function(){
                var title = "{!! $crud->getSubheading() ?? trans('backpack::crud.add').' '.$title_modal_create !!}";
                $('#modalCreate .modal-title').html(title);
            });
        }
    </script>
    <script>
        if(errorShowMessage === undefined && normalizeShowMessage === undefined){
            function errorShowMessage(rootForm, errorJson){
                window.errors = {
                    default: errorJson
                };
                $.each(errors, function(bag, errorMessages){
                    $.each(errorMessages,  function (inputName, messages) {
                        var normalizedProperty = inputName.split('.').map(function(item, index){
                                return index === 0 ? item : '['+item+']';
                            }).join('');

                        var field = $('#'+rootForm+' [name="' + normalizedProperty + '[]"]').length ?
                                    $('#'+rootForm+' [name="' + normalizedProperty + '[]"]') :
                                    $('#'+rootForm+' [name="' + normalizedProperty + '"]'),
                                    container = field.closest('.form-group');

                        // iterate the inputs to add invalid classes to fields and red text to the field container.
                        container.find('input, textarea, select').each(function() {
                            let containerField = $(this);
                            // add the invalid class to the field.
                            containerField.addClass('is-invalid');
                            // get field container
                            let container = containerField.closest('.form-group');

                            // TODO: `repeatable-group` should be deprecated in future version as a BC in favor of a more generic class `no-error-display`
                            if(!container.hasClass('repeatable-group') && !container.hasClass('no-error-display')){
                                container.addClass('text-danger');
                            }
                        });

                        $.each(messages, function(key, msg){
                            // highlight the input that errored
                            var row = $('<div class="invalid-feedback d-block">' + msg + '</div>');

                            // TODO: `repeatable-group` should be deprecated in future version as a BC in favor of a more generic class `no-error-display`
                            if(!container.hasClass('repeatable-group') && !container.hasClass('no-error-display')){
                                row.appendTo(container);
                            }


                            // highlight its parent tab
                            @if ($crud->tabsEnabled())
                            var tab_id = $(container).closest('[role="tabpanel"]').attr('id');
                            $("#form_tabs [aria-controls="+tab_id+"]").addClass('text-danger');
                            @endif
                        });
                    });
                });
            }

            function normalizeShowMessage(rootForm){
                if(window.errors === undefined){
                    window.errors = {
                        default: {}
                    };

                }
                $.each(errors, function(bag, errorMessages){
                    $.each(errorMessages,  function (inputName, messages) {
                        var normalizedProperty = inputName.split('.').map(function(item, index){
                                return index === 0 ? item : '['+item+']';
                            }).join('');

                        var field = $('#'+rootForm+' [name="' + normalizedProperty + '[]"]').length ?
                                    $('#'+rootForm+' [name="' + normalizedProperty + '[]"]') :
                                    $('#'+rootForm+' [name="' + normalizedProperty + '"]'),
                                    container = field.closest('.form-group');

                        // iterate the inputs to add invalid classes to fields and red text to the field container.
                        container.find('input, textarea, select').each(function() {
                            let containerField = $(this);
                            // add the invalid class to the field.
                            containerField.removeClass('is-invalid');
                            // get field container
                            let container = containerField.closest('.form-group');

                            // TODO: `repeatable-group` should be deprecated in future version as a BC in favor of a more generic class `no-error-display`
                            if(!container.hasClass('repeatable-group') && !container.hasClass('no-error-display')){
                                container.removeClass('text-danger');
                            }
                        });

                        $.each(messages, function(key, msg){
                            // highlight the input that errored
                            var row = $('<div class="invalid-feedback d-block">' + msg + '</div>');

                            // TODO: `repeatable-group` should be deprecated in future version as a BC in favor of a more generic class `no-error-display`
                            // if(!container.hasClass('repeatable-group') && !container.hasClass('no-error-display')){
                            //     row.appendTo(container);
                            // }

                            $('.invalid-feedback').remove();


                            // highlight its parent tab
                            @if ($crud->tabsEnabled())
                            var tab_id = $(container).closest('[role="tabpanel"]').attr('id');
                            $("#form_tabs [aria-controls="+tab_id+"]").addClass('text-danger');
                            @endif
                        });
                    });
                });
            }
        }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
@endpush

@if (isset($scripts) && isset($modals))
    @foreach ($scripts->getScripts()->where('line', 'top')->all() ?? [] as $script)
        @push('inline_scripts')
            @if($script['type'] === 'src')
                <script src="{{ $script['content'] }}"
                    @if($script['defer']) defer @endif
                    @if($script['async']) async @endif>
                </script>
            @else
                <script>
                    {!! $script['content'] !!}
                </script>
            @endif
        @endpush
    @endforeach
    @foreach ($scripts->getScripts()->where('line', 'bottom')->all() ?? [] as $script)
        @push('after_scripts')
            @if($script['type'] === 'src')
                <script src="{{ $script['content'] }}"
                    @if($script['defer']) defer @endif
                    @if($script['async']) async @endif>
                </script>
            @else
                <script>
                    {!! $script['content'] !!}
                </script>
            @endif
        @endpush
    @endforeach

    @foreach ($modals->getModals()->all() ?? [] as $modal)
        @push('after_scripts')
            @if (isset($modal['parent_view']))
                @include($modal['parent_view'], ['modal' => $modal])
            @else
                @include('vendor.backpack.crud.components.modal', ['modal' => $modal])
            @endif
        @endpush
    @endforeach
@endif
