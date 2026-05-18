<?php

namespace App\Http\Controllers\Admin;

use App\Models\Setting;
use App\Models\Voucher;
use App\Models\ClientPo;
use App\Models\InvoiceClient;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Exports\ExportExcel;
use App\Http\Helpers\CustomHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\ClientPoRequest;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\FormaterExport;
use App\Http\Controllers\Operation\PermissionAccess;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

use App\DTOs\ClientManagement\ClientPoData;
use App\DTOs\ClientManagement\ClientPoFilterData;
use App\Services\ClientManagement\ClientPoService;
use App\Repositories\ClientManagement\ClientPoRepository;
use App\Repositories\ClientManagement\ClientQuotationRepository;
use App\DTOs\ClientManagement\QuotationSelectionRequestData;
use App\Models\Company;
use Illuminate\Http\Request;

/**
 * Class ClientPoCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ClientPoCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;
    use FormaterExport;

    protected $clientPoService;
    protected $clientPoRepository;
    protected $quotationRepository;

    public function __construct(
        ClientPoService $clientPoService,
        ClientPoRepository $clientPoRepository,
        ClientQuotationRepository $quotationRepository
    ) {
        parent::__construct();
        $this->clientPoService = $clientPoService;
        $this->clientPoRepository = $clientPoRepository;
        $this->quotationRepository = $quotationRepository;
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(ClientPo::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/client/po');
        CRUD::setEntityNameStrings(trans('backpack::crud.client_po.title_header'), trans('backpack::crud.client_po.title_header'));

        $allAccess = [
            'AKSES SEMUA MENU ACCOUNTING',
            'AKSES MENU CLIENT',
        ];

        $viewMenu = [
            'MENU INDEX CLIENT PO',
        ];

        $this->settingPermission([
            'create' => [
                'CREATE INDEX CLIENT PO',
                ...$allAccess
            ],
            'update' => [
                'UPDATE INDEX CLIENT PO',
                ...$allAccess
            ],
            'delete' => [
                'DELETE INDEX CLIENT PO',
                ...$allAccess
            ],
            'list' => $viewMenu,
            'show' => $viewMenu,
            'print' => true,
        ]);

        CRUD::addButtonFromView('line', 'print_po_client', 'print_po_client', 'beginning');
    }

    public function index()
    {
        $this->crud->hasAccessOrFail('list');

        $this->crud->filter('status_invoice11crudTable-client_po')
            ->label(trans('backpack::crud.client_po.column.list_invoice'))
            ->type('select2')
            ->values([
                '1' => 'ADA',
                '0' => 'TIDAK ADA',
            ]);

        $this->crud->filter('date_po11crudTable-client_po')
            ->label(trans('backpack::crud.client_po.column.date_po'))
            ->type('date');

        $this->card->addCard([
            'name' => 'client_po',
            'line' => 'top',
            'view' => 'crud::components.datatable-origin',
            'params' => [
                'filter' => true,
                'crud_custom' => $this->crud,
                'hide_title' => true,
                'columns' => [
                    [
                        'name'      => 'row_number',
                        'type'      => 'row_number',
                        'label'     => 'No',
                        'orderable' => false,
                    ],
                    ...(backpack_user()->hasRole('Super Admin') ? [[
                        'label' => trans('backpack::crud.subkon.column.company'),
                        'type'      => 'text',
                        'name'      => 'company.name',
                    ]] : []),
                    [
                        'name'      => 'work_code',
                        'type'      => 'text',
                        'label'     => trans('backpack::crud.client_po.column.work_code'),
                        'orderable' => true,
                    ],
                    [
                        'label' => trans('backpack::crud.client_po.column.client_id'),
                        'type'      => 'text',
                        'name'      => 'client_id',
                        'orderable' => true,
                    ],
                    [
                        'name'      => 'reimburse_type',
                        'type'      => 'text',
                        'label'     => trans('backpack::crud.client_po.column.reimburse_type'),
                        'orderable' => true,
                    ],
                    [
                        'name'      => 'po_number',
                        'type'      => 'text',
                        'label'     => trans('backpack::crud.client_po.column.po_number'),
                        'orderable' => true,
                        'limit'     => 40,
                    ],
                    [
                        'name'      => 'job_name',
                        'type'      => 'text',
                        'label'     => trans('backpack::crud.client_po.column.job_name'),
                        'orderable' => true,
                    ],
                    [
                        'name'      => 'rap_value',
                        'type'      => 'text',
                        'label'     => trans('backpack::crud.client_po.column.rap_value'),
                        'orderable' => true,
                    ],
                    [
                        'name'      => 'job_value_exclude_ppn',
                        'type'      => 'text',
                        'label'     => trans('backpack::crud.client_po.column.job_value_exclude_ppn'),
                        'orderable' => true,
                    ],
                    [
                        'name'      => 'job_value_include_ppn',
                        'type'      => 'text',
                        'label'     => trans('backpack::crud.client_po.column.job_value_include_ppn'),
                        'orderable' => true,
                    ],
                    [
                        'name'      => 'start_date,end_date',
                        'type'      => 'text',
                        'label'     => trans('backpack::crud.client_po.column.start_date_end_date'),
                        'orderable' => false,
                    ],
                    [
                        'label'  => trans('backpack::crud.client_po.column.date_po'),
                        'name' => 'date_po',
                        'type'  => 'date',
                        'orderable' => true,
                    ],
                    [
                        'name'      => 'document_path',
                        'type'      => 'text',
                        'label'     => trans('backpack::crud.client_po.column.document_path'),
                        'orderable' => false,
                    ],
                    [
                        'name'      => 'category',
                        'type'      => 'text',
                        'label'     => trans('backpack::crud.client_po.column.category'),
                        'orderable' => true,
                    ],
                    [
                        'name' => 'list_invoice',
                        'type' => 'text',
                        'label' => trans('backpack::crud.client_po.column.list_invoice'),
                    ],
                    [
                        'name' => 'action',
                        'type' => 'action',
                        'label' =>  trans('backpack::crud.actions'),
                    ]
                ],
                'filter_table' => collect($this->crud->filters())->slice(0, 2),
                'route' => backpack_url('/client/po/search'),
            ]
        ]);

        $this->card->addCard([
            'name' => 'client_po-plugin',
            'line' => 'top',
            'view' => 'crud::components.client_po-plugin',
            'parent_view' => 'crud::components.filter-parent',
            'params' => [],
        ]);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = "PO Client";
        $this->data['title_modal_edit'] = "PO Client";
        $this->data['title_modal_delete'] = "PO Client";
        $this->data['cards'] = $this->card;
        $breadcrumbs = [
            'Client' => backpack_url('client'),
            'PO' => backpack_url($this->crud->route)
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;
        $this->data['year_options'] = CustomHelper::getYearOptions('client_po', 'date_po');

        $list = "crud::list-blank" ?? $this->crud->getListView();

        return view($list, $this->data);
    }

    public function countAllPPn()
    {
        $request = request();
        $filters = ClientPoFilterData::fromRequest($request);
        $summary = $this->clientPoRepository->getSummaryValues($filters);

        return response()->json([
            'total_job_value' => CustomHelper::formatRupiahWithCurrency($summary['total_job_value']),
            'total_job_value_ppn' => CustomHelper::formatRupiahWithCurrency($summary['total_job_value_ppn'])
        ]);
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

    public function select2Client()
    {
        $this->crud->hasAccessOrFail('create');

        $request = request();
        $search = $request->input('q');

        $query = \App\Models\Client::select(['id', 'name']);

        if ($request->has('company_id')) {
            $company_id = $request->input('company_id');
            $query->where('company_id', $company_id);
        }

        $dataset = $query->where('name', 'LIKE', "%$search%")
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

    public function select_count_without_po()
    {
        $po = ClientPo::select(DB::raw("COUNT(id) as count"))
            ->where('status', 'TANPA PO')->first();
        return response()->json([
            'count' => $po->count + 1,
        ]);
    }

    public function calculateClientPo($request)
    {
        $nilaiPekerjaan = floatval(str_replace(',', '', $request->input('job_value')));
        $ppn = floatval($request->input('tax_ppn'));

        $nilaiPpn = ($ppn == 0) ? 0 : ($nilaiPekerjaan * ($ppn / 100));
        $total = $nilaiPekerjaan + $nilaiPpn;

        $totalBiaya = floatval(str_replace(',', '', $request->input('price_total')));
        $labaRugiPo = $nilaiPekerjaan - $totalBiaya;

        $bebanUmum = floatval(str_replace(',', '', $request->input('load_general_value')));
        $labaRugiAkhir = $labaRugiPo - $bebanUmum;

        // Simpan ke database atau kirim balik ke view
        return [
            'price_after_year' => 0,
            'price_total' => 0,
            'load_general_value' => 0,
            'job_value_include_ppn' => $total,
            'profit_and_loss' => 0,
            'profit_and_loss_final' => 0,
        ];
    }

    public function store()
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        // Specific logic for TANPA PO
        if ($request->status == 'TANPA PO') {
            $setting = Setting::first();
            $request->merge([
                'po_number' => $setting->work_code_prefix,
            ]);
        }

        try {
            $data = ClientPoData::fromRequest($request);
            $item = $this->clientPoService->createClientPo($data);

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
                    'events' => $this->clientPoService->getUIEvents($item, 'create'),
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
            $data = ClientPoData::fromRequest($request);
            $item = $this->clientPoService->updateClientPo($request->get($this->crud->model->getKeyName()), $data);

            \Alert::success(trans('backpack::crud.update_success'))->flash();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
                    'events' => $this->clientPoService->getUIEvents($item, 'update'),
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

        $this->crud->file_title_export_pdf = "Laporan_daftar_client_po.pdf";
        $this->crud->file_title_export_excel = "Laporan_daftar_client_po.xlsx";
        $this->crud->param_uri_export = "?export=1";

        CRUD::addButtonFromView('top', 'export-excel-table', 'export-excel-table', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-table', 'export-pdf-table', 'beginning');
        CRUD::addButtonFromView('top', 'filter_year', 'filter-year', 'beginning');

        $new_format_date = 'DD/MM/YYYY';
        $request = request();
        CRUD::disableResponsiveTable();

        $status_file = '';
        if (strpos(url()->current(), 'excel')) {
            $status_file = 'excel';
        } else {
            $status_file = 'pdf';
        }


        // filter
        $filters = ClientPoFilterData::fromRequest($request);
        $this->crud->query = $this->clientPoRepository->getFilteredData($filters);

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
                'label'  => trans('backpack::crud.client_po.column.work_code'),
                'name' => 'work_code',
                'type'  => 'text'
            ],
        );

        CRUD::column([
            // 1-n relationship
            'label' => trans('backpack::crud.client_po.column.client_id'),
            'type'      => 'select',
            'name'      => 'client_id', // the column that contains the ID of that connected entity;
            'entity'    => 'client', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\Client", // foreign key model
            // OPTIONAL
            // 'limit' => 32, // Limit the number of characters shown
        ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.reimburse_type'),
                'name' => 'reimburse_type',
                'type'  => 'text'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.po_number'),
                'name' => 'po_number',
                'type'  => 'text',
                'limit'  => 40,
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.job_name'),
                'name' => 'job_name',
                'type'  => 'wrap_text'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.rap_value'),
                'name' => 'rap_value',
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return $this->priceFormatExport($status_file, $entry->rap_value);
                },
                // 'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.job_value_exclude_ppn'),
                'name' => 'job_value',
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return $this->priceFormatExport($status_file, $entry->job_value);
                },
                // 'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.job_value_include_ppn'),
                'name' => 'job_value_include_ppn',
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return $this->priceFormatExport($status_file, $entry->job_value_include_ppn);
                },
                // 'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.startdate_and_enddate'),
                'name' => 'start_date,end_date',
                'type'  => 'date_range_custom',
                'format' => $new_format_date,
            ],
        );

        CRUD::column([
            'label'  => trans('backpack::crud.client_po.column.date_po'),
            'name' => 'date_po',
            'type'  => 'date',
            'format' => $new_format_date,
        ]);

        CRUD::column([
            'name'   => 'document_path',
            'type'   => 'upload',
            'label'  => trans('backpack::crud.client_po.column.document_path'),
            'disk'   => 'public',
        ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.category'),
                'name' => 'category',
                'type'  => 'text'
            ],
        );

        CRUD::addColumn([
            'name' => 'list_invoice',
            'label' => trans('backpack::crud.client_po.column.list_invoice'),
            'type' => 'custom_html',
            'value' => function ($entry) {
                $count_data = $entry->invoices->count();
                if ($count_data > 0) {
                    return "ADA";
                }
                return 'TIDAK ADA';
            },
            'orderable'  => true,
            'orderLogic' => function ($query, $column, $columnDirection) {
                $invoice = InvoiceClient::select(DB::raw('client_po_id, count(invoice_number) as total_invoice'))
                    ->groupBy('client_po_id');
                return $query->leftJoinSub($invoice, 'invoices', function ($join) {
                    $join->on('client_po.id', 'invoices.client_po_id');
                })->select('client_po.*')->orderBy('invoices.total_invoice', $columnDirection);
            }
        ]);
    }

    private function setupListExport()
    {
        $this->crud->addColumn([
            'name'      => 'row_number',
            'type'      => 'export',
            'label'     => 'No',
            'orderable' => false,
            'wrapper' => [
                'element' => 'strong',
            ]
        ])->makeFirstColumn();

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.work_code'),
                'name' => 'work_code',
                'type'  => 'export'
            ],
        );

        CRUD::column([
            // 1-n relationship
            'label' => trans('backpack::crud.client_po.column.client_id'),
            'type'      => 'closure',
            'name'      => 'client_id', // the column that contains the ID of that connected entity;
            'entity'    => 'client', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\Client", // foreign key model
            'function' => function ($client_id) {
                return $client_id?->client?->name;
            }
        ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.reimburse_type'),
                'name' => 'reimburse_type',
                'type'  => 'export'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.po_number'),
                'name' => 'po_number',
                'type'  => 'export'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.job_name'),
                'name' => 'job_name',
                'type'  => 'export'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.rap_value'),
                'name' => 'rap_value',
                'type'  => 'export',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.job_value_exclude_ppn'),
                'name' => 'job_value',
                'type'  => 'export',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.job_value_include_ppn'),
                'name' => 'job_value_include_ppn',
                'type'  => 'export',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => 'Start Date',
                'name' => 'start_date',
                'type'  => 'text'
            ],
        );

        CRUD::column(
            [
                'label'  => "End Date",
                'name' => 'end_date',
                'type'  => 'text'
            ],
        );
        // CRUD::column([
        //     'name'   => 'document_path',
        //     'type'   => 'upload',
        //     'label'  => trans('backpack::crud.client_po.column.document_path'),
        //     'disk'   => 'public',
        // ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.category'),
                'name' => 'category',
                'type'  => 'export'
            ],
        );
    }

    public function exportPdf()
    {

        // $this->setupListExport();
        $this->setupListOperation();

        $columns = $this->crud->columns();
        $items =  $this->crud->getEntries();

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
                $item_value = CustomHelper::clean_html($item_value);
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $title = "DAFTAR CLIENT PO";

        $pdf = Pdf::loadView('exports.table-pdf', [
            'columns' => $columns,
            'items' => $all_items,
            'title' => $title
        ])->setPaper('A4', 'landscape');

        $fileName = 'vendor_po_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportExcel()
    {

        // $this->setupListExport();
        $this->setupListOperation();

        $columns = $this->crud->columns();
        $items =  $this->crud->getEntries();

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
                $item_value = CustomHelper::clean_html($item_value);
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $name = 'DAFTAR CLIENT PO';

        return response()->streamDownload(function () use ($columns, $items, $all_items) {
            echo Excel::raw(new ExportExcel(
                $columns,
                $all_items
            ), \Maatwebsite\Excel\Excel::XLSX);
        }, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Download Failure',
        ], 400);
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(ClientPoRequest::class);

        // Script to widen modal if this is an inline create
        \Backpack\CRUD\app\Library\Widget::add([
            'type' => 'script',
            'content' => '
                $(document).ready(function() {
                    // Try to find the parent modal and widen it
                    let modal = window.parent.$(".modal-dialog");
                    if (modal.length) {
                        modal.addClass("modal-xl").css("max-width", "90%");
                    }
                    // Also try current window just in case
                    $(".modal-dialog", window.parent.document).addClass("modal-xl").css("max-width", "90%");
                });
            '
        ]);

        if (backpack_user()->hasRole('Super Admin')) {
            $companies = \App\Models\Company::pluck('name', 'id')->toArray();
            CRUD::addField([
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'select2_array',
                'name'      => 'company_id',
                'options'   => ['' => trans('backpack::crud.filter.all_company') ?? 'All (Semua Perusahaan)'] + $companies,
                'wrapper'   => [
                    'class' => 'form-group col-md-6',
                ],
            ]);
        }

        CRUD::addField([
            'name' => 'is_from_quotation',
            'label' => trans('backpack::crud.client_po.field.is_from_quotation.label') ?? 'Pilih dari Penawaran (Client Quotation)',
            'type' => 'checkbox',
        ]);

        CRUD::addField([
            'name' => 'quotation_ids',
            'type' => 'hidden',
        ]);

        CRUD::addField([
            'name' => 'quotation_selection',
            'type' => 'select_quotation_table',
            'label' => trans('backpack::crud.client_po.field.quotation_selection.label') ?? 'Tabel Penawaran',
            'wrapper' => [
                'class' => 'form-group col-md-12 quotation-segment'
            ]
        ]);

        $this->setupCreateManualPo();

    }


    private function setupCreateManualPo()
    {
        // CRUD::setValidation(ClientPoRequest::class);
        $settings = Setting::first();

        $po_prefix = [];
        $work_code_prefix = [];
        $work_code_disabled = [
            // 'disabled' => true,
        ];
        $po_number_disabled = [
            'disabled' => true,
        ];
        if (!$this->crud->getCurrentEntryId()) {
            if ($settings?->po_prefix) {
                $po_prefix = [
                    'value' => $settings->po_prefix,
                ];
            }
            if ($settings?->work_code_prefix) {
                $work_code_prefix = [
                    'value' => $settings->work_code_prefix,
                ];
            }
            $work_code_disabled = [];
            $po_number_disabled = [];
        } else {
            $id = $this->crud->getCurrentEntryId();
            $voucher_exists = Voucher::where('client_po_id', $id)
                ->first();
            if ($voucher_exists) {
                $work_code_disabled = [
                    'disabled' => true,
                ];
            }
        }


        // CRUD::setFromDb(); // set fields from db columns.
        CRUD::field([   // 1-n relationship
            'label'       => trans('backpack::crud.client_po.field.client_id.label'), // Table column heading
            'type'        => "select2_ajax_custom",
            'name'        => 'client_id', // the column that contains the ID of that connected entity
            'entity'      => 'client', // the method that defines the relationship in your Model
            'attribute'   => "name", // foreign key attribute that is shown to user
            'data_source' => backpack_url('client/select2-client'), // url to controller search function (with /{id} should return a single entry)
            'dependencies' => ['company_id'], // make it dependent on company_id
            'include_all_form_fields' => true,
            'wrapper'   => [
                'class' => 'form-group col-md-6 manual-segment',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.client_po.field.client_id.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'work_code',
            'label' => trans('backpack::crud.client_po.field.work_code.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6 manual-segment',
            ],
            'attributes' => [
                ...$work_code_disabled,
                'placeholder' => trans('backpack::crud.client_po.field.work_code.placeholder'),
            ],
            ...$work_code_prefix,
        ]);

        CRUD::addField([  // Select2
            'label'     => trans('backpack::crud.client_po.field.status.label'),
            'type'      => 'select2_array',
            'name'      => 'status',
            'options'   => [
                'ADA PO' => 'ADA PO',
                'TANPA PO' => 'TANPA PO',
            ], // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
            'wrapper' => [
                'class' => 'form-group col-md-6 manual-segment'
            ]
        ]);

        CRUD::addField([
            'name' => 'po_number',
            'label' => trans('backpack::crud.client_po.field.po_number.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6 manual-segment',
                'placeholder' => trans('backpack::crud.client_po.field.po_number.placeholder')
            ],
            'attributes' => [
                ...$po_number_disabled,
                'placeholder' => trans('backpack::crud.client_po.field.po_number.placeholder')
            ],
            ...$po_prefix,
        ]);


        CRUD::addField([
            'name' => 'job_name',
            'label' => trans('backpack::crud.client_po.field.job_name.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12 manual-segment',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.client_po.field.job_name.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'rap_value',
            'label' => trans('backpack::crud.client_po.column.rap_value'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp",
            'wrapper'   => [
                'class' => 'form-group col-md-6 manual-segment',
            ],
            'attributes' => [
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'job_value',
            'label' => trans('backpack::crud.client_po.field.job_value.label'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp",
            'wrapper'   => [
                'class' => 'form-group col-md-6 manual-segment',
            ],
            'attributes' => [
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'tax_ppn',
            'label' => trans('backpack::crud.client_po.field.tax_ppn.label'),
            'type' => 'number',
            // optionals
            'attributes' => ["step" => "any"], // allow decimals
            'prefix'     => "%",
            // 'suffix'     => ".00",
            'wrapper'   => [
                'class' => 'form-group col-md-2 manual-segment',
            ],
            'attributes' => [
                'placeholder' => '0',
            ]
        ]);

        CRUD::addField([   // Hidden
            'name'  => 'space_1',
            'type'  => 'hidden',
            'value' => 'active',
            'wrapper'   => [
                'class' => 'form-group col-md-2 manual-segment'
            ],
            'attributes' => [
                'disabled'  => 'disabled',
                // 'placeholder' => trans('backpack::crud.spk.field.')
            ]
        ]);

        CRUD::addField([   // Hidden
            'name'  => 'space_2',
            'type'  => 'hidden',
            'value' => 'active',
            'wrapper'   => [
                'class' => 'form-group col-md-2 manual-segment'
            ],
            'attributes' => [
                'disabled'  => 'disabled',
                // 'placeholder' => trans('backpack::crud.spk.field.')
            ]
        ]);

        CRUD::addField([
            'name' => 'job_value_include_ppn',
            'label' => trans('backpack::crud.client_po.column.job_value_include_ppn_2'),
            'type' => 'text',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            // optionals
            'attributes' => [
                'disabled' => true,
            ], // allow decimals
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp",
            'wrapper'   => [
                'class' => 'form-group col-md-6 manual-segment'
            ],
        ]);

        CRUD::addField([
            'name'  => 'date_po',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.client_po.field.date_po.label'),
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6 manual-segment'
            ],
        ]);

        CRUD::field([   // date_range
            'name'  => 'start_date,end_date', // db columns for start_date & end_date
            'label' => trans('backpack::crud.client_po.field.startdate_and_enddate.label'),
            'type'  => 'date_range',

            'date_range_options' => [
                'drops' => 'down', // can be one of [down/up/auto]
                // 'locale' => ['format' => 'DD/MM/YYYY']
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6 manual-segment',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.client_po.field.startdate_and_enddate.placeholder'),
            ]
        ]);

        CRUD::field([
            'name'        => 'reimburse_type',
            'label'       => trans('backpack::crud.client_po.field.reimburse_type.label'),
            'type'        => 'select_from_array',
            'options'     => ['' => trans('backpack::crud.client_po.field.reimburse_type.placeholder'), 'REIMBURSE' => 'REIMBURSE', 'NON REIMBURSE' => 'NON REIMBURSE'],
            'allows_null' => false,
            'wrapper'   => [
                'class' => 'form-group col-md-6 manual-segment',
            ],
            // 'allows_multiple' => true, // OPTIONAL; needs you to cast this to array in your model;
        ]);


        CRUD::addField([
            'name' => 'document_path',
            'label' => trans('backpack::crud.client_po.field.document_path.label'),
            'type' => 'upload',
            'hint' => trans('backpack::crud.client_po.field.document_path.hint'),
            'attributes' => [
                'accept' => '.pdf'
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6 manual-segment'
            ],
            'disk' => 'public',
            'custom_upload' => true,
        ]);

        CRUD::addField([  // Select2
            'label'     => trans('backpack::crud.client_po.column.category'),
            'type'      => 'select2_array',
            'name'      => 'category',
            'options'   => [
                '' => trans('backpack::crud.voucher.field.payment_type.placeholder'),
                'RUTIN' => 'RUTIN',
                'NON RUTIN' => 'NON RUTIN',
            ], // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
            'wrapper' => [
                'class' => 'form-group col-md-6 manual-segment'
            ]
        ]);

        CRUD::addField([
            'name' => 'logic_client_po',
            'type' => 'logic_client_po',
        ]);

       
    }

    

    protected function setupUpdateOperation()
    {
        CRUD::setValidation(ClientPoRequest::class);
        $settings = Setting::first();

        $po_prefix = [];
        $work_code_prefix = [];
        $work_code_disabled = [];
        $po_number_disabled = [];

        $id = $this->crud->getCurrentEntryId();
        $voucher_exists = Voucher::where('client_po_id', $id)->first();
        if ($voucher_exists) {
            $work_code_disabled = ['disabled' => true];
        }

        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::addField([
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'select',
                'name'      => 'company_id',
                'entity'    => 'company',
                'attribute' => 'name',
                'model'     => "App\Models\Company",
                'wrapper'   => [
                    'class' => 'form-group col-md-6',
                ],
            ]);
        }

        CRUD::field([
            'label'       => trans('backpack::crud.client_po.field.client_id.label'),
            'type'        => "select2_ajax_custom",
            'name'        => 'client_id',
            'entity'      => 'client',
            'attribute'   => "name",
            'data_source' => backpack_url('client/select2-client'),
            'dependencies' => ['company_id'],
            'include_all_form_fields' => true,
            'wrapper'   => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'work_code',
            'label' => trans('backpack::crud.client_po.field.work_code.label'),
            'type' => 'text',
            'wrapper'   => ['class' => 'form-group col-md-6'],
            'attributes' => $work_code_disabled,
        ]);

        CRUD::addField([
            'label'     => trans('backpack::crud.client_po.field.status.label'),
            'type'      => 'select2_array',
            'name'      => 'status',
            'options'   => ['ADA PO' => 'ADA PO', 'TANPA PO' => 'TANPA PO'],
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'po_number',
            'label' => trans('backpack::crud.client_po.field.po_number.label'),
            'type' => 'text',
            'wrapper'   => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'job_name',
            'label' => trans('backpack::crud.client_po.field.job_name.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.client_po.field.job_name.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'rap_value',
            'label' => trans('backpack::crud.client_po.column.rap_value'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp",
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'job_value',
            'label' => trans('backpack::crud.client_po.field.job_value.label'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp",
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'tax_ppn',
            'label' => trans('backpack::crud.client_po.field.tax_ppn.label'),
            'type' => 'number',
            // optionals
            'attributes' => ["step" => "any"], // allow decimals
            'prefix'     => "%",
            // 'suffix'     => ".00",
            'wrapper'   => [
                'class' => 'form-group col-md-2',
            ],
            'attributes' => [
                'placeholder' => '0',
            ]
        ]);

        CRUD::addField([   // Hidden
            'name'  => 'space_1',
            'type'  => 'hidden',
            'value' => 'active',
            'wrapper'   => [
                'class' => 'form-group col-md-2'
            ],
            'attributes' => [
                'disabled'  => 'disabled',
                // 'placeholder' => trans('backpack::crud.spk.field.')
            ]
        ]);

        CRUD::addField([   // Hidden
            'name'  => 'space_2',
            'type'  => 'hidden',
            'value' => 'active',
            'wrapper'   => [
                'class' => 'form-group col-md-2'
            ],
            'attributes' => [
                'disabled'  => 'disabled',
                // 'placeholder' => trans('backpack::crud.spk.field.')
            ]
        ]);

        CRUD::addField([
            'name' => 'job_value_include_ppn',
            'label' => trans('backpack::crud.client_po.column.job_value_include_ppn_2'),
            'type' => 'text',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            // optionals
            'attributes' => [
                'disabled' => true,
            ], // allow decimals
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp",
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name'  => 'date_po',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.client_po.field.date_po.label'),
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::field([   // date_range
            'name'  => 'start_date,end_date', // db columns for start_date & end_date
            'label' => trans('backpack::crud.client_po.field.startdate_and_enddate.label'),
            'type'  => 'date_range',

            'date_range_options' => [
                'drops' => 'down', // can be one of [down/up/auto]
                // 'locale' => ['format' => 'DD/MM/YYYY']
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.client_po.field.startdate_and_enddate.placeholder'),
            ]
        ]);

        CRUD::field([
            'name'        => 'reimburse_type',
            'label'       => trans('backpack::crud.client_po.field.reimburse_type.label'),
            'type'        => 'select_from_array',
            'options'     => ['' => trans('backpack::crud.client_po.field.reimburse_type.placeholder'), 'REIMBURSE' => 'REIMBURSE', 'NON REIMBURSE' => 'NON REIMBURSE'],
            'allows_null' => false,
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            // 'allows_multiple' => true, // OPTIONAL; needs you to cast this to array in your model;
        ]);

        // CRUD::addField([
        //     'name' => 'price_after_year',
        //     'label' => trans('backpack::crud.client_po.column.price_after_year'),
        //     'type' => 'mask',
        //     'mask' => '000.000.000.000.000.000',
        //     'mask_options' => [
        //         'reverse' => true
        //     ],
        //     'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp",
        //     'wrapper'   => [
        //         'class' => 'form-group col-md-6',
        //     ],
        //     'attributes' => [
        //         'placeholder' => '000.000',
        //     ]
        // ]);

        // CRUD::addField([
        //     'name' => 'price_total',
        //     'label' => trans('backpack::crud.client_po.field.price_total.label'),
        //     'type' => 'mask',
        //     'mask' => '000.000.000.000.000.000',
        //     'mask_options' => [
        //         'reverse' => true
        //     ],
        //     'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp",
        //     'wrapper'   => [
        //         'class' => 'form-group col-md-6',
        //     ],
        //     'attributes' => [
        //         'placeholder' => '000.000',
        //     ]
        // ]);

        // CRUD::addField([
        //     'name' => 'profit_and_loss',
        //     'label' => trans('backpack::crud.client_po.column.profit_and_loss'),
        //     'type' => 'mask',
        //     'mask' => '000.000.000.000.000.000',
        //     'mask_options' => [
        //         'reverse' => true
        //     ],
        //     'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp",
        //     'wrapper'   => [
        //         'class' => 'form-group col-md-6',
        //     ],
        //     'attributes' => [
        //         'placeholder' => '000.000',
        //         'disabled' => true,
        //     ]
        // ]);

        // CRUD::addField([
        //     'name' => 'load_general_value',
        //     'label' => trans('backpack::crud.client_po.column.load_general_value'),
        //     'type' => 'mask',
        //     'mask' => '000.000.000.000.000.000',
        //     'mask_options' => [
        //         'reverse' => true
        //     ],
        //     'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp",
        //     'wrapper'   => [
        //         'class' => 'form-group col-md-6 .load_general_value_wrapper',
        //     ],
        //     'attributes' => [
        //         'placeholder' => '000.000',
        //     ]
        // ]);

        // CRUD::addField([
        //     'name' => 'profit_and_lost_final',
        //     'label' => trans('backpack::crud.client_po.column.profit_and_lost_final'),
        //     'type' => 'mask',
        //     'mask' => '000.000.000.000.000.000',
        //     'mask_options' => [
        //         'reverse' => true
        //     ],
        //     'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp",
        //     'wrapper'   => [
        //         'class' => 'form-group col-md-6',
        //     ],
        //     'attributes' => [
        //         'placeholder' => '000.000',
        //         'disabled' => true,
        //     ]
        // ]);

        CRUD::addField([
            'name' => 'document_path',
            'label' => trans('backpack::crud.client_po.field.document_path.label'),
            'type' => 'upload',
            'hint' => trans('backpack::crud.client_po.field.document_path.hint'),
            'attributes' => [
                'accept' => '.pdf'
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
            'disk' => 'public',
            'custom_upload' => true,
            // 'withFiles' => [
            //     'disk' => 'public',
            //     'path' => 'document_client_po',
            //     'deleteWhenEntryIsDeleted' => true,
            // ],
        ]);

        // CRUD::field([   // date_picker
        //     'name'  => 'date_invoice',
        //     'type'  => 'date_picker',
        //     'label' => trans('backpack::crud.client_po.field.date_invoice.label'),

        //     // optional:
        //     'date_picker_options' => [
        //         'language' => App::getLocale(),
        //     ],
        //     'wrapper'   => [
        //         'class' => 'form-group col-md-6'
        //     ],
        // ]);

        CRUD::addField([  // Select2
            'label'     => trans('backpack::crud.client_po.column.category'),
            'type'      => 'select2_array',
            'name'      => 'category',
            'options'   => [
                '' => trans('backpack::crud.voucher.field.payment_type.placeholder'),
                'RUTIN' => 'RUTIN',
                'NON RUTIN' => 'NON RUTIN',
            ], // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'logic_client_po',
            'type' => 'logic_client_po',
        ]);

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */
    }


    protected function setupShowOperation()
    {
        $settings = Setting::first();
        $new_format_date = 'DD/MM/YYYY';

        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::field([
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'select',
                'name'      => 'company_id',
                'entity'    => 'company',
                'attribute' => 'name',
                'model'     => "App\Models\Company",
                'wrapper'   => [
                    'class' => 'form-group col-md-12',
                ],
            ]);

            CRUD::column([
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'select',
                'name'      => 'company_id',
                'entity'    => 'company',
                'attribute' => 'name',
                'model'     => "App\Models\Company",
            ]);
        }

        CRUD::field([   // 1-n relationship
            'label'       => trans('backpack::crud.client_po.field.client_id.label'), // Table column heading
            'type'        => "select2_ajax_custom",
            'name'        => 'client_id', // the column that contains the ID of that connected entity
            'entity'      => 'client', // the method that defines the relationship in your Model
            'attribute'   => "name", // foreign key attribute that is shown to user
            'data_source' => backpack_url('client/select2-client'), // url to controller search function (with /{id} should return a single entry)
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.client_po.field.client_id.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'work_code',
            'label' => trans('backpack::crud.client_po.field.work_code.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.client_po.field.work_code.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'po_number',
            'label' => trans('backpack::crud.client_po.field.po_number.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
                'placeholder' => trans('backpack::crud.client_po.field.po_number.placeholder')
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.client_po.field.po_number.placeholder')
            ]
        ]);

        CRUD::addField([
            'name' => 'job_name',
            'label' => trans('backpack::crud.client_po.field.job_name.label'),
            'type' => 'wrap_text',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.client_po.field.job_name.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'rap_value',
            'label' => trans('backpack::crud.client_po.column.rap_value'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => 'Rp',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'job_value',
            'label' => trans('backpack::crud.client_po.field.job_value.label'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => 'Rp',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'job_value_include_ppn',
            'label' => trans('backpack::crud.client_po.column.job_value_include_ppn_2'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            // optionals
            'attributes' => [
                'disabled' => true,
            ], // allow decimals
            'prefix'     => "Rp.",
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::field([   // date_range
            'name'  => 'start_date,end_date', // db columns for start_date & end_date
            'label' => trans('backpack::crud.client_po.field.startdate_and_enddate.label'),
            'type'  => 'date_range',
            'format' => $new_format_date,

            'date_range_options' => [
                'drops' => 'down', // can be one of [down/up/auto]
                // 'locale' => ['format' => 'DD/MM/YYYY']
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.client_po.field.startdate_and_enddate.placeholder'),
            ]
        ]);

        CRUD::field([
            'name'        => 'reimburse_type',
            'label'       => trans('backpack::crud.client_po.field.reimburse_type.label'),
            'type'        => 'select_from_array',
            'options'     => ['' => trans('backpack::crud.client_po.field.reimburse_type.placeholder'), 'REIMBURSE' => 'REIMBURSE', 'NON REIMBURSE' => 'NON REIMBURSE'],
            'allows_null' => false,
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            // 'allows_multiple' => true, // OPTIONAL; needs you to cast this to array in your model;
        ]);

        CRUD::addField([
            'name' => 'price_after_year',
            'label' => trans('backpack::crud.client_po.column.price_after_year'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => 'Rp',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'price_total',
            'label' => trans('backpack::crud.client_po.field.price_total.label'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => 'Rp',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'profit_and_loss',
            'label' => trans('backpack::crud.client_po.column.profit_and_loss'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => 'Rp',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '000.000',
                'disabled' => true,
            ]
        ]);

        CRUD::addField([
            'name' => 'profit_and_lost_final',
            'label' => trans('backpack::crud.client_po.column.profit_and_lost_final'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => 'Rp',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '000.000',
                'disabled' => true,
            ]
        ]);

        CRUD::addField([
            'name' => 'document_path',
            'label' => trans('backpack::crud.client_po.field.document_path.label'),
            'type' => 'upload',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
            'withFiles' => [
                'disk' => 'public',
                'path' => 'document_client_po',
                'deleteWhenEntryIsDeleted' => true,
            ],
        ]);

        CRUD::addField([  // Select2
            'label'     => trans('backpack::crud.client_po.column.category'),
            'type'      => 'select2_array',
            'name'      => 'category',
            'options'   => [
                '' => trans('backpack::crud.voucher.field.payment_type.placeholder'),
                'RUTIN' => 'RUTIN',
                'NON RUTIN' => 'NON RUTIN',
            ], // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);
        //
        CRUD::column([
            // 1-n relationship
            'label' => trans('backpack::crud.client_po.column.client_id'),
            'type'      => 'select',
            'name'      => 'client_id', // the column that contains the ID of that connected entity;
            'entity'    => 'client', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\Client", // foreign key model
            // OPTIONAL
            // 'limit' => 32, // Limit the number of characters shown
        ]);
        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.work_code'),
                'name' => 'work_code',
                'type'  => 'text'
            ],
        );
        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.po_number'),
                'name' => 'po_number',
                'type'  => 'text'
            ],
        );
        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.job_name'),
                'name' => 'job_name',
                'type'  => 'wrap_text'
            ],
        );
        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.rap_value'),
                'name' => 'rap_value',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );
        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.rap_value'),
                'name' => 'job_value',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );
        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.job_value_include_ppn'),
                'name' => 'job_value_include_ppn',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );
        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.startdate_and_enddate'),
                'name' => 'start_date,end_date',
                'type'  => 'date_range_custom',
                'format' => $new_format_date,
            ],
        );
        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.reimburse_type'),
                'name' => 'reimburse_type',
                'type'  => 'text'
            ],
        );
        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.price_after_year'),
                'name' => 'price_after_year',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );
        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.price_total'),
                'name' => 'price_total',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );
        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.profit_and_loss'),
                'name' => 'profit_and_loss',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );
        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.profit_and_lost_final'),
                'name' => 'profit_and_lost_final',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );
        CRUD::column([
            'label'  => trans('backpack::crud.client_po.field.document_path.label'),
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
        ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client_po.column.category'),
                'name' => 'category',
                'type'  => 'text'
            ],
        );
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

    public function getQuotations(Request $request)
    {
        $dto = QuotationSelectionRequestData::fromRequest($request);
        $result = $this->quotationRepository->getQuotationsForSelection($dto);

        // return json
        return response()->json([
            'draw' => $dto->draw,
            'recordsTotal' => $result['total'],
            'recordsFiltered' => $result['filtered'],
            'data' => $result['data']->map(function ($row) {
                return [
                    'id' => $row->id,
                    'work_code' => $row->work_code,
                    'client_name' => $row->client?->name ?? '-',
                    'job_name' => $row->job_name,
                    'job_value' => number_format($row->job_value, 0, ',', '.'),
                    'tax_ppn' => $row->tax_ppn,
                    'job_value_include_ppn' => number_format($row->job_value_include_ppn, 0, ',', '.'),
                ];
            })
        ]);
    }

    public function getQuotationDetails(Request $request)
    {
        $dto = QuotationSelectionRequestData::fromRequest($request);
        $quotations = $this->quotationRepository->getQuotationDetailsByIds($dto->ids);

        if ($quotations->isEmpty()) {
            return response()->json([]);
        }

        $job_names = $quotations->pluck('job_name')->unique()->toArray();

        return response()->json([
            'client_id' => $quotations->first()->client_id,
            'job_name' => implode(' | ', $job_names),
            'job_value' => $quotations->sum('job_value'),
            'rap_value' => $quotations->sum('rap_value'),
            'tax_ppn' => $quotations->first()->tax_ppn,
            'work_code' => $quotations->first()->work_code,
            'start_date' => $quotations->first()->start_date,
            'end_date' => $quotations->first()->end_date,
            'reimburse_type' => $quotations->first()->reimburse_type,
            'category' => $quotations->first()->category,
            'status' => $quotations->first()->status ?? 'ADA PO',
        ]);
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;

        $this->crud->delete($id);

        $messages['success'][] = trans('backpack::crud.delete_confirmation_message');
        $messages['events'] = [
            'crudTable-filter_client_po_plugin_load' => true,
            'crudTable-client_po_create_success' => true,
        ];
        return response()->json($messages);
    }

    public function printPo($id)
    {
        $this->crud->hasAccessOrFail('show');
        $entry = $this->crud->getEntry($id);
        $settings = Setting::first();

        $pdf = Pdf::loadView('exports.client-po-pdf', [
            'entry' => $entry,
            'settings' => $settings,
        ]);

        return $pdf->stream('PO-' . ($entry->po_number ?? $entry->id) . '.pdf');
    }
}
