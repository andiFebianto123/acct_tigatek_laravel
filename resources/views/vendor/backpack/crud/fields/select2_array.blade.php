@php
    // $entity_model = $field['model'] ?? $crud->model::getRelationModel($field['entity']);
    $selected_key = old($field['name']) ?? ($field['value'] ?? ($field['default'] ?? ''));
@endphp

@include('crud::fields.inc.wrapper_start')

    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    @if(isset($field['prefix']) || isset($field['suffix'])) <div class="input-group"> @endif
        @if(isset($field['prefix'])) <span class="input-group-text">{!! $field['prefix'] !!}</span> @endif
        <select
            name="{{ $field['name'] }}"
            data-init-function="bpFieldInitSelect2"
            @include('crud::fields.inc.attributes', ['default_class' => 'form-control select2 select2_field'])>
            @if (!empty($field['allows_null']) && $field['allows_null'] === true)
                <option value="">-</option>
            @endif

            @php
                $options = $field['options'] ?? [];
            @endphp

            @foreach ($options as $key => $entry)
                <option value="{{ $key }}" @if ((string) $key === (string) $selected_key) selected @endif>
                    {{ $entry }}
                </option>
            @endforeach
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
        async function bpFieldInitSelect2(element){
            var formCrud = "{{ $crud->getActionMethod() }}";
            await new Promise((resolve) => {
                setTimeout(() => {
                    resolve(1);
                }, 100);
            })
            var form = (formCrud == 'create') ? '#modalCreate' : '#modalEdit';

            element.select2({
                // theme: 'bootstrap-5',
                width: '100%',
                dropdownParent: $(form+" .modal-body")
            });
        }
    </script>
        <script>
            // $('.select2_field').val(null).trigger('change'); // Clear
            // $('#').select2('destroy');
            // $('#{{$field['name']}}').select2({
            //         // theme: 'bootstrap-5',
            //         // width: '100%',
            //         dropdownParent: $(".modal.show")
            // });
            // var d =  {!! json_encode($field) !!};
            // // console.log(d);
            // // if(d !== undefined){
            // //     $('.select2_field').
            // // }
        </script>
    @endpush
@include('crud::fields.inc.wrapper_end')

