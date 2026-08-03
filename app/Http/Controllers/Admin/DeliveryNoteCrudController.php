<?php

namespace App\Http\Controllers\Admin;

use App\Models\DeliveryNote;
use App\Models\ClientPo;
use App\Models\Client;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Exports\ExportExcel;
use App\Http\Helpers\CustomHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\DeliveryNoteRequest;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\FormaterExport;
use App\Http\Controllers\Operation\PermissionAccess;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Prologue\Alerts\Facades\Alert;

use App\DTOs\ClientManagement\DeliveryNoteData;
use App\DTOs\ClientManagement\DeliveryNoteFilterData;
use App\Services\ClientManagement\DeliveryNoteService;
use App\Repositories\ClientManagement\DeliveryNoteRepository;
use App\Repositories\ClientManagement\ClientPoRepository;
use Illuminate\Http\Request;

/**
 * Class DeliveryNoteCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class DeliveryNoteCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;
    use FormaterExport;

    protected $service;
    protected $repository;
    protected $clientPoRepository;

    public function __construct(
        DeliveryNoteService $service,
        DeliveryNoteRepository $repository,
        ClientPoRepository $clientPoRepository
    ) {
        parent::__construct();
        $this->service = $service;
        $this->repository = $repository;
        $this->clientPoRepository = $clientPoRepository;
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(DeliveryNote::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/client/delivery-note');
        CRUD::setEntityNameStrings(trans('backpack::crud.delivery_note.title_header'), trans('backpack::crud.delivery_note.title_header'));

        $allAccess = [
            'AKSES SEMUA MENU ACCOUNTING',
            'AKSES MENU CLIENT',
        ];

        $viewMenu = [
            'MENU INDEX CLIENT SURAT JALAN',
        ];

        $this->settingPermission([
            'create' => [
                'CREATE INDEX CLIENT SURAT JALAN',
                ...$allAccess
            ],
            'update' => [
                'UPDATE INDEX CLIENT SURAT JALAN',
                ...$allAccess
            ],
            'delete' => [
                'DELETE INDEX CLIENT SURAT JALAN',
                ...$allAccess
            ],
            'list' => $viewMenu,
            'show' => $viewMenu,
            'print' => true,
        ]);

        CRUD::addButtonFromView('line', 'print_delivery_note', 'print_delivery_note', 'beginning');
    }

    public function index()
    {
        $this->crud->hasAccessOrFail('list');

        $this->crud->filter('date11crudTable-delivery_note')
            ->label(trans('backpack::crud.delivery_note.column.date'))
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
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'text',
                'name'      => 'company.name',
            ];
        }

        $columns = array_merge($columns, [
            [
                'name'      => 'number',
                'type'      => 'text',
                'label'     => trans('backpack::crud.delivery_note.column.number'),
                'orderable' => true,
            ],
            [
                'name'      => 'date',
                'type'      => 'date',
                'label'     => trans('backpack::crud.delivery_note.column.date'),
                'orderable' => true,
            ],
            [
                'name'      => 'client.name',
                'type'      => 'text',
                'label'     => trans('backpack::crud.delivery_note.column.client_id'),
                'orderable' => true,
            ],
            [
                'name'      => 'reference_type',
                'type'      => 'text',
                'label'     => trans('backpack::crud.delivery_note.column.reference_type'),
                'orderable' => false,
            ],
            [
                'name'      => 'description',
                'type'      => 'text',
                'label'     => trans('backpack::crud.delivery_note.column.description'),
                'orderable' => true,
            ],
            [
                'name'      => 'information',
                'type'      => 'text',
                'label'     => trans('backpack::crud.delivery_note.column.information'),
                'orderable' => true,
            ],
            [
                'name'  => 'action',
                'type'  => 'action',
                'label' => trans('backpack::crud.actions'),
            ]
        ]);

        $this->card->addCard([
            'name' => 'delivery_note',
            'line' => 'top',
            'view' => 'crud::components.datatable-origin',
            'params' => [
                'filter' => true,
                'crud_custom' => $this->crud,
                'hide_title' => true,
                'columns' => $columns,
                'filter_table' => collect($this->crud->filters())->slice(0, 1),
                'route' => backpack_url('/client/delivery-note/search'),
            ]
        ]);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.delivery_note.title_header');
        $this->data['title_modal_edit'] = trans('backpack::crud.delivery_note.title_header');
        $this->data['title_modal_delete'] = trans('backpack::crud.delivery_note.title_header');
        $this->data['cards'] = $this->card;
        
        $breadcrumbs = [
            'Client' => backpack_url('client'),
            'Surat Jalan' => backpack_url($this->crud->route)
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;
        $this->data['year_options'] = CustomHelper::getYearOptions('delivery_notes', 'date');

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
        $entry->load('details.device_stock');
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

    public function store()
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        try {
            DB::beginTransaction();
            $data = DeliveryNoteData::fromRequest($request);
            $item = $this->service->createDeliveryNote($data);
            DB::commit();

            Alert::success(trans('backpack::crud.insert_success'))->flash();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
                    'events' => $this->service->getUIEvents($item, 'create'),
                ]);
            }
            return $this->crud->performSaveAction($item->getKey());
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function update()
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        try {
            DB::beginTransaction();
            $data = DeliveryNoteData::fromRequest($request);
            $item = $this->service->updateDeliveryNote($request->get($this->crud->model->getKeyName()), $data);
            DB::commit();

            Alert::success(trans('backpack::crud.update_success'))->flash();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
                    'events' => $this->service->getUIEvents($item, 'update'),
                ]);
            }
            return $this->crud->performSaveAction($item->getKey());
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
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

    public function select2ClientPo()
    {
        // $this->crud->hasAccessOrFail('create');

        $request = request();
        $search = $request->input('q');
        $company_id = $request->input('company_id');

        $query = ClientPo::select(['id', 'po_number']);

        if ($request->has('company_id') && $company_id !== '') {
            $query->where('company_id', $company_id);
        } else if (backpack_user() && !backpack_user()->hasRole('Super Admin')) {
            $query->where('company_id', backpack_user()->company_id);
        }

        $dataset = $query->where('po_number', 'LIKE', "%$search%")
            ->paginate(10);

        $results = [];
        foreach ($dataset as $item) {
            $results[] = [
                'id' => $item->id,
                'text' => $item->po_number,
            ];
        }
        return response()->json(['results' => $results]);
    }

    public function getClientAddress()
    {
        $this->crud->hasAccessOrFail('create');
        $id = request()->input('client_id');
        $client = Client::find($id);

        return response()->json([
            'address' => $client?->address ?? ''
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

        $title = "DAFTAR SURAT JALAN";

        $pdf = Pdf::loadView('exports.table-pdf', [
            'columns' => $columns,
            'items' => $all_items,
            'title' => $title
        ])->setPaper('A4', 'landscape');

        $fileName = 'surat_jalan_' . now()->format('Ymd_His') . '.pdf';

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

        $name = 'DAFTAR SURAT JALAN';

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

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation()
    {
        $this->crud->file_title_export_pdf = "Laporan_daftar_surat_jalan.pdf";
        $this->crud->file_title_export_excel = "Laporan_daftar_surat_jalan.xlsx";
        $this->crud->param_uri_export = "?export=1";

        CRUD::addButtonFromView('top', 'export-excel-table', 'export-excel-table', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-table', 'export-pdf-table', 'beginning');
        CRUD::addButtonFromView('top', 'filter_year', 'filter-year', 'beginning');

        CRUD::disableResponsiveTable();

        $filters = DeliveryNoteFilterData::fromRequest(request());
        $this->crud->query = $this->repository->getFilteredData($filters);

        CRUD::addColumn([
            'name'      => 'row_number',
            'type'      => 'row_number',
            'label'     => 'No',
            'orderable' => false,
            'wrapper'   => ['element' => 'strong'],
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

        CRUD::column([
            'label'  => trans('backpack::crud.delivery_note.column.number'),
            'name'   => 'number',
            'type'   => 'text',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.delivery_note.column.date'),
            'name'   => 'date',
            'type'   => 'date',
            'format' => 'DD/MM/YYYY',
        ]);

        CRUD::column([
            'label'     => trans('backpack::crud.delivery_note.column.client_id'),
            'type'      => 'select',
            'name'      => 'client_id',
            'entity'    => 'client',
            'attribute' => 'name',
            'model'     => "App\Models\Client",
        ]);

        // Badge Jenis Referensi — selaras dengan $columns di index()
        CRUD::column([
            'label'    => trans('backpack::crud.delivery_note.column.reference_type'),
            'name'     => 'reference_type',
            'type'     => 'custom_html',
            'value' => function ($entry) {
                $map = [
                    'quotation'        => ['label' => trans('backpack::crud.delivery_note.field.reference_type.options.quotation'),        'class' => 'bg-info text-white'],
                    'proforma_invoice' => ['label' => trans('backpack::crud.delivery_note.field.reference_type.options.proforma_invoice'), 'class' => 'bg-warning text-dark'],
                    'client_po'        => ['label' => trans('backpack::crud.delivery_note.field.reference_type.options.client_po'),        'class' => 'bg-secondary text-white'],
                    'invoice_client'   => ['label' => trans('backpack::crud.delivery_note.field.reference_type.options.invoice_client'),   'class' => 'bg-success text-white'],
                ];
                $type = $entry->reference_type;
                if (!$type || !isset($map[$type])) {
                    return '<span class="badge bg-light text-dark">-</span>';
                }
                return '<span class="badge ' . $map[$type]['class'] . '">' . e($map[$type]['label']) . '</span>';
            },
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.delivery_note.column.description'),
            'name'   => 'description',
            'type'   => 'text',
        ]);

        CRUD::column([
            'label'      => trans('backpack::crud.delivery_note.column.information'),
            'name'       => 'information',
            'type'       => 'wrap_text',
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(DeliveryNoteRequest::class);

        CRUD::addField([
            'name' => 'logic_delivery_note',
            'type' => 'logic_delivery_note',
        ]);

        if (backpack_user()->hasRole('Super Admin')) {
            $companies = \App\Models\Company::pluck('name', 'id')->toArray();
            CRUD::addField([
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'select2_array',
                'name'      => 'company_id',
                'options'   => ['' => trans('backpack::crud.filter.all_company') ?? 'All (Semua Perusahaan)'] + $companies,
                'wrapper'   => ['class' => 'form-group col-md-6'],
            ]);
        }

        // Field: Jenis Referensi Dokumen
        CRUD::addField([
            'name'        => 'reference_type',
            'label'       => trans('backpack::crud.delivery_note.field.reference_type.label'),
            'type'        => 'select_from_array',
            'options'     => [
                'quotation'        => trans('backpack::crud.delivery_note.field.reference_type.options.quotation'),
                'proforma_invoice' => trans('backpack::crud.delivery_note.field.reference_type.options.proforma_invoice'),
                'client_po'        => trans('backpack::crud.delivery_note.field.reference_type.options.client_po'),
                // 'invoice_client'   => trans('backpack::crud.delivery_note.field.reference_type.options.invoice_client'),
            ],
            'allows_null' => true,
            'wrapper'     => ['class' => 'form-group col-md-6'],
        ]);

        // Field: No. Dokumen Referensi — Select2 AJAX, data_source diubah via JS sesuai reference_type
        CRUD::addField([
            'label'       => trans('backpack::crud.delivery_note.field.reference_id.label'),
            'type'        => 'select2_ajax_custom',
            'name'        => 'reference_id',
            'entity'      => false,
            'attribute'   => 'text',
            'data_source' => backpack_url('client/delivery-note/select2-invoice'),
            'dependencies' => ['company_id', 'reference_type'],
            'include_all_form_fields' => true,
            'wrapper'   => ['class' => 'form-group col-md-6'],
            'attributes' => [
                'placeholder' => trans('backpack::crud.delivery_note.field.reference_id.placeholder'),
            ],
        ]);

        CRUD::addField([
            'label'        => trans('backpack::crud.delivery_note.field.client_id.label'),
            'type'         => 'select2_ajax_custom',
            'name'         => 'client_id',
            'entity'       => 'client',
            'attribute'    => 'name',
            'data_source'  => backpack_url('client/select2-client'),
            'dependencies' => ['company_id'],
            'include_all_form_fields' => true,
            'wrapper'   => ['class' => 'form-group col-md-6'],
            'attributes' => [
                'placeholder' => trans('backpack::crud.delivery_note.field.client_id.placeholder'),
            ],
        ]);

        CRUD::addField([
            'name'  => 'address',
            'type'  => 'textarea',
            'label' => trans('backpack::crud.delivery_note.field.address.label'),
            'wrapper'   => ['class' => 'form-group col-md-12'],
            'attributes' => [
                'placeholder' => trans('backpack::crud.delivery_note.field.address.placeholder'),
                'rows' => 3,
            ],
        ]);

        CRUD::addField([
            'name'  => 'date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.delivery_note.field.date.label'),
            'date_picker_options' => ['language' => App::getLocale()],
            'wrapper'   => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name'    => 'number',
            'type'    => 'text',
            'label'   => trans('backpack::crud.delivery_note.field.number.label'),
            'default' => $this->repository->generateNextNumber(),
            'wrapper' => ['class' => 'form-group col-md-6'],
            'attributes' => [
                'placeholder' => trans('backpack::crud.delivery_note.field.number.placeholder'),
            ],
        ]);

        CRUD::addField([
            'name'  => 'description',
            'type'  => 'text',
            'label' => trans('backpack::crud.delivery_note.field.description.label'),
            'wrapper'   => ['class' => 'form-group col-md-12'],
            'attributes' => [
                'placeholder' => trans('backpack::crud.delivery_note.field.description.placeholder'),
            ],
        ]);

        CRUD::addField([
            'name'  => 'information',
            'type'  => 'text',
            'label' => trans('backpack::crud.delivery_note.field.information.label'),
            'wrapper'   => ['class' => 'form-group col-md-12'],
            'attributes' => [
                'placeholder' => trans('backpack::crud.delivery_note.field.information.placeholder'),
            ],
        ]);

        CRUD::addField([
            'name'  => 'invoice_items_table',
            'type'  => 'custom_html',
            'value' => '<div class="form-group col-md-12"><label class="font-weight-bold">' . trans('backpack::crud.delivery_note.field.items.header') . '</label><div id="delivery_note_invoice_items_container"><table class="table table-bordered table-sm align-middle" id="table-invoice-items"><thead class="table-dark"><tr><th class="text-center" style="width:40px">#</th><th style="width:35%">' . trans('backpack::crud.delivery_note.field.items.select_stock_placeholder') . '</th><th>' . trans('backpack::crud.delivery_note.field.items.description_placeholder') . '</th><th class="text-center" style="width:100px">' . trans('backpack::crud.delivery_note.field.items.qty') . '</th><th class="text-center" style="width:60px">' . trans('backpack::crud.delivery_note.field.items.action') . '</th></tr></thead><tbody></tbody></table><div class="mt-2"><button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-dn-item"><i class="la la-plus"></i> ' . trans('backpack::crud.delivery_note.field.items.add_row') . '</button></div></div></div>',
            'wrapper' => ['class' => 'form-group col-md-12'],
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        // get entry ID from Request (makes sure its the last ID for nested resources)
        $id = $this->crud->getCurrentEntryId() ?? $id;

        $this->crud->delete($id);

        $messages['success'][] = trans('backpack::crud.delete_confirmation_message');
        $messages['events'] = [
            'crudTable-filter_delivery_note_plugin_load' => true,
            'crudTable-delivery_note_create_success' => true,
        ];
        return response()->json($messages);
    }

    /**
     * Custom show method to return AJAX modal render JSON
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
     * Define what happens when the Show operation is loaded.
     */
    protected function setupShowOperation()
    {
        $this->setupCreateOperation();
        CRUD::removeField('logic_delivery_note');
        CRUD::removeField('invoice_items_table');

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

        CRUD::column([
            'label'    => trans('backpack::crud.delivery_note.field.reference_type.label'),
            'name'     => 'reference_type',
            'type'     => 'closure',
            'function' => function ($entry) {
                $map = [
                    'quotation'        => trans('backpack::crud.delivery_note.field.reference_type.options.quotation'),
                    'proforma_invoice' => trans('backpack::crud.delivery_note.field.reference_type.options.proforma_invoice'),
                    'client_po'        => trans('backpack::crud.delivery_note.field.reference_type.options.client_po'),
                    'invoice_client'   => trans('backpack::crud.delivery_note.field.reference_type.options.invoice_client'),
                ];
                return $map[$entry->reference_type] ?? '-';
            },
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.delivery_note.field.reference_id.label'),
            'name'  => 'reference_number',
            'type'  => 'closure',
            'function' => function ($entry) {
                return $entry->reference_number ?? '-';
            },
        ]);

        CRUD::column([
            'label'     => trans('backpack::crud.delivery_note.field.client_id.label'),
            'type'      => 'select',
            'name'      => 'client_id',
            'entity'    => 'client',
            'attribute' => 'name',
            'model'     => "App\Models\Client",
        ]);

        CRUD::column([
            'name'  => 'address',
            'type'  => 'textarea',
            'label' => trans('backpack::crud.delivery_note.field.address.label'),
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.delivery_note.field.date.label'),
            'name'   => 'date',
            'type'   => 'date',
            'format' => 'DD/MM/YYYY',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.delivery_note.field.number.label'),
            'name'   => 'number',
            'type'   => 'text',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.delivery_note.field.description.label'),
            'name'   => 'description',
            'type'   => 'text',
        ]);

        CRUD::column([
            'label'     => trans('backpack::crud.delivery_note.field.information.label'),
            'name'      => 'information',
            'width_box' => '100%',
            'type'      => 'wrap_text',
        ]);

        CRUD::column([
            'name'      => 'details_table',
            'label'     => trans('backpack::crud.delivery_note.field.items.header'),
            'width_box' => '100%',
            'type'      => 'custom_html',
            'value'     => function ($entry) {
                if (!$entry->details || $entry->details->count() === 0) {
                    return '<span class="text-muted">' . trans('backpack::crud.delivery_note.field.items.empty') . '</span>';
                }
                $html = '<table class="table table-bordered table-sm m-0"><thead class="table-dark"><tr><th class="text-center" style="width:40px">#</th><th>Nama Barang / Deskripsi</th><th class="text-center" style="width:80px">QTY</th></tr></thead><tbody>';
                foreach ($entry->details as $idx => $detail) {
                    $name = e($detail->device_stock?->name ? ($detail->device_stock->name . ($detail->description && $detail->description !== $detail->device_stock->name ? ' - ' . $detail->description : '')) : ($detail->description ?? '-'));
                    $qty  = (int) $detail->qty;
                    $html .= '<tr><td class="text-center">' . ($idx + 1) . '</td><td>' . $name . '</td><td class="text-center"><strong>' . $qty . '</strong></td></tr>';
                }
                $html .= '</tbody></table>';
                return $html;
            },
        ]);
    }

    public function printDeliveryNote($id)
    {
        $this->crud->hasAccessOrFail('show');
        $entry = $this->crud->getEntry($id);
        $entry->load(['details.device_stock', 'client', 'company']);

        $pdf = Pdf::loadView('exports.delivery-note-pdf', [
            'entry' => $entry,
        ]);

        $filename = 'SURAT_JALAN-' . str_replace(['/', '\\'], '-', $entry->number ?? $entry->id) . '.pdf';

        return $pdf->stream($filename);
    }

    public function select2Invoice()
    {
        $request    = request();
        $search     = $request->input('q');
        $company_id = $request->input('company_id');

        $query = \App\Models\InvoiceClient::select(['id', 'invoice_number', 'description']);

        if ($request->has('company_id') && $company_id !== '') {
            $query->where('company_id', $company_id);
        } else if (backpack_user() && !backpack_user()->hasRole('Super Admin')) {
            $query->where('company_id', backpack_user()->company_id);
        }

        $dataset = $query->where('invoice_number', 'LIKE', "%$search%")->paginate(10);

        $results = [];
        foreach ($dataset as $item) {
            $results[] = ['id' => $item->id, 'text' => $item->invoice_number];
        }
        return response()->json(['results' => $results]);
    }

    /**
     * Select2 AJAX untuk Penawaran (Quotation).
     */
    public function select2Quotation()
    {
        $request    = request();
        $search     = $request->input('q');
        $company_id = $request->input('company_id');

        $query = \App\Models\ClientQuotation::select(['id', 'po_number', 'job_name']);

        if ($request->has('company_id') && $company_id !== '') {
            $query->where('company_id', $company_id);
        } else if (backpack_user() && !backpack_user()->hasRole('Super Admin')) {
            $query->where('company_id', backpack_user()->company_id);
        }

        $dataset = $query->where(function ($q) use ($search) {
            $q->where('po_number', 'LIKE', "%$search%")
              ->orWhere('job_name', 'LIKE', "%$search%");
        })->paginate(10);

        $results = [];
        foreach ($dataset as $item) {
            $results[] = [
                'id'   => $item->id,
                'text' => $item->po_number . ($item->job_name ? ' — ' . $item->job_name : ''),
            ];
        }
        return response()->json(['results' => $results]);
    }

    /**
     * Select2 AJAX untuk Proforma Invoice Client.
     */
    public function select2ProformaInvoice()
    {
        $request    = request();
        $search     = $request->input('q');
        $company_id = $request->input('company_id');

        $query = \App\Models\ProformaInvoiceClient::select(['id', 'invoice_number', 'description']);

        if ($request->has('company_id') && $company_id !== '') {
            $query->where('company_id', $company_id);
        } else if (backpack_user() && !backpack_user()->hasRole('Super Admin')) {
            $query->where('company_id', backpack_user()->company_id);
        }

        $dataset = $query->where('invoice_number', 'LIKE', "%$search%")->paginate(10);

        $results = [];
        foreach ($dataset as $item) {
            $results[] = ['id' => $item->id, 'text' => $item->invoice_number];
        }
        return response()->json(['results' => $results]);
    }

    /**
     * Ambil detail Penawaran (Quotation) untuk prefill form.
     */
    public function getQuotationDetails()
    {
        $this->crud->hasAccessOrFail('create');
        $id        = (int) request()->input('reference_id');
        $quotation = \App\Models\ClientQuotation::with(['details.deviceStock', 'client'])->find($id);

        if (!$quotation) {
            return response()->json(['success' => false, 'message' => 'Quotation tidak ditemukan'], 404);
        }

        $items = [];
        if ($quotation->details && $quotation->details->count() > 0) {
            foreach ($quotation->details as $detail) {
                $dsName = $detail->deviceStock 
                    ? $detail->deviceStock->name . ' (' . $detail->deviceStock->code . ')'
                    : null;
                $items[] = [
                    'device_stock_id'   => $detail->device_stock_id,
                    'device_stock_text' => $dsName,
                    'name'              => $detail->item_name ?? $detail->deviceStock?->name ?? '-',
                    'qty'               => (float) $detail->qty,
                ];
            }
        } elseif ($quotation->job_name) {
            $items[] = ['name' => $quotation->job_name, 'qty' => 1];
        }

        return response()->json([
            'success'      => true,
            'client_id'    => $quotation->client_id ?? '',
            'client_name'  => $quotation->client?->name ?? '',
            'address'      => $quotation->client?->address ?? '',
            'description'  => $quotation->job_name ?? '',
            'items'        => $items,
        ]);
    }

    /**
     * Ambil detail Proforma Invoice Client untuk prefill form.
     */
    public function getProformaInvoiceDetails()
    {
        $this->crud->hasAccessOrFail('create');
        $id      = (int) request()->input('reference_id');
        $invoice = \App\Models\ProformaInvoiceClient::with(['proforma_invoice_client_details.deviceStock', 'client'])->find($id);

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Proforma Invoice tidak ditemukan'], 404);
        }

        $items = [];
        foreach ($invoice->proforma_invoice_client_details as $detail) {
            $dsName = $detail->deviceStock 
                ? $detail->deviceStock->name . ' (' . $detail->deviceStock->code . ')'
                : null;
            $items[] = [
                'device_stock_id'   => $detail->device_stock_id ?? null,
                'device_stock_text' => $dsName,
                'name'              => $detail->name ?? $detail->deviceStock?->name ?? '-',
                'qty'               => (float) ($detail->qty ?? 1),
            ];
        }

        return response()->json([
            'success'      => true,
            'client_id'    => $invoice->client_id ?? '',
            'client_name'  => $invoice->client?->name ?? '',
            'address'      => $invoice->address_po ?? $invoice->client?->address ?? '',
            'description'  => $invoice->description ?? '',
            'items'        => $items,
        ]);
    }

    /**
     * Ambil detail Invoice Client untuk prefill form Surat Jalan.
     */
    public function getInvoiceDetails()
    {
        $this->crud->hasAccessOrFail('create');
        $id      = (int) request()->input('reference_id');
        $invoice = \App\Models\InvoiceClient::with(['invoice_client_details.deviceStock', 'client'])->find($id);

        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice tidak ditemukan'], 404);
        }

        $items = [];
        foreach ($invoice->invoice_client_details as $detail) {
            $dsName = $detail->deviceStock 
                ? $detail->deviceStock->name . ' (' . $detail->deviceStock->code . ')'
                : null;
            $items[] = [
                'device_stock_id'   => $detail->device_stock_id ?? null,
                'device_stock_text' => $dsName,
                'name'              => $detail->name ?? $detail->deviceStock?->name ?? '-',
                'qty'               => (float) ($detail->qty ?? 1),
            ];
        }

        return response()->json([
            'success'      => true,
            'client_id'    => $invoice->client_id ?? '',
            'client_name'  => $invoice->client?->name ?? '',
            'address'      => $invoice->address_po ?? $invoice->client?->address ?? '',
            'description'  => $invoice->description ?? '',
            'items'        => $items,
        ]);
    }

    /**
     * Ambil detail Client PO untuk prefill form Surat Jalan.
     */
    public function getClientPoDetails()
    {
        $this->crud->hasAccessOrFail('create');
        $id = (int) request()->input('reference_id');
        $po = \App\Models\ClientPo::with(['quotations.details.deviceStock', 'client'])->find($id);

        if (!$po) {
            return response()->json(['success' => false, 'message' => 'Client PO tidak ditemukan'], 404);
        }

        $items = [];
        if ($po->quotations && $po->quotations->count() > 0) {
            foreach ($po->quotations as $quotation) {
                foreach ($quotation->details as $detail) {
                    $dsName = $detail->deviceStock 
                        ? $detail->deviceStock->name . ' (' . $detail->deviceStock->code . ')'
                        : null;
                    $items[] = [
                        'device_stock_id'   => $detail->device_stock_id,
                        'device_stock_text' => $dsName,
                        'name'              => $detail->item_name ?? $detail->deviceStock?->name ?? '-',
                        'qty'               => (float) $detail->qty,
                    ];
                }
            }
        }

        if (empty($items) && $po->job_name) {
            $items[] = ['name' => $po->job_name, 'qty' => 1];
        }

        return response()->json([
            'success'     => true,
            'client_id'   => $po->client_id ?? '',
            'client_name' => $po->client?->name ?? '',
            'address'     => $po->client?->address ?? '',
            'description' => $po->job_name ?? '',
            'items'       => $items,
        ]);
    }

    public function getPoDetails()
    {
        $this->crud->hasAccessOrFail('create');
        $id = request()->input('po_id');
        $po = $this->clientPoRepository->findWithClient((int) $id);

        return response()->json([
            'client_id'   => $po?->client_id ?? '',
            'client_name' => $po?->client?->name ?? '',
            'address'     => $po?->client?->address ?? '',
            'job_name'    => $po?->job_name ?? '',
        ]);
    }

    /**
     * Endpoint Select2 AJAX untuk daftar Device Stock (Persediaan) pada Surat Jalan.
     */
    public function select2DeviceStock()
    {
        if (!$this->crud->hasAccess('create') && !$this->crud->hasAccess('update') && !$this->crud->hasAccess('list')) {
            abort(403);
        }

        $search = request()->input('q', '');

        $query = \App\Models\DeviceStock::select(['id', 'name', 'code', 'qty', 'sell_price']);

        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('code', 'LIKE', "%{$search}%");
            });
        }

        $dataset = $query->where('qty', '>', 0)->paginate(20);

        $results = [];
        foreach ($dataset as $item) {
            $results[] = [
                'id'    => $item->id,
                'text'  => $item->name . ' (' . $item->code . ') (Stok: ' . $item->qty . ')',
                'name'  => $item->name,
                'qty'   => $item->qty,
            ];
        }
        return response()->json(['results' => $results]);
    }
}
