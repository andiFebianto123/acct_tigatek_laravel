@php
  $field['wrapper'] = $field['wrapper'] ?? $field['wrapperAttributes'] ?? [];
  $field['wrapper']['class'] = $field['wrapper']['class'] ?? "hidden";
  $entry_value = $crud?->entry;
@endphp

@include('crud::fields.inc.wrapper_start')
  <input
  	type="hidden"
    name="{{ $field['name'] }}"
    value="{{ old_empty_or_null($field['name'], '') ??  $field['value'] ?? $field['default'] ?? '' }}"
    @include('crud::fields.inc.attributes')
  	>
@include('crud::fields.inc.wrapper_end')

@push('crud_fields_scripts')
    <script>
        SIAOPS.setAttribute('logic_purchase_order', function(){
            return {
                form_type : "{{ $crud->getActionMethod() }}",
                toggleFormFields: function(){
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';
                    var po_type = $(form+' select[name="po_type"]').val();
                    
                    var workCodeWrapper = $(form+' input[name="work_code"]').closest('.form-group');
                    var workCodeInput = $(form+' input[name="work_code"]');
                    
                    var jobNameWrapper = $(form+' input[name="job_name"]').closest('.form-group');
                    var jobNameLabel = jobNameWrapper.find('label');
                    var jobNameInput = $(form+' input[name="job_name"]');

                    var jobDescWrapper = $(form+' [name="job_description"]').closest('.form-group');
                    var jobDescInput = $(form+' [name="job_description"]');
                    
                    if (po_type === 'supplier') {
                        workCodeWrapper.hide();
                        workCodeInput.attr('disabled', true);
                        
                        jobNameLabel.text("{{ trans('backpack::crud.po.field.job_name.label_supplier') }}");
                        jobNameInput.attr('placeholder', "{{ trans('backpack::crud.po.field.job_name.placeholder_supplier') }}");

                        jobDescWrapper.hide();
                        jobDescInput.attr('disabled', true);
                    } else {
                        workCodeWrapper.show();
                        workCodeInput.removeAttr('disabled');
                        
                        jobNameLabel.text("{{ trans('backpack::crud.po.field.job_name.label') }}");
                        jobNameInput.attr('placeholder', "{{ trans('backpack::crud.po.field.job_name.placeholder') }}");

                        jobDescWrapper.show();
                        jobDescInput.removeAttr('disabled');
                    }
                },
                load: function(){
                    var instance = this;
                    var form = (this.form_type == 'create') ? '#form-create' : '#form-edit';

                    // Initial state toggle
                    instance.toggleFormFields();

                    // On change of po_type
                    $(form+' select[name="po_type"]').on('change select2:select', function() {
                        instance.toggleFormFields();
                    });
                }
            }
        });
        SIAOPS.getAttribute('logic_purchase_order').load();
    </script>
@endpush
