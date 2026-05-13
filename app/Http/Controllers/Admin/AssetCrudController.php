<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Asset;
use App\Models\Account;
use App\Models\Company;
use App\Models\Setting;
use App\Models\JournalEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rule;
use App\Http\Exports\ExportExcel;
use App\Models\ProjectProfitLost;
use App\Http\Helpers\CustomHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Illuminate\Notifications\Action;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\PermissionAccess;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

use App\Services\Asset\AssetService;
use App\Repositories\Asset\AssetRepository;
use App\DTOs\Asset\AssetSaveData;
use App\Http\Requests\Asset\AssetRequest;

class AssetCrudController extends CrudController
{
    public function __construct(
        protected AssetService $service,
        protected AssetRepository $repository
    ) {
        parent::__construct();
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;

    public function setup()
    {
        $this->crud->denyAllAccess(['create', 'update', 'delete', 'list', 'show']);
        $this->crud->setModel(Asset::class);
        $this->crud->setRoute(config('backpack.base.route_prefix') . '/finance-report/list-asset');
        $this->crud->setEntityNameStrings(trans('backpack::crud.asset.title_header'), trans('backpack::crud.asset.title_header'));

        $base = 'INDEX LAPORAN KEUANGAN DAFTAR ASET';
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

    public function index()
    {
        $this->crud->hasAccessOrFail('list');

        // $this->listCardComponents();

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.asset.title_modal_create');
        $this->data['title_modal_edit'] = trans('backpack::crud.asset.title_modal_edit');
        $this->data['title_modal_delete'] = trans('backpack::crud.asset.title_modal_delete');

        $breadcrumbs = [
            trans('backpack::crud.menu.finance_report') => backpack_url('finance-report'),
            trans('backpack::crud.menu.asset') => backpack_url($this->crud->route)
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;

        $this->data['cards'] = $this->card;
        $this->data['modals'] = $this->modal;
        $this->data['scripts'] = $this->script;
        $list = "crud::list-custom" ?? $this->crud->getListView();
        return view($list, $this->data);
    }

    protected function setupListOperation()
    {
        CRUD::disableResponsiveTable();
        $new_format_date = 'DD/MM/YYYY';

        $this->crud->file_title_export_pdf = "Laporan_daftar_asset.pdf";
        $this->crud->file_title_export_excel = "Laporan_daftar_asset.xlsx";
        $this->crud->param_uri_export = "?export=1";
        CRUD::addButtonFromView('top', 'export-excel-table', 'export-excel-table', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-table', 'export-pdf-table', 'beginning');
        CRUD::addButtonFromView('top', 'filter-year-asset', 'filter-year-asset', 'beginning');

        $settings = Setting::first();
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
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'select',
                'name'      => 'company_id',
                'entity'    => 'company',
                'attribute' => 'name',
                'model'     => "App\Models\Company",
            ]);
        }

        CRUD::column([
            // 1-n relationship
            'label' => trans('backpack::crud.asset.column.account_id'),
            'type'      => 'select',
            'name'      => 'account_id', // the column that contains the ID of that connected entity;
            'entity'    => 'account', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\Account", // foreign key model
            // OPTIONAL
            // 'limit' => 32, // Limit the number of characters shown
        ]);

        CRUD::column([
            // 1-n relationship
            'label' => trans('backpack::crud.asset.column.depreciation_account_id'),
            'type'      => 'select',
            'name'      => 'depreciation_account_id', // the column that contains the ID of that connected entity;
            'entity'    => 'account_depreciation', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\Account", // foreign key model
            // OPTIONAL
            // 'limit' => 32, // Limit the number of characters shown
        ]);

        CRUD::column([
            // 1-n relationship
            'label' => trans('backpack::crud.asset.column.expense_account_id'),
            'type'      => 'select',
            'name'      => 'expense_account_id', // the column that contains the ID of that connected entity;
            'entity'    => 'account_expense', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\Account", // foreign key model
            // OPTIONAL
            // 'limit' => 32, // Limit the number of characters shown
        ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.description'),
                'name' => 'description',
                'type'  => 'text'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.year_acquisition'),
                'name' => 'year_acquisition',
                'type'  => 'date',
                'format' => 'MMM Y'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.price_acquisition'),
                'name' => 'price_acquisition',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.economic_age'),
                'name' => 'economic_age',
                'type'  => 'text'
            ],
        );

        CRUD::addColumn([
            'label'  => trans('backpack::crud.asset.column.tarif'),
            'name' => 'tarif',
            'type'  => 'text',
            'suffix' => '%',
        ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.price_rate_per_year'),
                'name' => 'price_rate_per_year',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.price_rate_year_ago'),
                'name' => 'price_rate_year_ago',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.accumulated_until_december_last_year'),
                'name' => 'accumulated_until_december_last_year',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.book_value_last_december'),
                'name' => 'book_value_last_december',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.this_year_depreciation_rate'),
                'name' => 'this_year_depreciation_rate',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.accumulated_until_december_this_year'),
                'name' => 'accumulated_until_december_this_year',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.book_value_this_december'),
                'name' => 'book_value_this_december',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        if (request()->has('filter_year')) {
            $filter_year = request()->get('filter_year');
            if ($filter_year != 'all') {
                $this->crud->query = $this->crud->query
                    ->where(DB::raw("YEAR(year_acquisition)"), $filter_year);
            }
        }
    }

    private function setupListExport()
    {
        CRUD::disableResponsiveTable();

        CRUD::addButtonFromView('top', 'filter-year-asset', 'filter-year-asset', 'beginning');

        $settings = Setting::first();
        $this->crud->addColumn([
            'name'      => 'row_number',
            'type'      => 'export',
            'label'     => 'No',
            'orderable' => false,
            'wrapper' => [
                'element' => 'strong',
            ]
        ])->makeFirstColumn();

        CRUD::column([
            // 1-n relationship
            'label' => trans('backpack::crud.asset.column.account_id'),
            'type'      => 'closure',
            'name'      => 'account_id', // the column that contains the ID of that connected entity;
            'entity'    => 'account', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\Account", // foreign key model
            'function' => function ($entry) {
                return $entry->account->name;
            }
            // OPTIONAL
            // 'limit' => 32, // Limit the number of characters shown
        ]);

        CRUD::column([
            // 1-n relationship
            'label' => trans('backpack::crud.asset.column.depreciation_account_id'),
            'type'      => 'closure',
            'name'      => 'depreciation_account_id', // the column that contains the ID of that connected entity;
            'entity'    => 'account_depreciation', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\Account", // foreign key model
            'function' => function ($entry) {
                return $entry->account_depreciation->name;
            }
            // OPTIONAL
            // 'limit' => 32, // Limit the number of characters shown
        ]);

        CRUD::column([
            // 1-n relationship
            'label' => trans('backpack::crud.asset.column.expense_account_id'),
            'type'      => 'closure',
            'name'      => 'expense_account_id', // the column that contains the ID of that connected entity;
            'entity'    => 'account_expense', // the method that defines the relationship in your Model
            'attribute' => 'name', // foreign key attribute that is shown to user
            'model'     => "App\Models\Account", // foreign key model
            'function' => function ($entry) {
                return $entry->account_expense->name;
            }
            // OPTIONAL
            // 'limit' => 32, // Limit the number of characters shown
        ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.description'),
                'name' => 'description',
                'type'  => 'export'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.year_acquisition'),
                'name' => 'year_acquisition',
                'type'  => 'export',
                'format' => 'MMM Y'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.price_acquisition'),
                'name' => 'price_acquisition',
                'type'  => 'export',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.economic_age'),
                'name' => 'economic_age',
                'type'  => 'export'
            ],
        );

        CRUD::addColumn([
            'label'  => trans('backpack::crud.asset.column.tarif'),
            'name' => 'tarif',
            'type'  => 'export',
            'suffix' => '%',
        ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.price_rate_per_year'),
                'name' => 'price_rate_per_year',
                'type'  => 'export',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.price_rate_year_ago'),
                'name' => 'price_rate_year_ago',
                'type'  => 'export',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.accumulated_until_december_last_year'),
                'name' => 'accumulated_until_december_last_year',
                'type'  => 'export',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.book_value_last_december'),
                'name' => 'book_value_last_december',
                'type'  => 'export',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.this_year_depreciation_rate'),
                'name' => 'this_year_depreciation_rate',
                'type'  => 'export',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.accumulated_until_december_this_year'),
                'name' => 'accumulated_until_december_this_year',
                'type'  => 'export',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.asset.column.book_value_this_december'),
                'name' => 'book_value_this_december',
                'type'  => 'export',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ],
        );

        if (request()->has('filter_year')) {
            $filter_year = request()->get('filter_year');
            if ($filter_year != 'all') {
                $this->crud->query = $this->crud->query
                    ->where(DB::raw("YEAR(year_acquisition)"), $filter_year);
            }
        }
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

        $title = "DAFTAR ASSET";

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

        $name = 'DAFTAR ASSET';

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


    public function select2account()
    {
        $this->crud->hasAccessOrFail('create');

        $search = request()->input('q');
        $dataset = \App\Models\Account::select(['id', 'name']);

        if (request()->has('type')) {
            if (request()->get('type') == 'beban') {
                $dataset = $dataset->where('type', 'Expense');
            }
        } else {
            $dataset = $dataset->where('type', 'Assets')
                ->where('code', 'LIKE', "105%");
        }
        $dataset = $dataset
            ->where('name', 'LIKE', "%$search%")
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

    protected function setupCreateOperation()
    {
        CRUD::setValidation(AssetRequest::class);

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

        $settings = Setting::first();
        CRUD::field([   // 1-n relationship
            'label'       => trans('backpack::crud.asset.field.account_id.label'), // Table column heading
            'type'        => "select2_ajax_custom",
            'name'        => 'account_id', // the column that contains the ID of that connected entity
            'entity'      => 'account', // the method that defines the relationship in your Model
            'attribute'   => "name", // foreign key attribute that is shown to user
            'data_source' => backpack_url('finance-report/select2-account-id'), // url to controller search function (with /{id} should return a single entry)
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::field([   // 1-n relationship
            'label'       => trans('backpack::crud.asset.field.account_depreciation.label'), // Table column heading
            'type'        => "select2_ajax_custom",
            'name'        => 'depreciation_account_id', // the column that contains the ID of that connected entity
            'entity'      => 'account_depreciation', // the method that defines the relationship in your Model
            'attribute'   => "name", // foreign key attribute that is shown to user
            'data_source' => backpack_url('finance-report/select2-account-id'), // url to controller search function (with /{id} should return a single entry)
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::field([   // 1-n relationship
            'label'       => trans('backpack::crud.asset.field.expense_account_id.label'), // Table column heading
            'type'        => "select2_ajax_custom",
            'name'        => 'expense_account_id', // the column that contains the ID of that connected entity
            'entity'      => 'account_expense', // the method that defines the relationship in your Model
            'attribute'   => "name", // foreign key attribute that is shown to user
            'data_source' => backpack_url('finance-report/select2-account-id?type=beban'), // url to controller search function (with /{id} should return a single entry)
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => trans('backpack::crud.asset.column.description'),
            'type' => 'text',
            'attributes' => [
                'placeholder' => trans('backpack::crud.asset.field.description.placeholder'),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::field([   // date_picker
            'name'  => 'year_acquisition',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.asset.field.year_acquisition.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
                'format'   => 'mm-yyyy',
                'startView' => "months",
                'minViewMode' => "months",
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'price_acquisition',
            'label' => trans('backpack::crud.asset.field.price_acquisition.label'),
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
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'economic_age',
            'label' => trans('backpack::crud.asset.field.economic_age.label'),
            'type' => 'number',
            // optionals
            'attributes' => ["step" => "any"], // allow decimals
            // 'suffix'     => ".00",
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.asset.field.economic_age.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'tarif',
            'label' => trans('backpack::crud.asset.field.tarif.label'),
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
                'disabled' => true,
            ]
        ]);

        CRUD::addField([
            'name' => 'price_rate_per_year',
            'label' => trans('backpack::crud.asset.field.price_rate_per_year.label'),
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
                'placeholder' => '000.000',
                'disabled' => true
            ]
        ]);

        CRUD::addField([
            'name' => 'price_rate_year_ago',
            'label' => trans('backpack::crud.asset.field.price_rate_year_ago.label'),
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
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'accumulated_until_december_last_year',
            'label' => trans('backpack::crud.asset.field.accumulated_until_december_last_year.label'),
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
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'book_value_last_december',
            'label' => trans('backpack::crud.asset.field.book_value_last_december.label'),
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
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'this_year_depreciation_rate',
            'label' => trans('backpack::crud.asset.field.this_year_depreciation_rate.label'),
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
                'placeholder' => '000.000',
                'disabled' => true
            ]
        ]);


        CRUD::addField([
            'name' => 'accumulated_until_december_this_year',
            'label' => trans('backpack::crud.asset.field.accumulated_until_december_this_year.label'),
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
                'placeholder' => '000.000',
                'disabled' => true
            ]
        ]);

        CRUD::addField([
            'name' => 'book_value_this_december',
            'label' => trans('backpack::crud.asset.field.book_value_this_december.label'),
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
                'placeholder' => '000.000',
                'disabled' => true
            ]
        ]);

        CRUD::addField([
            'name' => 'logic_asset',
            'type' => 'logic_asset',
        ]);
    }


    public function store()
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {
            $data = AssetSaveData::fromRequest($request);
            $item = $this->service->storeAsset($data);

            $this->data['entry'] = $this->crud->entry = $item;
            \Alert::success(trans('backpack::crud.insert_success'))->flash();
            $this->crud->setSaveAction();

            DB::commit();
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
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

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    public function update()
    {
        $this->crud->hasAccessOrFail('update');
        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {
            $data = AssetSaveData::fromRequest($request);
            $item = $this->service->storeAsset($data);

            $this->data['entry'] = $this->crud->entry = $item;
            \Alert::success(trans('backpack::crud.update_success'))->flash();
            $this->crud->setSaveAction();

            DB::commit();
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
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
        $this->setupCreateOperation();
        CRUD::field('logic_asset')->remove();

        $this->setupListOperation();
        CRUD::column('row_number')->remove();
    }

    // public function destroy($id)
    // {
    //     $this->crud->hasAccessOrFail('delete');

    //     // get entry ID from Request (makes sure its the last ID for nested resources)
    //     $id = $this->crud->getCurrentEntryId() ?? $id;

    //     return $this->crud->delete($id);
    // }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $this->crud->hasAccessOrFail('delete');
            $this->service->deleteAsset((int) $id);
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'type' => 'errors',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
