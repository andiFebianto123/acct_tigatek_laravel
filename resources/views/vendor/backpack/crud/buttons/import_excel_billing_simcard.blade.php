@if ($crud->hasAccess('create'))
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalImportExcelSimcard">
        <i class="la la-file-import"></i> <span>Import Excel</span>
    </button>
@endif

@push('after_scripts')
<div class="modal fade" id="modalImportExcelSimcard" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalImportExcelSimcardLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalImportExcelSimcardLabel">Import Billing SIMCARD dari Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formImportExcelSimcard" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    @if (backpack_user()->hasRole('Super Admin'))
                        <div class="form-group mb-3">
                            <label class="form-label" for="import_company_id_simcard">Milik Perusahaan <span class="text-danger">*</span></label>
                            <select name="company_id" id="import_company_id_simcard" class="form-select" required>
                                <option value="">-- Pilih Perusahaan --</option>
                                @foreach (\App\Models\Company::all() as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-group mb-3">
                        <label class="form-label" for="import_file_simcard">File Excel (.xlsx, .xls) <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="import_file_simcard" class="form-control" accept=".xlsx, .xls" required />
                        <div class="form-text text-muted mt-2">
                            Kolom yang diharapkan dalam file Excel: <br/>
                            <code>product</code>, <code>device_name</code>, <code>technology</code>, <code>device_profile_id</code>, <code>iccid</code>, <code>msisdn</code>, <code>status</code>, <code>rate_plan</code>, <code>subscription_expiry_date</code>, <code>installation_date</code>, <code>expired_date</code>.
                        </div>
                    </div>
                    
                    <div id="import-error-alert-simcard" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btn-submit-import-simcard" class="btn btn-primary">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#formImportExcelSimcard').off('submit').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var btn = $('#btn-submit-import-simcard');
            var spinner = btn.find('.spinner-border');
            var errorAlert = $('#import-error-alert-simcard');
            
            // Disable button and show spinner
            btn.prop('disabled', true);
            spinner.removeClass('d-none');
            errorAlert.addClass('d-none').text('');
            
            var formData = new FormData(this);
            
            $.ajax({
                url: "{{ url($crud->route . '/import') }}",
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    btn.prop('disabled', false);
                    spinner.addClass('d-none');
                    
                    if (response.status) {
                        // Close modal
                        var modalEl = document.getElementById('modalImportExcelSimcard');
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        modal.hide();
                        
                        // Clear file input
                        $('#import_file_simcard').val('');
                        if ($('#import_company_id_simcard').length) {
                            $('#import_company_id_simcard').val('');
                        }
                        
                        // Show success message
                        new Noty({
                            type: "success",
                            text: response.message || "Data berhasil diimport."
                        }).show();
                        
                        // Reload datatable
                        var datatableInstance = SIAOPS.getAttribute('crudTable-billing_simcard');
                        if (datatableInstance && datatableInstance.table) {
                            datatableInstance.table.ajax.reload();
                        } else {
                            window.location.reload();
                        }
                    } else {
                        errorAlert.removeClass('d-none').text(response.error || "Terjadi kesalahan saat mengimpor.");
                    }
                },
                error: function(xhr) {
                    btn.prop('disabled', false);
                    spinner.addClass('d-none');
                    
                    var errorMsg = "Terjadi kesalahan saat mengimpor data.";
                    if (xhr.responseJSON && xhr.responseJSON.error) {
                        errorMsg = xhr.responseJSON.error;
                    } else if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    errorAlert.removeClass('d-none').text(errorMsg);
                }
            });
        });
    });
</script>
@endpush
