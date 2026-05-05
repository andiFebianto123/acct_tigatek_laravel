<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Operation\PermissionAccess;
use App\Models\ClientPo;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Exports\ExportExcel;
use App\Http\Helpers\CustomHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use App\Http\Requests\ClientRequest;
use Maatwebsite\Excel\Facades\Excel;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

use App\DTOs\ClientManagement\ClientData;
use App\DTOs\ClientManagement\ClientFilterData;
use App\Services\ClientManagement\ClientService;
use App\Repositories\ClientManagement\ClientRepository;
use App\Models\Company;

/**
 * Class ClientCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ClientCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;
    
    protected $clientService;
    protected $clientRepository;

    public function __construct(
        ClientService $clientService,
        ClientRepository $clientRepository
    ) {
        parent::__construct();
        $this->clientService = $clientService;
        $this->clientRepository = $clientRepository;
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Client::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/client/client-list');
        CRUD::setEntityNameStrings(trans('backpack::crud.client.title_header'), trans('backpack::crud.client.title_header'));
        
        $allAccess = [
            'AKSES SEMUA MENU ACCOUNTING',
            'AKSES MENU CLIENT'
        ];

        $viewMenu = [
            'MENU INDEX CLIENT DAFTAR CLIENT'
        ];

        $this->settingPermission([
            'create' => [
                'CREATE INDEX CLIENT DAFTAR CLIENT',
                ...$allAccess,
            ],
            'update' => [
                'UPDATE INDEX CLIENT DAFTAR CLIENT',
                ...$allAccess,
            ],
            'delete' => [
                'DELETE INDEX CLIENT DAFTAR CLIENT',
                ...$allAccess,
            ],
            'list' => $viewMenu,
            'show' => $viewMenu,
            'print' => true,
        ]);

    }

     public function index()
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.menu.list_client');
        $this->data['title_modal_edit'] = 'Data Client';
        $this->data['title_modal_delete'] = 'Client';

        $breadcrumbs = [
            'Client' => backpack_url('vendor'),
            trans('backpack::crud.menu.list_client') => backpack_url($this->crud->route)
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;

        $list = "crud::list-custom" ?? $this->crud->getListView();
        return view($list, $this->data);
    }

    public function create()
    {
        $this->crud->hasAccessOrFail('create');

        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.add').' '.$this->crud->entity_name;

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
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit').' '.$this->crud->entity_name;
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
        // CRUD::setFromDb(); // set columns from db columns.

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
        $request = request();

        $this->crud->file_title_export_pdf = "Laporan_daftar_client.pdf";
        $this->crud->file_title_export_excel = "Laporan_daftar_client.xlsx";
        $this->crud->param_uri_export = "?export=1";
        CRUD::addButtonFromView('top', 'export-excel-table', 'export-excel-table', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-table', 'export-pdf-table', 'beginning');

        // CRUD::addButtonFromView('top', 'filter_year', 'filter-year-client', 'beginning');
        CRUD::disableResponsiveTable();

        $filters = ClientFilterData::fromRequest($request);
        $this->crud->query = $this->clientRepository->getFilteredData($filters);

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
                'label'  => trans('backpack::crud.client.column.name'),
                'name' => 'name',
                'type'  => 'text'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client.column.address'),
                'name' => 'address',
                'type'  => 'text'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client.column.npwp'),
                'name' => 'npwp',
                'type'  => 'text'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client.column.phone'),
                'name' => 'phone',
                'type'  => 'text'
            ],
        );

        // CRUD::addColumn([
        //     'name'     => 'list_po_number',
        //     'label'    => trans('backpack::crud.subkon.column.list_po'),
        //     'type'     => 'custom_html',
        //     'value' => function($entry) {
        //         return "".$entry->client_po->map(function($item, $key){
        //             return "<li>".$item->po_number."</li>";
        //         })->implode('')."";
        //     },
        //     'searchLogic' => function ($query, $column, $searchTerm) {
        //         $query->orWhereHas('client_po', function ($q) use ($column, $searchTerm) {
        //             $q->where('po_number', 'like', '%'.$searchTerm.'%');
        //         });
        //     }
        // ]);

        CRUD::addColumn([
            'name'     => 'list_po_count',
            'label'    => trans('backpack::crud.subkon.column.count_po'),
            'type'     => 'custom_html',
            'value' => function($entry) {
                $count_data = $entry->client_po->count();
                if($count_data > 0){
                    return $count_data;
                }
                return '-';
            },
            'orderable'  => true,
            'orderLogic' => function ($query, $column, $columnDirection) {
                $po = ClientPo::select(DB::raw('client_id, count(po_number) as total_po'))
                ->groupBy('client_id');
                return $query->leftJoinSub($po, 'po', function($join){
                    $join->on('po.client_id', 'clients.id');
                })->select('clients.*')->orderBy('po.total_po', $columnDirection);
            }
        ]);

        if($request->has('filter_year')){
            if($request->filter_year != 'all'){
                $filterYear = $request->filter_year;
                $this->crud->query = $this->crud->query
                ->where(function($query) use($filterYear){
                    $query->whereHas('client_po', function($q) use($filterYear){
                        $q->where(DB::raw("YEAR(end_date)"), $filterYear);
                    });
                });
            }
        }



    }

    private function setupListExport(){

        $request = request();

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
                'label'  => trans('backpack::crud.client.column.name'),
                'name' => 'name',
                'type'  => 'export'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client.column.address'),
                'name' => 'address',
                'type'  => 'export'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client.column.npwp'),
                'name' => 'npwp',
                'type'  => 'export'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.client.column.phone'),
                'name' => 'phone',
                'type'  => 'export'
            ],
        );

        // CRUD::addColumn([
        //     'name'     => 'list_po_number',
        //     'label'    => trans('backpack::crud.subkon.column.list_po'),
        //     'type'     => 'custom_html',
        //     'value' => function($entry) {
        //         return "".$entry->client_po->map(function($item, $key){
        //             return "<li>".$item->po_number."</li>";
        //         })->implode('')."";
        //     },
        //     'searchLogic' => function ($query, $column, $searchTerm) {
        //         $query->orWhereHas('client_po', function ($q) use ($column, $searchTerm) {
        //             $q->where('po_number', 'like', '%'.$searchTerm.'%');
        //         });
        //     }
        // ]);

        CRUD::addColumn([
            'name'     => 'list_po_count',
            'label'    => trans('backpack::crud.subkon.column.count_po'),
            'type'     => 'closure',
            'function' => function($entry) {
                $count_data = $entry->client_po->count();
                if($count_data > 0){
                    return $count_data;
                }
                return '-';
            },
            'orderable'  => true,
            'orderLogic' => function ($query, $column, $columnDirection) {
                $po = ClientPo::select(DB::raw('client_id, count(po_number) as total_po'))
                ->groupBy('client_id');
                return $query->leftJoinSub($po, 'po', function($join){
                    $join->on('po.client_id', 'clients.id');
                })->select('clients.*')->orderBy('po.total_po', $columnDirection);
            }
        ]);

        if($request->has('filter_year')){
            if($request->filter_year != 'all'){
                $filterYear = $request->filter_year;
                $this->crud->query = $this->crud->query
                ->where(function($query) use($filterYear){
                    $query->whereHas('client_po', function($q) use($filterYear){
                        $q->where(DB::raw("YEAR(end_date)"), $filterYear);
                    });
                });
            }
        }
    }

    public function exportPdf(){

        // $this->setupListExport();
        $this->setupListOperation();

        $columns = $this->crud->columns();
        $items =  $this->crud->getEntries();

        $row_number = 0;

        $all_items = [];

        foreach($items as $item){
            $row_items = [];
            $row_number++;
            foreach($columns as $column){
                $item_value = ($column['name'] == 'row_number') ? $row_number : $this->crud->getCellView($column, $item, $row_number);
                $item_value = str_replace('<span>', '', $item_value);
                $item_value = str_replace('</span>', '', $item_value);
                $item_value = str_replace("\n", '', $item_value);
                $item_value = CustomHelper::clean_html($item_value);
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $title = "DAFTAR CLIENT";

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
            'Content-Disposition' => 'attachment; filename="'.$fileName.'"',
        ]);
    }

    public function exportExcel(){

        // $this->setupListExport();
        $this->setupListOperation();

        $columns = $this->crud->columns();
        $items =  $this->crud->getEntries();

        $row_number = 0;

        $all_items = [];

        foreach($items as $item){
            $row_items = [];
            $row_number++;
            foreach($columns as $column){
                $item_value = ($column['name'] == 'row_number') ? $row_number : $this->crud->getCellView($column, $item, $row_number);
                $item_value = str_replace('<span>', '', $item_value);
                $item_value = str_replace('</span>', '', $item_value);
                $item_value = str_replace("\n", '', $item_value);
                $item_value = CustomHelper::clean_html($item_value);
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $name = 'DAFTAR CLIENT';

        return response()->streamDownload(function () use($columns, $items, $all_items){
            echo Excel::raw(new ExportExcel(
                $columns, $all_items), \Maatwebsite\Excel\Excel::XLSX);
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
        CRUD::setValidation(ClientRequest::class);
        // CRUD::setFromDb(); // set fields from db columns.

        /**
         * Fields can be defined using the fluent syntax:
         * - CRUD::field('price')->type('number');
         */

        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::addField([
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'select',
                'name'      => 'company_id',
                'entity'    => 'company',
                'attribute' => 'name',
                'model'     => "App\Models\Company",
            ]);
        }

        CRUD::addField([
            'name' => 'name',
            'label' => trans('backpack::crud.client.column.name'),
            'type' => 'text',
        ]);

        CRUD::addField([
            'name' => 'address',
            'label' => trans('backpack::crud.client.column.address'),
            'type' => 'text',
        ]);

        CRUD::addField([
            'name' => 'npwp',
            'label' => trans('backpack::crud.client.column.npwp'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ]
        ]);

        CRUD::addField([
            'name' => 'phone',
            'label' => trans('backpack::crud.client.column.phone'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ]
        ]);

    }

    public function store()
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        try {
            $data = ClientData::fromRequest($request);
            $item = $this->clientService->createClient($data);

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
                    'events' => $this->clientService->getUIEvents($item, 'create'),
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
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function update()
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        try {
            $data = ClientData::fromRequest($request);
            $item = $this->clientService->updateClient($request->get($this->crud->model->getKeyName()), $data);

            \Alert::success(trans('backpack::crud.update_success'))->flash();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
                    'events' => $this->clientService->getUIEvents($item, 'update'),
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

    protected function setupShowOperation()
    {
        $this->setupCreateOperation();
        CRUD::field([
            'name' => 'list_po_count',
            'label' => trans('backpack::crud.client.column.list_po_count'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ]
        ]);
        $this->setupListOperation();
        CRUD::column('row_number')->remove();
        // CRUD::column('list_po_count')->remove();
        // CRUD::addField([
        //     'name' => 'list_po_number',
        //     'label' => trans('backpack::crud.subkon.column.list_po'),
        //     'type' => 'text',
        //     'wrapper'   => [
        //         'class' => 'form-group col-md-6'
        //     ],
        // ]);
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

        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.preview').' '.$this->crud->entity_name;

        // load the view from /resources/views/vendor/backpack/crud/ if it exists, otherwise load the one in the package
        // return view($this->crud->getShowView(), $this->data);
        return response()->json([
            'html' => view($this->crud->getShowView(), $this->data)->render()
        ]);
    }

}
