<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\Invoice\ProformaInvoiceClientFilterData;
use App\DTOs\Invoice\ProformaInvoiceClientSaveData;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\FormaterExport;
use App\Http\Controllers\Operation\PermissionAccess;
use App\Http\Helpers\CustomHelper;
use App\Http\Requests\ProformaInvoiceClientRequest;
use App\Models\ClientPo;
use App\Models\ProformaInvoiceClient;
use App\Models\ProformaInvoiceClientDetail;
use App\Models\Setting;
use App\Repositories\Invoice\ProformaInvoiceClientRepository;
use App\Services\Invoice\ProformaInvoiceClientService;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Prologue\Alerts\Facades\Alert;
use App\Http\Exports\ExportExcel;
use Maatwebsite\Excel\Facades\Excel;

class ProformaInvoiceClientCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;
    use FormaterExport;

    public function __construct(
        protected ProformaInvoiceClientRepository $repository,
        protected ProformaInvoiceClientService $service
    ) {
        parent::__construct();
    }

    public function setup()
    {
        CRUD::setModel(\App\Models\ProformaInvoiceClient::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/client/proforma-invoice');
        CRUD::setEntityNameStrings('Proforma Invoice Client', 'Proforma Invoice Client');

        $base = 'INDEX CLIENT PROFORMA INVOICE';
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

    public function total_price()
    {
        $dto = ProformaInvoiceClientFilterData::fromRequest(request());
        $totals = $this->repository->getTotals($dto);

        return response()->json([
            'total_price_exclude_ppn' => CustomHelper::formatRupiahWithCurrency($totals['total_price_exclude_ppn'] ?? 0),
            'total_price_include_ppn' => CustomHelper::formatRupiahWithCurrency($totals['total_price_include_ppn'] ?? 0),
            'total_discount_pph' => CustomHelper::formatRupiahWithCurrency($totals['total_discount_pph'] ?? 0),
        ]);
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

        $title = strtoupper(trans('backpack::crud.proforma_invoice.title_header') . ' CLIENT');
        $pdf = Pdf::loadView('exports.table-pdf', [
            'columns' => $columns,
            'items' => $all_items,
            'title' => $title
        ])->setPaper('A4', 'landscape');

        $fileName = 'proforma_invoice_client_' . now()->format('Ymd_His') . '.pdf';

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

        $name = strtoupper(trans('backpack::crud.proforma_invoice.title_header') . ' CLIENT');

        return response()->streamDownload(function () use ($columns, $items, $all_items) {
            echo Excel::raw(new ExportExcel(
                $columns,
                $all_items
            ), \Maatwebsite\Excel\Excel::XLSX);
        }, $name . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $name . '.xlsx"',
        ]);
    }

    public function index()
    {
        $this->crud->hasAccessOrFail('list');
        $this->crud->param_uri_export = "?export=1";
        $this->getComponent();

        $this->data['crud'] = $this->crud;
        $this->data['title'] = trans('backpack::crud.proforma_invoice.title_header') . ' Client';
        $this->data['title_modal_create'] = trans('backpack::crud.proforma_invoice.title_modal_create') . ' Client';
        $this->data['title_modal_edit'] = trans('backpack::crud.proforma_invoice.title_modal_edit') . ' Client';
        $this->data['title_modal_delete'] = trans('backpack::crud.proforma_invoice.title_modal_delete') . ' Client';
        $this->data['cards'] = $this->card;

        $breadcrumbs = [
            'Client' => backpack_url('client'),
            trans('backpack::crud.proforma_invoice.title_header') . ' Client' => backpack_url('client/proforma-invoice'),
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;
        $this->data['year_options'] = CustomHelper::getYearOptions('proforma_invoice_clients', 'invoice_date');

        $list = "crud::list-blank" ?? $this->crud->getListView();
        return view($list, $this->data);
    }

    public function create()
    {
        $this->crud->hasAccessOrFail('create');

        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = trans('backpack::crud.add') . ' ' . trans('backpack::crud.proforma_invoice.title_header') . ' Client';

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
        $entry->nominal_exclude_ppn = $entry->price_total_exclude_ppn;
        $entry->nominal_include_ppn = $entry->price_total_include_ppn;
        $entry->send_invoice_normal = $entry->send_invoice_normal_date;
        $entry->send_invoice_revision = $entry->send_invoice_revision_date;
        $entry->proforma_invoice_client_details_edit = $entry->proforma_invoice_client_details;

        $this->data['entry'] = $entry;
        $this->crud->setOperationSetting('fields', $this->crud->getUpdateFields());

        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = trans('backpack::crud.edit') . ' ' . trans('backpack::crud.proforma_invoice.title_header') . ' Client';
        $this->data['id'] = $id;

        return response()->json([
            'html' => view($this->crud->getEditView(), $this->data)->render()
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



        // $this->crud->filter('send_invoice_normal11crudTable-invoice')
        //     ->label(trans('backpack::crud.invoice_client.column.send_invoice_normal'))
        //     ->type('date');

        // $this->crud->filter('send_invoice_revision11crudTable-invoice')
        //     ->label(trans('backpack::crud.invoice_client.column.send_invoice_revision'))
        //     ->type('date');

        $status_file = strpos(url()->current(), 'excel') ? 'excel' : 'pdf';

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
                'label' => trans('backpack::crud.invoice_client.column.client_id'),
                'name' => 'client_name',
                'type' => 'text',
                'orderable' => true,
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
                'label' => trans('backpack::crud.invoice_client.column.description'),
                'name' => 'description',
                'type' => 'wrap_text',
                'orderable' => true,
            ],
            [
                'label'  => trans('backpack::crud.proforma_invoice.column.unit_price'),
                'name' => 'price_total_exclude_ppn',
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return CustomHelper::formatCurrency($entry->price_total_exclude_ppn, $entry->currency_code ?? 'IDR', $status_file === 'excel');
                },
                'orderable' => true,
            ],
            [
                'label'  => trans('backpack::crud.client_quotation.column.job_value_base'),
                'name'   => 'price_total_exclude_ppn_base',
                'type'   => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return CustomHelper::formatCurrency($entry->price_total_exclude_ppn_base ?? $entry->price_total_exclude_ppn, 'IDR', $status_file === 'excel');
                },
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
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return CustomHelper::formatCurrency($entry->price_total_include_ppn, $entry->currency_code ?? 'IDR', $status_file === 'excel');
                },
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
                'route' => backpack_url('client/proforma-invoice/search'),
            ]
        ]);

        $this->card->addCard([
            'name' => 'client-proforma-invoice-plugin',
            'line' => 'top',
            'view' => 'crud::components.proforma-invoice-plugin',
            'parent_view' => 'crud::components.filter-parent',
            'params' => [],
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
        CRUD::addButtonFromView('top', 'filter_year', 'filter-year', 'beginning');
        CRUD::addButtonFromView('line', 'show', 'show', 'end');
        CRUD::addButtonFromView('line', 'update', 'update', 'end');
        CRUD::addButtonFromView('line', 'print', 'print', 'end');
        CRUD::addButtonFromView('line', 'delete', 'delete', 'end');

        $this->repository->applyListQuery(
            $this->crud->query,
            ProformaInvoiceClientFilterData::fromRequest(request())
        );

        CRUD::addClause('select', [
            DB::raw("
                proforma_invoice_clients.*,
                companies.name as company_name,
                clients.name as client_name
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
            'label' => trans('backpack::crud.invoice_client.column.client_id'),
            'name' => 'client_name',
            'type' => 'text',
            'orderable' => true,
            'orderLogic' => function ($query, $column, $columnDir) {
                return $query->orderBy('clients.name', $columnDir);
            },
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.client_quotation.column.currency_code'),
            'name'  => 'currency_code',
            'type'  => 'closure',
            'function' => function ($entry) {
                $code = $entry->currency_code ?? 'IDR';
                $badgeClass = ($code === 'USD') ? 'badge bg-warning text-dark' : 'badge bg-secondary';
                return '<span class="' . $badgeClass . '">' . e($code) . '</span>';
            },
            'escaped' => false,
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
                return CustomHelper::formatCurrency($entry->price_total_exclude_ppn, $entry->currency_code ?? 'IDR', $status_file === 'excel');
            },
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.client_quotation.column.job_value_base'),
            'name'   => 'price_total_exclude_ppn_base',
            'type'   => 'closure',
            'function' => function ($entry) use ($status_file) {
                return CustomHelper::formatCurrency($entry->price_total_exclude_ppn_base ?? $entry->price_total_exclude_ppn, 'IDR', $status_file === 'excel');
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
                return CustomHelper::formatCurrency($entry->price_total_include_ppn, $entry->currency_code ?? 'IDR', $status_file === 'excel');
            },
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.proforma_invoice.column.note'),
            'name' => 'note',
            'type' => 'wrap_text'
        ]);
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(ProformaInvoiceClientRequest::class);
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
            'name' => 'client_quotation_id',
            'label' => trans('backpack::crud.client_quotation.title_header'),
            'type' => 'select2_ajax_custom',
            'entity' => 'clientQuotation',
            'attribute' => 'po_number',
            'data_source' => url(config('backpack.base.route_prefix') . '/client/proforma-invoice/select2-client-quotation'),
            'placeholder' => trans('backpack::crud.delivery_note.field.reference_type.options.quotation'),
            'minimum_input_length' => 0,
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'id' => 'client_quotation_id_select',
            ],
        ]);

        CRUD::addField([
            'name' => 'invoice_number',
            'label' => trans('backpack::crud.proforma_invoice.field.invoice_number.label'),
            'type' => 'text',
            'default' => $defaultProformaInvoiceNumber,
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.proforma_invoice.field.invoice_number.placeholder'),
            ],
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
            'label'        => trans('backpack::crud.invoice_client.field.client_id.label'),
            'type'         => 'select2_ajax_custom',
            'name'         => 'client_id',
            'entity'       => 'client',
            'attribute'    => 'name',
            'data_source'  => backpack_url('client/select2-client'),
            'dependencies' => ['company_id'],
            'include_all_form_fields' => true,
            'placeholder'  => trans('backpack::crud.invoice_client.field.client_id.placeholder'),
            'wrapper'      => ['class' => 'form-group col-md-12'],
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
                'class' => 'form-group col-md-12',
            ],
        ]);

        CRUD::addField([
            'name' => 'nominal_exclude_ppn',
            'label' => trans('backpack::crud.invoice_client.field.nominal_exclude_ppn.label'),
            'type' => 'mask_currency',
            'currency_name' => 'nominal_exclude_ppn_currency',
            'currency_options' => [
                'IDR' => 'IDR (Rp)',
                'USD' => 'USD ($)',
            ],
            'default_currency' => 'IDR',
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
            'name'        => 'type_device',
            'label'       => trans('backpack::crud.invoice_client.field.type_device.label') ?? 'Tipe Barang',
            'type'        => 'select_from_array',
            'options'     => [
                'App\Models\DeviceStock' => 'Persediaan',
                'App\Models\BillingDevice' => 'Billing Device',
                'App\Models\BillingSimcard' => 'Billing SIMCARD',
            ],
            'allows_null' => true,
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
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
                'name' => 'proforma_invoice_client_details_edit',
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
                        'name' => 'device_stock_id',
                        'type' => 'hidden',
                        'wrapper' => [
                            'class' => 'form-group col-md-0 d-none',
                        ],
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
                        'type' => 'mask_currency',
                        'label' => trans('backpack::crud.invoice_client.field.item.items.price.label'),
                        'currency_name' => 'price_currency',
                        'default_currency' => 'IDR',
                        'wrapper' => [
                            'class' => 'form-group col-md-5',
                        ],
                    ],
                ],
            ]);
        } else {
            CRUD::addField([
                'name' => 'proforma_invoice_client_details',
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
                        'name' => 'device_stock_id',
                        'type' => 'hidden',
                        'wrapper' => [
                            'class' => 'form-group col-md-0 d-none',
                        ],
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
        $currencyCode = $request->input('currency_code', 'IDR');
        $rawExc = (string) ($request->nominal_exclude_ppn ?? '0');
        $exclude_ppn = ($currencyCode === 'USD') ? (float) str_replace(',', '', $rawExc) : (float) str_replace('.', '', $rawExc);
        $tax_ppn = (float) ($request->tax_ppn ?? 0);
        $request->merge([
            'nominal_include_ppn' => $exclude_ppn + ($exclude_ppn * $tax_ppn / 100),
        ]);

        $this->crud->validateRequest();

        try {
            DB::beginTransaction();

            $dto = ProformaInvoiceClientSaveData::fromRequest($request);

            $invoice = $this->service->createInvoice($dto);

            $this->data['entry'] = $this->crud->entry = $invoice;

            Alert::success(trans('backpack::crud.insert_success'))->flash();
            $this->crud->setSaveAction();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => true,
                    'success' => true,
                    'redirect_url' => $this->crud->getRoute(),
                    'events' => [
                        'crudTable-invoice_create_success' => true
                    ]
                ]);
            }

            return $this->performSaveAction($invoice->getKey());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Store Proforma Invoice Client failed: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'status' => false,
                    'success' => false,
                    'error' => $e->getMessage(),
                ]);
            }

            Alert::error($e->getMessage())->flash();
            return redirect()->back()->withInput();
        }
    }

    public function update()
    {
        $this->crud->hasAccessOrFail('update');
        $request = request();
        $currencyCode = $request->input('currency_code', 'IDR');
        $rawExc = (string) ($request->nominal_exclude_ppn ?? '0');
        $exclude_ppn = ($currencyCode === 'USD') ? (float) str_replace(',', '', $rawExc) : (float) str_replace('.', '', $rawExc);
        $tax_ppn = (float) ($request->tax_ppn ?? 0);
        $request->merge([
            'nominal_include_ppn' => $exclude_ppn + ($exclude_ppn * $tax_ppn / 100),
        ]);

        $this->crud->validateRequest();

        try {
            DB::beginTransaction();

            $dto = ProformaInvoiceClientSaveData::fromRequest($request);
            


            $id = $request->input('id');
            $invoice = $this->service->updateInvoice((int) $id, $dto);

            $this->data['entry'] = $this->crud->entry = $invoice;

            Alert::success(trans('backpack::crud.update_success'))->flash();
            $this->crud->setSaveAction();

            DB::commit();

            if ($request->ajax()) {
                return response()->json([
                    'status' => true,
                    'success' => true,
                    'redirect_url' => $this->crud->getRoute(),
                    'events' => [
                        'crudTable-invoice_updated_success' => true
                    ]
                ]);
            }

            return $this->performSaveAction($invoice->getKey());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update Proforma Invoice Client failed: ' . $e->getMessage());

            if ($request->ajax()) {
                return response()->json([
                    'status' => false,
                    'success' => false,
                    'error' => $e->getMessage(),
                ]);
            }

            Alert::error($e->getMessage())->flash();
            return redirect()->back()->withInput();
        }
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
            'name' => 'term',
            'label' => trans('backpack::crud.proforma_invoice.field.term.label'),
            'type' => 'custom_html',
            'value' => $this->crud->getCurrentEntry() ? $this->crud->getCurrentEntry()->term : '',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
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
                return CustomHelper::formatCurrency($entry->price_total_exclude_ppn, $entry->currency_code ?? 'IDR');
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
                return CustomHelper::formatCurrency($entry->price_total_include_ppn, $entry->currency_code ?? 'IDR');
            }
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.proforma_invoice.column.note'),
            'name' => 'note',
            'type'  => 'wrap_text',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.proforma_invoice.field.term.label'),
            'name' => 'term',
            'type'  => 'custom_html',
            'value' => $this->crud->getCurrentEntry()?->term,
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.invoice_client.field.nominal_information_show.label'),
            'name' => 'price_total',
            'type' => 'closure',
            'function' => function ($entry) {
                $total = ($entry->withholding_agent == "WAPU")
                    ? ($entry->price_total_exclude_ppn - $entry->discount_pph)
                    : $entry->price_total;
                return CustomHelper::formatCurrency($total, $entry->currency_code ?? 'IDR');
            }
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.invoice_client.field.item.label'),
            'name' => 'item_details_label',
            'type' => 'list-proforma-client', // Custom table list for details
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

    public function show($id)
    {
        $this->crud->hasAccessOrFail('show');
        $id = $this->crud->getCurrentEntryId() ?? $id;

        $this->data['entry'] = $this->crud->getEntryWithLocale($id);
        $this->data['entry_value'] = $this->crud->getRowViews($this->data['entry']);
        $this->data['crud'] = $this->crud;
        $this->data['title'] = trans('backpack::crud.preview') . ' Proforma Invoice Client';

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
        $data['header'] = ProformaInvoiceClient::where('id', $id)->first();
        $data['details'] = ProformaInvoiceClientDetail::where('proforma_invoice_client_id', $id)->get();

        $pdf = Pdf::loadView('exports.invoice-client-proforma-single-pdf', $data);
        $fileName = 'Proforma-Invoice-Client-' . ($data['header']->invoice_number ?? $id) . '.pdf';
        $safeFileName = str_replace(['/', '\\'], '-', $fileName);

        return $pdf->stream($safeFileName);
    }

    public function select2ClientQuotation()
    {
        $search = request()->input('q');
        $companyId = request()->input('company_id');

        $query = \App\Models\ClientQuotation::with(['client'])
            ->select(['id', 'po_number', 'job_name', 'client_id', 'job_value', 'currency_code', 'tax_ppn', 'pic']);

        if ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('po_number', 'LIKE', "%{$search}%")
                  ->orWhere('job_name', 'LIKE', "%{$search}%");
            });
        }

        $dataset = $query->paginate(20);

        $results = [];
        foreach ($dataset as $item) {
            $clientName = $item->client?->name ? ' (' . $item->client->name . ')' : '';
            $results[] = [
                'id' => $item->id,
                'text' => $item->po_number . ' - ' . $item->job_name . $clientName,
            ];
        }

        return response()->json(['results' => $results]);
    }

    public function getClientQuotationDetails()
    {
        $id = (int) request()->input('id');
        $quotation = \App\Models\ClientQuotation::with(['details.deviceStock', 'client'])->find($id);

        if (!$quotation) {
            return response()->json(['status' => false, 'message' => 'Quotation tidak ditemukan'], 404);
        }

        $items = [];
        if ($quotation->details && $quotation->details->count() > 0) {
            foreach ($quotation->details as $detail) {
                $items[] = [
                    'name' => $detail->item_name ?? $detail->deviceStock?->name ?? '-',
                    'qty' => (float) ($detail->qty ?? 1),
                    'price' => (float) ($detail->unit_price ?? $detail->price ?? 0),
                    'device_stock_id' => $detail->device_stock_id ?? null,
                ];
            }
        }

        return response()->json([
            'status' => true,
            'client_id' => $quotation->client_id,
            'client_name' => $quotation->client?->name ?? '',
            'address' => $quotation->client?->address ?? '',
            'description' => $quotation->job_name ?? '',
            'pic' => $quotation->pic ?? '',
            'currency_code' => $quotation->currency_code ?? 'IDR',
            'nominal_exclude_ppn' => (float) ($quotation->job_value ?? 0),
            'tax_ppn' => (float) ($quotation->tax_ppn ?? 11),
            'items' => $items,
        ]);
    }
}
