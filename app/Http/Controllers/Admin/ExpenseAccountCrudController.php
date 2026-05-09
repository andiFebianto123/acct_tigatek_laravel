<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Account;
use App\Models\Setting;
use App\Models\JournalEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rule;
use App\Http\Exports\ExportExcel;
use App\Http\Helpers\CustomHelper;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\PermissionAccess;

use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\DTOs\Coa\CoaSaveData;
use App\DTOs\Coa\CoaFilterData;
use App\Repositories\Coa\CoaRepository;
use App\Services\Coa\CoaService;
use App\Http\Requests\Coa\CoaRequest;

class ExpenseAccountCrudController extends CrudController
{
    protected CoaRepository $repository;
    protected CoaService $service;

    public function __construct(CoaRepository $repository, CoaService $service)
    {
        parent::__construct();
        $this->repository = $repository;
        $this->service = $service;
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use PermissionAccess;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(Account::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/finance-report/expense-account');
        CRUD::setEntityNameStrings(trans('backpack::crud.expense_account.title_header'), trans('backpack::crud.expense_account.title_header'));

        $allAccess = [
            'AKSES SEMUA MENU ACCOUNTING',
        ];

        $viewMenu = [
            'MENU INDEX LAPORAN KEUANGAN COA',
        ];

        $this->settingPermission([
            'create' => [
                'CREATE INDEX LAPORAN KEUANGAN COA',
                ...$allAccess
            ],
            'update' => [
                'UPDATE INDEX LAPORAN KEUANGAN COA',
                ...$allAccess
            ],
            'delete' => [
                'DELETE INDEX LAPORAN KEUANGAN COA',
                ...$allAccess
            ],
            'list' => $viewMenu,
            'show' => $viewMenu,
            'print' => true,
        ]);
    }

    public function listCardComponents($type)
    {
        $dataset = Account::whereIn('level', [2])
            ->where('is_active', 1)->orderBy('code', 'asc')->get();

        $this->card->addCard([
            'name' => 'filter',
            'line' => 'top',
            'view' => 'crud::components.filter-account-balance',
            'parent_view' => 'crud::components.filter-parent',
            'params' => []
        ]);

        $yearParam = request('filter_year') ?? request('amp;filter_year');
        $quarterParam = request('filter_quarter') ?? request('amp;filter_quarter');
        $queryParams = array_filter(['filter_year' => $yearParam, 'filter_quarter' => $quarterParam]);
        $queryStr = http_build_query($queryParams);
        $routeSuffix = $queryStr ? '&' . $queryStr : '';

        foreach ($dataset as $account) {
            $this->card->addCard([
                'name' => 'account_' . $account->id,
                'line' => 'top',
                'view' => 'crud::components.card-account',
                'params' => [
                    'crud' => $this->crud,
                    'account' => $account,
                    'route' => url($this->crud->route . '/search?_id=' . $account->id . $routeSuffix),
                ]
            ]);
        }
    }

    public function index()
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['is_disabled_list'] = true;

        $this->listCardComponents(Account::EXPENSE);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.expense_account.title_modal_create');
        $this->data['title_modal_edit'] = trans('backpack::crud.expense_account.title_modal_edit');
        $this->data['title_modal_delete'] = trans('backpack::crud.expense_account.title_modal_delete');

        $breadcrumbs = [
            trans('backpack::crud.menu.finance_report') => backpack_url('cash-flow'),
            trans('backpack::crud.menu.expense_account') => backpack_url($this->crud->route)
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;

        $this->data['journal_years'] = JournalEntry::selectRaw('YEAR(date) as year')
            ->groupBy('year')
            ->orderBy('year', 'desc')
            ->pluck('year')
            ->toArray();

        $this->modal->addModal([
            'name' => 'modal_ledger',
            'title' => 'Buku Besar',
            'size' => 'modal-xl',
            'title_alignment' => 'center',
            'view' => 'crud::components.modal-ledger',
        ]);

        $this->data['cards'] = $this->card;
        $this->data['modals'] = $this->modal;
        $this->data['scripts'] = $this->script;

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

        $this->data['entry'] = $this->repository->getEntryForEdit($id);

        $this->crud->entry = $this->data['entry'];

        $this->crud->setOperationSetting('fields', $this->crud->getUpdateFields());

        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit') . ' ' . $this->crud->entity_name;
        $this->data['id'] = $id;

        return response()->json([
            'html' => view($this->crud->getEditView(), $this->data)->render()
        ]);
    }


    protected function setupCreateOperation()
    {
        CRUD::setValidation(CoaRequest::class);
        $disabled_attr = [];
        if ($this->crud->getCurrentEntryId()) {
            $disabled_attr = [
                'disabled' => true,
            ];
        }

        $settings = Setting::first();

        CRUD::addField([
            'name' => 'code',
            'label' => trans('backpack::crud.expense_account.column.code'),
            'type' => 'text',
            'attributes' => [
                'placeholder' => trans('backpack::crud.expense_account.field.code.placeholder')
            ]
        ]);

        CRUD::addField([
            'name' => 'name',
            'label' => trans('backpack::crud.expense_account.column.name'),
            'type' => 'text',
            'attributes' => [
                'placeholder' => trans('backpack::crud.expense_account.field.name.placeholder')
            ]
        ]);

        CRUD::addField([
            'name' => 'balance',
            'label' =>  trans('backpack::crud.expense_account.column.balance'),
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
                ...$disabled_attr,
            ]
        ]);
    }

