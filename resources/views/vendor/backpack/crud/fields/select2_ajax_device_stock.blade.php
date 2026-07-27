@php
    $field_id = $field['id'] ?? $field['name'];
    $entity = $field['entity'] ?? $field['name'];
    $attribute = $field['attribute'] ?? 'name';
    $data_source = $field['data_source'] ?? false;
    $minimum_input_length = $field['minimum_input_length'] ?? 0;
    $placeholder = $field['placeholder'] ?? 'Pilih Nama Barang';
    $delay = $field['delay'] ?? 250;
    $method = strtoupper($field['method'] ?? 'POST');
    $value_name = '';

    if (array_key_exists('value', $field) && $field['value']) {
        $entity_model = $field['model'] ?? \App\Models\DeviceStock::class;
        $entry_data = $entity_model::find($field['value']);
        if ($entry_data) {
            $value_name = $entry_data->{$attribute};
        }
    }
@endphp

@include('crud::fields.inc.wrapper_start')

    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <select
        name="{{ $field['name'] }}"
        style="width: 100%"
        data-init-function="bpFieldInitSelect2AjaxDeviceStock"
        data-minimum-input-length="{{ $minimum_input_length }}"
        data-placeholder="{{ $placeholder }}"
        data-data-source="{{ $data_source }}"
        data-delay="{{ $delay }}"
        data-method="{{ $method }}"
        data-value-id="{{ $field['value'] ?? '' }}"
        data-value-name="{{ $value_name }}"
        @include('crud::fields.inc.attributes')
    >
    </select>

    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif

@push('crud_fields_scripts')
<script>
    if (typeof bpFieldInitSelect2AjaxDeviceStock === 'function') {
        // function already declared globally
    } else {
        function bpFieldInitSelect2AjaxDeviceStock(element) {
            if (!element.data('data-source')) {
                console.error('Select2 AJAX Device Stock: data_source URL is required');
                return;
            }

            var delay = parseInt(element.data('delay')) || 250;
            var method = element.data('method') || 'post';
            var valueId = element.data('value-id');
            var valueName = element.data('value-name');

            element.select2({
                dropdownParent: $(".modal.show .modal-body").length ? $(".modal.show .modal-body") : $(document.body),
                ajax: {
                    url: element.data('data-source'),
                    dataType: 'json',
                    delay: delay,
                    type: method,
                    data: function(params) {
                        return {
                            q: params.term || '',
                            page: params.page || 1
                        };
                    },
                    processResults: function(data) {
                        return {
                            results: data.results
                        };
                    },
                    cache: true
                },
                placeholder: element.data('placeholder'),
                minimumInputLength: parseInt(element.data('minimum-input-length')) || 0,
                width: '100%',
                allowClear: true
            });

            if (valueId && valueName) {
                var selectedOption = new Option(valueName, valueId, true, true);
                element.append(selectedOption).trigger('change');
            }
        }
    }
</script>
@endpush
@include('crud::fields.inc.wrapper_end')
