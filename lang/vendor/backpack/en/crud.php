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
    |
    */

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
            'is_from_quotation' => [
                'label' => 'Select from Quotation (Client Quotation)',
            ],
            'quotation_selection' => [
                'label' => 'Select from Quotation (Client Quotation)',
            ],
            'quotation_selection_info' => 'Select one or more quotations below to auto-fill PO data.',
        ],
    ],
    'invoice_client' => [
        'column' => [
            'withholding_agent' => 'Withholding Agent',
        ],
        'field' => [
            'withholding_agent' => [
                'label' => 'Withholding Agent',
                'placeholder' => '- SELECT WITHHOLDING AGENT -',
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
];
