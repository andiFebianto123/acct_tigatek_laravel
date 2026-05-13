<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\PermissionAccess;
use App\Http\Exports\ExportExcel;
use App\Http\Helpers\CustomHelper;
use App\Http\Helpers\CustomVoid;
use App\Models\Account;
use App\Models\AccountTransaction;
use App\Models\CastAccount;
use App\Models\InvoiceClient;
use App\Models\JournalEntry;
use App\Models\LogPayment;
use App\Models\Setting;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\DTOs\CastAccount\CastAccountSaveData;
use App\DTOs\CastAccount\CastAccountFilterData;
use App\DTOs\CastAccount\TransactionSaveData;
use App\DTOs\CastAccount\TransferBalanceData;
use App\Services\CastAccount\CastAccountService;
use App\Repositories\CastAccount\CastAccountRepository;

/**
 * Class CastAccountsCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CastAccountsCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;

    public function __construct(
        protected CastAccountService $service,
        protected CastAccountRepository $repository
    ) {
        parent::__construct();
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(CastAccount::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/cash-flow/cast-accounts');
        CRUD::setEntityNameStrings(trans('backpack::crud.cash_account.title_header'), trans('backpack::crud.cash_account.title_header'));

        $allAccess = [
            'AKSES SEMUA MENU ACCOUNTING',
        ];

        $viewMenu = [
            "MENU INDEX ARUS REKENING KAS",
        ];

        $this->settingPermission([
            'create' => [
                'CREATE INDEX ARUS REKENING KAS',
                ...$allAccess
            ],
            'update' => [
                'UPDATE INDEX ARUS REKENING KAS',
                ...$allAccess
            ],
            'delete' => [
                'DELETE INDEX ARUS REKENING KAS',
                ...$allAccess
            ],
            'list' => $viewMenu,
            'show' => $viewMenu,
            'print' => true,
        ]);
    }

    public function listCardComponents($list = null)
    {
        if ($list && ($list->count() > 0)) {

            $this->modal->addModal([
                'name' => 'modal_info_cast_account',
                'title' => 'info',
                'title_alignment' => 'center',
                'size' => 'modal-fullscreen',
                'view' => 'crud::components.modal-info-cast-account',
                'params' => [
                    'name' => 'modal_info_cast_account',
                    'crud' => $this->crud,
                ],
                'buttons' => [
                    'footer' => [
                        'right' => [
                            [
                                'class' => 'btn btn-primary btn-sm',
                                'label' => trans('backpack::crud.modal.close'),
                                'action' => "$('#modal_info_cast_account .btn-close').click()",
                            ]
                        ]
                    ]
                ]
            ]);

            $this->modal->addModal([
                'name' => 'modal_transfer_balance',
                'title' => trans('backpack::crud.modal.title_transfer_balance'),
                'title_alignment' => 'center',
                'size' => 'modal-lg',
                'view' => 'crud::components.modal-transfer-balance-cast-account',
                'params' => [
                    'name' => 'modal_transfer_balance',
                    'crud' => $this->crud,
                    'settings' => Setting::first(),
                ],
                'buttons' => [
                    'footer' => [
                        'right' => [
                            [
                                'class' => 'btn btn-secondary btn-sm',
                                'label' => trans('backpack::crud.modal.cancel'),
                                'action' => "$('#modal_transfer_balance .btn-close').click()",
                            ],
                            [
                                'class' => 'btn btn-primary btn-sm btn-transfer-balance',
                                'label' => trans('backpack::crud.modal.move'),
                            ],
                        ]
                    ]
                ]
            ]);

            foreach ($list as $l) {
                $l->saldo = ($l->total_saldo_enter - $l->total_saldo_out);
                $this->card->addCard([
                    'name' => 'card_cast_account' . $l->id,
                    'line' => 'top',
                    'view' => 'crud::components.cast_account_card',
                    'params' => [
                        'access' => $l->informations,
                        'detail' => $l,
                        'crud' => $this->crud,
                        'name' => 'card_cast_account' . $l->id,
                        'route_edit' => url($this->crud->route . "/" . $l->id . '/edit?type=cast_account'),
                        'route_update' => url($this->crud->route . "/" . $l->id . '?type=cast_account'),
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

        $filters = CastAccountFilterData::fromRequest(request());
        $listCashAccounts = $this->repository->getCastAccountsWithBalance($filters);

        $this->listCardComponents($listCashAccounts);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.cash_account.title_modal_create');
        $this->data['title_modal_edit'] = trans('backpack::crud.cash_account.title_modal_edit');
        $this->data['title_modal_delete'] = trans('backpack::crud.cash_account.title_modal_delete');

        $breadcrumbs = [
            trans('backpack::crud.menu.cash_flow') => backpack_url('cash-flow'),
            trans('backpack::crud.menu.cash_flow_cash') => backpack_url($this->crud->route)
        ];
        $this->data['year_options'] = CustomHelper::getYearOptions('account_transactions', 'date_transaction');
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
        $this->crud->file_title_export_pdf = "Laporan_daftar_rekening_kas.pdf";
        $this->crud->file_title_export_excel = "Laporan_daftar_rekening_kas.xlsx";
        $this->crud->param_uri_export = "?export=1";

        CRUD::addButtonFromView('top', 'export-excel-table', 'export-excel-cast-account', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf-table', 'export-pdf-cast-account', 'beginning');
        CRUD::addButtonFromView('top', 'filter_year', 'filter-year-cast-account', 'beginning');
        CRUD::addButtonFromView('top', 'filter_cash_account_order', 'filter-cash-account-order', 'beginning');

        // CRUD::setFromDb(); // set columns from db columns.

        /**
         * Columns can be defined using the fluent syntax:
         * - CRUD::column('price')->type('number');
         */
    }

    private function setupListExport()
    {
        $settings = Setting::first();
        $this->crud->query = $this->crud->query->leftJoin('account_transactions', 'account_transactions.cast_account_id', '=', 'cast_accounts.id')
            ->where('cast_accounts.status', CastAccount::CASH)
            ->groupBy('cast_accounts.id')
            ->orderBy('id', 'ASC');

        if (request()->has('filter_year') && request()->filter_year != 'all') {
            $this->crud->query = $this->crud->query->whereYear('account_transactions.date_transaction', request()->filter_year);
        }

        $this->crud->query = $this->crud->query->select(DB::raw('
            cast_accounts.id,
            MAX(cast_accounts.name) as name,
            MAX(cast_accounts.bank_name) as bank_name,
            MAX(cast_accounts.no_account) as no_account,
            MAX(cast_accounts.status) as status,
            (SUM(IF(account_transactions.status = "enter", account_transactions.nominal_transaction, 0)) - SUM(IF(account_transactions.status = "out", account_transactions.nominal_transaction, 0))) as saldo
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

        CRUD::column(
            [
                'label'  => trans('backpack::crud.cash_account.field.total_saldo.label'),
                'name' => 'saldo',
                'type'  => 'text'
            ],
        );
        // CRUD::column([
        //     'label'  => trans('backpack::crud.cash_account.field.total_saldo.label'),
        //     'name' => 'saldo',
        //     'type'  => 'number',
        //     'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
        //     'decimals'      => 2,
        //     'dec_point'     => ',',
        //     'thousands_sep' => '.',
        // ]);
    }

    private function setuplistExportTrans()
    {
        $id = request()->id;
        $new_format_date = 'DD/MM/YYYY';
        $settings = Setting::first();
        CRUD::setModel(AccountTransaction::class);
        $castAccount = CastAccount::where('id', $id)->first();
        // $detail = AccountTransaction::where('cast_account_id', $id)
        // ->where('is_first', 0)
        // ->orderBy('date_transaction', 'ASC')->get();
        $this->crud->query = $this->crud->query
            ->leftJoin('invoice_clients', function ($join) {
                $join->on('account_transactions.reference_id', 'invoice_clients.id')
                    ->where('account_transactions.reference_type', 'App\\Models\\InvoiceClient');
            })
            ->leftJoin('vouchers', function ($join) {
                $join->on('account_transactions.reference_id', 'vouchers.id')
                    ->where('account_transactions.reference_type', 'App\\Models\\Voucher');
            })
            ->leftJoin('invoice_clients as voucher_invoice', function ($join) {
                $join->on('vouchers.client_po_id', 'voucher_invoice.client_po_id');
            })
            ->leftJoin('accounts as voucher_account', 'vouchers.account_id', '=', 'voucher_account.id')
            ->leftJoin('cast_accounts as trans_cast_account', 'account_transactions.cast_account_id', '=', 'trans_cast_account.id')
            ->leftJoin('accounts as trans_account', 'trans_cast_account.account_id', '=', 'trans_account.id')
            ->select([
                'account_transactions.*',
                'invoice_clients.kdp as invoice_kdp',
                'vouchers.work_code as voucher_kdp',
                'vouchers.payment_description as voucher_description',
                'voucher_account.code as voucher_account_code',
                'voucher_account.name as voucher_account_name',
                'trans_account.code as trans_account_code',
                'trans_account.name as trans_account_name',
                'invoice_clients.invoice_number as direct_invoice_number',
                'voucher_invoice.invoice_number as indirect_invoice_number'
            ])
            ->where('account_transactions.cast_account_id', $id)
            ->where('account_transactions.is_first', 0);

        if (request()->has('filter_year') && request()->filter_year != 'all') {
            $this->crud->query = $this->crud->query->whereYear('account_transactions.date_transaction', request()->filter_year);
        }

        $wrap_length = [
            'width_box' => '300px',
        ];

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

        CRUD::column([
            'label'  => trans('backpack::crud.cash_account.field_transaction.date_transaction.label'),
            'name' => 'date_transaction',
            'type'  => 'date',
            'format' => $new_format_date,
        ]);

        // CRUD::column(
        //     [
        //         'label'  => trans('backpack::crud.cash_account.field_transaction.nominal.label'),
        //         'name' => 'nominal_transaction',
        //         'type'  => 'export'
        //     ],
        // );

        CRUD::column([
            'label'  => trans('backpack::crud.cash_account.field_transaction.nominal.label'),
            'name' => 'nominal_transaction',
            'type'  => 'number',
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
        ]);

        CRUD::column(
            [
                'label'  => trans('backpack::crud.cash_account.field_transaction.description.label'),
                'name' => 'description',
                'type'  => 'closure',
                'function' => function ($row) {
                    return $row->voucher_description ?: ($row->description ?? '-');
                },
                ...$wrap_length
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.cash_account.field_transaction.kdp.label'),
                'name' => 'kdp',
                'type'  => 'closure',
                'function' => function ($row) {
                    return $row->invoice_kdp ?: ($row->voucher_kdp ?: ($row->kdp ?? '-'));
                }
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.cash_account.field_transaction.job_name.label'),
                'name' => 'job_name',
                'type'  => 'wrap_text',
                ...$wrap_length
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.cash_account.field_transaction.account.label'),
                'name' => 'account',
                'type'  => 'closure',
                'function' => function ($row) {
                    return ($row->voucher_account_code) ? $row->voucher_account_code . ' - ' . $row->voucher_account_name : (($row->trans_account_code) ? $row->trans_account_code . ' - ' . $row->trans_account_name : '-');
                }
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.cash_account.field_transaction.no_invoice.label'),
                'name' => 'no_invoice',
                'type'  => 'closure',
                'function' => function ($row) {
                    return $row->direct_invoice_number ?: ($row->indirect_invoice_number ?: ($row->no_invoice ?? '-'));
                }
            ],
        );

        CRUD::column(
            [
                'label'  => trans('backpack::crud.cash_account.field_transaction.status.label'),
                'name' => 'status',
                'type'  => 'closure',
                'function' => function ($row) {
                    return ucfirst(strtolower(trans('backpack::crud.cash_account.field_transaction.status.' . $row->status)));
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

        $title = "DAFTAR REKENING KAS";

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
                    $item_value = str_replace(['<span>', '</span>', "\n"], '', $item_value);
                    $item_value = CustomHelper::clean_html($item_value);
                    $item_value = trim($item_value);
                }
                $row_items[] = $item_value;
            }
            $all_items[] = $row_items;
        }

        $name = 'DAFTAR_REKENING_KAS';

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
        $id = request()->id;
        $cast = $this->setuplistExportTrans();

        $columns = $this->crud->columns();
        $items =  $this->repository->getTransactionsByCastAccountId((int)$id, request()->filter_year);
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

        $title = "DAFTAR TRANSAKSI KAS REKENING " . $cast?->name;

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
        $id = request()->id;
        $this->setuplistExportTrans();

        $columns = $this->crud->columns();
        $items =  $this->repository->getTransactionsByCastAccountId((int)$id, request()->filter_year);

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
                    $item_value = CustomHelper::clean_html($item_value);
                    $item_value = trim($item_value);
                }
                $row_items[] = $item_value;
            }
            $all_items[] = $row_items;
        }

        $name = 'DAFTAR_TRANSAKSI_REKENING_KAS';

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

    function ruleValidation()
    {
        $id = request('id') ?? '';
        $no_account = request('no_account') ?? '';
        // 'name' => 'required|min:5|max:255'
        return [
            'name' => 'required|max:100|unique:cast_accounts,name,' . $id,
            'bank_name' => 'required|max:50',
            'no_account' => 'required|max:100,unique:cast_accounts,no_account,' . $id,
            'total_saldo' => 'required|numeric|min:0',
            'account_id' => [
                'required',
                'exists:accounts,id',
                function ($attribute, $value, $fail) use ($no_account) {
                    $castAccount = CastAccount::where('account_id', $value)
                        ->where('status', CastAccount::CASH)
                        ->where('no_account', $no_account)
                        ->first();
                    if ($castAccount) {
                        $fail(trans('backpack::crud.cash_account.field_transfer.errors.account_id_exist'));
                    }
                    return true;
                }
            ]
        ];
    }

    function ruleValidationTransaction()
    {
        $cast_account_id = request()->cast_account_id;
        $has_access_primary = $this->accessAccount($cast_account_id);
        $has_access_payment = $this->accessPaymentAccount($cast_account_id);
        $status = request()->status; // Ambil status dari request

        $rule = [
            'date_transaction' => 'required',
            'status' => 'required|in:enter,out', // Validasi status
            'nominal_transaction' => [
                'required',
                'numeric',
                'min:0'
            ],
            'kdp' => 'max:50',
            'job_name' => 'max:100',
            'no_invoice' => 'max:100',
            'account_id' => 'required|exists:accounts,id',
        ];

        if (request()->has('no_invoice') && request()->no_invoice != '' && request()->has('kdp') && request()->kdp != '') {
            $rule['account_id'] = 'nullable';
        }

        // Validasi saldo hanya untuk transaksi keluar (out) dan bukan primary/payment account
        if ($has_access_primary == 0 && $status == 'out') {
            $rule['nominal_transaction'] = [
                'required',
                'numeric',
                'min:1000',
                function ($attribute, $value, $fail) use ($cast_account_id, $has_access_payment) {
                    if ($has_access_payment > 0) {
                        // Payment account bisa overdraft
                    } else {
                        $balance = CustomHelper::total_balance_cast_account($cast_account_id, CastAccount::CASH);
                        if ($value > $balance) {
                            $fail(trans("backpack::crud.cash_account.field_transfer.errors.nominal_transfer_to_more"));
                        }
                    }
                }
            ];
        }

        return $rule;
    }

    function ruleValidationEditTransactionAccountPrimary()
    {
        return [
            'date_transaction' => 'required',
            'kdp' => 'max:50',
            'job_name' => 'max:100',
            'no_invoice' => 'max:100',
            'account_id' => 'exists:accounts,id',
        ];
    }

    function ruleValidationEditTransactionAccountSecondary()
    {
        $cast_account_id = request()->cast_account_id;
        $id = request()->id;
        $has_access_payment = $this->accessPaymentAccount($cast_account_id);

        // Cek apakah transaksi memiliki log_payment
        $has_log_payment = false;
        if ($id) {
            $has_log_payment = LogPayment::whereHasMorph('reference', AccountTransaction::class, function ($q) use ($id) {
                $q->where('id', $id);
            })->exists();
        }

        $rules = [
            'date_transaction' => 'required',
            'kdp' => 'max:50',
            'job_name' => 'max:100',
            'no_invoice' => 'max:100',
            'account_id' => 'exists:accounts,id',
            'nominal_transaction' => [
                'required',
                'numeric',
                'min:1000',
                function ($attribute, $value, $fail) use ($cast_account_id, $id, $has_access_payment) {
                    // Tentukan status untuk cek saldo
                    $status = request()->status;
                    if (empty($status) && $id) {
                        $transaction = AccountTransaction::find($id);
                        $status = $transaction->status;
                    }

                    // Hanya cek saldo jika statusnya OUT
                    if ($status == AccountTransaction::OUT) {
                        if ($id) {
                            $balance = CustomHelper::total_balance_cast_account_edit($cast_account_id, $id, CastAccount::CASH);
                        } else {
                            $balance = CustomHelper::total_balance_cast_account($cast_account_id, CastAccount::CASH);
                        }
                        if ($value > $balance) {
                            $fail(trans("backpack::crud.cash_account.field_transfer.errors.nominal_transfer_to_more"));
                        }
                    }
                }
            ]
        ];

        // Hanya wajibkan status jika ada log_payment
        if ($has_log_payment) {
            $rules['status'] = 'required|in:enter,out';
        }

        return $rules;
    }

    function ruleValidationStoreTransactionAccountPrimary()
    {
        $cast_account_id = request()->cast_account_id;
        $id = request()->id;

        $has_log_payment = false;
        if ($id) {
            $has_log_payment = LogPayment::whereHasMorph('reference', AccountTransaction::class, function ($q) use ($id) {
                $q->where('id', $id);
            })->exists();
        }

        $rules = [
            'date_transaction' => 'required',
            'kdp' => 'max:50',
            'job_name' => 'max:100',
            'no_invoice' => 'max:100',
            'account_id' => 'required|exists:accounts,id',
            'nominal_transaction' => [
                'required',
                'numeric',
                'min:1000',
                function ($attribute, $value, $fail) use ($cast_account_id, $id) {
                    // Tentukan status untuk cek saldo
                    $status = request()->status;
                    if (empty($status) && $id) {
                        $transaction = AccountTransaction::find($id);
                        $status = $transaction->status;
                    }

                    // Hanya cek saldo jika statusnya OUT
                    if ($status == AccountTransaction::OUT) {
                        if ($id) {
                            $balance = CustomHelper::total_balance_cast_account_edit($cast_account_id, $id, CastAccount::CASH);
                        } else {
                            $balance = CustomHelper::total_balance_cast_account($cast_account_id, CastAccount::CASH);
                        }
                        if ($value > $balance) {
                            $fail(trans("backpack::crud.cash_account.field_transfer.errors.nominal_transfer_to_more"));
                        }
                    }
                }
            ]
        ];

        // Hanya wajibkan status jika ada log_payment
        if ($has_log_payment) {
            $rules['status'] = 'required|in:enter,out';
        }

        return $rules;
    }

    function account_select2()
    {
        $this->crud->hasAccessOrFail('create');

        $search = request()->input('q');
        $dataset = \App\Models\Account::select(['id', 'code', 'name'])
            ->where('level', ">", 2)
            ->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                    ->orWhere('code', 'LIKE', "%$search%");
            })
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

    function account_child_select2()
    {
        $this->crud->hasAccessOrFail('create');

        $search = request()->input('q');
        $dataset = \App\Models\Account::select(['id', 'code', 'name'])
            ->where('level', '>', 1)
            ->where(function ($q) use ($search) {
                $q->where('name', 'LIKE', "%$search%")
                    ->orWhere('code', 'LIKE', "%$search%");
            })
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

    public function select2Invoice()
    {
        $this->crud->hasAccessOrFail('create');

        $search = request()->input('q');
        $r_type = request()->type;
        $company_id = request()->input('company_id');

        $invoices = InvoiceClient::where('status', 'Unpaid');

        if (!empty($company_id)) {
            $invoices->where('company_id', $company_id);
        }

        $invoices->where(function ($q) use ($search) {
            $q->where('invoice_number', 'LIKE', "%$search%")
                ->orWhere('name', 'LIKE', "%$search%")
                ->orWhere('kdp', 'LIKE', "%$search%");
        });

        $dataset = $invoices->paginate(20);

        $results = [];
        foreach ($dataset as $item) {
            $type = ucfirst($item->type);
            $results[] = [
                'id' => $item->id,
                'text' => ($r_type == 'kdp') ? $item->kdp : $item->invoice_number,
            ];
        }
        return response()->json(['results' => $results]);
    }

    public function get_invoice_ajax()
    {
        // url : {{ url($crud->route) }}/get-invoice
        // method : GET
        $req = request();
        $id = $req->id;
        $invoice = InvoiceClient::leftJoin('client_po', 'client_po.id', 'invoice_clients.client_po_id')
            ->where('invoice_clients.id', $id)
            ->select(DB::raw('
            invoice_clients.id,
            invoice_clients.kdp,
            invoice_clients.invoice_number,
            client_po.job_name,
            invoice_clients.price_total_exclude_ppn,
            invoice_clients.price_total_include_ppn,
            invoice_clients.tax_ppn,
            invoice_clients.pph,
            invoice_clients.discount_pph,
            invoice_clients.withholding_agent
        '))
            ->first();
        return response()->json($invoice);
    }

    public function saveBankAjax()
    {
        $name = request()->input('name');
        if (empty($name)) {
            return response()->json(['success' => false, 'message' => 'Nama bank tidak boleh kosong']);
        }

        try {
            $bank = \App\Models\Bank::firstOrCreate(['name' => $name]);
            return response()->json([
                'success' => true,
                'data' => $bank,
                'message' => 'Bank berhasil disimpan'
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    function accessAccount($id_account)
    {
        $account = CastAccount::whereHas('informations', function ($q) {
            $q->where('name', 'Jadikan rekening utama');
        })->where('id', $id_account)->get();
        return $account->count();
    }

    function accessPaymentAccount($id_account)
    {
        $account = CastAccount::whereHas('informations', function ($q) {
            $q->where('name', 'Digunakan sebagai pembayaran langsung');
        })->where('id', $id_account)->get();
        return $account->count();
    }

    function createTransactionOperation($id = null)
    {

        $settings = Setting::first();

        CRUD::setModel(AccountTransaction::class);
        CRUD::setValidation($this->ruleValidationTransaction());

        $attribute_form = [];
        $invoice_disabled = [];

        $has_access_primary = $this->accessAccount($id);

        if (request()->has('type')) {
            // edit
            $transaction_id = request()->id;
            $transaction = AccountTransaction::find($transaction_id);

            $has_log_payment = LogPayment::whereHasMorph('reference', AccountTransaction::class, function ($q) use ($transaction_id) {
                $q->where('id', $transaction_id);
            })->exists();

            if ($has_log_payment) {
                $attribute_form = [];
                $invoice_disabled = [];
            } else {
                if ($has_access_primary > 0) {
                    $attribute_form = [
                        'disabled' => true,
                    ];
                }

                $invoice_disabled = [
                    'disabled' => true,
                ];
            }
        }


        CRUD::addField([
            'name' => 'cast_account_id',
            'type' => 'hidden',
            'value' => $id,
        ]);

        // Ambil data rekening untuk ditampilkan
        $cast_account = CastAccount::find($id);
        if ($cast_account) {
            CRUD::addField([
                'name' => 'account_info',
                'type' => 'custom_html',
                'value' => '
                    <div class="alert alert-info" style="margin-bottom: 20px;">
                        <h5 style="margin-top: 0; margin-bottom: 10px;">
                            <i class="la la-bank"></i> ' . trans('backpack::crud.cash_account.account_info.title') . '
                        </h5>
                        <div class="row">
                            <div class="col-md-4">
                                <strong>' . trans('backpack::crud.cash_account.account_info.account_name') . ':</strong><br>
                                ' . e($cast_account->name) . '
                            </div>
                            <div class="col-md-4">
                                <strong>' . trans('backpack::crud.cash_account.account_info.bank_name') . ':</strong><br>
                                ' . e($cast_account->bank_name) . '
                            </div>
                            <div class="col-md-4">
                                <strong>' . trans('backpack::crud.cash_account.account_info.account_number') . ':</strong><br>
                                ' . e($cast_account->no_account) . '
                            </div>
                        </div>
                    </div>
                ',
            ]);
        }

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
                ...$attribute_form,
            ]
        ]);

        CRUD::addField([
            'name' => 'withholding_agent_status',
            'label' => trans('backpack::crud.cash_account.field_transaction.withholding_agent_status.label'),
            'type' => 'text',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'readonly' => true,
            ]
        ]);

        CRUD::addField([
            'name' => 'tax_ppn_nominal',
            'label' => trans('backpack::crud.cash_account.field_transaction.tax_ppn_nominal.label'),
            'type' => 'text',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'readonly' => true,
            ]
        ]);

        CRUD::addField([
            'name' => 'pph_nominal',
            'label' => trans('backpack::crud.cash_account.field_transaction.pph_nominal.label'),
            'type' => 'text',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'readonly' => true,
            ]
        ]);

        CRUD::addField([
            'name' => 'total_nominal_transfer',
            'label' => trans('backpack::crud.cash_account.field_transaction.total_nominal_transfer.label'),
            'type' => 'text',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'readonly' => true,
            ]
        ]);

        CRUD::addField([
            'name'        => 'status',
            'label'       => trans('backpack::crud.cash_account.field_transaction.status.label'),
            'type'        => 'select_from_array',
            'options'     => [
                '' => trans('backpack::crud.cash_account.field_transaction.status.placeholder'),
                'enter' => trans('backpack::crud.cash_account.field_transaction.status.enter'),
                'out' => trans('backpack::crud.cash_account.field_transaction.status.out')
            ],
            'allows_null' => false,
            'default'     => '',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                ...$attribute_form,
            ]
        ]);

        CRUD::addField([
            'name' => 'description',
            'label' => trans('backpack::crud.cash_account.field_transaction.description.label'),
            'type' => 'textarea',
            'attributes' => [
                'placeholder' => trans('backpack::crud.cash_account.field_transaction.description.placeholder'),
            ]
        ]);

        // CRUD::addField([
        //     'name' => 'kdp',
        //     'label' => trans('backpack::crud.cash_account.field_transaction.kdp.label'),
        //     'type' => 'text',
        //     'wrapper' => [
        //         'class' => 'form-group col-md-6'
        //     ],
        //     'attributes' => [
        //         'placeholder' => trans('backpack::crud.cash_account.field_transaction.kdp.placeholder'),
        //     ]
        // ]);

        CRUD::addField([
            'label'       => trans('backpack::crud.cash_account.field_transaction.kdp.label'), // Table column heading
            'type'        => "select2_ajax_custom",
            'name'        => 'kdp',
            'model'       => 'App\Models\ClientPo',
            'attribute'   => "work_code",
            'data_source' => backpack_url('cash-flow/cast-accounts-select-to-invoice?type=kdp'),
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.work_code.placeholder'),
                ...$invoice_disabled,
            ],
            'dependencies' => ['company_id'],
            'include_all_form_fields' => true,
        ]);

        CRUD::addField([
            'name' => 'space_1',
            'type' => 'hidden',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'job_name',
            'label' => trans('backpack::crud.cash_account.field_transaction.job_name.label'),
            'type' => 'text',
            'attributes' => [
                'placeholder' => trans('backpack::crud.cash_account.field_transaction.job_name.placeholder'),
            ]
        ]);

        CRUD::addField([
            'label'       => trans('backpack::crud.cash_account.field_transaction.account_id.label'), // Table column heading
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
                'placeholder' => trans('backpack::crud.cash_account.field_transaction.account_id.placeholder'),
            ]
        ]);

        CRUD::addField([
            'label' => trans('backpack::crud.cash_account.field_transaction.no_invoice.label'),
            'type'        => "select2_ajax_custom",
            'name'        => 'no_invoice',
            'model'       => 'App\Models\ClientPo',
            'attribute'   => "work_code",
            'data_source' => backpack_url('cash-flow/cast-accounts-select-to-invoice?type=invoice'),
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.cash_account.field_transaction.no_invoice.placeholder'),
                ...$invoice_disabled,
            ],
            'dependencies' => ['company_id'],
            'include_all_form_fields' => true,
        ]);



        CRUD::addField([
            'name' => 'logic_account_transaction',
            'type' => 'logic-account-transaction',
        ]);

        if ($has_access_primary == 0) {
            // CRUD::addField([
            //     'name'        => 'status',
            //     'label'       => trans('backpack::crud.cash_account.field_transaction.status.label'),
            //     'type'        => 'select_from_array',
            //     'options'     => [
            //         '' => trans('backpack::crud.cash_account.field_transaction.status.placeholder'),
            //         'enter' => trans('backpack::crud.cash_account.field_transaction.status.enter'),
            //         'out' => trans('backpack::crud.cash_account.field_transaction.status.out')
            //     ],
            //     'allows_null' => false,
            //     'wrapper'   => [
            //         'class' => 'form-group col-md-6',
            //     ],
            //     'attributes' => [
            //         ...$attribute_form,
            //     ]
            //     // 'allows_multiple' => true, // OPTIONAL; needs you to cast this to array in your model;
            // ]);
        }
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

        $request = request();

        // CRUD::setValidation($this->ruleValidation());

        if ($request->has('type')) {

            if ($request->type == 'cast_account') {
                $id = $request->id ?? '';
                CRUD::setValidation([
                    'name' => 'required|max:100|unique:cast_accounts,name,' . $id,
                ]);
                CRUD::addField([
                    'name' => 'name',
                    'label' => trans('backpack::crud.cash_account.field.name.label'),
                    'type' => 'text',
                    'attributes' => [
                        'placeholder' => trans('backpack::crud.cash_account.field.name.placeholder'),
                    ]
                ]);
            } else if ($request->type == 'transaction') {
                $this->createTransactionOperation($request->_id);
                return;
            }
        } else if ($request->has('_id')) {
            $this->createTransactionOperation($request->_id);
        } else {
            CRUD::setValidation($this->ruleValidation());
            CRUD::addField([
                'name' => 'name',
                'label' => trans('backpack::crud.cash_account.field.name.label'),
                'type' => 'text',
                // 'wrapper'   => [
                //     'class' => 'form-group col-md-12',
                // ],
                'attributes' => [
                    'placeholder' => trans('backpack::crud.cash_account.field.name.placeholder'),
                ]
            ]);

            CRUD::field([  // Select2
                'label'     => trans('backpack::crud.cash_account.field.bank_name.label'),
                'type'      => 'select2_bank_tags',
                'name'      => 'bank_name',
                'options'   => ['' => trans('backpack::crud.cash_account.field.bank_name.placeholder'), ...CustomHelper::getBanks()],
                'wrapper' => [
                    'class' => 'form-group col-md-6'
                ]
            ]);

            CRUD::addField([
                'name' => 'no_account',
                'label' => trans('backpack::crud.cash_account.field.no_account.label'),
                'type' => 'text',
                'wrapper'   => [
                    'class' => 'form-group col-md-12',
                ],
                'attributes' => [
                    'placeholder' => trans('backpack::crud.cash_account.field.no_account.placeholder'),
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
                'name' => 'total_saldo',
                'label' => trans('backpack::crud.cash_account.field.total_saldo.label'),
                'type' => 'mask',
                'mask' => '000.000.000.000.000.000',
                'mask_options' => [
                    'reverse' => true
                ],
                'prefix' => 'Rp',
                'wrapper'   => [
                    'class' => 'form-group col-md-6',
                ],
                'attributes' => [
                    'placeholder' => '000.000',
                ]
            ]);

            CRUD::field([   // Checklist
                'label'     => trans('backpack::crud.cash_account.field.additional_information.label'),
                'type'      => 'checklist-custom',
                'name'      => 'informations',
                'entity'    => 'informations',
                'attribute' => 'name',
                // 'model'     => "app\Models\CastAccount",
                'pivot'     => true,
                'wrapper'   => [
                    'class' => 'form-group col-md-6',
                ],
                // 'show_select_all' => true, // default false
                // 'number_of_columns' => 3,
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
        $request = request();

        if ($request->has('type')) {
            if ($request->type == 'transaction') {
                CRUD::setModel(AccountTransaction::class);
            }
        }

        $id = $this->crud->getCurrentEntryId() ?? $id;

        $this->crud->registerFieldEvents();

        $this->data['entry'] = $this->crud->getEntryWithLocale($id);
        if ($this->data['entry']->reference_type == 'App\Models\InvoiceClient') {
            $invoice = InvoiceClient::where('id', $this->data['entry']->reference_id)
                ->first();
            $this->data['invoice'] = $invoice;
        }

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
        $dto = CastAccountSaveData::fromRequest($request);

        $this->crud->registerFieldEvents();

        if ($request->bank_name) {
            \App\Models\Bank::firstOrCreate(['name' => $request->bank_name]);
        }

        DB::beginTransaction();
        try {
            $item = $this->service->storeCastAccount($dto);
            $this->data['entry'] = $this->crud->entry = $item;

            $this->crud->setSaveAction();

            DB::commit();

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

    public function updateTransaction()
    {
        $request = request();
        CRUD::setModel(AccountTransaction::class);

        $old_item = AccountTransaction::findOrFail($request->id);

        // Determine status_account for validation and DTO
        if ($request->has('status') && in_array($request->status, ['enter', 'out'])) {
            $status_account = $request->status;
        } else {
            $has_access_primary = $this->accessAccount($old_item->cast_account_id);
            if (empty($old_item->reference_type)) {
                $status_account = $request->input('status', $old_item->status);
            } else {
                $status_account = AccountTransaction::OUT;
                $has_payment_access = $this->accessPaymentAccount($old_item->cast_account_id);
                if ($has_payment_access > 0 || $has_access_primary > 0) {
                    $status_account = AccountTransaction::ENTER;
                }
            }

            if ($request->has('type')) {
                if ($has_access_primary > 0) {
                    $request->validate($this->ruleValidationStoreTransactionAccountPrimary());
                } else {
                    $request->validate($this->ruleValidationEditTransactionAccountSecondary());
                }
            }
        }

        // Merge status back to request so DTO fromRequest can capture it
        $request->merge(['status' => $status_account]);
        $dto = TransactionSaveData::fromRequest($request);

        DB::beginTransaction();

        try {
            $item = $this->service->updateTransaction((int)$request->id, $dto);

            $event = [];
            $this->data['entry'] = $item;
            $event['cast_account_store_success'] = true;

            \Alert::success(trans('backpack::crud.update_success'))->flash();

            $this->crud->setSaveAction();

            DB::commit();
            return response()->json([
                'success' => true,
                'data' => $item,
                'events' => $event
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

    public function update()
    {
        $this->crud->hasAccessOrFail('update');

        if (request()->has('type')) {
            if (request()->type == 'transaction') {
                return $this->updateTransaction();
            }
        }

        CRUD::setValidation([
            'name' => 'required|max:100|unique:cast_accounts,name,' . request('id'),
        ]);

        $request = $this->crud->validateRequest();
        $dto = CastAccountSaveData::fromRequest($request);

        $this->crud->registerFieldEvents();

        if ($request->bank_name) {
            \App\Models\Bank::firstOrCreate(['name' => $request->bank_name]);
        }

        DB::beginTransaction();
        try {
            $event = [];
            $item = $this->service->updateCastAccount((int)$request->id, $dto);
            $event['cast_account_store_success'] = true;

            $this->data['entry'] = $item;

            \Alert::success(trans('backpack::crud.update_success'))->flash();

            $this->crud->setSaveAction();

            DB::commit();
            return response()->json([
                'success' => true,
                'data' => $item,
                'events' => $event
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

    public function storeTransaction()
    {
        $this->crud->hasAccessOrFail('create');
        $request = request();
        $request->validate($this->ruleValidationTransaction());
        
        // Determine status_account for DTO consistency
        if (!$request->has('status') || !in_array($request->status, ['enter', 'out'])) {
            $has_access_primary = $this->accessAccount($request->cast_account_id);
            if ($has_access_primary > 0) {
                $status_account = AccountTransaction::ENTER;
            } else {
                $has_payment_access = $this->accessPaymentAccount($request->cast_account_id);
                if ($has_payment_access > 0) {
                    $status_account = AccountTransaction::ENTER;
                } else {
                    $status_account = AccountTransaction::OUT;
                }
            }
            $request->merge(['status' => $status_account]);
        }

        $dto = TransactionSaveData::fromRequest($request);

        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {

            $storeTransaction = $this->service->storeTransaction($dto);
            $this->data['entry'] = $this->crud->entry = $storeTransaction;

            $this->crud->setSaveAction();

            DB::commit();
            if ($request->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $storeTransaction,
                    'events' => [
                        'card_cast_account' . $request->cast_account_id . '_create_success' => $storeTransaction,
                    ]
                ]);
            }

            return $this->crud->performSaveAction($storeTransaction->getKey());
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function showTransaction()
    {
        $id = request()->_id;
        $castAccount = CastAccount::where('id', $id)->first();

        if (!$castAccount) {
            return response()->json(['status' => false, 'message' => 'Account not found'], 404);
        }

        $total_balance = CustomHelper::total_balance_cast_account($id, CastAccount::CASH);
        $castAccount->total_saldo = $total_balance;
        $castAccount->total_saldo_str = CustomHelper::formatRupiahWithCurrency($total_balance);

        return response()->json([
            'status' => true,
            'result' => [
                'cast_account' => $castAccount
            ]
        ]);
    }

    public function storeMoveTransfer(\App\Http\Requests\CastAccount\TransferBalanceRequest $request)
    {
        $this->crud->hasAccessOrFail('create');

        $dto = TransferBalanceData::fromRequest($request);

        $this->crud->registerFieldEvents();
        DB::beginTransaction();

        try {
            $item = $this->service->transferBalance($dto);
            $this->data['entry'] = $this->crud->entry = $item;

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            $this->crud->setSaveAction();

            if ($request->ajax()) {
                $events = [
                    'cast_account_store_success' => true,
                    'card_cast_account' . $dto->cast_account_id . '_create_success' => $this->repository->findById($dto->cast_account_id),
                ];

                if ($item->cast_account_destination_id) {
                    $otherAccount = CastAccount::find($item->cast_account_destination_id);
                    $events['card_cast_account' . $item->cast_account_destination_id . '_create_success'] = $otherAccount;
                }

                DB::commit();
                return response()->json([
                    'success' => true,
                    'events' => $events
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
        DB::beginTransaction();
        try{

            $id = $this->crud->getCurrentEntryId() ?? $id;
            $result = $this->service->deleteCastAccount((int)$id);

            DB::commit();
            $event = [];
            $event['cast_account_store_success'] = true;

            $messages['success'][] = trans('backpack::crud.delete_confirmation_message');
            $messages['events'] = $event;
            return response()->json($messages);
        }catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'type' => 'errors',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroyTransaction($id)
    {
        $this->crud->hasAccessOrFail('delete');
        DB::beginTransaction();
        try {
            $this->service->deleteTransaction((int)$id);

            $event = [];
            $event['cast_account_store_success'] = true;

            $messages['success'][] = trans('backpack::crud.delete_confirmation_message');
            $messages['events'] = $event;

            DB::commit();
            return response()->json($messages);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'type' => 'errors',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function destroyTransactionVoid($id)
    {
        $this->crud->hasAccessOrFail('delete');
        DB::beginTransaction();
        try {
            $event = [];

            CustomVoid::rollbackPayment(AccountTransaction::class, $id);

            $event['cast_account_store_success'] = true;

            $messages['success'][] = trans('backpack::crud.delete_confirmation_message');
            $messages['events'] = $event;

            DB::commit();
            return response()->json($messages);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'type' => 'errors',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    public function getTransactionDataTable()
    {
        $id = request()->_id;
        $castAccount = $this->repository->findById((int)$id);
        if (!$castAccount) {
            return response()->json(['data' => []]);
        }

        $query = $this->repository->getTransactionQuery((int)$id, request()->filter_year);

        // Access Check
        $has_access_primary = $this->accessAccount($id);

        // DataTables logic manually (or using yajra if available, but here we do simple manual processing from request)
        $total_data = $query->count();
        $filtered_data = $query; // In real app, apply search filters here

        // General Search logic
        if ($search = request()->input('search.value')) {
            $query->where(function ($q) use ($search) {
                $q->where('account_transactions.description', 'LIKE', "%$search%")
                    ->orWhere('account_transactions.nominal_transaction', 'LIKE', "%$search%")
                    ->orWhere('account_transactions.kdp', 'LIKE', "%$search%")
                    ->orWhere('account_transactions.job_name', 'LIKE', "%$search%")
                    ->orWhere('account_transactions.no_invoice', 'LIKE', "%$search%");
            });
        }

        // Column Search logic
        $columnsReq = request()->input('columns', []);
        foreach ($columnsReq as $col) {
            if (!empty($col['search']['value'])) {
                $searchValue = $col['search']['value'];
                $colName = $col['name'];

                if ($colName == 'date_transaction') {
                    $query->where('account_transactions.date_transaction', 'LIKE', "%{$searchValue}%");
                } elseif ($colName == 'nominal_transaction') {
                    $query->where('account_transactions.nominal_transaction', 'LIKE', "%{$searchValue}%");
                } elseif ($colName == 'description') {
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('account_transactions.description', 'LIKE', "%{$searchValue}%")
                            ->orWhere('vouchers.payment_description', 'LIKE', "%{$searchValue}%");
                    });
                } elseif ($colName == 'kdp') {
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('account_transactions.kdp', 'LIKE', "%{$searchValue}%")
                            ->orWhere('invoice_clients.kdp', 'LIKE', "%{$searchValue}%")
                            ->orWhere('vouchers.work_code', 'LIKE', "%{$searchValue}%");
                    });
                } elseif ($colName == 'job_name') {
                    $query->where('account_transactions.job_name', 'LIKE', "%{$searchValue}%");
                } elseif ($colName == 'account_id') {
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('voucher_account.code', 'LIKE', "%{$searchValue}%")
                            ->orWhere('voucher_account.name', 'LIKE', "%{$searchValue}%")
                            ->orWhere('trans_account.code', 'LIKE', "%{$searchValue}%")
                            ->orWhere('trans_account.name', 'LIKE', "%{$searchValue}%");
                    });
                } elseif ($colName == 'no_invoice') {
                    $query->where(function ($q) use ($searchValue) {
                        $q->where('account_transactions.no_invoice', 'LIKE', "%{$searchValue}%")
                            ->orWhere('invoice_clients.invoice_number', 'LIKE', "%{$searchValue}%")
                            ->orWhere('voucher_invoice.invoice_number', 'LIKE', "%{$searchValue}%");
                    });
                } elseif ($colName == 'status') {
                    $query->where('account_transactions.status', 'LIKE', "%{$searchValue}%");
                }
            }
        }

        $total_filtered = $query->count();

        // Order logic
        $columns = ['date_transaction', 'nominal_transaction', 'description', 'kdp', 'job_name', 'account_id', 'no_invoice', 'status'];
        if ($order = request()->input('order.0')) {
            $query->orderBy($columns[$order['column']], $order['dir']);
        } else {
            $query->orderBy('account_transactions.date_transaction', 'ASC')
                ->orderBy('account_transactions.id', 'ASC');
        }

        // Pagination
        $start = request()->input('start', 0);
        $length = request()->input('length', 10);
        $detail = $query->offset($start)->limit($length)->get();

        $data = [];
        foreach ($detail as $entry) {
            $date_str = Carbon::parse($entry->date_transaction)->translatedFormat('d/m/Y');
            $nominal_str = CustomHelper::formatRupiahWithCurrency($entry->nominal_transaction);

            // Logic Tombol Aksi (Pindahan dari Blade lama)
            $btn = '';
            // $isRegularTransaction = !$entry->reference_type;
            if ($entry->log_payment_id) {
                $url_edit = url($this->crud->route . '/' . $entry->id . '/edit?type=transaction&_id=' . $entry->cast_account_id);
                if ($entry->cast_account_destination_id == null) {
                    $url_update = url($this->crud->route) . '/' . $entry->id . '?type=transaction&_id=' . $entry->cast_account_id;
                    $btn .= '<a href="javascript:void(0)" onclick="editEntry(this)" data-route="' . $url_edit . '" data-route-action="' . $url_update . '" data-title-edit="Ubah Data Transaksi" class="btn btn-sm btn-primary me-1"><i class="la la-pen"></i></a>';
                }
                $url_delete = url($this->crud->route . "/delete-transaction-void/" . $entry->id);
                $btn .= '<a href="javascript:void(0)" onclick="deleteEntry(this)" data-route="' . $url_delete . '" class="btn btn-sm btn-danger" data-title-delete="Hapus Item Transaksi" data-body="Apakah anda yakin ingin menghapus data item transaksi ini ?"><i class="la la-trash"></i></a>';
            }

            // if ($isRegularTransaction) {
            //     if ($has_access_primary) {
            //         if (!$entry->cast_account_destination_id) {
            //             $url_edit = url($this->crud->route . '/' . $entry->id . '/edit?type=transaction&_id=' . $entry->cast_account_id);
            //             $url_update = url($this->crud->route) . '/' . $entry->id . '?type=transaction&_id=' . $entry->cast_account_id;
            //             $btn .= '<a href="javascript:void(0)" onclick="editEntry(this)" data-route="' . $url_edit . '" data-route-action="' . $url_update . '" data-title-edit="Ubah Data Transaksi" class="btn btn-sm btn-primary me-1"><i class="la la-pen"></i></a>';
            //         }
            //         if ($entry->log_payment_id) {
            //             $url_delete = url($this->crud->route . "/delete-transaction-void/" . $entry->id);
            //             $btn .= '<a href="javascript:void(0)" onclick="deleteEntry(this)" data-route="' . $url_delete . '" class="btn btn-sm btn-danger" data-title-delete="Hapus Item Transaksi" data-body="Apakah anda yakin ingin menghapus data item transaksi ini ?"><i class="la la-trash"></i></a>';
            //         }
            //     } else {
            //         if (!$entry->cast_account_destination_id) {
            //             if (!$entry->kdp) {
            //                 $url_edit = url($this->crud->route . '/' . $entry->id . '/edit?type=transaction&_id=' . $entry->cast_account_id);
            //                 $url_update = url($this->crud->route) . '/' . $entry->id . '?type=transaction&_id=' . $entry->cast_account_id;
            //                 $btn .= '<a href="javascript:void(0)" onclick="editEntry(this)" data-route="' . $url_edit . '" data-route-action="' . $url_update . '" data-title-edit="Ubah Data Transaksi" class="btn btn-sm btn-primary me-1"><i class="la la-pen"></i></a>';

            //                 $url_delete = url($this->crud->route . "/delete-transaction/" . $entry->id);
            //                 $btn .= '<a href="javascript:void(0)" onclick="deleteEntry(this)" data-route="' . $url_delete . '" class="btn btn-sm btn-danger" data-title-delete="Hapus Item Transaksi" data-body="Apakah anda yakin ingin menghapus data item transaksi ini ?"><i class="la la-trash"></i></a>';
            //             } else if ($entry->log_payment_id) {
            //                 $url_edit = url($this->crud->route . '/' . $entry->id . '/edit?type=transaction&_id=' . $entry->cast_account_id);
            //                 $url_update = url($this->crud->route) . '/' . $entry->id . '?type=transaction&_id=' . $entry->cast_account_id;
            //                 $btn .= '<a href="javascript:void(0)" onclick="editEntry(this)" data-route="' . $url_edit . '" data-route-action="' . $url_update . '" data-title-edit="Ubah Data Transaksi" class="btn btn-sm btn-primary me-1"><i class="la la-pen"></i></a>';
            //             }
            //         }
            //     }
            // }

            $data[] = [
                'date_transaction_str' => $date_str,
                'nominal_transaction_str' => $nominal_str,
                'description_str' => $entry->voucher_description ?: ($entry->description ?? '-'),
                'kdp_str' => $entry->invoice_kdp ?: ($entry->voucher_kdp ?: ($entry->kdp ?? '-')),
                'job_name_str' => $entry->job_name ?? '-',
                'account_id_str' => ($entry->voucher_account_code) ? $entry->voucher_account_code . ' - ' . $entry->voucher_account_name : (($entry->trans_account_code) ? $entry->trans_account_code . ' - ' . $entry->trans_account_name : '-'),
                'no_invoice_str' => $entry->invoice_number ?: ($entry->indirect_invoice_number ?? '-'),
                'status_str' => ucfirst(strtolower(trans('backpack::crud.cash_account.field_transaction.status.' . $entry->status))),
                'action_buttons' => $btn,
            ];
        }

        $filter_year = request()->input('filter_year');
        $total_balance = CustomHelper::total_balance_cast_account($id, CastAccount::CASH, $filter_year);
        $castAccount->total_saldo_str = CustomHelper::formatRupiahWithCurrency($total_balance);

        return response()->json([
            'draw' => intval(request()->input('draw')),
            'recordsTotal' => $total_data,
            'recordsFiltered' => $total_filtered,
            'data' => $data,
            'header' => [
                'name' => $castAccount->name,
                'bank_name' => $castAccount->bank_name,
                'no_account' => $castAccount->no_account,
                'total_saldo_str' => $castAccount->total_saldo_str
            ]
        ]);
    }

    public function getSelectToAccount()
    {
        $castAccounts = CastAccount::whereHas('informations', function ($q) {
            $q->where("additional_informations.id", 2);
        })->where('status', CastAccount::CASH)->get(['id', 'name']);

        $usedAccountIds = CastAccount::whereNotNull('account_id')->pluck('account_id')->toArray();

        $accounts = \App\Models\Account::where('level', '>', 2)
            ->whereNotIn('id', $usedAccountIds)
            ->get(['id', 'name', 'code']);

        $result = [];
        foreach ($castAccounts as $item) {
            $result[] = [
                'id' => $item->id,
                'name' => "[Rekening Kas] " . $item->name,
            ];
        }

        foreach ($accounts as $item) {
            $result[] = [
                'id' => "acc_" . $item->id,
                'name' => "[Account] " . $item->code . " - " . $item->name,
            ];
        }

        return response()->json([
            'status' => true,
            'result' => $result,
        ]);
    }
}
