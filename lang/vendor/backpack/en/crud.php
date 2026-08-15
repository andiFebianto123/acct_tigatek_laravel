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
        'title' => 'Payment',
        'payment' => 'Pay',
    ],
    'loading' => 'Loading...',
    'please_wait' => 'Please wait...',

    // Forms
    'save_action_save_and_new' => 'Save and new item',
    'save_action_save_and_edit' => 'Save and edit this item',
    'save_action_save_and_back' => 'Save and back',
    'save_action_save_and_preview' => 'Save and preview',
    'save_action_changed_notification' => 'Default behaviour after saving has been changed.',

    // Create form
    'add' => 'Add',
    'back_to_all' => 'Back to all ',
    'cancel' => 'Cancel',
    'add_a_new' => 'Add a new ',

    // Edit form
    'edit' => 'Edit',
    'save' => 'Save',

    // Translatable models
    'edit_translations' => 'Translation',
    'language' => 'Language',

    // CRUD table view
    'all' => 'All ',
    'in_the_database' => 'in the database',
    'list' => 'List',
    'reset' => 'Reset',
    'actions' => 'Actions',
    'preview' => 'Preview',
    'delete' => 'Delete',
    'admin' => 'Admin',
    'details_row' => 'This is the details row. Modify as you please.',
    'details_row_loading_error' => 'There was an error loading the details. Please retry.',
    'clone' => 'Clone',
    'clone_success' => '<strong>Entry cloned</strong><br>A new entry has been added, with the same information as this one.',
    'clone_failure' => '<strong>Cloning failed</strong><br>The new entry could not be created. Please try again.',

    // Confirmation messages and bubbles
    'delete_confirm' => 'Are you sure you want to delete this item?',
    'delete_confirm_2' => 'Are you sure you want to delete data',
    'delete_confirmation_title' => 'Item Deleted',
    'delete_confirmation_message' => 'The item has been deleted successfully.',
    'delete_confirmation_not_title' => 'NOT deleted',
    'delete_confirmation_not_message' => "There's been an error. Your item might not have been deleted.",
    'delete_confirmation_not_deleted_title' => 'Not deleted',
    'delete_confirmation_not_deleted_message' => 'Nothing happened. Your item is safe.',

    // Bulk actions
    'bulk_no_entries_selected_title' => 'No entries selected',
    'bulk_no_entries_selected_message' => 'Please select one or more items to perform a bulk action on them.',

    // Bulk delete
    'bulk_delete_are_you_sure' => 'Are you sure you want to delete these :number entries?',
    'bulk_delete_sucess_title' => 'Entries deleted',
    'bulk_delete_sucess_message' => ' items have been deleted',
    'bulk_delete_error_title' => 'Delete failed',
    'bulk_delete_error_message' => 'One or more items could not be deleted',

    // Bulk clone
    'bulk_clone_are_you_sure' => 'Are you sure you want to clone these :number entries?',
    'bulk_clone_sucess_title' => 'Entries cloned',
    'bulk_clone_sucess_message' => ' items have been cloned.',
    'bulk_clone_error_title' => 'Cloning failed',
    'bulk_clone_error_message' => 'One or more entries could not be created. Please try again.',

    // Ajax errors
    'ajax_error_title' => 'Error',
    'ajax_error_text' => 'Error loading page. Please refresh the page.',

    // DataTables translation
    'emptyTable' => 'No data available in table',
    'info' => 'Showing _START_ to _END_ of _TOTAL_ entries',
    'infoEmpty' => 'No entries',
    'infoFiltered' => '(filtered from _MAX_ total entries)',
    'infoPostFix' => '.',
    'thousands' => ',',
    'lengthMenu' => '_MENU_ entries per page',
    'loadingRecords' => 'Loading...',
    'processing' => 'Processing...',
    'search' => 'Search',
    'zeroRecords' => 'No matching entries found',
    'paginate' => [
        'first' => 'First',
        'last' => 'Last',
        'next' => 'Next',
        'previous' => 'Previous',
    ],
    'aria' => [
        'sortAscending' => ': activate to sort column ascending',
        'sortDescending' => ': activate to sort column descending',
    ],
    'export' => [
        'export' => 'Export',
        'copy' => 'Copy',
        'excel' => 'Excel',
        'csv' => 'CSV',
        'pdf' => 'PDF',
        'print' => 'Print',
        'column_visibility' => 'Column visibility',
    ],
    'custom_views' => [
        'title' => 'custom views',
        'title_short' => 'views',
        'default' => 'default',
    ],

    // global crud - errors
    'unauthorized_access' => 'Unauthorized access - you do not have the necessary permissions to see this page.',
    'please_fix' => 'Please fix the following errors:',

    // global crud - success / error notification bubbles
    'insert_success' => 'The item has been added successfully.',
    'update_success' => 'The item has been modified successfully.',

    // CRUD reorder view
    'reorder' => 'Reorder',
    'reorder_text' => 'Use drag&drop to reorder.',
    'reorder_success_title' => 'Done',
    'reorder_success_message' => 'Your order has been saved.',
    'reorder_error_title' => 'Error',
    'reorder_error_message' => 'Your order has not been saved.',

    // CRUD yes/no
    'yes' => 'Yes',
    'no' => 'No',

    // CRUD filters navbar view
    'filters' => 'Filters',
    'toggle_filters' => 'Toggle filters',
    'remove_filters' => 'Remove filters',
    'apply' => 'Apply',

    //filters language strings
    'today' => 'Today',
    'yesterday' => 'Yesterday',
    'last_7_days' => 'Last 7 Days',
    'last_30_days' => 'Last 30 Days',
    'this_month' => 'This Month',
    'last_month' => 'Last Month',
    'custom_range' => 'Custom Range',
    'weekLabel' => 'W',

    // Fields
    'browse_uploads' => 'Browse uploads',
    'select_all' => 'Select All',
    'unselect_all' => 'Unselect All',
    'select_files' => 'Select files',
    'select_file' => 'Select file',
    'clear' => 'Clear',
    'page_link' => 'Page link',
    'page_link_placeholder' => 'http://example.com/your-desired-page',
    'internal_link' => 'Internal link',
    'internal_link_placeholder' => 'Internal slug. Ex: \'admin/page\' (no quotes) for \':url\'',
    'external_link' => 'External link',
    'choose_file' => 'Choose file',
    'new_item' => 'New Item',
    'select_entry' => 'Select an entry',
    'select_entries' => 'Select entries',
    'upload_multiple_files_selected' => 'Files selected. After save, they will show up above.',

    //Table field
    'table_cant_add' => 'Cannot add new :entity',
    'table_max_reached' => 'Maximum number of :max reached',

    // google_map
    'google_map_locate' => 'Get my location',

    // File manager
    'file_manager' => 'File Manager',

    // InlineCreateOperation
    'related_entry_created_success' => 'Related entry has been created and selected.',
    'related_entry_created_error' => 'Could not create related entry.',
    'inline_saving' => 'Saving...',

    // returned when no translations found in select inputs
    'empty_translations' => '(empty)',

    // The pivot selector required validation message
    'pivot_selector_required_validation_message' => 'The pivot field is required.',

    // Quick button messages
    'quick_button_ajax_error_title' => 'Request Failed!',
    'quick_button_ajax_error_message' => 'There was an error processing your request.',
    'quick_button_ajax_success_title' => 'Request Completed!',
    'quick_button_ajax_success_message' => 'Your request was completed with success.',

    // translations
    'no_attributes_translated' => 'This entry is not translated in :locale.',
    'no_attributes_translated_href_text' => 'Fill inputs from :locale',

    'menu' => [
        'tracker' => 'Monitoring Tracker',
        'delivery_note' => 'Surat Jalan',
        'bast' => 'BAST',
        'billing_device' => 'Billing Device',
        'billing_simcard' => 'Billing SIMCARD',
        'transaction_history' => 'Transaction History',
        'billing_notification' => 'Billing Notification',
        'inventory' => 'Inventory',
        'device_stock' => 'Stok Barang',
    ],
    'setting' => [
        'brand_setting' => 'Brand Setting',
        'system_setting' => 'System Setting',
        'company_setting' => 'Company Setting',
        'logo_dark' => 'Logo Dark',
        'logo_light' => 'Logo Light',
        'favicon' => 'Favicon',
        'upload_file_here' => 'Upload File Here',
        'save_changes' => 'Save Changes',
        'currency' => 'Currency',
        'position_currency_symbol' => 'Position Currency Symbol',
        'pre' => 'Pre',
        'post' => 'Post',
        'po_prefix' => 'PO Prefix',
        'work_code_prefix' => 'Work Code Prefix',
        'faktur_prefix' => 'Tax Invoice Prefix',
        'pi_prefix' => 'PI Prefix',
        'quotation_prefix' => 'Quotation Prefix',
        'currency_symbol' => 'Currency Symbol',
        'usd_rate' => 'USD Rate (USD to IDR)',
        'format_decimal_number' => 'Decimal Format',
        'spk_prefix' => 'SPK Prefix',
        'voucher_prefix' => 'Voucher Prefix',
        'invoice_prefix' => 'Invoice Prefix',
        'surat_jalan_prefix' => 'Surat Jalan Prefix',
        'bast_prefix' => 'BAST Prefix',
        'company_name' => 'Company Name',
        'city' => 'City',
        'zip_code' => 'ZIP/Postal Code',
        'phone' => 'Phone',
        'start_time' => 'Company Start Time',
        'tax_number' => 'Tax Number',
        'address' => 'Address',
        'province' => 'Province',
        'country' => 'Country',
        'company_registration_number' => 'Company Registration Number',
        'end_time' => 'Company End Time',
        'no_file_chosen' => 'No file chosen',
    ],
    'monitoring_tracker' => [
        'tab' => [
            'tracker' => 'Monitoring Tracker',
            'tracker_edit' => 'Edit History',
        ],
        'column' => [
            'no' => 'No',
            'no_po_spk' => 'No. PO/SPK',
            'name' => 'Project Name',
            'client_id' => 'Company Name',
            'actual_end_date' => 'Actual End Date',
            'total_time' => 'Total Time',
            'progress' => 'Progress (%)',
            'status' => 'Status',
            'pic' => 'PIC',
            'user' => 'User',
            'information' => 'Information',
            'information_tracker' => 'Tracker Information',
            'duration' => 'Duration',
            'search_client' => 'Search company name...',
        ],
        'breadcrumb' => [
            'monitoring' => 'Monitoring',
            'tracker' => 'Monitoring Tracker',
        ],
        'title' => [
            'monitoring_tracker' => 'Monitoring Tracker',
        ],
        'history_update_text' => 'Edit monitoring tracker data',
    ],
    'device_stock' => [
        'title_header' => 'Stock Items',
        'export_title' => 'Stock Items Report',
        'placeholder_category' => 'Select/Add New Category',
        'info_header' => 'Device Stock Information',
        'modal_title' => 'Device Stock Data',
        'no_data' => 'No device stock data available.',
        'source_invoice' => 'Invoice',
        'source_invoice_title' => 'Price from Latest Sales Invoice',
        'source_master' => 'Master',
        'source_master_title' => 'Price from Item Master',
        'dashboard' => [
            'total_stok' => 'Total Stock Quantity',
            'total_barang' => 'Total Item Types',
            'total_nominal' => 'Total Nominal (Accumulated Selling Price)',
        ],
        'column' => [
            'name' => 'Item Name',
            'code' => 'Item Code',
            'category' => 'Category',
            'qty' => 'Qty',
            'sell_price' => 'Selling Price',
            'buy_price' => 'Buying Price',
            'latest_sell_price' => 'Latest Selling Price',
            'total_sell_nominal' => 'Total Nominal Sales',
        ],
    ],
    'subkon' => [
        'title_header' => 'List Subkon',
        'title_modal_create' => 'Data Vendor (Subkon)',
        'title_modal_edit' => 'Data Vendor (Subkon)',
        'column' => [
            'name' => 'Name Company',
            'address' => 'Address',
            'npwp' => 'NPWP',
            'phone' => 'Phone',
            'bank_name' => 'Bank Name',
            'bank_account' => 'Bank Account',
        ]
    ],
    'po' => [
        'title_header' => 'PO',
        'title_modal_create' => 'Data PO Vendor (Subkon)',
        'title_modal_edit' => 'Data PO Vendor (Subkon)',
        'column' => [
            'subkon_id ' => 'Name Company',
            'po_number' => 'No. PO',
            'job_name' => 'Job Name',
            'job_description' => 'Description/Details',
            'job_value' => 'Job Value',
            'tax_ppn' => 'Tax PPN',
            'total_value_with_tax' => 'Job Value Includes PPn',
            'document_path' => 'Document PO',
        ],
        'field' => [
            'po_type' => [
                'label' => 'PO Type',
                'subkon' => 'Subkon',
                'supplier' => 'Supplier',
            ],
            'job_name' => [
                'label' => 'Job Name',
                'placeholder' => 'Enter job name',
                'label_supplier' => 'Item Name',
                'placeholder_supplier' => 'Enter item name',
            ]
        ],
        'button' => [
            'post_stock' => 'Post Stock',
            'post_stock_title' => 'Submit / Post Stock to Warehouse & FIFO History',
        ],
        'badge' => [
            'stock_posted' => 'Stock Posted',
            'stock_posted_title' => 'Stock has been posted to Master Device & History Layer',
        ],
        'swal' => [
            'post_title' => 'Post Supplier PO Stock?',
            'post_text' => 'Item stocks for this PO will be permanently posted to Master Device Stock and Layer History FIFO.',
            'confirm_btn' => 'Yes, Submit Stock!',
            'cancel_btn' => 'Cancel',
        ],
        'noty' => [
            'post_success' => 'PO stock successfully posted to Master & History Layer FIFO!',
            'post_failed' => 'Failed to post stock.',
            'post_error' => 'An error occurred while posting stock.',
        ],
        'message' => [
            'post_stock_success' => 'PO stock :number successfully posted to Master Device Stock & History Layer FIFO!',
        ],
        'error' => [
            'cannot_delete_stock_used' => 'Deletion failed: Item stock \':name\' from this PO has already been consumed by an Invoice.',
            'only_supplier_po' => 'Only Supplier type Purchase Orders can post stock.',
            'stock_already_posted' => 'Stock for this Purchase Order has already been posted.',
            'empty_details' => 'Purchase Order has no item details.',
            'no_valid_stock_items' => 'No valid device stock items to post for this PO.',
        ]
    ],
    'spk' => [
        'title_header' => 'SPK',
        'title_modal_create' => 'Data SPK Vendor (Subkon)',
        'title_modal_edit' => 'Data SPK Vendor (Subkon)',
        'column' => [
            'subkon_id ' => 'Name Company',
            'no_spk' => 'No. SPK',
            'date_spk' => 'Date SPK',
            'job_name' => 'Job Name',
            'job_description' => 'Description/Details',
            'job_value' => 'Job Value',
            'tax_ppn' => 'PPn',
            'total_value_with_tax' => 'Job Value Includes PPn',
            'document_path' => 'Document SPK',
        ],
        'field' => [
            'subkon_id' => [
                'placeholder' => 'NAME COMPANY',
            ],
            'no_spk' => [
                'placeholder' => 'Enter the company SPK number',
            ],
            'date_spk' => [
                'placeholder' => 'Select Date',
            ],
            'job_name' => [
                'placeholder' => 'Enter a job name',
            ],
            'job_description' => [
                'label' => 'Job Description',
                'placeholder' => 'Write a job description',
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
                'label' => 'Upload SPK Document',
                'placeholder' => '000.000',
            ],
        ]
    ],
    'save_submit' => 'Save',
    'cancel_submit' => 'Cancel',
    'save_changes_submit' => 'Save Changes',
    'cash_account' => [
        'field' => [
            'bank_name' => [
                'add_new' => 'ADD NEW BANK',
                'custom_placeholder' => 'Enter New Bank Name',
                'save_button' => 'Save Bank',
                'save_hint' => 'Click "Save Bank" to register to database.',
                'save_confirm' => 'Are you sure you want to save this new bank?',
                'error_empty' => 'Bank name cannot be empty!',
                'error_ajax' => 'Error occurred while saving bank.',
            ],
            'bank_branch' => [
                'label' => 'Bank Branch',
                'placeholder' => 'Enter bank branch',
            ],
            'address' => [
                'label' => 'Bank Address',
                'placeholder' => 'Enter bank address',
            ],
            'swift_code' => [
                'label' => 'Swift Code',
                'placeholder' => 'Enter swift code',
            ]
        ],
        'field_transaction' => [
            'withholding_agent_status' => [
                'label' => 'Withholding Agent',
            ],
            'tax_ppn_nominal' => [
                'label' => 'VAT',
            ],
            'pph_nominal' => [
                'label' => 'Income Tax',
            ],
            'total_nominal_transfer' => [
                'label' => 'Total Transfer Value',
            ],
        ]
    ],
    'client_po' => [
        'field' => [
            'error_has_voucher' => 'This client PO already has a client voucher created so it cannot be made into a supplier.',
            'purchase_order_id_unique' => 'This Purchase Order has already been used by another Client PO.',
            'po_type' => [
                'label' => 'PO Type',
                'subkon' => 'Subkon',
                'supplier' => 'Supplier',
            ],
            'purchase_order_id' => [
                'label' => 'Select Supplier PO',
                'placeholder' => 'Select Supplier PO...',
            ],
            'job_name' => [
                'label_supplier' => 'Order Description',
                'placeholder_supplier' => 'Enter order description',
            ],
            'is_from_quotation' => [
                'label' => 'Select from Quotation (Client Quotation)',
            ],
            'quotation_selection' => [
                'label' => 'Select from Quotation (Client Quotation)',
            ],
            'quotation_selection_info' => 'Select one or more quotations below to auto-fill PO data.',
        ],
    ],
    'delivery_note' => [
        'title_header' => 'Delivery Note',
        'column' => [
            'number'         => 'Number',
            'date'           => 'Date',
            'client_id'      => 'Client Name',
            'reference_type' => 'Reference Type',
            'description'    => 'Description',
            'information'    => 'Information',
        ],
        'field' => [
            'reference_type' => [
                'label' => 'Reference Type',
                'placeholder' => '- SELECT REFERENCE TYPE -',
                'options' => [
                    'quotation'        => 'Quotation',
                    'proforma_invoice' => 'Proforma Invoice (PI)',
                    'client_po'        => 'Client PO',
                    'invoice_client'   => 'Invoice Client',
                ],
            ],
            'reference_id' => [
                'label' => 'Reference Document No.',
                'placeholder' => '- SELECT DOCUMENT -',
            ],
            'client_po_id' => [
                'label' => 'No. PO',
                'placeholder' => '- SELECT PO NO -',
            ],
            'invoice_client_id' => [
                'label' => 'Invoice No.',
                'placeholder' => '- SELECT INVOICE NO -',
            ],
            'client_id' => [
                'label' => 'Ship To',
                'placeholder' => '- SELECT CLIENT -',
            ],
            'address' => [
                'label' => 'Address',
                'placeholder' => 'Enter shipping address',
            ],
            'date' => [
                'label' => 'Date',
            ],
            'number' => [
                'label' => 'Delivery Note No.',
                'placeholder' => 'Enter delivery note number',
            ],
            'description' => [
                'label' => 'Description / Item',
                'placeholder' => 'Enter item description',
            ],
            'qty' => [
                'label' => 'Qty',
            ],
            'information' => [
                'label' => 'Information',
                'placeholder' => 'Additional notes',
            ],
        ],
    ],

    'bast' => [
        'title_header' => 'BAST',
        'column' => [
            'number' => 'Number',
            'date' => 'Date',
            'client_id' => 'Client Name',
            'first_party' => 'Penyerah',
            'description' => 'Description',
            'qty' => 'Qty',
            'information' => 'Keterangan',
        ],
        'field' => [
            'client_po_id' => [
                'label' => 'No. PO',
                'placeholder' => '- SELECT PO NO -',
            ],
            'first_party' => [
                'label' => 'First Party',
                'placeholder' => 'Enter first party name',
            ],
            'first_party_address' => [
                'label' => 'First Party Address',
                'placeholder' => 'Enter first party address',
            ],
            'client_id' => [
                'label' => 'Second Party',
                'placeholder' => '- SELECT CLIENT -',
            ],
            'address' => [
                'label' => 'Second Party Address',
                'placeholder' => 'Enter second party address',
            ],
            'date' => [
                'label' => 'Date',
            ],
            'number' => [
                'label' => 'No. BAST',
                'placeholder' => 'Enter BAST number',
            ],
            'description' => [
                'label' => 'Item',
                'placeholder' => 'Enter item description',
            ],
            'qty' => [
                'label' => 'Qty',
            ],
            'information' => [
                'label' => 'Keterangan',
                'placeholder' => 'Additional notes',
            ],
        ],
    ],
    'billing_device' => [
        'title_header' => 'Billing Device',
        'column' => [
            'device_id' => 'Device Id',
            'phone' => 'Phone',
            'vehicle_uid' => 'Vehicle Uid',
            'vehicle_name' => 'Vehicle Name',
            'imei' => 'Imei',
            'speed_limit' => 'Speed Limit',
            'sim_network' => 'Sim Network',
            'category' => 'Category',
            'model' => 'Model',
            'subscription_expiry_date' => 'Subscription Expiry Date',
            'installation_date' => 'Installation Date',
            'expired_date' => 'Expired date',
            'reminder_date' => 'Reminder Date',
        ],
    ],
    'billing_simcard' => [
        'title_header' => 'Billing SIMCARD',
        'column' => [
            'product' => 'Product',
            'device_name' => 'Device Name',
            'technology' => 'Technology',
            'device_profile_id' => 'Device Profile ID',
            'iccid' => 'ICCID',
            'msisdn' => 'MSISDN',
            'status' => 'Status',
            'simcard_status' => 'Status SIMCARD',
            'rate_plan' => 'Rate Plan',
            'subscription_expiry_date' => 'Subscription Expiry Date',
            'installation_date' => 'Installation Date',
            'expired_date' => 'Expired date',
        ],
    ],
    'billing_notification' => [
        'title_header' => 'Billing Notification',
        'column' => [
            'billable_type' => 'Billing Type',
            'billable_id' => 'Item ID',
            'notification_date' => 'Notification Date',
            'message' => 'Message',
            'company' => 'Milik Perusahaan',
        ],
    ],
    'transaction_history' => [
        'title_header' => 'Transaction History',
        'column' => [
            'transaction_id' => 'Transaction Id',
            'device_id' => 'Device Id',
            'msisdn' => 'MSISDN',
            'op_completion_time' => 'Op Completion Time',
            'operations' => 'Oprations',
            'devices_upload' => 'Devices Upload',
            'device_prosses' => 'Device Prosses',
            'device_update' => 'Device Update',
            'last_update' => 'Last Update',
            'status' => 'Status',
        ],
    ],
    'invoice_client' => [
        'column' => [
            'delivery_status' => 'Delivery Status',
            'withholding_agent' => 'Withholding Agent',
        ],
        'error' => [
            'cannot_edit_delivery_note_exists' => 'Invoice cannot be modified because a Delivery Note has already been issued.',
            'cannot_delete_delivery_note_exists' => 'Invoice cannot be deleted because an active Delivery Note is associated with it. Please delete the Delivery Note first.',
        ],
        'field' => [
            'withholding_agent' => [
                'label' => 'Withholding Agent',
                'placeholder' => '- SELECT WITHHOLDING AGENT -',
            ],
            'type_device' => [
                'label' => 'Device Type',
                'placeholder' => 'Select Device Type',
            ],
            'item' => [
                'items' => [
                    'name' => [
                        'label' => 'Device ID',
                    ],
                ],
            ],
        ],
    ],
    'expense_account' => [
        'title_header' => 'Chart Of Account',
        'column' => [
            'code' => 'Account Code',
            'name' => 'Account Name',
            'balance' => 'Balance',
            'balance_initial' => 'Initial Balance',
        ],
        'field' => [
            'balance' => [
                'placeholder' => '000.000',
            ],
        ],
    ],
    'filter' => [
        'all_year' => 'All Years',
    ],
    'voucher' => [
        'confirm' => [
            'account_loan' => 'Account cannot be a loan account',
        ],
    ],
    'profit_lost' => [
        'fields' => [
            'po_client_supplier' => [
                'label' => 'PO Client Supplier',
            ],
        ],
        'column' => [
            'total_qty_sold' => 'Qty Sold',
            'sell_value' => 'Total Sell (Revenue)',
            'avg_harga_jual_satuan' => 'Avg Sell Unit',
            'purchase_value' => 'Total Purchase (COGS FIFO)',
            'avg_harga_beli_satuan' => 'Avg Purchase Unit',
            'voucher_supplier_value' => 'Other Costs',
            'profit_lost_supplier' => 'Gross Profit',
            'margin_percent' => 'Margin (%)',
            'delivery_status' => 'Delivery Status',
        ],
    ],
    'proforma_invoice' => [
        'title_header' => 'Proforma Invoice',
        'title_modal_create' => 'Proforma Invoice Data',
        'title_modal_edit' => 'Proforma Invoice Data',
        'title_modal_delete' => 'Proforma Invoice',
        'column' => [
            'invoice_number' => 'PI No.',
            'invoice_date' => 'Date',
            'subkon_name' => 'Subcon Name',
            'unit_price' => 'Unit Price',
            'ppn' => 'PPN',
            'amount' => 'Amount',
            'note' => 'Notes',
        ],
        'field' => [
            'invoice_number' => [
                'label' => 'PI No.',
                'placeholder' => 'Enter PI number',
            ],
            'invoice_date' => [
                'label' => 'Date',
                'placeholder' => 'Select date',
            ],
            'subkon_id' => [
                'label' => 'Subcon Name',
                'placeholder' => '- SELECT SUBCON',
            ],
            'note' => [
                'label' => 'Notes',
                'placeholder' => 'Enter additional notes',
            ],
            'term' => [
                'label' => 'Term Details',
                'placeholder' => 'Enter term details',
            ],
            'document_imei_iccid' => [
                'label' => 'Upload Dokumen IMEI/ICCID',
                'hint'  => 'Supported format: .xlsx, .xls, .csv (Max 10MB)',
            ],
        ],
    ],
];
