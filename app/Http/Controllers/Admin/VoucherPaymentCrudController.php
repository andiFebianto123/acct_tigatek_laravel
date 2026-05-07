<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Account;
use App\Models\Setting;
use App\Models\Voucher;
use App\Models\Approval;
use App\Models\ClientPo;
use App\Models\LogPayment;
use App\Models\JournalEntry;
use App\Models\InvoiceClient;
use App\Models\PaymentVoucher;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Helpers\CustomVoid;
use App\Http\Exports\ExportExcel;
use App\Http\Helpers\CustomHelper;
use App\Models\PaymentVoucherPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\PermissionAccess;
use App\Models\AccountTransaction;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\DTOs\Fa\VoucherPaymentFilterData;
use App\DTOs\Fa\VoucherPaymentStoreData;
use App\DTOs\Fa\VoucherPaymentStoreSingleData;
use App\Services\Fa\VoucherPaymentService;
use App\Repositories\Fa\VoucherPaymentRepository;
use App\Http\Requests\Fa\VoucherPaymentRequest;
use Illuminate\Support\Facades\View;
use Prologue\Alerts\Facades\Alert;

class VoucherPaymentCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use PermissionAccess;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;

    public function setup()
    {
        CRUD::setModel(PaymentVoucher::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/fa/voucher-payment');
        CRUD::setEntityNameStrings(trans('backpack::crud.voucher_payment.title_header'), trans('backpack::crud.voucher_payment.title_header'));



        $viewMenu = [
            "MENU INDEX FA PEMBAYARAN"
        ];

        $this->settingPermission([
            'approve' => ["APPROVE RENCANA BAYAR"],
            'create' => [
                'CREATE INDEX FA PEMBAYARAN',
            ],
            'update' => [
                'UPDATE INDEX FA PEMBAYARAN',
            ],
            'delete' => [
                'DELETE INDEX FA PEMBAYARAN',
            ],
            'void' => ["VOID INDEX FA VOUCHER"],
            'list' => $viewMenu,
            'show' => $viewMenu,
            'print' => true,
        ]);
    }

    function total_voucher()
    {
        $dto = VoucherPaymentFilterData::fromRequest(request());
        $repository = new VoucherPaymentRepository();
        return response()->json($repository->getTotalVoucher($dto));
    }

    function index()
    {
        $this->crud->hasAccessOrFail('list');

        $isSuperAdmin = backpack_user()->hasRole('Super Admin');
        $companyColumn = [
            'label' => trans('backpack::crud.subkon.column.company'),
            'type'  => 'text',
            'name'  => 'company_name',
            'orderable' => true,
        ];

        $this->card->addCard([
            'name' => 'payment_non_rutin',
            'line' => 'top',
            'view' => 'crud::components.card-tab',
            'title' => 'Non Rutin',
            'params' => [
                'tabs' => [
                    [
                        'name' => 'voucher_payment_non_rutin',
                        'label' => trans('backpack::crud.voucher_payment.tab.title_voucher_payment'),
                        'active' => true,
                        'view' => 'crud::components.datatable',
                        'params' => [
                            'filter' => true,
                            'crud_custom' => $this->crud,
                            'columns' => array_merge(
                                [
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
                                ],
                                $isSuperAdmin ? [$companyColumn] : [],
                                [
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.no_voucher.label'),
                                        'type'      => 'text',
                                        'name'      => 'no_voucher',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.date_voucher.label'),
                                        'type'      => 'text',
                                        'name'      => 'date_voucher',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.bussines_entity_name.label'),
                                        'type'      => 'text',
                                        'name'      => 'bussines_entity_name',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.bill_date.label'),
                                        'type'      => 'text',
                                        'name'      => 'bill_date',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.no_po_spk.label'),
                                        'type'      => 'text',
                                        'name'      => 'no_po_spk',
                                        'orderable' => false,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.payment_transfer.label_2'),
                                        'type'      => 'text',
                                        'name'      => 'payment_transfer',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.due_date.label_2'),
                                        'type'      => 'text',
                                        'name'      => 'due_date',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.factur_status.label'),
                                        'type'      => 'text',
                                        'name'      => 'factur_status',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.due_date.label'),
                                        'type'      => 'text',
                                        'name'      => 'due_date_payment',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.payment_status.label'),
                                        'type'      => 'text',
                                        'name'      => 'payment_status',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.approved_at.label'),
                                        'type'      => 'text',
                                        'name'      => 'approved_at',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.status.label'),
                                        'type'      => 'text',
                                        'name'      => 'status',
                                        'orderable' => false,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.user_approval.label'),
                                        'type' => 'text',
                                        'name' => 'user_approval',
                                        'orderable' => false,
                                    ],
                                ]
                            ),
                            'route' => backpack_url('/fa/voucher-payment/search?tab=voucher_payment&type=NON RUTIN'),
                            'route_export_pdf' => url($this->crud->route . '/export-pdf?tab=voucher_payment&type=NON+RUTIN'),
                            'title_export_pdf' => 'Laporan_voucher_payment_non_rutin.pdf',
                            'route_export_excel' => url($this->crud->route . '/export-excel?tab=voucher_payment&type=NON+RUTIN'),
                            'title_export_excel' => 'Laporan_voucher_payment_non_rutin.xlsx',
                        ]
                    ],
                ]
            ]
        ]);

        $this->card->addCard([
            'name' => 'payment_rutin',
            'line' => 'top',
            'view' => 'crud::components.card-tab',
            'title' => 'Subkon',
            'params' => [
                'tabs' => [
                    [
                        'name' => 'voucher_payment_rutin',
                        'label' => trans('backpack::crud.voucher_payment.tab.title_voucher_payment'),
                        'active' => true,
                        'view' => 'crud::components.datatable',
                        'params' => [
                            'filter' => true,
                            'crud_custom' => $this->crud,
                            'columns' => array_merge(
                                [
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
                                ],
                                $isSuperAdmin ? [$companyColumn] : [],
                                [
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.no_voucher.label'),
                                        'type'      => 'text',
                                        'name'      => 'no_voucher',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.date_voucher.label'),
                                        'type'      => 'text',
                                        'name'      => 'date_voucher',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.bussines_entity_name.label'),
                                        'type'      => 'text',
                                        'name'      => 'subkon_id',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.bill_date.label'),
                                        'type'      => 'text',
                                        'name'      => 'bill_date',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.no_po_spk.label'),
                                        'type'      => 'text',
                                        'name'      => 'reference_id',
                                        'orderable' => false,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.payment_transfer.label_2'),
                                        'type'      => 'text',
                                        'name'      => 'payment_transfer',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.due_date.label_2'),
                                        'type'      => 'text',
                                        'name'      => 'due_date',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.factur_status.label'),
                                        'type'      => 'text',
                                        'name'      => 'factur_status',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.due_date.label'),
                                        'type'      => 'text',
                                        'name'      => 'due_date_payment',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.payment_status.label'),
                                        'type'      => 'text',
                                        'name'      => 'payment_status',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.approved_at.label'),
                                        'type'      => 'text',
                                        'name'      => 'approved_at',
                                        'orderable' => true,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.status.label'),
                                        'type'      => 'text',
                                        'name'      => 'status',
                                        'orderable' => false,
                                    ],
                                    [
                                        'label' => trans('backpack::crud.voucher.column.voucher.user_approval.label'),
                                        'type' => 'text',
                                        'name' => 'user_approval',
                                        'orderable' => false,
                                    ],
                                ]
                            ),
                            'route' => backpack_url('/fa/voucher-payment/search?tab=voucher_payment&type=SUBKON'),
                            'route_export_pdf' => url($this->crud->route . '/export-pdf?tab=voucher_payment&type=SUBKON'),
                            'title_export_pdf' => 'Laporan_voucher_payment_subkon.pdf',
                            'route_export_excel' => url($this->crud->route . '/export-excel?tab=voucher_payment&type=SUBKON'),
                            'title_export_excel' => 'Laporan_voucher_payment_subkon.xlsx',
                        ]
                    ],
                ]
            ]
        ]);

        $this->card->addCard([
            'name' => 'voucher-payment-plugin',
            'line' => 'top',
            'view' => 'crud::components.voucher-payment-plugin',
            'parent_view' => 'crud::components.filter-parent',
            'params' => [],
        ]);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.voucher_payment.title_modal_create_payment');
        $this->data['title_modal_edit'] = trans('backpack::crud.voucher_payment.title_modal_edit');
        $this->data['title_modal_delete'] = trans('backpack::crud.voucher_payment.title_modal_delete');
        $this->data['cards'] = $this->card;

        $breadcrumbs = [
            'FA' => backpack_url('fa'),
            trans('backpack::crud.voucher_payment.title_header') => backpack_url($this->crud->route)
        ];

        $this->data['breadcrumbs'] = $breadcrumbs;
        $this->data['year_options'] = CustomHelper::getYearOptions('vouchers', 'date_voucher');

        $list = "crud::list-blank" ?? $this->crud->getListView();
        return view($list, $this->data);
    }

    protected function setupListOperation()
    {
        $request = request();
        $tab = $request->tab;
        $type = $request->type;

        $this->crud->set('file_title_export_pdf', "Laporan_voucher_pembayaran.pdf");
        $this->crud->set('file_title_export_excel', "Laporan_voucher_pembayaran.xlsx");
        $this->crud->set('param_uri_export', "?export=1&tab=voucher_payment&type=" . urlencode($type));

        CRUD::addButtonFromView('top', 'voucher-payment-export-excel', 'voucher-payment-export-excel', 'beginning');
        CRUD::addButtonFromView('top', 'voucher-payment-export-pdf', 'voucher-payment-export-pdf', 'beginning');
        CRUD::addButtonFromView('top', 'filter_year', 'filter-year', 'beginning');
        CRUD::removeButton('delete');
        CRUD::addButtonFromView('line_start', 'approve_button', 'approve_button', 'end');
        CRUD::addButtonFromView('line_start', 'void_voucher_payment', 'void_voucher_payment', 'end');

        CRUD::setModel(PaymentVoucher::class);
        CRUD::disableResponsiveTable();

        $dto = VoucherPaymentFilterData::fromRequest($request);
        $repository = new VoucherPaymentRepository();
        $repository->applyListQuery($this->crud->query, $dto);

        $this->setupColumns($type);
    }

    protected function setupListExport()
    {
        $request = request();
        $type = $request->type;
        CRUD::setModel(PaymentVoucher::class);
        $dto = VoucherPaymentFilterData::fromRequest($request);
        $repository = new VoucherPaymentRepository();
        $repository->applyListQuery($this->crud->query, $dto);
        $this->setupColumns($type);
    }

    protected function setupColumns($type)
    {
        $settings = Setting::first();
        $new_format_date = 'DD/MM/YYYY';

        // 1. Column: Row Number
        CRUD::addColumn([
            'name'      => 'row_number',
            'type'      => 'row_number',
            'label'     => 'No',
            'orderable' => false,
            'wrapper' => [
                'element' => 'strong',
            ]
        ])->makeFirstColumn();

        // 2. Column: Action (handle difference in name/label)
        $actionColumnName = ($type == 'NON RUTIN') ? 'action' : 'action_custom';
        $actionLabel = ($type == 'NON RUTIN') ? '' : trans('backpack::crud.actions');
        $actionWidth = ($type == 'NON RUTIN') ? '100px' : '150px';

        CRUD::addColumn([
            'name' => $actionColumnName,
            'type' => 'closure',
            'label' =>  $actionLabel,
            'escaped' => false,
            'width_box' => $actionWidth,
            'function' => function ($entry, $rowNumber = null) {
                $crud = $this->crud;
                return View::make('crud::inc.button_stack', ['stack' => 'line_start'])
                    ->with('crud', $crud)
                    ->with('entry', $entry)
                    ->with('row_number', $rowNumber)
                    ->render();
            }
        ]);

        // 3. Column: Milik Perusahaan (Conditional for Super Admin)
        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::column([
                'name'  => 'company_name',
                'label' => trans('backpack::crud.subkon.column.company'),
                'type'  => 'text',
                'orderLogic' => function ($query, $column, $order) {
                    return $query->orderBy('companies.name', $order);
                }
            ])->afterColumn($actionColumnName);
        }

        // 4. Shared Columns
        CRUD::column([
            'label'  => trans('backpack::crud.voucher.column.voucher.no_voucher.label'),
            'name' => 'no_voucher',
            'type'  => 'text',
            'orderLogic' => function ($query, $column, $order) {
                return $query->orderBy('vouchers.no_voucher', $order);
            }
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.voucher.column.voucher.date_voucher.label'),
            'name' => 'date_voucher',
            'type'  => 'date',
            'format' => $new_format_date,
            'orderLogic' => function ($query, $column, $order) {
                return $query->orderBy('vouchers.date_voucher', $order);
            }
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.voucher.column.voucher.bussines_entity_name.label'),
            'name' => 'subkon_id',
            'type'  => 'closure',
            'function' => function ($entry) {
                return $entry?->voucher?->subkon?->name;
            },
            'orderLogic' => function ($query, $column, $order) {
                return $query->leftJoin('subkons', 'subkons.id', '=', 'vouchers.subkon_id')
                    ->orderBy('subkons.name', $order);
            }
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.voucher.column.voucher.bill_date.label'),
            'name' => 'bill_date',
            'type'  => 'date',
            'format' => $new_format_date,
            'orderLogic' => function ($query, $column, $order) {
                return $query->orderBy('vouchers.bill_date', $order);
            }
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.voucher.column.voucher.no_po_spk.label'),
            'name' => 'reference_id',
            'type'  => 'closure',
            'function' => function ($entry) {
                if ($entry?->voucher?->reference_type == 'App\Models\Spk') {
                    return $entry?->voucher?->reference?->no_spk;
                }
                return $entry?->voucher?->reference?->po_number;
            }
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.voucher.column.voucher.payment_transfer.label'),
            'name' => 'payment_transfer',
            'type'  => 'number',
            'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : "Rp.",
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
            'orderLogic' => function ($query, $column, $order) {
                return $query->orderBy('vouchers.payment_transfer', $order);
            }
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.voucher.column.voucher.due_date.label'),
            'name' => 'due_date',
            'type'  => 'date',
            'format' => $new_format_date,
            'orderLogic' => function ($query, $column, $order) {
                return $query->orderBy('vouchers.due_date', $order);
            }
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.voucher.column.voucher.factur_status.label'),
            'name' => 'factur_status',
            'type'  => 'text',
            'orderLogic' => function ($query, $column, $order) {
                return $query->orderBy('vouchers.factur_status', $order);
            }
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.voucher.column.voucher.due_date.label'),
            'name' => 'payment_date',
            'type'  => 'date',
            'format' => $new_format_date,
            'orderLogic' => function ($query, $column, $order) {
                return $query->orderBy('vouchers.payment_date', $order);
            }
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.voucher.column.voucher.payment_status.label'),
            'name' => 'payment_status',
            'type'  => 'text',
            'orderLogic' => function ($query, $column, $order) {
                return $query->orderBy('vouchers.payment_status', $order);
            }
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.voucher.column.voucher.approved_at.label'),
            'name' => 'approval_approved_at',
            'type'  => 'date',
            'format' => $new_format_date,
            'orderLogic' => function ($query, $column, $order) {
                return $query->orderBy('approvals.approved_at', $order);
            }
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.voucher.column.voucher.status.label'),
            'name' => 'status',
            'type'  => 'approval-voucher',
        ]);

        CRUD::addColumn([
            'name'     => 'user_approval',
            'label'    => trans('backpack::crud.voucher.column.voucher.user_approval.label'),
            'type'     => 'custom_html',
            'value' => function ($entry) {
                $approvals = Approval::where('model_type', PaymentVoucherPlan::class)
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
        ]);
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


    protected function setupCreateOperation()
    {
        CRUD::setValidation(VoucherPaymentRequest::class);
        $settings = Setting::first();

        CRUD::addField([
            'name' => 'voucher_payment',
            'label' => '',
            'type' => 'voucher-list-ajax',
        ]);
    }

    public function datatableVoucher()
    {
        $dto = VoucherPaymentFilterData::fromRequest(request());
        $repository = new VoucherPaymentRepository();
        return response()->json($repository->getDatatableVoucher($dto));
    }



    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        $request = $this->crud->validateRequest();

        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {
            $dto = VoucherPaymentStoreData::fromRequest(request());
            $service = new VoucherPaymentService();
            $event = $service->store($dto);

            Alert::success(trans('backpack::crud.insert_success'))->flash();

            $this->crud->setSaveAction();

            DB::commit();
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'events' => $event,
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function storeSingle()
    {
        $this->crud->hasAccessOrFail('create');
        $request = request();
        $request->validate([
            'id' => 'required|exists:vouchers,id',
        ]);

        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {
            $dto = VoucherPaymentStoreSingleData::fromRequest($request);
            $service = new VoucherPaymentService();
            $event = $service->storeSingle($dto);

            \Alert::success(trans('backpack::crud.insert_success'))->flash();

            $this->crud->setSaveAction();

            DB::commit();
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'events' => $event,
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }



    public function exportPdf()
    {
        $type = request()->tab;

        $this->setupListExport();

        CRUD::removeColumn('document_path');

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

        $title = trans('backpack::crud.voucher_payment.title_header');

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
        $type = request()->tab;

        $this->setupListExport();
        CRUD::removeColumn('document_path');

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

        $name = 'VOUCHER PEMBAYARAN';

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

    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        DB::beginTransaction();
        try {
            $service = new VoucherPaymentService();
            $event = $service->destroy($id);

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
    public function voidPayment($id)
    {
        $this->crud->hasAccessOrFail('void');

        DB::beginTransaction();
        try {
            $service = new VoucherPaymentService();
            $event = $service->voidPayment($id);

            DB::commit();
            return response()->json([
                'success' => true,
                'message' => 'Pembayaran voucher berhasil di-Void.',
                'events' => $event
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
