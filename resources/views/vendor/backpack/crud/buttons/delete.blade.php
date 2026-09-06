{{-- @if ($crud->hasAccess('delete', $entry))
    <a href="javascript:void(0)" onclick="deleteEntry(this)" bp-button="delete" data-route="{{ url($crud->route.'/'.$entry->getKey()) }}" class="btn btn-sm btn-link" data-button-type="delete">
        <i class="la la-trash"></i> <span>{{ trans('backpack::crud.delete') }}</span>
    </a>
@endif --}}

@if ($crud->hasAccess('delete', $entry))
    <a href="javascript:void(0)"
        onclick="deleteEntry(this)"
        bp-button="delete"
        data-route="{{ url($crud->route.'/'.$entry->getKey()) }}"
        class="btn btn-sm btn-danger"
        data-button-type="delete"
        data-bs-toggle="modal"
        data-bs-target="#modalDelete">
        <i class="la la-trash"></i>
    </a>
@endif

{{-- Button Javascript --}}
{{-- - used right away in AJAX operations (ex: List) --}}
{{-- - pushed to the end of the page, after jQuery is loaded, for non-AJAX operations (ex: Show) --}}
@push('after_scripts') @if (request()->ajax()) @endpush @endif
@bassetBlock('backpack/crud/buttons/delete-button-'.app()->getLocale().'.js')
<script>

	if (typeof deleteEntry != 'function') {
	  $("[data-button-type=delete]").unbind('click');

	  function deleteEntry(button) {
		// ask for confirmation before deleting an item
		// e.preventDefault();
		var route = $(button).attr('data-route');

        $('#btn-delete').off('click').on('click', function(e){

            var btn = $(this);
            btn.attr('disabled', true);
            btn.find('.btn-text').html("Loading...");
            btn.find('.btn-spinner').removeClass('d-none');

            $.ajax({
			    url: route,
			    type: 'DELETE',
			    success: function(result) {
                    btn.attr('disabled', false);
                    btn.find('.btn-text').html("{{ trans('backpack::crud.delete') }}");
                    btn.find('.btn-spinner').addClass('d-none');

                    hideModal('modalDelete');

                    if (result == 1) {
                        // Redraw the table
                        if (typeof crud != 'undefined' && typeof crud.table != 'undefined') {
                            // Move to previous page in case of deleting the only item in table
                            if(crud.table.rows().count() === 1) {
                            crud.table.page("previous");
                            }

                            crud.table.draw(false);
                        }

                        // Show a success notification bubble
                        new Noty({
                        type: "success",
                        text: "{!! '<strong>'.trans('backpack::crud.delete_confirmation_title').'</strong><br>'.trans('backpack::crud.delete_confirmation_message') !!}"
                        }).show();

                        // Hide the modal, if any
                        $('.modal').modal('hide');
                    } else {
                        // if the result is an array, it means
                        // we have notification bubbles to show
                        if (result instanceof Object) {
                        // trigger one or more bubble notifications
                        Object.entries(result).forEach(function(entry, index) {
                            var type = entry[0];
                            // if(type != 'events'){
                            //     entry[1].forEach(function(message, i) {
                            //         new Noty({
                            //         type: type,
                            //         text: message
                            //         }).show();
                            //     });
                            // }
                            if(type != 'events'){
                                entry[1].forEach(function(message, i) {
                                    swal({
                                        title: "Success",
                                        text: message,
                                        icon: "success",
                                        timer: 4000,
                                        buttons: false,
                                    });

                                });
                            }
                        });
                        if(result.events){
                            forEachFlexible(result.events, function(eventname, data){
                                eventEmitter.emit(eventname, data);
                            });
                        }
                        } else {// Show an error alert
                            btn.attr('disabled', false);
                            btn.find('.btn-text').html("{{ trans('backpack::crud.delete') }}");
                            btn.find('.btn-spinner').addClass('d-none');
                            swal({
                            title: "{!! trans('backpack::crud.delete_confirmation_not_title') !!}",
                            text: "{!! trans('backpack::crud.delete_confirmation_not_message') !!}",
                            icon: "error",
                            timer: 4000,
                            buttons: false,
                            });
                        }
                    }
                },
                error: function(result) {
                    btn.attr('disabled', false);
                    btn.find('.btn-text').html("{{ trans('backpack::crud.delete') }}");
                    btn.find('.btn-spinner').addClass('d-none');
                    // Show an alert with the result

                    var errorMessage = "{!! trans('backpack::crud.delete_confirmation_not_message') !!}";
                    if(result.responseJSON?.error){
                        errorMessage = result.responseJSON?.error;
                    }

                    swal({
                    title: "{!! trans('backpack::crud.delete_confirmation_not_title') !!}",
                    text: errorMessage,
                    icon: "error",
                    timer: 4000,
                    buttons: false,
                    });
                }
            });
        });

      }
	}

	// make it so that the function above is run after each DataTable draw event
	// crud.addFunctionToDataTablesDrawEventQueue('deleteEntry');
</script>
@endBassetBlock
@if (!request()->ajax()) @endpush @endif
