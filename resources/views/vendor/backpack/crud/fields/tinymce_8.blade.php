{{-- TinyMCE 8.7.0 Rich Text Editor field --}}
@php
    // Default options for TinyMCE 8
    $defaultOptions = [
        'height' => 300,
        'menubar' => false,
        'plugins' => [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'code', 'help', 'wordcount',
        ],
        'toolbar_mode' => 'wrap',
        'toolbar' => 'undo redo | styles | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent',
        'branding' => false,
        'promotion' => false,
        'help_accessibility' => false
    ];

    $field['options'] = array_merge($defaultOptions, $field['options'] ?? []);
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')
    <textarea
        name="{{ $field['name'] }}"
        data-init-function="bpFieldInitTinyMCE8Element"
        data-options="{{ json_encode($field['options']) }}"
        bp-field-main-input
        @include('crud::fields.inc.attributes', ['default_class' => 'form-control tinymce-8'])
        >{{ old_empty_or_null($field['name'], '') ?? $field['value'] ?? $field['default'] ?? '' }}</textarea>

    {{-- HINT --}}
    @if (isset($field['hint']))
        <p class="help-block">{!! $field['hint'] !!}</p>
    @endif
@include('crud::fields.inc.wrapper_end')

{{-- FIELD JS - loaded in the after_scripts section --}}
@push('crud_fields_scripts')
    {{-- Include TinyMCE 8.7.0 JS --}}
    <script>
        window.tinyMCEPreInit = {
            baseURL: "{{ asset('packages/tinymce_8.7.0/tinymce/js/tinymce') }}",
            suffix: ".min"
        };
    </script>
    <script src="{{ asset('packages/tinymce_8.7.0/tinymce/js/tinymce/tinymce.min.js') }}" referrerpolicy="origin" crossorigin="anonymous"></script>

    @bassetBlock('backpack/crud/fields/tinymce_8.js')
    <script>
        // Prevent Bootstrap modal from stealing focus from TinyMCE dropdowns/modals
        document.addEventListener('focusin', function (e) {
            if (e.target.closest(".tox-tinymce, .tox-tinymce-aux, .moxman-window, .tam-assetmanager-root") !== null) {
                e.stopImmediatePropagation();
            }
        });

        function bpFieldInitTinyMCE8Element(element) {
            var tinymceOptions = element.data('options') || {};
            
            // Ensure unique ID for editor reference
            var id = element.attr('id');
            if (!id) {
                id = 'tinymce_' + Math.random().toString(36).substring(2, 9);
                element.attr('id', id);
            }

            var defaultOptions = {
                target: element[0],
                setup: function (editor) {
                    // Synchronize editor contents back to textarea on keyup, change, and input
                    editor.on('change keyup input', function () {
                        editor.save();
                        element.trigger('change');
                    });
                }
            };

            // Merge configured options
            var options = $.extend({}, defaultOptions, tinymceOptions);

            options = {
                ...options,
                license_key: 'gpl'
            };
            // Initialize editor
            tinymce.init(options);

            // Handle Backpack disable/enable events
            element.on('CrudField:disable', function(e) {
                var editor = tinymce.get(id);
                if (editor) {
                    editor.mode.set('readonly');
                }
            });

            element.on('CrudField:enable', function(e) {
                var editor = tinymce.get(id);
                if (editor) {
                    editor.mode.set('design');
                }
            });
        }
    </script>
    @endBassetBlock
@endpush
