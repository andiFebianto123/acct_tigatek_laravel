{{-- select2_multiple field --}}
@php
    $entity_model = $field['model'] ?? $crud->getModel()->getRelationModel($field['entity']);

    if (!isset($field['options'])) {
        $options = $entity_model::all();
    } else {
        $options = call_user_func($field['options'], $entity_model::query());
    }

    $field['allows_null'] = $field['allows_null'] ?? true;

    // Ambil value: prioritas old() -> $field['value'] -> $entry relation -> default
    $value = old($field['name']);
    if ($value === null) {
        if (isset($field['value'])) {
            $value = $field['value'];
        } elseif (isset($entry) && is_object($entry)) {
            $value = $entry->{$field['name']};
        } else {
            $value = $field['default'] ?? collect();
        }
    }

    if (is_a($value, \Illuminate\Support\Collection::class)) {
        $selectedKeys = $value->pluck(app($entity_model)->getKeyName())->toArray();
    } elseif (is_array($value)) {
        $selectedKeys = array_map(function($v) {
            return is_object($v) ? $v->getKey() : $v;
        }, $value);
    } else {
        $selectedKeys = (array) $value;
    }

    $uniqueId = 'select2_multi_' . str_replace(['[', ']', '.'], '_', $field['name']) . '_' . uniqid();
@endphp

@include('crud::fields.inc.wrapper_start')

    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    {{-- Hidden input agar saat semua pilihan dihapus, request tetap mengirimkan field kosong --}}
    <input type="hidden" name="{{ $field['name'] }}" value="" @if(in_array('disabled', $field['attributes'] ?? [])) disabled @endif />

    <select
        id="{{ $uniqueId }}"
        name="{{ $field['name'] }}[]"
        @include('crud::fields.inc.attributes', ['default_class' => 'form-control select2 select2_multiple_field'])
        multiple>

        @if (count($options))
            @foreach ($options as $option)
                <option value="{{ $option->getKey() }}"
                    @if(in_array($option->getKey(), $selectedKeys))
                        selected
                    @endif
                >
                    {{ $option->{$field['attribute'] ?? 'name'} }}
                </option>
            @endforeach
        @endif

    </select>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif

@include('crud::fields.inc.wrapper_end')

<style>
    .select2-container {
        width: 100% !important;
    }
    .select2-container .select2-selection--multiple {
        min-height: calc(2.25rem + 2px);
        border: 1px solid #ced4da;
        border-radius: 0.375rem;
        padding: 2px 6px;
    }
    .select2-container .select2-selection--multiple .select2-selection__choice {
        background-color: #0d6efd !important;
        border: 1px solid #0d6efd !important;
        color: #ffffff !important;
        border-radius: 0.25rem;
        padding: 2px 8px;
        margin-top: 4px;
        margin-right: 5px;
        font-size: 0.875rem;
    }
    .select2-container .select2-selection--multiple .select2-selection__choice__remove {
        color: #ffffff !important;
        margin-right: 5px;
    }
    .select2-container .select2-selection--multiple .select2-selection__choice__remove:hover {
        color: #ffcccc !important;
    }
    .select2-dropdown {
        z-index: 99999 !important;
    }
</style>

<script>
    (function() {
        function initSelect2Instance() {
            var $el = $('#{{ $uniqueId }}');
            if (!$el.length) return;

            var $modal = $el.closest('.modal');
            var dropdownParent = $modal.length ? $modal.find('.modal-body') : null;

            if ($el.hasClass("select2-hidden-accessible")) {
                $el.select2('destroy');
            }

            $el.select2({
                width: '100%',
                placeholder: $el.attr("placeholder") || "{{ $field['placeholder'] ?? 'Pilih ' . ($field['label'] ?? '') }}",
                allowClear: true,
                dropdownParent: dropdownParent
            });
        }

        // Jalankan inisialisasi segera dan setelah modal siap
        setTimeout(initSelect2Instance, 100);
        setTimeout(initSelect2Instance, 300);

        $(document).one('shown.bs.modal', '#modalEdit, #modalCreate', function() {
            initSelect2Instance();
        });
    })();
</script>
