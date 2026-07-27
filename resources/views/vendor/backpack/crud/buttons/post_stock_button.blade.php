@if ($entry->po_type === 'supplier')
    @if (!$entry->is_stock_posted)
        <a href="javascript:void(0)" 
           onclick="postPoStock('{{ $entry->id }}')" 
           class="btn btn-sm btn-outline-success me-1" 
           title="{{ trans('backpack::crud.po.button.post_stock_title') ?: 'Submit / Posting Stok ke Gudang & History FIFO' }}">
            <i class="la la-box"></i> {{ trans('backpack::crud.po.button.post_stock') ?: 'Post Stok' }}
        </a>
    @else
        <span class="badge bg-success text-white p-2" title="{{ trans('backpack::crud.po.badge.stock_posted_title') ?: 'Stok sudah diposting ke Master Device & History Layer' }}">
            <i class="la la-check-circle"></i> {{ trans('backpack::crud.po.badge.stock_posted') ?: 'Stok Terposting' }}
        </span>
    @endif
@endif

@push('after_scripts') @if (request()->ajax()) @endpush @endif
<script>
    if (typeof window.postPoStock === 'undefined') {
        window.postPoStock = function(id) {
            var title = '{{ trans("backpack::crud.po.swal.post_title") ?: "Posting Stok PO Supplier?" }}';
            var text = '{{ trans("backpack::crud.po.swal.post_text") ?: "Stok item pada PO ini akan dimasukkan ke Master Device Stock dan Layer History FIFO secara permanen." }}';
            var confirmBtn = '{{ trans("backpack::crud.po.swal.confirm_btn") ?: "Ya, Submit Stok!" }}';
            var cancelBtn = '{{ trans("backpack::crud.po.swal.cancel_btn") ?: "Batal" }}';

            var doConfirm = function(callback) {
                if (typeof Swal !== 'undefined' && typeof Swal.fire === 'function') {
                    Swal.fire({
                        title: title,
                        text: text,
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#28a745',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: confirmBtn,
                        cancelButtonText: cancelBtn
                    }).then((result) => callback(result.isConfirmed));
                } else if (typeof swal === 'function') {
                    swal({
                        title: title,
                        text: text,
                        icon: 'info',
                        buttons: {
                            cancel: {
                                text: cancelBtn,
                                value: false,
                                visible: true,
                                className: "btn btn-secondary",
                                closeModal: true,
                            },
                            confirm: {
                                text: confirmBtn,
                                value: true,
                                visible: true,
                                className: "btn btn-success",
                                closeModal: true,
                            }
                        }
                    }).then((willPost) => callback(willPost));
                } else {
                    if (confirm(text)) {
                        callback(true);
                    }
                }
            };

            doConfirm(function(isConfirmed) {
                if (isConfirmed) {
                    $.ajax({
                        url: '{{ backpack_url("vendor/purchase-order") }}/' + id + '/post-stock',
                        type: 'POST',
                        data: {
                            _token: '{{ csrf_token() }}'
                        },
                        success: function(response) {
                            if (response.success) {
                                new Noty({
                                    type: "success",
                                    text: response.message || '{{ trans("backpack::crud.po.noty.post_success") ?: "Stok PO berhasil diposting ke Master & History Layer FIFO!" }}'
                                }).show();

                                if (typeof crud !== 'undefined' && crud.table) {
                                    crud.table.ajax.reload(null, false);
                                } else {
                                    location.reload();
                                }
                            } else {
                                new Noty({
                                    type: "error",
                                    text: response.message || '{{ trans("backpack::crud.po.noty.post_failed") ?: "Gagal memposting stok." }}'
                                }).show();
                            }
                        },
                        error: function(xhr) {
                            var msg = (xhr.responseJSON && xhr.responseJSON.message) 
                                ? xhr.responseJSON.message 
                                : '{{ trans("backpack::crud.po.noty.post_error") ?: "Terjadi kesalahan saat memposting stok." }}';
                            new Noty({
                                type: "error",
                                text: msg
                            }).show();
                        }
                    });
                }
            });
        };
    }
</script>
@if (!request()->ajax()) @endpush @endif
