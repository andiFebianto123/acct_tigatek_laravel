<form method="post" id="form-edit" action="{{ $actionUrl }}">
    {!! csrf_field() !!}
    {!! method_field('PUT') !!}

    <input type="hidden" name="company_id" value="{{ $company_id }}">
    <input type="hidden" name="code_billing" value="{{ $code_billing }}">
    <input type="hidden" name="installation_date" value="{{ $installation_date }}">
    <input type="hidden" name="period" id="input_period" value="{{ $entry->period ?? '' }}">

    <div class="row g-3">
        {{-- Kode Billing --}}
        <div class="col-12">
            <label class="form-label fw-bold">{{ trans('backpack::crud.billing_reminder.column.code_billing') ?? 'Kode Billing' }}</label>
            <input type="text" 
                   class="form-control" 
                   value="{{ $code_billing }}" 
                   placeholder="Masukkan kode billing" 
                   readonly 
                   style="background-color: #f8f9fa;">
        </div>

        {{-- Start Date & End Date --}}
        <div class="col-md-6">
            <label class="form-label fw-bold">{{ trans('backpack::crud.billing_reminder.column.start_date') ?? 'Start Date' }}</label>
            <div class="input-group">
                <input type="date" 
                       name="start_date" 
                       id="reminder_start_date" 
                       class="form-control" 
                       value="{{ $start_date_val }}" 
                       placeholder="YYYY-MM-DD">
            </div>
        </div>

        <div class="col-md-6">
            <label class="form-label fw-bold">{{ trans('backpack::crud.billing_reminder.column.end_date') ?? 'End Date' }}</label>
            <div class="input-group">
                <input type="date" 
                       name="end_date" 
                       id="reminder_end_date" 
                       class="form-control" 
                       value="{{ $end_date_val }}" 
                       placeholder="YYYY-MM-DD">
            </div>
        </div>

        {{-- Pilihan Cepat Durasi --}}
        <div class="col-12">
            <div class="card border p-3" style="background: #ffffff; border-radius: 8px;">
                <label class="form-label fw-bold mb-2">Pilihan Cepat Durasi</label>
                <div class="d-flex flex-wrap gap-4 align-items-center">
                    <div class="form-check">
                        <input class="form-check-input period-checkbox" 
                               type="checkbox" 
                               id="period_1_month" 
                               value="1_month"
                               {{ ($entry->period ?? '') === '1_month' ? 'checked' : '' }}>
                        <label class="form-check-label" for="period_1_month">
                            {{ trans('backpack::crud.billing_reminder.period_options.1_month') ?? 'Sebulan' }}
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input period-checkbox" 
                               type="checkbox" 
                               id="period_3_months" 
                               value="3_months"
                               {{ ($entry->period ?? '') === '3_months' ? 'checked' : '' }}>
                        <label class="form-check-label" for="period_3_months">
                            {{ trans('backpack::crud.billing_reminder.period_options.3_months') ?? '3 Bulan' }}
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input period-checkbox" 
                               type="checkbox" 
                               id="period_6_months" 
                               value="6_months"
                               {{ ($entry->period ?? '') === '6_months' ? 'checked' : '' }}>
                        <label class="form-check-label" for="period_6_months">
                            {{ trans('backpack::crud.billing_reminder.period_options.6_months') ?? '6 Bulan' }}
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input period-checkbox" 
                               type="checkbox" 
                               id="period_1_year" 
                               value="1_year"
                               {{ ($entry->period ?? '') === '1_year' ? 'checked' : '' }}>
                        <label class="form-check-label" for="period_1_year">
                            {{ trans('backpack::crud.billing_reminder.period_options.1_year') ?? 'Setahun' }}
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<script>
    (function() {
        function addMonthsToDate(dateStr, months) {
            if (!dateStr) return '';
            var d = new Date(dateStr);
            if (isNaN(d.getTime())) return '';
            
            var originalDay = d.getDate();
            d.setMonth(d.getMonth() + months);
            if (d.getDate() !== originalDay) {
                d.setDate(0);
            }
            var year = d.getFullYear();
            var month = ('0' + (d.getMonth() + 1)).slice(-2);
            var day = ('0' + d.getDate()).slice(-2);
            return year + '-' + month + '-' + day;
        }

        function calculateEndDate() {
            var selectedPeriod = $('#input_period').val();
            var startDateVal = $('#reminder_start_date').val();
            if (!startDateVal || !selectedPeriod) return;

            var months = 0;
            if (selectedPeriod === '1_month') months = 1;
            else if (selectedPeriod === '3_months') months = 3;
            else if (selectedPeriod === '6_months') months = 6;
            else if (selectedPeriod === '1_year') months = 12;

            if (months > 0) {
                var calculated = addMonthsToDate(startDateVal, months);
                if (calculated) {
                    $('#reminder_end_date').val(calculated);
                }
            }
        }

        $('.period-checkbox').off('change').on('change', function() {
            if ($(this).is(':checked')) {
                $('.period-checkbox').not(this).prop('checked', false);
                $('#input_period').val($(this).val());
                calculateEndDate();
            } else {
                $('#input_period').val('');
            }
        });

        $('#reminder_start_date').off('change').on('change', function() {
            if ($('#input_period').val()) {
                calculateEndDate();
            }
        });

        // Wire AJAX submit to #btn-submit-edit
        $('#btn-submit-edit').off('click').on('click', function(e) {
            e.preventDefault();
            var form = $('#form-edit');
            var url = form.attr('action');
            var formData = new FormData(form[0]);

            btnLoader('btn-submit-edit', false);

            $.ajax({
                url: url,
                type: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(data) {
                    btnLoader('btn-submit-edit', true);
                    if (data.status || data.success) {
                        swal({
                            title: "Success",
                            text: data.message || "{!! trans('backpack::crud.update_success') !!}",
                            icon: "success",
                            timer: 3000,
                            buttons: false,
                        });

                        // Sembunyikan modal menggunakan helper dan bootstrap API
                        $('#modalEdit').modal('hide');
                        if (typeof hideModal === 'function') {
                            hideModal('modalEdit');
                        }
                        try {
                            var editModalEl = document.getElementById('modalEdit');
                            if (editModalEl && typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                                var modalInstance = bootstrap.Modal.getInstance(editModalEl);
                                if (modalInstance) {
                                    modalInstance.hide();
                                }
                            }
                        } catch (err) {
                            console.error('Error closing modal:', err);
                        }

                        // Emit event sesuai pola Backpack CRUD (PurchaseOrder & SIAOPS)
                        if (data.events) {
                            if (typeof forEachFlexible === 'function') {
                                forEachFlexible(data.events, function(eventname, payload) {
                                    if (typeof eventEmitter !== 'undefined') {
                                        eventEmitter.emit(eventname, payload);
                                    }
                                });
                            } else if (typeof eventEmitter !== 'undefined') {
                                Object.keys(data.events).forEach(function(eventname) {
                                    eventEmitter.emit(eventname, data.events[eventname]);
                                });
                            }
                        } else if (typeof eventEmitter !== 'undefined') {
                            eventEmitter.emit('crudTable-billing_reminder_updated_success', data.data);
                        }

                        // Reload datatable
                        if (window.crud && window.crud.table) {
                            window.crud.table.ajax.reload();
                        } else if (typeof SIAOPS !== 'undefined' && SIAOPS.getAttribute('crudTable-billing_reminder')) {
                            var tabInst = SIAOPS.getAttribute('crudTable-billing_reminder');
                            if (tabInst && tabInst.table) {
                                tabInst.table.ajax.reload();
                            }
                        } else if ($.fn.DataTable && $.fn.DataTable.isDataTable('.table')) {
                            $('.table').DataTable().ajax.reload();
                        }
                    } else {
                        swal({
                            title: "{!! trans('backpack::crud.ajax_error_title') !!}",
                            text: data.error || "An error occurred",
                            icon: "error",
                            timer: 4000,
                            buttons: false,
                        });
                    }
                },
                error: function(xhr) {
                    btnLoader('btn-submit-edit', true);
                    var msg = "An error occurred while saving the reminder.";
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        msg = xhr.responseJSON.message;
                    } else if (xhr.responseJSON && xhr.responseJSON.error) {
                        msg = xhr.responseJSON.error;
                    }
                    swal({
                        title: "Error",
                        text: msg,
                        icon: "error",
                        timer: 4000,
                        buttons: false,
                    });
                }
            });
        });
    })();
</script>
