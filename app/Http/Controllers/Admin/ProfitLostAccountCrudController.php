<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Account;
use App\Models\Setting;
use App\Models\Voucher;
use App\Models\ClientPo;
use App\Models\JournalEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Exports\ExportExcel;
use App\Models\ProjectProfitLost;
use App\Http\Helpers\CustomHelper;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Exports\ProfitLostExcel;
use App\Models\ConsolidateIncomeItem;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\FormaterExport;
use App\Http\Exports\ExportProfitLostConsolidation;
use App\Http\Controllers\Operation\PermissionAccess;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\DTOs\ProfitLost\ProfitLostFilterData;
use App\DTOs\ProfitLost\ProjectProfitLostSaveData;
use App\DTOs\ProfitLost\ConsolidateItemSaveData;
use App\Repositories\ProfitLost\ProfitLostRepository;
use App\Services\ProfitLost\ProfitLostService;
use App\Http\Requests\ProfitLost\ProfitLostRequest;

class ProfitLostAccountCrudController extends CrudController
{
    protected ProfitLostRepository $repository;
    protected ProfitLostService $service;

    public function __construct(ProfitLostRepository $repository, ProfitLostService $service)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->service = $service;
    }

    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use PermissionAccess;
    use FormaterExport;

    public function setup()
    {
        CRUD::setModel(Account::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/finance-report/profit-lost');
        CRUD::setEntityNameStrings(trans('backpack::crud.profit_lost.title_header'), trans('backpack::crud.profit_lost.title_header'));

        $base = 'INDEX LAPORAN KEUANGAN LABA RUGI';
        $allAccess = ['AKSES SEMUA MENU ACCOUNTING'];
        $viewMenu  = ["MENU $base"];

        $this->settingPermission([
            'create' => ["CREATE $base", ...$allAccess],
            'update' => ["UPDATE $base", ...$allAccess],
            'delete' => ["DELETE $base", ...$allAccess],
            'list'   => $viewMenu,
            'show'   => $viewMenu,
            'print'  => true,
        ]);
    }

    public function total_report_account_profit_lost_ajax()
    {
        $dto = ProfitLostFilterData::fromRequest(request());
        return $this->repository->getConsolidatedFormula($dto);
    }

    public function listCardComponents($type)
    {
        $this->card->addCard([
            'name' => 'report_profit_lost',
            'line' => 'top',
            'view' => 'crud::components.card-report-account-profit',
            'params' => [
                'crud' => $this->crud,
                'route' => url($this->crud->route . '/report-total'),
            ]
        ]);

        $this->crud->filter('category77crudTable-project')
            ->label('Kategori')
            ->type('select2')
            ->values([
                'RUTIN' => 'RUTIN',
                'NON RUTIN' => 'NON RUTIN',
            ]);

        $this->card->addCard([
            'name' => 'project',
            'line' => 'bottom',
            'view' => 'crud::components.datatable-origin',
            'params' => [
                'title' => trans('backpack::crud.profit_lost.project_income_statement'),
                'crud_custom' => $this->crud,
                'columns' => [
                    [
                        'name'      => 'row_number',
                        'type'      => 'row_number',
                        'label'     => 'No',
                        'orderable' => false,
                    ],
                    [
                        'label' => trans('backpack::crud.profit_lost.column.client_po_id'),
                        'type'      => 'select',
                        'name'      => 'client_po_id',
                        'orderable' => true,
                    ],
                    [
                        'label'  => trans('backpack::crud.client_po.column.reimburse_type'),
                        'type'      => 'text',
                        'name'      => 'reimburse_type',
                        'orderable' => true,
                    ],
                    [
                        'label'  => trans('backpack::crud.client_po.column.work_code'),
                        'type'      => 'text',
                        'name'      => 'work_code',
                        'orderable' => true,
                    ],
                    [
                        'label'  => trans('backpack::crud.client_po.column.po_number'),
                        'type'      => 'text',
                        'name'      => 'po_number',
                        'orderable' => true,
                    ],
                    [
                        'label'  => trans('backpack::crud.client_po.column.job_name'),
                        'type'      => 'text',
                        'name'      => 'job_name',
                        'orderable' => true,
                    ],
                    [
                        'label'  => trans('backpack::crud.client_po.column.job_value_exclude_ppn'),
                        'type'      => 'text',
                        'name'      => 'job_value',
                        'orderable' => true,
                    ],
                    [
                        'label'  => trans('backpack::crud.client_po.column.job_value_include_ppn'),
                        'type'      => 'text',
                        'name'      => 'job_value_include_ppn',
                        'orderable' => true,
                    ],
                    [
                        'label'  => trans('backpack::crud.client_po.column.price_after_year'),
                        'type'      => 'text',
                        'name'      => 'price_after_year',
                        'orderable' => true,
                    ],
                    [
                        'label'  => trans('backpack::crud.profit_lost.column.price_voucher'),
                        'type'      => 'text',
                        'name'      => 'price_voucher',
                        'orderable' => true,
                    ],
                    [
                        'label'  => trans('backpack::crud.profit_lost.column.price_small_cash'),
                        'type'      => 'text',
                        'name'      => 'price_small_cash',
                        'orderable' => true,
                    ],
                    [
                        'label'  => trans('backpack::crud.profit_lost.column.price_total'),
                        'type'      => 'text',
                        'name'      => 'price_total',
                        'orderable' => true,
                    ],
                    [
                        'label'  => trans('backpack::crud.profit_lost.column.profit_lost_po'),
                        'type'      => 'text',
                        'name'      => 'profit_lost_po',
                        'orderable' => true,
                    ],
                    [
                        'label'  => trans('backpack::crud.profit_lost.column.load_general_value'),
                        'type'      => 'text',
                        'name'      => 'load_general_value',
                        'orderable' => true,
                    ],
                    [
                        'label'  => trans('backpack::crud.profit_lost.column.profit_lost_final'),
                        'type'      => 'text',
                        'name'      => 'profit_lost_final',
                        'orderable' => true,
                    ],
                    [
                        'label'  => trans('backpack::crud.profit_lost.column.category'),
                        'type'      => 'text',
                        'name'      => 'category',
                        'orderable' => true,
                    ],
                    [
                        'label' => trans('backpack::crud.profit_lost.column.invoice_date'),
                        'type' => 'date-multiple',
                        'name' => 'invoice_date',
                        'format' => 'DD/MM/YYYY',
                        'orderable' => true,
                    ],
                    [
                        'name' => 'action',
                        'type' => 'action',
                        'label' =>  trans('backpack::crud.actions'),
                    ]
                ],
                'filter_table' => collect($this->crud->filters())->slice(0, 2),
                'route' => url($this->crud->route . '/search?type=project'),
            ]
        ]);

        $this->card->addCard([
            'name' => 'profit-lost-plugin',
            'line' => 'top',
            'view' => 'crud::components.profit-lost-plugin',
            'parent_view' => 'crud::components.filter-parent',
            'params' => [],
        ]);
    }

    public function get_total_excl_ppn_final_profit()
    {
        $dto = ProfitLostFilterData::fromRequest(request());
        $result = $this->repository->getProjectProfitLostTotals($dto);
        return response()->json($result);
    }

    public function select2Account()
    {
        $search = request()->input('q');
        $results = $this->repository->getSelect2Accounts($search);
        return response()->json(['results' => $results]);
    }

    public function index()
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['is_disabled_list'] = true;

        $this->listCardComponents(Account::INCOME);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.profit_lost.title_modal_create');
        $this->data['title_modal_edit'] = trans('backpack::crud.profit_lost.title_modal_edit_consolidation');
        $this->data['title_modal_delete'] = trans('backpack::crud.profit_lost.title_modal_delete_consolidation');

        $breadcrumbs = [
            trans('backpack::crud.menu.finance_report') => backpack_url('cash-flow'),
            trans('backpack::crud.menu.profit_lost') => backpack_url($this->crud->route)
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;

        $this->data['cards'] = $this->card;
        $this->data['modals'] = $this->modal;
        $this->data['scripts'] = $this->script;
        $list = "crud::list-blank" ?? $this->crud->getListView();
        $this->data['year_options'] = CustomHelper::getYearOptions('journal_entries', 'date');

        return view($list, $this->data);
    }

    public function total_detail_project($id, $pure = 0)
    {
        $filter_year = request()->get('filter_year');
        return $this->repository->getProjectDetail((int) $id, $filter_year, (bool) $pure);
    }

    public function consolidate_formula()
    {
        $dto = ProfitLostFilterData::fromRequest(request());
        return $this->repository->getConsolidatedFormula($dto);
    }

    public function detail($id)
    {
        $this->crud->hasAccessOrFail('list');
        $this->data['is_disabled_list'] = true;
        $profitLost = ProjectProfitLost::where('id', $id)->first();

        $this->crud->id_profit_lost = $id;

        CRUD::addButtonFromView('top', 'export-excel-profit-lost', 'export-excel-profit-lost', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-profit-lost', 'export-pdf-profit-lost', 'beginning');

        $breadcrumbs = [
            trans('backpack::crud.menu.finance_report') => backpack_url('cash-flow'),
            trans('backpack::crud.menu.profit_lost') => url($this->crud->route),
            $profitLost->clientPo->po_number =>  $profitLost->clientPo->po_number,
        ];
        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.profit_lost.title_modal_create');
        $this->data['title_modal_edit'] = trans('backpack::crud.profit_lost.title_modal_edit_consolidation');
        $this->data['title_modal_delete'] = trans('backpack::crud.profit_lost.title_modal_delete_consolidation');

        $this->card->addCard([
            'name' => 'detail-project',
            'line' => 'top',
            'view' => 'crud::components.detail-project-profit-lost',
            'params' => [
                'data' => $profitLost,
                'report' => $this->total_detail_project($id),
            ],
            'wrapper' => [
                'class' => 'col-md-6'
            ]
        ]);

        $this->data['breadcrumbs'] = $breadcrumbs;
        $this->data['cards'] = $this->card;
        $this->data['modals'] = $this->modal;
        $this->data['scripts'] = $this->script;
        $this->data['id_profit_lost'] = $id;

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

    public function edit($id)
    {
        $this->crud->hasAccessOrFail('update');

        $id = $this->crud->getCurrentEntryId() ?? $id;

        $this->crud->registerFieldEvents();

        $this->data['entry'] = $this->crud->getEntryWithLocale($id);

        $this->crud->entry = $this->data['entry'];
        $client_po = $this->data['entry']->clientPo;

        $this->crud->entry->po_number = $client_po->po_number;

        $this->crud->setOperationSetting('fields', $this->crud->getUpdateFields());

        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit') . ' ' . $this->crud->entity_name;
        $this->data['id'] = $id;

        return response()->json([
            'html' => view($this->crud->getEditView(), $this->data)->render()
        ]);
    }

    public function select2Po()
    {
        $q = request()->q;
        $results = $this->repository->getSelect2Po($q);
        return response()->json(['results' => $results]);
    }

    public function get_client_selected_ajax()
    {
        $id = request()->id;
        $result = $this->repository->getClientPoSelectedData((int) $id);
        return response()->json($result);
    }

    protected function setupCreateOperation()
    {
        $request = request();
        $settings = Setting::first();
        
        $job_code_prefix_value = [];
        $price_voucher_attribute = [];
        $price_small_cash_attribute = [];
        $category_attribute = [];

        if (!$this->crud->getCurrentEntryId()) {
            $job_code_prefix_value = [
                'value' => $settings?->work_code_prefix,
            ];
        } else {
            $job_code_prefix_value = ['disabled' => true];
            $price_voucher_attribute = ['disabled' => true];
            $category_attribute = ['disabled' => true];
        }

        $readonly = ['disabled' => true];

        if ($request->has('type') && $request->type == 'project') {
            CRUD::setValidation(ProfitLostRequest::class);
            CRUD::setModel(ProjectProfitLost::class);

            if (backpack_user()->hasRole('Super Admin')) {
                $companies = \App\Models\Company::pluck('name', 'id')->toArray();
                CRUD::addField([
                    'label'     => trans('backpack::crud.subkon.column.company') ?? 'Company',
                    'type'      => 'select2_array',
                    'name'      => 'company_id',
                    'options'   => ['' => trans('backpack::crud.filter.all_company') ?? 'All (Semua Perusahaan)'] + $companies,
                    'wrapper'   => [
                        'class' => 'form-group col-md-12',
                    ],
                ]);
            }

            CRUD::addField([
                'name' => 'work_code',
                'label' => trans('backpack::crud.profit_lost.fields.job_code.label'),
                'type' => 'select2_ajax_custom',
                'attribute' => 'work_code',
                'entity' => 'clientPo',
                'data_source' => backpack_url('finance-report/profit-lost/select2-po'),
                'wrapper' => ['class' => 'form-group col-md-6'],
                'attributes' => $job_code_prefix_value,
                'dependencies' => ['company_id'],
                'include_all_form_fields' => true,
            ]);

            CRUD::addField([
                'label'       => trans('backpack::crud.invoice_client.field.client_po_id.label'),
                'type'        => "text",
                'name'        => 'po_number',
                'wrapper'   => ['class' => 'form-group col-md-6'],
                'attributes' => [
                    'placeholder' => trans('backpack::crud.profit_lost.fields.no_po.placeholder'),
                    'disabled' => true,
                ],
            ]);

            CRUD::addField([
                'name' => 'price_after_year',
                'label' =>  trans('backpack::crud.profit_lost.fields.price_after_year.label'),
                'type' => 'mask',
                'mask' => '000.000.000.000.000.000',
                'mask_options' => ['reverse' => true],
                'prefix' => $settings?->currency_symbol ?: 'Rp.',
                'wrapper'   => ['class' => 'form-group col-md-6'],
                'attributes' => ['placeholder' => '000.000']
            ]);

            CRUD::addField([
                'name' => 'price_voucher',
                'label' =>  trans('backpack::crud.profit_lost.fields.price_voucher.label'),
                'type' => 'mask',
                'mask' => '000.000.000.000.000.000',
                'mask_options' => ['reverse' => true],
                'prefix' => $settings?->currency_symbol ?: 'Rp.',
                'wrapper'   => ['class' => 'form-group col-md-6'],
                'attributes' => array_merge(['placeholder' => '000.000'], $price_voucher_attribute)
            ]);

            CRUD::addField([
                'name' => 'price_small_cash',
                'label' =>  trans('backpack::crud.profit_lost.fields.price_small_cash.label'),
                'type' => 'mask',
                'mask' => '000.000.000.000.000.000',
                'mask_options' => ['reverse' => true],
                'prefix' => $settings?->currency_symbol ?: 'Rp.',
                'wrapper'   => ['class' => 'form-group col-md-6'],
                'attributes' => array_merge(['placeholder' => '000.000'], $price_small_cash_attribute)
            ]);

            CRUD::addField([
                'name' => 'price_total',
                'label' =>  trans('backpack::crud.profit_lost.fields.price_total.label'),
                'type' => 'text',
                'mask' => '000.000.000.000.000.000',
                'mask_options' => ['reverse' => true],
                'prefix' => $settings?->currency_symbol ?: 'Rp.',
                'wrapper'   => ['class' => 'form-group col-md-6'],
                'attributes' => array_merge(['placeholder' => '000.000'], $readonly)
            ]);

            CRUD::addField([
                'name' => 'price_profit_lost_po',
                'label' =>  trans('backpack::crud.profit_lost.fields.price_profit_lost_po.label'),
                'type' => 'text',
                'mask' => 'Z000.000.000.000.000.000',
                'mask_options' => ['reverse' => true],
                'prefix' => $settings?->currency_symbol ?: 'Rp.',
                'wrapper'   => ['class' => 'form-group col-md-6'],
                'attributes' => array_merge(['placeholder' => '000.000'], $readonly)
            ]);

            CRUD::addField([
                'name' => 'price_general',
                'label' =>  trans('backpack::crud.profit_lost.fields.price_general.label'),
                'type' => 'mask',
                'mask' => '000.000.000.000.000.000',
                'mask_options' => ['reverse' => true],
                'prefix' => $settings?->currency_symbol ?: 'Rp.',
                'wrapper'   => ['class' => 'form-group col-md-6'],
                'attributes' => ['placeholder' => '000.000']
            ]);

            CRUD::addField([
                'name' => 'price_prift_lost_final',
                'label' =>  trans('backpack::crud.profit_lost.fields.price_prift_lost_final.label'),
                'type' => 'text',
                'mask' => 'Z000.000.000.000.000.000',
                'mask_options' => ['reverse' => true],
                'prefix' => $settings?->currency_symbol ?: 'Rp.',
                'wrapper'   => ['class' => 'form-group col-md-6'],
                'attributes' => array_merge(['placeholder' => '000.000'], $readonly)
            ]);

            CRUD::addField([
                'label'     => trans('backpack::crud.client_po.column.category'),
                'type'      => 'select2_array',
                'name'      => 'category',
                'options'   => [
                    '' => trans('backpack::crud.voucher.field.payment_type.placeholder'),
                    'RUTIN' => 'RUTIN',
                    'NON RUTIN' => 'NON RUTIN',
                ],
                'wrapper' => ['class' => 'form-group col-md-6'],
                'attributes' => $category_attribute
            ]);

            CRUD::addField([
                'name' => 'logic_profit_lost',
                'type' => 'logic_profit_lost'
            ]);
        } else {
            CRUD::setValidation(ProfitLostRequest::class);
            CRUD::setModel(ConsolidateIncomeItem::class);
            $consolidate_header = DB::table('consolidate_income_headers')->get();
            $optionHeader = [];
            foreach ($consolidate_header as $header) {
                $optionHeader[$header->id] = $header->name;
            }

            CRUD::addField([
                'label'     => trans('backpack::crud.profit_lost.fields.header_id.label'),
                'type'      => 'select2_array',
                'name'      => 'header_id',
                'options'   =>  $optionHeader,
                'wrapper' => ['class' => 'form-group col-md-12']
            ]);

            CRUD::addField([
                'label'       => trans('backpack::crud.voucher.field.account_id.label'),
                'type'        => "select2_ajax_custom",
                'name'        => 'account_id',
                'entity'      => 'account',
                'model'       => 'App\Models\Account',
                'attribute'   => "name",
                'data_source' => backpack_url('finance-report/profit-lost/select2-account'),
                'wrapper'   => ['class' => 'form-group col-md-12'],
                'attributes' => ['placeholder' => trans('backpack::crud.voucher.field.account_id.placeholder')]
            ]);
        }
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function store()
    {
        $this->crud->hasAccessOrFail('create');
        $this->crud->validateRequest();

        try {
            if (request('type') == 'project') {
                $dto = ProjectProfitLostSaveData::fromRequest(request());
                $item = $this->service->storeProjectProfitLost($dto);
                $eventName = 'project_create_success';
            } else {
                $dto = ConsolidateItemSaveData::fromRequest(request());
                $item = $this->service->storeConsolidateItem($dto);
                $eventName = 'account_create_success';
            }

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            return response()->json([
                'success' => true,
                'data' => $item,
                'events' => [$eventName => $item]
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function storeProject()
    {
        CRUD::setValidation(ProfitLostRequest::class);
        return $this->store();
    }

    public function update()
    {
        $this->crud->hasAccessOrFail('update');
        $this->crud->validateRequest();

        try {
            if (request('type') == 'project') {
                $dto = ProjectProfitLostSaveData::fromRequest(request());
                $item = $this->service->updateProjectProfitLost($dto);
                $events = [
                    'crudTable-filter_profit_lost_plugin_load' => true,
                    'project_create_success' => true,
                ];
            } else {
                $dto = ConsolidateItemSaveData::fromRequest(request());
                $item = $this->service->updateConsolidateItem((int)request()->id, $dto);
                $events = ['account_create_success' => $item];
            }

            \Alert::success(trans('backpack::crud.update_success'))->flash();

            return response()->json([
                'success' => true,
                'data' => $item,
                'events' => $events
            ]);
        } catch (\Exception $e) {
            return response()->json(['status' => false, 'success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function updateProject()
    {
        return $this->update();
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        try {
            if (request('type') == 'project') {
                $this->service->deleteProjectProfitLost((int) $id);
                return response()->json(['events' => ['crudTable-project_updated_success' => true]]);
            }

            $this->service->deleteConsolidateItem((int) $id);

            return response()->json([
                'success' => [
                    '<strong>' . trans('backpack::crud.delete_confirmation_title') . '</strong><br>' . trans('backpack::crud.delete_confirmation_message'),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json(['type' => 'errors', 'message' => $e->getMessage()], 500);
        }
    }

    protected function setupListOperation()
    {
        $settings = Setting::first();
        CRUD::disableResponsiveTable();

        $request = request();
        $this->crud->file_title_export_pdf = "Laporan_daftar_laba_rugi_proyek.pdf";
        $this->crud->file_title_export_excel = "Laporan_daftar_laba_rugi_proyek.xlsx";
        $this->crud->param_uri_export = "?type=project";

        CRUD::addButtonFromView('top', 'export-excel-table', 'export-excel-table', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-table', 'export-pdf-table', 'beginning');
        CRUD::addButtonFromView('top', 'filter_year', 'filter-year', 'beginning');

        if ($request->has('type') && $request->type == 'project') {
            CRUD::setModel(ProjectProfitLost::class);
            $dto = ProfitLostFilterData::fromRequest($request);
            $this->repository->applyListQuery($this->crud->query, $dto);

            CRUD::removeButton('update');
            CRUD::removeButton('delete');
            CRUD::removeButton('show');

            CRUD::addButtonFromView('line', 'delete-profit-lost-project', 'delete-profit-lost-project', 'beginning');
            CRUD::addButtonFromView('line', 'update-profit-lost', 'update-profit-lost', 'beginning');
            CRUD::addButtonFromView('line', 'show-detail-project', "show-detail-project", 'end');

            $this->crud->addColumn([
                'name'      => 'row_number',
                'type'      => 'row_number',
                'label'     => 'No',
                'orderable' => false,
                'wrapper' => ['element' => 'strong']
            ])->makeFirstColumn();

            CRUD::column([
                'label' => '',
                'type'      => 'closure',
                'name'      => 'client_po_id',
                'function' => function ($entry) {
                    return $entry->clientPo->client->name ?? '-';
                },
                'searchLogic' => function ($query, $column, $searchTerm) {
                    $query->orWhereHas('clientPo.client', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', '%' . $searchTerm . '%');
                    });
                }
            ]);

            CRUD::column(['label'  => trans('backpack::crud.client_po.column.reimburse_type'), 'name' => 'reimburse_type', 'type' => 'text']);
            CRUD::column(['label'  => trans('backpack::crud.client_po.column.work_code'), 'name' => 'work_code', 'type' => 'text']);
            CRUD::column(['label'  => trans('backpack::crud.client_po.column.po_number'), 'name' => 'po_number', 'type' => 'text']);
            CRUD::column(['label'  => trans('backpack::crud.client_po.column.job_name'), 'name' => 'job_name', 'type' => 'text']);

            CRUD::column([
                'label'  => trans('backpack::crud.client_po.column.job_value_exclude_ppn'),
                'name' => 'price_job_exlude_ppn_logic',
                'type'  => 'closure',
                'function' => function ($entry) use ($settings) {
                    return CustomHelper::formatRupiahWithCurrency($entry->price_job_exlude_ppn_logic);
                },
                'orderLogic' => function ($query, $column, $columnDirection) {
                    $query->orderBy('client_po.price_job_exlude_ppn_logic', $columnDirection);
                }
            ]);


            CRUD::column([
                'label'  => trans('backpack::crud.client_po.column.job_value_include_ppn'),
                'name' => 'job_value_include_ppn_logic',
                'type'  => 'closure',
                'function' => function ($entry) use ($settings) {
                    return CustomHelper::formatRupiahWithCurrency($entry->job_value_include_ppn_logic);
                },
                'orderLogic' => function ($query, $column, $columnDirection) {
                    $query->orderBy('client_po.job_value_include_ppn_logic', $columnDirection);
                }
            ]);


            CRUD::column([
                'label'  => trans('backpack::crud.client_po.column.price_after_year'),
                'name' => 'price_after_year',
                'type'  => 'number',
                'prefix' => $settings?->currency_symbol ?: "Rp.",
                'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.',
            ]);

            CRUD::column([
                'label'  => trans('backpack::crud.profit_lost.column.price_voucher'),
                'name' => 'voucher_biaya',
                'type'  => 'number',
                'prefix' => $settings?->currency_symbol ?: "Rp.",
                'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.',
                'orderLogic' => function ($query, $column, $columnDirection) { $query->orderBy('voucher_biaya', $columnDirection); }
            ]);

            CRUD::column([
                'label'  => trans('backpack::crud.profit_lost.column.price_small_cash'),
                'name' => 'total_small_cash',
                'type'  => 'number',
                'prefix' => $settings?->currency_symbol ?: "Rp.",
                'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.',
                'orderLogic' => function ($query, $column, $columnDirection) { $query->orderBy('total_small_cash', $columnDirection); }
            ]);

            CRUD::column([
                'label'  => trans('backpack::crud.profit_lost.column.price_total'),
                'name' => 'price_total_str',
                'type'  => 'number',
                'prefix' => $settings?->currency_symbol ?: "Rp.",
                'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.',
                'orderLogic' => function ($query, $column, $columnDirection) { $query->orderBy('price_total_str', $columnDirection); }
            ]);

            CRUD::column([
                'label'  => trans('backpack::crud.profit_lost.column.profit_lost_po'),
                'name' => 'price_profit_lost_str',
                'type'  => 'number',
                'prefix' => $settings?->currency_symbol ?: "Rp.",
                'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.',
                'orderLogic' => function ($query, $column, $columnDirection) { $query->orderBy('price_profit_lost_str', $columnDirection); }
            ]);

            CRUD::column([
                'label'  => trans('backpack::crud.profit_lost.column.load_general_value'),
                'name' => 'price_general',
                'type'  => 'number',
                'prefix' => $settings?->currency_symbol ?: "Rp.",
                'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.',
                'orderLogic' => function ($query, $column, $columnDirection) { $query->orderBy('price_general', $columnDirection); }
            ]);

            CRUD::column([
                'label'  => trans('backpack::crud.profit_lost.column.profit_lost_final'),
                'name' => 'price_prift_lost_final_str',
                'type'  => 'number',
                'prefix' => $settings?->currency_symbol ?: "Rp.",
                'decimals' => 2, 'dec_point' => ',', 'thousands_sep' => '.',
                'orderLogic' => function ($query, $column, $columnDirection) { $query->orderBy('price_prift_lost_final_str', $columnDirection); }
            ]);

            CRUD::column(['label'  => trans('backpack::crud.profit_lost.column.category'), 'type' => 'text', 'name' => 'category']);

            CRUD::column([
                'label' => trans('backpack::crud.profit_lost.column.invoice_date'),
                'type' => 'date-multiple',
                'name' => 'invoice_date',
                'format' => 'DD/MM/YYYY',
                'orderLogic' => function ($query, $column, $columnDirection) { $query->orderBy('client_po.invoice_date', $columnDirection); }
            ]);
        } else {
            CRUD::removeButton('create');
            CRUD::removeButton('update');
            CRUD::removeButton('delete');

            CRUD::addButtonFromView('top', 'create', 'create-account-profit-lost', 'begining');
            CRUD::addButtonFromView('line', 'delete', "delete-account", 'beginning');
            CRUD::addButtonFromView('line', 'update', "update-account", 'beginning');

            CRUD::column(['name' => 'code_', 'label' => trans('backpack::crud.expense_account.column.code'), 'type' => 'text']);

            CRUD::column([
                'name' => 'name_',
                'label' => trans('backpack::crud.expense_account.column.name'),
                'type' => 'custom_html',
                'value' => function ($entry) {
                    if ($entry->level_ > 2) {
                        $space = str_repeat('&nbsp;', $entry->level_);
                        return $space . '&bull; ' . $entry->name_;
                    }
                    return $entry->name_;
                }
            ]);

            CRUD::column([
                'name' => 'balance',
                'label' => trans('backpack::crud.expense_account.column.balance'),
                'type' => 'custom_html',
                'value' => function ($entry) {
                    return CustomHelper::formatRupiahWithCurrency($entry->balance);
                },
            ]);

            if ($request->has('_id')) {
                $id = $request->_id;
                $account = Account::findOrFail($id);

                $this->crud->query = $this->crud->query
                    ->leftJoin('journal_entries', 'journal_entries.account_id', '=', 'accounts.id');

                CRUD::addClause('select', [
                    DB::raw("
                        accounts.id as id,
                        accounts.id as id_,
                        MAX(accounts.code) as code_,
                        MAX(accounts.name) as name_,
                        MAX(accounts.level) as level_,
                        (SUM(journal_entries.debit) - SUM(journal_entries.credit)) as balance
                    ")
                ]);

                if ($account->level == 1) {
                    $this->crud->query = $this->crud->query->where('code', 'LIKE', "{$account->code}");
                } else {
                    $this->crud->query = $this->crud->query->where('code', 'LIKE', "{$account->code}%");
                }

                $this->crud->query = $this->crud->query
                    ->orderBy('code', 'asc')
                    ->groupBy('accounts.id');
            }
        }
    }

    protected function setupListExportOperation()
    {
        $settings = Setting::first();
        $request = request();
        if ($request->has('type') && $request->type == 'project') {
            CRUD::setModel(ProjectProfitLost::class);
            $dto = ProfitLostFilterData::fromRequest($request);
            $this->repository->applyListQuery($this->crud->query, $dto);

            $status_file = strpos(url()->current(), 'excel') ? 'excel' : 'pdf';

            CRUD::column([
                'label' => 'Client',
                'type' => 'closure',
                'name' => 'client_po_id',
                'function' => function ($entry) { return $entry->clientPo->client->name ?? '-'; }
            ]);

            CRUD::column(['label' => trans('backpack::crud.client_po.column.reimburse_type'), 'name' => 'reimburse_type', 'type' => 'text']);
            CRUD::column(['label' => trans('backpack::crud.client_po.column.work_code'), 'name' => 'work_code', 'type' => 'text']);
            CRUD::column(['label' => trans('backpack::crud.client_po.column.po_number'), 'name' => 'po_number', 'type' => 'text']);
            CRUD::column(['label' => trans('backpack::crud.client_po.column.job_name'), 'name' => 'job_name', 'type' => 'text']);

            CRUD::column([
                'label' => trans('backpack::crud.client_po.column.job_value_exclude_ppn'),
                'name' => 'price_job_exlude_ppn_logic',
                'type' => 'closure',
                'function' => function ($entry) use ($status_file) { return $this->priceFormatExport($status_file, $entry->price_job_exlude_ppn_logic); }
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.client_po.column.job_value_include_ppn'),
                'name' => 'job_value_include_ppn_logic',
                'type' => 'closure',
                'function' => function ($entry) use ($status_file) { return $this->priceFormatExport($status_file, $entry->job_value_include_ppn_logic); }
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.client_po.column.price_after_year'),
                'name' => 'price_after_year',
                'type' => 'closure',
                'function' => function ($entry) use ($status_file) { return $this->priceFormatExport($status_file, $entry->price_after_year); }
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.profit_lost.column.price_voucher'),
                'name' => 'voucher_biaya',
                'type' => 'closure',
                'function' => function ($entry) use ($status_file) { return $this->priceFormatExport($status_file, $entry->voucher_biaya); }
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.profit_lost.column.price_small_cash'),
                'name' => 'total_small_cash',
                'type' => 'closure',
                'function' => function ($entry) use ($status_file) { return $this->priceFormatExport($status_file, $entry->total_small_cash); }
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.profit_lost.column.price_total'),
                'name' => 'price_total_str',
                'type' => 'closure',
                'function' => function ($entry) use ($status_file) { return $this->priceFormatExport($status_file, $entry->price_total_str); }
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.profit_lost.column.profit_lost_po'),
                'name' => 'price_profit_lost_str',
                'type' => 'closure',
                'function' => function ($entry) use ($status_file) { return $this->priceFormatExport($status_file, $entry->price_profit_lost_str); }
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.profit_lost.column.load_general_value'),
                'name' => 'price_general',
                'type' => 'closure',
                'function' => function ($entry) use ($status_file) { return $this->priceFormatExport($status_file, $entry->price_general); }
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.profit_lost.column.profit_lost_final'),
                'name' => 'price_prift_lost_final_str',
                'type' => 'closure',
                'function' => function ($entry) use ($status_file) { return $this->priceFormatExport($status_file, $entry->price_prift_lost_final_str); }
            ]);

            CRUD::column(['label' => trans('backpack::crud.profit_lost.column.category'), 'type' => 'text', 'name' => 'category']);
            CRUD::column(['label' => trans('backpack::crud.profit_lost.column.invoice_date'), 'type' => 'text', 'name' => 'invoice_date']);
        }
    }

    public function search()
    {
        $this->crud->hasAccessOrFail('list');
        $this->crud->applyUnappliedFilters();

        $start = (int) request()->input('start');
        $length = (int) request()->input('length');
        $search = request()->input('search');

        if ($length && ! in_array($length, $this->crud->getPageLengthMenu()[0])) {
            return response()->json(['error' => 'Unknown page length.'], 400);
        }

        if ($search && $search['value'] ?? false) {
            $this->crud->applySearchTerm($search['value']);
        }
        if ($start) { $this->crud->skip($start); }
        if ($length) { $this->crud->take($length); }
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

    public function exportPdf()
    {
        $this->setupListExportOperation();
        $columns = $this->crud->columns();
        $items =  $this->crud->getEntries();

        $row_number = 0;
        $all_items = [];

        foreach ($items as $item) {
            $row_items = [];
            $row_number++;
            foreach ($columns as $column) {
                $item_value = ($column['name'] == 'row_number') ? $row_number : $this->crud->getCellView($column, $item, $row_number);
                $item_value = str_replace(['<span>', '</span>', "\n"], '', $item_value);
                $item_value = CustomHelper::clean_html($item_value);
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $pdf = Pdf::loadView('exports.table-pdf', [
            'columns' => $columns,
            'items' => $all_items,
            'title' => "DAFTAR LAPORAN LABA RUGI PROYEK"
        ])->setPaper('A4', 'landscape');

        $fileName = 'laba_rugi_proyek_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) { echo $pdf->output(); }, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportExcel()
    {
        $this->setupListExportOperation();
        $columns = $this->crud->columns();
        $items =  $this->crud->getEntries();

        $row_number = 0;
        $all_items = [];

        foreach ($items as $item) {
            $row_items = [];
            $row_number++;
            foreach ($columns as $column) {
                $item_value = ($column['name'] == 'row_number') ? $row_number : $this->crud->getCellView($column, $item, $row_number);
                $item_value = str_replace(['<span>', '</span>', "\n"], '', $item_value);
                $item_value = CustomHelper::clean_html($item_value);
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $name = 'DAFTAR_LABA_RUGI_PROYEK.xlsx';

        return response()->streamDownload(function () use ($columns, $all_items) {
            echo Excel::raw(new ExportExcel($columns, $all_items), \Maatwebsite\Excel\Excel::XLSX);
        }, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }

    public function exportDetailPdf()
    {
        $id = request()->id;
        $profitLost = ProjectProfitLost::findOrFail($id);

        $pdf = Pdf::loadView('exports.profit-lost-detail', [
            'profit_lost' => $profitLost,
            'report' => $this->total_detail_project($id)
        ])->setPaper('A4', 'portrait');

        $fileName = 'laporan-laba-rugi_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) { echo $pdf->output(); }, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportDetailExcel()
    {
        $id = request()->id;
        $profitLost = ProjectProfitLost::findOrFail($id);
        $report = $this->total_detail_project($id, 1);

        $name = "Laporan-laba-rugi-proyek-detail.xlsx";

        return response()->streamDownload(function () use ($profitLost, $report) {
            echo Excel::raw(new ProfitLostExcel($profitLost, $report), \Maatwebsite\Excel\Excel::XLSX);
        }, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }

    public function exportConsolidationPdf()
    {
        $pdf = Pdf::loadView('exports.profit-lost-consolidation-pdf', [
            'data' => $this->total_report_account_profit_lost_ajax(),
        ])->setPaper('A4', 'portrait');

        $fileName = 'laba-rugi-konsolidasi_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) { echo $pdf->output(); }, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportConsolidationExcel()
    {
        $data = $this->total_report_account_profit_lost_ajax();

        foreach ($data as $key => $d) {
            $data[$key]['total'] = (float)str_replace([',', 'Rp', '.', ' '], ['', '', '', ''], $d['total']) / 100;
            if ($d['item']->count() > 0) {
                $d['item'] = $d['item']->map(function ($item) {
                    $item['total'] = (float)str_replace([',', 'Rp', '.', ' '], ['', '', '', ''], $item['total']) / 100;
                    return $item;
                });
            }
        }

        $name = "Laporan-laba-rugi-konsolidasi.xlsx";

        return response()->streamDownload(function () use ($data) {
            echo Excel::raw(new ExportProfitLostConsolidation($data), \Maatwebsite\Excel\Excel::XLSX);
        }, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }
}
