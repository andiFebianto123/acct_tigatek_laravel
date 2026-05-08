<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\Invoice\InvoiceClientFilterData;
use App\DTOs\Invoice\InvoiceClientSaveData;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\FormaterExport;
use App\Http\Controllers\Operation\PermissionAccess;
use App\Http\Exports\ExportExcel;
use App\Http\Helpers\CustomHelper;
use App\Http\Helpers\CustomVoid;
use App\Http\Requests\InvoiceClientRequest;
use App\Models\ClientPo;
use App\Models\InvoiceClient;
use App\Models\InvoiceClientDetail;
use App\Models\LogPayment;
use App\Models\Setting;
use App\Repositories\Invoice\InvoiceClientRepository;
use App\Services\Invoice\InvoiceClientService;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class InvoiceClientCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class InvoiceClientCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;
    use FormaterExport;

    public function __construct(
        protected InvoiceClientRepository $repository,
        protected InvoiceClientService $service
    ) {
        parent::__construct();
    }
    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\InvoiceClient::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/invoice-client');
        CRUD::setEntityNameStrings(trans('backpack::crud.menu.invoice_client'), trans('backpack::crud.menu.invoice_client'));

        $base = 'INDEX INVOICE';
        $allAccess = ['AKSES SEMUA MENU ACCOUNTING'];
        $viewMenu  = ["MENU $base"];

        $this->settingPermission([
            'create' => ["CREATE $base", ...$allAccess],
            'update' => ["UPDATE $base", ...$allAccess],
            'delete' => ["DELETE $base", ...$allAccess],
            'void'   => ["VOID $base", ...$allAccess],
            'list'   => $viewMenu,
            'show'   => $viewMenu,
            'print'  => true,
        ]);
    }

    public function select2ClientPo()
    {
        $this->crud->hasAccessOrFail('create');

        $request = request();

        $search = $request->input('q');
        $company_id = $request->input('company_id');

        $query = \App\Models\ClientPo::select(['id', 'po_number']);

        if ($request->has('company_id')) {
            $query->where('company_id', $company_id);
        }

        $dataset = $query->where(function ($q) use ($search) {
            $q->where('po_number', 'LIKE', "%$search%")
                ->orWhere('work_code', 'like', "$search")
                ->orWhere('job_name', 'LIKE', "%$search%");
        })->paginate(10);

        $results = [];
        foreach ($dataset as $item) {
            $results[] = [
                'id' => $item->id,
                'text' => $item->po_number,
            ];
        }
        return response()->json(['results' => $results]);
    }

    public function selectedClientPo()
    {
        $this->crud->hasAccessOrFail('create');
        $id = request()->id;
        $entry = ClientPo::where('id', $id)->first();

        $entry->date_invoice = ($entry->date_invoice) ? Carbon::createFromFormat('Y-m-d', $entry->date_invoice)->format('d/m/Y') : Carbon::now()->format('d/m/Y');
        $entry->date_po_str = ($entry->date_po) ? Carbon::createFromFormat('Y-m-d', $entry->date_po)->format('d/m/Y') : Carbon::now()->format('d/m/Y');
        // $entry->job_value = CustomHelper::formatRupiah($entry->job_value);
        // $entry->total_value_with_tax = CustomHelper::formatRupiah($entry->job_value_include_ppn);
        $entry->client_name = $entry->client->name;
        return response()->json([
            'result' => $entry,
        ]);
    }

    private function getComponent()
    {
        if (backpack_user()->hasRole('Super Admin')) {
            $this->crud->filter('company_id11crudTable-invoice')
                ->label('Milik Perusahaan')
                ->type('select2')
                ->values(fn() => \App\Models\Company::pluck('name', 'id')->toArray());
        }

        $this->crud->filter('invoice_date11crudTable-invoice')
            ->label(trans('backpack::crud.invoice_client.column.invoice_date'))
            ->type('date');

        $this->crud->filter('po_date11crudTable-invoice')
            ->label(trans('backpack::crud.invoice_client.column.po_date'))
            ->type('date');

        $this->crud->filter('send_invoice_normal11crudTable-invoice')
            ->label(trans('backpack::crud.invoice_client.column.send_invoice_normal'))
            ->type('date');

        $this->crud->filter('send_invoice_revision11crudTable-invoice')
            ->label(trans('backpack::crud.invoice_client.column.send_invoice_revision'))
            ->type('date');

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
                'label' => trans('backpack::crud.subkon.column.company'),
                'name' => 'company',
                'type' => 'text',
                'orderable' => true,
            ];
        }

        $columns = array_merge($columns, [
            [
                'label'  => trans('backpack::crud.invoice_client.column.invoice_number'),
                'name' => 'invoice_number',
                'type'  => 'text',
                'orderlable' => true,
            ],
            [
                'label' => trans('backpack::crud.invoice_client.field.kdp.label'),
                'name' => 'kdp',
                'type'  => 'text',
                'orderable' => true,
            ],
            [
                'label'  => trans('backpack::crud.invoice_client.column.name'),
                'name' => 'name',
                'type'  => 'text',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.invoice_client.column.description'),
                'name' => 'description',
                'type' => 'text',
                'orderable' => true,
            ],
            [
                'label'  => trans('backpack::crud.invoice_client.column.invoice_date'),
                'name' => 'invoice_date',
                'type'  => 'text',
                'orderable' => true,
            ],
            [
                'label'  => trans('backpack::crud.invoice_client.column.client_po_id'),
                'name' => 'client_po_id',
                'type'  => 'text',
                'orderable' => true,
                'limit' => 40,
            ],
            [
                'label' => trans('backpack::crud.invoice_client.column.po_date'),
                'name' => 'po_date_from_po',
                'type' => 'text',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.invoice_client.column.client_id'),
                'type' => 'text',
                'name' => 'client_name',
                'orderable' => true,
            ],
            [
                'label'  => trans('backpack::crud.invoice_client.column.price_total_exclude_ppn'),
                'name' => 'price_total_exclude_ppn',
                'type'  => 'text',
                'orderable' => true,
            ],
            [
                'label'  => trans('backpack::crud.invoice_client.column.price_total_include_ppn'),
                'name' => 'price_total_include_ppn',
                'type'  => 'text',
                'orderable' => true,
            ],
            [
                'label'  => trans('backpack::crud.invoice_client.column.discount_pph'),
                'name' => 'discount_pph',
                'type'  => 'text',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.invoice_client.column.send_invoice_normal'),
                'name' => 'send_invoice_normal',
                'type'  => 'text',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.invoice_client.column.send_invoice_revision'),
                'name' => 'send_invoice_revision',
                'type'  => 'text',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.invoice_client.column.status'),
                'name' => 'status',
                'type' => 'text',
            ],
            [
                'label' => trans('backpack::crud.invoice_client.column.document_invoice'),
                'name' => 'invoice_document',
                'type' => 'text',
                'orderable' => false,
            ],
            [
                'name' => 'action',
                'type' => 'action',
                'label' =>  trans('backpack::crud.actions'),
                'width_box' => '150px',
            ]
        ]);

        $this->card->addCard([
            'name' => 'invoice',
            'line' => 'top',
            'view' => 'crud::components.datatable-origin',
            'params' => [
                'filter' => true,
                'crud_custom' => $this->crud,
                'hide_title' => true,
                'columns' => $columns,
                'filter_table' => collect($this->crud->filters())->slice(0, 4),
                'route' => backpack_url('invoice-client/search'),
            ]
        ]);

        $this->card->addCard([
            'name' => 'invoice-plugin',
            'line' => 'top',
            'view' => 'crud::components.invoice-plugin',
            'parent_view' => 'crud::components.filter-parent',
            'params' => [],
        ]);
    }

    public function total_price()
    {
        $dto = InvoiceClientFilterData::fromRequest(request());
        $totals = $this->repository->getTotals($dto);

        return response()->json([
            'total_price_exclude_ppn' => CustomHelper::formatRupiahWithCurrency($totals['total_price_exclude_ppn'] ?? 0),
            'total_price_include_ppn' => CustomHelper::formatRupiahWithCurrency($totals['total_price_include_ppn'] ?? 0),
            'total_discount_pph' => CustomHelper::formatRupiahWithCurrency($totals['total_discount_pph'] ?? 0),
        ]);
    }

    public function index()
    {
        $this->crud->hasAccessOrFail('list');

        $this->crud->param_uri_export = "?export=1";

        $this->getComponent();

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.invoice_client.title_modal_create');
        $this->data['title_modal_edit'] = trans('backpack::crud.invoice_client.title_modal_edit');
        $this->data['title_modal_delete'] = trans('backpack::crud.invoice_client.title_modal_delete');
        $this->data['cards'] = $this->card;

        $breadcrumbs = [
            'Invoice (client)' => backpack_url('invoice-client'),
            // trans('backpack::crud.menu.list_client') => backpack_url($this->crud->route)
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;
        $this->data['year_options'] = CustomHelper::getYearOptions('invoice_clients', 'invoice_date');

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

        $entry = $this->crud->getEntryWithLocale($id);
        $entry->po_date = Carbon::parse($entry->client_po->date_po)->format('d/m/Y');
        // $entry->client_name = $entry->client->name;
        $entry->price_total_exclude_ppn = $entry->price_total_exclude_ppn;
        $entry->price_total_include_ppn = $entry->price_total_include_ppn;

        $entry->invoice_client_details_edit = $entry->invoice_client_details;
        $entry->client_name = $entry->client_po->client->name;
        $entry->nominal_exclude_ppn = $entry->price_total_exclude_ppn;
        $entry->nominal_include_ppn = $entry->price_total_include_ppn;
        $entry->send_invoice_normal = $entry->send_invoice_normal_date;
        $entry->send_invoice_revision = $entry->send_invoice_revision_date;

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

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::disableResponsiveTable();
        CRUD::removeButtons(['delete', 'show', 'update'], 'line');
        $new_format_date = 'DD/MM/YYYY';

        $this->crud->file_title_export_pdf = "Laporan_invoice.pdf";
        $this->crud->file_title_export_excel = "Laporan_invoice.xlsx";
        $this->crud->param_uri_export = "?export=1";

        CRUD::addButtonFromView('top', 'export-excel-table', 'export-excel-table', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-table', 'export-pdf-table', 'beginning');
        CRUD::addButtonFromView('top', 'filter_paid_unpaid', 'filter-paid_unpaid', 'beginning');
        CRUD::addButtonFromView('top', 'filter_year', 'filter-year', 'beginning');
        CRUD::addButtonFromView('line', 'show', 'show', 'end');
        CRUD::addButtonFromView('line', 'update', 'update', 'end');
        CRUD::addButtonFromView('line', 'print', 'print', 'end');
        CRUD::addButtonFromView('line', 'delete', 'delete', 'end');
        CRUD::addButtonFromView('line', 'void_invoice', 'void_invoice', 'end');

        $this->repository->applyListQuery(
            $this->crud->query,
            InvoiceClientFilterData::fromRequest(request())
        );

        CRUD::addClause('select', [
            DB::raw("
                invoice_clients.*,
                log_void.id as payment_log_id,
                client_po.date_po as po_date_from_po,
                companies.name as company_name
            ")
        ]);

        $status_file = '';
        if (strpos(url()->current(), 'excel')) {
            $status_file = 'excel';
        } else {
            $status_file = 'pdf';
        }

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
                'label' => trans('backpack::crud.subkon.column.company'),
                'name' => 'company_name',
                'type' => 'text',
                'orderable' => true,
                'orderLogic' => function ($query, $column, $columnDir) {
                    return $query->orderBy('companies.name', $columnDir);
                },
            ]);
        }

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.column.invoice_number'),
                'name' => 'invoice_number',
                'type'  => 'text'
            ],
        );

        CRUD::column(
            [
                'label' => trans('backpack::crud.invoice_client.field.kdp.label'),
                'name' => 'kdp',
                'type'  => 'text'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.column.name'),
                'name' => 'name',
                'type'  => 'closure',
                'width_box' => '300px',
                'function' => function ($entry) {
                    return $entry->client_po?->job_name;
                },
                'orderable' => true,
                'orderLogic' => function ($query, $column, $columnDir) {
                    return $query->orderBy('client_po.job_name', $columnDir);
                },
            ],
        );

        CRUD::column(
            [
                'label' => trans('backpack::crud.invoice_client.column.description'),
                'name' => 'description',
                'type' => 'wrap_text'
            ]
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.column.invoice_date'),
                'name' => 'invoice_date',
                'type'  => 'date',
                'format' => $new_format_date,
            ],
        );

        CRUD::column([
            'label' => trans('backpack::crud.invoice_client.column.client_po_id'),
            'type'      => 'select',
            'name'      => 'client_po_id',
            'entity'    => 'client_po',
            'attribute' => 'po_number',
            'model'     => "App\Models\ClientPo",
            'limit' => 40,
        ]);

        CRUD::column(
            [
                'label' => trans('backpack::crud.invoice_client.column.po_date'),
                'name' => 'po_date_from_po',
                'type' => 'date',
                'format' => $new_format_date,
            ]
        );

        CRUD::column([
            'label' => trans('backpack::crud.invoice_client.column.client_id'),
            'type'      => 'closure',
            'name'      => 'client_name',
            'function' => function ($entry) {
                return $entry->client_po?->client?->name;
            }
        ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.column.price_total_exclude_ppn'),
                'name' => 'price_total_exclude_ppn',
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return $this->priceFormatExport($status_file, $entry->price_total_exclude_ppn);
                },
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.column.price_total_include_ppn'),
                'name' => 'price_total_include_ppn',
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return $this->priceFormatExport($status_file, $entry->price_total_include_ppn);
                },
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.column.discount_pph'),
                'name' => 'discount_pph',
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return $this->priceFormatExport($status_file, $entry->discount_pph);
                },
            ],
        );

        CRUD::column(
            [
                'label' => trans('backpack::crud.invoice_client.column.send_invoice_normal'),
                'name' => 'send_invoice_normal_date',
                'type'  => 'date',
                'format' => $new_format_date,
            ],
        );

        CRUD::column(
            [
                'label' => trans('backpack::crud.invoice_client.column.send_invoice_revision'),
                'name' => 'send_invoice_revision_date',
                'type'  => 'date',
                'format' => $new_format_date,
            ],
        );

        CRUD::column(
            [
                'label' => trans('backpack::crud.invoice_client.column.status'),
                'name' => 'status',
                'type' => 'text',
            ]
        );

        CRUD::column([
            'name'   => 'invoice_document',
            'type'   => 'upload',
            'label'  => trans('backpack::crud.client_po.column.document_path'),
            'disk'   => 'public',
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
            $query_clone = $this->crud->query->toBase()->clone();

            $outer_query = $query_clone->newQuery();
            $subQuery = $query_clone->cloneWithout(['limit', 'offset']);

            $totalEntryCount = $outer_query->select(DB::raw('count(*) as total_rows'))
                ->fromSub($subQuery, 'total_aggregator')->cursor()->first()->total_rows;
            $filteredEntryCount = $totalEntryCount;

            // $totalEntryCount = (int) (request()->get('totalEntryCount') ?: $this->crud->getTotalQueryCount());
            // $filteredEntryCount = $this->crud->getFilteredQueryCount() ?? $totalEntryCount;
        } else {
            $totalEntryCount = $length;
            $entryCount = $entries->count();
            $filteredEntryCount = $entryCount < $length ? $entryCount : $length + $start + 1;
        }

        // store the totalEntryCount in CrudPanel so that multiple blade files can access it
        $this->crud->setOperationSetting('totalEntryCount', $totalEntryCount);

        return $this->crud->getEntriesAsJsonForDatatables($entries, $totalEntryCount, $filteredEntryCount, $start);
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

        $title = "DAFTAR INVOICE";

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

        $name = 'DAFTAR INVOICE';

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
        CRUD::setValidation(InvoiceClientRequest::class);
        $settings = Setting::first();
        $inv_prefix_value = [];
        if (!$this->crud->getCurrentEntryId()) {
            $inv_prefix_value = [
                'value' => $settings?->invoice_prefix,
            ];
        }
        // CRUD::setFromDb(); // set fields from db columns.

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
            'name' => 'invoice_number',
            'label' => trans('backpack::crud.invoice_client.field.invoice_number.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.invoice_client.field.invoice_number.placeholder'),
            ],
            ...$inv_prefix_value,
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'invoice_date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.invoice_client.field.invoice_date.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.invoice_client.field.invoice_date.placeholder')
            ]
        ]);

        CRUD::addField([
            'name' => 'client_name',
            'label' => trans('backpack::crud.invoice_client.field.client_id.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.invoice_client.field.client_id.placeholder'),
                'disabled' => true,
            ]
        ]);

        CRUD::addField([
            'name' => 'address_po',
            'label' => trans('backpack::crud.invoice_client.field.address.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.invoice_client.field.address.placeholder'),
            ]
        ]);

        CRUD::addField([   // 1-n relationship
            'label'       => trans('backpack::crud.invoice_client.field.client_po_id.label'), // Table column heading
            'type'        => "select2_ajax_custom",
            'name'        => 'client_po_id', // the column that contains the ID of that connected entity
            'entity'      => 'client_po', // the method that defines the relationship in your Model
            'attribute'   => 'po_number', // foreign key attribute that is shown to user
            'data_source' => backpack_url('invoice-client/select2-client-po'), // url to controller search function (with /{id} should return a single entry)
            'dependencies' => ['company_id'],
            'include_all_form_fields' => true,
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'placeholder' => trans('backpack::crud.invoice_client.field.client_po_id.placeholder'),
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'po_date',
            'type'  => 'text',
            'label' => trans('backpack::crud.invoice_client.field.po_date.label'),

            'suffix' => '<i class="la la-calendar"></i>',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.invoice_client.field.po_date.placeholder'),
                'disabled' => true
            ]
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => trans('backpack::crud.invoice_client.field.description.label'),
            'type' => 'textarea',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.invoice_client.field.description.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'nominal_exclude_ppn',
            'label' => trans('backpack::crud.invoice_client.field.nominal_exclude_ppn.label'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.invoice_client.field.nominal_exclude_ppn.placeholder'),
                // 'disabled' => true,
            ]
        ]);

        CRUD::addField([
            'name' => 'dpp_other',
            'label' => trans('backpack::crud.invoice_client.field.dpp_other.label'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.invoice_client.field.dpp_other.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'tax_ppn',
            'label' => trans('backpack::crud.invoice_client.field.tax_ppn.label'),
            'type' => 'number',
            // optionals
            'attributes' => ["step" => "any"], // allow decimals
            'prefix'     => "%",
            // 'suffix'     => ".00",
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '0',
            ]
        ]);

        CRUD::addField([
            'name' => 'nominal_include_ppn',
            'label' => trans('backpack::crud.invoice_client.field.nominal_include_ppn.label'),
            'type' => 'text',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '000.000',
                'disabled' => true,
            ]
        ]);

        CRUD::addField([
            'name' => 'pph',
            'label' => trans('backpack::crud.invoice_client.field.pph.label'),
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
            'name' => 'discount_pph',
            'label' => trans('backpack::crud.invoice_client.field.discount_pph.label'),
            'type' => 'text',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'disabled' => true,
                'placeholder' => '000.000',
            ]
        ]);


        CRUD::addField([
            'name' => 'kdp',
            'label' => trans('backpack::crud.invoice_client.field.kdp.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.invoice_client.field.kdp.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name'        => 'withholding_agent',
            'label'       => trans('backpack::crud.invoice_client.field.withholding_agent.label'),
            'type'        => 'select_from_array',
            'options'     => ['WAPU' => 'WAPU', 'NON WAPU' => 'NON WAPU'],
            'allows_null' => true,
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'send_invoice_normal',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.invoice_client.field.send_invoice_normal.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'send_invoice_revision',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.invoice_client.field.send_invoice_revision.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);


        CRUD::addField([
            'name'        => 'status',
            'label'       => trans('backpack::crud.invoice_client.field.status.label'),
            'type'        => 'select_from_array',
            'options'     => ['' => trans('backpack::crud.invoice_client.field.status.placeholder'), 'Paid' => 'Paid', 'Unpaid' => 'Unpaid'],
            'allows_null' => false,
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'disabled' => true,
            ]
            // 'allows_multiple' => true, // OPTIONAL; needs you to cast this to array in your model;
        ]);

        CRUD::addField([
            'name' => 'space_2',
            'label' => '',
            'type' => 'hidden',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'nominal_information',
            'label' => trans('backpack::crud.invoice_client.field.nominal_information.label'),
            'type' => 'text',
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '000.000',
                'readonly' => true,
            ]
        ]);

        CRUD::addField([
            'name' => 'space_3',
            'label' => '',
            'type' => 'hidden',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'invoice_document',
            'label' => trans('backpack::crud.invoice_client.field.invoice_document.label'),
            'type' => 'upload',
            'upload' => true,
            'disk' => 'public',
            'prefix' => 'document_invoice/',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'accept' => '.pdf',
            ],
            'hint' => trans('backpack::crud.invoice_client.field.invoice_document.hint'),
        ]);

        $id = request()->segment(3);

        if ($id != 'create') {
            CRUD::addField([
                'name' => 'invoice_client_details_edit',
                'label' => trans('backpack::crud.invoice_client.field.item.label'),
                'type' => 'repeatable',
                'new_item_label'  => trans('backpack::crud.invoice_client.field.item.new_item_label'),
                'fields' => [
                    [
                        'name' => 'name',
                        'type' => 'text',
                        'label' => trans('backpack::crud.invoice_client.field.item.items.name.label'),
                        'wrapper' => [
                            'class' => 'form-group col-md-6',
                        ]
                    ],
                    [
                        'name' => 'price',
                        'type' => 'mask_repeat',
                        'label' => trans('backpack::crud.invoice_client.field.item.items.price.label'),
                        'wrapper' => [
                            'class' => 'form-group col-md-6',
                        ],
                        'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                        'mask' => '000.000.000.000.000.000',
                        'mask_options' => [
                            'reverse' => true
                        ],
                    ],
                ]
            ]);
        } else {
            CRUD::addField([
                'name' => 'invoice_client_details',
                'label' => trans('backpack::crud.invoice_client.field.item.label'),
                'type' => 'repeatable',
                'new_item_label'  => trans('backpack::crud.invoice_client.field.item.new_item_label'),
                'fields' => [
                    [
                        'name' => 'name',
                        'type' => 'text',
                        'label' => trans('backpack::crud.invoice_client.field.item.items.name.label'),
                        'wrapper' => [
                            'class' => 'form-group col-md-6',
                        ]
                    ],
                    [
                        'name' => 'price',
                        'label' => trans('backpack::crud.invoice_client.field.item.items.price.label'),
                        'type' => 'mask',
                        'mask' => '000.000.000.000.000.000',
                        'mask_options' => [
                            'reverse' => true
                        ],
                        'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                        'wrapper'   => [
                            'class' => 'form-group col-md-6'
                        ],
                    ]
                ]
            ]);
        }


        CRUD::addField([
            'name' => 'logic_invoice',
            'label' => '',
            'type' => 'logic_invoice',
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
    }

    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        $request = request();
        $po = ClientPo::find($request->client_po_id);

        if ($po != null) {
            $request->merge([
                'nominal_include_ppn' => (int) $request->nominal_exclude_ppn + ($request->nominal_exclude_ppn * $request->tax_ppn / 100),
            ]);
        }

        $this->crud->validateRequest();

        try {
            $dto = InvoiceClientSaveData::fromRequest($request);
            $invoice = $this->service->createInvoice($dto);

            $this->data['entry'] = $this->crud->entry = $invoice;

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            $this->crud->setSaveAction();

            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $invoice,
                    'events' => [
                        'crudTable-filter_invoice_plugin_load' => true,
                        'crudTable-invoice_create_success' => true
                    ],
                ]);
            }
            return $this->crud->performSaveAction($invoice->getKey());
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update($id)
    {
        $this->crud->hasAccessOrFail('update');

        $request = request();
        $po = ClientPo::find($request->client_po_id);

        if ($po != null) {
            $request->merge([
                'nominal_include_ppn' => (int) $request->nominal_exclude_ppn + ($request->nominal_exclude_ppn * $request->tax_ppn / 100),
            ]);
        }

        $this->crud->validateRequest();

        try {
            $dto = InvoiceClientSaveData::fromRequest($request);
            $invoice = $this->service->updateInvoice((int) $id, $dto);

            $this->data['entry'] = $this->crud->entry = $invoice;

            \Alert::success(trans('backpack::crud.update_success'))->flash();
            $this->crud->setSaveAction();

            return response()->json([
                'success' => true,
                'data' => $invoice,
                'events' => [
                    'crudTable-filter_invoice_plugin_load' => true,
                    'crudTable-invoice_updated_success' => true
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }


    protected function setupShowOperation()
    {
        $settings = Setting::first();
        $new_format_date = 'DD/MM/YYYY';

        $this->crud->query = $this->crud->query
            ->leftJoin('client_po', 'client_po.id', '=', 'invoice_clients.client_po_id');
        CRUD::addClause('select', [
            DB::raw("
                invoice_clients.*,
                client_po.date_po as po_date_from_po
            ")
        ]);

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

        // --- FIELDS (LABELS) ---

        CRUD::addField([
            'name' => 'invoice_number',
            'label' => trans('backpack::crud.invoice_client.field.invoice_number.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
        ]);

        CRUD::addField([
            'name' => 'name_label',
            'label' => trans('backpack::crud.invoice_client.column.name'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
        ]);

        CRUD::addField([
            'name'  => 'invoice_date',
            'label' => trans('backpack::crud.invoice_client.field.invoice_date.label'),
            'type'  => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'client_name',
            'label' => trans('backpack::crud.invoice_client.field.client_id.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'address_po',
            'label' => trans('backpack::crud.invoice_client.field.address.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
        ]);

        CRUD::addField([
            'name'        => 'client_po_id',
            'label'       => trans('backpack::crud.invoice_client.field.client_po_id.label'),
            'type'        => "text",
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'po_date',
            'label' => trans('backpack::crud.invoice_client.field.po_date.label'),
            'type'  => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => trans('backpack::crud.invoice_client.field.description.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
        ]);

        CRUD::addField([
            'name' => 'nominal_exclude_ppn',
            'label' => trans('backpack::crud.invoice_client.field.nominal_exclude_ppn.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'dpp_other',
            'label' => trans('backpack::crud.invoice_client.field.dpp_other.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'tax_ppn',
            'label' => trans('backpack::crud.invoice_client.field.tax_ppn.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'nominal_include_ppn',
            'label' => trans('backpack::crud.invoice_client.field.nominal_include_ppn.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'pph',
            'label' => trans('backpack::crud.invoice_client.field.pph.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'discount_pph',
            'label' => trans('backpack::crud.invoice_client.field.discount_pph.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'kdp',
            'label' => trans('backpack::crud.invoice_client.field.kdp.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'        => 'withholding_agent',
            'label'       => trans('backpack::crud.invoice_client.field.withholding_agent.label'),
            'type'        => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'send_invoice_normal_label',
            'label' => trans('backpack::crud.invoice_client.field.send_invoice_normal.label'),
            'type'  => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name'  => 'send_invoice_revision_label',
            'label' => trans('backpack::crud.invoice_client.field.send_invoice_revision.label'),
            'type'  => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name'        => 'status_label',
            'label'       => trans('backpack::crud.invoice_client.field.status.label'),
            'type'        => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'nominal_information',
            'label' => trans('backpack::crud.invoice_client.field.nominal_information_show.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'invoice_document_label',
            'label' => trans('backpack::crud.invoice_client.field.invoice_document.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
        ]);

        CRUD::addField([
            'name' => 'item_details_label',
            'label' => trans('backpack::crud.invoice_client.field.item.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
        ]);

        // --- COLUMNS (VALUES) ---

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.field.invoice_number.label'),
                'name' => 'invoice_number',
                'type'  => 'text'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.column.name'),
                'name' => 'name',
                'type'  => 'closure',
                'width_box' => '100%',
                'function' => function ($entry) {
                    return $entry->client_po?->job_name;
                }
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.field.invoice_date.label'),
                'name' => 'invoice_date',
                'type'  => 'date',
                'format' => $new_format_date,
            ],
        );

        CRUD::column([
            'label' => trans('backpack::crud.invoice_client.field.client_id.label'),
            'type'      => 'closure',
            'name'      => 'client_name',
            'function' => function ($entry) {
                return $entry->client_po?->client?->name;
            }
        ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.field.address.label'),
                'name' => 'address_po',
                'type'  => 'text'
            ],
        );

        CRUD::column([
            'label' => trans('backpack::crud.invoice_client.field.client_po_id.label'),
            'type'      => 'select',
            'name'      => 'client_po_id',
            'entity'    => 'client_po',
            'attribute' => 'po_number',
            'model'     => "App\Models\ClientPo",
        ]);

        CRUD::column(
            [
                'label' => trans('backpack::crud.invoice_client.field.po_date.label'),
                'name' => 'po_date_from_po',
                'type' => 'date',
                'format' => $new_format_date,
            ]
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.field.description.label'),
                'name' => 'description',
                'type'  => 'closure',
                'width_box' => '100%',
                'function' => function ($entry) {
                    return $entry->description;
                },
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.field.nominal_exclude_ppn.label'),
                'name' => 'price_total_exclude_ppn',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.field.dpp_other.label'),
                'name' => 'price_dpp',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.field.tax_ppn.label'),
                'name' => 'tax_ppn',
                'type'  => 'number',
                'suffix' => '%',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.field.nominal_include_ppn.label'),
                'name' => 'price_total_include_ppn',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.field.pph.label'),
                'name' => 'pph',
                'type'  => 'number',
                'suffix' => '%',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.invoice_client.field.discount_pph.label'),
                'name' => 'discount_pph',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label' => trans('backpack::crud.invoice_client.field.kdp.label'),
                'name' => 'kdp',
                'type' => 'text',
            ]
        );

        CRUD::column(
            [
                'label' => trans('backpack::crud.invoice_client.field.withholding_agent.label'),
                'name' => 'withholding_agent',
                'type' => 'text',
            ]
        );

        CRUD::column(
            [
                'label' => trans('backpack::crud.invoice_client.field.send_invoice_normal.label'),
                'name' => 'send_invoice_normal_date',
                'type' => 'date',
                'format' => $new_format_date,
            ]
        );

        CRUD::column(
            [
                'label' => trans('backpack::crud.invoice_client.field.send_invoice_revision.label'),
                'name' => 'send_invoice_revision_date',
                'type' => 'date',
                'format' => $new_format_date,
            ]
        );

        CRUD::column(
            [
                'label' => trans('backpack::crud.invoice_client.field.status.label'),
                'name' => 'status',
                'type' => 'text',
            ]
        );

        CRUD::column([
            'label' => trans('backpack::crud.invoice_client.field.nominal_information_show.label'),
            'name' => 'price_total',
            'type' => 'closure',
            // 'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
            // 'decimals'      => 2,
            // 'dec_point'     => ',',
            // 'thousands_sep' => '.',
            'function' => function ($entry) {
                if ($entry->withholding_agent == "NON WAPU" || $entry->withholding_agent == "" || $entry->withholding_agent == null) {
                    return CustomHelper::formatRupiahWithCurrency($entry->price_total);
                } else if ($entry->withholding_agent == "WAPU") {
                    return CustomHelper::formatRupiahWithCurrency($entry->price_total_exclude_ppn - $entry->discount_pph);
                } else {
                    return CustomHelper::formatRupiahWithCurrency($entry->price_total);
                }
            }
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.invoice_client.field.invoice_document.label'),
            'name' => 'invoice_document',
            'type' => 'closure',
            'width_box' => '400px',
            'function' => function ($entry) {
                if ($entry->invoice_document) {
                    $url = asset('storage/' . $entry->invoice_document);
                    $filename = basename($entry->invoice_document);
                    return '<a href="' . $url . '" target="_blank"><i class="la la-file-pdf"></i> ' . $filename . '</a>';
                }
                return '';
            },
            'escaped' => false,
        ]);

        CRUD::column(
            [
                'label' => trans('backpack::crud.invoice_client.field.item.label'),
                'name' => 'list_invoice',
                'type' => 'list-invoice',
            ]
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


    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');
        $id = $this->crud->getCurrentEntryId() ?? $id;

        try {
            $this->service->deleteInvoice((int) $id);

            return response()->json([
                'success' => [trans('backpack::crud.delete_confirmation_message')],
                'events' => [
                    'crudTable-filter_invoice_plugin_load' => true,
                    'crudTable-invoice_create_success' => true,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }


    public function printInvoice($id)
    {

        $data = [];
        $data['header'] = InvoiceClient::where('id', $id)->first();
        $data['details'] = InvoiceClientDetail::where('invoice_client_id', $id)->get();

        $pdf = Pdf::loadView('exports.invoice-client-pdf-new', $data);
        return $pdf->stream('invoice.pdf');
        return view('exports.invoice-client-pdf-new');
    }

    public function voidPayment($id)
    {
        $this->crud->hasAccessOrFail('void');

        try {
            $this->service->voidInvoice((int) $id);

            return response()->json([
                'success' => true,
                'message' => 'Pembayaran invoice berhasil di-Void.',
                'events' => [
                    'crudTable-invoice_create_success' => true,
                    'crudTable-filter_invoice_plugin_load' => true,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
