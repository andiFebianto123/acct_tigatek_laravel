<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Account;
use App\Models\Setting;
use App\Models\CastAccount;
use Illuminate\Support\Str;
use App\Models\JournalEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Exports\ExportExcel;
use App\Http\Helpers\CustomHelper;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use App\Models\LoanTransactionFlag;
use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\PermissionAccess;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

use App\Services\CastAccountLoan\CastAccountLoanService;
use App\Repositories\CastAccountLoan\CastAccountLoanRepository;
use App\DTOs\CastAccountLoan\CastAccountLoanSaveData;
use App\DTOs\CastAccountLoan\LoanTransactionSaveData;
use App\DTOs\CastAccountLoan\LoanMoveTransactionSaveData;
use App\DTOs\CastAccountLoan\CastAccountLoanFilterData;

/**
 * Class CastAccountsCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CastAccountsLoanCrudController extends CrudController
{
    public function __construct(
        protected CastAccountLoanService $service,
        protected CastAccountLoanRepository $repository
    ) {
        parent::__construct();
    }
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;
    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(CastAccount::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/cash-flow/cast-account-loan');
        CRUD::setEntityNameStrings(trans('backpack::crud.cash_account_loan.title_header'), trans('backpack::crud.cash_account_loan.title_header'));

        $allAccess = [
            'AKSES SEMUA MENU ACCOUNTING',
        ];

        $viewMenu = [
            'MENU INDEX ARUS REKENING PINJAMAN',
        ];

        $this->settingPermission([
            'create' => [
                'CREATE INDEX ARUS REKENING PINJAMAN',
                ...$allAccess
            ],
            'update' => [
                'UPDATE INDEX ARUS REKENING PINJAMAN',
                ...$allAccess
            ],
            'delete' => [
                'DELETE INDEX ARUS REKENING PINJAMAN',
                ...$allAccess
            ],
            'list' => $viewMenu,
            'show' => $viewMenu,
            'print' => true,
        ]);
    }

    public function listCardComponents($list = null)
    {
        $filter_year = request()->input('filter_year');
        if ($list && ($list->count() > 0)) {

            foreach ($list as $l) {

                $journal_ = JournalEntry::whereHasMorph('reference', AccountTransaction::class, function ($q) use ($l, $filter_year) {
                    $q->where('cast_account_id', $l->id);
                    if ($filter_year && $filter_year != 'all') {
                        $q->whereYear('date_transaction', $filter_year);
                    }
                })
                    // ->orWhereHasMorph('reference', CastAccount::class, function ($q) use ($l) {
                    //     $q->where('id', $l->id);
                    // })
                    ->select(DB::raw('SUM(debit) - SUM(credit) as total'))
                    ->first();

                $l->saldo = $journal_->total;
                $l->account = Account::find($l->account_id);

                $this->card->addCard([
                    'name' => 'card_cast_account' . $l->id,
                    'line' => 'top',
                    'view' => 'crud::components.cast_account_card',
                    'params' => [
                        'access' => $l->informations,
                        'detail' => $l,
                        'crud' => $this->crud,
                        'name' => 'card_cast_account' . $l->id,
                        'route_edit' => url($this->crud->route . "/" . $l->id . "/edit?type=cast_account"),
                        'route_update' => url($this->crud->route . "/" . $l->id . "?type=cast_account"),
                    ]
                ]);
            }
        } else {
            $this->card->addCard([
                'name' => 'blank_cast_account',
                'line' => 'top',
                'view' => 'crud::components.blank_card',
                'params' => [
                    'message' => trans('backpack::crud.card.blank_cast_account')
                ]
            ]);
        }
    }

    public function index()
    {
        $this->crud->hasAccessOrFail('list');

        $this->data['is_disabled_list'] = true;

        $filters = CastAccountLoanFilterData::fromRequest(request());
        $listCashAccounts = $this->repository->getLoanAccountsWithBalance($filters);
        $this->listCardComponents($listCashAccounts);

        $this->data['year_options'] = CustomHelper::getYearOptions('account_transactions', 'date_transaction');

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.cash_account_loan.title_modal_create');
        $this->data['title_modal_edit'] = trans('backpack::crud.cash_account_loan.title_modal_edit');
        $this->data['title_modal_delete'] = trans('backpack::crud.cash_account_loan.title_modal_delete');

        $breadcrumbs = [
            trans('backpack::crud.menu.cash_flow') => backpack_url('cash-flow'),
            trans('backpack::crud.menu.cash_flow_loan') => backpack_url($this->crud->route)
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;

        $this->data['cards'] = $this->card;
        $this->data['modals'] = $this->modal;
        $this->data['scripts'] = $this->script;
        $list = "crud::list-blank" ?? $this->crud->getListView();
        return view($list, $this->data);
    }

    /**
     * Define what happens when the List operation is loaded.
     *
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        // filter-cash-account-order
        $this->crud->file_title_export_pdf = "Laporan_daftar_rekening_pinjaman.pdf";
        $this->crud->file_title_export_excel = "Laporan_daftar_rekening_pinjaman.xlsx";
        $this->crud->param_uri_export = "?export=1";

        CRUD::addButtonFromView('top', 'export-excel-table', 'export-excel-cast-account', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-table', 'export-pdf-cast-account', 'beginning');
        CRUD::addButtonFromView('top', 'filter_year', 'filter-year-cast-account', 'beginning');
        CRUD::addButtonFromView('top', 'filter_cash_account_order', 'filter-cash-account-order', 'beginning');
    }

    private function setupListExport()
    {
        $settings = Setting::first();

        $this->crud->query = $this->crud->query->leftJoin('account_transactions', 'account_transactions.cast_account_id', '=', 'cast_accounts.id')
            ->where('cast_accounts.status', CastAccount::LOAN);

        $this->crud->query = $this->crud->query->groupBy('cast_accounts.id')
            ->orderBy('id', 'ASC');

        $year = request()->filter_year ?? null;
        $tableBalance = DB::table('account_transactions')
            ->select(DB::raw('
                account_transactions.cast_account_id,
                (SUM(IF(account_transactions.status = "enter", account_transactions.nominal_transaction, 0)) - SUM(IF(account_transactions.status = "out", account_transactions.nominal_transaction, 0))) as saldo
            '))
            ->when($year, function ($query) use ($year) {
                if ($year != "all") {
                    $query->whereYear('date_transaction', $year);
                }
            })
            ->groupBy('cast_account_id');

        $this->crud->query = $this->crud->query->leftJoinSub($tableBalance, 'tableBalance', function ($join) {
            $join->on('tableBalance.cast_account_id', '=', 'cast_accounts.id');
        });

        $this->crud->query = $this->crud->query->select(DB::raw('
            cast_accounts.id,
            MAX(cast_accounts.name) as name,
            MAX(cast_accounts.bank_name) as bank_name,
            MAX(cast_accounts.no_account) as no_account,
            MAX(cast_accounts.status) as status,
            MAX(cast_accounts.account_id) as account_id,
            MAX(tableBalance.saldo) as saldo
        '));


        $this->crud->addColumn([
            'name'      => 'row_number',
            'type'      => 'export',
            'label'     => 'No',
            'orderable' => false,
            'wrapper' => [
                'element' => 'strong',
            ]
        ])->makeFirstColumn();

        CRUD::column(
            [
                'label'  => trans('backpack::crud.cash_account.field.name.label'),
                'name' => 'name',
                'type'  => 'export'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.cash_account.field.bank_name.label'),
                'name' => 'bank_name',
                'type'  => 'export'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.cash_account.field.no_account.label'),
                'name' => 'no_account',
                'type'  => 'export'
            ],
        );

        CRUD::column([
            'label'  => trans('backpack::crud.cash_account.field.total_saldo.label'),
            'name' => 'saldo',
            'type'  => 'number',
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
        ]);
    }

    private function setuplistExportTrans()
    {
        $id = request()->id;
        $filter_year = request()->input('filter_year');
        $new_format_date = 'DD/MM/YYYY';
        CRUD::setModel(AccountTransaction::class);

        $castAccount = CastAccount::where('id', $id)->first();

        $this->crud->query = $this->crud->query
            ->where('cast_account_id', $id)
            ->where('is_first', 0);

        if ($filter_year && $filter_year != 'all') {
            $this->crud->query = $this->crud->query->whereYear('account_transactions.date_transaction', $filter_year);
        }

        $this->crud->query = $this->crud->query->orderBy('date_transaction', 'ASC');

        $this->crud->addColumn([
            'name'      => 'row_number',
            'type'      => 'export',
            'label'     => 'No',
            'orderable' => false,
            'wrapper' => [
                'element' => 'strong',
            ]
        ])->makeFirstColumn();

        CRUD::column(
            [
                'label'  => trans('backpack::crud.cash_account.field_transaction.date_transaction.label'),
                'name' => 'date_transaction',
                'type'  => 'export'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.cash_account.field_transaction.nominal_transaction.label'),
                'name' => 'nominal_transaction',
                'type'  => 'export'
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.cash_account_loan.field.cast_account_destination_id.label'),
                'name' => 'cast_account_destination_id',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return ($entry->cast_account_destination_id) ? $entry->cast_account_destination->name : ($entry->description ?? '-');
                }
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.cash_account.field_transaction.nominal_transaction.label'),
                'name' => 'status',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return ucfirst(strtolower(trans('backpack::crud.cash_account.field_transaction.status.' . $entry->status)));
                }
            ],
        );

        return $castAccount;
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

        $title = "DAFTAR REKENING PINJAMAN";

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
                if ($column['name'] == 'row_number') {
                    $item_value = $row_number;
                } elseif ($column['name'] == 'saldo') {
                    $item_value = $item->saldo;
                } else {
                    $item_value = $this->crud->getCellView($column, $item, $row_number);
                    $item_value = str_replace('<span>', '', $item_value);
                    $item_value = str_replace('</span>', '', $item_value);
                    $item_value = str_replace("\n", '', $item_value);
                    $item_value = CustomHelper::clean_html($item_value);
                    $item_value = trim($item_value);
                }
                $row_items[] = $item_value;
            }
            $all_items[] = $row_items;
        }

        $name = 'DAFTAR_REKENING_PINJAMAN';

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

    public function exportTransPdf()
    {
        $cast = $this->setuplistExportTrans();

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
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $title = "DAFTAR TRANSAKSI REKENING PINJAMAN " . $cast?->name;

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

    public function exportTransExcel()
    {
        $this->setuplistExportTrans();

        $columns = $this->crud->columns();
        $items =  $this->crud->getEntries();

        $row_number = 0;

        $all_items = [];

        foreach ($items as $item) {
            $row_items = [];
            $row_number++;
            foreach ($columns as $column) {
                if ($column['name'] == 'row_number') {
                    $item_value = $row_number;
                } elseif ($column['name'] == 'nominal_transaction') {
                    $item_value = $item->nominal_transaction;
                } else {
                    $item_value = $this->crud->getCellView($column, $item, $row_number);
                    $item_value = str_replace(['<span>', '</span>', "\n"], '', $item_value);
                    $item_value = trim($item_value);
                }
                $row_items[] = $item_value;
            }
            $all_items[] = $row_items;
        }

        $name = 'DAFTAR_REKENING_PINJAMAN';

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



    function account_select2()
    {
        $this->crud->hasAccessOrFail('create');

        $search = request()->input('q');
        $dataset = \App\Models\Account::select(['id', 'code', 'name'])
            ->where('name', 'LIKE', "%$search%")
            ->orWhere('code', 'LIKE', "%$search%")
            ->orderBy('code', 'ASC')
            ->paginate(10);

        $results = [];
        foreach ($dataset as $item) {
            $results[] = [
                'id' => $item->id,
                'text' => $item->code . ' - ' . $item->name,
            ];
        }
        return response()->json(['results' => $results]);
    }

    function loan_transaction_flag_select2()
    {
        $this->crud->hasAccessOrFail('create');;

        $search = request()->input('q');
        $cast_account_id = request()->input('castaccount');

        $dataset = LoanTransactionFlag::select(['id', 'kode', 'total_price'])
            ->whereExists(function ($q) use ($cast_account_id) {
                $q->selectRaw(1)
                    ->from('account_transactions as at2')
                    ->whereColumn('at2.reference_id', 'loan_transaction_flags.id')
                    ->where('at2.reference_type', LoanTransactionFlag::class)
                    ->where('at2.cast_account_id', $cast_account_id);
            })
            ->where('kode', 'LIKE', "%$search%")
            ->orderBy('id', 'DESC')
            ->paginate(10);

        $results = [];
        foreach ($dataset as $item) {
            $results[] = [
                'id' => $item->id,
                'text' => $item->kode,
            ];
        }
        return response()->json(['results' => $results]);
    }

    function get_loan_balance_ajax()
    {
        $search = request()->input('loan_transaction_flag_id');
        if ($search == null) {
            return response()->json([
                'status' => false,
                'message' => 'Loan transaction flag not found',
            ]);
        }
        $loan_transaction_flag = LoanTransactionFlag::find($search);
        $total_balance_out = AccountTransaction::where("reference_id", $search)
            ->whereNull('cast_account_destination_id')
            ->where("reference_type", LoanTransactionFlag::class)
            ->where('status', CastAccount::OUT)
            ->sum('nominal_transaction');
        $remaining_balance = $loan_transaction_flag->total_price - $total_balance_out;
        return response()->json(['remaining_balance' => CustomHelper::formatRupiahWithCurrency($remaining_balance)]);
    }

    function createTransactionOperation($id = null)
    {

        CRUD::setModel(AccountTransaction::class);
        CRUD::setValidation(\App\Http\Requests\CastAccountLoan\LoanTransactionRequest::class);
        $settings = Setting::first();

        CRUD::addField([
            'name' => 'cast_account_id ',
            'type' => 'hidden',
            'value' => $id,
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'date_transaction',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.cash_account.field_transaction.date_transaction.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.cash_account.field_transaction.date_transaction.placeholder')
            ]
        ]);

        CRUD::addField([
            'name' => 'space_1',
            'type' => 'hidden',
            'value' => 'space_1',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'nominal_transaction',
            'label' =>  trans('backpack::crud.cash_account.field_transaction.nominal_transaction.label'),
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
            'name' => 'space_2',
            'type' => 'hidden',
            'value' => 'space_2',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::field([  // Select2
            'label'     => trans('backpack::crud.cash_account_loan.field.cast_account_destination_id.label'),
            'type'      => 'select2_array',
            'name'      => 'cast_account_destination_id',
            'options'   => array_replace([
                '' => trans('backpack::crud.cash_account_loan.field.cast_account_destination_id.placeholder'),
            ], CastAccount::where("status", CastAccount::CASH)->pluck('name', 'id')->all()),
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'space_3',
            'type' => 'hidden',
            'value' => 'space_3',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => trans('backpack::crud.cash_account.field_transaction.description.label'),
            'type' => 'textarea',
            'attributes' => [
                'placeholder' => trans('backpack::crud.cash_account.field_transaction.description.placeholder'),
            ],
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'space_4',
            'type' => 'hidden',
            'value' => 'space_4',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);
    }

    function createMoveTransactionOperation($id = null)
    {
        CRUD::setModel(AccountTransaction::class);
        CRUD::setValidation(\App\Http\Requests\CastAccountLoan\LoanMoveTransactionRequest::class);
        $settings = Setting::first();

        CRUD::addField([
            'name' => 'cast_account_id ',
            'type' => 'hidden',
            'value' => $id,
        ]);

        CRUD::addField([
            'name' => 'balance_information',
            'type' => 'balance-information',
            'wrapper' => [
                'class' => 'form-group col-md-12'
            ],
            'value' => 0,
        ]);

        CRUD::addField([
            'label' => trans('backpack::crud.cash_account_loan.field.balance_information.loan_transaction_flag_id'),
            'type'        => "select2_ajax_custom",
            'name'        => 'loan_transaction_flag_id',
            'entity'      => 'account',
            //'model'       => 'App\Models\LoanTransactionFlag',
            'attribute'   => "kode",
            'data_source' => backpack_url('cash-flow/cast-account-loan/loan-transaction-flag-select2?castaccount=' . $id),
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => []
        ]);

        CRUD::addField([
            'name' => 'space_0',
            'type' => 'hidden',
            'value' => 'space_0',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'date_loan_transaction',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.cash_account_loan.field.balance_information.date'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'space_1',
            'type' => 'hidden',
            'value' => 'space_1',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::field([  // Select2
            'label'     => trans('backpack::crud.voucher.field.account_source_id.label'),
            'type'      => 'select2_array',
            'name'      => 'cast_account_destination_id',
            'options'   => array_replace([
                '' => trans('backpack::crud.voucher.field.account_source_id.placeholder'),
            ], CastAccount::where('status', CastAccount::CASH)->pluck('name', 'id')->all()),
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'space_2',
            'type' => 'hidden',
            'value' => 'space_2',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'payment_price',
            'label' =>  trans('backpack::crud.cash_account_loan.field.balance_information.payment_price'),
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
            'name' => 'space_3',
            'type' => 'hidden',
            'value' => 'space_3',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => trans('backpack::crud.cash_account.field_transaction.description.label'),
            'type' => 'textarea',
            'attributes' => [
                'placeholder' => trans('backpack::crud.cash_account.field_transaction.description.placeholder'),
            ],
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'logic_cast_loan',
            'type' => 'logic-cast-loan',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ],
        ]);
    }

    /**
     * Define what happens when the Create operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        // CRUD::setFromDb(); // set fields from db columns.
        $settings = Setting::first();

        $request = request();

        if ($request->has('_id') && $request?->type == null) {
            $this->createTransactionOperation($request->_id);
            return;
        }

        if ($request->has('type')) {
            if ($request->has('_id')) {
                if ($request->type == 'move') {
                    $this->createMoveTransactionOperation($request->_id);
                    return;
                }
            }
            if ($request->type == 'cast_account') {
                $id = $request->id ?? '';
                CRUD::setValidation(\App\Http\Requests\CastAccountLoan\CastAccountLoanRequest::class);
                CRUD::addField([
                    'name' => 'name',
                    'label' => trans('backpack::crud.cash_account.field.name.label'),
                    'type' => 'text',
                    'attributes' => [
                        'placeholder' => trans('backpack::crud.cash_account.field.name.placeholder'),
                    ]
                ]);
                return;
            }
        } else {
            CRUD::setValidation(\App\Http\Requests\CastAccountLoan\CastAccountLoanRequest::class);
            CRUD::addField([
                'name' => 'name',
                'label' => trans('backpack::crud.cash_account_loan.field.name.label'),
                'type' => 'text',
                'attributes' => [
                    'placeholder' => trans('backpack::crud.cash_account_loan.field.name.placeholder'),
                ]
            ]);

            CRUD::field([  // Select2
                'label'     => trans('backpack::crud.cash_account_loan.field.bank_name.label'),
                'type'      => 'select2_array',
                'name'      => 'bank_name',
                'options'   => ['' => trans('backpack::crud.cash_account_loan.field.bank_name.placeholder'), ...CustomHelper::getBanks()], // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
                'wrapper' => [
                    'class' => 'form-group col-md-6'
                ]
            ]);

            CRUD::addField([
                'name' => 'no_account',
                'label' => trans('backpack::crud.cash_account_loan.field.no_account.label'),
                'type' => 'text',
                'wrapper'   => [
                    'class' => 'form-group col-md-6',
                ],
                'attributes' => [
                    'placeholder' => trans('backpack::crud.cash_account_loan.field.no_account.placeholder'),
                ]
            ]);

            CRUD::addField([
                'label'       => trans('backpack::crud.cash_account_loan.field.account.label'), // Table column heading
                'type'        => "select2_ajax_custom",
                'name'        => 'account_id',
                'entity'      => 'account',
                'model'       => 'App\Models\Account',
                'attribute'   => "name",
                'data_source' => backpack_url('account/select2-account'),
                'wrapper'   => [
                    'class' => 'form-group col-md-6',
                ],
                'attributes' => [
                    'placeholder' => trans('backpack::crud.cash_account_loan.field.account.placeholder'),
                ]
            ]);

            CRUD::addField([
                'type' => 'hidden',
                'name' => 'space_1',
                'wrapper' => [
                    'class' => 'form-group col-md-6'
                ]
            ]);

            CRUD::addField([
                'name' => 'total_saldo',
                'label' => trans('backpack::crud.cash_account.field.total_saldo.label'),
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
                'name'  => 'date_transaction_init',
                'type'  => 'date_picker',
                'label' => trans('backpack::crud.cash_account.field_transaction.date_transaction.label'),
                'date_picker_options' => [
                    'language' => App::getLocale(),
                ],
                'wrapper' => [
                    'class' => 'form-group col-md-6'
                ],
                'attributes' => [
                    'placeholder' => trans('backpack::crud.cash_account.field_transaction.date_transaction.placeholder')
                ]
            ]);

            CRUD::addField([
                'type' => 'hidden',
                'name' => 'status',
                'value' => CastAccount::LOAN,
                'wrapper' => [
                    'class' => 'form-group col-md-6'
                ]
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

        CRUD::setValidation(\App\Http\Requests\CastAccountLoan\CastAccountLoanRequest::class);
        $request = $this->crud->validateRequest();

        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {
            $data = CastAccountLoanSaveData::fromRequest($request);
            $item = $this->service->storeLoanAccount($data);

            $this->data['entry'] = $this->crud->entry = $item;

            $this->crud->setSaveAction();

            DB::commit();
            // return $this->crud->performSaveAction($item->getKey());

            return response()->json([
                'success' => true,
                'data' => $item,
                'events' => [
                    'cast_account_store_success' => $item,
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function generateCodeLoan()
    {
        do {
            $kode = 'LOAN-' . strtoupper(Str::random(8));
            $check = LoanTransactionFlag::where('kode', $kode)->first();
        } while ($check != null);
        return $kode;
    }

    public function storeTransaction()
    {
        $this->crud->hasAccessOrFail('create');
        CRUD::setValidation(\App\Http\Requests\CastAccountLoan\LoanTransactionRequest::class);

        $request = $this->crud->validateRequest();

        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {
            $data = LoanTransactionSaveData::fromRequest($request);
            $item = $this->service->storeTransaction($data);

            $total_saldo = CustomHelper::balanceAccount($item->account->code);
            $item->new_saldo = CustomHelper::formatRupiahWithCurrency($total_saldo);

            $this->data['entry'] = $this->crud->entry = $item;

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            $this->crud->setSaveAction();

            DB::commit();
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
                    'events' => [
                        'card_cast_account' . $data->cast_account_id . '_create_success' => $item,
                        'card_cast_account' . $data->cast_account_id . '_store_move_success' => $item
                    ]
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
        CRUD::setValidation(\App\Http\Requests\CastAccountLoan\CastAccountLoanRequest::class);
        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {
            $data = CastAccountLoanSaveData::fromRequest($request);
            $item = $this->service->storeLoanAccount($data); // Service should handle update if ID exists, or create updateCastAccount

            \Alert::success(trans('backpack::crud.update_success'))->flash();
            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $item,
                'events' => ['cast_account_store_success' => true]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['status' => false, 'success' => false, 'error' => $e->getMessage()]);
        }
    }

    public function storeMoveTransaction()
    {
        $this->crud->hasAccessOrFail('create');

        CRUD::setValidation(\App\Http\Requests\CastAccountLoan\LoanMoveTransactionRequest::class);

        $request = $this->crud->validateRequest();
        $this->crud->registerFieldEvents();
        DB::beginTransaction();

        try {
            $data = LoanMoveTransactionSaveData::fromRequest($request);
            $item = $this->service->storeMoveTransaction($data);

            $first_account_transaction = $this->repository->getFirstAccountTransactionByFlagId($data->loan_transaction_flag_id);
            $total_balance = CustomHelper::balanceAccount(Account::find($first_account_transaction->account_id)->code);
            $item->saldo = CustomHelper::formatRupiahWithCurrency($total_balance);

            $this->data['entry'] = $item;

            $this->crud->setSaveAction();

            DB::commit();
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'events' => [
                        'card_cast_account' . $data->cast_account_id . '_store_move_success' => $item,
                    ]
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

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');
        $id = $this->crud->getCurrentEntryId() ?? $id;

        DB::beginTransaction();
        try{
            $result = $this->service->deleteLoanAccount($id);
            DB::commit();
            $event = [];
            $event['cast_account_store_success'] = true;

            $messages['success'][] = trans('backpack::crud.delete_confirmation_message');
            $messages['events'] = $event;
            return response()->json($messages);
        }
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'type' => 'errors',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function showTransaction()
    {
        $id = request()->_id;
        $page = request()->page ?? 1;
        $per_page = request()->per_page ?? 2;
        $filter_year = request()->filter_year;

        $castAccount = $this->repository->findById($id);
        $query = $this->repository->getTransactionQuery($id, $filter_year);

        $query->orderByDesc('account_transactions.reference_id')
            ->orderBy('account_transactions.id');

        $total = $query->count();
        $detail = $query->skip(($page - 1) * $per_page)->take($per_page)->get();

        // Cari kode terakhir dari data sebelumnya untuk grouping lintas page
        $prev_kode = null;
        if ($page > 1) {
            $prev_item_query = AccountTransaction::select(['loan_transaction_flags.kode'])
                ->join("loan_transaction_flags", function ($join) {
                    $join->on('loan_transaction_flags.id', '=', 'account_transactions.reference_id')
                        ->where('account_transactions.reference_type', LoanTransactionFlag::class);
                })
                ->where('account_transactions.cast_account_id', $id);

            if ($filter_year && $filter_year != 'all') {
                $prev_item_query->whereYear('account_transactions.date_transaction', $filter_year);
            }

            $prev_item = $prev_item_query->orderByDesc('account_transactions.reference_id')
                ->orderBy('account_transactions.id')
                ->skip(($page - 1) * $per_page - 1)
                ->take(1)
                ->first();
            $prev_kode = $prev_item ? $prev_item->kode : null;
        }

        foreach ($detail as $row => $entry) {
            $is_new_group = false;
            if ($row == 0) {
                // Baris pertama di batch ini, cek terhadap batch sebelumnya
                if ($page == 1 || $prev_kode != $entry->kode) {
                    $is_new_group = true;
                }
            } else {
                // Baris di dalam batch yang sama, cek terhadap baris sebelumnya
                if ($detail[$row - 1]->kode != $entry->kode) {
                    $is_new_group = true;
                }
            }

            if ($is_new_group) {
                $entry->status_str = ($entry->status == 1) ? 'Paid' : 'Unpaid';
                $entry->kode_str = $entry->kode;
                $entry->nominal_str = "-";
            } else {
                $entry->status_str = '-';
                $entry->kode_str = '-';
                $entry->nominal_str = CustomHelper::formatRupiahWithCurrency($entry->nominal_transaction);
            }
            $entry->loan_str = CustomHelper::formatRupiahWithCurrency($entry->loan_remaining);
            $entry->date_str = \Carbon\Carbon::parse($entry->date_transaction)->translatedFormat('d/m/Y');
        }

        $total_balance = CustomHelper::total_balance_cast_account($id, CastAccount::LOAN, $filter_year);
        $castAccount->total_saldo_str = CustomHelper::formatRupiahWithCurrency($total_balance);

        return response()->json([
            'status' => true,
            'result' => [
                'cast_account' => $castAccount,
                'detail' => $detail,
                'has_more' => ($page * $per_page) < $total,
                'current_page' => (int)$page,
                'total' => $total
            ]
        ]);
    }

    public function getSelectToAccount()
    {
        $castAccounts = CastAccount::whereHas('informations', function ($q) {
            $q->where("additional_informations.id", 2)->select(DB::raw("1"));
        })->where('status', CastAccount::CASH)->get(['id', 'name']);
        return response()->json([
            'status' => true,
            'result' => $castAccounts,
        ]);
    }
}
