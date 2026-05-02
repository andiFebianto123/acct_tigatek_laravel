<?php

namespace App\Http\Controllers\Admin;

use App\Models\Spk;
use App\Models\Setting;
use App\Models\Voucher;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Exports\ExportExcel;
use App\Http\Requests\SpkRequest;
use App\Http\Helpers\CustomHelper;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\PermissionAccess;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

use App\DTOs\SubkonManagement\SpkData;
use App\DTOs\SubkonManagement\SpkFilterData;
use App\Services\SubkonManagement\SpkService;
use App\Repositories\SubkonManagement\SpkRepository;

/**
 * Class SpkCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SpkCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;

    protected $spkService;
    protected $spkRepository;

    public function __construct(
        SpkService $spkService,
        SpkRepository $spkRepository
    ) {
        parent::__construct();
        $this->spkService = $spkService;
        $this->spkRepository = $spkRepository;
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Spk::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/vendor/spk-trans');
        CRUD::setEntityNameStrings('SPK', 'SPK');

        $allAccess = [
            'AKSES SEMUA MENU ACCOUNTING',
            'AKSES MENU VENDOR'
        ];

        $viewMenu = [
            'AKSES SEMUA VIEW ACCOUNTING',
            'AKSES SEMUA MENU ACCOUNTING',
            'AKSES MENU VENDOR',
            'MENU INDEX VENDOR SPK',
        ];

        $this->settingPermission([
            'create' => [
                'CREATE INDEX VENDOR SPK',
                ...$allAccess,
            ],
            'update' => [
                'UPDATE INDEX VENDOR SPK',
                ...$allAccess,
            ],
            'delete' => [
                'DELETE INDEX VENDOR SPK',
                ...$allAccess,
            ],
            'list' => $viewMenu,
            'show' => $viewMenu,
            'print' => true,
        ]);
    }

    public function total_price()
    {
        return $this->spkRepository->getTotalPrices(request()->filter_year);
    }

    public function index()
    {
        $this->crud->hasAccessOrFail('list');

        $this->card->addCard([
            'name' => 'spk_tab',
            'line' => 'top',
            'view' => 'crud::components.card-tab',
            'params' => [
                'tabs' => [
                    [
                        'name' => 'list_all_spk',
                        'label' => trans('backpack::crud.po.tab.title_all_po'),
                        // 'class' => '',
                        'active' => true,
                        'view' => 'crud::components.datatable',
                        'params' => [
                            // 'filter' => false,
                            'crud_custom' => $this->crud,
                            'columns' => [
                                [
                                    'name'      => 'row_number',
                                    'type'      => 'row_number',
                                    'label'     => 'No',
                                    'orderable' => false,
                                ],
                                ...(backpack_user()->hasRole('Super Admin') ? [[
                                    'label' => trans('backpack::crud.subkon.column.company'),
                                    'type'      => 'select',
                                    'name'      => 'company_id',
                                    'entity'    => 'company',
                                    'attribute' => 'name',
                                    'model'     => "App\Models\Company",
                                ]] : []),
                                [
                                    'label'  => trans('backpack::crud.spk.column.no_spk'),
                                    'name' => 'no_spk',
                                    'type'  => 'text'
                                ],
                                [
                                    'label'  => trans('backpack::crud.spk.column.date_spk'),
                                    'name' => 'date_spk',
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
                                    'name' => 'due_date',
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
                            'route' => backpack_url('/vendor/spk-trans/search?tab=list_all_po'),
                            'route_export_pdf' => backpack_url('/vendor/spk-trans/export-pdf?tab=list_all_spk'),
                            'title_export_pdf' => 'Spk.pdf',
                            'route_export_excel' => backpack_url('/vendor/spk-trans/export-excel?tab=list_all_spk'),
                            'title_export_excel' => 'Spk.xlsx',
                        ],
                    ],
                    [
                        'name' => 'list_open',
                        'label' => trans('backpack::crud.po.tab.open'),
                        // 'class' => '',
                        'active' => false,
                        'view' => 'crud::components.datatable',
                        'params' => [
                            // 'filter' => false,
                            'crud_custom' => $this->crud,
                            'columns' => [
                                [
                                    'name'      => 'row_number',
                                    'type'      => 'row_number',
                                    'label'     => 'No',
                                    'orderable' => false,
                                ],
                                ...(backpack_user()->hasRole('Super Admin') ? [[
                                    'label' => trans('backpack::crud.subkon.column.company'),
                                    'type'      => 'select',
                                    'name'      => 'company_id',
                                    'entity'    => 'company',
                                    'attribute' => 'name',
                                    'model'     => "App\Models\Company",
                                ]] : []),
                                [
                                    'label'  => trans('backpack::crud.spk.column.no_spk'),
                                    'name' => 'no_spk',
                                    'type'  => 'text'
                                ],
                                [
                                    'label'  => trans('backpack::crud.spk.column.date_spk'),
                                    'name' => 'date_spk',
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
                                    'name' => 'due_date',
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
                            'total_include_ppn' => CustomHelper::formatRupiah(Spk::where('status', Spk::OPEN)->sum('total_value_with_tax')),
                            'route' => backpack_url('/vendor/spk-trans/search?tab=open'),
                            'route_export_pdf' => backpack_url('/vendor/spk-trans/export-pdf?tab=open'),
                            'title_export_pdf' => 'Spk-open.pdf',
                            'route_export_excel' => backpack_url('/vendor/spk-trans/export-excel?tab=open'),
                            'title_export_excel' => 'Spk-open.xlsx',
                        ],
                    ],
                    [
                        'name' => 'list_close',
                        'label' => trans('backpack::crud.po.tab.close'),
                        // 'class' => '',
                        'active' => false,
                        'view' => 'crud::components.datatable',
                        'params' => [
                            // 'filter' => false,
                            'crud_custom' => $this->crud,
                            'columns' => [
                                [
                                    'name'      => 'row_number',
                                    'type'      => 'row_number',
                                    'label'     => 'No',
                                    'orderable' => false,
                                ],
                                ...(backpack_user()->hasRole('Super Admin') ? [[
                                    'label' => trans('backpack::crud.subkon.column.company'),
                                    'type'      => 'select',
                                    'name'      => 'company_id',
                                    'entity'    => 'company',
                                    'attribute' => 'name',
                                    'model'     => "App\Models\Company",
                                ]] : []),
                                [
                                    'label'  => trans('backpack::crud.spk.column.no_spk'),
                                    'name' => 'no_spk',
                                    'type'  => 'text'
                                ],
                                [
                                    'label'  => trans('backpack::crud.spk.column.date_spk'),
                                    'name' => 'date_spk',
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
                                    'name' => 'due_date',
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
                            'total_include_ppn' => CustomHelper::formatRupiah(Spk::where('status', Spk::CLOSE)->sum('total_value_with_tax')),
                            'route' => backpack_url('/vendor/spk-trans/search?tab=close'),
                            'route_export_pdf' => backpack_url('/vendor/spk-trans/export-pdf?tab=close'),
                            'title_export_pdf' => 'Spk-close.pdf',
                            'route_export_excel' => backpack_url('/vendor/spk-trans/export-excel?tab=close'),
                            'title_export_excel' => 'Spk-close.xlsx',
                        ],
                    ]
                ]
            ]
        ]);

        $this->card->addCard([
            'name' => 'spk-plugin',
            'line' => 'top',
            'view' => 'crud::components.spk-plugin',
            'parent_view' => 'crud::components.filter-parent',
            'params' => [],
        ]);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = "SPK vendor (Subkon)";
        $this->data['title_modal_edit'] = "SPK Vendor (Subkon)";
        $this->data['title_modal_delete'] = "SPK Vendor (Subkon)";
        $this->data['cards'] = $this->card;

        $breadcrumbs = [
            'Vendor (Subkon)' => backpack_url('vendor'),
            trans($this->data['title']) => backpack_url($this->crud->route)
        ];

        $this->data['breadcrumbs'] = $breadcrumbs;

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
            $data = SpkData::fromRequest($request);
            $item = $this->spkService->createSpk($data);

            \Alert::success(trans('backpack::crud.insert_success'))->flash();


            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
                    'events' => $this->spkService->getUIEvents($item, 'create'),
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
            $data = SpkData::fromRequest($request);
            $item = $this->spkService->updateSpk($request->get($this->crud->model->getKeyName()), $data);

            \Alert::success(trans('backpack::crud.update_success'))->flash();
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
                    'events' => $this->spkService->getUIEvents($item, 'update'),
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

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        // CRUD::setFromDb(); // set columns from db columns.
        $settings = Setting::first();

        // $this->crud->file_title_export_pdf = "Laporan_daftar_spk.pdf";
        // $this->crud->file_title_export_excel = "Laporan_daftar_spk.xlsx";
        // $this->crud->param_uri_export = "?export=1";

        CRUD::addButtonFromView('top', 'export-excel-table', 'export-excel-table', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-table', 'export-pdf-table', 'beginning');
        CRUD::addButtonFromView('top', 'filter_year', 'filter-year-spk', 'beginning');

        CRUD::disableResponsiveTable();

        $new_format_date = 'DD/MM/YYYY';

        $request = request();

        $filters = SpkFilterData::fromRequest($request);
        $this->crud->query = $this->spkRepository->getFilteredData($filters);

        $this->crud->addColumn([
            'name'      => 'row_number',
            'type'      => 'row_number',
            'label'     => 'No',
            'orderable' => false,
            'wrapper' => [
                'element' => 'strong',
            ]
        ])->makeFirstColumn();

        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::column([
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'select',
                'name'      => 'company_id',
                'entity'    => 'company',
                'attribute' => 'name',
                'model'     => "App\Models\Company",
            ]);
        }

        CRUD::column(
            [
                'label'  => trans('backpack::crud.spk.column.no_spk'),
                'name' => 'no_spk',
                'type'  => 'text'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.spk.column.date_spk'),
                'name' => 'date_spk',
                'type'  => 'date',
                'format' => $new_format_date,
            ],
        );

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

        CRUD::addColumn(
            [
                'label'  => trans('backpack::crud.client_po.field.work_code.label'),
                'name' => 'work_code',
                'type'  => 'text'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.spk.column.job_name'),
                'name' => 'job_name',
                'type'  => 'wrap_text'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.spk.column.job_description'),
                'name' => 'job_description',
                'type'  => 'textarea'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.spk.column.job_value'),
                'name' => 'job_value',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column([
            'label'  => trans('backpack::crud.spk.column.tax_ppn'),
            'name' => 'tax_ppn',
            'type'  => 'number',
            'suffix' => '%',
        ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.spk.column.total_value_with_tax'),
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

        CRUD::addColumn([
            'label'  => trans('backpack::crud.po.column.due_date'),
            'name' => 'due_date',
            'type'  => 'date',
            'format' => $new_format_date,
        ]);

        CRUD::addColumn([
            'label'  => trans('backpack::crud.spk.column.status'),
            'name' => 'status',
            'type'  => 'closure',
            'function' => function ($entry) {
                return strtoupper($entry->status);
            }
        ]);

        CRUD::column([
            'name'   => 'document_path',
            'type'   => 'upload',
            'label'  => trans('backpack::crud.spk.column.document_path'),
            'disk'   => 'public',
        ]);

        CRUD::addColumn(
            [
                'label'  => trans('backpack::crud.po.column.additional_info'),
                'name' => 'additional_info',
                'type'  => 'textarea'
            ],
        );
    }

    private function setupListExport()
    {

        $request = request();

        if ($request->has('filter_year')) {
            if ($request->filter_year != 'all') {
                $filterYear = $request->filter_year;
                $this->crud->query = $this->crud->query
                    ->where(DB::raw("YEAR(date_spk)"), $filterYear);
            }
        }

        $this->crud->addColumn([
            'name'      => 'row_number',
            'type'      => 'export',
            'label'     => 'No',
            'orderable' => false,
            'wrapper' => [
                'element' => 'strong',
            ]
        ])->makeFirstColumn();

        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::column([
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'closure',
                'name'      => 'company_id',
                'function'  => function ($entry) {
                    return $entry->company?->name;
                }
            ]);
        }

        CRUD::column(
            [
                'label'  => trans('backpack::crud.spk.column.no_spk'),
                'name' => 'no_spk',
                'type'  => 'export'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.spk.column.date_spk'),
                'name' => 'date_spk',
                'type'  => 'export'
            ],
        );

        CRUD::column([
            // 1-n relationship
            'label' => trans('backpack::crud.subkon.column.name'),
            'type'      => 'closure',
            'name'      => 'subkon_id', // the column that contains the ID of that connected entity;
            'function' => function ($entry) {
                return $entry->subkon->name;
            }
            // OPTIONAL
            // 'limit' => 32, // Limit the number of characters shown
        ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.spk.column.job_name'),
                'name' => 'job_name',
                'type'  => 'export'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.spk.column.job_description'),
                'name' => 'job_description',
                'type'  => 'export'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.spk.column.job_value'),
                'name' => 'job_value',
                'type'  => 'export',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column([
            'label'  => trans('backpack::crud.spk.column.tax_ppn'),
            'name' => 'tax_ppn',
            'type'  => 'export',
            'suffix' => '%',
        ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.spk.column.total_value_with_tax'),
                'name' => 'total_value_with_tax',
                'type'  => 'closure',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
                'function' => function ($entry) {
                    return $entry->job_value + ($entry->job_value * $entry->tax_ppn / 100);
                }
            ],
        );
    }

    public function exportPdf()
    {
        $this->setupListOperation();
        $filters = SpkFilterData::fromRequest(request());
        $items = $this->spkRepository->getFilteredData($filters)->get();

        $columns = $this->crud->columns();
        $row_number = 0;
        $all_items = [];

        foreach ($items as $item) {
            $row_items = [];
            $row_number++;
            foreach ($columns as $column) {
                $item_value = ($column['name'] == 'row_number') ? $row_number : $this->crud->getCellView($column, $item, $row_number);
                $item_value = CustomHelper::clean_html(strip_tags($item_value));
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $pdf = Pdf::loadView('exports.table-pdf', [
            'columns' => $columns,
            'items' => $all_items,
            'title' => "DAFTAR SPK"
        ])->setPaper('A4', 'landscape');

        $fileName = 'vendor_spk_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportExcel()
    {
        $this->setupListOperation();
        $filters = SpkFilterData::fromRequest(request());
        $items = $this->spkRepository->getFilteredData($filters)->get();

        $columns = $this->crud->columns();
        $row_number = 0;
        $all_items = [];

        foreach ($items as $item) {
            $row_items = [];
            $row_number++;
            foreach ($columns as $column) {
                $item_value = ($column['name'] == 'row_number') ? $row_number : $this->crud->getCellView($column, $item, $row_number);
                $item_value = CustomHelper::clean_html(strip_tags($item_value));
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $name = 'DAFTAR_SPK_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($columns, $all_items) {
            echo Excel::raw(new ExportExcel($columns, $all_items), \Maatwebsite\Excel\Excel::XLSX);
        }, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');
        $id = $this->crud->getCurrentEntryId() ?? $id;

        try {
            // Check if there are related vouchers before deleting
            if ($this->spkRepository->hasVoucher($id)) {
                throw new \Exception("Cannot delete SPK because it has associated vouchers.");
            }

            $this->spkService->deleteSpk($id);
            return response()->json([
                'success' => true,
                'message' => trans('backpack::crud.delete_confirmation_message')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'errors',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(SpkRequest::class);
        $settings = Setting::first();

        $spk_prefix = [];
        $work_code_prefix = [];
        $work_code_disable = [];
        if (!$this->crud->getCurrentEntryId()) {
            if ($settings?->spk_prefix) {
                $spk_prefix = [
                    'value' => $settings->spk_prefix,
                ];
            }
            if ($settings?->work_code_prefix) {
                $work_code_prefix = [
                    'value' => $settings->work_code_prefix,
                ];
            }
            $work_code_disable = [];
        } else {
            $id = $this->crud->getCurrentEntryId();
            $voucher_exists = Voucher::where('reference_type', Spk::class)
                ->where('reference_id', $id)->first();
            if ($voucher_exists) {
                $work_code_disable = [
                    'disabled' => true,
                ];
            }
        }

        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::addField([
                'name'      => 'company_id',
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'select2_array',
                'options'   => \App\Models\Company::all()->pluck('name', 'id')->toArray(),
                'allows_null' => false,
                'wrapper'   => ['class' => 'form-group col-md-6'],
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
        }



        // CRUD::setFromDb(); // set fields from db columns.
        CRUD::field([   // 1-n relationship
            'label'       => trans('backpack::crud.subkon.column.name'), // Table column heading
            'type'        => "select2_ajax_custom",
            'name'        => 'subkon_id', // the column that contains the ID of that connected entity
            'entity'      => 'subkon', // the method that defines the relationship in your Model
            'attribute'   => "name", // foreign key attribute that is shown to user
            'data_source' => backpack_url('vendor/select2-subkon-id'), // url to controller search function (with /{id} should return a single entry)
            // 'attributes' => [
            //     'disabled'  => 'disabled',
            //     'placeholder' => trans('backpack::crud.spk.field.subkon_id.placeholder')
            // ],
            'placeholder' => trans('backpack::crud.spk.field.subkon_id.placeholder'),
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
            'dependencies' => ['company_id'],
            'include_all_form_fields' => true,
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
            'name' => 'no_spk',
            'label' => trans('backpack::crud.spk.column.no_spk'),
            'type' => 'text',
            'attributes' => [
                'placeholder' => trans('backpack::crud.spk.field.no_spk.placeholder'),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            ...$spk_prefix,
        ]);

        CRUD::addField([
            'name' => 'date_spk',
            'label' => trans('backpack::crud.spk.column.date_spk'),
            'type' => 'date',
            'attributes' => [
                'placeholder' => trans('backpack::crud.spk.field.date_spk.placeholder'),
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
            'attributes' => [
                'placeholder' => trans('backpack::crud.spk.field.job_name.placeholder'),
            ],
            // 'wrapper'   => [
            //     'class' => 'form-group col-md-6'
            // ],
        ]);

        CRUD::addField([
            'name' => 'job_description',
            'label' => trans('backpack::crud.spk.field.job_description.label'),
            'type' => 'textarea',
            'attributes' => [
                'placeholder' => trans('backpack::crud.spk.field.job_description.placeholder'),
            ],
            // 'wrapper'   => [
            //     'class' => 'form-group col-md-6'
            // ],
        ]);

        // CRUD::addField([
        //     'name' => 'job_value',
        //     'label' => trans('backpack::crud.spk.column.job_value'),
        //     'type' => 'number',
        //       // optionals
        //     'attributes' => [
        //         "step" => "any",
        //         'placeholder' => trans('backpack::crud.spk.field.job_value.placeholder'),
        //     ], // allow decimals
        //     'prefix'     => "Rp.",
        //     'wrapper'   => [
        //         'class' => 'form-group col-md-6'
        //     ],
        // ]);

        CRUD::addField([
            'name' => 'job_value',
            'label' => trans('backpack::crud.spk.column.job_value'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.spk.field.job_value.placeholder'),
            ],
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp",
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'tax_ppn',
            'label' => trans('backpack::crud.spk.column.tax_ppn'),
            'type' => 'number',
            // optionals
            'attributes' => [
                "step" => "any",
                "placeholder" => trans('backpack::crud.spk.field.tax_ppn.placeholder'),
            ], // allow decimals
            'prefix'     => "%",
            // 'suffix'     => ".00",
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
            'value' => 0,
        ]);

        CRUD::addField([
            'name' => 'total_value_with_tax',
            'label' => trans('backpack::crud.po.column.total_value_with_tax'),
            'type' => 'number-disable-po',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            // optionals
            'attributes' => [
                'placeholder' => trans('backpack::crud.spk.field.total_value_with_tax.placeholder'),
            ], // allow decimals
            'prefix'     => "Rp.",
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
            'label'       => trans('backpack::crud.spk.field.status.label'),
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
        ]);

        // CRUD::addField([
        //     'name' => 'document_path',
        //     'label' => trans('backpack::crud.spk.field.document_path.label'),
        //     'type' => 'upload',
        //     'wrapper'   => [
        //         'class' => 'form-group col-md-6'
        //     ],
        //     'withFiles' => [
        //         'disk' => 'public',
        //         'path' => 'document_spk',
        //         'deleteWhenEntryIsDeleted' => true,
        //     ],
        // ]);

        CRUD::addField([
            'name' => 'document_path',
            'label' => trans('backpack::crud.spk.field.document_path.label'),
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
            // 'wrapper'   => [
            //     'class' => 'form-group col-md-6'
            // ],
        ]);

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
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
        CRUD::field('tax_ppn')->remove();
        CRUD::addField([
            'name' => 'tax_ppn',
            'label' => trans('backpack::crud.spk.column.tax_ppn'),
            'type' => 'number',
            // optionals
            'attributes' => [
                "step" => "any",
                "placeholder" => trans('backpack::crud.spk.field.tax_ppn.placeholder'),
            ], // allow decimals
            'prefix'     => "%",
            // 'suffix'     => ".00",
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);
        CRUD::field('tax_ppn')->after('job_value');
    }

    protected function setupShowOperation()
    {
        $this->setupCreateOperation();

        // update field hidden
        CRUD::field('space')->remove();
        CRUD::field('additional_info')->remove();
        CRUD::field('space_2')->remove();
        CRUD::field('space_0')->remove();
        CRUD::field('company_id')->remove();

        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::field([
                'name'      => 'company',
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'select',
                'entity'    => 'company',
                'attribute' => 'name',
                'model'     => "App\Models\Company",
                'wrapper'   => ['class' => 'form-group col-md-12'],
            ])->before('subkon_id');
        }

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
        ])->before('no_spk');
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
        $this->setupListOperation();

        // remove row number
        CRUD::column('row_number')->remove();

        // update document path
        CRUD::column('document_path')->remove();
        CRUD::column('additional_info')->remove();
        CRUD::column(
            [
                'label'  => trans('backpack::crud.po.column.document_path'),
                'name' => 'document_path',
                'type'  => 'text',
                'wrapper'   => [
                    'element' => 'a', // the element will default to "a" so you can skip it here
                    'href' => function ($crud, $column, $entry, $related_key) {
                        if ($entry->document_path != '') {
                            return url('storage/' . $entry->document_path);
                        }
                        return "javascript:void(0)";
                    },
                    'target' => '_blank',
                    // 'class' => 'some-class',
                ],
            ],
        );

        // update date_po
        CRUD::column('date_spk')->remove();
        CRUD::column([
            'name' => 'date_spk',
            'label' => trans('backpack::crud.po.column.date_spk'),
            'type' => 'date',
            'format' => 'DD/MM/YYYY',
        ])->after('no_spk');
        CRUD::column(
            [
                'label'  => trans('backpack::crud.po.column.additional_info'),
                'name' => 'additional_info',
                'type'  => 'textarea'
            ],
        )->after('document_path');

        CRUD::column('subkon_id')->before('no_spk');
        CRUD::column('no_spk')->after('subkon_id');
    }

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
}
