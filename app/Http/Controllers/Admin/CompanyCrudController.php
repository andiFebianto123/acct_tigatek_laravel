<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\CompanyManagement\CompanyData;
use App\Http\Controllers\Operation\PermissionAccess;
use App\Http\Exports\ExportExcel;
use App\Http\Helpers\CustomHelper;
use App\Http\Requests\CompanyRequest;
use App\Repositories\CompanyManagement\CompanyRepository;
use App\Services\CompanyManagement\CompanyService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Prologue\Alerts\Facades\Alert;

/**
 * Class CompanyCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CompanyCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;

    protected $companyService;
    protected $companyRepository;

    public function __construct(
        CompanyService $companyService,
        CompanyRepository $companyRepository
    ) {
        parent::__construct();
        $this->companyService = $companyService;
        $this->companyRepository = $companyRepository;
    }

    public $card, $modal, $script;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Company::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/setting/company');
        CRUD::setEntityNameStrings(trans('backpack::crud.company.title_header'), trans('backpack::crud.company.title_header'));
        $this->card = app('component.card');
        $this->modal = app('component.modal');
        $this->script = app('component.script');

        $allAccess = [
            'AKSES SEMUA MENU ACCOUNTING',
        ];

        $this->settingPermission([
            'create' => [
                "CREATE INDEX PENGATURAN COMPANY",
                ...$allAccess,
            ],
            'update' => [
                "UPDATE INDEX PENGATURAN COMPANY",
                ...$allAccess,
            ],
            'delete' => [
                "DELETE INDEX PENGATURAN COMPANY",
                ...$allAccess,
            ],
            'list' => [
                'MENU INDEX PENGATURAN COMPANY',
                ...$allAccess,
            ],
            'show' => [
                'MENU INDEX PENGATURAN COMPANY',
                ...$allAccess,
            ],
            'print' => true,
        ]);

        $user = backpack_user();
        if ($user && $user->getRoles()->whereIn('name', ['Super Admin'])->count() > 0) {
            $this->crud->allowAccess(['list', 'show', 'create', 'update', 'delete']);
        }
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

        $this->crud->file_title_export_pdf = "Laporan_daftar_company.pdf";
        $this->crud->file_title_export_excel = "Laporan_daftar_company.xlsx";
        $this->crud->param_uri_export = "?export=1";

        CRUD::addButtonFromView('top', 'export-excel-table', 'export-excel-table', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-table', 'export-pdf-table', 'beginning');

        $this->crud->addColumn([
            'name'      => 'row_number',
            'type'      => 'row_number',
            'label'     => 'No',
            'orderable' => false,
            'wrapper'   => [
                'element' => 'strong',
            ]
        ])->makeFirstColumn();

        CRUD::addColumn([
            'name'      => 'logo',
            'label'     => trans('backpack::crud.company.column.logo'),
            'type'      => 'image',
            'disk'      => 'public',
            'height'    => '35px',
            'width'     => 'auto',
            'orderable' => false,
        ]);

        CRUD::addColumn([
            'name'  => 'name',
            'label' => trans('backpack::crud.company.column.name'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'address',
            'label' => trans('backpack::crud.company.column.address'),
            'type'  => 'wrap_text',
        ]);

        CRUD::addColumn([
            'name'  => 'city',
            'label' => trans('backpack::crud.company.column.city'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'province',
            'label' => trans('backpack::crud.company.column.province'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'postal_code',
            'label' => trans('backpack::crud.company.column.postal_code'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'phone',
            'label' => trans('backpack::crud.company.column.phone'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'email',
            'label' => trans('backpack::crud.company.column.email'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'website',
            'label' => trans('backpack::crud.company.column.website'),
            'type'  => 'text',
        ]);
    }

    private function setupListExport()
    {
        $this->crud->addColumn([
            'name'      => 'row_number',
            'type'      => 'row_number',
            'label'     => 'No',
            'orderable' => false,
            'wrapper'   => [
                'element' => 'strong',
            ]
        ])->makeFirstColumn();

        CRUD::addColumn([
            'name'  => 'name',
            'label' => trans('backpack::crud.company.column.name'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'address',
            'label' => trans('backpack::crud.company.column.address'),
            'type'  => 'wrap_text',
        ]);

        CRUD::addColumn([
            'name'  => 'city',
            'label' => trans('backpack::crud.company.column.city'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'province',
            'label' => trans('backpack::crud.company.column.province'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'postal_code',
            'label' => trans('backpack::crud.company.column.postal_code'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'phone',
            'label' => trans('backpack::crud.company.column.phone'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'email',
            'label' => trans('backpack::crud.company.column.email'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'website',
            'label' => trans('backpack::crud.company.column.website'),
            'type'  => 'text',
        ]);
    }

    public function exportPdf()
    {
        $this->setupListExport();

        $columns = $this->crud->columns();
        $items = $this->crud->getEntries();

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
            'items'   => $all_items,
            'title'   => trans('backpack::crud.company.title_header')
        ])->setPaper('A4', 'landscape');

        $fileName = 'company_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportExcel()
    {
        $this->setupListExport();

        $columns = $this->crud->columns();
        $items = $this->crud->getEntries();

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

        $name = 'COMPANY_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($columns, $all_items) {
            echo Excel::raw(new ExportExcel($columns, $all_items), \Maatwebsite\Excel\Excel::XLSX);
        }, $name, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
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
        $this->data['title_modal_create'] = trans('backpack::crud.company.title_modal_create');
        $this->data['title_modal_edit'] = trans('backpack::crud.company.title_modal_edit');
        $this->data['title_modal_delete'] = trans('backpack::crud.company.title_modal_delete');

        $breadcrumbs = [
            trans('backpack::crud.menu.setting') => backpack_url('setting'),
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
            $data = CompanyData::fromRequest($request);
            $item = $this->companyService->createCompany($data);

            Alert::success(trans('backpack::crud.insert_success'))->flash();
            return $this->crud->performSaveAction($item->getKey());
        } catch (\Exception $e) {
            Log::error('Error creating company: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'success' => false,
                'error'   => $e->getMessage(),
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
            $data = CompanyData::fromRequest($request);
            $keyName = $this->crud->getModel()->getKeyName();
            $item = $this->companyService->updateCompany((int) $request->get($keyName), $data);

            Alert::success(trans('backpack::crud.update_success'))->flash();
            return $this->crud->performSaveAction($item->getKey());
        } catch (\Exception $e) {
            Log::error('Error updating company: ' . $e->getMessage());
            return response()->json([
                'status'  => false,
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');
        $id = $this->crud->getCurrentEntryId() ?? $id;

        try {
            $this->companyService->deleteCompany($id);
            return response()->json([
                'success' => true,
                'message' => trans('backpack::crud.delete_confirmation_message')
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting company: ' . $e->getMessage());
            return response()->json([
                'type'    => 'errors',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(CompanyRequest::class);

        CRUD::addField([
            'name'    => 'name',
            'label'   => trans('backpack::crud.company.column.name'),
            'type'    => 'text',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name'          => 'logo',
            'label'         => trans('backpack::crud.company.column.logo'),
            'type'          => 'upload',
            'disk'          => 'public',
            'wrapper'       => ['class' => 'form-group col-md-6'],
            'custom_upload' => true,
            'hint'          => trans('backpack::crud.company.field.logo.hint'),
        ]);

        CRUD::addField([
            'name'    => 'address',
            'label'   => trans('backpack::crud.company.column.address'),
            'type'    => 'textarea',
            'wrapper' => ['class' => 'form-group col-md-12'],
        ]);

        CRUD::addField([
            'name'    => 'city',
            'label'   => trans('backpack::crud.company.column.city'),
            'type'    => 'text',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name'    => 'province',
            'label'   => trans('backpack::crud.company.column.province'),
            'type'    => 'text',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name'    => 'postal_code',
            'label'   => trans('backpack::crud.company.column.postal_code'),
            'type'    => 'text',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name'    => 'phone',
            'label'   => trans('backpack::crud.company.column.phone'),
            'type'    => 'text',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name'    => 'email',
            'label'   => trans('backpack::crud.company.column.email'),
            'type'    => 'email',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name'    => 'website',
            'label'   => trans('backpack::crud.company.column.website'),
            'type'    => 'text',
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

    protected function setupShowOperation()
    {
        $this->setupCreateOperation();

        CRUD::addColumn([
            'name'  => 'name',
            'label' => trans('backpack::crud.company.column.name'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'   => 'logo',
            'label'  => trans('backpack::crud.company.column.logo'),
            'type'   => 'image',
            'disk'   => 'public',
            'height' => '50px',
            'width'  => 'auto',
        ]);

        CRUD::addColumn([
            'name'  => 'address',
            'label' => trans('backpack::crud.company.column.address'),
            'type'  => 'wrap_text',
        ]);

        CRUD::addColumn([
            'name'  => 'city',
            'label' => trans('backpack::crud.company.column.city'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'province',
            'label' => trans('backpack::crud.company.column.province'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'postal_code',
            'label' => trans('backpack::crud.company.column.postal_code'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'phone',
            'label' => trans('backpack::crud.company.column.phone'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'email',
            'label' => trans('backpack::crud.company.column.email'),
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'name'  => 'website',
            'label' => trans('backpack::crud.company.column.website'),
            'type'  => 'text',
        ]);
    }
}
