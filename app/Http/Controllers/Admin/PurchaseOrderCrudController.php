<?php

namespace App\Http\Controllers\Admin;

// use Backpack\CRUD\app\Http\Controllers\CrudController;
use Carbon\Carbon;
use App\Models\Setting;
use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Helpers\CustomHelper;
use Illuminate\Support\Facades\DB;
use App\Http\Exports\ExportVendorPo;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\CrudController;
use App\DTOs\SubkonManagement\PurchaseOrderData;
use App\DTOs\SubkonManagement\PurchaseOrderFilterData;
use App\Services\SubkonManagement\PurchaseOrderService;
use App\Repositories\SubkonManagement\PurchaseOrderRepository;
use App\Http\Requests\PurchaseOrderRequest;
use App\Http\Controllers\Admin\PurchaseOrderTabController;
use App\Http\Controllers\Operation\PermissionAccess;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class PurchaseOrderCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class PurchaseOrderCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;

    protected $purchaseOrderService;
    protected $purchaseOrderRepository;

    public function __construct(
        PurchaseOrderService $purchaseOrderService,
        PurchaseOrderRepository $purchaseOrderRepository
    ) {
        parent::__construct();
        $this->purchaseOrderService = $purchaseOrderService;
        $this->purchaseOrderRepository = $purchaseOrderRepository;
    }
    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\PurchaseOrder::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/vendor/purchase-order');
        CRUD::setEntityNameStrings('purchase order', 'purchase orders');

        $viewMenu = [
            'AKSES SEMUA VIEW ACCOUNTING',
            'AKSES SEMUA MENU ACCOUNTING',
            'AKSES MENU VENDOR',
            'MENU INDEX VENDOR PO',
        ];

        $allAccess = [
            'AKSES SEMUA MENU ACCOUNTING',
            'AKSES MENU VENDOR',
        ];

        $this->settingPermission([
            'create' => [
                'CREATE INDEX VENDOR PO',
                ...$allAccess,
            ],
            'update' => [
                'UPDATE INDEX VENDOR PO',
                ...$allAccess,
            ],
            'delete' => [
                'DELETE INDEX VENDOR PO',
                ...$allAccess,
            ],
            'list' => $viewMenu,
            'show' => $viewMenu,
            'print' => true,
        ]);

        CRUD::addButtonFromView('line', 'print_po_subkon', 'print_po_subkon', 'beginning');
    }

    public function setupTabsCrud($nameTabs)
    {
        if ($nameTabs == 'open') {
            $crud = new PurchaseOrderTabController();
            $crud->get_crud();
            return $crud;
        }
    }

    public function total_price()
    {
        return $this->purchaseOrderRepository->getTotalPrices(request()->filter_year);
    }

    private function applySearchFilters($query, $request)
    {
        $filters = PurchaseOrderFilterData::fromRequest($request);
        return $this->purchaseOrderRepository->applySearchFilters($query, $filters);
    }

    private function getPoColumns(): array
    {
        return [
            [
                'name'      => 'row_number',
                'type'      => 'row_number',
                'label'     => 'No',
                'orderable' => false,
            ],
            [
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'select',
                'name'      => 'company_id',
                'entity'    => 'company',
                'attribute' => 'name',
                'model'     => "App\Models\Company",
            ],
            [
                'label' => trans('backpack::crud.po.column.po_number'),
                'name'  => 'po_number',
                'type'  => 'text'
            ],
            [
                'label' => trans('backpack::crud.po.column.date_po'),
                'name'  => 'date_po',
                'type'  => 'date'
            ],
            [
                'label'     => trans('backpack::crud.subkon.column.name'),
                'type'      => 'select',
                'name'      => 'subkon_id',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.client_po.field.work_code.label'),
                'name'  => 'work_code',
                'type'  => 'text'
            ],
            [
                'label' => trans('backpack::crud.po.column.job_name'),
                'name'  => 'job_name',
                'type'  => 'text'
            ],
            [
                'name'  => 'currency_code',
                'label' => trans('backpack::crud.client_quotation.column.currency_code'),
                'type'  => 'closure',
                'function' => function ($entry) {
                    $code = $entry->currency_code ?? 'IDR';
                    $badgeClass = ($code === 'USD') ? 'badge bg-warning text-dark' : 'badge bg-secondary';
                    return '<span class="' . $badgeClass . '">' . e($code) . '</span>';
                },
                'escaped' => false,
            ],
            [
                'label' => trans('backpack::crud.po.column.job_description'),
                'name'  => 'job_description',
                'type'  => 'textarea'
            ],
            [
                'label' => trans('backpack::crud.po.column.job_value'),
                'name'  => 'job_value',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return CustomHelper::formatCurrency($entry->job_value, $entry->currency_code ?? 'IDR');
                },
            ],
            [
                'label' => trans('backpack::crud.po.column.job_value') . ' (IDR)',
                'name'  => 'job_value_base',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return CustomHelper::formatCurrency($entry->job_value_base ?? $entry->job_value, 'IDR');
                },
            ],
            [
                'label' => trans('backpack::crud.po.column.tax_ppn'),
                'name'  => 'tax_ppn',
                'type'  => 'number',
            ],
            [
                'label' => trans('backpack::crud.po.column.total_value_with_tax'),
                'name'  => 'total_value_with_tax',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return CustomHelper::formatCurrency($entry->total_value_with_tax, $entry->currency_code ?? 'IDR');
                },
            ],
            [
                'label' => trans('backpack::crud.po.column.due_date'),
                'name'  => 'date_po',
                'type'  => 'date'
            ],
            [
                'label' => trans('backpack::crud.po.column.status'),
                'name'  => 'status',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return strtoupper($entry->status ?? '');
                }
            ],
            [
                'name'  => 'document_path',
                'type'  => 'upload',
                'label' => trans('backpack::crud.po.column.document_path'),
            ],
            [
                'label' => trans('backpack::crud.po.column.additional_info'),
                'name'  => 'additional_info',
                'type'  => 'textarea'
            ],
            [
                'name'  => 'action',
                'type'  => 'action',
                'label' => trans('backpack::crud.actions'),
            ],
        ];
    }

    public function index()
    {
        $this->crud->hasAccessOrFail('list');

        $poColumns = $this->getPoColumns();

        $this->card->addCard([
            'name' => 'po_tab',
            'line' => 'top',
            'view' => 'crud::components.card-tab',
            'params' => [
                'tabs' => [
                    [
                        'name' => 'list_all_po',
                        'name_tab' => 'po_tab',
                        'label' => trans('backpack::crud.po.tab.title_all_po'),
                        'active' => true,
                        'view' => 'crud::components.datatable',
                        'params' => [
                            'filter' => true,
                            'crud_custom' => $this->crud,
                            'columns' => $poColumns,
                            'route' => backpack_url('/vendor/purchase-order/search?tab=list_all_po'),
                            'route_export_pdf' => backpack_url('/vendor/download-po-pdf?tab=list_all_po'),
                            'title_export_pdf' => 'Purchase_order.pdf',
                            'route_export_excel' => backpack_url('/vendor/download-po?tab=list_all_po'),
                            'title_export_excel' => 'Purchase_order.xlsx',
                        ],
                    ],
                    [
                        'name' => 'list_open',
                        'name_tab' => 'po_tab',
                        'label' => trans('backpack::crud.po.tab.open'),
                        'active' => false,
                        'view' => 'crud::components.datatable-po',
                        'params' => [
                            'filter' => true,
                            'crud_custom' => $this->crud,
                            'columns' => $poColumns,
                            'route' => backpack_url('/vendor/purchase-order/search?tab=list_open'),
                            'route_export_pdf' => backpack_url('/vendor/download-po-pdf?tab=list_open'),
                            'title_export_pdf' => 'Purchase_order.pdf',
                            'route_export_excel' => backpack_url('/vendor/download-po?tab=list_open'),
                            'title_export_excel' => 'Purchase_order.xlsx',
                        ],
                    ],
                    [
                        'name' => 'list_close',
                        'name_tab' => 'po_tab',
                        'label' => trans('backpack::crud.po.tab.close'),
                        'active' => false,
                        'view' => 'crud::components.datatable-po',
                        'params' => [
                            'filter' => true,
                            'crud_custom' => $this->crud,
                            'columns' => $poColumns,
                            'route' => backpack_url('/vendor/purchase-order/search?tab=list_close'),
                            'route_export_pdf' => backpack_url('/vendor/download-po-pdf?tab=list_close'),
                            'title_export_pdf' => 'Purchase_order.pdf',
                            'route_export_excel' => backpack_url('/vendor/download-po?tab=list_close'),
                            'title_export_excel' => 'Purchase_order.xlsx',
                        ],
                    ]
                ]
            ]
        ]);

        $this->card->addCard([
            'name' => 'voucher-plugin',
            'line' => 'top',
            'view' => 'crud::components.purchase-order-plugin',
            'parent_view' => 'crud::components.filter-parent',
            'params' => [],
        ]);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = "PO vendor (Subkon)";
        $this->data['title_modal_edit'] = "PO Vendor (Subkon)";
        $this->data['title_modal_delete'] = "PO Vendor (Subkon)";
        $this->data['cards'] = $this->card;

        $breadcrumbs = [
            'Vendor (Subkon)' => backpack_url('vendor'),
            'PO' => backpack_url($this->crud->route)
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;

        // $list = "crud::list-custom" ?? $this->crud->getListView();
        $list = "crud::list-blank" ?? $this->crud->getListView();
        return view($list, $this->data);
    }

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

    public function store()
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        try {
            $dto = PurchaseOrderData::fromRequest($request);
            $item = $this->purchaseOrderService->createPO($dto);

            $this->data['entry'] = $this->crud->entry = $item;
            \Alert::success(trans('backpack::crud.insert_success'))->flash();
            $this->crud->setSaveAction();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
                    'events' => $this->purchaseOrderService->getUIEvents($item, 'create'),
                ]);
            }
            return $this->crud->performSaveAction($item->getKey());
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function edit($id)
    {
        $this->crud->hasAccessOrFail('update');

        $id = $this->crud->getCurrentEntryId() ?? $id;

        $this->crud->registerFieldEvents();

        $entry = $this->crud->getEntryWithLocale($id);
        $entry->load('purchase_order_details.device_stock');
        $entry->purchase_order_details_edit = $entry->purchase_order_details;
        $this->data['entry'] = $entry;

        $this->crud->setOperationSetting('fields', $this->crud->getUpdateFields());

        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit') . ' ' . $this->crud->entity_name;
        $this->data['id'] = $id;

        return response()->json([
            'html' => view($this->crud->getEditView(), $this->data)->render()
        ]);
    }

    public function update()
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        try {
            $dto = PurchaseOrderData::fromRequest($request);
            $id = $request->get($this->crud->model->getKeyName());

            $item = $this->purchaseOrderService->updatePO($id, $dto);

            $this->data['entry'] = $this->crud->entry = $item;
            \Alert::success(trans('backpack::crud.update_success'))->flash();
            $this->crud->setSaveAction();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
                    'events' => $this->purchaseOrderService->getUIEvents($item, 'update'),
                ]);
            }

            return $this->crud->performSaveAction($item->getKey());
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function setCustomColumn($app)
    {
        CRUD::disableResponsiveTable();

        $settings = Setting::first();
        $request = request();
        $status_file = strpos(url()->current(), 'excel') ? 'excel' : 'pdf';


        $new_format_date = 'DD/MM/YYYY';

        $app->addColumn([
            'name'      => 'row_number',
            'type'      => 'row_number',
            'label'     => 'No',
            'orderable' => false,
            'wrapper' => [
                'element' => 'strong',
            ]
        ])->makeFirstColumn();

        $user = backpack_user();
        if ($user && !$user->canAccessAllCompanies()) {
            $accessibleCompanyIds = $user->getAccessibleCompanyIds();
            $this->crud->addClause('whereIn', 'company_id', $accessibleCompanyIds);
        }

        $app->addColumn([
            'name'      => 'company',
            'label'     => trans('backpack::crud.subkon.column.company'),
            'type'      => 'select',
            'entity'    => 'company',
            'attribute' => 'name',
            'model'     => "App\Models\Company",
        ]);
        $this->crud->addClause('with', 'company');

        $app->addColumn(
            [
                'label'  => trans('backpack::crud.po.column.po_number'),
                'name' => 'po_number',
                'type'  => 'text'
            ],
        );

        $app->addColumn(
            [
                'label'  => trans('backpack::crud.po.column.date_po'),
                'name' => 'date_po',
                'type'  => 'date',
                'format' => $new_format_date,
            ],
        );

        $app->addColumn([
            // 1-n relationship
            'label' => trans('backpack::crud.subkon.column.name'),
            'type'      => 'select',
            'name'      => 'subkon_id', // the column that contains the ID of that connected entity;
            'entity'    => 'subkon', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\Subkon", // foreign key model
            // OPTIONAL
            // 'limit' => 32, // Limit the number of characters shown
        ]);

        $app->addColumn(
            [
                'label'  => trans('backpack::crud.client_po.field.work_code.label'),
                'name' => 'work_code',
                'type'  => 'text'
            ],
        );

        $app->addColumn(
            [
                'label'  => trans('backpack::crud.po.column.job_name'),
                'name' => 'job_name',
                'type'  => 'wrap_text',
                'limit' => 600,
                'width_box' => '350px',
            ],
        );

        $app->addColumn([
            'label' => trans('backpack::crud.client_quotation.column.currency_code'),
            'name'  => 'currency_code',
            'type'  => 'text',
            'wrapper' => [
                'badge' => function ($crud, $column, $entry) {
                    return ($entry->currency_code === 'USD') ? 'badge bg-warning text-dark' : 'badge bg-secondary';
                }
            ]
        ]);

        $app->addColumn(
            [
                'label'  => trans('backpack::crud.po.column.job_description'),
                'name' => 'job_description',
                'type'  => 'wrap_text',
                'limit' => 600,
                'width_box' => '400px',
            ],
        );

        // $app->addColumn(
        //     [
        //         'label'  => trans('backpack::crud.po.column.job_value'),
        //         'name' => 'job_value',
        //         'type'  => 'number',
        //         'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
        //         'decimals'      => 2,
        //         'dec_point'     => ',',
        //         'thousands_sep' => '.',
        //     ],
        // );

        $app->addColumn([
            'label'  => trans('backpack::crud.client_quotation.column.job_value_exclude_ppn'),
            'name'   => 'job_value',
            'type'   => 'closure',
            'function' => function ($entry) use ($status_file) {
                return CustomHelper::formatCurrency($entry->job_value, $entry->currency_code, $status_file === 'excel');
            },
        ]);

        $app->addColumn([
            'label'  => trans('backpack::crud.client_quotation.column.job_value_base'),
            'name'   => 'job_value_base',
            'type'   => 'closure',
            'function' => function ($entry) use ($status_file) {
                return CustomHelper::formatCurrency($entry->job_value_base ?? $entry->job_value, 'IDR', $status_file === 'excel');
            },
        ]);

        $app->addColumn([
            'label'  => trans('backpack::crud.po.column.tax_ppn'),
            'name' => 'tax_ppn',
            'type'  => 'number',
            'suffix' => '%',
        ]);

        $app->addColumn([
            'label'  => trans('backpack::crud.client_quotation.column.job_value_include_ppn'),
            'name'   => 'total_value_with_tax',
            'type'   => 'closure',
            'function' => function ($entry) use ($status_file) {
                return CustomHelper::formatCurrency(($entry->job_value + ($entry->job_value * $entry->tax_ppn / 100)), $entry->currency_code, $status_file === 'excel');
            },
        ]);

        $app->addColumn([
            'label'  => trans('backpack::crud.po.column.due_date'),
            'name' => 'due_date',
            'type'  => 'date',
            'format' => $new_format_date,
        ]);

        $app->addColumn([
            'label'  => trans('backpack::crud.po.column.status'),
            'name' => 'status',
            'type'  => 'closure',
            'function' => function ($entry) {
                return strtoupper($entry->status);
            }
        ]);

        $app->addColumn([
            'name'   => 'document_path',
            'type'   => 'upload',
            'label'  => trans('backpack::crud.po.column.document_path'),
            'disk'   => 'public',
        ]);

        $app->addColumn(
            [
                'label'  => trans('backpack::crud.po.column.additional_info'),
                'name' => 'additional_info',
                'type'  => 'textarea'
            ],
        );

        if ($request->has('filter_year')) {
            if ($request->filter_year != 'all') {
                $filterYear = $request->filter_year;
                $this->crud->query = $this->crud->query
                    ->where(DB::raw("YEAR(date_po)"), $filterYear);
            }
        }

        $this->crud->query = $this->applySearchFilters($this->crud->query, $request);
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::addButtonFromView('top', 'export-excel', 'export-excel', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf', 'export-pdf', 'beginning');
        CRUD::addButtonFromView('top', 'filter_year', 'filter-year', 'beginning');
        CRUD::addButtonFromView('line', 'post_stock_button', 'post_stock_button', 'beginning');

        $type = request()->tab;
        if ($type == 'open') {
            $this->crud->query = $this->crud->query->where('status', PurchaseOrder::OPEN);
        } else if ($type == 'close') {
            $this->crud->query = $this->crud->query->where('status', PurchaseOrder::CLOSE);
        }
        $this->setCustomColumn($this->crud);
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(PurchaseOrderRequest::class);
        $settings = Setting::first();
        // CRUD::setFromDb(); // set fields from db columns.

        $settings = Setting::first();
        $po_prefix_value = [];
        $work_code_prefix = [];
        $work_code_disable = [
            // 'disabled' => true,
        ];
        if (!$this->crud->getCurrentEntryId()) {
            $po_prefix_value = [
                'value' => $settings?->po_prefix,
            ];
            if ($settings?->work_code_prefix) {
                $work_code_prefix = [
                    'value' => $settings->work_code_prefix,
                ];
            }
        } else {
            $id = $this->crud->getCurrentEntryId();
            if ($id && $this->purchaseOrderRepository->hasVoucher($id)) {
                $work_code_disable = [
                    'disabled' => true,
                ];
            }
        }

        $user = backpack_user();
        $accessibleCompanyIds = $user ? $user->getAccessibleCompanyIds() : [];

        CRUD::addField([
            'name'        => 'company_id',
            'label'       => trans('backpack::crud.subkon.column.company'),
            'type'        => 'select2_array',
            'options'     => \App\Models\Company::whereIn('id', $accessibleCompanyIds)->pluck('name', 'id')->toArray(),
            'allows_null' => false,
            'wrapper'     => ['class' => 'form-group col-md-6'],
        ]);
        CRUD::addField([   // Hidden
            'name'  => 'space_0',
            'type'  => 'hidden',
            'value' => 'active',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
            'attributes' => [
                'disabled'  => 'disabled',
            ]
        ]);

        CRUD::field([   // 1-n relationship
            'label'       => trans('backpack::crud.subkon.column.name'), // Table column heading
            'type'        => "select2_ajax_custom",
            'name'        => 'subkon_id', // the column that contains the ID of that connected entity
            'entity'      => 'subkon', // the method that defines the relationship in your Model
            'attribute'   => "name", // foreign key attribute that is shown to user
            'data_source' => backpack_url('vendor/select2-subkon-id'), // url to controller search function (with /{id} should return a single entry)
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
            'dependencies' => ['company_id'],
            'include_all_form_fields' => true,
        ]);

        CRUD::addField([
            'name' => 'pic',
            'label' => trans('backpack::crud.client_quotation.field.pic.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.client_quotation.field.pic.placeholder'),
            ],
        ]);

        CRUD::addField([
            'name'        => 'po_type',
            'label'       => trans('backpack::crud.po.field.po_type.label'),
            'type'        => 'select_from_array',
            'options'     => [
                'subkon' => trans('backpack::crud.po.field.po_type.subkon'),
                'supplier' => trans('backpack::crud.po.field.po_type.supplier'),
            ],
            'allows_null' => false,
            'default'     => 'subkon',
            'wrapper'     => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'po_number',
            'label' => trans('backpack::crud.po.column.po_number'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
            ...$po_prefix_value
        ]);

        CRUD::addField([
            'name' => 'date_po',
            'label' => trans('backpack::crud.po.column.date_po'),
            'type' => 'date',
            'attributes' => [
                'placeholder' => trans('backpack::crud.po.field.date_po.placeholder'),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'work_code',
            'label' => trans('backpack::crud.client_po.field.work_code.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                ...$work_code_disable,
                'placeholder' => trans('backpack::crud.client_po.field.work_code.placeholder'),
            ],
            ...$work_code_prefix
        ]);

        CRUD::addField([
            'name' => 'space_2',
            'type' => 'hidden',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'job_name',
            'label' => trans('backpack::crud.po.column.job_name'),
            'type' => 'text',
        ]);

        CRUD::addField([
            'name'        => 'currency_code',
            'label'       => trans('backpack::crud.client_quotation.field.currency_code.label'),
            'type'        => 'select_from_array',
            'options'     => [
                'IDR' => 'IDR (Rp)',
                'USD' => 'USD ($)',
            ],
            'default'     => 'IDR',
            'allows_null' => false,
            'wrapper'     => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'job_description',
            'label' => trans('backpack::crud.po.field.job_description.label'),
            'type' => 'textarea',
            'attributes' => [
                'placeholder' => trans('backpack::crud.po.field.job_description.placeholder')
            ]
        ]);

        CRUD::addField([
            'name' => 'job_value',
            'label' => trans('backpack::crud.po.column.job_value'),
            'type' => 'mask_currency',
            'currency_name' => 'currency_code',
            'currency_options' => [
                'IDR' => 'IDR (Rp)',
                'USD' => 'USD ($)',
            ],
            'default_currency' => 'IDR',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.po.column.job_value'),
            ]
        ]);

        CRUD::addField([
            'name' => 'tax_ppn',
            'label' => trans('backpack::crud.po.column.tax_ppn'),
            'type' => 'number',
            // optionals
            'attributes' => ["step" => "any"], // allow decimals
            'prefix'     => "%",
            // 'suffix'     => ".00",
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);


        CRUD::addField([
            'name' => 'total_value_with_tax',
            'label' => trans('backpack::crud.po.column.total_value_with_tax'),
            'type' => 'text',
            'attributes' => [
                'placeholder' => trans('backpack::crud.spk.field.total_value_with_tax.placeholder'),
            ],
            'attributes' => [
                'readonly' => true,
            ],
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'due_date',
            'label' => trans('backpack::crud.po.field.due_date.label'),
            'type' => 'date',
            'attributes' => [
                'placeholder' => trans('backpack::crud.po.field.field.due_date.placeholder'),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name'        => 'status',
            'label'       => trans('backpack::crud.po.field.status.label'),
            'type'        => 'select_from_array',
            'options'     => [
                '' => trans('backpack::crud.po.field.status.placeholder'),
                'open' => trans('backpack::crud.po.field.status.open'),
                'close' => trans('backpack::crud.po.field.status.close')
            ],
            'allows_null' => false,
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            // 'allows_multiple' => true, // OPTIONAL; needs you to cast this to array in your model;
        ]);

        CRUD::addField([
            'name' => 'document_path',
            'label' => trans('backpack::crud.po.column.document_path'),
            'type' => 'upload',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
            'disk' => 'public',
            'custom_upload' => true,
            // 'withFiles' => [
            //     'disk' => 'public',
            //     'path' => 'document_po',
            //     'deleteWhenEntryIsDeleted' => true,
            // ],
        ]);

        CRUD::addField([
            'name' => 'additional_info',
            'label' => trans('backpack::crud.po.field.additional_info.label'),
            'type' => 'textarea',
            'attributes' => [
                'placeholder' => trans('backpack::crud.po.field.additional_info.placeholder')
            ]
        ]);

        CRUD::addField([
            'name' => 'term',
            'label' => trans('backpack::crud.proforma_invoice.field.term.label'),
            'type' => 'tinymce_8',
            'default' => '<ol><li>Include PPN 12%</li><li>Include shipping costs</li><li>Terms Of Payment :<ul><li>100% before device delivered</li></ul></li></ol>',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.proforma_invoice.field.term.placeholder'),
            ],
        ]);

        $id = request()->segment(4);

        if ($id && $id != 'create') {
            CRUD::addField([
                'name' => 'purchase_order_details_edit',
                'label' => trans('backpack::crud.invoice_client.field.item.label') ?? 'PO Items',
                'type' => 'repeatable',
                'new_item_label'  => trans('backpack::crud.invoice_client.field.item.new_item_label') ?? 'Tambah Item',
                'wrapper' => [
                    'class' => 'form-group col-md-12',
                ],
                'fields' => [
                    [
                        'name' => 'reference_id',
                        'type' => 'select2_ajax_device_stock',
                        'label' => trans('backpack::crud.invoice_client.field.item.items.name.label') ?? 'Nama Barang',
                        'data_source' => backpack_url('vendor/purchase-order/select2-device-stock'),
                        'placeholder' => 'Pilih Nama Barang',
                        'minimum_input_length' => 0,
                        'model' => \App\Models\DeviceStock::class,
                        'attribute' => 'name',
                        'wrapper' => [
                            'class' => 'form-group col-md-5',
                        ]
                    ],
                    [
                        'name' => 'qty',
                        'type' => 'number',
                        'label' => 'QTY',
                        'default' => 1,
                        'wrapper' => [
                            'class' => 'form-group col-md-2',
                        ],
                        'attributes' => [
                            'min' => 1,
                        ]
                    ],
                    [
                        'name' => 'price',
                        'label' => trans('backpack::crud.invoice_client.field.item.items.price.label') ?? 'Harga',
                        'type' => 'mask_currency',
                        'currency_name' => 'price_currency',
                        'default_currency' => 'IDR',
                        'wrapper' => [
                            'class' => 'form-group col-md-5',
                        ],
                    ],
                ]
            ]);
        } else {
            CRUD::addField([
                'name' => 'purchase_order_details',
                'label' => trans('backpack::crud.invoice_client.field.item.label') ?? 'PO Items',
                'type' => 'repeatable',
                'new_item_label'  => trans('backpack::crud.invoice_client.field.item.new_item_label') ?? 'Tambah Item',
                'wrapper' => [
                    'class' => 'form-group col-md-12',
                ],
                'fields' => [
                    [
                        'name' => 'reference_id',
                        'type' => 'select2_ajax_device_stock',
                        'label' => trans('backpack::crud.invoice_client.field.item.items.name.label') ?? 'Nama Barang',
                        'data_source' => backpack_url('vendor/purchase-order/select2-device-stock'),
                        'placeholder' => 'Pilih Nama Barang',
                        'minimum_input_length' => 0,
                        'model' => \App\Models\DeviceStock::class,
                        'attribute' => 'name',
                        'wrapper' => [
                            'class' => 'form-group col-md-5',
                        ]
                    ],
                    [
                        'name' => 'qty',
                        'type' => 'number',
                        'label' => 'QTY',
                        'default' => 1,
                        'wrapper' => [
                            'class' => 'form-group col-md-2',
                        ],
                        'attributes' => [
                            'min' => 1,
                        ]
                    ],
                    [
                        'name' => 'price',
                        'label' => trans('backpack::crud.invoice_client.field.item.items.price.label') ?? 'Harga',
                        'type' => 'mask_currency',
                        'currency_name' => 'price_currency',
                        'default_currency' => 'IDR',
                        'wrapper'   => [
                            'class' => 'form-group col-md-5'
                        ],
                    ]
                ]
            ]);
        }

        CRUD::addField([
            'name' => 'logic_purchase_order',
            'type' => 'logic_purchase_order',
        ]);
    }

    public function select2DeviceStock()
    {
        $this->crud->hasAccessOrFail('create');

        $search = request()->input('q');

        $query = \App\Models\DeviceStock::select(['id', 'name', 'sell_price', 'code']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        $dataset = $query->paginate(10);

        $results = [];
        foreach ($dataset as $item) {
            $results[] = [
                'id' => $item->id,
                'text' => $item->name.' ('. $item->code .')',
                'sell_price' => (float) $item->sell_price,
            ];
        }
        return response()->json(['results' => $results]);
    }

    public function select2SubkonId()
    {
        $this->crud->hasAccessOrFail('create');

        $search = request()->input('q');
        $company_id = request()->input('company_id');

        $dataset = \App\Models\Subkon::select(['id', 'name'])
            ->where('name', 'LIKE', "%$search%");

        if ($company_id) {
            $dataset = $dataset->where('company_id', $company_id);
        }

        $dataset = $dataset->paginate(10);

        $results = [];
        foreach ($dataset as $item) {
            $results[] = [
                'id' => $item->id,
                'text' => $item->name,
            ];
        }
        return response()->json(['results' => $results]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupShowOperation()
    {
        $this->setupCreateOperation();
        $settings = Setting::first();
        $new_format_date = 'DD/MM/YYYY';

        // update field hidden
        CRUD::field('space_0')->remove();
        CRUD::field('space')->remove();
        CRUD::field('additional_info')->remove();
        CRUD::field('logic_purchase_order')->remove();
        CRUD::field('space_2')->remove();
        CRUD::field('po_type')->remove();

        // update subkon id
        CRUD::field('company_id')->remove();
        CRUD::field('subkon_id')->remove();
        CRUD::field([   // 1-n relationship
            'label'       => trans('backpack::crud.subkon.column.name'), // Table column heading
            'type'        => "select2_ajax_custom",
            'name'        => 'subkon_id', // the column that contains the ID of that connected entity
            'entity'      => 'subkon', // the method that defines the relationship in your Model
            'attribute'   => "name", // foreign key attribute that is shown to user
            'data_source' => backpack_url('vendor/select2-subkon-id'), // url to controller search function (with /{id} should return a single entry)
            'wrapper'   => [
                'class' => 'form-group col-md-12'
            ],
        ])->before('po_number');

        CRUD::field([
            'name'      => 'company',
            'label'     => trans('backpack::crud.subkon.column.company'),
            'type'      => 'select',
            'entity'    => 'company',
            'attribute' => 'name',
            'model'     => "App\Models\Company",
            'wrapper'   => ['class' => 'form-group col-md-12'],
        ])->before('subkon_id');
        // update job_name
        CRUD::field('job_name')->remove();
        CRUD::field([
            'label'  => trans('backpack::crud.po.column.job_name'),
            'name' => 'job_name',
            'type'  => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-12',
            ]
        ])->before('job_description');
        // update job_description
        CRUD::field('job_description')->remove();
        CRUD::field([
            'name' => 'job_description',
            'label' => trans('backpack::crud.po.field.job_description.label'),
            'type' => 'textarea',
            'wrapper' => [
                'class' => 'form-group col-md-12',
            ]
        ])->before('job_value');
        CRUD::field([
            'label'  => trans('backpack::crud.po.column.additional_info'),
            'name' => 'additional_info',
            'type'  => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-12',
            ]
        ])->after('document_path');

        CRUD::field('term')->remove();
        CRUD::addField([
            'name' => 'term',
            'label' => trans('backpack::crud.proforma_invoice.field.term.label'),
            'type' => 'custom_html',
            'value' => $this->crud->getCurrentEntry() ? $this->crud->getCurrentEntry()->term : '',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
        ]);

        // load entry data
        // $this->setupListOperation();

        // remove row number
        // CRUD::column('row_number')->remove();

        CRUD::column([
            'label' => trans('backpack::crud.client_quotation.field.pic.label'),
            'name'  => 'pic',
            'type'  => 'text',
        ]);

        CRUD::column([
            'name'      => 'company',
            'label'     => trans('backpack::crud.subkon.column.company'),
            'type'      => 'closure',
            'function' => function ($entry) {
                return $entry->company?->name ?? '-';
            }
        ]);

        CRUD::column([
            // 1-n relationship
            'label'     => trans('backpack::crud.subkon.column.name'),
            'type'      => 'select',
            'name'      => 'subkon_id', // the column that contains the ID of that connected entity;
            'entity'    => 'subkon', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\Subkon", // foreign key model
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.po.column.po_number'),
            'name'  => 'po_number',
            'type'  => 'text',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.po.column.date_po'),
            'name'   => 'date_po',
            'type'   => 'date',
            'format' => $new_format_date,
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.client_po.field.work_code.label'),
            'name'  => 'work_code',
            'type'  => 'text',
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.client_quotation.column.currency_code'),
            'name'  => 'currency_code',
            'type'  => 'text',
            'wrapper' => [
                'badge' => function ($crud, $column, $entry) {
                    return ($entry->currency_code === 'USD') ? 'badge bg-warning text-dark' : 'badge bg-secondary';
                }
            ]
        ]);

        CRUD::column([
            'label'     => trans('backpack::crud.po.column.job_name'),
            'name'      => 'job_name',
            'type'      => 'wrap_text',
            'limit'     => 600,
            'width_box' => '350px',
        ]);

        CRUD::column([
            'label'     => trans('backpack::crud.po.column.job_description'),
            'name'      => 'job_description',
            'type'      => 'wrap_text',
            'limit'     => 600,
            'width_box' => '400px',
        ]);

        CRUD::column([
            'label'    => trans('backpack::crud.po.column.job_value'),
            'name'     => 'job_value',
            'type'     => 'closure',
            'function' => function ($entry) {
                return CustomHelper::formatCurrency($entry->job_value, $entry->currency_code ?? 'IDR');
            },
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.po.column.tax_ppn'),
            'name'   => 'tax_ppn',
            'type'   => 'number',
            'suffix' => '%',
        ]);

        CRUD::column([
            'label'    => trans('backpack::crud.po.column.total_value_with_tax'),
            'name'     => 'total_value_with_tax',
            'type'     => 'closure',
            'function' => function ($entry) {
                return CustomHelper::formatCurrency($entry->total_value_with_tax, $entry->currency_code ?? 'IDR');
            },
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.po.column.due_date'),
            'name'   => 'due_date',
            'type'   => 'date',
            'format' => $new_format_date,
        ]);

        CRUD::column([
            'label'    => trans('backpack::crud.po.column.status'),
            'name'     => 'status',
            'type'     => 'closure',
            'function' => function ($entry) {
                return strtoupper($entry->status);
            }
        ]);

        CRUD::column([
            'label'   => trans('backpack::crud.po.column.document_path'),
            'name'    => 'document_path',
            'type'    => 'text',
            'wrapper' => [
                'element' => 'a', // the element will default to "a" so you can skip it here
                'href' => function ($crud, $column, $entry, $related_key) {
                    if ($entry->document_path != '') {
                        return url("storage/" . $entry->document_path);
                    }
                    return "javascript:void(0)";
                },
                'target' => '_blank',
            ],
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.po.column.additional_info'),
            'name'  => 'additional_info',
            'type'  => 'textarea'
        ]);

        $detailsFieldName = request()->segment(4) && request()->segment(4) != 'create' ? 'purchase_order_details_edit' : 'purchase_order_details';
        CRUD::column([
            'label'    => trans('backpack::crud.invoice_client.field.item.label') ?? 'PO Items',
            'name'     => $detailsFieldName,
            'type'     => 'closure',
            'width_box' => '100%',
            'function' => function ($entry) {
                $details = $entry->purchase_order_details;
                if (!$details || $details->isEmpty()) {
                    return '<span class="text-muted">-</span>';
                }
                $curr = $entry->currency_code ?? 'IDR';
                $html = '<div class="table-responsive"><table class="table table-sm table-bordered table-striped mt-1 mb-0 w-100">';
                $html .= '<thead class="table-light"><tr><th>Nama Barang</th><th class="text-center" width="80">QTY</th><th class="text-end" width="150">Harga Satuan</th><th class="text-end" width="180">Subtotal</th></tr></thead><tbody>';
                foreach ($details as $d) {
                    $itemPrice = (float) $d->price;
                    $itemQty = (int) $d->qty;
                    $itemSubtotal = $itemPrice * $itemQty;
                    $itemName = $d->device_stock ? ($d->device_stock->name . ' (' . $d->device_stock->code . ')') : ($d->name ?? '-');
                    $html .= '<tr>';
                    $html .= '<td>' . e($itemName) . '</td>';
                    $html .= '<td class="text-center">' . $itemQty . '</td>';
                    $html .= '<td class="text-end">' . CustomHelper::formatCurrency($itemPrice, $curr) . '</td>';
                    $html .= '<td class="text-end fw-bold">' . CustomHelper::formatCurrency($itemSubtotal, $curr) . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody></table></div>';
                return $html;
            },
            'escaped'  => false,
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.proforma_invoice.field.term.label'),
            'name'   => 'term',
            'type'   => 'custom_html',
            'value'  => $this->crud->getCurrentEntry()?->term,
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Contracts\View\View
     */
    public function show($id)
    {
        $this->crud->hasAccessOrFail('show');

        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;

        // get the info for that entry (include softDeleted items if the trait is used)
        if ($this->crud->get('show.softDeletes') && in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses($this->crud->model))) {
            $this->data['entry'] = $this->crud->getModel()->withTrashed()->findOrFail($id);
        } else {
            $this->data['entry'] = $this->crud->getEntryWithLocale($id);
        }

        $this->data['entry_value'] = $this->crud->getRowViews($this->data['entry']);
        $this->data['crud'] = $this->crud;

        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.preview') . ' ' . $this->crud->entity_name;

        // load the view from /resources/views/vendor/backpack/crud/ if it exists, otherwise load the one in the package
        // return view($this->crud->getShowView(), $this->data);
        return response()->json([
            'html' => view($this->crud->getShowView(), $this->data)->render()
        ]);
    }

    public function search()
    {
        $this->crud->hasAccessOrFail('list');

        $this->crud->applyUnappliedFilters();

        $start = (int) request()->input('start');
        $length = (int) request()->input('length');
        $search = request()->input('search');

        // check if length is allowed by developer
        if ($length && ! in_array($length, $this->crud->getPageLengthMenu()[0])) {
            return response()->json([
                'error' => 'Unknown page length.',
            ], 400);
        }

        // if a search term was present
        if ($search && $search['value'] ?? false) {
            // filter the results accordingly
            $this->crud->applySearchTerm($search['value']);
        }
        // start the results according to the datatables pagination
        if ($start) {
            $this->crud->skip($start);
        }
        // limit the number of results according to the datatables pagination
        if ($length) {
            $this->crud->take($length);
        }
        // overwrite any order set in the setup() method with the datatables order
        $this->crud->applyDatatableOrder();

        $entries = $this->crud->getEntries();

        // if show entry count is disabled we use the "simplePagination" technique to move between pages.
        if ($this->crud->getOperationSetting('showEntryCount')) {
            $totalEntryCount = (int) (request()->get('totalEntryCount') ?: $this->crud->getTotalQueryCount());
            $filteredEntryCount = $this->crud->getFilteredQueryCount() ?? $totalEntryCount;
        } else {
            $totalEntryCount = $length;
            $entryCount = $entries->count();
            $filteredEntryCount = $entryCount < $length ? $entryCount : $length + $start + 1;
        }

        // store the totalEntryCount in CrudPanel so that multiple blade files can access it
        $this->crud->setOperationSetting('totalEntryCount', $totalEntryCount);

        return $this->crud->getEntriesAsJsonForDatatables($entries, $totalEntryCount, $filteredEntryCount, $start);
    }

    public function exportExcel(Request $request)
    {
        $name = "document-subkon-po" . now()->format('Ymd_His') . ".xlsx";
        $type = $request->tab;
        $year = $request->filter_year;
        return response()->streamDownload(function () use ($type, $year) {
            echo Excel::raw(new ExportVendorPo($type, $year), \Maatwebsite\Excel\Excel::XLSX);
        }, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Download Failure',
        ], 400);
    }

    public function exportPdf()
    {
        $filters = PurchaseOrderFilterData::fromRequest(request());
        $items = $this->purchaseOrderRepository->getFilteredData($filters)->get();

        $pdf = Pdf::loadView('exports.vendor-po-pdf', compact('items'))->setPaper('A4', 'landscape');
        $fileName = 'vendor_po_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');
        $id = $this->crud->getCurrentEntryId() ?? $id;

        try {
            $this->purchaseOrderService->deletePO($id);

            return response()->json([
                'success' => [trans('backpack::crud.delete_confirmation_message')],
                'events' => [
                    'crudTable-list_all_po_create_success' => true,
                    'crudTable-list_open_create_success' => true,
                    'crudTable-list_close_create_success' => true,
                    'crudTable-filter-purchase_order_plugin_load' => true
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'errors',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function printPo($id)
    {
        $this->crud->hasAccessOrFail('show');
        $entry = $this->crud->getEntry($id);
        $entry->load(['purchase_order_details.device_stock', 'company']);
        $settings = Setting::first();

        $pdf = Pdf::loadView('exports.vendor-po-single-pdf', [
            'entry' => $entry,
            'settings' => $settings,
        ]);

        $fileName = 'PO-Subkon-' . ($entry->po_number ?? $entry->id) . '.pdf';
        $safeFileName = str_replace(['/', '\\'], '-', $fileName);

        return $pdf->stream($safeFileName);
    }

    /**
     * Submit / Post stock for a Supplier Purchase Order.
     */
    public function postStock($id)
    {
        $this->crud->hasAccessOrFail('update');

        try {
            $po = $this->purchaseOrderService->postStock((int) $id);

            return response()->json([
                'success' => true,
                'message' => trans('backpack::crud.po.message.post_stock_success', ['number' => $po->po_number ?? $po->id]) ?: ('Stok PO ' . e($po->po_number ?? $po->id) . ' berhasil diposting ke Master Device Stock & History Layer FIFO!'),
                'data' => $po,
                'events' => [
                    'crudTable-list_all_po_updated_success' => true,
                    'crudTable-list_open_updated_success' => true,
                    'crudTable-list_close_updated_success' => true,
                    'crudTable-filter-purchase_order_plugin_load' => true
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
