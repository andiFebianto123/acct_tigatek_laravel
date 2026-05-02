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

    public function index()
    {
        $this->crud->hasAccessOrFail('list');

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
                            'columns' => [
                                [
                                    'name'      => 'row_number',
                                    'type'      => 'row_number',
                                    'label'     => 'No',
                                    'orderable' => false,
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.po_number'),
                                    'name' => 'po_number',
                                    'type'  => 'text'
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.date_po'),
                                    'name' => 'date_po',
                                    'type'  => 'date'
                                ],
                                [
                                    'label' => trans('backpack::crud.subkon.column.name'),
                                    'type'      => 'select',
                                    'name'      => 'subkon_id',
                                    'orderable' => true,
                                ],
                                [
                                    'label'  => trans('backpack::crud.client_po.field.work_code.label'),
                                    'name' => 'work_code',
                                    'type'  => 'text'
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.job_name'),
                                    'name' => 'job_name',
                                    'type'  => 'text'
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.job_description'),
                                    'name' => 'job_description',
                                    'type'  => 'textarea'
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.job_value'),
                                    'name' => 'job_value',
                                    'type'  => 'number',
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.tax_ppn'),
                                    'name' => 'tax_ppn',
                                    'type'  => 'number',
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.total_value_with_tax'),
                                    'name' => 'total_value_with_tax',
                                    'type'  => 'number-custom',
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.due_date'),
                                    'name' => 'date_po',
                                    'type'  => 'date'
                                ],
                                [
                                    'label' => trans('backpack::crud.po.column.status'),
                                    'name' => 'status',
                                    'type' => 'closure'
                                ],
                                [
                                    'name'   => 'document_path',
                                    'type'   => 'upload',
                                    'label'  => trans('backpack::crud.po.column.document_path'),
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.additional_info'),
                                    'name' => 'additional_info',
                                    'type'  => 'textarea'
                                ],
                                [
                                    'name' => 'action',
                                    'type' => 'action',
                                    'label' =>  trans('backpack::crud.actions'),
                                ],
                            ],
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
                        // 'class' => '',
                        'active' => false,
                        'view' => 'crud::components.datatable-po',
                        'params' => [
                            'filter' => true,
                            'crud_custom' => $this->crud,
                            'columns' => [
                                [
                                    'name'      => 'row_number',
                                    'type'      => 'row_number',
                                    'label'     => 'No',
                                    'orderable' => false,
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.po_number'),
                                    'name' => 'po_number',
                                    'type'  => 'text'
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.date_po'),
                                    'name' => 'date_po',
                                    'type'  => 'date'
                                ],

                                [
                                    'label' => trans('backpack::crud.subkon.column.name'),
                                    'type'      => 'select',
                                    'name'      => 'subkon_id',
                                    'orderable' => true,
                                ],
                                [
                                    'label'  => trans('backpack::crud.client_po.field.work_code.label'),
                                    'name' => 'work_code',
                                    'type'  => 'text'
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.job_name'),
                                    'name' => 'job_name',
                                    'type'  => 'text'
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.job_description'),
                                    'name' => 'job_description',
                                    'type'  => 'textarea'
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.job_value'),
                                    'name' => 'job_value',
                                    'type'  => 'number',
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.tax_ppn'),
                                    'name' => 'tax_ppn',
                                    'type'  => 'number',
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.total_value_with_tax'),
                                    'name' => 'total_value_with_tax',
                                    'type'  => 'number-custom',
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.due_date'),
                                    'name' => 'date_po',
                                    'type'  => 'date'
                                ],
                                [
                                    'label' => trans('backpack::crud.po.column.status'),
                                    'name' => 'status',
                                    'type' => 'closure'
                                ],
                                [
                                    'name'   => 'document_path',
                                    'type'   => 'upload',
                                    'label'  => trans('backpack::crud.po.column.document_path'),
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.additional_info'),
                                    'name' => 'additional_info',
                                    'type'  => 'textarea'
                                ],
                            ],
                            'total_include_ppn' => CustomHelper::formatRupiah(PurchaseOrder::where('status', PurchaseOrder::OPEN)->sum('total_value_with_tax')),
                            'route' => backpack_url('/vendor/purchase-order/search?tab=open'),
                            'route_export_pdf' => backpack_url('/vendor/download-po-pdf?tab=open'),
                            'title_export_pdf' => 'Purchase_order-open.pdf',
                            'route_export_excel' => backpack_url('/vendor/download-po?tab=open'),
                            'title_export_excel' => 'Purchase_order-open.xlsx',
                        ],
                    ],
                    [
                        'name' => 'list_close',
                        'name_tab' => 'po_tab',
                        'label' => trans('backpack::crud.po.tab.close'),
                        // 'class' => '',
                        'active' => false,
                        'view' => 'crud::components.datatable-po',
                        'params' => [
                            'filter' => true,
                            'crud_custom' => $this->crud,
                            'columns' => [
                                [
                                    'name'      => 'row_number',
                                    'type'      => 'row_number',
                                    'label'     => 'No',
                                    'orderable' => false,
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.po_number'),
                                    'name' => 'po_number',
                                    'type'  => 'text'
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.date_po'),
                                    'name' => 'date_po',
                                    'type'  => 'date'

                                ],
                                [
                                    'label' => trans('backpack::crud.subkon.column.name'),
                                    'type'      => 'select',
                                    'name'      => 'subkon_id',
                                    'orderable' => true,
                                ],
                                [
                                    'label'  => trans('backpack::crud.client_po.field.work_code.label'),
                                    'name' => 'work_code',
                                    'type'  => 'text'
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.job_name'),
                                    'name' => 'job_name',
                                    'type'  => 'text'
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.job_description'),
                                    'name' => 'job_description',
                                    'type'  => 'textarea'
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.job_value'),
                                    'name' => 'job_value',
                                    'type'  => 'number',
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.tax_ppn'),
                                    'name' => 'tax_ppn',
                                    'type'  => 'number',
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.total_value_with_tax'),
                                    'name' => 'total_value_with_tax',
                                    'type'  => 'number-custom',
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.due_date'),
                                    'name' => 'date_po',
                                    'type'  => 'date'
                                ],
                                [
                                    'label' => trans('backpack::crud.po.column.status'),
                                    'name' => 'status',
                                    'type' => 'closure'
                                ],
                                [
                                    'name'   => 'document_path',
                                    'type'   => 'upload',
                                    'label'  => trans('backpack::crud.po.column.document_path'),
                                ],
                                [
                                    'label'  => trans('backpack::crud.po.column.additional_info'),
                                    'name' => 'additional_info',
                                    'type'  => 'textarea'
                                ],
                            ],
                            'total_include_ppn' => CustomHelper::formatRupiah(PurchaseOrder::where('status', PurchaseOrder::CLOSE)->sum('total_value_with_tax')),
                            'route' => backpack_url('/vendor/purchase-order/search?tab=close'),
                            'route_export_pdf' => backpack_url('/vendor/download-po-pdf?tab=close'),
                            'title_export_pdf' => 'Purchase_order-close.pdf',
                            'route_export_excel' => backpack_url('/vendor/download-po?tab=close'),
                            'title_export_excel' => 'Purchase_order-close.xlsx',
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

        $app->addColumn(
            [
                'label'  => trans('backpack::crud.po.column.job_description'),
                'name' => 'job_description',
                'type'  => 'wrap_text',
                'limit' => 600,
                'width_box' => '400px',
            ],
        );

        $app->addColumn(
            [
                'label'  => trans('backpack::crud.po.column.job_value'),
                'name' => 'job_value',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        $app->addColumn([
            'label'  => trans('backpack::crud.po.column.tax_ppn'),
            'name' => 'tax_ppn',
            'type'  => 'number',
            'suffix' => '%',
        ]);

        $app->addColumn(
            [
                'label'  => trans('backpack::crud.po.column.total_value_with_tax'),
                'name' => 'total_value_with_tax',
                'type'  => 'number-custom',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
                'function' => function ($entry) {
                    return $entry->job_value + ($entry->job_value * $entry->tax_ppn / 100);
                }
            ],
        );

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
        ]);

        CRUD::addField([   // Hidden
            'name'  => 'space',
            'type'  => 'hidden',
            'value' => 'active',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
            'attributes' => [
                'disabled'  => 'disabled',
                // 'placeholder' => trans('backpack::crud.spk.field.')
            ]
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
            // 'wrapper'   => [
            //     'class' => 'form-group col-md-6'
            // ],
        ]);

        CRUD::addField([
            'name' => 'job_description',
            'label' => trans('backpack::crud.po.field.job_description.label'),
            'type' => 'textarea',
            'attributes' => [
                'placeholder' => trans('backpack::crud.po.field.job_description.placeholder')
            ]
            // 'wrapper'   => [
            //     'class' => 'form-group col-md-6'
            // ],
        ]);


        CRUD::addField([
            'name' => 'job_value',
            'label' => trans('backpack::crud.po.column.job_value'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
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
            'type' => 'number-disable-po',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.spk.field.total_value_with_tax.placeholder'),
            ],
            // optionals
            // 'attributes' => ["step" => "any"], // allow decimals
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

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
    }

    public function select2SubkonId()
    {
        $this->crud->hasAccessOrFail('create');

        $search = request()->input('q');
        $dataset = \App\Models\Subkon::select(['id', 'name'])
            ->where('name', 'LIKE', "%$search%")
            ->paginate(10);

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
        CRUD::field('space')->remove();
        CRUD::field('additional_info')->remove();
        CRUD::field('space_2')->remove();

        // update subkon id
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

        // load entry data
        // $this->setupListOperation();

        // remove row number
        // CRUD::column('row_number')->remove();
        CRUD::column([
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

        CRUD::column(
            [
                'label'  => trans('backpack::crud.po.column.po_number'),
                'name' => 'po_number',
                'type'  => 'text'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.po.column.date_po'),
                'name' => 'date_po',
                'type'  => 'date',
                'format' => $new_format_date,
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.field.work_code.label'),
                'name' => 'work_code',
                'type'  => 'text'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.po.column.job_name'),
                'name' => 'job_name',
                'type'  => 'wrap_text',
                'limit' => 600,
                'width_box' => '350px',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.po.column.job_description'),
                'name' => 'job_description',
                'type'  => 'wrap_text',
                'limit' => 600,
                'width_box' => '400px',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.po.column.job_value'),
                'name' => 'job_value',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column([
            'label'  => trans('backpack::crud.po.column.tax_ppn'),
            'name' => 'tax_ppn',
            'type'  => 'number',
            'suffix' => '%',
        ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.po.column.total_value_with_tax'),
                'name' => 'total_value_with_tax',
                'type'  => 'number-custom',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
                'function' => function ($entry) {
                    return $entry->job_value + ($entry->job_value * $entry->tax_ppn / 100);
                }
            ],
        );

        CRUD::column([
            'label'  => trans('backpack::crud.po.column.due_date'),
            'name' => 'due_date',
            'type'  => 'date',
            'format' => $new_format_date,
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.po.column.status'),
            'name' => 'status',
            'type'  => 'closure',
            'function' => function ($entry) {
                return strtoupper($entry->status);
            }
        ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.po.column.document_path'),
                'name' => 'document_path',
                'type'  => 'text',
                'wrapper'   => [
                    'element' => 'a', // the element will default to "a" so you can skip it here
                    'href' => function ($crud, $column, $entry, $related_key) {
                        if ($entry->document_path != '') {
                            return url("storage/" . $entry->document_path);
                        }
                        return "javascript:void(0)";
                    },
                    'target' => '_blank',
                    // 'class' => 'some-class',
                ],
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.po.column.additional_info'),
                'name' => 'additional_info',
                'type'  => 'textarea'
            ],
        );
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
                'success' => true,
                'message' => trans('backpack::crud.delete_confirmation_message'),
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
}
