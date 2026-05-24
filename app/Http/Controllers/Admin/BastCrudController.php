<?php

namespace App\Http\Controllers\Admin;

use App\Models\Bast;
use App\Models\ClientPo;
use App\Models\Client;
use App\Http\Requests\BastRequest;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\FormaterExport;
use App\Http\Controllers\Operation\PermissionAccess;
use App\Http\Exports\ExportExcel;
use App\Http\Helpers\CustomHelper;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Facades\Excel;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Prologue\Alerts\Facades\Alert;

use App\DTOs\ClientManagement\BastData;
use App\DTOs\ClientManagement\BastFilterData;
use App\Services\ClientManagement\BastService;
use App\Repositories\ClientManagement\BastRepository;
use Illuminate\Http\Request;

/**
 * Class BastCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class BastCrudController extends CrudController
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

    public function __construct(
        BastService $service,
        BastRepository $repository
    ) {
        parent::__construct();
        $this->service = $service;
        $this->repository = $repository;
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(Bast::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/client/bast');
        CRUD::setEntityNameStrings(trans('backpack::crud.bast.title_header'), trans('backpack::crud.bast.title_header'));

        $allAccess = [
            'AKSES SEMUA MENU ACCOUNTING',
            'AKSES MENU CLIENT',
        ];

        $viewMenu = [
            'MENU INDEX CLIENT BAST',
        ];

        $this->settingPermission([
            'create' => [
                'CREATE INDEX CLIENT BAST',
                ...$allAccess
            ],
            'update' => [
                'UPDATE INDEX CLIENT BAST',
                ...$allAccess
            ],
            'delete' => [
                'DELETE INDEX CLIENT BAST',
                ...$allAccess
            ],
            'list' => $viewMenu,
            'show' => $viewMenu,
            'print' => true,
        ]);
    }

    public function index()
    {
        $this->crud->hasAccessOrFail('list');

        $this->crud->filter('date11crudTable-bast')
            ->label(trans('backpack::crud.bast.column.date'))
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
                'type'      => 'text',
                'name'      => 'company.name',
            ];
        }

        $columns = array_merge($columns, [
            [
                'name'      => 'number',
                'type'      => 'text',
                'label'     => trans('backpack::crud.bast.column.number'),
                'orderable' => true,
            ],
            [
                'name'      => 'date',
                'type'      => 'date',
                'label'     => trans('backpack::crud.bast.column.date'),
                'orderable' => true,
            ],
            [
                'name'      => 'client.name',
                'type'      => 'text',
                'label'     => trans('backpack::crud.bast.column.client_id'),
                'orderable' => true,
            ],
            [
                'name'      => 'first_party',
                'type'      => 'text',
                'label'     => trans('backpack::crud.bast.column.first_party'),
                'orderable' => true,
            ],
            [
                'name'      => 'description',
                'type'      => 'text',
                'label'     => trans('backpack::crud.bast.column.description'),
                'orderable' => true,
            ],
            [
                'name'      => 'qty',
                'type'      => 'text',
                'label'     => trans('backpack::crud.bast.column.qty'),
                'orderable' => true,
            ],
            [
                'name'      => 'information',
                'type'      => 'text',
                'label'     => trans('backpack::crud.bast.column.information'),
                'orderable' => true,
            ],
            [
                'name' => 'action',
                'type' => 'action',
                'label' =>  trans('backpack::crud.actions'),
            ]
        ]);

        $this->card->addCard([
            'name' => 'bast',
            'line' => 'top',
            'view' => 'crud::components.datatable-origin',
            'params' => [
                'filter' => true,
                'crud_custom' => $this->crud,
                'hide_title' => true,
                'columns' => $columns,
                'filter_table' => collect($this->crud->filters())->slice(0, 1),
                'route' => backpack_url('/client/bast/search'),
            ]
        ]);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.bast.title_header');
        $this->data['title_modal_edit'] = trans('backpack::crud.bast.title_header');
        $this->data['title_modal_delete'] = trans('backpack::crud.bast.title_header');
        $this->data['cards'] = $this->card;
        
        $breadcrumbs = [
            'Client' => backpack_url('client'),
            'BAST' => backpack_url($this->crud->route)
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;
        $this->data['year_options'] = CustomHelper::getYearOptions('basts', 'date');

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
            $data = BastData::fromRequest($request);
            $item = $this->service->createBast($data);
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
            $data = BastData::fromRequest($request);
            $item = $this->service->updateBast($request->get($this->crud->model->getKeyName()), $data);
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
        $this->crud->hasAccessOrFail('create');

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

        $title = "DAFTAR BAST (BERITA ACARA SERAH TERIMA)";

        $pdf = Pdf::loadView('exports.table-pdf', [
            'columns' => $columns,
            'items' => $all_items,
            'title' => $title
        ])->setPaper('A4', 'landscape');

        $fileName = 'bast_' . now()->format('Ymd_His') . '.pdf';

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

        $name = 'DAFTAR BAST';

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
        $this->crud->file_title_export_pdf = "Laporan_daftar_bast.pdf";
        $this->crud->file_title_export_excel = "Laporan_daftar_bast.xlsx";
        $this->crud->param_uri_export = "?export=1";

        CRUD::addButtonFromView('top', 'export-excel-table', 'export-excel-table', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-table', 'export-pdf-table', 'beginning');
        CRUD::addButtonFromView('top', 'filter_year', 'filter-year', 'beginning');

        CRUD::disableResponsiveTable();

        $filters = BastFilterData::fromRequest(request());
        $this->crud->query = $this->repository->getFilteredData($filters);

        CRUD::addColumn([
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

        CRUD::column([
            'label'  => trans('backpack::crud.bast.column.number'),
            'name'   => 'number',
            'type'   => 'text'
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.bast.column.date'),
            'name'   => 'date',
            'type'   => 'date',
            'format' => 'DD/MM/YYYY',
        ]);

        CRUD::column([
            'label'     => trans('backpack::crud.bast.column.client_id'),
            'type'      => 'select',
            'name'      => 'client_id',
            'entity'    => 'client',
            'attribute' => 'name',
            'model'     => "App\Models\Client",
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.bast.column.first_party'),
            'name'   => 'first_party',
            'type'   => 'text'
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.bast.column.description'),
            'name'   => 'description',
            'type'   => 'text'
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.bast.column.qty'),
            'name'   => 'qty',
            'type'   => 'text'
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.bast.column.information'),
            'name'   => 'information',
            'type'   => 'text'
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(BastRequest::class);

        // Scripts to widen parent modal and handle client address autocomplete
        \Backpack\CRUD\app\Library\Widget::add([
            'type' => 'script',
            'content' => '
                $(document).ready(function() {
                    let modal = window.parent.$(".modal-dialog");
                    if (modal.length) {
                        modal.addClass("modal-xl").css("max-width", "90%");
                    }
                    $(".modal-dialog", window.parent.document).addClass("modal-xl").css("max-width", "90%");

                    // Event listener for autofilling client (Pihak Ke 2) address
                    $(document).on("change", "select[name=\'client_id\']", function() {
                        let clientId = $(this).val();
                        if (clientId) {
                            $.ajax({
                                url: "' . backpack_url('client/bast/client-address') . '",
                                type: "GET",
                                data: { client_id: clientId },
                                success: function(response) {
                                    if (response && response.address) {
                                        $("textarea[name=\'address\']").val(response.address);
                                    }
                                }
                            });
                        }
                    });
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
            'label'       => trans('backpack::crud.bast.field.client_po_id.label'),
            'type'        => 'select2_ajax_custom',
            'name'        => 'client_po_id',
            'entity'      => 'client_po',
            'attribute'   => 'po_number',
            'data_source' => backpack_url('client/bast/select2-po'),
            'dependencies' => ['company_id'],
            'include_all_form_fields' => true,
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.bast.field.client_po_id.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name'  => 'first_party',
            'type'  => 'text',
            'label' => trans('backpack::crud.bast.field.first_party.label'),
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.bast.field.first_party.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name'  => 'first_party_address',
            'type'  => 'textarea',
            'label' => trans('backpack::crud.bast.field.first_party_address.label'),
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.bast.field.first_party_address.placeholder'),
                'rows' => 3,
            ]
        ]);

        CRUD::addField([
            'label'       => trans('backpack::crud.bast.field.client_id.label'),
            'type'        => 'select2_ajax_custom',
            'name'        => 'client_id',
            'entity'      => 'client',
            'attribute'   => 'name',
            'data_source' => backpack_url('client/select2-client'),
            'dependencies' => ['company_id'],
            'include_all_form_fields' => true,
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.bast.field.client_id.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name'  => 'address',
            'type'  => 'textarea',
            'label' => trans('backpack::crud.bast.field.address.label'),
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.bast.field.address.placeholder'),
                'rows' => 3,
            ]
        ]);

        CRUD::addField([
            'name'  => 'date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.bast.field.date.label'),
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'number',
            'type'  => 'text',
            'label' => trans('backpack::crud.bast.field.number.label'),
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.bast.field.number.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name'  => 'description',
            'type'  => 'text',
            'label' => trans('backpack::crud.bast.field.description.label'),
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.bast.field.description.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name'  => 'qty',
            'type'  => 'number',
            'label' => trans('backpack::crud.bast.field.qty.label'),
            'default' => 1,
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '1',
                'min' => 1,
            ]
        ]);

        CRUD::addField([
            'name'  => 'information',
            'type'  => 'text',
            'label' => trans('backpack::crud.bast.field.information.label'),
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.bast.field.information.placeholder'),
            ]
        ]);
    }

    /**
     * Define what happens when the Update operation is loaded.
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
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
     * Respects standard synchronized field and column ordering rule.
     */
    protected function setupShowOperation()
    {
        $this->setupCreateOperation();

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
            'label'     => trans('backpack::crud.bast.field.client_po_id.label'),
            'type'      => 'select',
            'name'      => 'client_po_id',
            'entity'    => 'client_po',
            'attribute' => 'po_number',
            'model'     => "App\Models\ClientPo",
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.bast.field.first_party.label'),
            'name'   => 'first_party',
            'type'   => 'text',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.bast.field.first_party_address.label'),
            'name'   => 'first_party_address',
            'type'   => 'textarea',
        ]);

        CRUD::column([
            'label'     => trans('backpack::crud.bast.field.client_id.label'),
            'type'      => 'select',
            'name'      => 'client_id',
            'entity'    => 'client',
            'attribute' => 'name',
            'model'     => "App\Models\Client",
        ]);

        CRUD::column([
            'name'  => 'address',
            'type'  => 'textarea',
            'label' => trans('backpack::crud.bast.field.address.label'),
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.bast.field.date.label'),
            'name'   => 'date',
            'type'   => 'date',
            'format' => 'DD/MM/YYYY',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.bast.field.number.label'),
            'name'   => 'number',
            'type'   => 'text',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.bast.field.description.label'),
            'name'   => 'description',
            'type'   => 'text',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.bast.field.qty.label'),
            'name'   => 'qty',
            'type'   => 'text',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.bast.field.information.label'),
            'name'   => 'information',
            'type'   => 'text',
        ]);
    }
}
