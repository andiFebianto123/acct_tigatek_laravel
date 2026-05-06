<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Spk;
use App\Models\User;
use App\Models\Subkon;
use App\Models\Account;
use App\Models\Setting;
use App\Models\Voucher;
use App\Models\Approval;
use App\Models\ClientPo;
use App\Models\CastAccount;
use App\Models\VoucherEdit;
use App\Models\InvoiceClient;
use App\Models\LogPayment;
use App\Models\PurchaseOrder;
use Mpdf\Mpdf;
use App\Http\Helpers\CustomVoid;
use App\Http\Exports\ExportExcel;
use App\Http\Helpers\CustomHelper;
use App\Models\AccountTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\FormaterExport;
use App\Http\Controllers\Operation\PermissionAccess;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Prologue\Alerts\Facades\Alert;
use App\DTOs\Fa\VoucherFilterData;
use App\DTOs\Fa\VoucherData;
use App\Repositories\Fa\VoucherRepository;
use App\Services\Fa\VoucherService;
use App\Http\Requests\VoucherRequest;

class VoucherCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;
    use FormaterExport;

    protected $voucherRepository;
    protected $voucherService;

    public function __construct(
        VoucherRepository $voucherRepository,
        VoucherService $voucherService
    ) {
        parent::__construct();
        $this->voucherRepository = $voucherRepository;
        $this->voucherService = $voucherService;
    }

    public function setup()
    {
        CRUD::setModel(Voucher::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/fa/voucher');
        CRUD::setEntityNameStrings('Voucher', 'Voucher');


        $viewMenu = [
            'MENU INDEX FA VOUCHER'
        ];

        $this->settingPermission([
            'approve' => ["APPROVE VOUCHER", "APPROVE EDIT VOUCHER"],
            'create' => [
                "CREATE INDEX FA VOUCHER",
            ],
            'update' => ["UPDATE INDEX FA VOUCHER"],
            'delete' => ["DELETE INDEX FA VOUCHER"],
            'void' => ["VOID INDEX FA VOUCHER"],
            'list' => $viewMenu,
            'show' => $viewMenu,
            'print' => true,
        ]);
    }

    private function getComponent()
    {
        $this->crud->addFilter([
            'name' => 'date_voucher11crudTable-voucher',
            'type' => 'date',
            'label' => trans('backpack::crud.voucher.column.voucher.date_voucher.label'),
        ], false, function ($value) {});

        $this->crud->addFilter([
            'name' => 'bill_date11crudTable-voucher',
            'type' => 'date',
            'label' => trans('backpack::crud.voucher.column.voucher.bill_date.label'),
        ], false, function ($value) {});

        $this->crud->addFilter([
            'name' => 'payment_date11crudTable-voucher',
            'type' => 'date',
            'label' => trans('backpack::crud.voucher.column.voucher.due_date.label'),
        ], false, function ($value) {});
    }


    function total_voucher()
    {
        $request = request();
        $filters = VoucherFilterData::fromRequest($request);
        $summary = $this->voucherRepository->getSummaryValues($filters);

        return response()->json([
            'total_exclude_ppn' => CustomHelper::formatRupiahWithCurrency($summary['total_exclude_ppn']),
            'total_include_ppn' => CustomHelper::formatRupiahWithCurrency($summary['total_include_ppn']),
            'total_nilai_transfer' => CustomHelper::formatRupiahWithCurrency($summary['total_nilai_transfer']),
        ]);
    }


    function index()
    {
        $this->crud->hasAccessOrFail('list');
        $this->getComponent();

        $this->card->addCard([
            'name' => 'voucher',
            'line' => 'top',
            'view' => 'crud::components.card-tab',
            'params' => [
                'tabs' => [
                    [
                        'name' => 'voucher',
                        'label' => trans('backpack::crud.voucher.tab.title_voucher'),
                        // 'class' => '',
                        'active' => true,
                        'view' => 'crud::components.datatable',
                        'params' => [
                            'filter' => true,
                            'crud_custom' => $this->crud,
                            'columns' => [
                                [
                                    'name'      => 'row_number',
                                    'type'      => 'row_number',
                                    'label'     => 'No',
                                    'orderable' => false,
                                ],
                                [
                                    'name' => 'action_custom',
                                    'type' => 'text',
                                    'label' =>  trans('backpack::crud.actions'),
                                    'searchable' => false,
                                    'orderable' => false,
                                ],
                                backpack_user()->hasRole('Super Admin') ? [
                                    'label' => trans('backpack::crud.subkon.column.company'),
                                    'type'      => 'text',
                                    'name'      => 'company_name',
                                    'orderable' => true,
                                ] : [],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.no_voucher.label'),
                                    'type'      => 'text',
                                    'name'      => 'no_voucher',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.date_voucher.label'),
                                    'type' => 'text',
                                    'name' => 'date_voucher',
                                    'orderable' => true,
                                    'searchable' => false,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.bussines_entity_name.label'),
                                    'type' => 'text',
                                    'name' => 'bussines_entity_name',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.bill_number.label'),
                                    'type' => 'text',
                                    'name' => 'bill_number',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.bill_date.label'),
                                    'type' => 'text',
                                    'name' => 'bill_date',
                                    'orderable' => true,
                                    'searchable' => false,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.payment_description.label'),
                                    'type' => 'text',
                                    'name' => 'payment_description',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.bill_value.label'),
                                    'type' => 'text',
                                    'name' => 'bill_value',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.field.total_price_ppn.label'),
                                    'type' => 'text',
                                    'name' => 'total_price_ppn',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.total.label'),
                                    'type' => 'text',
                                    'name' => 'total',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.payment_transfer.label'),
                                    'type' => 'text',
                                    'name' => 'payment_transfer',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.field.no_factur.label'),
                                    'type' => 'text',
                                    'name' => 'no_factur',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.factur_status.label'),
                                    'type' => 'text',
                                    'name' => 'factur_status',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.bussines_entity_code.label'),
                                    'type' => 'text',
                                    'name' => 'bussines_entity_code',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.job_name.label'),
                                    'type' => 'text',
                                    'name' => 'job_name',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.account_id.label'),
                                    'type' => 'text',
                                    'name' => 'account_id',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.no_account.label'),
                                    'type' => 'text',
                                    'name' => 'no_account',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.payment_type.label'),
                                    'type' => 'text',
                                    'name' => 'payment_type',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.status.label'),
                                    'type' => 'text',
                                    'name' => 'status',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.user_approval.label'),
                                    'type' => 'text',
                                    'name' => 'user_approval',
                                    'orderable' => false,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.payment_status.label'),
                                    'type' => 'text',
                                    'name' => 'payment_status',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.due_date.label'),
                                    'type' => 'text',
                                    'name' => 'due_date',
                                    'orderable' => true,
                                    'searchable' => false,
                                ],
                                // [
                                //     'name' => 'action',
                                //     'type' => 'action',
                                //     'label' =>  trans('backpack::crud.actions'),
                                // ]
                            ],
                            'route' => backpack_url('/fa/voucher/search?tab=voucher'),
                            'route_export_pdf' => url($this->crud->route . '/export-pdf?tab=voucher'),
                            'title_export_pdf' => 'Voucher.pdf',
                            'route_export_excel' => url($this->crud->route . '/export-excel?tab=voucher'),
                            'title_export_excel' => 'Voucher.xlsx',
                            'filter_table' => $this->crud->filters(),
                        ],
                    ],
                    [
                        'name' => 'history_edit_voucher',
                        'label' => trans('backpack::crud.voucher.tab.title_voucher_edit'),
                        'view' => 'crud::components.datatable',
                        'params' => [
                            'crud_custom' => $this->crud,
                            'columns' => [
                                [
                                    'name'      => 'row_number',
                                    'type'      => 'row_number',
                                    'label'     => 'No',
                                    'orderable' => false,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher.no_voucher.label'),
                                    'type'      => 'text',
                                    'name'      => 'no_voucher',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher_edit.user_id.label'),
                                    'type'      => 'text',
                                    'name'      => 'user_id',
                                    'orderable' => false,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher_edit.date_update.label'),
                                    'type'      => 'text',
                                    'name'      => 'date_update',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher_edit.history_update.label'),
                                    'type'      => 'text',
                                    'name'      => 'history_update',
                                    'orderable' => false,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher_edit.no_apprv.label'),
                                    'type'      => 'text',
                                    'name'      => 'no_apprv',
                                    'orderable' => false,
                                ],
                                [
                                    'label' => trans('backpack::crud.voucher.column.voucher_edit.status.label'),
                                    'type'      => 'text',
                                    'name'      => 'status',
                                    'orderable' => false,
                                ],
                            ],
                            'route' => backpack_url('/fa/voucher/search?tab=voucher_edit'),
                            'route_export_pdf' => url($this->crud->route . '/export-pdf?tab=voucher_edit'),
                            'title_export_pdf' => 'Voucher-edit.pdf',
                            'route_export_excel' => url($this->crud->route . '/export-excel?tab=voucher_edit'),
                            'title_export_excel' => 'Voucher-edit.xlsx',
                        ]
                    ]
                ]
            ]
        ]);

        $this->card->addCard([
            'name' => 'voucher-plugin',
            'line' => 'top',
            'view' => 'crud::components.voucher-plugin',
            'parent_view' => 'crud::components.filter-parent',
            'params' => [],
        ]);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = "Voucher";
        $this->data['title_modal_edit'] = "Voucher";
        $this->data['title_modal_delete'] = "Voucher";
        $this->data['cards'] = $this->card;

        $breadcrumbs = [
            'FA' => backpack_url('fa'),
            'Voucher' => backpack_url($this->crud->route)
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;
        $this->data['year_options'] = CustomHelper::getYearOptions('vouchers', 'date_voucher');

        // $list = "crud::list-custom" ?? $this->crud->getListView();
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

    protected function setupListOperation()
    {
        $settings = Setting::first();
        $type = request()->tab;
        CRUD::addButtonFromView('top', 'export-excel', 'export-excel', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf', 'export-pdf', 'beginning');
        CRUD::addButtonFromView('top', 'filter_year', 'filter-year', 'beginning');
        $request = request();
        $new_format_date = 'DD/MM/YYYY';
        if ($type == 'voucher') {
            CRUD::setModel(Voucher::class);
            CRUD::disableResponsiveTable();

            CRUD::removeButtons(['delete', 'show', 'update'], 'line');
            CRUD::addButtonFromView('line_start', 'show', 'show', 'end');
            CRUD::addButtonFromView('line_start', 'update', 'update', 'end');
            CRUD::addButtonFromView('line_start', 'print', 'print', 'end');
            CRUD::addButtonFromView('line_start', 'delete', 'delete', 'end');
            CRUD::addButtonFromView('line_start', 'approve_button', 'approve_button', 'end');
            // CRUD::addButtonFromView('line_start', 'void_voucher', 'void_voucher', 'end');


            $filters = VoucherFilterData::fromRequest($request);
            $this->crud->query = $this->voucherRepository->getFilteredDataQuery($filters);



            CRUD::addColumn([
                'name'      => 'row_number',
                'type'      => 'row_number',
                'label'     => 'No',
                'orderable' => false,
                'wrapper' => [
                    'element' => 'strong',
                ]
            ])->makeFirstColumn();

            CRUD::addColumn([
                'name' => 'action',
                'type' => 'closure',
                'label' =>  '',
                'escaped' => false,
                'width_box' => "220px",
                'function' => function ($entry, $rowNumber) {
                    $crud = $this->crud;
                    return \View::make('crud::inc.button_stack', ['stack' => 'line_start'])
                        ->with('crud', $crud)
                        ->with('entry', $entry)
                        ->with('row_number', $rowNumber)
                        ->render();
                }
            ]);

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
                'label'  => '',
                'name' => 'no_voucher',
                'type'  => 'wrap_text',
                'searchLogic' => function ($query, $column, $searchTerm) {
                    // $query->orWhereHas('client_po', function ($q) use ($column, $searchTerm) {
                    //     $q->where('po_number', 'like', '%'.$searchTerm.'%');
                    // });
                }
            ]);
            CRUD::column([
                'label'  => '',
                'name' => 'date_voucher',
                'type'  => 'date',
                'format' => $new_format_date,
            ]);

            CRUD::column(
                [
                    'label'  => '',
                    'name' => 'subkon_id',
                    'type'  => 'closure',
                    'function' => function ($entry) {
                        return $entry?->subkon?->name;
                    }
                ], // BELUM FILTER
            );
            CRUD::column(
                [
                    'label'  => '',
                    'name' => 'bill_number',
                    'type'  => 'text'
                ],
            );
            CRUD::column([
                'label'  => '',
                'name' => 'bill_date',
                'type'  => 'date',
                'format' => $new_format_date,
            ]);
            CRUD::column(
                [
                    'label'  => '',
                    'name' => 'payment_description',
                    'type'  => 'wrap_text',
                ],
            );
            CRUD::column([
                'label'  => '',
                'name' => 'bill_value',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ]);
            CRUD::column([
                'label'  => '',
                'name' => 'total_price_ppn',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ]);
            CRUD::column([
                'label'  => '',
                'name' => 'total',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ]);
            CRUD::column([
                'label'  => '',
                'name' => 'payment_transfer',
                'type'  => 'number',
                'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ]);
            CRUD::column(
                [
                    'label'  => '',
                    'name' => 'no_factur',
                    'type'  => 'text'
                ],
            );
            CRUD::column(
                [
                    'label'  => '',
                    'name' => 'factur_status',
                    'type'  => 'text'
                ],
            );
            CRUD::column(
                [
                    'label'  => '',
                    'name' => 'client_po_id',
                    'type'  => 'closure',
                    'function' => function ($entry) {
                        return $entry?->client_po?->work_code;
                    }
                ], // BELUM FILTER
            );
            CRUD::column(
                [
                    'label'  => '',
                    'name' => 'job_name',
                    'type'  => 'wrap_text',
                ],
            );
            CRUD::column(
                [
                    'label'  => '',
                    'name' => 'account_id',
                    'type'  => 'closure',
                    'function' => function ($entry) {
                        return $entry->account->code . " - " . $entry->account->name;
                    }
                ],
            );
            CRUD::column(
                [
                    'label'  => '',
                    'name' => 'account_source_id',
                    'type'  => 'closure',
                    'function' => function ($entry) {
                        return $entry?->cast_account_name;
                    }
                ], // BELUM FILTER
            );
            CRUD::column(
                [
                    'label'  => '',
                    'name' => 'payment_type',
                    'type'  => 'text'
                ],
            );
            CRUD::column(
                [
                    'label'  => '',
                    'name' => 'status',
                    'type'  => 'approval-voucher',
                ],
            );
            CRUD::addColumn([
                'name'     => 'user_approval',
                'label'    => trans('backpack::crud.voucher.column.voucher.user_approval.label'),
                'type'     => 'custom_html',
                'value' => function ($entry) {
                    $approvals = Approval::where('model_type', VoucherEdit::class)
                        ->where('model_id', $entry->voucer_edit_id)
                        ->orderBy('no_apprv', 'ASC')
                        ->get();
                    return "<ul>" . $approvals->map(function ($item, $key) {
                        if ($item->status == Approval::APPROVED) {
                            return "<li class='text-success'>" . $item->user->name . "</li>";
                        }
                        return "<li>" . $item->user->name . "</li>";
                    })->implode('') . "</ul>";
                },
                'searchLogic' => function ($query, $column, $searchTerm) {
                    // $query->orWhereHas('purchase_orders', function ($q) use ($column, $searchTerm) {
                    //     $q->where('po_number', 'like', '%'.$searchTerm.'%');
                    // });
                }
            ]);
            CRUD::column(
                [
                    'label'  => '',
                    'name' => 'payment_status',
                    'type'  => 'text'
                ],
            );
            CRUD::column([
                'label'  => '',
                'name' => 'payment_date',
                'type'  => 'date',
                'format' => $new_format_date,
            ]);
        } else if ($type == 'voucher_edit') {
            CRUD::setModel(VoucherEdit::class);
            CRUD::disableResponsiveTable();

            $a_p = DB::table('approvals')
                ->select(DB::raw('MAX(id) as id'), 'model_type', 'model_id')
                ->groupBy('model_type', 'model_id');

            $this->crud->query = $this->crud->query
                ->leftJoinSub($a_p, 'a_p', function ($join) {
                    $join->on('a_p.model_id', '=', 'voucher_edit.id')
                        ->where('a_p.model_type', '=', DB::raw('"App\\\\Models\\\\VoucherEdit"'));
                })
                ->leftJoin('approvals', 'approvals.id', '=', 'a_p.id')
                ->leftJoin('vouchers', 'vouchers.id', '=', 'voucher_edit.voucher_id');

            if ($request->has('filter_year') && $request->filter_year != 'all') {
                $this->crud->query = $this->crud->query->whereYear('vouchers.date_voucher', $request->filter_year);
            }

            CRUD::addClause('select', [
                DB::raw("
                    voucher_edit.*,
                    vouchers.no_voucher,
                    approvals.no_apprv as approval_no_apprv,
                    approvals.status as approval_status,
                    'voucher_edit' as type
                ")
            ]);

            CRUD::addColumn([
                'name'      => 'row_number',
                'type'      => 'row_number',
                'label'     => 'No',
                'orderable' => false,
                'wrapper' => [
                    'element' => 'strong',
                ]
            ])->makeFirstColumn();

            CRUD::column([
                'label'  => '',
                'name' => 'no_voucher',
                'type'  => 'text',
            ]);

            CRUD::column([
                'label'  => '',
                'name' => 'user_id',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry->user->name;
                }
            ]);

            CRUD::column([
                'label'  => '',
                'name' => 'date_update',
                'type'  => 'date',
                'format' => $new_format_date . ' HH:mm'
            ]);

            CRUD::column([
                'label'  => '',
                'name' => 'history_update',
                'type'  => 'wrap_text',
            ]);

            CRUD::column([
                'label'  => '',
                'name' => 'no_apprv',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return 'Final - Approver User';
                }
            ]);

            CRUD::column(
                [
                    'label'  => '',
                    'name' => 'status',
                    'type'  => 'approval-voucher',
                ],
            );
        }
    }

    private function setupListExport($tab)
    {
        $settings = Setting::first();
        $request = request();
        $new_format_date = 'DD/MM/YYYY';
        if ($tab == 'voucher') {
            $status_file = '';
            if (strpos(url()->current(), 'excel')) {
                $status_file = 'excel';
            } else if (strpos(url()->current(), 'pdf')) {
                $status_file = 'pdf';
            }
            $wrap_length = [];

            if ($status_file == 'excel' || $status_file == 'pdf') {
                $wrap_length = [
                    'width_box' => '100%',
                ];
            }
            CRUD::setModel(Voucher::class);

            $filters = VoucherFilterData::fromRequest($request);
            $this->crud->query = $this->voucherRepository->getFilteredDataQuery($filters);

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
                'name' => 'no_payment',
                'label' => trans('backpack::crud.voucher.field.no_payment.label'),
                'type' => 'text',
            ]);

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.column.voucher.account_id.label'),
                    'name' => 'account_id',
                    'type'  => 'closure',
                    'function' => function ($entry) {
                        return $entry->account->code . " - " . $entry->account->name;
                    }
                ],
            );

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.column.voucher.bussines_entity_code.label'),
                    'name' => 'client_po_id',
                    'type'  => 'closure',
                    'function' => function ($entry) {
                        return $entry?->client_po?->work_code;
                    }
                ], // BELUM FILTER
            );

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.column.voucher.no_account.label'),
                    'name' => 'account_source_id',
                    'type'  => 'closure',
                    'function' => function ($entry) {
                        return $entry?->cast_account_name;
                    }
                ], // BELUM FILTER
            );

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.column.voucher.job_name.label'),
                    'name' => 'job_name',
                    'type'  => 'wrap_text',
                    ...$wrap_length
                ],
            );

            CRUD::column([
                'label' => trans('backpack::crud.voucher.field.no_voucher.label'),
                'name' => 'no_voucher',
                'type'  => 'text',
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.voucher.field.date_voucher.label'),
                'name' => 'date_voucher',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry->date_voucher ? Carbon::parse($entry->date_voucher)->format('d/m/Y') : '-';
                }
            ]);

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.column.voucher.bussines_entity_name.label'),
                    'name' => 'subkon_id',
                    'type'  => 'closure',
                    'function' => function ($entry) {
                        return $entry?->subkon?->name;
                    }
                ], // BELUM FILTER
            );

            CRUD::column([
                'label' => trans('backpack::crud.voucher.field.account_holder_name.label'),
                'name' => 'account_holder_name',
                'type'  => 'wrap_text',
            ]);

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.column.voucher.bill_number.label'),
                    'name' => 'bill_number',
                    'type'  => 'closure',
                    'function' => function ($entry) {
                        return str_replace('.00', '', $entry->bill_number);
                    },
                ],
            );
            CRUD::column([
                'label' => trans('backpack::crud.voucher.column.voucher.bill_date.label'),
                'name' => 'bill_date',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry->bill_date ? Carbon::parse($entry->bill_date)->format('d/m/Y') : '-';
                }
            ]);
            CRUD::column([
                'label' => trans('backpack::crud.voucher.field.date_receipt_bill.label'),
                'name' => 'date_receipt_bill',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry->date_receipt_bill ? Carbon::parse($entry->date_receipt_bill)->format('d/m/Y') : '-';
                }
            ]);
            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.column.voucher.payment_description.label'),
                    'name' => 'payment_description',
                    'type'  => 'wrap_text',
                    ...$wrap_length
                ],
            );

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.field.no_po_spk.label'),
                    'name' => 'reference_id',
                    'type'  => 'closure',
                    'function' => function ($entry) {
                        if ($entry->reference_type == Spk::class) {
                            return $entry?->reference?->no_spk;
                        }
                        return $entry?->reference?->po_number;
                    }
                ], // BELUM FILTER
            );

            CRUD::column([
                'label' => trans('backpack::crud.voucher.column.voucher.bill_value.label'),
                'name' => 'bill_value',
                // 'type'  => 'bald',
                // 'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return $this->priceFormatExport($status_file, $entry->bill_value);
                },
            ]);

            CRUD::column([
                'name' => 'tax_ppn',
                'label' => trans('backpack::crud.voucher.field.tax_ppn.label'),
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return $this->priceFormatExport($status_file, $entry->tax_ppn);
                },
            ]);

            CRUD::column([
                'name' => 'total_price_ppn',
                'label' => trans('backpack::crud.voucher.field.total_price_ppn.label'),
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    $total_price_ppn = ($entry->bill_value * $entry->tax_ppn / 100);
                    $total_price_ppn = str_replace('.00', '', $total_price_ppn);
                    $total_price_ppn = str_replace('.0', '', $total_price_ppn);
                    return $this->priceFormatExport($status_file, $total_price_ppn);
                },
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.voucher.column.voucher.total.label'),
                'name' => 'total',
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return $this->priceFormatExport($status_file, $entry->total);
                },
            ]);

            CRUD::column([
                'name' => 'pph_23',
                'label' => trans('backpack::crud.voucher.field.pph_23.label'),
                'type'  => 'closure',
                'function' => function ($entry) {
                    return str_replace('.00', '', $entry->pph_23);
                },
            ]);

            CRUD::column([
                'name' => 'discount_pph_23',
                'label' => trans('backpack::crud.voucher.field.discount_pph_23.label'),
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return $this->priceFormatExport($status_file, $entry->discount_pph_23);
                },
            ]);

            CRUD::column([
                'name' => 'pph_4',
                'label' => trans('backpack::crud.voucher.field.pph_4.label'),
                'type'  => 'closure',
                'function' => function ($entry) {
                    return str_replace('.00', '', $entry->pph_4);
                },
            ]);

            CRUD::column([
                'name' => 'discount_pph_4',
                'label' =>  trans('backpack::crud.voucher.field.discount_pph_4.label'),
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return $this->priceFormatExport($status_file, $entry->discount_pph_4);
                },
            ]);

            CRUD::column([
                'name' => 'pph_21',
                'label' => trans('backpack::crud.voucher.field.pph_21.label'),
                'type'  => 'closure',
                'function' => function ($entry) {
                    return str_replace('.00', '', $entry->pph_21);
                },
            ]);

            CRUD::column([
                'name' => 'discount_pph_21',
                'label' =>  trans('backpack::crud.voucher.field.discount_pph_21.label'),
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return $this->priceFormatExport($status_file, $entry->discount_pph_21);
                },
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.voucher.column.voucher.payment_transfer.label'),
                'name' => 'payment_transfer',
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return $this->priceFormatExport($status_file, $entry->payment_transfer);
                },
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.voucher.field.due_date.label'),
                'name' => 'due_date',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry->due_date ? Carbon::parse($entry->due_date)->format('d/m/Y') : '-';
                }
            ]);

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.column.voucher.factur_status.label'),
                    'name' => 'factur_status',
                    'type'  => 'text'
                ],
            );

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.field.no_factur.label'),
                    'name' => 'no_factur',
                    'type'  => 'wrap_text'
                ],
            );

            CRUD::column([
                'label' => trans('backpack::crud.voucher.field.date_factur.label'),
                'name' => 'date_factur',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry->date_factur ? Carbon::parse($entry->date_factur)->format('d/m/Y') : '-';
                }
            ]);

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.field.bank_name.label'),
                    'name' => 'bank_name',
                    'type'  => 'text'
                ],
            );

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.field.no_account.label'),
                    'name' => 'no_account',
                    'type'  => 'text'
                ],
            );

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.column.voucher.payment_type.label'),
                    'name' => 'payment_type',
                    'type'  => 'text'
                ],
            );

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.column.voucher.payment_status.label'),
                    'name' => 'payment_status',
                    'type'  => 'text'
                ],
            );

            CRUD::column([
                'label' => trans('backpack::crud.voucher.field.payment_date.label'),
                'name' => 'payment_date',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry->payment_date ? Carbon::parse($entry->payment_date)->format('d/m/Y') : '-';
                }
            ]);

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.field.priority.label'),
                    'name' => 'priority',
                    'type'  => 'text'
                ],
            );

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.field.information.label'),
                    'name' => 'information',
                    'type'  => 'wrap_text',
                    ...$wrap_length
                ],
            );

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.column.voucher.status.label'),
                    'name' => 'status',
                    'type'  => 'approval-voucher',
                ],
            );
        } else if ($tab == 'voucher_edit') {
            CRUD::setModel(VoucherEdit::class);
            CRUD::disableResponsiveTable();

            $a_p = DB::table('approvals')
                ->select(DB::raw('MAX(id) as id'), 'model_type', 'model_id')
                ->groupBy('model_type', 'model_id');

            $this->crud->query = $this->crud->query
                ->leftJoinSub($a_p, 'a_p', function ($join) {
                    $join->on('a_p.model_id', '=', 'voucher_edit.id')
                        ->where('a_p.model_type', '=', DB::raw('"App\\\\Models\\\\VoucherEdit"'));
                })
                ->leftJoin('approvals', 'approvals.id', '=', 'a_p.id')
                ->leftJoin('vouchers', 'vouchers.id', '=', 'voucher_edit.voucher_id');

            if ($request->has('filter_year') && $request->filter_year != 'all') {
                $this->crud->query = $this->crud->query->whereYear('vouchers.date_voucher', $request->filter_year);
            }

            CRUD::addClause('select', [
                DB::raw("
                    voucher_edit.*,
                    vouchers.no_voucher,
                    approvals.no_apprv as approval_no_apprv,
                    approvals.status as approval_status,
                    'voucher_edit' as type
                ")
            ]);

            CRUD::addColumn([
                'name'      => 'row_number',
                'type'      => 'row_number',
                'label'     => 'No',
                'orderable' => false,
                'wrapper' => [
                    'element' => 'strong',
                ]
            ])->makeFirstColumn();

            CRUD::column([
                'label' => trans('backpack::crud.voucher.column.voucher.no_voucher.label'),
                'name' => 'no_voucher',
                'type'  => 'text',
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.voucher.column.voucher_edit.user_id.label'),
                'name' => 'user_id',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry->user->name;
                }
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.voucher.column.voucher_edit.date_update.label'),
                'name' => 'date_update',
                'type'  => 'date',
                'format' => 'DD MMM YYYY HH:mm'
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.voucher.column.voucher_edit.history_update.label'),
                'name' => 'history_update',
                'type'  => 'text',
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.voucher.column.voucher_edit.no_apprv.label'),
                'name' => 'no_apprv',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return 'Final - Approver User';
                }
            ]);

            CRUD::column(
                [
                    'label' => trans('backpack::crud.voucher.column.voucher_edit.status.label'),
                    'name' => 'status',
                    'type'  => 'approval-voucher',
                ],
            );
        }
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

    public function clientSelectedAjax()
    {
        $id = request()->id;
        $type = request()->type;

        if ($type == 'client') {
            $client = ClientPo::where('id', $id)
                ->select(DB::raw("id, po_number, job_name,
                IF(job_value > 0, job_value, 0) as price_total,
                IF(tax_ppn > 0, tax_ppn, 0) as ppn,
                IF(job_value_include_ppn > 0, job_value_include_ppn, 0) as price_total_include_ppn, work_code, 'Client' as type, status, client_id, '' as date_po"))
                ->first();
            $invoice_exists = InvoiceClient::where('client_po_id', $id)->first();
            $company = null;
        } else if ($type == 'subkon') {
            $client = PurchaseOrder::where('id', $id)
                ->select(DB::raw("id, po_number, job_name,
                IF(job_value > 0, job_value, 0) as price_total,
                IF(tax_ppn > 0, tax_ppn, 0) as ppn,
                IF(total_value_with_tax > 0, total_value_with_tax, 0) as price_total_include_ppn, work_code, 'Subkon' as type, subkon_id, date_po"))
                ->first();
            $company = $client->subkon;
            $invoice_exists = null;
        } else if ($type == 'spk') {
            $client = Spk::where('id', $id)
                ->select(DB::raw("id, no_spk as po_number, job_name,
                IF(job_value > 0, job_value, 0) as price_total,
                IF(tax_ppn > 0, tax_ppn, 0) as ppn,
                IF(total_value_with_tax > 0, total_value_with_tax, 0) as price_total_include_ppn,
                work_code, 'Spk' as type,
                subkon_id, date_spk as date_po"))
                ->first();
            $company = $client->subkon;
            $invoice_exists = null;
        }

        $account_selected = Account::query();

        $account_selected = $account_selected->where('code', 501)->first();

        $data = [
            'invoice_exists' => $invoice_exists,
            'po' => $client,
            'date_po' => ($client->date_po != '') ? Carbon::parse($client->date_po)->format('d/m/Y') : '',
            'account' => $account_selected,
            'company' => $company,
        ];

        return response()->json($data);
    }

    public function castAccountSelectedAjax()
    {
        $id = request()->id;
        $castAccount = Subkon::find($id);
        return response()->json($castAccount);
    }

    public function select2_no_po_spk()
    {
        $q = request()->q;

        $po_subkon = PurchaseOrder::select(DB::raw("id, po_number, 'subkon' as type"));
        if (request()->has('company_id')) {
            $company_id = request()->company_id;
            $po_subkon->where('company_id', $company_id);
        }

        $po_spk = Spk::select(DB::raw("id, no_spk as po_number, 'spk' as type"));
        if (request()->has('company_id')) {
            $company_id = request()->company_id;
            $po_spk->where('company_id', $company_id);
        }

        $union = $po_subkon
            ->unionAll($po_spk)
            ->where('po_number', 'like', "%$q%")
            ->paginate(20);

        $results = [];
        foreach ($union as $item) {
            $type = ucfirst($item->type);
            $results[] = [
                'id' => $item->id,
                'text' => $item->po_number . ' (' . $type . ')',
                'type' => $item->type,
            ];
        }
        return response()->json(['results' => $results]);
    }



    protected function setupCreateOperation()
    {
        CRUD::setValidation(VoucherRequest::class);
        $settings = Setting::first();

        $voucher_prefix_value = [];
        $work_code_prefix_value = [];
        $faktur_prefix_value = [];
        if (!$this->crud->getCurrentEntryId()) {
            $voucher_prefix_value = [
                'value' => $this->generateIndexVoucher() . '-' . $settings?->vouhcer_prefix,
            ];
            $work_code_prefix_value = [
                'value' => $settings?->work_code_prefix,
            ];
            $faktur_prefix_value = [
                'value' => $settings?->faktur_prefix,
            ];
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

        CRUD::addField([
            'name' => 'no_payment',
            'label' => trans('backpack::crud.voucher.field.no_payment.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.no_payment.placeholder'),
            ]
        ]);

        CRUD::addField([
            'label'       => trans('backpack::crud.voucher.field.account_id.label'), // Table column heading
            'type'        => "select2_ajax_custom",
            'name'        => 'account_id',
            'entity'      => 'account',
            'model'       => 'App\Models\Account',
            'attribute'   => "name",
            'data_source' => backpack_url('account/select2-account-child'),
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.account_id.placeholder'),
            ]
        ]);

        CRUD::addField([
            'label'       => trans('backpack::crud.voucher.field.work_code.label'), // Table column heading
            'type'        => "select2_ajax_custom",
            // 'name'        => 'reference_id',
            'name'        => 'client_po_id',
            'entity'      => 'client_po',
            'model'       => 'App\Models\ClientPo',
            'attribute'   => "work_code",
            'data_source' => backpack_url('fa/voucher/select2-work-code'),
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.work_code.placeholder'),
            ],
            'dependencies' => ['company_id'],
            'include_all_form_fields' => true,
        ]);

        $cash_accounts = CastAccount::where('status', '!=', CastAccount::LOAN)->get();

        $cash_account_options = [
            '' => trans('backpack::crud.voucher.field.account_source_id.placeholder'),
        ];
        foreach ($cash_accounts as $key => $value) {
            $cash_account_options[$value->id] = $value->name;
        }

        CRUD::addField([
            'name' => 'account_source_id',
            'label' => trans('backpack::crud.voucher.field.account_source_id.label'),
            'type' => 'select2_array',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'options' => $cash_account_options,
            'attributes' => [
                // 'disabled' => true,
                'placeholder' => trans('backpack::crud.voucher.field.bussines_entity_name.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'job_name_disabled',
            'label' => trans('backpack::crud.voucher.field.job_name.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'disabled' => true,
                'placeholder' => trans('backpack::crud.voucher.field.job_name.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'job_name',
            'type' => 'hidden',
        ]);

        CRUD::addField([
            'name' => 'no_voucher',
            'label' => trans('backpack::crud.voucher.field.no_voucher.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.no_voucher.placeholder'),
            ],
            ...$voucher_prefix_value,
        ]);


        CRUD::addField([   // date_picker
            'name'  => 'date_voucher',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.voucher.field.date_voucher.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'label' => trans('backpack::crud.voucher.field.bussines_entity_name.label'),
            'type'        => "select2_ajax_custom",
            'name'        => 'subkon_id',
            'entity'      => 'subkon',
            'model'       => 'App\Models\Subkon',
            'attribute'   => "name",
            'data_source' => backpack_url('fa/voucher/select2-subkon'),
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.bussines_entity_name.placeholder'),
            ],
            'dependencies' => ['company_id'],
            'include_all_form_fields' => true,
        ]);

        CRUD::addField([
            'name' => 'bill_number',
            'label' => trans('backpack::crud.voucher.field.bill_number.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                // 'disabled' => true,
                'placeholder' => trans('backpack::crud.voucher.field.bill_number.placeholder'),
            ]
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'bill_date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.voucher.field.bill_date.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'date_receipt_bill',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.voucher.field.date_receipt_bill.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'payment_description',
            'label' => trans('backpack::crud.voucher.field.payment_description.label'),
            'type' => 'textarea',
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.payment_description.placeholder'),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-12'
            ],
        ]);

        CRUD::addField([
            'label'       => trans('backpack::crud.voucher.field.no_po_spk.label'), // Table column heading
            'type'        => "select2_ajax_custom",
            // 'name'        => 'client_po_id',
            'name'        => 'reference_id',
            'entity'      => 'purchase_order',
            'model'       => 'App\Models\PurchaseOrder',
            'attribute'   => "po_number",
            'data_source' => backpack_url('fa/voucher/select2-po-spk'),
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.no_po_spk.placeholder'),
            ],
            'dependencies' => ['company_id'],
            'include_all_form_fields' => true,
        ]);

        CRUD::addField([
            'name' => 'space_1',
            'type' => 'hidden',
            'label' => '',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'bill_value',
            'label' =>  trans('backpack::crud.voucher.field.bill_value.label'),
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
            'name' => 'dpp_value',
            'label' =>  'Nilai DPP',
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
            'name' => 'tax_ppn',
            'label' => trans('backpack::crud.voucher.field.tax_ppn.label'),
            'type' => 'number',
            // optionals
            'attributes' => ["step" => "any"], // allow decimals
            'prefix'     => "%",
            // 'suffix'     => ".00",
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'total_price_ppn',
            'label' =>  trans('backpack::crud.voucher.field.total_price_ppn.label'),
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
                'disabled' => true,
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'total',
            'label' =>  trans('backpack::crud.voucher.field.total.label'),
            'type' => 'text',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
            'wrapper'   => [
                'class' => 'form-group col-md-4',
            ],
            'attributes' => [
                'disabled' => true,
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'space_total_price_ppn',
            'type' => 'hidden',
            'label' => '',
            'wrapper' => [
                'class' => 'form-group col-md-8'
            ]
        ]);

        CRUD::addField([
            'name' => 'pph_23',
            'label' => trans('backpack::crud.voucher.field.pph_23.label'),
            'type' => 'number',
            // optionals
            'attributes' => ["step" => "any"], // allow decimals
            'prefix'     => "%",
            // 'suffix'     => ".00",
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'discount_pph_23',
            'label' =>  trans('backpack::crud.voucher.field.discount_pph_23.label'),
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
                'disabled' => true,
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'pph_4',
            'label' => trans('backpack::crud.voucher.field.pph_4.label'),
            'type' => 'number',
            // optionals
            'attributes' => ["step" => "any"], // allow decimals
            'prefix'     => "%",
            // 'suffix'     => ".00",
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'discount_pph_4',
            'label' =>  trans('backpack::crud.voucher.field.discount_pph_4.label'),
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
                'disabled' => true,
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'pph_21',
            'label' => trans('backpack::crud.voucher.field.pph_21.label'),
            'type' => 'number',
            // optionals
            'attributes' => ["step" => "any"], // allow decimals
            'prefix'     => "%",
            // 'suffix'     => ".00",
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'discount_pph_21',
            'label' =>  trans('backpack::crud.voucher.field.discount_pph_21.label'),
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
                'disabled' => true,
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'payment_transfer',
            'label' =>  trans('backpack::crud.voucher.field.payment_transfer.label'),
            'type' => 'text',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'disabled' => true,
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'due_date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.voucher.field.due_date.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'disabled' => true,
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([  // Select2
            'label'     => trans('backpack::crud.voucher.field.factur_status.label'),
            'type'      => 'select2_array',
            'name'      => 'factur_status',
            'options'   => [
                '' => trans('backpack::crud.voucher.field.factur_status.placeholder'),
                'ADA' => 'ADA',
                'TIDAK ADA' => 'TIDAK ADA',
                'AKAN ADA' => 'AKAN ADA',
            ], // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'no_factur',
            'label' => trans('backpack::crud.voucher.field.no_factur.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.no_factur.placeholder'),
            ],
            ...$faktur_prefix_value,
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'date_factur',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.voucher.field.date_factur.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'account_holder_name',
            'label' => trans('backpack::crud.voucher.field.account_holder_name.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'disabled' => true,
                'placeholder' => trans('backpack::crud.voucher.field.account_holder_name.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'bank_name',
            'label' => trans('backpack::crud.voucher.field.bank_name.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'disabled' => true,
                'placeholder' => trans('backpack::crud.voucher.field.bank_name.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'no_account',
            'label' => trans('backpack::crud.voucher.field.no_account.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'disabled' => true,
                'placeholder' => trans('backpack::crud.voucher.field.no_account.placeholder'),
            ]
        ]);

        CRUD::addField([  // Select2
            'label'     => trans('backpack::crud.voucher.field.payment_type.label'),
            'type'      => 'select2_array',
            'name'      => 'payment_type',
            'options'   => [
                '' => trans('backpack::crud.voucher.field.payment_type.placeholder'),
                'SUBKON' => 'SUBKON',
                'NON RUTIN' => 'NON RUTIN',
            ], // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([  // Select2
            'label'     => trans('backpack::crud.voucher.field.payment_status.label'),
            'type'      => 'select2_array',
            'name'      => 'payment_status',
            'options'   => [
                // '' => trans('backpack::crud.voucher.field.payment_status.placeholder'),
                'BELUM BAYAR' => 'BELUM BAYAR',
                'BAYAR' => 'BAYAR',
            ], // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'payment_date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.voucher.field.payment_date.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([  // Select2
            'label'     => trans('backpack::crud.voucher.field.priority.label'),
            'type'      => 'select2_array',
            'name'      => 'priority',
            'options'   => [
                '' => trans('backpack::crud.voucher.field.priority.placeholder'),
                'HARI INI' => 'HARI INI',
                'MINGGU INI' => 'MINGGU INI',
                'TEMPO' => 'TEMPO'
            ], // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
            'wrapper' => [
                'class' => 'form-group col-md-12'
            ]
        ]);

        CRUD::addField([
            'name' => 'information',
            'label' => trans('backpack::crud.voucher.field.information.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.information.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'logic_voucher',
            'type' => 'logic_voucher',
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

        $voucher_status = $this->voucherRepository->findByIdWithApprovalStatus((int) $request->id);

        $flag_approval_status = ($voucher_status->approval_status == Approval::APPROVED || $voucher_status->approval_status == Approval::REJECTED) ? true : false;

        DB::beginTransaction();
        try {
            $data = VoucherData::fromRequest($request);
            $item = $this->voucherService->updateVoucher((int) $request->id, $data, $request);

            $this->data['entry'] = $this->crud->entry = $item;

            \Alert::success(trans('backpack::crud.update_success'))->flash();

            $this->crud->setSaveAction();

            DB::commit();
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
                    'events' => [
                        'crudTable-filter_voucher_plugin_load' => $item,
                        'crudTable-voucher_updated_success' => $item,
                        'crudTable-history_edit_voucher_updated_success' => $item,
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

    public function edit($id)
    {
        $this->crud->hasAccessOrFail('update');

        $id = $this->crud->getCurrentEntryId() ?? $id;

        $this->crud->registerFieldEvents();
        $voucher = Voucher::find($id);

        if ($voucher?->reference_type == ClientPo::class) {
            $voucher->reference->type = 'client';
        } else if ($voucher?->reference_type == PurchaseOrder::class) {
            $voucher->reference->type = 'subkon';
        } else if ($voucher?->reference_type == Spk::class) {
            $voucher->reference->type = 'spk';
        }

        $voucher->client_po = ClientPo::find($voucher->client_po_id);

        $this->data['entry'] = $voucher;

        $this->crud->setOperationSetting('fields', $this->crud->getUpdateFields());

        $this->data['crud'] = $this->crud;
        $this->data['saveAction'] = $this->crud->getSaveAction();
        $this->data['title'] = $this->crud->getTitle() ?? trans('backpack::crud.edit') . ' ' . $this->crud->entity_name;
        $this->data['id'] = $id;

        return response()->json([
            'html' => view($this->crud->getEditView(), $this->data)->render()
        ]);
    }



    function generateIndexVoucher()
    {
        return $this->voucherRepository->generateNextVoucherNumber();
    }

    public function select2WorkCode()
    {
        // $this->crud->hasAccessOrFail('create');

        $search = request()->input('q');
        $po_client = ClientPo::select(DB::raw("id, work_code, 'client' as type"))
            ->where('work_code', 'LIKE', "%$search%");

        if (request()->has('company_id')) {
            $po_client->where('company_id', request()->input('company_id'));
        }

        $dataset = $po_client->paginate(20);

        $results = [];
        foreach ($dataset as $item) {
            $type = ucfirst($item->type);
            $results[] = [
                'id' => $item->id,
                'text' => $item->work_code . ' (' . $type . ')',
                'type' => $item->type,
            ];
        }
        return response()->json(['results' => $results]);
    }

    public function select2Subkon()
    {
        $this->crud->hasAccessOrFail('create');
        $search = request()->input('q');
        $query = Subkon::select(['id', 'name'])
            ->where('name', 'LIKE', "%$search%");

        if (request()->has('company_id')) {
            $query->where('company_id', request()->input('company_id'));
        }

        $dataset = $query->paginate(10);

        $results = [];
        foreach ($dataset as $item) {
            $results[] = [
                'id' => $item->id,
                'text' => $item->name,
            ];
        }
        return response()->json(['results' => $results]);
    }

    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        $request = $this->crud->validateRequest();

        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {
            $data = VoucherData::fromRequest($request);
            $item = $this->voucherService->createVoucher($data, $request);

            $event = [
                'crudTable-filter_voucher_plugin_load' => true,
                'crudTable-voucher_create_success' => $item,
                'crudTable-history_edit_voucher_create_success' => $item,
            ];

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            $this->crud->setSaveAction();

            DB::commit();
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
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function addTransaction($id)
    {
        return $this->voucherService->addTransaction((int) $id);
    }

    public function approvedStore($id)
    {
        $this->crud->hasAccessOrFail('create');

        $request = $this->crud->validateRequest();

        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {
            $userId = backpack_user()->id;
            $approval = $this->voucherService->approveVoucherEdit((int) $id, $userId, $request->all());

            $event = [
                'crudTable-voucher_create_success' => true,
                'crudTable-history_edit_voucher_create_success' => true,
            ];

            DB::commit();
            return response()->json([
                'success' => true,
                'data' => $approval,
                'events' => $event,
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

    protected function setupShowOperation()
    {
        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::field([
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'select',
                'name'      => 'company_id',
                'entity'    => 'company',
                'attribute' => 'name',
                'model'     => "App\Models\Company",
                'wrapper'   => [
                    'class' => 'form-group col-md-12',
                ],
            ]);

            CRUD::column([
                'label'     => trans('backpack::crud.subkon.column.company'),
                'type'      => 'select',
                'name'      => 'company_id',
                'entity'    => 'company',
                'attribute' => 'name',
                'model'     => "App\Models\Company",
            ]);
        }

        CRUD::addField([
            'name' => 'no_payment',
            'label' => trans('backpack::crud.voucher.field.no_payment.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.no_payment.placeholder'),
            ]
        ]);

        CRUD::addField([
            'label'       => trans('backpack::crud.voucher.field.account_id.label'), // Table column heading
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
                'placeholder' => trans('backpack::crud.voucher.field.account_id.placeholder'),
            ]
        ]);

        CRUD::addField([
            'label'       => trans('backpack::crud.voucher.field.work_code.label'), // Table column heading
            'type'        => "select2_ajax_custom",
            'name'        => 'client_po_id',
            'entity'      => 'client_po',
            'model'       => 'App\Models\ClientPo',
            'attribute'   => "work_code",
            'data_source' => backpack_url('fa/voucher/select2-work-code'),
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.work_code.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'account_source_id',
            'label' => trans('backpack::crud.voucher.field.account_source_id.label'),
            'type' => 'select2_array',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'options' => [],
            'attributes' => [
                // 'disabled' => true,
                'placeholder' => trans('backpack::crud.voucher.field.bussines_entity_name.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'job_name',
            'label' => trans('backpack::crud.voucher.field.job_name.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.job_name.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'no_voucher',
            'label' => trans('backpack::crud.voucher.field.no_voucher.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.no_voucher.placeholder'),
            ],
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'date_voucher',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.voucher.field.date_voucher.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'label' => trans('backpack::crud.voucher.field.bussines_entity_name.label'),
            'type'        => "select2_ajax_custom",
            'name'        => 'subkon_id',
            'entity'      => 'subkon',
            'model'       => 'App\Models\Subkon',
            'attribute'   => "name",
            'data_source' => backpack_url('fa/voucher/select2-subkon'),
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.bussines_entity_name.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'account_holder_name',
            'label' => trans('backpack::crud.voucher.field.account_holder_name.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'disabled' => true,
                'placeholder' => trans('backpack::crud.voucher.field.account_holder_name.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'bill_number',
            'label' => trans('backpack::crud.voucher.field.bill_number.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                // 'disabled' => true,
                'placeholder' => trans('backpack::crud.voucher.field.bill_number.placeholder'),
            ]
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'bill_date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.voucher.field.bill_date.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'date_receipt_bill',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.voucher.field.date_receipt_bill.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'payment_description',
            'label' => trans('backpack::crud.voucher.field.payment_description.label'),
            'type' => 'textarea',
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.payment_description.placeholder'),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-12'
            ],
        ]);

        CRUD::addField([
            'label'       => trans('backpack::crud.voucher.field.no_po_spk.label'), // Table column heading
            'type'        => "select2_ajax_custom",
            'name'        => 'reference_id',
            'entity'      => 'client_po',
            'model'       => 'App\Models\CLientPo',
            'attribute'   => "po_number",
            'data_source' => backpack_url('fa/voucher/select2-po-spk'),
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.no_po_spk.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'space_1',
            'type' => 'hidden',
            'label' => '',
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        // CRUD::addField([   // date_picker
        //     'name'  => 'date_po_spk',
        //     'type'  => 'text',
        //     'label' => trans('backpack::crud.voucher.field.date_po_spk.label'),

        //     // optional:
        //     'date_picker_options' => [
        //         'language' => App::getLocale(),
        //     ],
        //     'wrapper'   => [
        //         'class' => 'form-group col-md-6'
        //     ],
        //     'suffix' => '<span class="la la-calendar"></span>',
        //     'attributes' => [
        //         'disabled' => true,
        //     ]
        // ]);

        CRUD::addField([
            'name' => 'bill_value',
            'label' =>  trans('backpack::crud.voucher.field.bill_value.label'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'dpp_value',
            'label' =>  'Nilai DPP',
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'tax_ppn',
            'label' => trans('backpack::crud.voucher.field.tax_ppn.label'),
            'type' => 'number',
            // optionals
            'attributes' => ["step" => "any"], // allow decimals
            'prefix'     => "%",
            // 'suffix'     => ".00",
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'total',
            'label' =>  trans('backpack::crud.voucher.field.total.label'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-4',
            ],
            'attributes' => [
                'disabled' => true,
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'pph_23',
            'label' => trans('backpack::crud.voucher.field.pph_23.label'),
            'type' => 'number',
            // optionals
            'attributes' => ["step" => "any"], // allow decimals
            'prefix'     => "%",
            // 'suffix'     => ".00",
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'discount_pph_23',
            'label' =>  trans('backpack::crud.voucher.field.discount_pph_23.label'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'disabled' => true,
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'pph_4',
            'label' => trans('backpack::crud.voucher.field.pph_4.label'),
            'type' => 'number',
            // optionals
            'attributes' => ["step" => "any"], // allow decimals
            'prefix'     => "%",
            // 'suffix'     => ".00",
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'discount_pph_4',
            'label' =>  trans('backpack::crud.voucher.field.discount_pph_4.label'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'disabled' => true,
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'pph_21',
            'label' => trans('backpack::crud.voucher.field.pph_21.label'),
            'type' => 'number',
            // optionals
            'attributes' => ["step" => "any"], // allow decimals
            'prefix'     => "%",
            // 'suffix'     => ".00",
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'discount_pph_21',
            'label' =>  trans('backpack::crud.voucher.field.discount_pph_21.label'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'disabled' => true,
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([
            'name' => 'payment_transfer',
            'label' =>  trans('backpack::crud.voucher.field.payment_transfer.label'),
            'type' => 'mask',
            'mask' => '000.000.000.000.000.000',
            'mask_options' => [
                'reverse' => true
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'disabled' => true,
                'placeholder' => '000.000',
            ]
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'due_date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.voucher.field.due_date.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'disabled' => true,
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([  // Select2
            'label'     => trans('backpack::crud.voucher.field.factur_status.label'),
            'type'      => 'select2_array',
            'name'      => 'factur_status',
            'options'   => [
                '' => trans('backpack::crud.voucher.field.factur_status.placeholder'),
                'ADA' => 'ADA',
                'TIDAK ADA' => 'TIDAK ADA',
                'AKAN ADA' => 'AKAN ADA',
            ], // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'no_factur',
            'label' => trans('backpack::crud.voucher.field.no_factur.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.no_factur.placeholder'),
            ],
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'date_factur',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.voucher.field.date_factur.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([
            'name' => 'bank_name',
            'label' => trans('backpack::crud.voucher.field.bank_name.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                // 'disabled' => true,
                'placeholder' => trans('backpack::crud.voucher.field.bank_name.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'no_account',
            'label' => trans('backpack::crud.voucher.field.no_account.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                // 'disabled' => true,
                'placeholder' => trans('backpack::crud.voucher.field.no_account.placeholder'),
            ]
        ]);

        CRUD::addField([  // Select2
            'label'     => trans('backpack::crud.voucher.field.payment_type.label'),
            'type'      => 'select2_array',
            'name'      => 'payment_type',
            'options'   => [
                '' => trans('backpack::crud.voucher.field.payment_type.placeholder'),
                'SUBKON' => 'SUBKON',
                'NON RUTIN' => 'NON RUTIN',
            ], // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([  // Select2
            'label'     => trans('backpack::crud.voucher.field.payment_status.label'),
            'type'      => 'select2_array',
            'name'      => 'payment_status',
            'options'   => [
                '' => trans('backpack::crud.voucher.field.payment_status.placeholder'),
                'BAYAR' => 'BAYAR',
                'BELUM BAYAR' => 'BELUM BAYAR',
            ], // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'payment_date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.voucher.field.payment_date.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([  // Select2
            'label'     => trans('backpack::crud.voucher.field.priority.label'),
            'type'      => 'select2_array',
            'name'      => 'priority',
            'options'   => [
                '' => trans('backpack::crud.voucher.field.priority.placeholder'),
                'HARI INI' => 'HARI INI',
                'MINGGU INI' => 'MINGGU INI',
                'TEMPO' => 'TEMPO'
            ], // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
            'wrapper' => [
                'class' => 'form-group col-md-12'
            ]
        ]);

        CRUD::addField([
            'name' => 'information',
            'label' => trans('backpack::crud.voucher.field.information.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.voucher.field.information.placeholder'),
            ]
        ]);


        // column

        // CRUD::column(
        //     [
        //         'label'  => '',
        //         'name' => 'account_id',
        //         'type'  => 'closure',
        //         'function' => function($entry){
        //             return $entry->account->code ." - ".$entry->account->name;
        //         }
        //     ],
        // );

        //  CRUD::column([
        //     'label'  => '',
        //     'name' => 'payment_transfer',
        //     'type'  => 'number',
        //     'prefix' => "Rp.",
        //     'decimals'      => 2,
        //     'dec_point'     => ',',
        //     'thousands_sep' => '.',
        // ]);

        // CRUD::column([
        //     'label'  => '',
        //     'name' => 'pph_23',
        //     'type'  => 'number',
        //     'suffix' => '%',
        // ]);

        // CRUD::column([
        //     'label'  => '',
        //     'name' => 'due_date',
        //     'type'  => 'date',
        //     'format' => 'D MMM Y'
        // ]);

        $new_format_date = 'DD/MM/YYYY';

        CRUD::column([
            'label'  => '',
            'name' => 'no_payment',
            'type'  => 'wrap_text',
        ]);

        CRUD::column(
            [
                'label'  => '',
                'name' => 'account_id',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry->account->code . " - " . $entry->account->name;
                }
            ],
        );

        CRUD::column(
            [
                'label'  => '',
                'name' => 'client_po_id',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry?->client_po?->work_code;
                }
            ],
        );

        CRUD::column(
            [
                'label'  => '',
                'name' => 'account_source_id',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry->account_source->name;
                }
            ],
        );

        CRUD::column([
            'label'  => '',
            'name' => 'job_name',
            'type'  => 'wrap_text',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'no_voucher',
            'type'  => 'text',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'date_voucher',
            'type'  => 'date',
            'format' => $new_format_date,
        ]);

        CRUD::column(
            [
                'label'  => '',
                'name' => 'subkon_id',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry->subkon->name;
                }
            ],
        );

        CRUD::column(
            [
                'label'  => '',
                'name' => 'account_holder_name',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry->account_holder_name ?? $entry->subkon->account_holder_name;
                }
            ],
        );

        CRUD::column([
            'label'  => '',
            'name' => 'bill_number',
            'type'  => 'wrap_text',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'bill_date',
            'type'  => 'date',
            'format' => $new_format_date,
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'date_receipt_bill',
            'type'  => 'date',
            'format' => $new_format_date,
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'payment_description',
            'type'  => 'wrap_text',
            'width_box' => '100%',
        ]);

        CRUD::column(
            [
                'label'  => '',
                'name' => 'reference_id',
                'type'  => 'closure',
                'function' => function ($entry) {
                    if ($entry->reference_type == Spk::class) {
                        return $entry?->reference?->no_spk;
                    }
                    return $entry?->reference?->po_number;
                }
            ],
        );

        CRUD::column([
            'label'  => '',
            'name' => 'date_po_spk',
            'type'  => 'date',
            'format' => $new_format_date,
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'bill_value',
            'type'  => 'number',
            'prefix' => "Rp.",
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
        ]);

        CRUD::column([
            'label'  => 'Nilai DPP',
            'name' => 'dpp_value',
            'type'  => 'number',
            'prefix' => "Rp.",
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'tax_ppn',
            'type'  => 'number',
            'suffix' => '%',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'total',
            'type'  => 'number',
            'prefix' => "Rp.",
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'pph_23',
            'type'  => 'number',
            'suffix' => '%',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'discount_pph_23',
            'type'  => 'number',
            'prefix' => "Rp.",
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'pph_4',
            'type'  => 'number',
            'suffix' => '%',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'discount_pph_4',
            'type'  => 'number',
            'prefix' => "Rp.",
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'pph_21',
            'type'  => 'number',
            'suffix' => '%',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'discount_pph_21',
            'type'  => 'number',
            'prefix' => "Rp.",
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'payment_transfer',
            'type'  => 'number',
            'prefix' => "Rp.",
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'due_date',
            'type'  => 'date',
            'format' => $new_format_date,
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'factur_status',
            'type'  => 'text',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'no_factur',
            'type'  => 'text',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'date_factur',
            'type'  => 'date',
            'format' => $new_format_date,
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'bank_name',
            'type'  => 'text',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'no_account',
            'type'  => 'text',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'payment_type',
            'type'  => 'text',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'payment_status',
            'type'  => 'text',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'payment_date',
            'type'  => 'date',
            'format' => $new_format_date,
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'priority',
            'type'  => 'text',
        ]);

        CRUD::column([
            'label'  => '',
            'name' => 'information',
            'type'  => 'wrap_text',
        ]);
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

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $this->crud->hasAccessOrFail('delete');

            $this->voucherService->deleteVoucher((int) $id);

            $messages['success'][] = trans('backpack::crud.delete_confirmation_message');
            $messages['events'] = [
                'crudTable-filter_voucher_plugin_load' => true,
                'crudTable-voucher_create_success' => true,
                'crudTable-history_edit_voucher_create_success' => true,
            ];

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

    public function print($id)
    {
        $voucher = $this->voucherRepository->findForPrint((int) $id);

        $html = view('exports.voucher-pdf-origin', [
            'voucher' => $voucher,
        ])->render();

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-P',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        $mpdf->WriteHTML($html);

        $fileName = "voucher-$voucher->no_voucher.pdf";

        return response()->streamDownload(function () use ($mpdf) {
            echo $mpdf->Output('', 'S');
        }, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportPdf()
    {
        $type = request()->tab;

        $this->setupListExport($type);

        $columns = $this->crud->columns();
        $this->crud->autoEagerLoadRelationshipColumns();

        $entries = $this->crud->query->cursor();

        $row_number = 0;

        $title = "VOUCHER";
        if ($type == 'voucher_edit') {
            $title = "VOUCHER EDIT";
        }

        $mpdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => 'A4-L',
            'margin_left' => 10,
            'margin_right' => 10,
            'margin_top' => 10,
            'margin_bottom' => 10,
        ]);

        // Style and Table Header
        $html = '
        <html>
        <head>
            <style>
                body { font-family: sans-serif; font-size: 12px; }
                table { 
                    width: 100%; 
                    border-collapse: collapse; 
                    margin-top: 10px; 
                    table-layout: auto;
                }
                th, td { 
                    border: 1px solid #000; 
                    padding: 3px; 
                    text-align: center; 
                    word-wrap: break-word;
                }
                th { font-weight: bold; background-color: #eee; white-space: normal; }
            </style>
        </head>
        <body>
            <h3 style="text-align:center;">' . $title . '</h3>
            <table autosize="1">
                <thead>
                    <tr>';
        foreach ($columns as $col) {
            $html .= '<th>' . ($col['label'] ?? '') . '</th>';
        }
        $html .= '</tr>
                </thead>
                <tbody>';

        $mpdf->WriteHTML($html);

        foreach ($entries as $entry) {
            $entry->addFakes($this->crud->getFakeColumnsAsArray());
            $row_number++;
            $row_html = '<tr>';
            foreach ($columns as $column) {
                $item_value = ($column['name'] == 'row_number') ? $row_number : $this->crud->getCellView($column, $entry, $row_number);

                // Clean HTML
                $item_value = str_replace(['<span>', '</span>', "\n"], '', $item_value);
                $item_value = CustomHelper::clean_html($item_value);

                $row_html .= '<td>' . trim($item_value) . '</td>';
            }
            $row_html .= '</tr>';
            $mpdf->WriteHTML($row_html);
        }

        $mpdf->WriteHTML('</tbody></table></body></html>');

        $fileName = 'vendor_po_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($mpdf) {
            echo $mpdf->Output('', 'S');
        }, $fileName, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportExcel()
    {
        $type = request()->tab;

        $this->setupListExport($type);
        // $this->setupListOperation();

        $columns = $this->crud->columns();
        $items =  $this->crud->getEntries();

        $row_number = 0;

        $all_items = [];

        foreach ($items as $item) {
            $row_items = [];
            $row_number++;
            foreach ($columns as $column) {
                if (isset($column['type']) && $column['type'] == 'number') {
                    $name = $column['name'];
                    $item_value = $item->$name ?? 0;
                    $item_value = $this->priceFormatExport('excel', $item_value);
                } else {
                    $item_value = ($column['name'] == 'row_number') ? $row_number : $this->crud->getCellView($column, $item, $row_number);
                }
                $item_value = str_replace('<span>', '', $item_value);
                $item_value = str_replace('</span>', '', $item_value);
                $item_value = str_replace("\n", '', $item_value);
                $item_value = CustomHelper::clean_html($item_value);
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $name = 'VOUCHER';
        if ($type == 'voucher_edit') {
            $name = "VOUCHER EDIT";
        }

        return response()->streamDownload(function () use ($type, $columns, $items, $all_items) {
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

    public function voidPayment($id)
    {
        $this->crud->hasAccessOrFail('void');

        DB::beginTransaction();
        try {
            $result = $this->voucherService->voidPayment((int) $id);

            if (!$result['success']) {
                DB::rollBack();
                return response()->json($result);
            }

            DB::commit();
            return response()->json([
                'success' => true,
                'events' => [
                    'crudTable-voucher_create_success' => true,
                ],
                'message' => $result['message']
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
