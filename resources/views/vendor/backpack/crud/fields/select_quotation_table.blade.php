@php
    $settings = \App\Models\Setting::first();
    $company_id = old('company_id') ?? $crud->entry->company_id ?? null;
@endphp

<div class="form-group col-md-12">
    <label class="font-weight-bold text-primary"><i class="la la-file-text"></i> {{ $field['label'] }}</label>
    <div id="quotation-selection-table-container" class="card border-primary shadow-sm">
        <div class="card-body p-2">
            <div class="alert alert-info py-2 mb-2">
                <small><i class="la la-info-circle"></i> {{ trans('backpack::crud.client_po.field.quotation_selection_info') ?? 'Pilih satu atau beberapa penawaran di bawah untuk mengisi data PO secara otomatis.' }}</small>
            </div>
            <table id="quotation-selection-table" class="table table-sm table-striped table-hover mb-0" style="width:100%">
            <thead>
                <tr>
                    <th><input type="checkbox" id="select-all-quotations"></th>
                    <th>{{ trans('backpack::crud.client_quotation.column.work_code') }}</th>
                    <th>{{ trans('backpack::crud.client_quotation.column.client_id') }}</th>
                    <th>{{ trans('backpack::crud.client_quotation.column.job_name') }}</th>
                    <th>{{ trans('backpack::crud.client_quotation.column.job_value_exclude_ppn') }}</th>
                    <th>{{ trans('backpack::crud.client_quotation.column.tax_ppn') }}</th>
                    <th>{{ trans('backpack::crud.client_quotation.column.job_value_include_ppn') }}</th>
                </tr>
            </thead>
            <tbody>
                <!-- AJAX loaded -->
            </tbody>
        </table>
    </div>
</div>
    <input type="hidden" name="quotation_ids" id="quotation_ids" value="{{ old('quotation_ids') }}">
</div>

@push('crud_fields_scripts')
<script>
    $(document).ready(function() {
        let table = $('#quotation-selection-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ backpack_url('client/po/get-quotations') }}",
                data: function(d) {
                    d.company_id = $('select[name="company_id"]').val();
                }
            },
            columns: [
                { 
                    data: 'id', 
                    orderable: false, 
                    searchable: false,
                    render: function(data, type, row) {
                        let checked = $('#quotation_ids').val().split(',').includes(data.toString()) ? 'checked' : '';
                        return `<input type="checkbox" class="quotation-checkbox" value="${data}" ${checked}>`;
                    }
                },
                { data: 'work_code' },
                { data: 'client_name' },
                { data: 'job_name' },
                { data: 'job_value' },
                { data: 'tax_ppn' },
                { data: 'job_value_include_ppn' }
            ],
            order: [[1, 'asc']],
            pageLength: 5,
            lengthMenu: [5, 10, 25, 50]
        });

        $('select[name="company_id"]').on('change', function() {
            table.ajax.reload();
        });

        $('#select-all-quotations').on('click', function() {
            $('.quotation-checkbox').prop('checked', this.checked);
            updateSelectedQuotationIds();
        });

        $(document).on('change', '.quotation-checkbox', function() {
            updateSelectedQuotationIds();
        });

        function updateSelectedQuotationIds() {
            let ids = [];
            $('.quotation-checkbox:checked').each(function() {
                ids.push($(this).val());
            });
            $('#quotation_ids').val(ids.join(','));
            
            // Auto populate other fields if one or more selected
            if (ids.length > 0) {
                fetchQuotationDetails(ids);
            }
        }

        function fetchQuotationDetails(ids) {
            $.ajax({
                url: "{{ backpack_url('client/po/get-quotation-details') }}",
                data: { ids: ids },
                success: function(data) {
                    // Populate fields
                    if (data.client_id) {
                        $('select[name="client_id"]').val(data.client_id).trigger('change');
                    }
                    if (data.job_name) {
                        $('textarea[name="job_name"]').val(data.job_name);
                    }
                    
                    // Sum values
                    if (data.job_value) {
                        setInputNumber('#job_value_masked', data.job_value);
                    }
                    if (data.rap_value) {
                        setInputNumber('#rap_value_masked', data.rap_value);
                    }
                    if (data.tax_ppn !== undefined) {
                        $('input[name="tax_ppn"]').val(data.tax_ppn);
                    }
                    
                    if (data.work_code) {
                        $('input[name="work_code"]').val(data.work_code);
                    }
                    if (data.start_date) {
                        $('input[name="start_date"]').val(data.start_date);
                    }
                    if (data.end_date) {
                        $('input[name="end_date"]').val(data.end_date);
                    }
                    if (data.reimburse_type) {
                        $('select[name="reimburse_type"]').val(data.reimburse_type).trigger('change');
                    }
                    if (data.category) {
                        $('select[name="category"]').val(data.category).trigger('change');
                    }
                    if (data.status) {
                        $('select[name="status"]').val(data.status).trigger('change');
                    }
                    
                    // Trigger formula calculation if logic_client_po exists
                    if (typeof SIAOPS !== 'undefined' && SIAOPS.getAttribute('logic_client_po')) {
                        SIAOPS.getAttribute('logic_client_po').logicFormula();
                    }
                }
            });
        }
        
        function setInputNumber(selector, value) {
            if ($(selector).length) {
                // Formatting for masked input if necessary
                $(selector).val(new Intl.NumberFormat('id-ID').format(value)).trigger('input');
            }
        }
    });
</script>
@endpush
