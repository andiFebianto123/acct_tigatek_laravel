<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\SubkonManagement\SubkonData;
use App\Http\Controllers\Operation\PermissionAccess;
use App\Http\Exports\ExportExcel;
use App\Http\Helpers\CustomHelper;
use App\Http\Requests\SubkonRequest;
use App\Models\PurchaseOrder;
use App\Models\Spk;
use App\Repositories\SubkonManagement\SubkonRepository;
use App\Services\SubkonManagement\SubkonService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Class SubkonCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class SubkonCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;

    protected $subkonService;
    protected $subkonRepository;

    public function __construct(
        SubkonService $subkonService,
        SubkonRepository $subkonRepository
    ) {
        parent::__construct();
        $this->subkonService = $subkonService;
        $this->subkonRepository = $subkonRepository;
    }

    public $card, $modal, $script;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Subkon::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/vendor/subkon');
        CRUD::setEntityNameStrings(trans('backpack::crud.subkon.title_header'), trans('backpack::crud.subkon.title_header'));
        $this->card = app('component.card');
        $this->modal = app('component.modal');
        $this->script = app('component.script');

        $this->settingPermission([
            'create' => [
                "CREATE INDEX VENDOR DAFTAR SUBKON",
            ],
            'update' => [
                "UPDATE INDEX VENDOR DAFTAR SUBKON",
            ],
            'delete' => [
                "DELETE INDEX VENDOR DAFTAR SUBKON",
            ],
            'list' => [
                'MENU INDEX VENDOR DAFTAR SUBKON'
            ],
            'show' => [
                'MENU INDEX VENDOR DAFTAR SUBKON'
            ],
            'print' => true,
        ]);
    }

    private function setupCard() {}

    private function setupModal() {}

    public function setupComponent()
    {
        $this->setupCard();
        $this->setupModal();
    }

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation()
    {
        CRUD::disableResponsiveTable();
        $request = request();

        $this->crud->file_title_export_pdf = "Laporan_daftar_subkon.pdf";
        $this->crud->file_title_export_excel = "Laporan_daftar_subkon.xlsx";
        $this->crud->param_uri_export = "?export=1";

        CRUD::addButtonFromView('top', 'export-excel-table', 'export-excel-table', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-table', 'export-pdf-table', 'beginning');

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
            CRUD::addColumn([
                'name'      => 'company',
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'select',
                'entity'    => 'company',
                'attribute' => 'name',
                'model'     => "App\Models\Company",
            ]);
            CRUD::addClause('with', 'company');
        }

        CRUD::addColumn([
            'name'  => 'name',
            'label' => trans('backpack::crud.subkon.column.name'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'address',
            'label' => trans('backpack::crud.subkon.column.address'),
            'type'  => 'wrap_text',
        ]);

        CRUD::addColumn([
            'name'  => 'npwp',
            'label' => trans('backpack::crud.subkon.column.npwp'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'phone',
            'label' => trans('backpack::crud.subkon.column.phone'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'bank_name',
            'label' => trans('backpack::crud.subkon.column.bank_name'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'bank_account',
            'label' => trans('backpack::crud.subkon.column.bank_account'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'account_holder_name',
            'label' => trans('backpack::crud.subkon.column.account_holder_name'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'     => 'list_po_count',
            'label'    => trans('backpack::crud.subkon.column.count_po'),
            'type'     => 'custom_html',
            'value' => function ($entry) {
                $count_data = $entry->purchase_orders->count();
                if ($count_data > 0) {
                    return "<a href='" . url('admin/vendor/purchase-order') . "'>" . $count_data . "</a>";
                }
                return '-';
            },
            'orderable'  => true,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $this->subkonRepository->orderByPoCount($query, $columnDirection);
            }
        ]);

        CRUD::addColumn([
            'name'     => 'list_spk_count',
            'label'    => trans('backpack::crud.subkon.column.count_spk'),
            'type'     => 'custom_html',
            'value' => function ($entry) {
                $count_data = $entry->spks->count();
                if ($count_data > 0) {
                    return "<a href='" . url('admin/vendor/spk-trans') . "'>" . $count_data . "</a>";
                }
                return '-';
            },
            'orderable'  => true,
            'orderLogic' => function ($query, $column, $columnDirection) {
                return $this->subkonRepository->orderBySpkCount($query, $columnDirection);
            }
        ]);

        if ($request->has('filter_year')) {
            $this->crud->query = $this->subkonRepository->applyYearFilter($this->crud->query, $request->filter_year);
        }
    }

    private function setupListExport()
    {
        $this->setupListOperation();
    }

    public function exportPdf()
    {
        $this->setupListExport();

        $columns = $this->crud->columns();
        $items =  $this->crud->getEntries();

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
            'title' => "DAFTAR SUBKON"
        ])->setPaper('A4', 'landscape');

        $fileName = 'vendor_subkon_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportExcel()
    {
        $this->setupListExport();

        $columns = $this->crud->columns();
        $items =  $this->crud->getEntries();

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

        $name = 'DAFTAR_SUBKON_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($columns, $all_items) {
            echo Excel::raw(new ExportExcel($columns, $all_items), \Maatwebsite\Excel\Excel::XLSX);
        }, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }

    public function index()
    {
        $this->crud->hasAccessOrFail('list');
        $this->setupComponent();

        $this->data['cards'] = $this->card;
        $this->data['modals'] = $this->modal;
        $this->data['scripts'] = $this->script;
        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = "Data Vendor (Subkon)";
        $this->data['title_modal_edit'] = "Data Vendor (Subkon)";
        $this->data['title_modal_delete'] = "Vendor (Subkon)";

        $breadcrumbs = [
            'Vendor (Subkon)' => backpack_url('vendor'),
            trans($this->data['title']) => backpack_url($this->crud->route)
        ];

        $this->data['breadcrumbs'] = $breadcrumbs;

        return view("crud::list-custom", $this->data);
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
            $data = SubkonData::fromRequest($request);
            $item = $this->subkonService->createSubkon($data);

            \Alert::success(trans('backpack::crud.insert_success'))->flash();
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
            $data = SubkonData::fromRequest($request);
            $item = $this->subkonService->updateSubkon($request->get($this->crud->model->getKeyName()), $data);

            \Alert::success(trans('backpack::crud.update_success'))->flash();
            return $this->crud->performSaveAction($item->getKey());
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');
        $id = $this->crud->getCurrentEntryId() ?? $id;

        try {
            $this->subkonService->deleteSubkon($id);
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

    protected function setupCreateOperation()
    {
        CRUD::setValidation(SubkonRequest::class);

        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::addField([
                'name'      => 'company_id',
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'select2_array',
                'options'   => \App\Models\Company::all()->pluck('name', 'id')->toArray(),
                'allows_null' => false,
                'wrapper'   => ['class' => 'form-group col-md-6'],
            ]);
        }

        CRUD::addField([
            'name' => 'name',
            'label' => trans('backpack::crud.subkon.column.name'),
            'type' => 'text',
        ]);

        CRUD::addField([
            'name' => 'address',
            'label' => trans('backpack::crud.subkon.column.address'),
            'type' => 'text',
        ]);

        CRUD::addField([
            'name' => 'npwp',
            'label' => trans('backpack::crud.subkon.column.npwp'),
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'phone',
            'label' => trans('backpack::crud.subkon.column.phone'),
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::field([
            'label'     => trans('backpack::crud.cash_account.field.bank_name.label'),
            'type'      => 'select2_bank_tags',
            'name'      => 'bank_name',
            'options'   => ['' => trans('backpack::crud.cash_account.field.bank_name.placeholder'), ...CustomHelper::getBanks()],
            'wrapper' => ['class' => 'form-group col-md-6']
        ]);

        CRUD::addField([
            'name' => 'bank_account',
            'label' => trans('backpack::crud.subkon.column.bank_account'),
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'account_holder_name',
            'label' => trans('backpack::crud.subkon.column.account_holder_name'),
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
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

    protected function setupShowOperation()
    {
        // column
        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::addField([
                'name'      => 'company',
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'select',
                'entity'    => 'company',
                'attribute' => 'name',
                'model'     => "App\Models\Company",
            ]);
        }

        CRUD::addField([
            'name' => 'name',
            'label' => trans('backpack::crud.subkon.column.name'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12'
            ],
        ]);
        CRUD::addField([
            'name' => 'address',
            'label' => trans('backpack::crud.subkon.column.address'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12'
            ],
        ]);
        CRUD::addField([
            'name' => 'npwp',
            'label' => trans('backpack::crud.subkon.column.npwp'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);
        CRUD::addField([
            'name' => 'phone',
            'label' => trans('backpack::crud.subkon.column.phone'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);
        CRUD::field([  // Select2
            'label'     => trans('backpack::crud.subkon.column.bank_name'),
            'type'      => 'select2_array',
            'name'      => 'bank_name',
            'options'   => CustomHelper::getBanks(), // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);
        CRUD::addField([
            'name' => 'bank_account',
            'label' => trans('backpack::crud.subkon.column.bank_account'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);
        CRUD::addField([
            'name' => 'account_holder_name',
            'label' => trans('backpack::crud.subkon.column.account_holder_name'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12'
            ],
        ]);

        CRUD::addField([
            'name' => 'list_po',
            'label' => trans('backpack::crud.subkon.column.list_po'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'list_spk',
            'label' => trans('backpack::crud.subkon.column.list_spk'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'count_po',
            'label' => trans('backpack::crud.subkon.column.count_po'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'count_spk',
            'label' => trans('backpack::crud.subkon.column.count_spk'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        // column
        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::addColumn([
                'name'      => 'company',
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'closure',
                'function' => function ($entry) {
                    return $entry->company->name;
                }
            ]);
        }

        CRUD::addColumn([
            'name'  => 'name',
            'label' => trans('backpack::crud.subkon.column.name'),
            'type'  => 'wrap_text',
            'width_box' => '350px',
        ]);
        CRUD::addColumn([
            'name'  => 'address',
            'label' => trans('backpack::crud.subkon.column.address'),
            'type'  => 'wrap_text',
            'width_box' => '350px',
        ]);

        CRUD::addColumn([
            'name'  => 'npwp',
            'label' => trans('backpack::crud.subkon.column.npwp'),
            'type'  => 'wrap_text',
            'width_box' => '350px',
        ]);

        CRUD::addColumn([
            'name'  => 'phone',
            'label' => trans('backpack::crud.subkon.column.phone'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'bank_name',
            'label' => trans('backpack::crud.subkon.column.bank_name'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'bank_account',
            'label' => trans('backpack::crud.subkon.column.bank_account'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'account_holder_name',
            'label' => trans('backpack::crud.subkon.column.account_holder_name'),
            'type'  => 'wrap_text',
            'width_box' => '350px',
        ]);

        CRUD::addColumn([
            'name'     => 'list_po',
            'label'    => trans('backpack::crud.subkon.column.list_po'),
            'type'     => 'custom_html',
            'value' => function ($entry) {
                return "<ul style='margin: 8px; padding: 0;'>" . $entry->purchase_orders->map(function ($item, $key) {
                    return "<li>" . $item->po_number . "</li>";
                })->implode('') . "</ul>";
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('purchase_orders', function ($q) use ($column, $searchTerm) {
                    $q->where('po_number', 'like', '%' . $searchTerm . '%');
                });
            }
        ]);

        CRUD::addColumn([
            'name'     => 'list_spk',
            'label'    => trans('backpack::crud.subkon.column.list_spk'),
            'type'     => 'custom_html',
            'value' => function ($entry) {
                return "<ul style='margin: 8px; padding: 0;'>" . $entry->spks->map(function ($item, $key) {
                    return "<li>" . $item->no_spk . "</li>";
                })->implode('') . "</ul>";
            },
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('spks', function ($q) use ($column, $searchTerm) {
                    $q->where('no_spk', 'like', '%' . $searchTerm . '%');
                });
            }
        ]);

        CRUD::addColumn([
            'name'     => 'list_po_count',
            'label'    => trans('backpack::crud.subkon.column.count_po'),
            'type'     => 'custom_html',
            'value' => function ($entry) {
                $count_data = $entry->purchase_orders->count();
                if ($count_data > 0) {
                    return "<a href='" . url('admin/vendor/purchase-order') . "'>" . $count_data . "</a>";
                }
                return '-';
            },
        ]);

        CRUD::addColumn([
            'name'     => 'list_spk_count',
            'label'    => trans('backpack::crud.subkon.column.count_spk'),
            'type'     => 'custom_html',
            'value' => function ($entry) {
                $count_data = $entry->spks->count();
                if ($count_data > 0) {
                    return "<a href='" . url('admin/vendor/spk-trans') . "'>" . $count_data . "</a>";
                }
                return '-';
            },
        ]);
    }
}
