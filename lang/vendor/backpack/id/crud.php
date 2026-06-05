<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Backpack Crud Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used by the CRUD interface.
    | You are free to change them to anything
    | you want to customize your views to better match your application.
    */
    'payment' => [
        'title' => 'Pembayaran',
        'payment' => 'Bayar',
    ],
    'loading' => 'Memuat...',
    'please_wait' => 'Mohon tunggu...',

    // Forms
    'save_action_save_and_new' => 'Simpan dan buat baru',
    'save_action_save_and_edit' => 'Simpan dan lanjutkan perubahan',
    'save_action_save_and_back' => 'Simpan dan kembali',
    'save_action_save_and_preview' => 'Simpan dan lihat',
    'save_action_changed_notification' => 'Perilaku default setelah penyimpanan diubah.',

    // Create form
    'add' => 'Tambah',
    'back_to_all' => 'Kembali ke semua ',
    'cancel' => 'Batal',
    'add_a_new' => 'Tambahkan yang baru ',

    // Edit form
    'edit' => 'Edit',
    'save' => 'Simpan',

    // Translatable models
    'edit_translations' => 'Terjemahan',
    'language' => 'Bahasa',

    // CRUD table view
    'all' => 'Semua ',
    'in_the_database' => 'di database',
    'list' => 'Daftar',
    'reset' => 'Set ulang',
    'actions' => 'Aksi',
    'preview' => 'Detail',
    'delete' => 'Hapus',
    'admin' => 'Admin',
    'details_row' => 'Ini adalah baris rincian. Ubah sesuka Anda.',
    'details_row_loading_error' => 'Terjadi kesalahan saat memuat detail. Silakan coba lagi.',
    'clone' => 'Duplikat',
    'clone_success' => '<strong>Masukan telah diduplikat</strong><br>Masukan baru telah ditambahkan, dengan informasi yang sama.',
    'clone_failure' => '<strong>Proses duplikat gagal</strong><br>Masukan baru tidak dapat dibuat. Silakan coba lagi.',

    // Confirmation messages and bubbles
    'delete_confirm' => 'Anda yakin ingin menghapus item ini?',
    'delete_confirm_plan' => 'Apakah anda ingin menghapus rencana bayar ini?, setelah dihapus anda juga bisa menambahkannya kembali',
    'delete_confirm_2' => 'Apakah anda yakin ingin menghapus data',
    'delete_confirmation_title' => 'Item Dihapus',
    'delete_confirmation_message' => 'Item telah berhasil dihapus.',
    'delete_confirmation_not_title' => 'TIDAK dihapus',
    'delete_confirmation_not_message' => 'Terjadi kesalahan. Item Anda mungkin belum dihapus.',
    'delete_confirmation_not_deleted_title' => 'Tidak dihapus',
    'delete_confirmation_not_deleted_message' => 'Tidak ada yang terjadi. Item Anda aman.',

    // Bulk actions
    'bulk_no_entries_selected_title' => 'Tidak ada masukan yang dipilih',
    'bulk_no_entries_selected_message' => 'Silakan pilih satu atau lebih untuk melakukan tindakan massal pada mereka.',

    // Bulk confirmation
    'bulk_delete_are_you_sure' => 'Anda yakin ingin menghapus :number item ini?',
    'bulk_delete_sucess_title' => 'Item dihapus',
    'bulk_delete_sucess_message' => ' item telah dihapus',
    'bulk_delete_error_title' => 'Penghapusan gagal',
    'bulk_delete_error_message' => 'Satu atau lebih item tidak dapat dihapus',

    // Ajax errors
    'ajax_error_title' => 'Terjadi kesalahan',
    'ajax_error_text' => 'Terjadi kesalahan saat memuat halaman. Harap segarkan halaman.',

    // DataTables translation
    'emptyTable' => 'Tak ada data yang tersedia pada tabel ini',
    'info' => 'Menampilkan _START_ hingga _END_ dari _TOTAL_ masukan',
    'infoEmpty' => 'Tidak ada masukan',
    'infoFiltered' => '(difilter dari _MAX_ jumlah masukan)',
    'infoPostFix' => '.',
    'thousands' => ',',
    'lengthMenu' => '_MENU_ masukan per halaman',
    'loadingRecords' => 'Memuat...',
    'processing' => 'Memproses...',
    'search' => 'Cari',
    'zeroRecords' => 'Tidak ada data yang cocok ditemukan',
    'paginate' => [
        'first' => 'Pertama',
        'last' => 'Terakhir',
        'next' => 'Selanjutnya',
        'previous' => 'Sebelumnya',
    ],
    'aria' => [
        'sortAscending' => ': aktifkan untuk mengurutkan kolom naik',
        'sortDescending' => ': aktifkan untuk mengurutkan kolom turun',
    ],
    'export' => [
        'export' => 'Ekspor',
        'copy' => 'Salin',
        'excel' => 'Excel',
        'csv' => 'CSV',
        'pdf' => 'PDF',
        'print' => 'Cetak',
        'column_visibility' => 'Visibilitas kolom',
    ],

    // global crud - errors
    'unauthorized_access' => 'Akses tidak sah - Anda tidak memiliki izin yang diperlukan untuk melihat halaman ini.',
    'please_fix' => 'Harap perbaiki yang berikut ini:',

    // global crud - success / error notification bubbles
    'insert_success' => 'Item berhasil ditambahkan.',
    'update_success' => 'Item berhasil diubah.',
    'insert_error' => 'Terjadi kesalahan saat menambahkan item.',
    'approved_success' => 'Item berhasil disetujui.',

    // CRUD reorder view
    'reorder' => 'Susun ulang',
    'reorder_text' => 'Gunakan seret & lepas untuk menyusun ulang.',
    'reorder_success_title' => 'Selesai',
    'reorder_success_message' => 'Susunan Anda telah disimpan.',
    'reorder_error_title' => 'Terjadi kesalahan',
    'reorder_error_message' => 'Susunan Anda belum tersimpan',

    // CRUD yes/no
    'yes' => 'Ya',
    'no' => 'Tidak',

    // CRUD filters navbar view
    'filters' => 'Filter',
    'toggle_filters' => 'Alihkan filter',
    'remove_filters' => 'Hapus filter',

    // Fields
    'browse_uploads' => 'Jelajahi unggahan',
    'select_all' => 'Pilih Semua',
    'select_files' => 'Pilih file',
    'select_file' => 'Pilih file',
    'clear' => 'Bersihkan',
    'page_link' => 'Tautan halaman',
    'page_link_placeholder' => 'http://contoh.com/halaman-yang-anda-inginkan',
    'internal_link' => 'Tautan internal',
    'internal_link_placeholder' => 'Slug internal. Cth: \'admin/page\' (tanpa tanda kutip) untuk \':url\'',
    'external_link' => 'Tautan eksternal',
    'choose_file' => 'Pilih File',
    'new_item' => 'Item baru',
    'select_entry' => 'Pilih masukan',
    'select_entries' => 'Pilih masukan',

    //Table field
    'table_cant_add' => 'Tidak dapat menambahkan :entity yang baru',
    'table_max_reached' => 'Jumlah maksimum :max telah tercapai',

    // File manager
    'file_manager' => 'Manajer File',

    // InlineCreateOperation
    'related_entry_created_success' => 'Masukan terkait telah dibuat dan dipilih.',
    'related_entry_created_error' => 'Tidak dapat membuat masukan terkait.',

    // custom
    'void_payment' => [
        'void' => 'Void',
        'title' => 'Void Pembayaran Voucher',
        'confirm_title' => 'Konfirmasi Void Pembayaran',
        'confirm_text' => 'Apakah Anda yakin ingin membatalkan (Void) pembayaran voucher ini? Jurnal dan histori transaksi terkait akan dihapus.',
        'button_confirm' => 'Ya, Void!',
        'success_title' => 'Berhasil!',
        'success_text' => 'Pembayaran voucher telah berhasil di-Void.',
        'error_title' => 'Gagal!',
        'error_text' => 'Terjadi kesalahan saat melakukan Void.',
        'error_request' => 'Tidak dapat memproses permintaan Void.',
    ],

    'filter' => [
        'all_year' => 'Semua Tahun',
        'all_paid' => 'Semua',
        'change_order' => 'Ubah Urutan',
        'all_category' => 'Semua Kategori',
        'all_client' => 'Semua Client',
        'all_status_project' => 'Semua Status Proyek',
        'all_company' => 'All (Semua Perusahaan)',
    ],

    'card' => [
        'blank_cast_account' => 'Belum ada akun rekening',
        'cast_account_card' => [
            'name_bank' => 'Nama Bank',
            'name_rekening' => 'Nama Rekening',
            'no_rekening' => 'Nomor Rekening',
            'balance' => 'Saldo',
            'title_add_transaction' => "Tambah Data Transaksi",
            'loaded_count' => 'Menampilkan',
            'total_count' => 'dari',
            'transaction' => 'Transaksi',
            'btn_load_more' => 'Muat Lebih Banyak',
        ],
    ],

    'modal' => [
        'close' => 'Tutup',
        'bank_name' => 'Nama Bank',
        'no_account' => 'Nomor Rekening',
        'transfer_balance' => 'Pembayaran tagihan pinjaman',
        'title_transfer_balance' => 'Transfer Saldo',
        'move' => 'Pindah',
        'cancel' => 'Batalkan',
        'choose_account' => '-PILIH REKENING/ACCOUNT-',
    ],

    'dashboard' => [
        'title' => 'Dashboard',
        'no_data' => 'Belum ada data',
    ],

    'menu' => [
        'dashboard' => 'Dashboard',
        'vendor_subkon' => 'Vendor(Subkon)',
        'list_subkon' => 'Daftar Subkon',
        'po' => 'PO',
        'spk' => 'SPK',
        'client' => 'Client',
        'list_client' => 'Daftar Client',
        'client_po' => 'PO',
        'invoice_client' => 'Invoice',
        'proforma_invoice' => 'Invoice Proforma',
        'cash_flow' => 'Arus Rekening',
        'cash_flow_cash' => 'Rekening Kas',
        'cash_flow_loan' => 'Rekening Pinjaman',
        'finance_report' => 'Laporan Keuangan',
        'expense_account' => 'Chart Of Account',
        'profit_lost' => 'Laba Rugi',
        'balance_sheet' => 'Neraca',
        'asset' => 'Daftar Aset',
        'fa' => 'FA',
        'voucher' => 'Voucher',
        'voucher_payment' => 'Pembayaran',
        'monitoring_project' => 'Monitoring Proyek',
        'project_system_setup' => 'Project System Setup',
        'project_list' => 'Daftar Proyek',
        'project_report' => 'Project Report',
        'list_quotation' => 'Daftar Penawaran',
        'quotation_status' => 'Status Penawaran',
        'account_setting' => 'Pengaturan Akun',
        'setting' => 'Pengaturan',
        'setting_system' => 'Pengaturan Sistem',
        'voucher_payment_plan' => 'Rencana Pembayaran',
        'tracker' => 'Monitoring Tracker',
        'delivery_note' => 'Surat Jalan',
        'bast' => 'BAST',
    ],
    'monitoring_tracker' => [
        'tab' => [
            'tracker' => 'Monitoring Tracker',
            'tracker_edit' => 'Riwayat Edit',
        ],
        'column' => [
            'no' => 'No',
            'no_po_spk' => 'No. PO/SPK',
            'name' => 'Nama Proyek',
            'client_id' => 'Nama Perusahaan',
            'actual_end_date' => 'Actual End Date',
            'total_time' => 'Total Time',
            'progress' => 'Progress (%)',
            'status' => 'Status',
            'pic' => 'PIC',
            'user' => 'User',
            'information' => 'Keterangan',
            'information_tracker' => 'Keterangan Tracker',
            'duration' => 'Durasi',
            'search_client' => 'Cari nama perusahaan...',
        ],
        'breadcrumb' => [
            'monitoring' => 'Monitoring',
            'tracker' => 'Monitoring Tracker',
        ],
        'title' => [
            'monitoring_tracker' => 'Monitoring Tracker',
        ],
        'history_update_text' => 'Mengedit data monitoring tracker',
    ],
    'user' => [
        'field' => [
            'no_order' => [
                'label' => 'Nomor Order',
                'placeholder' => 'Masukan Nomor Order',
            ]
        ]
    ],
    'subkon' => [
        'title_header' => 'Daftar Subkon',
        'title_modal_create' => 'Data Vendor (Subkon)',
        'title_modal_edit' => 'Data Vendor (Subkon)',
        'column' => [
            'company' => 'Milik Perusahaan',
            'name' => 'Nama Badan Usaha',
            'address' => 'Alamat',
            'npwp' => 'NPWP',
            'phone' => 'Telepon',
            'bank_name' => 'Nama Bank',
            'bank_account' => 'No. Rekening',
            'list_po' => 'List PO',
            'count_po' => 'Jumlah PO',
            'list_spk' => 'List SPK',
            'count_spk' => 'Jumlah SPK',
            'account_holder_name' => 'A/N Rekening',
        ]
    ],
    'po' => [
        'title_header' => 'PO',
        'title_modal_create' => 'Data PO Vendor (Subkon)',
        'title_modal_edit' => 'Data PO Vendor (Subkon)',
        'tab' => [
            'input_per_page' => 'masukan per halaman',
            'title_all_po' => 'Daftar PO',
            'open' => 'Open',
            'close' => 'Close',
            'title_total_incl_ppn' => 'Total Incl PPn',
        ],
        'column' => [
            'subkon_id ' => 'Nama Perusahaan',
            'po_number' => 'No. PO',
            'date_po' => 'Tanggal PO',
            'job_name' => 'Nama Pekerjaan',
            'job_description' => 'Deskripsi/Detail',
            'job_value' => 'Nilai Pekerjaan',
            'tax_ppn' => 'PPN',
            'total_value_with_tax' => 'Nilai Pekerjaan Include PPn',
            'due_date' => 'Jatuh Tempo',
            'status' => 'Status PO',
            'document_path' => 'Dokumen PO',
            'additional_info' => 'Keterangan',
        ],
        'field' => [
            'date_po' => [
                'placeholder' => 'Pilih Tanggal',
            ],
            'job_description' => [
                'placeholder' => 'Masukan nama pekerjaan',
                'label' => 'Deskripsi Pekerjaan'
            ],
            'due_date' => [
                'label' => 'Jatuh Tempo',
                'placeholder' => 'Pilih Tanggal'
            ],
            'status' => [
                'label' => 'Status PO',
                'placeholder' => '-STATUS',
                'open' => 'OPEN',
                'close' => 'CLOSE',
            ],
            'additional_info' => [
                'label' => 'Keterangan',
                'placeholder' => 'Tulis Keterangan',
            ]
        ],
        'export' => [
            'pdf' => [
                'title_header' => 'Daftar Purchase Order (Subkon)',
            ]
        ]
    ],
    'spk' => [
        'title_header' => 'SPK',
        'title_modal_create' => 'Data SPK Vendor (Subkon)',
        'title_modal_edit' => 'Data SPK Vendor (Subkon)',
        'column' => [
            'subkon_id ' => 'Nama Perusahaan',
            'no_spk' => 'No. SPK',
            'date_spk' => 'Tanggal SPK',
            'job_name' => 'Nama Pekerjaan',
            'job_description' => 'Deskripsi/Detail',
            'job_value' => 'Nilai Pekerjaan',
            'tax_ppn' => 'PPN',
            'total_value_with_tax' => 'Nilai Pekerjaan Include PPn',
            'document_path' => 'Dokumen SPK',
            'status' => 'Status SPK',
        ],
        'field' => [
            'subkon_id' => [
                'placeholder' => 'NAMA PERUSAHAAN',
            ],
            'no_spk' => [
                'placeholder' => 'Masukkan nomor SPK perusahaan',
            ],
            'date_spk' => [
                'placeholder' => 'Pilih Tanggal',
            ],
            'job_name' => [
                'placeholder' => 'Masukkan nama pekerjaan',
            ],
            'job_description' => [
                'label' => 'Deskripsi Pekerjaan',
                'placeholder' => 'Tulis deskripsi pekerjaan',
            ],
            'job_value' => [
                'placeholder' => '000.000',
            ],
            'tax_ppn' => [
                'placeholder' => '0',
            ],
            'total_value_with_tax' => [
                'placeholder' => '000.000',
            ],
            'document_path' => [
                'label' => 'Unggah Dokumen SPK',
                'placeholder' => '000.000',
            ],
            'status' => [
                'label' => 'Status SPK',
            ]
        ]
    ],
    'client' => [
        'title_header' => 'Daftar Client',
        'column' => [
            'name' => 'Nama CLient',
            'address' => 'Alamat',
            'npwp' => 'No. NPWP',
            'phone' => 'No. Telepon',
            'list_po_count' => 'Jumlah PO',
        ],
    ],
    'client_po' => [
        'title_header' => 'PO',
        'column' => [
            'client_id' => 'Nama Perusahaan',
            'work_code' => 'Kode Pekerjaan',
            'po_number' => 'No. PO',
            'job_name' => 'Nama Pekerjaan',
            'job_value' => 'Nilai Pekerjaan',
            'tax_ppn' => 'PPn',
            'total_value_with_tax' => 'Nilai Pekerjaan Include PPn',
            'startdate_and_enddate' => "Start Date - End Date",
            'reimburse_type' => 'Reimburse/NonReimburse',
            'price_total' => 'Total Biaya',
            'profit_and_loss' => "Laba/Rugi PO",
            'document_path' => 'Dokumen PO',
            'date_invoice' => 'Tanggal Invoice',
            'rap_value' => 'Nilai RAP',
            'job_value_exclude_ppn' => 'Nilai Pekerjaan Excl PPn',
            'job_value_include_ppn' => 'Nilai Pekerjaan Incld PPn',
            'job_value_include_ppn_2' => 'Nilai Pekerjaan Include PPn',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'start_date_end_date' => 'Start Date - End Date',
            'price_after_year' => 'Biaya Lewat Tahun',
            'load_general_value' => 'Beban Umum',
            'profit_and_lost_final' => 'Laba/Rugi Akhir',
            'category' => 'Kategori',
            'list_invoice' => 'Status Invoice',
            'date_po' => 'Tanggal PO',
        ],
        'field' => [
            'client_id' => [
                'label' => 'Nama Perusahaan',
                'placeholder' => "- NAMA PERUSAHAAN",
            ],
            'work_code' => [
                'label' => 'Kode Kerja',
                'placeholder' => 'Masukan kode kerja perusahaan',
            ],
            'status' => [
                'label' => 'Status PO',
                'placeholder' => '-STATUS PO'
            ],
            'po_number' => [
                'label' => 'No. PO',
                'placeholder' => 'Masukan nomor PO Perusahaan',
            ],
            'job_name' => [
                'label' => 'Nama Pekerjaan',
                'placeholder' => 'Masukan nama pekerjaan',
            ],
            'job_value' => [
                'label' => 'Nilai Pekerjaan',
                'placeholder' => '000.000',
            ],
            'tax_ppn' => [
                'label' => 'PPn',
                'placeholder' => '0',
            ],
            'startdate_and_enddate' => [
                'label' => 'Start Date - End Date',
                'placeholder' => 'Start Date - End Date',
            ],
            'reimburse_type' => [
                'label' => 'Reimburse/NonReimburse',
                'placeholder' => '-PILIH',
            ],
            'price_total' => [
                'label' => 'Total Biaya'
            ],
            'profit_and_loss' => [
                'label' => 'Laba/Rugi PO',
            ],
            'document_path' => [
                'label' => 'Upload Dokumen PO',
                'hint' => 'Maksimal 35 MB (PDF)',
            ],
            'date_invoice' => [
                'label' => 'Tanggal Invoice',
                'placeholder' => 'Pilih Tanggal',
            ],
            'date_po' => [
                'label' => 'Tanggal PO',
                'placeholder' => 'Pilih Tanggal PO',
            ],
            'rap_value' => 'Nilai RAP',
            'job_value_exclude_ppn' => 'Nilai Pekerjaan Excl PPn',
            'job_value_include_ppn' => 'Nilai Pekerjaan Incld PPn',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'start_date_end_date' => 'Start Date - End Date',
            'price_after_year' => 'Biaya Lewat Tahun',
            'load_general_value' => 'Beban Umum',
            'profit_and_lost_final' => 'Laba/Rugi Akhir',
            'category' => 'Kategori',
            'is_from_quotation' => [
                'label' => 'Pilih dari Penawaran (Client Quotation)',
            ],
            'quotation_selection' => [
                'label' => 'Pilih dari Penawaran (Client Quotation)',
            ],
            'quotation_selection_info' => 'Pilih satu atau beberapa penawaran di bawah untuk mengisi data PO secara otomatis.',
        ],
        'count_include_ppn' => 'Jml nilai pekerjaan incld PPn',
        'count_exclude_ppn' => 'Jml nilai pekerjaan excld PPn',
    ],
    'delivery_note' => [
        'title_header' => 'Surat Jalan',
        'column' => [
            'number' => 'Nomer',
            'date' => 'Tanggal',
            'client_id' => 'Nama Client',
            'description' => 'Description',
            'qty' => 'Qty',
            'information' => 'Keterangan',
        ],
        'field' => [
            'client_po_id' => [
                'label' => 'No. PO',
                'placeholder' => '- PILIH NO. PO',
            ],
            'client_id' => [
                'label' => 'Kirim Ke',
                'placeholder' => '- PILIH CLIENT',
            ],
            'address' => [
                'label' => 'Alamat',
                'placeholder' => 'Masukkan alamat pengiriman',
            ],
            'date' => [
                'label' => 'Tanggal',
            ],
            'number' => [
                'label' => 'No. Surat Jalan',
                'placeholder' => 'Masukkan nomor surat jalan',
            ],
            'description' => [
                'label' => 'Item',
                'placeholder' => 'Masukkan item barang',
            ],
            'qty' => [
                'label' => 'Qty',
            ],
            'information' => [
                'label' => 'Keterangan',
                'placeholder' => 'Keterangan tambahan',
            ],
        ],
    ],
    'bast' => [
        'title_header' => 'BAST',
        'column' => [
            'number' => 'Nomer',
            'date' => 'Tanggal',
            'client_id' => 'Nama Client',
            'first_party' => 'Penyerah',
            'description' => 'Description',
            'qty' => 'Qty',
            'information' => 'Keterangan',
        ],
        'field' => [
            'client_po_id' => [
                'label' => 'No. PO',
                'placeholder' => '- PILIH NO. PO',
            ],
            'first_party' => [
                'label' => 'Pihak Pertama',
                'placeholder' => 'Masukkan nama pihak pertama',
            ],
            'first_party_address' => [
                'label' => 'Alamat Pihak Pertama',
                'placeholder' => 'Masukkan alamat pihak pertama',
            ],
            'client_id' => [
                'label' => 'Pihak Ke 2',
                'placeholder' => '- PILIH CLIENT',
            ],
            'address' => [
                'label' => 'Alamat Pihak Ke 2',
                'placeholder' => 'Masukkan alamat pihak ke 2',
            ],
            'date' => [
                'label' => 'Tanggal',
            ],
            'number' => [
                'label' => 'No. BAST',
                'placeholder' => 'Masukkan nomor BAST',
            ],
            'description' => [
                'label' => 'Item',
                'placeholder' => 'Masukkan item barang',
            ],
            'qty' => [
                'label' => 'Qty',
            ],
            'information' => [
                'label' => 'Keterangan',
                'placeholder' => 'Keterangan tambahan',
            ],
        ],
    ],
    'client_quotation' => [
        'title_header' => 'Penawaran',
        'column' => [
            'client_id' => 'Nama Perusahaan',
            'work_code' => 'Kode Pekerjaan',
            'po_number' => 'No. Penawaran',
            'job_name' => 'Nama Pekerjaan',
            'job_value' => 'Nilai Pekerjaan',
            'tax_ppn' => 'PPn',
            'total_value_with_tax' => 'Nilai Pekerjaan Include PPn',
            'startdate_and_enddate' => "Start Date - End Date",
            'reimburse_type' => 'Reimburse/NonReimburse',
            'price_total' => 'Total Biaya',
            'profit_and_loss' => "Laba/Rugi Penawaran",
            'document_path' => 'Dokumen Penawaran',
            'date_invoice' => 'Tanggal Invoice',
            'rap_value' => 'Nilai RAP',
            'job_value_exclude_ppn' => 'Nilai Pekerjaan Excl PPn',
            'job_value_include_ppn' => 'Nilai Pekerjaan Incld PPn',
            'job_value_include_ppn_2' => 'Nilai Pekerjaan Include PPn',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'start_date_end_date' => 'Start Date - End Date',
            'price_after_year' => 'Biaya Lewat Tahun',
            'load_general_value' => 'Beban Umum',
            'profit_and_lost_final' => 'Laba/Rugi Akhir',
            'category' => 'Kategori',
            'list_invoice' => 'Status Invoice',
            'date_po' => 'Tanggal Penawaran',
        ],
        'field' => [
            'client_id' => [
                'label' => 'Nama Perusahaan',
                'placeholder' => "- NAMA PERUSAHAAN",
            ],
            'work_code' => [
                'label' => 'Kode Kerja',
                'placeholder' => 'Masukan kode kerja perusahaan',
            ],
            'status' => [
                'label' => 'Status Penawaran',
                'placeholder' => '-STATUS PENAWARAN'
            ],
            'po_number' => [
                'label' => 'No. Penawaran',
                'placeholder' => 'Masukan nomor Penawaran Perusahaan',
            ],
            'job_name' => [
                'label' => 'Nama Pekerjaan',
                'placeholder' => 'Masukan nama pekerjaan',
            ],
            'job_value' => [
                'label' => 'Nilai Pekerjaan',
                'placeholder' => '000.000',
            ],
            'tax_ppn' => [
                'label' => 'PPn',
                'placeholder' => '0',
            ],
            'startdate_and_enddate' => [
                'label' => 'Start Date - End Date',
                'placeholder' => 'Start Date - End Date',
            ],
            'reimburse_type' => [
                'label' => 'Reimburse/NonReimburse',
                'placeholder' => '-PILIH',
            ],
            'price_total' => [
                'label' => 'Total Biaya'
            ],
            'profit_and_loss' => [
                'label' => 'Laba/Rugi Penawaran',
            ],
            'document_path' => [
                'label' => 'Upload Dokumen Penawaran',
                'hint' => 'Maksimal 35 MB (PDF)',
            ],
            'date_invoice' => [
                'label' => 'Tanggal Invoice',
                'placeholder' => 'Pilih Tanggal',
            ],
            'date_po' => [
                'label' => 'Tanggal Penawaran',
                'placeholder' => 'Pilih Tanggal Penawaran',
            ],
            'rap_value' => 'Nilai RAP',
            'job_value_exclude_ppn' => 'Nilai Pekerjaan Excl PPn',
            'job_value_include_ppn' => 'Nilai Pekerjaan Incld PPn',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'start_date_end_date' => 'Start Date - End Date',
            'price_after_year' => 'Biaya Lewat Tahun',
            'load_general_value' => 'Beban Umum',
            'profit_and_lost_final' => 'Laba/Rugi Akhir',
            'category' => 'Kategori',
        ],
        'count_include_ppn' => 'Jml nilai pekerjaan incld PPn',
        'count_exclude_ppn' => 'Jml nilai pekerjaan excld PPn',
    ],
    'invoice_client' => [
        'title_header' => 'Invoice (Client)',
        'title_modal_create' => 'Data Invoice (Client)',
        'title_modal_edit' => 'Data Invoice (Client)',
        'title_modal_delete' => 'Invoice (Client)',
        'column' => [
            'invoice_number' => 'No. Invoice',
            'name' => 'Nama Pekerjaan',
            'invoice_date' => 'Tanggal Invoice',
            'client_po_id' => 'No. PO',
            'po_date' => 'Tanggal PO',
            'client_id' => 'Client',
            'price_total_exclude_ppn' => 'Nilai Exclude PPn',
            'price_total_include_ppn' => 'Nilai Include PPn',
            'status' => 'Status',
            'send_invoice_normal' => "Tgl Pengiriman Invoice - Normal",
            'send_invoice_revision' => "Tgl Pengiriman Invoice - Revisi",
            'document_invoice' => 'Dokumen Invoice',
            'description' => 'Deskripsi',
            'pph' => 'PPh',
            'discount_pph' => 'Nominal PPh',
            'withholding_agent' => 'Wajib Pungut',
        ],
        'field' => [
            'invoice_number' => [
                'label' => 'No. Invoice',
                'placeholder' => 'Masukan nomor invoice',
            ],
            'name' => [
                'label' => 'Nama Invoice',
                'placeholder' => 'Masukan nama invoice',
            ],
            'invoice_date' => [
                'label' => 'Tanggal Invoice',
                'placeholder' => 'Pilih Tanggal',
            ],
            'client_po_id' => [
                'placeholder' => 'Masukan nomor PO Client',
                'label' => 'No. PO',
            ],
            'address' => [
                'placeholder' => 'Masukan alamat PO',
                'label' => 'Alamat',
            ],
            'description' => [
                'placeholder' => 'Tulis deskripsi invoice',
                'label' => 'Deskripsi'
            ],
            'nominal_exclude_ppn' => [
                'placeholder' => '000.000',
                'label' => 'Nominal Exclude PPn',
            ],
            'dpp_other' => [
                'placeholder' => '000.000',
                'label' => 'DPP Nilai Lainnya',
            ],
            'tax_ppn' => [
                'placeholder' => '',
                'label' => 'PPn',
            ],
            'nominal_include_ppn' => [
                'placeholder' => '000.000',
                'label' => 'Nominal Include PPn',
            ],
            'kdp' => [
                'placeholder' => 'Masukan KDP',
                'label' => 'KDP',
            ],
            'send_invoice_normal' => [
                'label' => 'Pengiriman Invoice - Normal',
                'placeholder' => '',
            ],
            'send_invoice_revision' => [
                'label' => 'Pengiriman Invoice - Revisi',
                'placeholder' => '',
            ],
            'po_date' => [
                'label' => 'Tanggal PO',
                'placeholder' => 'Pilih tanggal',
            ],
            'client_id' => [
                'label' => 'Client',
                'placeholder' => 'Nama Client',
            ],
            'price_total_exclude_ppn' => [
                'label' => 'Total Biaya Exclude PPn',
                'placeholder' => '000.000',
            ],
            'price_total_include_ppn' => [
                'label' => 'Total Biaya Include PPn',
                'placeholder' => '000.000',
            ],
            'status' => [
                'label' => 'Status',
                'placeholder' => '-STATUS',
            ],
            'nominal_information' => [
                'label' => 'Total Sisa Pembayaran',
            ],
            'nominal_information_show' => [
                'label' => 'Estimasi Nilai Transfer',
            ],
            'item' => [
                'label' => 'Invoice Item',
                'new_item_label' => 'Tambah Item',
                'errors' => [
                    'total_price' => 'Total harga pada item anda tidak sama dengan total harga PO',
                ],
                'items' => [
                    'name' => [
                        'label' => 'Nama Item',
                        'placeholder' => 'Nama Item',
                    ],
                    'price' => [
                        'label' => 'Harga Item',
                        'placeholder' => '000.000',
                    ]
                ]
            ],
            'invoice_document' => [
                'label' => 'Dokumen Invoice (Opsional)',
                'hint' => 'Upload file invoice dalam format PDF (maksimal 30MB)',
            ],
            'pph' => [
                'label' => 'PPh',
                'placeholder' => 'Masukan PPh',
            ],
            'discount_pph' => [
                'label' => 'Nominal PPh',
                'placeholder' => '000.000',
            ],
            'withholding_agent' => [
                'label' => 'Wajib Pungut',
                'placeholder' => '- PILIH WAJIB PUNGUT -',
            ],
            'invoice_payment_success' => 'Pembayaran invoice berhasil disimpan.',
        ],
    ],
    'cash_account' => [
        'title_header' => 'Rekening Kas',
        'title_modal_create' => 'Akun Rekening Kas',
        'title_modal_edit' => 'Akun Rekening Kas',
        'title_modal_delete' => 'Akun Rekening Kas',
        'title_modal_create_transaction' => 'Data Transaksi',
        'title_modal_create_transaction_loan' => 'Data Transaksi Pinjaman',
        'field' => [
            'name' => [
                'label' => 'Nama Rekening',
                'placeholder' => 'Masukan nama rekening',
            ],
            'bank_name' => [
                'label' => 'Nama Bank',
                'placeholder' => '-List Bank',
                'add_new' => 'TAMBAH BANK BARU',
                'custom_placeholder' => 'Masukkan Nama Bank Baru',
                'save_button' => 'Simpan Bank',
                'save_hint' => 'Klik "Simpan Bank" untuk mendaftarkan ke database.',
                'save_confirm' => 'Apakah Anda yakin ingin menyimpan bank baru ini?',
                'error_empty' => 'Nama bank tidak boleh kosong!',
                'error_ajax' => 'Terjadi kesalahan saat menyimpan bank.',
            ],
            'no_account' => [
                'label' => 'No. Rekening',
                'placeholder' => 'Masukan nomor rekening',
            ],
            'total_saldo' => [
                'label' => 'Saldo Rekening',
                'placeholder' => '000.000'
            ],
            'additional_information' => [
                'label' => 'Keterangan Tambahan',
            ]
        ],
        'account_info' => [
            'title' => 'Informasi Rekening',
            'account_name' => 'Nama Rekening',
            'bank_name' => 'Bank',
            'account_number' => 'Nomor Rekening',
        ],
        'field_transaction' => [
            'kode' => [
                'label' => 'Code Pinjaman',
            ],
            'date' => [
                'label' => 'Tanggal',
            ],
            'loan' => [
                'label' => 'Pinjaman',
            ],
            'nominal' => [
                'label' => 'Nominal',
            ],
            'status_loan' => [
                'label' => 'Status',
            ],
            'date_transaction' => [
                'label' => 'Tanggal Transaksi',
                'placeholder' => 'Pilih Tanggal',
            ],
            'no_transaction' => [
                'label' => 'No. PO/SPK',
                'placeholder' => 'Masukan nomor PO/SPK transaksi'
            ],
            'description' => [
                'label' => 'Deskripsi Pembayaran',
                'placeholder' => 'Masukan deskripsi pembayaran'
            ],
            'kdp' => [
                'label' => 'KDP',
                'placeholder' => 'Masukan kode perusahaan',
            ],
            'job_name' => [
                'label' => 'Nama Pekerjaan',
                'placeholder' => 'Masukan nama pekerjaan'
            ],
            'account_id' => [
                'label' => 'Akun',
                'placeholder' => '-AKUN',
                'hint_invoice_payment' => 'saat pembayaran invoice akun tidak perlu di isi',
            ],
            'account' => [
                'label' => 'Akun',
            ],
            'no_invoice' => [
                'label' => 'No. Invoice',
                'placeholder' => 'Masukan nomor invoice transaksi'
            ],
            'nominal' => [
                'label' => 'Nominal',
            ],
            'nominal_transaction' => [
                'label' => 'Nominal Transaksi',
                'placeholder' => '000.000',
            ],
            'withholding_agent_status' => [
                'label' => 'Wajib Pungut',
            ],
            'tax_ppn_nominal' => [
                'label' => 'PPN',
            ],
            'pph_nominal' => [
                'label' => 'PPh',
            ],
            'total_nominal_transfer' => [
                'label' => 'Total Nilai Transfer',
            ],
            'status' => [
                'label' => 'Keluar/Masuk',
                'placeholder' => 'Pilih Status',
                'enter' => 'MASUK',
                'out' => 'KELUAR',
            ]
        ],
        'field_transfer' => [
            'balance' => [
                'label' => 'Saldo',
            ],
            'nominal_transfer' => [
                'label' => 'Nominal Yang Ingin Dipindahkan',

            ],
            'description' => [
                'label' => 'Deskripsi',
                'placeholder' => 'Masukan deskripsi'
            ],
            'to_account' => [
                'label' => 'Rekening Tujuan',
                'placeholder' => '-REKENING TUJUAN'
            ],
            'date' => [
                'label' => 'Tanggal',
            ],
            'errors' => [
                'nominal_transfer_to_more' => 'Nominal tidak boleh melebihi jumlah saldo.',
                'nominal_transfer_to_more_destination' => 'Nominal tidak boleh melebihi jumlah saldo rekening Kas.',
                'to_account_is_same' => 'Rekening tujuan harus berbeda dengan rekening yang dipindah',
                'account_id_exist' => 'Akun rekening harus berbeda dengan akun rekening lainnya',
                'nominal_payment_to_more' => 'Nominal tidak boleh melebihi jumlah sisa pembayaran.',
                'balance_not_enough' => 'Saldo anda untuk Rekening :castname tidak mencukupi.',
            ]
        ]
    ],
    'cash_account_loan' => [
        'title_header' => 'Rekening Pinjaman',
        'title_modal_create' => 'Akun Rekening Pinjaman',
        'title_modal_edit' => 'Akun Rekening Pinjaman',
        'title_modal_delete' => 'Akun Rekening Pinjaman',
        'field' => [
            'name' => [
                'label' => 'Nama Rekening',
                'placeholder' => "Masukan nama rekening",
            ],
            'bank_name' => [
                'label' => 'Nama Bank',
                'placeholder' => '-List Bank',
            ],
            'no_account' => [
                'label' => 'No. Rekening',
                'placeholder' => 'Masukan nomor rekening',
            ],
            'account' => [
                'label' => 'Akun',
                'placeholder' => '-AKUN'
            ],
            'total_saldo' => [
                'label' => 'Saldo Rekening',
                'placeholder' => '000.000'
            ],
            'cast_account_destination_id' => [
                'label' => 'Asal/Tujuan Rekening',
                'placeholder' => '-ASAL/TUJUAN REKENING',
                'bank_loan' => 'PINJAMAN BANK',
                'bank_loan_placeholder' => 'PINJAMAN BANK',
                'bank_loan_alert' => "Maaf pinjaman bank hanya untuk uang masuk bukan keluar",
            ],
            'balance_information' => [
                'label' => 'Saldo',
                'balance_information' => "Jumlah Sisa Tagihan Pembayaran",
                'loan_transaction_flag_id' => 'Kode Pinjaman',
                'placeholder' => 'Nominal Yang Ingin Dipindahkan',
                'date' => 'Tanggal penagihan',
                'payment_price' => 'Nominal Pembayaran',
            ],
        ]
    ],
    'expense_account' => [
        'title_header' => 'Chart Of Account',
        'title_modal_create' => 'Chart Of Account',
        'title_modal_edit' => 'Chart Of Account',
        'title_modal_delete' => 'Chart Of Account',
        'title_modal_ledger' => 'Lihat Buku Besar',
        'column' => [
            'code' => 'Kode Akun',
            'name' => 'Nama Akun',
            'balance' => 'Saldo',
            'balance_initial' => 'Jumlah Saldo Awal',
            'action' => 'Action',
        ],
        'field' => [
            'code' => [
                'placeholder' => 'Masukan nomor kode akun',
                'errors' => [
                    'depedency' => "Kode akun tidak dapat diubah karena digantungkan pada akun lainnya.",
                    'delete' => 'Maaf akun tidak dapat dihapus karena digantungkan pada akun lainnya.',
                    'not_change_balance' => "Tidak dapat mengubah saldo karena akun telah digunakan untuk transaksi data lainnya.",
                ]
            ],
            'name' => [
                'placeholder' => 'Masukan nama akun',
            ],
            'balance' => [
                'placeholder' => '000.000',
            ],
        ]
    ],
    'profit_lost' => [
        'title_header' => 'Laba Rugi',
        'title_modal_create_consolidation' => 'Akun Laba Rugi Konsolidasi',
        'title_modal_edit_consolidation' => 'Akun Laba Rugi Konsolidasi',
        'title_modal_delete_consolidation' => 'Akun Laba Rugi Konsolidasi',
        'title_modal_create_project' => '',
        "total_profit_lost" => "Jumlah Laba/Rugi Akhir",
        'title_modal_create_project' => 'Tambah Laporan Laba Rugi Proyek',
        'title_modal_edit_project' => 'Edit Laporan Laba Rugi Proyek',
        'title_modal_delete' => 'Akun Biaya',
        'empty_account' => 'Belum ada akun',
        'consolidation_income_statement' => 'Laporan Laba Rugi Konsolidasi',
        'project_income_statement' => 'Laporan Laba Rugi Proyek',
        'choose_create' => [
            'consolidation_account' => 'Akun Laba Rugi Konsolidasi',
            'project_account' => 'Laba Rugi Proyek Proyek',
        ],
        'show_detail' => 'LIHAT DETAIL',
        'fields' => [
            'no_po' => [
                'label' => 'No PO',
                'placeholder' => '-No. PO'
            ],
            'job_code' => [
                'label' => 'Kode Kerja',
                'placeholder' => 'Kode Kerja',
            ],
            'price_after_year' => [
                'label' => 'Biaya Lewat Tahun',
                'placeholder' => '000.000',
            ],
            'price_voucher' => [
                'label' => 'Biaya (Vch)',
            ],
            'price_small_cash' => [
                'label' => 'Biaya (Kas Kecil)',
            ],
            'price_total' => [
                'label' => 'Total Biaya',
            ],
            'price_profit_lost_po' => [
                'label' => 'Laba/Rugi PO',
            ],
            'price_general' => [
                'label' => 'Beban Umum',
            ],
            'price_prift_lost_final' => [
                'label' => 'Laba/Rugi Akhir',
            ],
            'category' => [
                'label' => 'Kategori',
            ],
            'contract_value' => [
                'label' => 'Nilai Kontrak',
                'placeholder' => '',
            ],
            'total_project' => [
                'label' => 'Total Biaya Proyek',
                'placeholder' => '000.000'
            ],
            'price_material' => [
                'label' => 'Biaya Material',
                'placeholder' => '000.000'
            ],
            'price_subkon' => [
                'label' => 'Biaya Subkon',
                'placeholder' => '000.000',
            ],
            'price_btkl' => [
                'label' => 'Upah Pekerja Langsung (BTKL)',
                'placeholder' => '',
            ],
            'price_transport_project' => [
                'label' => 'Biaya Transportasi Proyek',
                'placeholder' => '',
            ],
            'price_worker_consumption' => [
                'label' => 'Biaya Konsumsi Pekerja',
                'placeholder' => '',
            ],
            'price_project_equipment' => [
                'label' => 'Sewa Peralatan Proyek',
            ],
            'price_other' => [
                'label' => 'Biaya Lain - lain (jika ada)'
            ],
            'price_profit_lost_project' => [
                'label' => 'Nilai Laba Rugi Proyek',
            ],
            'header_id' => [
                'label' => 'Header Konsolidasi',
            ]
        ],
        'column' => [
            'client_po_id' => 'Nama Perusahaan',
            'job_code' => 'Kode Pekerjaan',
            'no_po' => 'No.PO',
            'contract_value' => 'Nilai Kontrak',
            'total_project' => 'Total Biaya Proyek',
            'price_profit_lost_project' => 'Laba/Rugi Proyek',
            'price_voucher' => 'Biaya (Vch)',
            'price_small_cash' => 'Biaya (Kas Kecil)',
            'price_total' => 'Total Biaya',
            'profit_lost_po' => 'Laba/Rugi PO',
            'load_general_value' => 'Beban Umum',
            'profit_lost_final' => 'Laba/Rugi Akhir',
            'category' => 'Kategori',
            'invoice_date' => 'Tgl Invoice',
        ],
        'detail' => [
            'project_profit_and_loss_report' => 'Laporan Laba Rugi Proyek',
            'contract_revenue' => 'Pendapatan Kontrak',
            'contract_value' => 'Nilai Kontrak',
            'project_related_costs' => 'Biaya-biaya Terkait Proyek',
            'fee_type' => 'Jenis Biaya',
            'balance' => 'Saldo',
            'material' => 'Material',
            'subcon' => 'Subkon',
            'direct_labor_wages' => 'Upah Pekerja Langsung (BTKL)',
            'project_transportation' => 'Transportasi Proyek',
            'worker_consumption' => 'Konsumsi Pekerja',
            'project_equipment_rental' => 'Sewa Peralatan Proyek',
            'other_costs' => 'Biaya Lain-lain (jika ada)',
            'total_project_cost' => 'Total Biaya Proyek',
            'project_profit_loss' => 'Laba (Rugi) Proyek',
            'project_profit_loss_value' => 'Nilai Laba (Rugi) Proyek',
        ],
    ],
    'balance_sheet' => [
        'title_header' => 'Neraca',
        'title_modal_create' => 'Akun Neraca',
        'title_modal_edit' => 'Ubah Akun Neraca',
        'title_modal_delete' => 'Akun Neraca',
        'card' => [
            'asset' => 'Aset',
            'liabilities' => 'Kewajiban',
            'equity' => 'Ekuitas',
        ],
        'filters' => [
            'year' => [
                'label' => 'Tahun',
                'placeholder' => '- PILIH TAHUN'
            ],
            'quarter' => [
                'label' => 'Kuartal',
                'placeholder' => '- PILIH KUARTAL',
            ]
        ],
        'fields' => [
            'account_type' => [
                'label' => 'Jenis Akun',
                'placeholder' => '-PILIH JENIS AKUN',
                'options' => [
                    'account_asset' => 'AKUN ASET',
                    'account_liabilities' => 'AKUN KEWAJIBAN',
                    'account_equity' => 'AKUN EKUITAS',
                ]
            ],
            'code' => [
                'label' => 'Kode Akun',
                'placeholder' => 'Masukan nomor kode akun',
            ],
            'name' => [
                'label' => 'Nama Akun',
                'placeholder' => 'Masukan nama akun',
            ],
            'balance' => [
                'label' => 'Saldo',
                'placeholder' => '000.000',
            ],
            'date' => [
                'label' => 'Tanggal Akun',
                'placeholder' => 'Pilih Tanggal',
            ]
        ]

    ],
    'asset' => [
        'title_header' => 'Daftar Aset',
        'title_modal_create' => 'Daftar Aset',
        'title_modal_edit' => 'Ubah Daftar Aset',
        'title_modal_delete' => 'Daftar Aset',
        'column' => [
            'account_id' => 'Akun',
            'depreciation_account_id' => 'Akun Penyusutan',
            'expense_account_id' => 'Akun Beban',
            'description' => 'Keterangan',
            'year_acquisition' => 'Tahun Perolehan',
            'price_acquisition' => 'Harga Perolehan',
            'economic_age' => 'U.E',
            'tarif' => 'Tarif (%)',
            'price_rate_per_year' => 'Tarif Penyusutan Per Tahun',
            'price_rate_year_ago' => 'Tarif Penyusutan Tahun Lalu',
            'accumulated_until_december_last_year' => 'Akumulasi Penyusutan s.d. Desember Tahun Lalu',
            'book_value_last_december' => 'Nilai Buku Desember Tahun Lalu',
            'this_year_depreciation_rate' => 'Tarif Penysutan Tahun Ini',
            'accumulated_until_december_this_year' => 'Akumulasi Penyusutan s.d. Desember Tahun Ini',
            'book_value_this_december' => 'Nilai Buku Desember Tahun Ini',
        ],
        'field' => [
            'account_id' => [
                'label' => 'Akun Asset',
                'placeholder' => ''
            ],
            'account_depreciation' => [
                'label' => 'Akun Penyusutan',
                'placeholder' => ''
            ],
            'expense_account_id' => [
                'label' => 'Akun Beban',
            ],
            'description' => [
                'label' => 'Keterangan',
                'placeholder' => 'Masukan keterangan Asset',
            ],
            'year_acquisition' => [
                'label' => 'Tahun Perolehan',
                'placeholder' => 'Masukan Tahun Perolehan',
            ],
            'price_acquisition' => [
                'label' => 'Harga Perolehan',
                'placeholder' => '000.000',
            ],
            'economic_age' => [
                'label' => 'U.E. (Tahun)',
                'placeholder' => 'Masukan U.E.'
            ],
            'tarif' => [
                'label' => 'Tarif (%)',
                'placeholder' => '',
            ],
            'price_rate_per_year' => [
                'label' => 'Tarif Penyusutan Per Tahun',
            ],
            'price_rate_year_ago' => [
                'label' => 'Penyusutan Tahun Lalu',
                'placeholder' => '',
            ],
            'accumulated_until_december_last_year' => [
                'label' => 'Akumulasi Penyusutan s.d. Desember Tahun Lalu',
                'placeholder' => '',
            ],
            'book_value_last_december' => [
                'label' => 'Nilai Buku Desember Tahun Lalu',
                'placeholder' => '',
            ],
            'this_year_depreciation_rate' => [
                'label' => 'Tarif Penysutan Tahun Ini',
                'placeholder' => ''
            ],
            'accumulated_until_december_this_year' => [
                'label' => 'Akumulasi Penyusutan s.d. Desember Tahun Ini',
                'placeholder' => '',
            ],
            'book_value_this_december' => [
                'label' => 'Nilai Buku Desember Tahun Ini',
            ]
        ],
    ],
    'voucher' => [
        'title_header' => 'Voucher',
        'title_modal_create' => 'Voucher',
        'title_modal_edit' => 'Ubah Data Voucher',
        'title_modal_delete' => 'Voucher',
        'field' => [
            'no_payment' => [
                'label' => 'No Pengajuan Pembayaran',
                'placeholder' => 'Masukan nomor pengajuan pembayaran',
            ],
            'account_id' => [
                'label' => 'Akun',
                'placeholder' => '-AKUN BIAYA'
            ],
            'no_voucher' => [
                'label' => 'Nomor Voucher',
                'placeholder' => 'Masukan nomor voucher',
            ],
            'work_code' => [
                'label' => 'Kode Pekerjaan',
                'placeholder' => 'Masukan kode kerja voucher',
            ],
            'account_source_id' => [
                'label' => 'Sumber Rekening',
                'placeholder' => '-PILIH SUMBER REKENING'
            ],
            'job_name' => [
                'label' => 'Nama Pekerjaan',
                'placeholder' => 'Nama pekerjaan',
            ],
            'for_voucher' => [
                'label' => 'For Voucher',
                'placeholder' => '-FOR VOUCHER',
            ],
            'date_voucher' => [
                'label' => 'Tanggal Voucher',
            ],
            'bussines_entity_code' => [
                'label' => 'Kode Badan Usaha',
                'placeholder' => 'Masukan kode badan usaha',
            ],
            'bussines_entity_type' => [
                'label' => 'Bentuk Badan Usaha',
                'placeholder' => '-BENTUK BADAN USAHA',
            ],
            'bussines_entity_name' => [
                'label' => 'Nama Badan Usaha',
                'placeholder' => 'Nama badan usaha',
            ],
            'bill_number' => [
                'label' => 'No. Tagihan',
                'placeholder' => 'Masukan nomor tagihan',
            ],
            'bill_date' => [
                'label' => 'Tanggal Tagihan',
            ],
            'date_receipt_bill' => [
                'label' => 'Tanggal Terima Tagihan',
            ],
            'payment_description' => [
                'label' => 'Deskripsi Pembayaran',
                'placeholder' => 'Masukan deskripsi pembayaran',
            ],
            'no_po_spk' => [
                'label' => 'No. PO/SPK',
                'placeholder' => 'Masukan nomor PO/SPK',
            ],
            'date_po_spk' => [
                'label' => 'Tanggal PO/SPK',
            ],
            'bill_value' => [
                'label' => 'Nilai Tagihan',
            ],
            'tax_ppn' => [
                'label' => 'PPn',
            ],
            'total_price_ppn' => [
                'label' => 'Harga PPn',
            ],
            'total' => [
                'label' => 'Total Nilai',
            ],
            'pph_23' => [
                'label' => 'PPh 23',
            ],
            'discount_pph_23' => [
                'label' => 'Potongan PPh 23',
            ],
            'pph_4' => [
                'label' => 'PPh 4',
            ],
            'discount_pph_4' => [
                'label' => 'Potongan PPh 4',
            ],
            'pph_21' => [
                'label' => 'PPh 21',
            ],
            'discount_pph_21' => [
                'label' => 'Potongan PPh 21'
            ],
            'payment_transfer' => [
                'label' => 'Pembayaran (Nilai Transfer)',
            ],
            'due_date' => [
                'label' => 'Jatuh Tempo',
            ],
            'factur_status' => [
                'label' => 'Status Faktur',
                'placeholder' => '- STATUS FAKTUR',
            ],
            'no_factur' => [
                'label' => 'No. Faktur',
                'placeholder' => 'Masukan no faktur',
            ],
            'date_factur' => [
                'label' => 'Tanggal Faktur',
            ],
            'account_holder_name' => [
                'label' => 'A/N rekening',
                'placeholder' => 'Masukan A/N rekening',
            ],
            'bank_name' => [
                'label' => 'Nama Bank',
                'placeholder' => 'Masukan nama bank',
            ],
            'no_account' => [
                'label' => 'No. Rekening',
                'placeholder' => 'Masukan no rekening',
            ],
            'payment_type' => [
                'label' => 'Jenis Pembayaran',
                'placeholder' => '- JENIS PEKERJAAN',
            ],
            'payment_status' => [
                'label' => 'Status Bayar',
                'placeholder' => '- STATUS BAYAR',
            ],
            'payment_date' => [
                'label' => 'Tanggal Bayar',
            ],
            'priority' => [
                'label' => 'Prioritas',
                'placeholder' => '- PRIORITAS',
            ],
            'information' => [
                'label' => 'Keterangan',
                'placeholder' => 'Masukan keterangan',
            ]
        ],
        'tab' => [
            'title_voucher' => 'Data',
            'title_voucher_edit' => 'Riwayat Edit',
        ],
        'validation' => [
            'subkon_id_company_mismatch' => 'Nama Badan Usaha tidak sesuai dengan perusahaan yang dipilih.',
            'client_po_id_company_mismatch' => 'Kode Pekerjaan / No. PO/SPK tidak sesuai dengan perusahaan yang dipilih.',
        ],
        'column' => [
            'voucher' => [
                'no_voucher' => [
                    'label' => 'No. Voucher',
                ],
                'date_voucher' => [
                    'label' => 'Tanggal Voucher',
                ],
                'bussines_entity_name' => [
                    'label' => 'Nama Badan Usaha',
                ],
                'bank' => [
                    'label' => 'Bank',
                ],
                'number_account' => [
                    'label' => 'No. Rekening',
                ],
                'bill_number' => [
                    'label' => 'No. Tagihan',
                ],
                'no_po_spk' => [
                    'label' => 'No. PO/SPK',
                ],
                'bill_date' => [
                    'label' => 'Tanggal Tagihan',
                ],
                'payment_description' => [
                    'label' => 'Deskripsi Pembayaran',
                ],
                'bill_value' => [
                    'label' => 'Nilai (Exclude PPn)',
                ],
                'total' => [
                    'label' => 'Nilai (Include PPn)',
                ],
                'payment_transfer' => [
                    'label' => 'Nilai Transfer',
                    'label_2' => 'Pembayaran (Nilai Transfer)'
                ],
                'factur_status' => [
                    'label' => 'Status Faktur',
                ],
                'bussines_entity_code' => [
                    'label' => 'KDP',
                ],
                'job_name' => [
                    'label' => 'Nama Pekerjaan',
                ],
                'account_id' => [
                    'label' => 'Akun Biaya',
                ],
                'no_account' => [
                    'label' => 'Sumber Rekening',
                ],
                'payment_type' => [
                    'label' => 'Jenis Pembayaran',
                ],
                'payment_status' => [
                    'label' => 'Status Bayar',
                ],
                'due_date' => [
                    'label' => 'Tanggal Bayar',
                    'label_2' => 'Jatuh Tempo',
                ],
                'approved_at' => [
                    'label' => 'Tanggal Approve'
                ],
                'status' => [
                    'label' => 'Status Approve',
                ],
                'user_approval' => [
                    'label' => 'User Approval',
                ]
            ],
            'voucher_edit' => [
                'user_id' => [
                    'label' => 'Diedit Oleh',
                ],
                'date_update' => [
                    'label' => 'Waktu Perubahan',
                ],
                'history_update' => [
                    'label' => 'Riwayat Perubahan',
                ],
                'no_apprv' => [
                    'label' => 'Tahap Approval',
                ],
                'status' => [
                    'label' => 'Status Approval',
                ]
            ]

        ],
        'confirm' => [
            'title' => 'Menyetujui Voucher',
            'caution' => 'Perhatian !',
            'confirm_approved_statement' => 'Setujui untuk voucher ini?',
            'confirm_approved_statement_bulk' => 'Setujui untuk rencana bayar voucher ini?',
            'yes_approved' => 'Ya, Setujui',
            'cancel' => 'Batal',
            'confirm_after_success' => 'Voucher Berhasil Disetujui',
            'alert_success' => '',
            'update_failed_status' => 'Anda tidak bisa melakukan edit voucher karena status voucher saat ini sudah Approve',
            'update_failed_plan_exists' => 'Anda tidak bisa melakukan edit voucher karena voucher sudah masuk rencana bayar',
            'account_loan' => 'Akun tidak boleh pinjaman',
        ],
        'total_exclude_ppn' => 'Jumlah Exlude PPn',
        'total_include_ppn' => 'Jumlah Include PPn',
        'total_transfer_value' => 'Jumlah Nilai Transfer',
        'waiting_approval' => 'Menunggu Approval',
        'approved_approval' => 'Sudah Approval',
        'reject_approval' => 'Ditolak',
    ],
    'voucher_payment' => [
        'title_header' => 'Pembayaran',
        'title_modal_create_payment' => 'Pembayaran',
        'title_modal_create' => 'Rencana Bayar',
        'title_modal_edit' => 'Ubah Rencana Bayar',
        'title_modal_delete' => 'Rencana Bayar',
        'voucher_payment_exists' => "Data voucher sudah ada di pembayaran",
        'tab' => [
            'title_voucher_payment' => 'Data',
            'title_voucher_payment_plan' => 'Rencana Bayar',
        ],
        'field' => [
            'payment_date' => [
                'label' => 'Tanggal Bayar',
                'placeholder' => 'Input Tanggal Bayar',
            ]
        ],
        'confirm' => [
            'title' =>  'Menyetujui Pembayaran',
            'confirm_approved_statement' => 'Setujui untuk pembayaran ini?',
            'yes_approved' => 'Ya, Setujui',
            'cancel' => 'Batal',
            'alert_success' => '',
            'confirm_after_success' => 'Pembayaran Berhasil Disetujui',
        ],
        'total_payment_value' => 'Jumlah Nilai Transfer',
        'total_payment_approve_value' => 'Total Nilai Transfer Approved',
        'total_payment_plan_subkon' => 'UNPAID INCL PPN',
    ],
    'project_system_setup' => [
        'title_header' => 'Project System Setup',
        'card' => [
            'setup_category_project' => "Setup Ketegori Proyek",
            'setup_category_project_title_create' => 'Tambah Data Item Setup',
            'setup_category_project_title_edit' => 'Edit Data Item Setup',
            'setup_status_project' => "Setup Status Proyek",
            'setup_status_project_title_create' => 'Tambah Data Item Setup Status Proyek',
            'setup_status_project_title_edit' => 'Edit Data Item Setup Status Proyek',
            'setup_status_offering' => "Setup Status Penawaran",
            'setup_status_offering_title_create' => 'Tambah Data Item Setup Status Penawaran',
            'setup_status_offering_title_edit' => 'Edit Data Item Setup Status Penawaran',
            'setup_client' => "Setup Data Client",
            'setup_client_title_create' => 'Tambah Data Item Setup Data Client',
            'setup_client_title_edit' => 'Edit Data Item Setup Data Client',
            'setup_ppn' => 'Setup Tarif PPn',
            'setup_ppn_title_create' => 'Tambah Data Item Setup Tarif PPn',
            'setup_ppn_title_edit' => 'Edit Data Item Setup Tarif PPn',
            'setup_pph' => 'Setup Tarif PPh',
            'setup_pph_title_create' => 'Tambah Data Item Setup Tarif PPh',
            'setup_pph_title_edit' => 'Edit Data Item Setup Tarif PPh',
            'setup_company_classification' => 'Setup Klasifikasi Perusahaan',
            'setup_company_classification_title_create' => 'Tambah Data Item Setup Klasifikasi Perusahaan',
            'setup_company_classification_title_edit' => 'Edit Data Item Setup Klasifikasi Perusahaan',
        ],
        'field' => [
            'setup_category_project' => [
                'title' => [
                    'label' => 'Setup Kategori Proyek',
                ],
                'name' => [
                    'label' => 'Nama Item',
                    'placeholder' => 'Masukan nama item',
                    'errors' => [
                        'empty' => 'Anda menginput item kosong',
                        'unique' => 'Anda menginput item yang sudah ada',
                    ]
                ],
                'comma' => [
                    'label' => 'Pisahkan dengan koma untuk input multi data',
                ]
            ],
            'setup_status_project' => [
                'title' => [
                    'label' => 'Setup Status Proyek',
                ],
                'name' => [
                    'label' => 'Nama Item',
                    'placeholder' => 'Masukan nama item',
                    'errors' => [
                        'empty' => 'Anda menginput item kosong',
                        'unique' => 'Anda menginput item yang sudah ada',
                    ]
                ],
                'comma' => [
                    'label' => 'Pisahkan dengan koma untuk input multi data',
                ]
            ],
            'setup_status_offering' => [
                'title' => [
                    'label' => 'Setup Status Penawaran',
                ],
                'name' => [
                    'label' => 'Nama Item',
                    'placeholder' => 'Masukan nama item',
                    'errors' => [
                        'empty' => 'Anda menginput item kosong',
                        'unique' => 'Anda menginput item yang sudah ada',
                    ]
                ],
                'comma' => [
                    'label' => 'Pisahkan dengan koma untuk input multi data',
                ]
            ],
            'setup_client' => [
                'title' => [
                    'label' => 'Setup Client',
                ],
                'name' => [
                    'label' => 'Nama Item',
                    'placeholder' => 'Masukan nama item',
                    'errors' => [
                        'empty' => 'Anda menginput item kosong',
                        'unique' => 'Anda menginput item yang sudah ada',
                    ]
                ],
                'comma' => [
                    'label' => 'Pisahkan dengan koma untuk input multi data',
                ]
            ],
            'setup_ppn' => [
                'title' => [
                    'label' => 'Setup Tarif PPn',
                ],
                'name' => [
                    'label' => 'Nilai Item',
                    'placeholder' => '000.000',
                    'errors' => [
                        'empty' => 'Anda menginput item kosong',
                        'unique' => 'Anda menginput item yang sudah ada',
                    ]
                ],
                'comma' => [
                    'label' => 'Pisahkan dengan koma untuk input multi data',
                ]
            ],
            'setup_pph' => [
                'title' => [
                    'label' => 'Setup Tarif PPh',
                ],
                'name' => [
                    'label' => 'Nilai Item',
                    'placeholder' => '000.000',
                    'errors' => [
                        'empty' => 'Anda menginput item kosong',
                        'unique' => 'Anda menginput item yang sudah ada',
                    ]
                ],
                'comma' => [
                    'label' => 'Pisahkan dengan koma untuk input multi data',
                ]
            ],
            'setup_company_classification' => [
                'title' => [
                    'label' => 'Setup Klasifikasi Perusahaan',
                ],
                'name' => [
                    'label' => 'Nama Item',
                    'placeholder' => 'Masukan nama item (misal: WAPU)',
                    'errors' => [
                        'empty' => 'Anda menginput item kosong',
                        'unique' => 'Anda menginput item yang sudah ada',
                    ]
                ],
                'comma' => [
                    'label' => 'Pisahkan dengan koma untuk input multi data',
                ]
            ],
        ],
        'delete' => [
            'setup_category_project' => [
                'title' => 'Hapus Item Setup Sistem Proyek',
                'body' => 'Apakah anda yakin ingin menghapus data item setup sistem proyek ?',
            ],
            'setup_status_project' => [
                'title' => 'Hapus Item Setup Status Proyek',
                'body' => 'Apakah anda yakin ingin menghapus data item setup status proyek ?',
            ],
            'setup_status_offering' => [
                'title' => 'Hapus Item Setup Status Penawaran',
                'body' => 'Apakah anda yakin ingin menghapus data item setup status penawaran ?',
            ],
            'setup_client' => [
                'title' => 'Hapus Item Setup Client',
                'body' => 'Apakah anda yakin ingin menghapus data item setup client ?',
            ],
            'setup_ppn' => [
                'title' => 'Hapus Item Setup Tarif PPn',
                'body' => 'Apakah anda yakin ingin menghapus data item setup tarif ppn ?',
            ],
            'setup_pph' => [
                'title' => 'Hapus Item Setup Tarif PPh',
                'body' => 'Apakah anda yakin ingin menghapus data item setup tarif pph ?',
            ],
            'setup_company_classification' => [
                'title' => 'Hapus Item Setup Klasifikasi Perusahaan',
                'body' => 'Apakah anda yakin ingin menghapus data item setup klasifikasi perusahaan ?',
            ]
        ]
    ],
    'project' => [
        'title_header' => 'Daftar Proyek',
        'title_modal_create' => 'Data Daftar Proyek',
        'title_modal_edit' => 'Data Daftar Proyek',
        'title_modal_delete' => 'Data Daftar Proyek',
        'tab' => [
            'title_project' => 'Data',
            'title_project_edit' => 'Riwayat Edit',
        ],
        'column' => [
            'project' => [
                'no_po_spk' => [
                    'label' => 'No. PO/SPK',
                ],
                'name' => [
                    'label' => 'Nama Proyek',
                ],
                'po_date' => [
                    'label' => 'Tanggal PO',
                ],
                'price_total_exclude_ppn' => [
                    'label' => 'Nilai Exclude PPn'
                ],
                'price_ppn' => [
                    'label' => 'Tarif PPn'
                ],
                'tax_ppn' => [
                    'label' => 'PPn',
                ],
                'price_total_include_ppn' => [
                    'label' => 'Nilai Include PPn',
                ],
                'transfer_value' => [
                    'label' => 'Nilai Transfer',
                ],
                'tax_pph' => [
                    'label' => 'PPh',
                ],
                'price_pph' => [
                    'label' => 'Tarif PPh',
                ],
                'fine_price' => [
                    'label' => 'Denda / Lainnya',
                ],
                'company_classification' => [
                    'label' => 'Klasifikasi Perusahaan',
                ],
                'client_id' => [
                    'label' => 'Nama Perusahaan',
                ],
                'received_po_date' => [
                    'label' => 'Tanggal Terima PO',
                ],
                'startdate_and_enddate' => [
                    'label' => 'Start Date - End Date',
                ],
                'start_date' => [
                    'label' => ''
                ],
                'end_date' => [
                    'label' => 'End Date',
                ],
                'duration' => [
                    'label' => 'Delivery Time',
                ],
                'actual_start_date' => [
                    'label' => 'Actual Start Date',
                ],
                'actual_end_date' => [
                    'label' => 'Actual End Date',
                ],
                'status_po' => [
                    'label' => 'Status Proyek',
                ],
                'progress' => [
                    'label' => 'Progress (%)',
                ],
                'pic' => [
                    'label' => 'PIC',
                ],
                'user' => [
                    'label' => 'User',
                ],
                'category' => [
                    'label' => 'Kategori',
                ],
                'information' => [
                    'label' => 'Keterangan',
                ],
                'invoice_date' => [
                    'label' => 'Tanggal Kirim Invoice'
                ],
                'payment_date' => [
                    'label' => 'Tanggal Terbayar',
                ],
                'total_progress_day' => [
                    'label' => 'Total Proses (Hari)'
                ]
            ],
            'project_edit' => [
                'name' => [
                    'label' => 'Name Proyek',
                ],
                'user_id' => [
                    'label' => 'Diedit Oleh',
                ],
                'date_update' => [
                    'label' => 'Waktu Perubahan',
                ],
                'history_update' => [
                    'label' => 'Riwayat Perubahan',
                ]
            ]
        ],
        'field' => [
            'name' => [
                'label' => 'Nama Proyek',
                'placeholder' => 'Masukan nama proyek',
            ],
            'po_status' => [
                'label' => 'Belum ada PO'
            ],
            'no_po_spk' => [
                'label' => 'No. PO/SPK',
                'placeholder' => 'Masukan nomor PO/SPK',
            ],
            'po_date' => [
                'label' => 'Tanggal PO',
                'placeholder' => 'Pilih Tanggal',
            ],
            'received_po_date' => [
                'label' => 'Tanggal Terima PO',
                'placeholder' => 'Pilih Tanggal',
            ],
            'price_total_exclude_ppn' => [
                'label' => 'Nilai Exclude PPn',
            ],
            'tax_ppn' => [
                'label' => 'Tarif PPn',
                'placeholder' => '-PILIH TARIF PPN'
            ],
            'price_ppn' => [
                'label' => 'PPn',
            ],
            'price_total_include_ppn' => [
                'label' => 'Nilai Include PPn',
            ],
            'tax_pph' => [
                'label' => 'PPh',
                'placeholder' => '-PILIH PERSENTASE PPH'
            ],
            'price_pph' => [
                'label' => 'Tarif PPh',
            ],
            'fine_price' => [
                'label' => 'Denda / Lainnya',
            ],
            'start_date' => [
                'label' => 'Start Date - End Date',
            ],
            'duration' => [
                'label' => 'Delivery Time',
            ],
            'actual_start_date' => [
                'label' => 'Actual Start Date',
            ],
            'actual_end_date' => [
                'label' => 'Actual End Date',
            ],
            'status_po' => [
                'label' => 'Status Proyek',
                'placeholder' => '-PILIH STATUS',
            ],
            'client_id' => [
                'label' => 'Client',
                'placeholder' => '-PILIH CLIENT',
            ],
            'category' => [
                'label' => 'Kategori',
                'placeholder' => '-PILIH KATEGORI',
            ],
            'company_classification' => [
                'label' => 'Klasifikasi Perusahaan',
                'placeholder' => '-PILIH KLASIFIKASI',
            ],
            'progress' => [
                'label' => 'Progress'
            ],
            'pic' => [
                'label' => 'PIC',
                'placeholder' => 'Masukan nama pic'
            ],
            'user' => [
                'label' => 'User',
                'placeholder' => 'Masukan nama user'
            ],
            'information' => [
                'label' => 'Keterangan',
                'placeholder' => 'Masukan Keterangan Proyek'
            ]
        ]
    ],
    'project_report' => [
        'title_header' => 'Project Report',
        'title_modal_create' => 'Data Project Report',
        'title_modal_edit' => 'Data Project Report',
        'title_modal_delete' => 'Data Project Report',
        'column' => [
            'no_po_spk' => [
                'label' => 'No. PO/SPK',
            ],
            'name' => [
                'label' => 'Nama Proyek',
            ],
            'price_total_include_ppn' => [
                'label' => 'Nilai Include PPn',
            ],
            'client_id' => [
                'label' => 'Client',
            ],
            'startdate_and_enddate' => [
                'label' => 'Start Date - End Date',
            ],
            'duration' => [
                'label' => 'Durasi Hari',
            ],
            'actual_start_date' => [
                'label' => 'Actual Start Date',
            ],
            'actual_end_date' => [
                'label' => 'Actual End Date',
            ],
            'status_po' => [
                'label' => 'Status Proyek',
            ],
            'progress' => [
                'label' => 'Progress (%)'
            ]
        ],
    ],
    'quotation' => [
        'title_header' => 'Daftar Penawaran',
        'title_modal_create' => 'Data Daftar Penawaran',
        'title_modal_edit' => 'Data Daftar Penawaran',
        'title_modal_delete' => 'Penawaran',
        'tab' => [
            'quotation' => 'Data',
            'quotation_history' => 'Riwayat Edit',
        ],
        'field' => [
            'no_rfq' => [
                'label' => 'No. RFQ',
                'placeholder' => 'Masukan nomor RFQ',
            ],
            'rfq_date' => [
                'label' => 'Tanggal RFQ',
            ],
            'name_project' => [
                'label' => 'Proyek',
                'placeholder' => 'Masukan nama penawaran proyek'
            ],
            'rab' => [
                'label' => 'RAB',
            ],
            'rap' => [
                'label' => 'RAP',
            ],
            'client_id ' => [
                'label' => 'Client',
                'placeholder' => '-PILIH CLIENT',
            ],
            'pic' => [
                'label' => 'PIC',
                'placeholder' => 'Masukan nama PIC'
            ],
            'user' => [
                'label' => 'User',
                'placeholder' => 'Masukan nama user'
            ],
            'closing_date' => [
                'label' => 'Closing Date',
                'placeholder' => 'Pilih Tanggal',
            ],
            'status' => [
                'label' => 'Status',
                'placeholder' => '-PILIH STATUS'
            ],
            'information' => [
                'label' => 'Keterangan',
                'placeholder' => 'Masukan keterangan penawaran'
            ]
        ],
        'column' => [
            'no_rfq' => [
                'label' => 'No. RFQ'
            ],
            'rfq_date' => [
                'label' => 'Tanggal RFQ',
            ],
            'name_project' => [
                'label' => 'Proyek',
                'label_2' => 'Nama Proyek',
            ],
            'rab' => [
                'label' => 'RAB',
            ],
            'rap' => [
                'label' => 'RAP',
            ],
            'client_id' => [
                'label' => 'Client',
            ],
            'pic' => [
                'label' => 'PIC',
            ],
            'user' => [
                'label' => 'User',
            ],
            'closing_date' => [
                'label' => 'Closing Date',
            ],
            'status' => [
                'label' => 'Status',
            ],
            'information' => [
                'label' => 'Keterangan',
            ],
            'user_id' => [
                'label' => 'Diedit Oleh',
            ],
            'date_update' => [
                'label' => 'Waktu Perubahan',
            ],
            'history_update' => [
                'label' => 'Riwayat Perubahan',
            ]
        ],
        'validate' => [
            'quotation_check_exists' => 'Terdapat pilihan penawaran yang sudah diinput sebelumnya',
        ]
    ],
    'quotation_status' => [
        'title_header' => 'Status Penawaran',
        'title_modal_create' => 'Data Daftar Penawaran',
        'title_modal_edit' => 'Data Daftar Penawaran',
        'title_modal_delete' => 'Penawaran',
    ],
    'quotation_check' => [
        'title_header' => 'Data Proyeksi Pekerjaan',
        'title_modal_create' => 'Data Proyeksi Pekerjaan',
        'title_modal_edit' => 'Data Daftar Penawaran',
        'title_modal_delete' => 'Check List Proyeksi Proyek',
        'tab' => [
            'quotation_check' => 'Check List Proyeksi Proyek',
        ]
    ],
    'project_status' => [
        'title_header' => 'Status Proyek',
        'title_modal_create' => 'Status Proyek',
        'title_modal_edit' => 'Status Proyek',
        'title_modal_delete' => 'Status Proyek',
        'title_model_edit_unpaid' => 'Edit Data Unpaid Project',
        'title_model_edit_close' => 'Edit Data Close Project',
    ],
    'save_submit' => 'Simpan',
    'cancel_submit' => 'Batalkan',
    'save_changes_submit' => 'Simpan Perubahan',
    'approve_submit' => 'Setujui',
    'title_modal_voucher_payment' => 'Pembayaran Manual',
    'proforma_invoice' => [
        'title_header' => 'Proforma Invoice',
        'title_modal_create' => 'Data Proforma Invoice',
        'title_modal_edit' => 'Data Proforma Invoice',
        'title_modal_delete' => 'Proforma Invoice',
        'column' => [
            'invoice_number' => 'No. PI',
            'subkon_name' => 'Nama Subkon',
            'unit_price' => 'Unit Price',
            'ppn' => 'PPN',
            'amount' => 'Amount',
            'note' => 'Keterangan',
        ],
        'field' => [
            'invoice_number' => [
                'label' => 'No. PI',
                'placeholder' => 'Masukan nomor PI',
            ],
            'subkon_id' => [
                'label' => 'Nama Subkon',
                'placeholder' => '- PILIH SUBKON',
            ],
            'note' => [
                'label' => 'Keterangan',
                'placeholder' => 'Masukan keterangan tambahan',
            ]
        ],
    ],

];
