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
use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\PermissionAccess;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\Http\Requests\Coa\BalanceSheetRequest;
use App\DTOs\Coa\BalanceSheetSaveData;
use App\DTOs\Coa\BalanceSheetFilterData;
use App\Services\Coa\BalanceSheetService;
use App\Repositories\Coa\BalanceSheetRepository;

class BalanceSheetCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use PermissionAccess;

    public function __construct(
        protected BalanceSheetService $service,
        protected BalanceSheetRepository $repository
    ) {
        parent::__construct();
    }

    public function setup()
    {
        CRUD::setModel(Account::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/finance-report/balance-sheet');
        CRUD::setEntityNameStrings(trans('backpack::crud.balance_sheet.title_header'), trans('backpack::crud.balance_sheet.title_header'));

        $base = "INDEX LAPORAN KEUANGAN NERACA";
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

    public function listCardComponents()
    {
        $accountAsset = $this->repository->getFirstAccountByType('Assets');

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

        $this->card->addCard([
            'name' => 'account_Assets',
            'line' => 'top',
            'view' => 'crud::components.card-account-balance-sheet',
            'params' => [
                'title' => trans('backpack::crud.balance_sheet.card.asset'),
                'crud' => $this->crud,
                'account' => $accountAsset,
                'route' => url($this->crud->route . '/search?_type=Assets' . $routeSuffix),
            ]
        ]);

        $accountLiabilities = $this->repository->getFirstAccountByType('Liabilities');

        $this->card->addCard([
            'name' => 'account_Liabilities',
            'line' => 'top',
            'view' => 'crud::components.card-account-balance-sheet',
            'params' => [
                'title' => trans('backpack::crud.balance_sheet.card.liabilities'),
                'crud' => $this->crud,
                'account' => $accountLiabilities,
                'route' => url($this->crud->route . '/search?_type=Liabilities' . $routeSuffix),
            ]
        ]);

        $accountEquity = $this->repository->getFirstAccountByType('Equity');

        $this->card->addCard([
            'name' => 'account_Equity',
            'line' => 'top',
            'view' => 'crud::components.card-account-balance-sheet',
            'params' => [
                'title' => trans('backpack::crud.balance_sheet.card.equity'),
                'crud' => $this->crud,
                'account' => $accountEquity,
                'route' => url($this->crud->route . '/search?_type=Equity' . $routeSuffix),
            ]
        ]);

        $this->card->addCard([
            'name' => 'account_total',
            'line' => 'top',
            'view' => 'crud::components.card-asset-total',
            'params' => [
                'route' => url('admin/finance-report/show-total-account' . ($queryStr ? '?' . $queryStr : '')),
            ]
        ]);
    }

    public function index()
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['is_disabled_list'] = true;

        $this->listCardComponents();

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.balance_sheet.title_modal_create');
        $this->data['title_modal_edit'] = trans('backpack::crud.balance_sheet.title_modal_edit');
        $this->data['title_modal_delete'] = trans('backpack::crud.balance_sheet.title_modal_delete');

        $breadcrumbs = [
            trans('backpack::crud.menu.finance_report') => backpack_url('cash-flow'),
            trans('backpack::crud.menu.balance_sheet') => backpack_url($this->crud->route)
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;

        $this->data['journal_years'] = $this->repository->getJournalYears();

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

    protected function setupListOperation()
    {
        // $this->crud->setFromDb(false);
        CRUD::disableResponsiveTable();

        $request = request();

        // CRUD::removeButton('create');
        CRUD::removeButton('update');
        CRUD::removeButton('delete');

        // CRUD::addButtonFromView('top', 'create', 'create-account-profit-lost', 'begining');
        CRUD::addButtonFromView('line', 'delete', "delete-account", 'beginning');
        CRUD::addButtonFromView('line', 'update', "update-account", 'beginning');
        CRUD::addButtonFromView('line', 'view', "view-ledger", 'beginning');
        CRUD::addButtonFromView('top', 'print-all', 'print-all', 'end');

        $this->crud->file_title_export_pdf = "Laporan_akun_neraca.pdf";
        $this->crud->file_title_export_excel = "Laporan_akun_neraca.xlsx";
        $this->crud->param_uri_export = "?export=1";
        CRUD::addButtonFromView('top', 'balance_sheet_pdf', 'balance_sheet_pdf', 'end');
        CRUD::addButtonFromView('top', 'balance_sheet_excel', 'balance_sheet_excel', 'end');


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
            // 'value' => function($entry) {
            //     return CustomHelper::formatRupiahWithCurrency($entry->balance);
            // },
        ]);

        if (request()->has('_type')) {
            $filter = BalanceSheetFilterData::fromRequest(request());
            $this->repository->applyBalanceSheetQuery($this->crud->query, $filter);
        }
    }

    private function setupListExport()
    {
        $settings = \App\Models\Setting::first();
        $year = request('filter_year') ?? request('amp;filter_year') ?? date('Y');
        $quarter = request('filter_quarter') ?? request('amp;filter_quarter') ?? null;
        $filter = BalanceSheetFilterData::fromRequest(request());
        $this->repository->applyBalanceSheetQuery($this->crud->query, $filter);

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
                return $entry->balance;
            },
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
        ]);
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

        $title = "DAFTAR AKUN NERACA";

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
                    $item_value = str_replace('.', '', $item_value);
                    $item_value = str_replace(',', '.', $item_value);
                    $item_value = str_replace('Rp', '', $item_value);
                }
                $item_value = str_replace('<span>', '', $item_value);
                $item_value = str_replace('</span>', '', $item_value);
                $item_value = str_replace("\n", '', $item_value);
                $item_value = CustomHelper::clean_html($item_value);
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $name = 'DAFTAR AKUN NERACA';

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

        $this->data['entry'] = $this->repository->getEntryWithBalance($id);

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

    // validation moved to BalanceSheetRequest

    protected function setupCreateOperation()
    {
        $settings = Setting::first();
        $disabled_attr = [];
        if ($this->crud->getCurrentEntryId()) {
            $disabled_attr = [
                'disabled' => true,
            ];
        }
        CRUD::setValidation(BalanceSheetRequest::class);
        CRUD::addField([   // select_from_array
            'name'        => 'type',
            'label'       => trans('backpack::crud.balance_sheet.fields.account_type.label'),
            'type'        => 'select_from_array',
            'options'     => [
                '' => trans('backpack::crud.balance_sheet.fields.account_type.placeholder'),
                'Assets' => trans('backpack::crud.balance_sheet.fields.account_type.options.account_asset'),
                'Liabilities' => trans('backpack::crud.balance_sheet.fields.account_type.options.account_liabilities'),
                'Equity' => trans('backpack::crud.balance_sheet.fields.account_type.options.account_equity'),
            ],
            'allows_null' => false,
            // 'default'     => '',
        ]);
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
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'placeholder' => '000.000',
                ...$disabled_attr,
            ]
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.balance_sheet.fields.date.label'),
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'suffix' => '<i class="la la-calendar"></i>',
            'wrapper'   => [
                'class' => 'form-group col-md-12'
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.balance_sheet.fields.date.placeholder'),
            ]
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    // moved to BalanceSheetRepository

    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        $request = $this->crud->validateRequest();
        $dto = BalanceSheetSaveData::fromRequest($request);

        try {
            $item = $this->service->store($dto);
            $this->data['entry'] = $this->crud->entry = $item;

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

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

    public function update()
    {
        $this->crud->hasAccessOrFail('update');

        $request = $this->crud->validateRequest();
        $dto = BalanceSheetSaveData::fromRequest($request);

        try {
            $old_account = Account::find($dto->id);
            $rootParent_1 = $this->repository->findRootParent($old_account->code);

            $item = $this->service->update($dto);
            $rootParent_2 = $this->repository->findRootParent($item->code);

            $events = [];
            if ($rootParent_1) {
                $events['account_' . $rootParent_1->type . '_update_success'] = ($item->level == 2) ? $rootParent_1 : $item;
            }

            if ($rootParent_2) {
                $events['account_' . $rootParent_2->type . '_update_success'] = ($item->level == 2) ? $rootParent_2 : $item;
            }

            $this->data['entry'] = $this->crud->entry = $item;

            \Alert::success(trans('backpack::crud.update_success'))->flash();

            return response()->json([
                'success' => true,
                'data' => $item,
                'events' => $events
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

            $item = Account::findOrFail($id);
            $parent_account = $this->repository->findRootParent($item->code);

            $this->service->destroy($id);

            $events = [];
            if ($parent_account) {
                $events['account_' . $parent_account->type . '_update_success'] = true;
            }

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

    public function showTotalAccount()
    {
        $filter = BalanceSheetFilterData::fromRequest(request());
        $totals = $this->repository->getTotals($filter);

        return response()->json([
            'status' => true,
            'total_asset' => CustomHelper::formatRupiahWithCurrency($totals['total_asset']),
            'total_liabilities' => CustomHelper::formatRupiahWithCurrency($totals['total_liabilities']),
            'total_equity' => CustomHelper::formatRupiahWithCurrency($totals['total_equity']),
        ]);
    }

    public function getLedgerDataTable()
    {
        $id = request()->_id;
        $account = Account::findOrFail($id);

        $filter = BalanceSheetFilterData::fromRequest(request());
        $query = $this->repository->getLedgerQuery($account, $filter);

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

        $cumulative_balance = $this->repository->getCumulativeBalanceBefore($account, $entries->first()?->date, $entries->first()?->id);

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

        $filter = BalanceSheetFilterData::fromRequest(request());
        $query = $this->repository->getLedgerQuery($account, $filter);
        $query->orderBy('date', 'asc')->orderBy('id', 'asc');

        $entries = $query->get();
        $cumulative_balance = $this->repository->getCumulativeBalanceBefore($account, $filter->startDate);

        $data = [];
        if ($filter->startDate) {
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
        if ($filter->year && $filter->year != "" && $filter->year != "all") {
            $fileNameFilter .= '_' . $filter->year;
            if ($filter->quarter) {
                $fileNameFilter .= '_Q' . $filter->quarter;
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

        $filter = BalanceSheetFilterData::fromRequest(request());
        $query = $this->repository->getLedgerQuery($account, $filter);
        $query->orderBy('date', 'asc')->orderBy('id', 'asc');

        $entries = $query->get();
        $cumulative_balance = $this->repository->getCumulativeBalanceBefore($account, $filter->startDate);

        $data = [];
        if ($filter->startDate) {
            $data[] = [
                '-',
                'SALDO AWAL',
                0,
                0,
                $cumulative_balance,
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
        if ($filter->year && $filter->year != "" && $filter->year != "all") {
            $fileNameFilter .= '_' . $filter->year;
            if ($filter->quarter) {
                $fileNameFilter .= '_Q' . $filter->quarter;
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
