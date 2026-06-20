@if ($crud->hasAccess('create'))
    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#modalImportExcelTransactionHistory">
        <i class="la la-file-import"></i> <span>Import Excel</span>
    </button>
@endif

@push('after_scripts')
<div class="modal fade" id="modalImportExcelTransactionHistory" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="modalImportExcelTransactionHistoryLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalImportExcelTransactionHistoryLabel">Import Riwayat Transaksi dari Excel</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="formImportExcelTransactionHistory" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    @if (backpack_user()->hasRole('Super Admin'))
                        <div class="form-group mb-3">
                            <label class="form-label" for="import_company_id_transaction_history">Milik Perusahaan <span class="text-danger">*</span></label>
                            <select name="company_id" id="import_company_id_transaction_history" class="form-select" required>
                                <option value="">-- Pilih Perusahaan --</option>
                                @foreach (\App\Models\Company::all() as $company)
                                    <option value="{{ $company->id }}">{{ $company->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-group mb-3">
                        <label class="form-label" for="import_file_transaction_history">File Excel (.xlsx, .xls) <span class="text-danger">*</span></label>
                        <input type="file" name="file" id="import_file_transaction_history" class="form-control" accept=".xlsx, .xls" required />
                        <div class="form-text text-muted mt-2">
                            Kolom yang diharapkan dalam file Excel: <br/>
                            <code>transaction_id</code>, <code>device_id</code>, <code>msisdn</code>, <code>op_completion_time</code>, <code>oprations</code>, <code>devices_upload</code>, <code>device_prosses</code>, <code>device_update</code>, <code>last_update</code>, <code>status</code>.
                        </div>
                        <div class="mt-2">
                            <a href="{{ url($crud->route . '/download-template') }}" class="btn btn-outline-primary btn-sm">
                                <i class="la la-download"></i> Unduh Template Excel
                            </a>
                        </div>
                    </div>
                    
                    <div id="import-error-alert-transaction-history" class="alert alert-danger d-none"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" id="btn-submit-import-transaction-history" class="btn btn-primary">
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
        $('#formImportExcelTransactionHistory').off('submit').on('submit', function(e) {
            e.preventDefault();
            
            var form = $(this);
            var btn = $('#btn-submit-import-transaction-history');
            var spinner = btn.find('.spinner-border');
            var errorAlert = $('#import-error-alert-transaction-history');
            
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
                        var modalEl = document.getElementById('modalImportExcelTransactionHistory');
                        var modal = bootstrap.Modal.getInstance(modalEl);
                        modal.hide();
                        
                        // Clear file input
                        $('#import_file_transaction_history').val('');
                        if ($('#import_company_id_transaction_history').length) {
                            $('#import_company_id_transaction_history').val('');
                        }
                        
                        // Show success message
                        new Noty({
                            type: "success",
                            text: response.message || "Data berhasil diimport."
                        }).show();
                        
                        // Reload datatable
                        var datatableInstance = SIAOPS.getAttribute('crudTable-transaction_history');
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
