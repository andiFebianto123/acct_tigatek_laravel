<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Account;
use App\Models\Setting;
use App\Models\Voucher;
use App\Models\ClientPo;
use App\Models\PurchaseOrder;
use App\Models\JournalEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Exports\ExportExcel;
use App\Models\ProjectProfitLost;
use App\Models\InvoiceClient;
use App\Http\Helpers\CustomHelper;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Exports\ProfitLostExcel;
use App\Http\Exports\ProfitLostSupplierExcel;
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

        // -------------------------------------------------------
        // Card dengan 2 Tab: Proyek & Supplier
        // -------------------------------------------------------
        $this->card->addCard([
            'name' => 'project',
            'line' => 'bottom',
            'view' => 'crud::components.card-tab',
            'params' => [
                'name' => 'project',
                'tabs' => [
                    // Tab 1: Proyek (kolom sama seperti sebelumnya)
                    [
                        'name'   => 'project',
                        'label'  => trans('backpack::crud.profit_lost.project_income_statement'),
                        'active' => true,
                        'view'   => 'crud::components.datatable',
                        'params' => [
                            'crud_custom'   => $this->crud,
                            'filter'        => true,
                            'filter_table'  => collect($this->crud->filters())->slice(0, 2),
                            'columns'       => [
                                [
                                    'name'      => 'row_number',
                                    'type'      => 'row_number',
                                    'label'     => 'No',
                                    'orderable' => false,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.client_po_id'),
                                    'type'      => 'select',
                                    'name'      => 'client_po_id',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.client_po.column.reimburse_type'),
                                    'type'      => 'text',
                                    'name'      => 'reimburse_type',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.invoice_client.column.invoice_number'),
                                    'type'      => 'text',
                                    'name'      => 'invoice_number',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.client_po.column.job_name'),
                                    'type'      => 'text',
                                    'name'      => 'job_name',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.client_po.column.job_value_exclude_ppn'),
                                    'type'      => 'text',
                                    'name'      => 'job_value',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.client_po.column.job_value_include_ppn'),
                                    'type'      => 'text',
                                    'name'      => 'job_value_include_ppn',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.client_po.column.price_after_year'),
                                    'type'      => 'text',
                                    'name'      => 'price_after_year',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.price_voucher'),
                                    'type'      => 'text',
                                    'name'      => 'price_voucher',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.price_small_cash'),
                                    'type'      => 'text',
                                    'name'      => 'price_small_cash',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.price_total'),
                                    'type'      => 'text',
                                    'name'      => 'price_total',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.profit_lost_po'),
                                    'type'      => 'text',
                                    'name'      => 'profit_lost_po',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.load_general_value'),
                                    'type'      => 'text',
                                    'name'      => 'load_general_value',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.profit_lost_final'),
                                    'type'      => 'text',
                                    'name'      => 'profit_lost_final',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.category'),
                                    'type'      => 'text',
                                    'name'      => 'category',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.invoice_date'),
                                    'type'      => 'date-multiple',
                                    'name'      => 'invoice_date',
                                    'format'    => 'DD/MM/YYYY',
                                    'orderable' => true,
                                ],
                                [
                                    'name'  => 'action',
                                    'type'  => 'action',
                                    'label' => trans('backpack::crud.actions'),
                                ],
                            ],
                            'route'               => url($this->crud->route . '/search?type=project&tab=project'),
                            'route_export_pdf'    => url($this->crud->route . '/export-pdf?type=project&tab=project'),
                            'title_export_pdf'    => 'Laporan-Laba-Rugi-Proyek.pdf',
                            'route_export_excel'  => url($this->crud->route . '/export-excel?type=project&tab=project'),
                            'title_export_excel'  => 'Laporan-Laba-Rugi-Proyek.xlsx',
                        ],
                    ],

                    // Tab 2: Supplier (kolom FIFO)
                    [
                        'name'   => 'supplier',
                        'label'  => trans('backpack::crud.profit_lost.supplier_income_statement') ?? 'Laporan Laba Rugi Supplier',
                        'active' => false,
                        'view'   => 'crud::components.datatable',
                        'params' => [
                            'crud_custom' => $this->crud,
                            'filter'      => false,
                            'columns'     => [
                                [
                                    'name'      => 'row_number',
                                    'type'      => 'row_number',
                                    'label'     => 'No',
                                    'orderable' => false,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.supplier_invoice_number') ?? 'No. Invoice',
                                    'type'      => 'text',
                                    'name'      => 'supplier_invoice_number',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.invoice_date') ?? 'Tgl Invoice',
                                    'type'      => 'date',
                                    'name'      => 'supplier_date',
                                    'format'    => 'DD/MM/YYYY',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.supplier_name') ?? 'Klien / Supplier',
                                    'type'      => 'text',
                                    'name'      => 'supplier_name',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.total_qty_sold') ?? 'Qty Terjual',
                                    'type'      => 'number',
                                    'name'      => 'total_qty_sold',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.sell_value') ?? 'Total Jual (Revenue)',
                                    'type'      => 'text',
                                    'name'      => 'sell_value',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.avg_harga_jual_satuan') ?? 'Avg Jual Satuan',
                                    'type'      => 'text',
                                    'name'      => 'avg_harga_jual_satuan_base',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.purchase_value') ?? 'Total Beli (HPP FIFO)',
                                    'type'      => 'text',
                                    'name'      => 'purchase_value',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.avg_harga_beli_satuan') ?? 'Avg Beli Satuan',
                                    'type'      => 'text',
                                    'name'      => 'avg_harga_beli_satuan_base',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.voucher_supplier_value') ?? 'Biaya Lain - Lain',
                                    'type'      => 'text',
                                    'name'      => 'voucher_supplier_value',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.profit_lost_supplier') ?? 'Laba Kotor',
                                    'type'      => 'text',
                                    'name'      => 'profit_lost_supplier',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.margin_percent') ?? 'Margin (%)',
                                    'type'      => 'text',
                                    'name'      => 'margin_percent',
                                    'orderable' => true,
                                ],
                                [
                                    'label'     => trans('backpack::crud.profit_lost.column.delivery_status') ?? 'Status Pengiriman',
                                    'type'      => 'text',
                                    'name'      => 'delivery_status',
                                    'orderable' => true,
                                ],
                                [
                                    'name'  => 'action',
                                    'type'  => 'action',
                                    'label' => trans('backpack::crud.actions'),
                                ],
                            ],
                            'route'               => url($this->crud->route . '/search?type=supplier&tab=supplier'),
                            'route_export_pdf'    => url($this->crud->route . '/export-pdf?type=supplier&tab=supplier'),
                            'title_export_pdf'    => 'Laporan-Laba-Rugi-Supplier.pdf',
                            'route_export_excel'  => url($this->crud->route . '/export-excel?type=supplier&tab=supplier'),
                            'title_export_excel'  => 'Laporan-Laba-Rugi-Supplier.xlsx',
                        ],
                    ],
                ],
            ],
        ]);

        $this->card->addCard([
            'name'        => 'profit-lost-plugin',
            'line'        => 'top',
            'view'        => 'crud::components.profit-lost-plugin',
            'parent_view' => 'crud::components.filter-parent',
            'params'      => [],
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
        $request = request();
        $filter_year = $request->has('filter_year') ? $request->filter_year : null;
        return $this->repository->getProjectDetail((int) $id, $filter_year, (bool) $pure);
    }

    public function total_detail_supplier($id, $pure = 0)
    {
        $request = request();
        $filter_year = $request->has('filter_year') ? $request->filter_year : null;
        return $this->repository->getSupplierDetail((int) $id, $filter_year, (bool) $pure);
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

        $docLabel = $profitLost?->clientPo?->po_number ?? ($profitLost?->orderable?->invoice_number ?? '-');
        $breadcrumbs = [
            trans('backpack::crud.menu.finance_report') => backpack_url('cash-flow'),
            trans('backpack::crud.menu.profit_lost') => url($this->crud->route),
            $docLabel => $docLabel,
        ];
        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.profit_lost.title_modal_create');
        $this->data['title_modal_edit'] = trans('backpack::crud.profit_lost.title_modal_edit_consolidation');
        $this->data['title_modal_delete'] = trans('backpack::crud.profit_lost.title_modal_delete_consolidation');

        $isSupplier = ($profitLost?->orderable_type === \App\Models\InvoiceClient::class);

        if ($isSupplier) {
            $this->card->addCard([
                'name' => 'detail-supplier',
                'line' => 'top',
                'view' => 'crud::components.detail-supplier-profit-lost',
                'params' => [
                    'data'   => $profitLost,
                    'report' => $this->total_detail_supplier($id),
                ],
                'wrapper' => [
                    'class' => 'col-md-6'
                ]
            ]);
        } else {
            $this->card->addCard([
                'name' => 'detail-project',
                'line' => 'top',
                'view' => 'crud::components.detail-project-profit-lost',
                'params' => [
                    'data'   => $profitLost,
                    'report' => $this->total_detail_project($id),
                ],
                'wrapper' => [
                    'class' => 'col-md-6'
                ]
            ]);
        }

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

        if ($client_po) {
            $this->crud->entry->po_number = $client_po->po_number;
            if ($client_po->po_type === 'supplier') {
                $this->crud->entry->orderable_type = 'App\\Models\\PurchaseOrder';
                $this->crud->entry->purchase_order_id = $client_po->id;
            }
        }

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

    public function select2SupplierPo()
    {
        $q = request()->q;
        $results = $this->repository->getSelect2SupplierPo($q);
        return response()->json(['results' => $results]);
    }

    /**
     * Endpoint baru: pencarian invoice by kode invoice untuk tipe Supplier
     */
    public function select2SupplierInvoice()
    {
        $q = request()->q;
        $results = $this->repository->getSelect2SupplierInvoice($q);
        return response()->json(['results' => $results]);
    }

    public function get_source_selected_ajax()
    {
        $id = request()->id;
        $type = request()->type;
        $result = $this->repository->getSourceSelectedData((int) $id, $type);
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

            if (backpack_user()->canAccessAllCompanies()) {
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
                'name'        => 'orderable_type',
                'label'       => 'Jenis Laba Rugi',
                'type'        => 'select2_array',
                'options'     => [
                    'App\\Models\\ClientPo'      => 'Subkon',
                    'App\\Models\\InvoiceClient' => 'Supplier',
                ],
                'allows_null' => false,
                'default'     => 'App\\Models\\ClientPo',
                'wrapper'     => ['class' => 'form-group col-md-6'],
                'attributes'  => $category_attribute,
            ]);

            // Field PO Client (untuk tipe Subkon)
            CRUD::addField([
                'name'                  => 'work_code',
                'label'                 => 'Invoice',
                'type'                  => 'select2_ajax_custom',
                'attribute'             => 'invoice_number_display',
                'entity'                => 'clientPo',
                'model'                 => 'App\\Models\\ClientPo',
                'data_source'           => backpack_url('finance-report/profit-lost/select2-po'),
                'wrapper'               => ['class' => 'form-group col-md-6'],
                'attributes'            => $job_code_prefix_value,
                'dependencies'          => ['company_id'],
                'include_all_form_fields' => true,
            ]);

            // Field PO Supplier (hanya tampil saat orderable_type = PurchaseOrder)
            CRUD::addField([
                'name'        => 'purchase_order_id',
                'label'       => trans('backpack::crud.profit_lost.fields.po_client_supplier.label'),
                'type'        => 'select2_ajax_custom',
                'attribute'   => 'po_number',
                'entity'      => 'clientPo',
                'data_source' => backpack_url('finance-report/profit-lost/select2-supplier-po'),
                'wrapper'     => ['class' => 'form-group col-md-6'],
                'attributes'  => $job_code_prefix_value,
                'dependencies' => ['company_id'],
                'include_all_form_fields' => true,
            ]);

            // Field Invoice Supplier (hanya tampil saat orderable_type = PurchaseOrder)
            // Digunakan untuk mencari invoice by kode invoice
            CRUD::addField([
                'name'        => 'supplier_invoice_id',
                'label'       => trans('backpack::crud.profit_lost.fields.supplier_invoice.label') ?? 'No. Invoice Supplier',
                'type'        => 'select2_ajax_custom',
                'attribute'   => 'invoice_number',
                'entity'      => 'purchaseOrder',
                'data_source' => backpack_url('finance-report/profit-lost/select2-supplier-invoice'),
                'wrapper'     => ['class' => 'form-group col-md-6'],
                'attributes'  => $job_code_prefix_value,
                'dependencies' => ['company_id', 'purchase_order_id'],
                'include_all_form_fields' => true,
            ]);

            CRUD::addField([
                'name'         => 'price_after_year',
                'label'        => trans('backpack::crud.profit_lost.fields.price_after_year.label'),
                'type'         => 'mask',
                'mask'         => '000.000.000.000.000.000',
                'mask_options' => ['reverse' => true],
                'prefix'       => $settings?->currency_symbol ?: 'Rp.',
                'wrapper'      => ['class' => 'form-group col-md-6'],
                'attributes'   => ['placeholder' => '000.000']
            ]);

            CRUD::addField([
                'name'         => 'price_voucher',
                'label'        => trans('backpack::crud.profit_lost.fields.price_voucher.label'),
                'type'         => 'mask',
                'mask'         => '000.000.000.000.000.000',
                'mask_options' => ['reverse' => true],
                'prefix'       => $settings?->currency_symbol ?: 'Rp.',
                'wrapper'      => ['class' => 'form-group col-md-6'],
                'attributes'   => array_merge(['placeholder' => '000.000'], $price_voucher_attribute)
            ]);

            CRUD::addField([
                'name'         => 'price_small_cash',
                'label'        => trans('backpack::crud.profit_lost.fields.price_small_cash.label'),
                'type'         => 'mask',
                'mask'         => '000.000.000.000.000.000',
                'mask_options' => ['reverse' => true],
                'prefix'       => $settings?->currency_symbol ?: 'Rp.',
                'wrapper'      => ['class' => 'form-group col-md-6'],
                'attributes'   => array_merge(['placeholder' => '000.000'], $price_small_cash_attribute)
            ]);

            CRUD::addField([
                'name'         => 'price_total',
                'label'        => trans('backpack::crud.profit_lost.fields.price_total.label'),
                'type'         => 'text',
                'mask'         => '000.000.000.000.000.000',
                'mask_options' => ['reverse' => true],
                'prefix'       => $settings?->currency_symbol ?: 'Rp.',
                'wrapper'      => ['class' => 'form-group col-md-6'],
                'attributes'   => array_merge(['placeholder' => '000.000'], $readonly)
            ]);

            CRUD::addField([
                'name'         => 'price_profit_lost_po',
                'label'        => trans('backpack::crud.profit_lost.fields.price_profit_lost_po.label'),
                'type'         => 'text',
                'mask'         => 'Z000.000.000.000.000.000',
                'mask_options' => ['reverse' => true],
                'prefix'       => $settings?->currency_symbol ?: 'Rp.',
                'wrapper'      => ['class' => 'form-group col-md-6'],
                'attributes'   => array_merge(['placeholder' => '000.000'], $readonly)
            ]);

            CRUD::addField([
                'name'         => 'price_general',
                'label'        => trans('backpack::crud.profit_lost.fields.price_general.label'),
                'type'         => 'mask',
                'mask'         => '000.000.000.000.000.000',
                'mask_options' => ['reverse' => true],
                'prefix'       => $settings?->currency_symbol ?: 'Rp.',
                'wrapper'      => ['class' => 'form-group col-md-6'],
                'attributes'   => ['placeholder' => '000.000']
            ]);

            CRUD::addField([
                'name'         => 'price_prift_lost_final',
                'label'        => trans('backpack::crud.profit_lost.fields.price_prift_lost_final.label'),
                'type'         => 'text',
                'mask'         => 'Z000.000.000.000.000.000',
                'mask_options' => ['reverse' => true],
                'prefix'       => $settings?->currency_symbol ?: 'Rp.',
                'wrapper'      => ['class' => 'form-group col-md-6'],
                'attributes'   => array_merge(['placeholder' => '000.000'], $readonly)
            ]);

            CRUD::addField([
                'label'      => trans('backpack::crud.client_po.column.category'),
                'type'       => 'select2_array',
                'name'       => 'category',
                'options'    => [
                    ''           => trans('backpack::crud.voucher.field.payment_type.placeholder'),
                    'RUTIN'      => 'RUTIN',
                    'NON RUTIN'  => 'NON RUTIN',
                ],
                'wrapper'    => ['class' => 'form-group col-md-6'],
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
                'label'   => trans('backpack::crud.profit_lost.fields.header_id.label'),
                'type'    => 'select2_array',
                'name'    => 'header_id',
                'options' => $optionHeader,
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
                'wrapper'     => ['class' => 'form-group col-md-12'],
                'attributes'  => ['placeholder' => trans('backpack::crud.voucher.field.account_id.placeholder')]
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
                if(request('orderable_type') == "App\\Models\\InvoiceClient"){
                    $eventName = 'crudTable-supplier_create_success';
                }else{
                    $eventName = 'crudTable-project_create_success';
                }
            } else {
                $dto = ConsolidateItemSaveData::fromRequest(request());
                $item = $this->service->storeConsolidateItem($dto);
                $eventName = 'crudTable-account_create_success';
            }

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            return response()->json([
                'success' => true,
                'data'    => $item,
                'events'  => [$eventName => $item]
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
                'data'    => $item,
                'events'  => $events
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
                return response()->json(['events' => [
                    'crudTable-project_updated_success' => true,
                    'crudTable-supplier_updated_success' => true,
                ]]);
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
            // --------------------------------------------------
            // Tab: Proyek
            // --------------------------------------------------
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
                'wrapper'   => ['element' => 'strong']
            ])->makeFirstColumn();

            CRUD::column([
                'label'  => '',
                'type'      => 'closure',
                'name'      => 'client_po_id',
                'function' => function ($entry) {
                    if ($entry->clientPo && $entry->clientPo->po_type === 'supplier') {
                        return 'PT. TIGA TEKNOLOGI PERSADA';
                    }
                    return $entry->clientPo->client->name ?? '-';
                },
                'searchLogic' => function ($query, $column, $searchTerm) {
                    $query->orWhereHas('clientPo.client', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', '%' . $searchTerm . '%');
                    });
                }
            ]);

            CRUD::column(['label'  => trans('backpack::crud.client_po.column.reimburse_type'), 'name' => 'reimburse_type', 'type' => 'text']);
            CRUD::column(['label'  => trans('backpack::crud.invoice_client.column.invoice_number'), 'name' => 'invoice_number', 'type' => 'text']);
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

        } elseif ($request->has('type') && $request->type == 'supplier') {
            // --------------------------------------------------
            // Tab: Supplier (Perhitungan FIFO Lengkap)
            // --------------------------------------------------
            CRUD::setModel(ProjectProfitLost::class);
            $dto = ProfitLostFilterData::fromRequest($request);
            $this->repository->applySupplierListQuery($this->crud->query, $dto);

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
                'wrapper'   => ['element' => 'strong']
            ])->makeFirstColumn();

            // No. Invoice Supplier
            CRUD::column([
                'label' => trans('backpack::crud.profit_lost.column.supplier_invoice_number') ?? 'No. Invoice',
                'name'  => 'supplier_invoice_number',
                'type'  => 'text',
            ]);

            // Tanggal Invoice
            CRUD::column([
                'label'  => trans('backpack::crud.profit_lost.column.invoice_date') ?? 'Tgl Invoice',
                'name'   => 'supplier_date',
                'type'   => 'date',
                'format' => 'DD/MM/YYYY',
            ]);

            // Nama Klien / Supplier
            CRUD::column([
                'label'  => trans('backpack::crud.profit_lost.column.supplier_name') ?? 'Klien / Supplier',
                'name'   => 'supplier_name',
                'type'   => 'closure',
                'function' => function ($entry) {
                    return $entry->supplier_name ?? '-';
                },
                'searchLogic' => function ($query, $column, $searchTerm) {
                    $query->orWhere('c.name', 'like', '%' . $searchTerm . '%');
                }
            ]);

            // Total Qty Terjual
            CRUD::column([
                'label' => trans('backpack::crud.profit_lost.column.total_qty_sold') ?? 'Qty Terjual',
                'name'  => 'total_qty_sold',
                'type'  => 'number',
            ]);

            // Total Jual (Revenue - Exclude PPN IDR Base)
            CRUD::column([
                'label'    => trans('backpack::crud.profit_lost.column.sell_value') ?? 'Total Jual (Revenue)',
                'name'     => 'sell_value',
                'type'     => 'closure',
                'function' => function ($entry) {
                    return CustomHelper::formatRupiahWithCurrency($entry->sell_value ?? 0);
                },
                'orderLogic' => function ($query, $column, $columnDirection) {
                    $query->orderBy('supplier_data.total_harga_jual_base', $columnDirection);
                }
            ]);

            // Rata-rata Harga Jual Satuan
            CRUD::column([
                'label'    => trans('backpack::crud.profit_lost.column.avg_harga_jual_satuan') ?? 'Avg Jual Satuan',
                'name'     => 'avg_harga_jual_satuan_base',
                'type'     => 'closure',
                'function' => function ($entry) {
                    return CustomHelper::formatRupiahWithCurrency($entry->avg_harga_jual_satuan_base ?? 0);
                },
            ]);

            // Total Harga Beli / Modal HPP FIFO
            CRUD::column([
                'label'    => trans('backpack::crud.profit_lost.column.purchase_value') ?? 'Total Beli (HPP FIFO)',
                'name'     => 'purchase_value',
                'type'     => 'closure',
                'function' => function ($entry) {
                    return CustomHelper::formatRupiahWithCurrency($entry->purchase_value ?? 0);
                },
                'orderLogic' => function ($query, $column, $columnDirection) {
                    $query->orderBy('supplier_data.total_harga_beli_base', $columnDirection);
                }
            ]);

            // Rata-rata Harga Beli Modal Satuan
            CRUD::column([
                'label'    => trans('backpack::crud.profit_lost.column.avg_harga_beli_satuan') ?? 'Avg Beli Satuan',
                'name'     => 'avg_harga_beli_satuan_base',
                'type'     => 'closure',
                'function' => function ($entry) {
                    return CustomHelper::formatRupiahWithCurrency($entry->avg_harga_beli_satuan_base ?? 0);
                },
            ]);

            // Voucher Supplier
            CRUD::column([
                'label'    => trans('backpack::crud.profit_lost.column.voucher_supplier_value') ?? 'Biaya Lain - Lain',
                'name'     => 'voucher_supplier_value',
                'type'     => 'closure',
                'function' => function ($entry) {
                    return CustomHelper::formatRupiahWithCurrency($entry->voucher_supplier_value ?? 0);
                },
                'orderLogic' => function ($query, $column, $columnDirection) {
                    $query->orderBy('supplier_data.total_voucher_supplier_base', $columnDirection);
                }
            ]);

            // Laba Kotor (Revenue - HPP FIFO)
            CRUD::column([
                'label'    => trans('backpack::crud.profit_lost.column.profit_lost_supplier') ?? 'Laba Kotor',
                'name'     => 'profit_lost_supplier',
                'type'     => 'closure',
                'function' => function ($entry) {
                    $value = $entry->profit_lost_supplier ?? 0;
                    $formatted = CustomHelper::formatRupiahWithCurrency(abs($value));
                    if ($value < 0) {
                        return '<span class="text-danger">(' . $formatted . ')</span>';
                    }
                    return '<span class="text-success">' . $formatted . '</span>';
                },
                'escaped' => false,
                'orderLogic' => function ($query, $column, $columnDirection) {
                    $query->orderBy('supplier_data.laba_kotor_base', $columnDirection);
                }
            ]);

            // Persentase Margin Laba Kotor (%)
            CRUD::column([
                'label'    => trans('backpack::crud.profit_lost.column.margin_percent') ?? 'Margin (%)',
                'name'     => 'margin_percent',
                'type'     => 'closure',
                'function' => function ($entry) {
                    $margin = $entry->margin_percent ?? 0;
                    $badgeClass = $margin >= 0 ? 'bg-success' : 'bg-danger';
                    return '<span class="badge ' . $badgeClass . '">' . number_format($margin, 2, ',', '.') . '%</span>';
                },
                'escaped' => false,
            ]);

            // Status Pengiriman (FIFO Posted)
            CRUD::column([
                'label'    => trans('backpack::crud.profit_lost.column.delivery_status') ?? 'Status Pengiriman',
                'name'     => 'delivery_status',
                'type'     => 'closure',
                'function' => function ($entry) {
                    $status = $entry->delivery_status ?? '-';
                    $badge = str_contains($status, 'Dikirim') ? 'bg-info' : 'bg-secondary';
                    return '<span class="badge ' . $badge . '">' . e($status) . '</span>';
                },
                'escaped' => false,
            ]);

        } else {
            // --------------------------------------------------
            // Default: Akun Laba Rugi Konsolidasi
            // --------------------------------------------------
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
                'function' => function ($entry) {
                    if ($entry->clientPo && $entry->clientPo->po_type === 'supplier') {
                        return 'PT. TIGA TEKNOLOGI PERSADA';
                    }
                    return $entry->clientPo->client->name ?? '-';
                }
            ]);

            CRUD::column(['label' => trans('backpack::crud.client_po.column.reimburse_type'), 'name' => 'reimburse_type', 'type' => 'text']);
            CRUD::column(['label' => trans('backpack::crud.invoice_client.column.invoice_number'), 'name' => 'invoice_number', 'type' => 'text']);
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

        } elseif ($request->has('type') && $request->type == 'supplier') {
            // --------------------------------------------------
            // Export Tab Supplier (Perhitungan FIFO)
            // --------------------------------------------------
            CRUD::setModel(ProjectProfitLost::class);
            $dto = ProfitLostFilterData::fromRequest($request);
            $this->repository->applySupplierListQuery($this->crud->query, $dto);

            $status_file = strpos(url()->current(), 'excel') ? 'excel' : 'pdf';

            CRUD::column([
                'label' => trans('backpack::crud.profit_lost.column.supplier_invoice_number') ?? 'No. Invoice',
                'name'  => 'supplier_invoice_number',
                'type'  => 'text',
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.profit_lost.column.invoice_date') ?? 'Tgl Invoice',
                'name'  => 'supplier_date',
                'type'  => 'text',
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.profit_lost.column.supplier_name') ?? 'Klien / Supplier',
                'name'  => 'supplier_name',
                'type'  => 'closure',
                'function' => function ($entry) { return $entry->supplier_name ?? '-'; }
            ]);

            CRUD::column([
                'label' => 'Qty Terjual',
                'name'  => 'total_qty_sold',
                'type'  => 'text',
            ]);

            CRUD::column([
                'label' => 'Total Jual (Revenue)',
                'name'  => 'sell_value',
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) { return $this->priceFormatExport($status_file, $entry->sell_value ?? 0); }
            ]);

            CRUD::column([
                'label' => 'Avg Jual Satuan',
                'name'  => 'avg_harga_jual_satuan_base',
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) { return $this->priceFormatExport($status_file, $entry->avg_harga_jual_satuan_base ?? 0); }
            ]);

            CRUD::column([
                'label' => 'Total Beli (HPP FIFO)',
                'name'  => 'purchase_value',
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) { return $this->priceFormatExport($status_file, $entry->purchase_value ?? 0); }
            ]);

            CRUD::column([
                'label' => 'Avg Beli Satuan',
                'name'  => 'avg_harga_beli_satuan_base',
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) { return $this->priceFormatExport($status_file, $entry->avg_harga_beli_satuan_base ?? 0); }
            ]);

            CRUD::column([
                'label' => 'Laba Kotor',
                'name'  => 'profit_lost_supplier',
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) { return $this->priceFormatExport($status_file, $entry->profit_lost_supplier ?? 0); }
            ]);

            CRUD::column([
                'label' => 'Margin (%)',
                'name'  => 'margin_percent',
                'type'  => 'closure',
                'function' => function ($entry) { return number_format($entry->margin_percent ?? 0, 2, ',', '.') . '%'; }
            ]);

            CRUD::column([
                'label' => 'Status Pengiriman',
                'name'  => 'delivery_status',
                'type'  => 'text',
            ]);
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
            'items'   => $all_items,
            'title'   => "DAFTAR LAPORAN LABA RUGI PROYEK"
        ])->setPaper('A4', 'landscape');

        $fileName = 'laba_rugi_proyek_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) { echo $pdf->output(); }, $fileName, [
            'Content-Type'        => 'application/pdf',
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
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }

    public function exportDetailPdf()
    {
        $id = request()->id;
        $profitLost = ProjectProfitLost::findOrFail($id);

        $isSupplier = ($profitLost->orderable_type === \App\Models\InvoiceClient::class);

        if ($isSupplier) {
            $pdf = Pdf::loadView('exports.profit-lost-supplier-detail', [
                'profit_lost' => $profitLost,
                'report'      => $this->total_detail_supplier($id)
            ])->setPaper('A4', 'portrait');

            $fileName = 'laporan-laba-rugi-supplier_' . now()->format('Ymd_His') . '.pdf';
        } else {
            $pdf = Pdf::loadView('exports.profit-lost-detail', [
                'profit_lost' => $profitLost,
                'report'      => $this->total_detail_project($id)
            ])->setPaper('A4', 'portrait');

            $fileName = 'laporan-laba-rugi_' . now()->format('Ymd_His') . '.pdf';
        }

        return response()->streamDownload(function () use ($pdf) { echo $pdf->output(); }, $fileName, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportDetailExcel()
    {
        $id = request()->id;
        $profitLost = ProjectProfitLost::findOrFail($id);

        $isSupplier = ($profitLost->orderable_type === \App\Models\InvoiceClient::class);

        if ($isSupplier) {
            $report = $this->total_detail_supplier($id, 1);
            $name = "Laporan-laba-rugi-supplier-detail.xlsx";

            return response()->streamDownload(function () use ($profitLost, $report) {
                echo Excel::raw(new ProfitLostSupplierExcel($profitLost, $report), \Maatwebsite\Excel\Excel::XLSX);
            }, $name, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $name . '"',
            ]);
        } else {
            $report = $this->total_detail_project($id, 1);
            $name = "Laporan-laba-rugi-proyek-detail.xlsx";

            return response()->streamDownload(function () use ($profitLost, $report) {
                echo Excel::raw(new ProfitLostExcel($profitLost, $report), \Maatwebsite\Excel\Excel::XLSX);
            }, $name, [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="' . $name . '"',
            ]);
        }
    }

    public function exportConsolidationPdf()
    {
        $pdf = Pdf::loadView('exports.profit-lost-consolidation-pdf', [
            'data' => $this->total_report_account_profit_lost_ajax(),
        ])->setPaper('A4', 'portrait');

        $fileName = 'laba-rugi-konsolidasi_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) { echo $pdf->output(); }, $fileName, [
            'Content-Type'        => 'application/pdf',
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
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }
}
