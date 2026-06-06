<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\Invoice\ProformaInvoiceFilterData;
use App\DTOs\Invoice\ProformaInvoiceSaveData;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\FormaterExport;
use App\Http\Controllers\Operation\PermissionAccess;
use App\Http\Exports\ExportExcel;
use App\Http\Helpers\CustomHelper;
use App\Http\Requests\ProformaInvoiceRequest;
use App\Models\CastAccount;
use App\Models\ClientPo;
use App\Models\ProformaInvoice;
use App\Models\ProformaInvoiceDetail;
use App\Models\Setting;
use App\Repositories\Invoice\ProformaInvoiceRepository;
use App\Services\Invoice\ProformaInvoiceService;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Prologue\Alerts\Facades\Alert;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class ProformaInvoiceCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;
    use FormaterExport;

    public function __construct(
        protected ProformaInvoiceRepository $repository,
        protected ProformaInvoiceService $service
    ) {
        parent::__construct();
    }

    public function setup()
    {
        CRUD::setModel(\App\Models\ProformaInvoice::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/vendor/proforma-invoice');
        CRUD::setEntityNameStrings(trans('backpack::crud.proforma_invoice.title_header'), trans('backpack::crud.proforma_invoice.title_header'));

        $base = 'INDEX VENDOR PROFORMA INVOICE';
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

    public function select2SubkonId()
    {
        $this->crud->hasAccessOrFail('create');

        $search = request()->input('q');
        $company_id = request()->input('company_id');

        if (backpack_user()->hasRole('Super Admin') && !$company_id) {
            return response()->json(['results' => []]);
        }

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

    public function getSubkonDetails()
    {
        $this->crud->hasAccessOrFail('create');
        $id = request()->input('subkon_id');
        $subkon = \App\Models\Subkon::find($id);

        return response()->json([
            'address' => $subkon?->address ?? ''
        ]);
    }

    public function selectedClientPo()
    {
        $this->crud->hasAccessOrFail('create');
        $id = request()->id;
        $entry = ClientPo::where('id', $id)->first();

        $entry->date_invoice = ($entry->date_invoice) ? Carbon::createFromFormat('Y-m-d', $entry->date_invoice)->format('d/m/Y') : Carbon::now()->format('d/m/Y');
        $entry->date_po_str = ($entry->date_po) ? Carbon::createFromFormat('Y-m-d', $entry->date_po)->format('d/m/Y') : Carbon::now()->format('d/m/Y');
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
            ->label(trans('backpack::crud.proforma_invoice.field.invoice_date.label'))
            ->type('date');

        $this->crud->filter('po_date11crudTable-invoice')
            ->label(trans('backpack::crud.invoice_client.column.po_date'))
            ->type('date');

        // $this->crud->filter('send_invoice_normal11crudTable-invoice')
        //     ->label(trans('backpack::crud.invoice_client.column.send_invoice_normal'))
        //     ->type('date');

        // $this->crud->filter('send_invoice_revision11crudTable-invoice')
        //     ->label(trans('backpack::crud.invoice_client.column.send_invoice_revision'))
        //     ->type('date');

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
                'label'  => trans('backpack::crud.proforma_invoice.column.invoice_number'),
                'name' => 'invoice_number',
                'type'  => 'text',
                'orderable' => true,
            ],
            [
                'label'  => trans('backpack::crud.proforma_invoice.column.invoice_date'),
                'name' => 'invoice_date',
                'type'  => 'text',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.proforma_invoice.column.subkon_name'),
                'type' => 'text',
                'name' => 'subkon_name',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.invoice_client.column.description'),
                'name' => 'description',
                'type' => 'wrap_text',
                'orderable' => true,
            ],
            [
                'label'  => trans('backpack::crud.proforma_invoice.column.unit_price'),
                'name' => 'price_total_exclude_ppn',
                'type'  => 'text',
                'orderable' => true,
            ],
            [
                'label'  => trans('backpack::crud.proforma_invoice.column.ppn'),
                'name' => 'tax_ppn',
                'type'  => 'text',
                'orderable' => true,
            ],
            [
                'label'  => trans('backpack::crud.proforma_invoice.column.amount'),
                'name' => 'price_total_include_ppn',
                'type'  => 'text',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.proforma_invoice.column.note'),
                'name' => 'note',
                'type' => 'wrap_text',
                'orderable' => true,
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
                'filter_table' => collect($this->crud->filters())->slice(0, 2),
                'route' => backpack_url('vendor/proforma-invoice/search'),
            ]
        ]);

        $this->card->addCard([
            'name' => 'invoice-plugin',
            'line' => 'top',
            'view' => 'crud::components.proforma-invoice-plugin',
            'parent_view' => 'crud::components.filter-parent',
            'params' => [],
        ]);
    }

    public function total_price()
    {
        $dto = ProformaInvoiceFilterData::fromRequest(request());
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
        $this->data['title'] = trans('backpack::crud.proforma_invoice.title_header');
        $this->data['title_modal_create'] = trans('backpack::crud.proforma_invoice.title_modal_create');
        $this->data['title_modal_edit'] = trans('backpack::crud.proforma_invoice.title_modal_edit');
        $this->data['title_modal_delete'] = trans('backpack::crud.proforma_invoice.title_modal_delete');
        $this->data['cards'] = $this->card;

        $breadcrumbs = [
            'Vendor (Subkon)' => backpack_url('vendor'),
            trans('backpack::crud.proforma_invoice.title_header') => backpack_url('vendor/proforma-invoice'),
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;
        $this->data['year_options'] = CustomHelper::getYearOptions('proforma_invoices', 'invoice_date');

        $list = "crud::list-blank" ?? $this->crud->getListView();
        return view($list, $this->data);
    }

    public function create()
    {
        $this->crud->hasAccessOrFail('create');

        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = trans('backpack::crud.add') . ' ' . trans('backpack::crud.proforma_invoice.title_header');

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
        $entry->po_date = $entry->client_po ? Carbon::parse($entry->client_po->date_po)->format('d/m/Y') : null;
        $entry->price_total_exclude_ppn = $entry->price_total_exclude_ppn;
        $entry->price_total_include_ppn = $entry->price_total_include_ppn;

        $entry->proforma_invoice_details_edit = $entry->proforma_invoice_details;
        $entry->subkon_name = $entry->subkon?->name;
        $entry->nominal_exclude_ppn = $entry->price_total_exclude_ppn;
        $entry->nominal_include_ppn = $entry->price_total_include_ppn;
        $entry->send_invoice_normal = $entry->send_invoice_normal_date;
        $entry->send_invoice_revision = $entry->send_invoice_revision_date;

        $this->data['entry'] = $entry;
        $this->crud->setOperationSetting('fields', $this->crud->getUpdateFields());

        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = trans('backpack::crud.edit') . ' ' . trans('backpack::crud.proforma_invoice.title_header');
        $this->data['id'] = $id;

        return response()->json([
            'html' => view($this->crud->getEditView(), $this->data)->render()
        ]);
    }

    protected function setupListOperation()
    {
        CRUD::disableResponsiveTable();
        CRUD::removeButtons(['delete', 'show', 'update'], 'line');
        $new_format_date = 'DD/MM/YYYY';

        $this->crud->file_title_export_pdf = "Laporan_invoice_proforma.pdf";
        $this->crud->file_title_export_excel = "Laporan_invoice_proforma.xlsx";
        $this->crud->param_uri_export = "?export=1";

        CRUD::addButtonFromView('top', 'export-excel-table', 'export-excel-table', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-table', 'export-pdf-table', 'beginning');
        // CRUD::addButtonFromView('top', 'filter_paid_unpaid', 'filter-paid_unpaid', 'beginning');
        CRUD::addButtonFromView('top', 'filter_year', 'filter-year', 'beginning');
        CRUD::addButtonFromView('line', 'show', 'show', 'end');
        CRUD::addButtonFromView('line', 'update', 'update', 'end');
        CRUD::addButtonFromView('line', 'print', 'print', 'end');
        CRUD::addButtonFromView('line', 'delete', 'delete', 'end');

        $this->repository->applyListQuery(
            $this->crud->query,
            ProformaInvoiceFilterData::fromRequest(request())
        );

        CRUD::addClause('select', [
            DB::raw("
                proforma_invoices.*,
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

        CRUD::column([
            'label'  => trans('backpack::crud.proforma_invoice.column.invoice_number'),
            'name' => 'invoice_number',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.invoice_client.column.invoice_date'),
            'name' => 'invoice_date',
            'type'  => 'date',
            'format' => $new_format_date,
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.proforma_invoice.column.subkon_name'),
            'type'      => 'closure',
            'name'      => 'subkon_name',
            'function' => function ($entry) {
                return $entry->subkon?->name;
            }
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.invoice_client.column.description'),
            'name' => 'description',
            'type' => 'wrap_text'
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.proforma_invoice.column.unit_price'),
            'name' => 'price_total_exclude_ppn',
            'type'  => 'closure',
            'function' => function ($entry) use ($status_file) {
                return $this->priceFormatExport($status_file, $entry->price_total_exclude_ppn);
            },
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.proforma_invoice.column.ppn'),
            'name' => 'tax_ppn',
            'type'  => 'closure',
            'function' => function ($entry) use ($status_file) {
                return $status_file == 'excel' ? $entry->tax_ppn : $entry->tax_ppn . '%';
            },
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.proforma_invoice.column.amount'),
            'name' => 'price_total_include_ppn',
            'type'  => 'closure',
            'function' => function ($entry) use ($status_file) {
                return $this->priceFormatExport($status_file, $entry->price_total_include_ppn);
            },
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.proforma_invoice.column.note'),
            'name' => 'note',
            'type' => 'wrap_text'
        ]);


    }

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

    public function exportPdf()
    {
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

        $title = strtoupper(trans('backpack::crud.proforma_invoice.title_header'));
        $pdf = Pdf::loadView('exports.table-pdf', [
            'columns' => $columns,
            'items' => $all_items,
            'title' => $title
        ])->setPaper('A4', 'landscape');

        $fileName = 'proforma_invoice_' . now()->format('Ymd_His') . '.pdf';

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

        $name = strtoupper(trans('backpack::crud.proforma_invoice.title_header'));

        return response()->streamDownload(function () use ($columns, $items, $all_items) {
            echo Excel::raw(new ExportExcel(
                $columns,
                $all_items
            ), \Maatwebsite\Excel\Excel::XLSX);
        }, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(ProformaInvoiceRequest::class);
        $settings = Setting::first();
        $defaultProformaInvoiceNumber = null;
        if (!$this->crud->getCurrentEntryId()) {
            $defaultProformaInvoiceNumber = $this->repository->generateNextNumber();
        }

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
            'label' => trans('backpack::crud.proforma_invoice.field.invoice_number.label'),
            'type' => 'text',
            'default' => $defaultProformaInvoiceNumber,
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.proforma_invoice.field.invoice_number.placeholder'),
            ],
        ]);

        CRUD::addField([
            'name'  => 'invoice_date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.proforma_invoice.field.invoice_date.label'),
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.proforma_invoice.field.invoice_date.placeholder')
            ]
        ]);

        CRUD::addField([
            'label'       => trans('backpack::crud.proforma_invoice.field.subkon_id.label'),
            'type'        => 'select2_ajax_custom',
            'name'        => 'subkon_id',
            'entity'      => 'subkon',
            'attribute'   => 'name',
            'data_source' => backpack_url('vendor/proforma-invoice/select2-subkon-id'),
            'wrapper'     => [
                'class' => 'form-group col-md-6',
            ],
            'dependencies'            => ['company_id'],
            'include_all_form_fields' => true,
            'placeholder' => trans('backpack::crud.proforma_invoice.field.subkon_id.placeholder'),
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
            ]
        ]);



        CRUD::addField([
            'name' => 'tax_ppn',
            'label' => trans('backpack::crud.invoice_client.field.tax_ppn.label'),
            'type' => 'number',
            'attributes' => ["step" => "any"],
            'prefix'     => "%",
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
            'attributes' => ["step" => "any"],
            'prefix'     => "%",
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
            'name' => 'note',
            'label' => trans('backpack::crud.proforma_invoice.field.note.label'),
            'type' => 'textarea',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.proforma_invoice.field.note.placeholder'),
            ],
        ]);

        $id = request()->segment(4); // Adjusted for prefix vendor/proforma-invoice

        if ($id && $id != 'create') {
            CRUD::addField([
                'name' => 'proforma_invoice_details_edit',
                'label' => trans('backpack::crud.invoice_client.field.item.label'),
                'type' => 'repeatable',
                'new_item_label'  => trans('backpack::crud.invoice_client.field.item.new_item_label'),
                'fields' => [
                    [
                        'name' => 'name',
                        'type' => 'text',
                        'label' => trans('backpack::crud.invoice_client.field.item.items.name.label'),
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
                        'type' => 'mask_repeat',
                        'label' => trans('backpack::crud.invoice_client.field.item.items.price.label'),
                        'wrapper' => [
                            'class' => 'form-group col-md-5',
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
                'name' => 'proforma_invoice_details',
                'label' => trans('backpack::crud.invoice_client.field.item.label'),
                'type' => 'repeatable',
                'new_item_label'  => trans('backpack::crud.invoice_client.field.item.new_item_label'),
                'fields' => [
                    [
                        'name' => 'name',
                        'type' => 'text',
                        'label' => trans('backpack::crud.invoice_client.field.item.items.name.label'),
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
                        'label' => trans('backpack::crud.invoice_client.field.item.items.price.label'),
                        'type' => 'mask',
                        'mask' => '000.000.000.000.000.000',
                        'mask_options' => [
                            'reverse' => true
                        ],
                        'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                        'wrapper'   => [
                            'class' => 'form-group col-md-5'
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
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function store()
    {
        $this->crud->hasAccessOrFail('create');
        $request = request();
        $exclude_ppn = (float) str_replace('.', '', $request->nominal_exclude_ppn ?? '0');
        $tax_ppn = (float) ($request->tax_ppn ?? 0);
        $request->merge([
            'nominal_include_ppn' => $exclude_ppn + ($exclude_ppn * $tax_ppn / 100),
        ]);

        $this->crud->validateRequest();

        try {
            $dto = ProformaInvoiceSaveData::fromRequest($request);
            $invoice = $this->service->createInvoice($dto);

            $this->data['entry'] = $this->crud->entry = $invoice;

            Alert::success(trans('backpack::crud.insert_success'))->flash();
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
        $exclude_ppn = (float) str_replace('.', '', $request->nominal_exclude_ppn ?? '0');
        $tax_ppn = (float) ($request->tax_ppn ?? 0);
        $request->merge([
            'nominal_include_ppn' => $exclude_ppn + ($exclude_ppn * $tax_ppn / 100),
        ]);

        $this->crud->validateRequest();

        try {
            $dto = ProformaInvoiceSaveData::fromRequest($request);
            $invoice = $this->service->updateInvoice((int) $id, $dto);

            $this->data['entry'] = $this->crud->entry = $invoice;

            Alert::success(trans('backpack::crud.update_success'))->flash();
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
            ->leftJoin('client_po', 'client_po.id', '=', 'proforma_invoices.client_po_id');
        CRUD::addClause('select', [
            DB::raw("
                proforma_invoices.*,
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

        CRUD::addField([
            'name' => 'invoice_number',
            'label' => trans('backpack::crud.proforma_invoice.field.invoice_number.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
        ]);

        CRUD::addField([
            'name'  => 'invoice_date',
            'label' => trans('backpack::crud.proforma_invoice.field.invoice_date.label'),
            'type'  => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'subkon_id',
            'label' => trans('backpack::crud.proforma_invoice.field.subkon_id.label'),
            'type' => 'select',
            'entity' => 'subkon',
            'attribute' => 'name',
            'model' => 'App\Models\Subkon',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
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
            'label' => trans('backpack::crud.proforma_invoice.column.unit_price'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'tax_ppn',
            'label' => trans('backpack::crud.proforma_invoice.column.ppn'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'nominal_include_ppn',
            'label' => trans('backpack::crud.proforma_invoice.column.amount'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'note',
            'label' => trans('backpack::crud.proforma_invoice.field.note.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'price_total',
            'label' => trans('backpack::crud.invoice_client.field.nominal_information_show.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
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

        CRUD::column([
            'label'  => trans('backpack::crud.proforma_invoice.field.invoice_number.label'),
            'name' => 'invoice_number',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.proforma_invoice.column.invoice_date'),
            'name' => 'invoice_date',
            'type'  => 'date',
            'format' => $new_format_date,
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.proforma_invoice.column.subkon_name'),
            'type'      => 'closure',
            'name'      => 'subkon_id',
            'function' => function ($entry) {
                return $entry->subkon?->name;
            }
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.invoice_client.field.description.label'),
            'name' => 'description',
            'type'  => 'closure',
            'width_box' => '100%',
            'function' => function ($entry) {
                return $entry->description;
            },
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.proforma_invoice.column.unit_price'),
            'name' => 'nominal_exclude_ppn',
            'type'  => 'closure',
            'function' => function ($entry) {
                return CustomHelper::formatRupiahWithCurrency($entry->price_total_exclude_ppn);
            }
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.proforma_invoice.column.ppn'),
            'name' => 'tax_ppn',
            'type'  => 'number',
            'suffix' => '%',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.proforma_invoice.column.amount'),
            'name' => 'nominal_include_ppn',
            'type'  => 'closure',
            'function' => function ($entry) {
                return CustomHelper::formatRupiahWithCurrency($entry->price_total_include_ppn);
            }
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.proforma_invoice.column.note'),
            'name' => 'note',
            'type'  => 'wrap_text',
        ]);



        CRUD::column([
            'label' => trans('backpack::crud.invoice_client.field.nominal_information_show.label'),
            'name' => 'price_total',
            'type' => 'closure',
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
            'label' => trans('backpack::crud.invoice_client.field.item.label'),
            'name' => 'item_details_label',
            'type' => 'list-proforma',
        ]);
    }

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
        $this->data['title'] = trans('backpack::crud.preview') . ' ' . trans('backpack::crud.proforma_invoice.title_header');

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
        $data['header'] = ProformaInvoice::where('id', $id)->first();
        $data['details'] = ProformaInvoiceDetail::where('proforma_invoice_id', $id)->get();

        $pdf = Pdf::loadView('exports.invoice-proforma-single-pdf', $data);
        return $pdf->stream('Proforma-Invoice-' . ($data['header']->invoice_number ?? $id) . '.pdf');
    }
}
