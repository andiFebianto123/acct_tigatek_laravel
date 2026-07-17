@php
    $field_name = $field['name'];
    $selected_key = old($field_name) ?? ($field['value'] ?? ($field['default'] ?? ''));
    $options = $field['options'] ?? [];
    
    if (isset($options[''])) {
        unset($options['']);
    }

    $is_custom = $selected_key && !isset($options[$selected_key]) && $selected_key !== 'ADD_NEW';
@endphp

@include('crud::fields.inc.wrapper_start')
    <label>{!! $field['label'] !!}</label>
    @include('crud::fields.inc.translatable_icon')

    <div class="category-tags-container">
        <select
            id="{{ $field_name }}_select"
            data-init-function="bpFieldInitSelect2CategoryTags"
            class="form-control select2-category-tags-select"
            style="width: 100%;">
            <option value="">{{ $field['placeholder'] ?? '-' }}</option>
            <option value="ADD_NEW" @if($is_custom) selected @endif style="font-weight: bold; color: #007bff;">+ Tambah Kategori Baru</option>
            @foreach ($options as $key => $entry)
                <option value="{{ $key }}" @if ((string) $key === (string) $selected_key) selected @endif>
                    {{ $entry }}
                </option>
            @endforeach
        </select>

        <div id="{{ $field_name }}_custom_wrapper" class="mt-2" style="@if(!$is_custom) display: none; @endif">
            <div class="input-group">
                <input type="text" 
                       id="{{ $field_name }}_custom_input" 
                       class="form-control" 
                       placeholder="Nama Kategori Baru"
                       value="@if($is_custom){{ $selected_key }}@endif">
                <button type="button" class="btn btn-primary" id="{{ $field_name }}_save_btn">
                    <i class="la la-save"></i> Simpan
                </button>
            </div>
            <small class="text-muted">Kategori baru akan ditambahkan ke sistem.</small>
        </div>

        <input type="hidden" name="{{ $field_name }}" id="{{ $field_name }}_final" value="{{ $selected_key }}">
    </div>

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
        function bpFieldInitSelect2CategoryTags(element){
            let $select = element;
            let fieldName = "{{ $field_name }}";
            let $wrapper = $('#' + fieldName + '_custom_wrapper');
            let $customInput = $('#' + fieldName + '_custom_input');
            let $finalInput = $('#' + fieldName + '_final');
            let $saveBtn = $('#' + fieldName + '_save_btn');

            if (!$select.hasClass("select2-hidden-accessible")) {
                $select.select2({
                    width: '100%',
                    dropdownParent: $select.closest('.modal-body').length ? $select.closest('.modal-body') : $(document.body)
                });
            }

            $select.on('change', function() {
                let val = $(this).val();
                if (val === 'ADD_NEW') {
                    $wrapper.show();
                    $finalInput.val($customInput.val());
                    $customInput.focus();
                } else {
                    $wrapper.hide();
                    $finalInput.val(val);
                }
            });

            $customInput.on('input', function() {
                if ($select.val() === 'ADD_NEW') {
                    $finalInput.val($(this).val());
                }
            });

            $saveBtn.on('click', function() {
                let catName = $customInput.val();
                if (!catName) {
                    new Noty({
                        type: "error",
                        text: "Nama kategori tidak boleh kosong"
                    }).show();
                    return;
                }

                swal({
                    title: "Apakah Anda yakin ingin menyimpan kategori baru ini?",
                    icon: "warning",
                    buttons: {
                        cancel: {
                            text: "{{ trans('backpack::crud.cancel') }}",
                            value: null,
                            visible: true,
                            className: "btn btn-default",
                            closeModal: true,
                        },
                        confirm: {
                            text: "{{ trans('backpack::crud.yes') }}",
                            value: true,
                            visible: true,
                            className: "btn btn-primary",
                            closeModal: true
                        }
                    }
                }).then((value) => {
                    if (value) {
                        executeSaveCategoryAjax(catName);
                    }
                });
            });

            function executeSaveCategoryAjax(catName) {
                $saveBtn.prop('disabled', true).html('<i class="la la-spinner la-spin"></i> Menyimpan...');

                $.ajax({
                    url: "{{ backpack_url('inventory/device-stock/save-category') }}",
                    method: 'POST',
                    data: {
                        name: catName,
                        _token: "{{ csrf_token() }}"
                    },
                    success: function(response) {
                        if (response.success) {
                            new Noty({
                                type: "success",
                                text: response.message
                            }).show();

                            let newOption = new Option(response.data.name, response.data.id, true, true);
                            $select.append(newOption).trigger('change');
                            
                            $wrapper.hide();
                            $finalInput.val(response.data.id);
                        } else {
                            new Noty({
                                type: "error",
                                text: response.message
                            }).show();
                        }
                    },
                    error: function() {
                        new Noty({
                            type: "error",
                            text: "Terjadi kesalahan saat menyimpan kategori"
                        }).show();
                    },
                    complete: function() {
                        $saveBtn.prop('disabled', false).html('<i class="la la-save"></i> Simpan');
                    }
                });
            }
        }
    </script>
    @endpush
@include('crud::fields.inc.wrapper_end')
