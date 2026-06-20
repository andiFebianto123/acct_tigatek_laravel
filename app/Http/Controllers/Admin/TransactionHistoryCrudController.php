<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\FormaterExport;
use App\Http\Controllers\Operation\PermissionAccess;
use App\Models\TransactionHistory;
use App\DTOs\ClientManagement\TransactionHistoryFilterData;
use App\DTOs\ClientManagement\TransactionHistoryData;
use App\Repositories\ClientManagement\TransactionHistoryRepository;
use App\Services\ClientManagement\TransactionHistoryService;
use App\Http\Requests\TransactionHistoryRequest;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Prologue\Alerts\Facades\Alert;

class TransactionHistoryCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;
    use FormaterExport;

    protected $repository;
    protected $service;

    public function __construct(
        TransactionHistoryRepository $repository,
        TransactionHistoryService $service
    ) {
        parent::__construct();
        $this->repository = $repository;
        $this->service = $service;
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup()
    {
        CRUD::setModel(TransactionHistory::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/billing/transaction-history');
        CRUD::setEntityNameStrings(
            trans('backpack::crud.transaction_history.title_header') ?? 'Riwayat Transaksi',
            trans('backpack::crud.transaction_history.title_header') ?? 'Riwayat Transaksi'
        );

        $allAccess = [
            'AKSES SEMUA MENU ACCOUNTING',
            'AKSES MENU CLIENT',
        ];

        $viewMenu = [
            'MENU INDEX CLIENT RIWAYAT TRANSAKSI',
        ];

        $this->settingPermission([
            'create' => [
                'CREATE INDEX CLIENT RIWAYAT TRANSAKSI',
                ...$allAccess
            ],
            'update' => [
                'UPDATE INDEX CLIENT RIWAYAT TRANSAKSI',
                ...$allAccess
            ],
            'delete' => [
                'DELETE INDEX CLIENT RIWAYAT TRANSAKSI',
                ...$allAccess
            ],
            'list' => $viewMenu,
            'show' => $viewMenu,
        ]);
    }

    /**
     * Display the index list using custom blank page containing custom table card.
     */
    public function index()
    {
        $this->crud->hasAccessOrFail('list');

        $columns = [
            [
                'name'      => 'row_number',
                'type'      => 'row_number',
                'label'     => 'No',
                'orderable' => false,
            ],
        ];

        if (backpack_user()->hasRole('Super Admin')) {
            $columns[] = [
                'label' => trans('backpack::crud.subkon.column.company') ?? 'Milik Perusahaan',
                'type'  => 'text',
                'name'  => 'company.name',
            ];
        }

        $columns = array_merge($columns, [
            [
                'name'  => 'transaction_id',
                'type'  => 'text',
                'label' => trans('backpack::crud.transaction_history.column.transaction_id') ?? 'Transaction Id',
            ],
            [
                'name'  => 'device_id',
                'type'  => 'text',
                'label' => trans('backpack::crud.transaction_history.column.device_id') ?? 'Device Id',
            ],
            [
                'name'  => 'msisdn',
                'type'  => 'text',
                'label' => trans('backpack::crud.transaction_history.column.msisdn') ?? 'MSISDN',
            ],
            [
                'name'   => 'op_completion_time',
                'type'   => 'datetime',
                'label'  => trans('backpack::crud.transaction_history.column.op_completion_time') ?? 'Op Completion Time',
                'format' => 'DD/MM/YYYY HH:mm:ss',
            ],
            [
                'name'  => 'operations',
                'type'  => 'text',
                'label' => trans('backpack::crud.transaction_history.column.operations') ?? 'Oprations',
            ],
            [
                'name'  => 'devices_upload',
                'type'  => 'number',
                'label' => trans('backpack::crud.transaction_history.column.devices_upload') ?? 'Devices Upload',
            ],
            [
                'name'  => 'device_prosses',
                'type'  => 'number',
                'label' => trans('backpack::crud.transaction_history.column.device_prosses') ?? 'Device Prosses',
            ],
            [
                'name'  => 'device_update',
                'type'  => 'number',
                'label' => trans('backpack::crud.transaction_history.column.device_update') ?? 'Device Update',
            ],
            [
                'name'   => 'last_update',
                'type'   => 'datetime',
                'label'  => trans('backpack::crud.transaction_history.column.last_update') ?? 'Last Update',
                'format' => 'DD/MM/YYYY HH:mm:ss',
            ],
            [
                'name'  => 'status',
                'type'  => 'text',
                'label' => trans('backpack::crud.transaction_history.column.status') ?? 'Status',
            ],
            [
                'name'  => 'action',
                'type'  => 'action',
                'label' => trans('backpack::crud.actions') ?? 'Aksi',
            ]
        ]);

        $this->card->addCard([
            'name'   => 'transaction_history',
            'line'   => 'top',
            'view'   => 'crud::components.datatable-origin',
            'params' => [
                'filter'       => true,
                'crud_custom'  => $this->crud,
                'hide_title'   => true,
                'columns'      => $columns,
                'filter_table' => collect($this->crud->filters()),
                'route'        => backpack_url('/billing/transaction-history/search'),
            ]
        ]);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.transaction_history.title_header') ?? 'Riwayat Transaksi';
        $this->data['title_modal_edit'] = trans('backpack::crud.transaction_history.title_header') ?? 'Riwayat Transaksi';
        $this->data['title_modal_delete'] = trans('backpack::crud.transaction_history.title_header') ?? 'Riwayat Transaksi';
        $this->data['cards'] = $this->card;
        
        $breadcrumbs = [
            'Management Billing' => backpack_url('billing/transaction-history'),
            'Riwayat Transaksi' => backpack_url($this->crud->route)
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;

        $list = "crud::list-blank" ?? $this->crud->getListView();

        return view($list, $this->data);
    }

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation()
    {
        $this->crud->file_title_export_pdf = "Laporan_riwayat_transaksi.pdf";
        $this->crud->file_title_export_excel = "Laporan_riwayat_transaksi.xlsx";
        $this->crud->param_uri_export = "?export=1";

        // Custom buttons on top of the list view
        CRUD::addButtonFromView('top', 'import_excel_transaction_history', 'import_excel_transaction_history', 'beginning');
        CRUD::addButtonFromView('top', 'export-excel-table', 'export-excel-table', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-table', 'export-pdf-table', 'beginning');

        CRUD::disableResponsiveTable();

        $filters = TransactionHistoryFilterData::fromRequest(request());
        $this->crud->query = $this->repository->getFilteredData($filters);

        CRUD::addColumn([
            'name'      => 'row_number',
            'type'      => 'row_number',
            'label'     => 'No',
            'orderable' => false,
            'wrapper'   => [
                'element' => 'strong',
            ]
        ])->makeFirstColumn();

        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::column([
                'label'     => trans('backpack::crud.subkon.column.company') ?? 'Milik Perusahaan',
                'type'      => 'select',
                'name'      => 'company_id',
                'entity'    => 'company',
                'attribute' => 'name',
                'model'     => "App\Models\Company",
            ]);
        }

        CRUD::column([
            'label' => trans('backpack::crud.transaction_history.column.transaction_id') ?? 'Transaction Id',
            'name'  => 'transaction_id',
            'type'  => 'wrap_text',
            'width_box' => "220px",
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.transaction_history.column.device_id') ?? 'Device Id',
            'name'  => 'device_id',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.transaction_history.column.msisdn') ?? 'MSISDN',
            'name'  => 'msisdn',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.transaction_history.column.op_completion_time') ?? 'Op Completion Time',
            'name'   => 'op_completion_time',
            'type'   => 'datetime',
            'format' => 'DD/MM/YYYY HH:mm:ss',
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.transaction_history.column.operations') ?? 'Oprations',
            'name'  => 'operations',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.transaction_history.column.devices_upload') ?? 'Devices Upload',
            'name'  => 'devices_upload',
            'type'  => 'number'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.transaction_history.column.device_prosses') ?? 'Device Prosses',
            'name'  => 'device_prosses',
            'type'  => 'number'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.transaction_history.column.device_update') ?? 'Device Update',
            'name'  => 'device_update',
            'type'  => 'number'
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.transaction_history.column.last_update') ?? 'Last Update',
            'name'   => 'last_update',
            'type'   => 'datetime',
            'format' => 'DD/MM/YYYY HH:mm:ss',
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.transaction_history.column.status') ?? 'Status',
            'name'  => 'status',
            'type'  => 'text'
        ]);
    }

    /**
     * Handle the AJAX Search for the custom datatable.
     */
    public function search()
    {
        $this->crud->hasAccessOrFail('list');

        $this->crud->applyUnappliedFilters();

        $start = (int) request()->input('start');
        $length = (int) request()->input('length');
        $search = request()->input('search');

        if ($length && ! in_array($length, $this->crud->getPageLengthMenu()[0])) {
            return response()->json([
                'error' => 'Unknown page length.',
            ], 400);
        }

        if ($search && $search['value'] ?? false) {
            $this->crud->applySearchTerm($search['value']);
        }
        if ($start) {
            $this->crud->skip($start);
        }
        if ($length) {
            $this->crud->take($length);
        }
        $this->crud->applyDatatableOrder();

        $entries = $this->crud->getEntries();

        if ($this->crud->getOperationSetting('showEntryCount')) {
            $query_clone = $this->crud->query->toBase()->clone();
            $outer_query = $query_clone->newQuery();
            $subQuery = $query_clone->cloneWithout(['limit', 'offset']);

            $totalEntryCount = $outer_query->select(DB::raw('count(*) as total_rows'))
                ->fromSub($subQuery, 'total_aggregator')->cursor()->first()->total_rows;
            $filteredEntryCount = $totalEntryCount;
        } else {
            $totalEntryCount = $length;
            $entryCount = $entries->count();
            $filteredEntryCount = $entryCount < $length ? $entryCount : $length + $start + 1;
        }

        $this->crud->setOperationSetting('totalEntryCount', $totalEntryCount);

        return $this->crud->getEntriesAsJsonForDatatables($entries, $totalEntryCount, $filteredEntryCount, $start);
    }

    /**
     * Handle Excel file importing.
     */
    public function import(Request $request)
    {
        $this->crud->hasAccessOrFail('create');

        $rules = [
            'file' => 'required|file|mimes:xlsx,xls|max:10240', // 10MB limit
        ];

        if (backpack_user()->hasRole('Super Admin')) {
            $rules['company_id'] = 'required|integer|exists:companies,id';
        }

        $request->validate($rules);

        try {
            DB::beginTransaction();

            $companyId = null;
            if (backpack_user()->hasRole('Super Admin')) {
                $companyId = (int) $request->input('company_id');
            } else {
                $companyId = backpack_user()->company_id ? (int) backpack_user()->company_id : null;
            }

            $this->service->importTransactionHistories($request->file('file'), $companyId);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => trans('backpack::crud.insert_success') ?? 'Data berhasil diimport.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Transaction History Excel Import Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'error'  => 'Import failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * AJAX modal Show details handler.
     */
    public function show($id)
    {
        $this->crud->hasAccessOrFail('show');

        $id = $this->crud->getCurrentEntryId() ?? $id;

        if ($this->crud->get('show.softDeletes') && in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this->crud->model))) {
            $this->data['entry'] = $this->crud->getModel()->withTrashed()->findOrFail($id);
        } else {
            $this->data['entry'] = $this->crud->getEntryWithLocale($id);
        }

        $this->data['entry_value'] = $this->crud->getRowViews($this->data['entry']);
        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.preview') . ' ' . $this->crud->entity_name;

        return response()->json([
            'html' => view($this->crud->getShowView(), $this->data)->render()
        ]);
    }

    /**
     * Setup fields & columns sync for Show/Preview modal.
     */
    protected function setupShowOperation()
    {
        $this->setupCreateOperation();

        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::column([
                'label'     => trans('backpack::crud.subkon.column.company') ?? 'Milik Perusahaan',
                'type'      => 'select',
                'name'      => 'company_id',
                'entity'    => 'company',
                'attribute' => 'name',
                'model'     => "App\Models\Company",
            ]);
        }

        CRUD::column([
            'label' => trans('backpack::crud.transaction_history.column.transaction_id') ?? 'Transaction Id',
            'name'  => 'transaction_id',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.transaction_history.column.device_id') ?? 'Device Id',
            'name'  => 'device_id',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.transaction_history.column.msisdn') ?? 'MSISDN',
            'name'  => 'msisdn',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.transaction_history.column.op_completion_time') ?? 'Op Completion Time',
            'name'   => 'op_completion_time',
            'type'   => 'datetime',
            'format' => 'DD/MM/YYYY HH:mm:ss',
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.transaction_history.column.operations') ?? 'Oprations',
            'name'  => 'operations',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.transaction_history.column.devices_upload') ?? 'Devices Upload',
            'name'  => 'devices_upload',
            'type'  => 'number'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.transaction_history.column.device_prosses') ?? 'Device Prosses',
            'name'  => 'device_prosses',
            'type'  => 'number'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.transaction_history.column.device_update') ?? 'Device Update',
            'name'  => 'device_update',
            'type'  => 'number'
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.transaction_history.column.last_update') ?? 'Last Update',
            'name'   => 'last_update',
            'type'   => 'datetime',
            'format' => 'DD/MM/YYYY HH:mm:ss',
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.transaction_history.column.status') ?? 'Status',
            'name'  => 'status',
            'type'  => 'text'
        ]);
    }

    /**
     * Define create/update fields configuration. Used by Show modal fallback.
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(TransactionHistoryRequest::class);

        if (backpack_user()->hasRole('Super Admin')) {
            $companies = \App\Models\Company::pluck('name', 'id')->toArray();
            CRUD::addField([
                'label'   => trans('backpack::crud.subkon.column.company') ?? 'Milik Perusahaan',
                'type'    => 'select2_array',
                'name'    => 'company_id',
                'options' => ['' => 'All (Semua Perusahaan)'] + $companies,
                'wrapper' => [
                    'class' => 'form-group col-md-6',
                ],
            ]);
        }

        CRUD::addField([
            'name'  => 'transaction_id',
            'type'  => 'text',
            'label' => trans('backpack::crud.transaction_history.column.transaction_id') ?? 'Transaction Id',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'device_id',
            'type'  => 'text',
            'label' => trans('backpack::crud.transaction_history.column.device_id') ?? 'Device Id',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'msisdn',
            'type'  => 'text',
            'label' => trans('backpack::crud.transaction_history.column.msisdn') ?? 'MSISDN',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'op_completion_time',
            'type'  => 'datetime',
            'label' => trans('backpack::crud.transaction_history.column.op_completion_time') ?? 'Op Completion Time',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'operations',
            'type'  => 'text',
            'label' => trans('backpack::crud.transaction_history.column.operations') ?? 'Oprations',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'devices_upload',
            'type'  => 'number',
            'label' => trans('backpack::crud.transaction_history.column.devices_upload') ?? 'Devices Upload',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'device_prosses',
            'type'  => 'number',
            'label' => trans('backpack::crud.transaction_history.column.device_prosses') ?? 'Device Prosses',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'device_update',
            'type'  => 'number',
            'label' => trans('backpack::crud.transaction_history.column.device_update') ?? 'Device Update',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'last_update',
            'type'  => 'datetime',
            'label' => trans('backpack::crud.transaction_history.column.last_update') ?? 'Last Update',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'status',
            'type'  => 'text',
            'label' => trans('backpack::crud.transaction_history.column.status') ?? 'Status',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);
    }

    /**
     * Define update fields configuration.
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    /**
     * AJAX modal Create handler.
     */
    public function create()
    {
        $this->crud->hasAccessOrFail('create');
        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.add') . ' ' . $this->crud->entity_name;

        return response()->json([
            'html' => view('crud::create', $this->data)->render()
        ]);
    }

    /**
     * AJAX modal Edit details handler.
     */
    public function edit($id)
    {
        $this->crud->hasAccessOrFail('update');
        $id = $this->crud->getCurrentEntryId() ?? $id;
        $this->crud->registerFieldEvents();

        $this->data['entry'] = $this->crud->getEntryWithLocale($id);
        $this->crud->setOperationSetting('fields', $this->crud->getUpdateFields());
        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit') . ' ' . $this->crud->entity_name;
        $this->data['id'] = $id;

        return response()->json([
            'html' => view($this->crud->getEditView(), $this->data)->render()
        ]);
    }

    /**
     * Store operation.
     */
    public function store()
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        try {
            DB::beginTransaction();
            $data = TransactionHistoryData::fromRequest($request);
            $item = $this->service->createTransactionHistory($data);
            DB::commit();

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            return response()->json([
                'success' => true,
                'status'  => true,
                'data'    => $item,
                'events'  => [
                    'crudTable-transaction_history_create_success' => $item,
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Transaction History Store Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status'  => false,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * Update operation.
     */
    public function update()
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        try {
            DB::beginTransaction();
            $data = TransactionHistoryData::fromRequest($request);
            $id = $request->get($this->crud->model->getKeyName());
            $item = $this->service->updateTransactionHistory($id, $data);
            DB::commit();

            \Alert::success(trans('backpack::crud.update_success'))->flash();

            return response()->json([
                'success' => true,
                'status'  => true,
                'data'    => $item,
                'events'  => [
                    'crudTable-transaction_history_updated_success' => $item,
                ]
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Transaction History Update Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'status'  => false,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    /**
     * AJAX Delete Operation handler.
     */
    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        $id = $this->crud->getCurrentEntryId() ?? $id;

        try {
            $this->service->deleteTransactionHistory($id);

            $messages['success'][] = trans('backpack::crud.delete_confirmation_message') ?? 'Item has been deleted.';
            $messages['events'] = [
                'crudTable-transaction_history_create_success' => true,
            ];
            return response()->json($messages);
        } catch (\Throwable $e) {
            Log::error('Transaction History Delete Error: ' . $e->getMessage());

            return response()->json([
                'type'    => 'errors',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Export the transaction histories list to PDF.
     */
    public function exportPdf()
    {
        $this->setupListOperation();

        $columns = $this->crud->columns();
        $items = $this->crud->getEntries();

        $row_number = 0;
        $all_items = [];

        foreach ($items as $item) {
            $row_items = [];
            $row_number++;
            foreach ($columns as $column) {
                $item_value = ($column['name'] == 'row_number') ? $row_number : $this->crud->getCellView($column, $item, $row_number);
                $item_value = str_replace('<span>', '', $item_value);
                $item_value = str_replace('</span>', '', $item_value);
                $item_value = str_replace("\n", '', $item_value);
                $item_value = \App\Http\Helpers\CustomHelper::clean_html($item_value);
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $title = "DAFTAR RIWAYAT TRANSAKSI";

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.table-pdf', [
            'columns' => $columns,
            'items'   => $all_items,
            'title'   => $title
        ])->setPaper('A4', 'landscape');

        $fileName = 'transaction_history_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Export the transaction histories list to Excel.
     */
    public function exportExcel()
    {
        $this->setupListOperation();

        $columns = $this->crud->columns();
        $items = $this->crud->getEntries();

        $row_number = 0;
        $all_items = [];

        foreach ($items as $item) {
            $row_items = [];
            $row_number++;
            foreach ($columns as $column) {
                $item_value = ($column['name'] == 'row_number') ? $row_number : $this->crud->getCellView($column, $item, $row_number);
                $item_value = str_replace('<span>', '', $item_value);
                $item_value = str_replace('</span>', '', $item_value);
                $item_value = str_replace("\n", '', $item_value);
                $item_value = \App\Http\Helpers\CustomHelper::clean_html($item_value);
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $name = 'DAFTAR RIWAYAT TRANSAKSI.xlsx';

        return response()->streamDownload(function () use ($columns, $items, $all_items) {
            echo \Maatwebsite\Excel\Facades\Excel::raw(new \App\Http\Exports\ExportExcel(
                $columns,
                $all_items
            ), \Maatwebsite\Excel\Excel::XLSX);
        }, $name, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }

    /**
     * Download Excel template for import.
     */
    public function downloadTemplate()
    {
        $this->crud->hasAccessOrFail('create');

        $columns = [
            ['label' => 'transaction_id'],
            ['label' => 'device_id'],
            ['label' => 'msisdn'],
            ['label' => 'op_completion_time'],
            ['label' => 'oprations'],
            ['label' => 'devices_upload'],
            ['label' => 'device_prosses'],
            ['label' => 'device_update'],
            ['label' => 'last_update'],
            ['label' => 'status'],
        ];

        $data = [
            [
                '615cc1d0-e14f-482f-bcb3-a7bd3715ce50',
                'DEV-1001',
                '628123456789',
                '06/11/2026 08:40:16',
                'UPDATE_FIRMWARE',
                '10',
                '8',
                '8',
                '06/11/2026 08:40:16',
                'SUCCESS'
            ]
        ];

        $name = 'template_riwayat_transaksi.xlsx';

        return response()->streamDownload(function () use ($columns, $data) {
            echo \Maatwebsite\Excel\Facades\Excel::raw(new \App\Http\Exports\ExportExcel(
                $columns,
                $data
            ), \Maatwebsite\Excel\Excel::XLSX);
        }, $name, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }
}
