@php
    $field_id = $field['id'] ?? $field['name'];
    $entity = $field['entity'] ?? $field['name'];
    $attribute = $field['attribute'] ?? 'name';
    $data_source = $field['data_source'] ?? false;
    $minimum_input_length = $field['minimum_input_length'] ?? 2;
    $placeholder = $field['placeholder'] ?? 'Select an option';
    $delay = $field['delay'] ?? 250;
    $method = strtoupper($field['method'] ?? 'POST');
    $dependencies = $field['dependencies'] ?? [];
    $include_all_form_fields = $field['include_all_form_fields'] ?? false;

    $value_name = '';


    if(array_key_exists('value', $field)) {
        $entity_model = $field['model'] ?? $crud->model::getRelationModel($field['entity']);
        $entry_data = $entity_model::find($field['value']);
        if($entry_data) {
            $value_name = $entry_data->{$attribute};
        }
    }

@endphp

@include('crud::fields.inc.wrapper_start')

    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    @if(isset($field['prefix']) || isset($field['suffix'])) <div class="input-group"> @endif
        @if(isset($field['prefix'])) <span class="input-group-text">{!! $field['prefix'] !!}</span> @endif
        <select
            name="{{ $field['name'] }}"
            style="width: 100%"
            data-init-function="bpFieldInitSelect2FromAjaxCustom"
            data-minimum-input-length="{{ $minimum_input_length }}"
            data-placeholder="{{ $placeholder }}"
            data-data-source="{{ $data_source }}"
            data-delay="{{ $delay }}"
            data-method="{{ $method }}"
            data-include-all-form-fields="{{ $include_all_form_fields ? 'true' : 'false' }}"
            data-value-id = "{{ $field['value'] ?? ''}}"
            data-value-name = "{{ $value_name }}"
            {{-- id="select2_{{ $field_id }}" --}}
            @include('crud::fields.inc.attributes')
            @if(count($dependencies))
                data-dependencies="{{ implode(',', $dependencies) }}"
            @endif
            {{-- @if($field['attributes'] ?? false)
                @foreach ($field['attributes'] as $attr => $value)
                    {{ $attr }}="{{ $value }}"
                @endforeach
            @endif --}}
        >
            {{-- @if(isset($field['value']) && $field['value'])
                <option value="{{ $field['value'] }}" selected="selected">{{ $field['value'] }}</option>
            @endif --}}
        </select>
        @if(isset($field['suffix'])) <span class="input-group-text">{!! $field['suffix'] !!}</span> @endif
    @if(isset($field['prefix']) || isset($field['suffix'])) </div> @endif

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif

@push('crud_fields_scripts')
<style>
    form .select2.select2-container.select2-container--focus {
        border-radius: 0.2rem !important;
    }
    form .select2.select2-container {
        border: none !important;
    }

    .select2-container .select2-selection--single .select2-selection__rendered {
        line-height: 21px !important;
        padding-left: 0px !important;
        font-size: 1rem !important;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow{
        height: 100% !important;
    }
    .select2-container .select2-selection--single {
        height: calc(2.25rem + 2px);
        padding: 0.375rem 0.75rem;
        border: 1px solid #ced4da;
        border-radius: 0.2rem;
    }
</style>
<script>
    function bpFieldInitSelect2FromAjaxCustom(element) {
        if (!element.data('data-source')) {
            console.error('Select2 AJAX: data_source URL is required');
            return;
        }

        var formCrud = "{{ $crud->getActionMethod() }}";

        var delay = parseInt(element.data('delay')) || 250;
        var method = element.data('method') || 'post';
        var includeAllFormFields = element.data('include-all-form-fields') == true;
        var dependencies = element.data('dependencies') ? element.data('dependencies').split(',') : [];
        var valueId = element.data('value-id');
        var valueName = element.data('value-name');        

        var form = (formCrud == 'create') ? '#modalCreate' : '#modalEdit';


        // Reset value when dependencies change
        dependencies.forEach(function(depName) {
            var depElement = $('[name="' + depName + '"]');
            if (depElement.length) {
                depElement.on('change', function() {
                    element.val(null).trigger('change');
                });
            }
        });

        // element.select2();
        // element.select2('destroy');
        element.select2({
            dropdownParent: $(form+" .modal-body"),
            ajax: {
                url: element.data('data-source'),
                dataType: 'json',
                delay: delay,
                type: method,
                data: function(params) {
                    var query = {
                        q: params.term,
                        page: params.page || 1,
                    };

                    if (includeAllFormFields) {
                        // Serialize all form fields except this select
                        var form = element.closest('form');
                        if(form.length) {
                            var formDataArray = form.serializeArray();
                            formDataArray.forEach(function(item){
                                if(item.name !== element.attr('name')) {
                                    query[item.name] = item.value;
                                }
                            });
                        }
                        console.log(query, 'andi');
                    }

                    return query;
                },
                processResults: function(data, params) {
                    return {
                        results: data.results
                    };
                },
                cache: true,
            },
            placeholder: element.data('placeholder'),
            minimumInputLength: element.data('minimum-input-length'),
            width: '100%',
            allowClear: true,
        });

        if(valueName != '' && valueName != null) {
            var selectedOption = new Option(valueName, valueId, true, true);
            element.append(selectedOption).trigger('change');
        }

    }
</script>
@endpush
@include('crud::fields.inc.wrapper_end')
