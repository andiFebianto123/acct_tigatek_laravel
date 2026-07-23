<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\DeviceStock\DeviceStockData;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\FormaterExport;
use App\Http\Controllers\Operation\PermissionAccess;
use App\Http\Exports\ExportExcel;
use App\Http\Helpers\CustomHelper;
use App\Http\Requests\DeviceStockRequest;
use App\Models\DeviceStock;
use App\Models\DeviceStockCategory;
use App\Repositories\DeviceStock\DeviceStockRepository;
use App\Services\DeviceStock\DeviceStockService;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Prologue\Alerts\Facades\Alert;

class DeviceStockCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;
    use FormaterExport;

    public function __construct(
        protected DeviceStockRepository $repository,
        protected DeviceStockService $service
    ) {
        parent::__construct();
    }

    public function setup()
    {
        CRUD::setModel(DeviceStock::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/inventory/device-stock');
        CRUD::setEntityNameStrings(trans('backpack::crud.device_stock.title_header'), trans('backpack::crud.device_stock.title_header'));

        $base = 'INDEX STOK BARANG';
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

    private function getComponent()
    {
        $this->crud->filter('category_id11crudTable-device-stock')
            ->label(trans('backpack::crud.device_stock.column.category'))
            ->type('select2')
            ->values(fn() => DeviceStockCategory::pluck('name', 'id')->toArray());

        $status_file = strpos(url()->current(), 'excel') ? 'excel' : 'pdf';

        $columns = [
            [
                'name'      => 'row_number',
                'type'      => 'row_number',
                'label'     => 'No',
                'orderable' => false,
            ],
            [
                'label'     => trans('backpack::crud.device_stock.column.name'),
                'name'      => 'name',
                'type'      => 'text',
                'orderable' => true,
            ],
            [
                'label'     => trans('backpack::crud.device_stock.column.code'),
                'name'      => 'code',
                'type'      => 'text',
                'orderable' => true,
            ],
            [
                'label'     => trans('backpack::crud.device_stock.column.category'),
                'name'      => 'category_name',
                'type'      => 'text',
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
                'label'     => trans('backpack::crud.device_stock.column.qty'),
                'name'      => 'qty',
                'type'      => 'text',
                'orderable' => true,
            ],
            [
                'label'     => trans('backpack::crud.device_stock.column.sell_price'),
                'name'      => 'sell_price',
                'type'      => 'closure',
                'function'  => function ($entry) use ($status_file) {
                    return CustomHelper::formatCurrency($entry->sell_price, $entry->currency_code ?? 'IDR', $status_file === 'excel');
                },
                'orderable' => true,
            ],
            [
                'label'     => trans('backpack::crud.device_stock.column.buy_price'),
                'name'      => 'buy_price',
                'type'      => 'closure',
                'function'  => function ($entry) use ($status_file) {
                    return CustomHelper::formatCurrency($entry->buy_price, $entry->currency_code ?? 'IDR', $status_file === 'excel');
                },
                'orderable' => true,
            ],
            [
                'name'      => 'action',
                'type'      => 'action',
                'label'     => trans('backpack::crud.actions'),
                'width_box' => '150px',
            ]
        ];

        $this->card->addCard([
            'name' => 'device-stock',
            'line' => 'top',
            'view' => 'crud::components.datatable-origin',
            'params' => [
                'filter' => true,
                'crud_custom' => $this->crud,
                'hide_title' => true,
                'columns' => $columns,
                'filter_table' => collect($this->crud->filters()),
                'route' => backpack_url('inventory/device-stock/search'),
            ]
        ]);
    }

    public function index()
    {
        $this->crud->hasAccessOrFail('list');
        $this->crud->param_uri_export = "?export=1";
        $this->getComponent();

        $this->data['crud'] = $this->crud;
        $this->data['title'] = trans('backpack::crud.device_stock.title_header');
        $this->data['title_modal_create'] = trans('backpack::crud.add') . ' ' . trans('backpack::crud.device_stock.title_header');
        $this->data['title_modal_edit'] = trans('backpack::crud.edit') . ' ' . trans('backpack::crud.device_stock.title_header');
        $this->data['title_modal_delete'] = trans('backpack::crud.device_stock.title_header');
        $this->data['cards'] = $this->card;

        $breadcrumbs = [
            trans('backpack::crud.menu.inventory') => backpack_url('inventory/device-stock'),
            trans('backpack::crud.device_stock.title_header') => backpack_url('inventory/device-stock'),
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;

        $list = "crud::list-blank" ?? $this->crud->getListView();
        return view($list, $this->data);
    }

    public function create()
    {
        $this->crud->hasAccessOrFail('create');

        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = trans('backpack::crud.add') . ' ' . trans('backpack::crud.device_stock.title_header');

        return response()->json([
            'html' => view('crud::create', $this->data)->render()
        ]);
    }

    public function store()
    {
        $this->crud->hasAccessOrFail('create');
        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {
            $data = DeviceStockData::fromRequest($request);
            $item = $this->service->createStock($data);
            DB::commit();

            $event = [
                'crudTable-device-stock_create_success' => true,
            ];

            Alert::success(trans('backpack::crud.insert_success'))->flash();

            $this->crud->setSaveAction();

            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
                    'events' => $event,
                ]);
            }
            return $this->crud->performSaveAction($item->getKey());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error store device stock: ' . $e->getMessage());
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
        $this->data['title'] = trans('backpack::crud.edit') . ' ' . trans('backpack::crud.device_stock.title_header');
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

        DB::beginTransaction();
        try {
            $data = DeviceStockData::fromRequest($request);
            $item = $this->service->updateStock($request->get($this->crud->getModel()->getKeyName()), $data);
            DB::commit();
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
                    'events' => [
                        'crudTable-device-stock_updated_success' => $item,
                    ]
                ]);
            }
            return $this->crud->performSaveAction($item->getKey());
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error update device stock: ' . $e->getMessage());
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
            $this->service->deleteStock($id);
            return response()->json([
                'success' => true,
                'message' => trans('backpack::crud.delete_confirmation_message')
            ]);
        } catch (\Exception $e) {
            Log::error('Error delete device stock: ' . $e->getMessage());
            return response()->json([
                'type' => 'errors',
                'message' => $e->getMessage()
            ], 500);
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

        // Custom repository filtering based on request parameters
        $query = $this->crud->query->with('category');
        if (request()->has('category_id') && request()->input('category_id') !== '') {
            $query = $this->repository->applyCategoryFilter($query, request()->input('category_id'));
        }

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

    protected function setupListOperation()
    {
        CRUD::disableResponsiveTable();
        CRUD::removeButtons(['delete', 'show', 'update'], 'line');

        $exportTitle = str_replace(' ', '_', strtolower(trans('backpack::crud.device_stock.export_title')));
        $this->crud->file_title_export_pdf = $exportTitle . '.pdf';
        $this->crud->file_title_export_excel = $exportTitle . '.xlsx';
        $this->crud->param_uri_export = "?export=1";

        CRUD::addButtonFromView('top', 'export-excel-table', 'export-excel-table', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-table', 'export-pdf-table', 'beginning');
        CRUD::addButtonFromView('line', 'show', 'show', 'end');
        CRUD::addButtonFromView('line', 'update', 'update', 'end');
        CRUD::addButtonFromView('line', 'delete', 'delete', 'end');

        // Apply filters in query if loaded for export
        $this->crud->query = $this->repository->getExportData(request());

        $status_file = strpos(url()->current(), 'excel') ? 'excel' : 'pdf';

        $this->crud->addColumn([
            'name'      => 'row_number',
            'type'      => 'row_number',
            'label'     => 'No',
            'orderable' => false,
            'wrapper' => [
                'element' => 'strong',
            ]
        ])->makeFirstColumn();

        CRUD::column([
            'label' => trans('backpack::crud.device_stock.column.name'),
            'name' => 'name',
            'type' => 'text',
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.device_stock.column.code'),
            'name' => 'code',
            'type' => 'text',
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.device_stock.column.category'),
            'type' => 'closure',
            'name' => 'category_name',
            'function' => function ($entry) {
                return $entry->category?->name;
            }
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
        ])->after('category_name');

        CRUD::column([
            'label' => trans('backpack::crud.device_stock.column.qty'),
            'name' => 'qty',
            'type' => 'number',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.device_stock.column.sell_price'),
            'name' => 'sell_price',
            'type'  => 'closure',
            'function' => function ($entry) use ($status_file) {
                return CustomHelper::formatCurrency($entry->sell_price, $entry->currency_code ?? 'IDR', $status_file === 'excel');
            },
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.device_stock.column.buy_price'),
            'name' => 'buy_price',
            'type'  => 'closure',
            'function' => function ($entry) use ($status_file) {
                return CustomHelper::formatCurrency($entry->buy_price, $entry->currency_code ?? 'IDR', $status_file === 'excel');
            },
        ]);
    }

    public function exportPdf()
    {
        $this->setupListOperation();
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
            'items' => $all_items,
            'title' => strtoupper(trans('backpack::crud.device_stock.export_title'))
        ])->setPaper('A4', 'landscape');

        $fileName = str_replace(' ', '_', strtolower(trans('backpack::crud.device_stock.export_title'))) . '_' . now()->format('Ymd_His') . '.pdf';

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

        $name = str_replace(' ', '_', strtolower(trans('backpack::crud.device_stock.export_title'))) . '_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($columns, $all_items) {
            echo Excel::raw(new ExportExcel($columns, $all_items), \Maatwebsite\Excel\Excel::XLSX);
        }, $name, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }

    public function saveCategoryAjax()
    {
        $name = request()->input('name');
        if (empty($name)) {
            return response()->json(['success' => false, 'message' => 'Nama kategori tidak boleh kosong']);
        }

        try {
            $category = DeviceStockCategory::firstOrCreate(['name' => $name]);
            return response()->json([
                'success' => true,
                'data' => $category,
                'message' => 'Kategori berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(DeviceStockRequest::class);

        CRUD::addField([
            'name' => 'name',
            'label' => trans('backpack::crud.device_stock.column.name'),
            'type' => 'text',
        ]);

        CRUD::addField([
            'name' => 'code',
            'label' => trans('backpack::crud.device_stock.column.code'),
            'type' => 'text',
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'label'     => trans('backpack::crud.device_stock.column.category'),
            'type'      => 'select2_category_tags',
            'name'      => 'category_id',
            'options'   => ['' => trans('backpack::crud.device_stock.placeholder_category')] + DeviceStockCategory::orderBy('name', 'ASC')->pluck('name', 'id')->toArray(),
            'wrapper'   => ['class' => 'form-group col-md-6'],
            'placeholder' => trans('backpack::crud.device_stock.column.category'),
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
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name' => 'qty',
            'label' => trans('backpack::crud.device_stock.column.qty'),
            'type' => 'number',
            'default' => 0,
            'wrapper' => ['class' => 'form-group col-md-6'],
        ]);

        CRUD::addField([
            'name' => 'sell_price',
            'label' => trans('backpack::crud.device_stock.column.sell_price'),
            'type' => 'mask_currency',
            'currency_name' => 'currency_code',
            'currency_options' => [
                'IDR' => 'IDR (Rp)',
                'USD' => 'USD ($)',
            ],
            'default_currency' => 'IDR',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'buy_price',
            'label' => trans('backpack::crud.device_stock.column.buy_price'),
            'type' => 'mask_currency',
            'currency_name' => 'currency_code',
            'currency_options' => [
                'IDR' => 'IDR (Rp)',
                'USD' => 'USD ($)',
            ],
            'default_currency' => 'IDR',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'logic_device',
            'type' => 'logic_device',
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
        CRUD::field('logic_device')->remove();

        $this->crud->removeColumn('row_number');
        $this->crud->removeColumn('action');

        CRUD::column([
            'label' => trans('backpack::crud.device_stock.column.name'),
            'name' => 'name',
            'type' => 'text',
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.device_stock.column.code'),
            'name' => 'code',
            'type' => 'text',
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.device_stock.column.category'),
            'type' => 'closure',
            'name' => 'category_id',
            'function' => function ($entry) {
                return $entry->category?->name;
            }
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
            'label' => trans('backpack::crud.device_stock.column.qty'),
            'name' => 'qty',
            'type' => 'number',
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.device_stock.column.sell_price'),
            'name' => 'sell_price',
            'type'  => 'closure',
            'function' => function ($entry) {
                return CustomHelper::formatCurrency($entry->sell_price, $entry->currency_code ?? 'IDR');
            },
        ]);

        // CRUD::column([
        //     'label'  => trans('backpack::crud.device_stock.column.sell_price') . ' (Base IDR)',
        //     'name'   => 'sell_price_base',
        //     'type'   => 'closure',
        //     'function' => function ($entry) {
        //         return CustomHelper::formatCurrency($entry->sell_price_base ?? $entry->sell_price, 'IDR');
        //     },
        // ]);

        CRUD::column([
            'label'  => trans('backpack::crud.device_stock.column.buy_price'),
            'name' => 'buy_price',
            'type'  => 'closure',
            'function' => function ($entry) {
                return CustomHelper::formatCurrency($entry->buy_price, $entry->currency_code ?? 'IDR');
            },
        ]);

        // CRUD::column([
        //     'label'  => trans('backpack::crud.device_stock.column.buy_price') . ' (Base IDR)',
        //     'name'   => 'buy_price_base',
        //     'type'   => 'closure',
        //     'function' => function ($entry) {
        //         return CustomHelper::formatCurrency($entry->buy_price_base ?? $entry->buy_price, 'IDR');
        //     },
        // ]);
    }
}
