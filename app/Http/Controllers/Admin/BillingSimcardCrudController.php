<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\FormaterExport;
use App\Http\Controllers\Operation\PermissionAccess;
use App\Models\BillingSimcard;
use App\DTOs\ClientManagement\BillingSimcardFilterData;
use App\Repositories\ClientManagement\BillingSimcardRepository;
use App\Services\ClientManagement\BillingSimcardService;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;
use Prologue\Alerts\Facades\Alert;

class BillingSimcardCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;
    use FormaterExport;

    protected $repository;
    protected $service;

    public function __construct(
        BillingSimcardRepository $repository,
        BillingSimcardService $service
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
        CRUD::setModel(BillingSimcard::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/billing/billing-simcard');
        CRUD::setEntityNameStrings(
            trans('backpack::crud.billing_simcard.title_header') ?? 'Billing SIMCARD',
            trans('backpack::crud.billing_simcard.title_header') ?? 'Billing SIMCARD'
        );

        $allAccess = [
            'AKSES SEMUA MENU ACCOUNTING',
            'AKSES MENU CLIENT',
        ];

        $viewMenu = [
            'MENU INDEX CLIENT BILLING SIMCARD',
        ];

        $this->settingPermission([
            'create' => [
                'CREATE INDEX CLIENT BILLING SIMCARD',
                ...$allAccess
            ],
            'update' => [
                'UPDATE INDEX CLIENT BILLING SIMCARD',
                ...$allAccess
            ],
            'delete' => [
                'DELETE INDEX CLIENT BILLING SIMCARD',
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
                'name'  => 'product',
                'type'  => 'text',
                'label' => trans('backpack::crud.billing_simcard.column.product') ?? 'Produk',
            ],
            [
                'name'  => 'device_name',
                'type'  => 'text',
                'label' => trans('backpack::crud.billing_simcard.column.device_name') ?? 'Nama Device',
            ],
            [
                'name'  => 'technology',
                'type'  => 'text',
                'label' => trans('backpack::crud.billing_simcard.column.technology') ?? 'Teknologi',
            ],
            [
                'name'  => 'device_profile_id',
                'type'  => 'text',
                'label' => trans('backpack::crud.billing_simcard.column.device_profile_id') ?? 'Device Profile ID',
            ],
            [
                'name'  => 'iccid',
                'type'  => 'text',
                'label' => trans('backpack::crud.billing_simcard.column.iccid') ?? 'ICCID',
            ],
            [
                'name'  => 'msisdn',
                'type'  => 'text',
                'label' => trans('backpack::crud.billing_simcard.column.msisdn') ?? 'MSISDN',
            ],
            [
                'name'  => 'status',
                'type'  => 'text',
                'label' => trans('backpack::crud.billing_simcard.column.status') ?? 'Status',
            ],
            [
                'name'  => 'simcard_status',
                'type'  => 'text',
                'label' => trans('backpack::crud.billing_simcard.column.simcard_status') ?? 'Status SIMCARD',
            ],
            [
                'name'  => 'rate_plan',
                'type'  => 'text',
                'label' => trans('backpack::crud.billing_simcard.column.rate_plan') ?? 'Rate Plan',
            ],
            [
                'name'   => 'subscription_expiry_date',
                'type'   => 'date',
                'label'  => trans('backpack::crud.billing_simcard.column.subscription_expiry_date') ?? 'Subscription Expiry Date',
                'format' => 'DD/MM/YYYY',
            ],
            [
                'name'   => 'installation_date',
                'type'   => 'date',
                'label'  => trans('backpack::crud.billing_simcard.column.installation_date') ?? 'Installation Date',
                'format' => 'DD/MM/YYYY',
            ],
            [
                'name'   => 'expired_date',
                'type'   => 'date',
                'label'  => trans('backpack::crud.billing_simcard.column.expired_date') ?? 'Expired date',
                'format' => 'DD/MM/YYYY',
            ],
            [
                'name'  => 'action',
                'type'  => 'action',
                'label' => trans('backpack::crud.actions') ?? 'Aksi',
            ]
        ]);

        $this->card->addCard([
            'name'   => 'billing_simcard',
            'line'   => 'top',
            'view'   => 'crud::components.datatable-origin',
            'params' => [
                'filter'       => true,
                'crud_custom'  => $this->crud,
                'hide_title'   => true,
                'columns'      => $columns,
                'filter_table' => collect($this->crud->filters()),
                'route'        => backpack_url('/billing/billing-simcard/search'),
            ]
        ]);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.billing_simcard.title_header') ?? 'Billing SIMCARD';
        $this->data['title_modal_edit'] = trans('backpack::crud.billing_simcard.title_header') ?? 'Billing SIMCARD';
        $this->data['title_modal_delete'] = trans('backpack::crud.billing_simcard.title_header') ?? 'Billing SIMCARD';
        $this->data['cards'] = $this->card;
        
        $breadcrumbs = [
            'Management Billing' => backpack_url('billing/billing-simcard'),
            'Billing SIMCARD' => backpack_url($this->crud->route)
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
        $this->crud->file_title_export_pdf = "Laporan_daftar_billing_simcard.pdf";
        $this->crud->file_title_export_excel = "Laporan_daftar_billing_simcard.xlsx";
        $this->crud->param_uri_export = "?export=1";

        // Custom buttons on top of the list view
        CRUD::addButtonFromView('top', 'import_excel_billing_simcard', 'import_excel_billing_simcard', 'beginning');
        CRUD::addButtonFromView('top', 'export-excel-table', 'export-excel-table', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-table', 'export-pdf-table', 'beginning');

        CRUD::disableResponsiveTable();

        $filters = BillingSimcardFilterData::fromRequest(request());
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
            'label' => trans('backpack::crud.billing_simcard.column.product') ?? 'Produk',
            'name'  => 'product',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_simcard.column.device_name') ?? 'Nama Device',
            'name'  => 'device_name',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_simcard.column.technology') ?? 'Teknologi',
            'name'  => 'technology',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_simcard.column.device_profile_id') ?? 'Device Profile ID',
            'name'  => 'device_profile_id',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_simcard.column.iccid') ?? 'ICCID',
            'name'  => 'iccid',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_simcard.column.msisdn') ?? 'MSISDN',
            'name'  => 'msisdn',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_simcard.column.status') ?? 'Status',
            'name'  => 'status',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_simcard.column.simcard_status') ?? 'Status SIMCARD',
            'name'  => 'simcard_status',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_simcard.column.rate_plan') ?? 'Rate Plan',
            'name'  => 'rate_plan',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.billing_simcard.column.subscription_expiry_date') ?? 'Subscription Expiry Date',
            'name'   => 'subscription_expiry_date',
            'type'   => 'date',
            'format' => 'DD/MM/YYYY',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.billing_simcard.column.installation_date') ?? 'Installation Date',
            'name'   => 'installation_date',
            'type'   => 'date',
            'format' => 'DD/MM/YYYY',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.billing_simcard.column.expired_date') ?? 'Expired date',
            'name'   => 'expired_date',
            'type'   => 'date',
            'format' => 'DD/MM/YYYY',
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

            $this->service->importBillingSimcards($request->file('file'), $companyId);

            DB::commit();

            return response()->json([
                'status'  => true,
                'message' => trans('backpack::crud.insert_success') ?? 'Data berhasil diimport.',
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Billing SIMCARD Excel Import Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => false,
                'error'  => 'Import failed: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * AJAX Delete Operation handler.
     */
    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        $id = $this->crud->getCurrentEntryId() ?? $id;

        $this->crud->delete($id);

        $messages['success'][] = trans('backpack::crud.delete_confirmation_message') ?? 'Item has been deleted.';
        $messages['events'] = [
            'crudTable-billing_simcard_create_success' => true,
        ];
        return response()->json($messages);
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
            'label' => trans('backpack::crud.billing_simcard.column.product') ?? 'Produk',
            'name'  => 'product',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_simcard.column.device_name') ?? 'Nama Device',
            'name'  => 'device_name',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_simcard.column.technology') ?? 'Teknologi',
            'name'  => 'technology',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_simcard.column.device_profile_id') ?? 'Device Profile ID',
            'name'  => 'device_profile_id',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_simcard.column.iccid') ?? 'ICCID',
            'name'  => 'iccid',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_simcard.column.msisdn') ?? 'MSISDN',
            'name'  => 'msisdn',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_simcard.column.status') ?? 'Status',
            'name'  => 'status',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_simcard.column.simcard_status') ?? 'Status SIMCARD',
            'name'  => 'simcard_status',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_simcard.column.rate_plan') ?? 'Rate Plan',
            'name'  => 'rate_plan',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.billing_simcard.column.subscription_expiry_date') ?? 'Subscription Expiry Date',
            'name'   => 'subscription_expiry_date',
            'type'   => 'date',
            'format' => 'DD/MM/YYYY',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.billing_simcard.column.installation_date') ?? 'Installation Date',
            'name'   => 'installation_date',
            'type'   => 'date',
            'format' => 'DD/MM/YYYY',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.billing_simcard.column.expired_date') ?? 'Expired date',
            'name'   => 'expired_date',
            'type'   => 'date',
            'format' => 'DD/MM/YYYY',
        ]);
    }

    /**
     * Define create/update fields configuration. Used by Show modal fallback.
     */
    protected function setupCreateOperation()
    {
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
            'name'  => 'product',
            'type'  => 'text',
            'label' => trans('backpack::crud.billing_simcard.column.product') ?? 'Produk',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'device_name',
            'type'  => 'text',
            'label' => trans('backpack::crud.billing_simcard.column.device_name') ?? 'Nama Device',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'technology',
            'type'  => 'text',
            'label' => trans('backpack::crud.billing_simcard.column.technology') ?? 'Teknologi',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'device_profile_id',
            'type'  => 'text',
            'label' => trans('backpack::crud.billing_simcard.column.device_profile_id') ?? 'Device Profile ID',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'iccid',
            'type'  => 'text',
            'label' => trans('backpack::crud.billing_simcard.column.iccid') ?? 'ICCID',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'msisdn',
            'type'  => 'text',
            'label' => trans('backpack::crud.billing_simcard.column.msisdn') ?? 'MSISDN',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'status',
            'type'  => 'text',
            'label' => trans('backpack::crud.billing_simcard.column.status') ?? 'Status',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'rate_plan',
            'type'  => 'text',
            'label' => trans('backpack::crud.billing_simcard.column.rate_plan') ?? 'Rate Plan',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'subscription_expiry_date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.billing_simcard.column.subscription_expiry_date') ?? 'Subscription Expiry Date',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
        ]);

        CRUD::addField([
            'name'  => 'installation_date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.billing_simcard.column.installation_date') ?? 'Installation Date',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
        ]);

        CRUD::addField([
            'name'  => 'expired_date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.billing_simcard.column.expired_date') ?? 'Expired date',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
        ]);
    }

    /**
     * Export the billing SIM cards list to PDF.
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

        $title = "DAFTAR BILLING SIMCARD";

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('exports.table-pdf', [
            'columns' => $columns,
            'items'   => $all_items,
            'title'   => $title
        ])->setPaper('A4', 'landscape');

        $fileName = 'billing_simcard_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    /**
     * Export the billing SIM cards list to Excel.
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

        $name = 'DAFTAR BILLING SIMCARD.xlsx';

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
}
