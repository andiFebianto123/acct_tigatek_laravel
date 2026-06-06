{{-- dependencyJson --}}
@php
  $field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
  $field['wrapper']['class'] = $field['wrapper']['class'] ?? 'form-group col-sm-12';
  $field['wrapper']['class'] = $field['wrapper']['class'].' checklist_dependency';
  $field['wrapper']['data-entity'] = $field['wrapper']['data-entity'] ?? $field['field_unique_name'];
  $field['wrapper']['data-init-function'] = $field['wrapper']['init-function'] ?? 'bpFieldInitChecklistDependencyElement';
@endphp

@include('crud::fields.inc.wrapper_start')

<label>{!! $field['label'] !!}</label>
<?php
    $entity_model = $crud->getModel();

    //short name for dependency fields
    $primary_dependency = $field['subfields']['primary'];
    $secondary_dependency = $field['subfields']['secondary'];

    //all items with relation
    $dependencies = $primary_dependency['model']::with($primary_dependency['entity_secondary']);

    if(isset($primary_dependency['options']) && $primary_dependency['options'] instanceof \Closure){
        $dependencies = $primary_dependency['options']($dependencies);
    }

    // check if dependencies are a query builder, or the developer already called `->get()` on it.
    if ($dependencies instanceof \Illuminate\Contracts\Database\Query\Builder) {
        $dependencies = $dependencies->get();
    }

    $dependencyArray = [];

    //convert dependency array to simple matrix ( primary id as key and array with secondaries id )
    foreach ($dependencies as $primary) {
        $dependencyArray[$primary->id] = [];
        foreach ($primary->{$primary_dependency['entity_secondary']} as $secondary) {
            $dependencyArray[$primary->id][] = $secondary->id;
        }
    }

    $old_primary_dependency = old_empty_or_null($primary_dependency['name'], false) ?? false;
    $old_secondary_dependency = old_empty_or_null($secondary_dependency['name'], false) ?? false;

    //for update form, get initial state of the entity
    if (isset($id) && $id) {
        //get entity with relations for primary dependency
        $entity_dependencies = $entity_model->with($primary_dependency['entity'])
        ->with($primary_dependency['entity'].'.'.$primary_dependency['entity_secondary'])
        ->find($id);

        $secondaries_from_primary = [];

        //convert relation in array
        $primary_array = $entity_dependencies->{$primary_dependency['entity']}->toArray();

        $secondary_ids = [];
        //create secondary dependency from primary relation, used to check what checkbox must be checked from second checklist
        if ($old_primary_dependency) {
            foreach ($old_primary_dependency as $primary_item) {
                foreach ($dependencyArray[$primary_item] as $second_item) {
                    $secondary_ids[$second_item] = $second_item;
                }
            }
        } else { //create dependencies from relation if not from validate error
            foreach ($primary_array as $primary_item) {
                foreach ($primary_item[$secondary_dependency['entity']] as $second_item) {
                    $secondary_ids[$second_item['id']] = $second_item['id'];
                }
            }
        }
    }

    //json encode of dependency matrix
    $dependencyJson = json_encode($dependencyArray);

    $primaryDependencyOptionQuery = $primary_dependency['model']::query();

    if(isset($primary_dependency['options']) && $primary_dependency['options'] instanceof \Closure){
        $primaryDependencyOptionQuery = $primary_dependency['options']($primaryDependencyOptionQuery);
    }

    $primaryDependencyOptions = $primaryDependencyOptionQuery->get();

    $secondaryDependencyOptions = $secondary_dependency['model']::all();
    ?>

    <style>
      .checklist_dependency tr.table-row-inherited {
          background-color: rgba(0, 123, 255, 0.08) !important;
      }
      .checklist_dependency tr.table-row-extra {
          background-color: rgba(40, 167, 69, 0.08) !important;
      }
    </style>

    <div class="container">
      <div class="row mb-3">
          <div class="col-sm-12">
              <label class="font-weight-bold">{!! $primary_dependency['label'] !!}</label>
              @include('crud::fields.inc.translatable_icon', ['field' => $primary_dependency])
          </div>
      </div>

      <div class="row mb-4">
          <div class="hidden_fields_primary" data-name = "{{ $primary_dependency['name'] }}">
          <input type="hidden" bp-field-name="{{$primary_dependency['name']}}" name="{{$primary_dependency['name']}}" value="" />
          @if(isset($field['value']))
              @if($old_primary_dependency)
                  @foreach($old_primary_dependency as $item )
                  <input type="hidden" class="primary_hidden" name="{{ $primary_dependency['name'] }}[]" value="{{ $item }}">
                  @endforeach
              @else
                  @foreach( $field['value'][0]->pluck('id', 'id')->toArray() as $item )
                  <input type="hidden" class="primary_hidden" name="{{ $primary_dependency['name'] }}[]" value="{{ $item }}">
                  @endforeach
              @endif
            @endif
          </div>

      @foreach ($primaryDependencyOptions as $connected_entity_entry)
          <div class="col-sm-{{ isset($primary_dependency['number_columns']) ? intval(12/$primary_dependency['number_columns']) : '4'}}">
              <div class="checkbox">
                  <label class="font-weight-normal">
                      <input type="checkbox"
                          data-id = "{{ $connected_entity_entry->id }}"
                          class = 'primary_list'
                          @foreach ($primary_dependency as $attribute => $value)
                              @if (is_string($attribute) && $attribute != 'value')
                                  @if ($attribute=='name')
                                  {{ $attribute }}="{{ $value }}_show[]"
                                  @elseif(! $value instanceof \Closure)
                                  {{ $attribute }}="{{ $value }}"
                                  @endif
                              @endif
                          @endforeach
                          value="{{ $connected_entity_entry->id }}"

                          @if( ( isset($field['value']) && is_array($field['value']) && in_array($connected_entity_entry->id, $field['value'][0]->pluck('id', 'id')->toArray())) || $old_primary_dependency && in_array($connected_entity_entry->id, $old_primary_dependency))
                          checked = "checked"
                          @endif >
                          {{ $connected_entity_entry->{$primary_dependency['attribute']} }}
                  </label>
              </div>
          </div>
      @endforeach
      </div>

      <div class="row mb-2">
          <div class="col-sm-12">
              <label class="font-weight-bold">{!! $secondary_dependency['label'] !!}</label>
              @include('crud::fields.inc.translatable_icon', ['field' => $secondary_dependency])
          </div>
      </div>

      <div class="row">
          <div class="col-sm-12">
              <div class="hidden_fields_secondary" data-name="{{ $secondary_dependency['name'] }}">
                <input type="hidden" bp-field-name="{{$secondary_dependency['name']}}" name="{{$secondary_dependency['name']}}" value="" />
                @if(isset($field['value']))
                  @if($old_secondary_dependency)
                    @foreach($old_secondary_dependency as $item )
                      <input type="hidden" class="secondary_hidden" name="{{ $secondary_dependency['name'] }}[]" value="{{ $item }}">
                    @endforeach
                  @else
                    @foreach( $field['value'][1]->pluck('id', 'id')->toArray() as $item )
                      <input type="hidden" class="secondary_hidden" name="{{ $secondary_dependency['name'] }}[]" value="{{ $item }}">
                    @endforeach
                  @endif
                @endif
              </div>

              <div class="table-responsive">
                  <table class="table table-bordered table-striped" id="table_{{ $field['field_unique_name'] }}" style="width:100%">
                      <thead>
                          <tr>
                              <th style="width: 80px; text-align: center;">Pilih</th>
                              <th>Nama Hak Akses (Permission)</th>
                          </tr>
                      </thead>
                      <tbody>
                      @foreach ($secondaryDependencyOptions as $connected_entity_entry)
                          <tr>
                              <td style="text-align: center;">
                                  <input type="checkbox"
                                           class="secondary_list"
                                           data-id="{{ $connected_entity_entry->id }}"
                                       @foreach ($secondary_dependency as $attribute => $value)
                                           @if (is_string($attribute) && $attribute != 'value')
                                             @if ($attribute=='name')
                                               {{ $attribute }}="{{ $value }}_show[]"
                                             @elseif(! $value instanceof \Closure)
                                               {{ $attribute }}="{{ $value }}"
                                             @endif
                                           @endif
                                       @endforeach
                                        value="{{ $connected_entity_entry->id }}"

                                       @if( ( isset($field['value']) && is_array($field['value']) && (  in_array($connected_entity_entry->id, $field['value'][1]->pluck('id', 'id')->toArray()) || isset( $secondary_ids[$connected_entity_entry->id])) || $old_secondary_dependency && in_array($connected_entity_entry->id, $old_secondary_dependency)))
                                            checked="checked"
                                            @if(isset( $secondary_ids[$connected_entity_entry->id]))
                                             disabled="disabled"
                                            @endif
                                       @endif >
                              </td>
                              <td>{{ $connected_entity_entry->{$secondary_dependency['attribute']} }}</td>
                          </tr>
                      @endforeach
                      </tbody>
                  </table>
              </div>
          </div>
      </div>
    </div>{{-- /.container --}}


    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif

