@push('after_scripts')
<script>
    $(function() {
        // State for selected IDs
        window.billingDeviceSelectedIds = [];

        function updateBulkDeleteUI() {
            var count = window.billingDeviceSelectedIds.length;
            $('#bulk-delete-device-count').text(count);

            if (count > 0) {
                $('#btn-bulk-delete-device').removeClass('d-none');
            } else {
                $('#btn-bulk-delete-device').addClass('d-none');
            }
        }

        function cleanFilterInput() {
            var $filterTh = $('#crudTable-billing_device thead tr.filters th').eq(1);
            if ($filterTh.length) {
                $filterTh.empty().append('<input type="hidden" />');
            }
        }

        // Header Checkbox (Select / Deselect all visible)
        $(document).on('change', '#bulk-select-all-device', function() {
            var isChecked = $(this).is(':checked');
            var $table = $('#crudTable-billing_device');

            $table.find('tbody .bulk-checkbox-device').each(function() {
                var id = parseInt($(this).data('id'));
                $(this).prop('checked', isChecked);

                if (isChecked) {
                    if (window.billingDeviceSelectedIds.indexOf(id) === -1) {
                        window.billingDeviceSelectedIds.push(id);
                    }
                } else {
                    var index = window.billingDeviceSelectedIds.indexOf(id);
                    if (index !== -1) {
                        window.billingDeviceSelectedIds.splice(index, 1);
                    }
                }
            });

            updateBulkDeleteUI();
        });

        // Row Checkbox click
        $(document).on('change', '#crudTable-billing_device tbody .bulk-checkbox-device', function(e) {
            e.stopPropagation();
            var id = parseInt($(this).data('id'));
            var isChecked = $(this).is(':checked');

            if (isChecked) {
                if (window.billingDeviceSelectedIds.indexOf(id) === -1) {
                    window.billingDeviceSelectedIds.push(id);
                }
            } else {
                var index = window.billingDeviceSelectedIds.indexOf(id);
                if (index !== -1) {
                    window.billingDeviceSelectedIds.splice(index, 1);
                }
            }

            // Sync header checkbox
            var totalRows = $('#crudTable-billing_device tbody .bulk-checkbox-device').length;
            var totalChecked = $('#crudTable-billing_device tbody .bulk-checkbox-device:checked').length;
            $('#bulk-select-all-device').prop('checked', totalRows > 0 && totalRows === totalChecked);

            updateBulkDeleteUI();
        });

        // Prevent row selection / bubble on checkbox wrapper
        $(document).on('click', '#crudTable-billing_device tbody .bulk-checkbox-device, #bulk-select-all-device', function(e) {
            e.stopPropagation();
        });

        // Datatable draw event
        $('#crudTable-billing_device').on('draw.dt', function() {
            cleanFilterInput();

            // Re-apply checked status
            $('#crudTable-billing_device tbody .bulk-checkbox-device').each(function() {
                var id = parseInt($(this).data('id'));
                if (window.billingDeviceSelectedIds.indexOf(id) !== -1) {
                    $(this).prop('checked', true);
                }
            });

            var totalRows = $('#crudTable-billing_device tbody .bulk-checkbox-device').length;
            var totalChecked = $('#crudTable-billing_device tbody .bulk-checkbox-device:checked').length;
            $('#bulk-select-all-device').prop('checked', totalRows > 0 && totalRows === totalChecked);

            updateBulkDeleteUI();
        });

        // Initial filter row cleanup
        setTimeout(cleanFilterInput, 300);
        setTimeout(cleanFilterInput, 800);

        // Bulk Delete Button Click
        $(document).on('click', '#btn-bulk-delete-device', function(e) {
            e.preventDefault();

            var count = window.billingDeviceSelectedIds.length;
            if (count === 0) {
                if (typeof swal === 'function') {
                    swal("{{ trans('backpack::crud.voucher.confirm.caution') ?? 'Peringatan' }}", "{{ trans('backpack::crud.bulk_no_entries_selected_message') ?? 'Pilih setidaknya satu data.' }}", "warning");
                } else {
                    alert("{{ trans('backpack::crud.bulk_no_entries_selected_message') ?? 'Pilih setidaknya satu data.' }}");
                }
                return;
            }

            var confirmTitle = "{{ trans('backpack::crud.delete') }} " + count + " item?";
            var confirmText = "{{ trans('backpack::crud.delete_confirm_plan') ?? 'Apakah Anda yakin ingin menghapus data yang dipilih?' }}";

            var executeDelete = function() {
                var $btn = $('#btn-bulk-delete-device');
                var originalHtml = $btn.html();
                $btn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Loading...');

                $.ajax({
                    url: "{{ backpack_url('billing/billing-device/bulk-delete') }}",
                    type: 'POST',
                    data: {
                        _token: "{{ csrf_token() }}",
                        entries: JSON.stringify(window.billingDeviceSelectedIds)
                    },
                    success: function(response) {
                        $btn.prop('disabled', false).html(originalHtml);

                        if (response.success || response.status) {
                            window.billingDeviceSelectedIds = [];
                            $('#bulk-select-all-device').prop('checked', false);
                            updateBulkDeleteUI();

                            var successMsg = response.message || "{{ trans('backpack::crud.delete_confirmation_message') ?? 'Data berhasil dihapus.' }}";

                            if (typeof Noty === 'function') {
                                new Noty({
                                    type: "success",
                                    text: successMsg
                                }).show();
                            } else if (typeof swal === 'function') {
                                swal("{{ trans('backpack::crud.delete_confirmation_title') ?? 'Berhasil' }}", successMsg, "success");
                            }

                            // Trigger datatable reload
                            if (typeof eventEmitter !== 'undefined') {
                                eventEmitter.emit("crudTable-billing_device_create_success", true);
                            }
                            if ($.fn.DataTable && $.fn.DataTable.isDataTable('#crudTable-billing_device')) {
                                $('#crudTable-billing_device').DataTable().ajax.reload(null, false);
                            }
                        } else {
                            var errMsg = response.error || "Gagal menghapus data.";
                            if (typeof swal === 'function') {
                                swal("{{ trans('backpack::crud.voucher.confirm.caution') ?? 'Peringatan' }}", errMsg, "error");
                            } else {
                                alert(errMsg);
                            }
                        }
                    },
                    error: function(xhr) {
                        $btn.prop('disabled', false).html(originalHtml);
                        var errMsg = (xhr.responseJSON && xhr.responseJSON.error) ? xhr.responseJSON.error : "Terjadi kesalahan saat menghapus data.";
                        if (typeof swal === 'function') {
                            swal("Error", errMsg, "error");
                        } else {
                            alert(errMsg);
                        }
                    }
                });
            };

            if (typeof swal === 'function') {
                swal({
                    title: confirmTitle,
                    text: confirmText,
                    icon: "warning",
                    buttons: {
                        cancel: "{{ trans('backpack::crud.cancel') }}",
                        confirm: {
                            text: "{{ trans('backpack::crud.delete') }}",
                            value: true,
                            className: "swal-button--danger btn-danger"
                        }
                    },
                    dangerMode: true,
                }).then(function(willDelete) {
                    if (willDelete) {
                        executeDelete();
                    }
                });
            } else {
                if (confirm(confirmTitle + "\n" + confirmText)) {
                    executeDelete();
                }
            }
        });
    });
</script>
@endpush