    protected function setupUpdateOperation()
    {
        if (request('type') == 'add_balance') {
            CRUD::addField([
                'name' => 'id',
                'type' => 'hidden',
            ]);
            CRUD::addField([
                'name' => 'code',
                'label' => trans('backpack::crud.expense_account.column.code'),
                'type' => 'text',
                'attributes' => [
                    'readonly' => 'readonly',
                ]
            ]);
            CRUD::addField([
                'name' => 'name',
                'label' => trans('backpack::crud.expense_account.column.name'),
                'type' => 'text',
                'attributes' => [
                    'readonly' => 'readonly',
                ]
            ]);

            $settings = Setting::first();
            CRUD::addField([
                'name' => 'balance',
                'label' => trans('backpack::crud.expense_account.column.balance_initial'),
                'type' => 'mask',
                'mask' => '000.000.000.000.000.000',
                'mask_options' => [
                    'reverse' => true
                ],
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                'wrapper'   => [
                    'class' => 'form-group col-md-12',
                ],
                'attributes' => [
                    'placeholder' => trans('backpack::crud.expense_account.field.balance.placeholder'),
                ]
            ]);
        } else {
            $this->setupCreateOperation();
        }
    }

    public function update()
    {
        $this->crud->hasAccessOrFail('update');

        $this->crud->validateRequest();

        $this->crud->registerFieldEvents();

        try {
            $dto = CoaSaveData::fromRequest(request());

            if (request('type') == 'add_balance') {
                $result = $this->service->addBalance($dto);

                \Alert::success(trans('backpack::crud.update_success'))->flash();

                return response()->json([
                    'success' => true,
                    'data' => $result['item'],
                    'events' => $result['events']
                ]);
            }

            $result = $this->service->update($dto);

            $this->data['entry'] = $this->crud->entry = $result['item'];

            \Alert::success(trans('backpack::crud.update_success'))->flash();

            $this->crud->setSaveAction();

            return response()->json([
                'success' => true,
                'data' => $result['item'],
                'events' => $result['events']
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }


    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        $this->crud->validateRequest();

        $this->crud->registerFieldEvents();

        try {
            $dto = CoaSaveData::fromRequest(request());
            $item = $this->service->store($dto);

            $this->data['entry'] = $this->crud->entry = $item;

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            $this->crud->setSaveAction();

            return response()->json([
                'success' => true,
                'data' => $item,
                'events' => [
                    'account_create_success' => $item,
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

    public function destroy($id)
    {
        try {
            $this->crud->hasAccessOrFail('delete');

            $events = $this->service->destroy((int) $id);

            return response()->json([
                'success' => [
                    '<strong>' . trans('backpack::crud.delete_confirmation_title') . '</strong><br>' . trans('backpack::crud.delete_confirmation_message'),
                ],
                'events' => $events,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'type' => 'errors',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    protected function setupListOperation()
    {
        $settings = Setting::first();

        CRUD::disableResponsiveTable();
        CRUD::removeButton('update');
        CRUD::removeButton('delete');

        $this->crud->file_title_export_pdf = "Laporan_akun.pdf";
        $this->crud->file_title_export_excel = "Laporan_akun.xlsx";
        $this->crud->param_uri_export = "?export=1";

        CRUD::addButtonFromView('top', 'balance_sheet_excel', 'balance_sheet_excel', 'beginning');
        CRUD::addButtonFromView('top', 'balance_sheet_pdf', 'balance_sheet_pdf', 'beginning');

        CRUD::addButtonFromView('line', 'delete', "delete-account", 'beginning');
        CRUD::addButtonFromView('line', 'update', "update-account", 'beginning');
        CRUD::addButtonFromView('line', 'add_balance', "add-balance-account", 'beginning');
        CRUD::addButtonFromView('line', 'view', "view-ledger", 'beginning');

        CRUD::column([
            'name' => 'code_',
            'label' => trans('backpack::crud.expense_account.column.code'),
            'type' => 'text',
        ]);

        CRUD::column([
            'name' => 'name_',
            'label' => trans('backpack::crud.expense_account.column.name'),
            'type' => 'custom_html',
            'value' => function ($entry) {
                if ($entry->level_ > 2) {
                    $space = str_repeat('&nbsp;', $entry->level_);
                    return $space . '&bull; ' . $entry->name_;
                }
                return $entry->name_;
            }
        ]);

        CRUD::column([
            'name' => 'balance',
            'label' => trans('backpack::crud.expense_account.column.balance'),
            'type' => 'balance',
        ]);

        $dto = CoaFilterData::fromRequest(request());
        $this->repository->applyListQuery($this->crud->query, $dto);
    }

    private function setupListExport()
    {
        $settings = Setting::first();

        $dto = CoaFilterData::fromRequest(request());

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
            'name' => 'code_',
            'label' => trans('backpack::crud.expense_account.column.code'),
            'type' => 'export',
        ]);

        CRUD::column([
            'name' => 'name_',
            'label' => trans('backpack::crud.expense_account.column.name'),
            'type' => 'export',
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.expense_account.column.balance'),
            'name' => 'balance',
            'type'  => 'number',
            'value' => function ($entry) {
                if ($entry->balance < 0 && $entry->balance > -1) {
                    return 0;
                }
                return $entry->balance;
            },
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
        ]);

        $this->repository->applyListQuery($this->crud->query, $dto);
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
                $item_value = str_replace('<span>', '', $item_value);
                $item_value = str_replace('</span>', '', $item_value);
                $item_value = str_replace("\n", '', $item_value);
                $item_value = CustomHelper::clean_html($item_value);
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $title = "DAFTAR AKUN";

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
                if ($column['name'] == 'balance') {
                    $item_value = str_replace('Rp', '', $item_value);
                    $item_value = str_replace('.', '', $item_value);
                    $item_value = str_replace(',', '', $item_value);
                }
                $item_value = str_replace('<span>', '', $item_value);
                $item_value = str_replace('</span>', '', $item_value);
                $item_value = str_replace("\n", '', $item_value);
                $item_value = CustomHelper::clean_html($item_value);
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $name = 'DAFTAR AKUN';

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

    public function getLedgerDataTable()
    {
        $id = request()->_id;
        $account = Account::findOrFail($id);

        $dto = CoaFilterData::fromRequest(request());

        $query = $this->repository->getLedgerQuery($account, $dto->startDate, $dto->endDate);

        $total_data = $query->count();

        // Search
        if ($search = request()->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'LIKE', "%$search%")
                    ->orWhere('debit', 'LIKE', "%$search%")
                    ->orWhere('credit', 'LIKE', "%$search%");
            });
        }

        $total_filtered = $query->count();

        // Order
        $columns = ['date', 'description', 'debit', 'credit', 'id']; // for sequence
        if ($order = request()->input('order.0')) {
            $query->orderBy($columns[$order['column']], $order['dir']);
        } else {
            $query->orderBy('date', 'asc')->orderBy('id', 'asc');
        }

        // Pagination
        $start = request()->input('start', 0);
        $length = request()->input('length', 10);
        $entries = $query->offset($start)->limit($length)->get();

        $cumulative_balance = 0;
        if ($entries->count() > 0) {
            $cumulative_balance = $this->repository->getCumulativeBalanceBeforeEntry($account, $entries->first());
        }

        $data = [];
        foreach ($entries as $entry) {
            $cumulative_balance += ($entry->debit - $entry->credit);
            $data[] = [
                'date' => Carbon::parse($entry->date)->translatedFormat('d/m/Y'),
                'description' => $entry->description,
                'debit' => CustomHelper::formatRupiahWithCurrency($entry->debit),
                'credit' => CustomHelper::formatRupiahWithCurrency($entry->credit),
                'balance' => CustomHelper::formatRupiahWithCurrency($cumulative_balance),
            ];
        }

        return response()->json([
            'draw' => request()->input('draw'),
            'recordsTotal' => $total_data,
            'recordsFiltered' => $total_filtered,
            'data' => $data,
        ]);
    }
    public function exportLedgerPdf()
    {
        $id = request()->id;
        $account = Account::findOrFail($id);

        $dto = CoaFilterData::fromRequest(request());

        $query = $this->repository->getLedgerQuery($account, $dto->startDate, $dto->endDate);
        $query->orderBy('date', 'asc')->orderBy('id', 'asc');

        $entries = $query->get();
        $cumulative_balance = $this->repository->getInitialBalance($account, $dto->startDate);

        $data = [];
        if ($dto->startDate) {
            $data[] = [
                '-',
                'SALDO AWAL',
                '-',
                '-',
                CustomHelper::formatRupiahWithCurrency($cumulative_balance),
            ];
        }

        foreach ($entries as $entry) {
            $cumulative_balance += ($entry->debit - $entry->credit);
            $data[] = [
                Carbon::parse($entry->date)->translatedFormat('d/m/Y'),
                $entry->description,
                CustomHelper::formatRupiahWithCurrency($entry->debit),
                CustomHelper::formatRupiahWithCurrency($entry->credit),
                CustomHelper::formatRupiahWithCurrency($cumulative_balance),
            ];
        }

        $columns = [
            ['label' => 'Tanggal', 'name' => 'date'],
            ['label' => 'Keterangan', 'name' => 'description'],
            ['label' => trans('backpack::crud.cash_account.field_transaction.status.enter'), 'name' => 'debit'],
            ['label' => trans('backpack::crud.cash_account.field_transaction.status.out'), 'name' => 'credit'],
            ['label' => 'Saldo Komulatif', 'name' => 'balance'],
        ];

        $title = "LAPORAN BUKU BESAR: " . $account->code . " - " . $account->name;

        $pdf = Pdf::loadView('exports.table-pdf', [
            'columns' => $columns,
            'items' => $data,
            'title' => $title
        ])->setPaper('A4', 'landscape');

        $fileNameFilter = '';
        if ($dto->year && $dto->year != "" && $dto->year != "all") {
            $fileNameFilter .= '_' . $dto->year;
            if ($dto->quarter) {
                $fileNameFilter .= '_Q' . $dto->quarter;
            }
        }

        $fileName = 'Buku_Besar_' . str_replace(' ', '_', $account->name) . $fileNameFilter . '_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportLedgerExcel()
    {
        $id = request()->id;
        $account = Account::findOrFail($id);

        $dto = CoaFilterData::fromRequest(request());

        $query = $this->repository->getLedgerQuery($account, $dto->startDate, $dto->endDate);
        $query->orderBy('date', 'asc')->orderBy('id', 'asc');

        $entries = $query->get();
        $cumulative_balance = $this->repository->getInitialBalance($account, $dto->startDate);

        $data = [];
        if ($dto->startDate) {
            $data[] = [
                '-',
                'SALDO AWAL',
                "0",
                "0",
                (string) $cumulative_balance,
            ];
        }


        foreach ($entries as $entry) {
            $cumulative_balance += ($entry->debit - $entry->credit);
            $data[] = [
                Carbon::parse($entry->date)->format('d/m/Y'),
                $entry->description,
                $entry->debit,
                $entry->credit,
                $cumulative_balance,
            ];
        }

        $columns = [
            ['label' => 'Tanggal', 'name' => 'date'],
            ['label' => 'Keterangan', 'name' => 'description'],
            ['label' => trans('backpack::crud.cash_account.field_transaction.status.enter'), 'name' => 'debit'],
            ['label' => trans('backpack::crud.cash_account.field_transaction.status.out'), 'name' => 'credit'],
            ['label' => 'Saldo Komulatif', 'name' => 'balance'],
        ];

        $fileNameFilter = '';
        if ($dto->year && $dto->year != "" && $dto->year != "all") {
            $fileNameFilter .= '_' . $dto->year;
            if ($dto->quarter) {
                $fileNameFilter .= '_Q' . $dto->quarter;
            }
        }

        $fileName = 'Buku_Besar_' . str_replace(' ', '_', $account->name) . $fileNameFilter . '_' . now()->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($columns, $data) {
            echo Excel::raw(new ExportExcel($columns, $data), \Maatwebsite\Excel\Excel::XLSX);
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }
}