@include('crud::fields.inc.wrapper_end')

{{-- ########################################## --}}
{{-- Extra CSS and JS for this particular field --}}
{{-- If a field type is shown multiple times on a form, the CSS and JS will only be loaded once --}}

{{-- FIELD JS - will be loaded in the after_scripts section --}}
@push('crud_fields_scripts')
  <script>
      var {{ $field['field_unique_name'] }} = {!! $dependencyJson !!};
  </script>

  @bassetBlock('backpack/crud/fields/checklist-dependency-datatable-field.js')
    <script>
      function bpFieldInitChecklistDependencyElement(element) {
          var unique_name = element.data('entity');
          var dependencyJson = window[unique_name];
          var thisField = element;

          // Inisialisasi DataTable untuk daftar permission
          var tableId = '#table_' + unique_name;
          var table = $(tableId).DataTable({
              paging: true,
              searching: true,
              info: true,
              pageLength: 10,
              lengthMenu: [5, 10, 25, 50],
              columnDefs: [
                  { orderable: false, targets: 0 }
              ]
          });

          // Fungsi untuk memperbarui highlight warna baris berdasarkan state checkbox
          var updateRowStyles = function() {
              table.rows().every(function() {
                  var rowNode = $(this.node());
                  var checkbox = rowNode.find('input.secondary_list');
                  if (checkbox.length) {
                      if (checkbox.is(':disabled')) {
                          rowNode.addClass('table-row-inherited').removeClass('table-row-extra');
                      } else if (checkbox.is(':checked')) {
                          rowNode.addClass('table-row-extra').removeClass('table-row-inherited');
                      } else {
                          rowNode.removeClass('table-row-inherited table-row-extra');
                      }
                  }
              });
          };

          // Update styles ketika tabel di-draw ulang (filtering, pagination, sorting)
          table.on('draw', function() {
              updateRowStyles();
          });

          var handleCheckInput = function(el, field, dependencyJson) {
            let idCurrent = el.data('id');
            //add hidden field with this value
            let nameInput = field.find('.hidden_fields_primary').data('name');
            if(field.find('input.primary_hidden[value="'+idCurrent+'"]').length === 0) {
              let inputToAdd = $('<input type="hidden" class="primary_hidden" name="'+nameInput+'[]" value="'+idCurrent+'">');

              field.find('.hidden_fields_primary').append(inputToAdd);
              field.find('.hidden_fields_primary').find('input.primary_hidden[value="'+idCurrent+'"]').trigger('change');
            }
            $.each(dependencyJson[idCurrent], function(key, value){
              // Menggunakan API table.$ untuk menjangkau elemen di semua page
              var checkbox = table.$('input.secondary_list[value="'+value+'"]');
              checkbox.prop( "checked", true );
              checkbox.prop( "disabled", true );
              checkbox.attr('forced-select', 'true');
              
              //remove hidden fields with secondary dependency if was set
              var hidden = field.find('input.secondary_hidden[value="'+value+'"]');
              if(hidden)
                hidden.remove();
            });
            updateRowStyles();
          };
          
          thisField.find('div.hidden_fields_primary').children('input').first().on('CrudField:disable', function(e) {
              let input = $(e.target);
              input.parent().parent().find('input[type=checkbox]').attr('disabled', 'disabled');
              input.siblings('input').attr('disabled','disabled');
          });

          thisField.find('div.hidden_fields_primary').children('input').first().on('CrudField:enable', function(e) {
              let input = $(e.target);
              input.parent().parent().find('input[type=checkbox]').not('[forced-select]').removeAttr('disabled');
              input.siblings('input').removeAttr('disabled');
          });

          thisField.find('div.hidden_fields_secondary').children('input').first().on('CrudField:disable', function(e) {
              let input = $(e.target);
              table.$('input[type=checkbox]').attr('disabled', 'disabled');
              input.siblings('input').attr('disabled','disabled');
          });

          thisField.find('div.hidden_fields_secondary').children('input').first().on('CrudField:enable', function(e) {
              let input = $(e.target);
              table.$('input[type=checkbox]').not('[forced-select]').removeAttr('disabled');
              input.siblings('input').removeAttr('disabled');
          });

          // Inisialisasi awal saat halaman dimuat
          thisField.find('.primary_list').each(function() {
            var checkbox = $(this);
            if(checkbox.is(':checked')){
               handleCheckInput(checkbox, thisField, dependencyJson);
            }
            
            // register event saat role berubah
            checkbox.change(function(){
              if(checkbox.is(':checked')){
                handleCheckInput(checkbox, thisField, dependencyJson);
              }else{
                let idCurrent = checkbox.data('id');
                //remove hidden field dengan value ini
                thisField.find('input.primary_hidden[value="'+idCurrent+'"]').remove();

                // uncheck dan aktifkan kembali secondary checkbox jika tidak ada di primary lain yang tercentang
                var secondary = dependencyJson[idCurrent];
                var selected = [];
                thisField.find('input.primary_hidden').each(function (index, input){
                  selected.push( $(this).val() );
                });

                $.each(secondary, function(index, secondaryItem){
                  var ok = 1;
                  $.each(selected, function(index2, selectedItem){
                    if( dependencyJson[selectedItem].indexOf(secondaryItem) != -1 ){
                      ok = 0;
                    }
                  });

                  if(ok){
                    var secCheckbox = table.$('input.secondary_list[value="'+secondaryItem+'"]');
                    secCheckbox.prop('checked', false);
                    secCheckbox.prop('disabled', false);
                    secCheckbox.removeAttr('forced-select');
                  }
                });
                updateRowStyles();
              }
            });
          });

          // Menggunakan event delegation pada DataTable agar mencakup baris di halaman manapun
          $(tableId).on('click', '.secondary_list', function() {
            var checkbox = $(this);
            var idCurrent = checkbox.data('id');
            if(checkbox.is(':checked')){
              // Tambahkan hidden field
              var nameInput = thisField.find('.hidden_fields_secondary').data('name');
              var inputToAdd = $('<input type="hidden" class="secondary_hidden" name="'+nameInput+'[]" value="'+idCurrent+'">');
              thisField.find('.hidden_fields_secondary').append(inputToAdd);
            }else{
              // Hapus hidden field
              thisField.find('input.secondary_hidden[value="'+idCurrent+'"]').remove();
            }
            updateRowStyles();
          });

          // Jalankan pewarnaan awal
          updateRowStyles();
      }
    </script>
  @endBassetBlock
@endpush
