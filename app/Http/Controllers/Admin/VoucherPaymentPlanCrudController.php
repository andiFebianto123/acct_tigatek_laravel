<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\Account;
use App\Models\Setting;
use App\Models\Voucher;
use App\Models\Approval;
use App\Models\CastAccount;
use App\Models\InvoiceClient;
use App\Models\PaymentVoucher;
use App\Models\Spk;
use App\Models\PurchaseOrder;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Helpers\CustomVoid;
use App\Http\Exports\ExportExcel;
use App\Http\Helpers\CustomHelper;
use App\Models\AccountTransaction;
use App\Models\PaymentVoucherPlan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\FormaterExport;
use App\Http\Controllers\Operation\PermissionAccess;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use App\DTOs\Fa\VoucherPaymentPlanFilterData;
use App\DTOs\Fa\VoucherPaymentPlanStoreData;
use App\DTOs\Fa\VoucherPaymentPlanStoreSingleData;
use App\DTOs\Fa\VoucherPaymentPlanApproveData;
use App\DTOs\Fa\VoucherPaymentPlanBulkApproveData;
use App\DTOs\Fa\VoucherPaymentPlanBulkDeleteData;
use App\Services\Fa\VoucherPaymentPlanService;
use App\Repositories\Fa\VoucherPaymentPlanRepository;
use App\Http\Requests\Fa\VoucherPaymentPlanRequest;

class VoucherPaymentPlanCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    // use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;
    use FormaterExport;

    public function setup()
    {
        CRUD::setModel(PaymentVoucher::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/voucher-payment-plan');
        CRUD::setEntityNameStrings(trans('backpack::crud.menu.voucher_payment_plan'), trans('backpack::crud.menu.voucher_payment_plan'));


        $viewMenu = [
            'MENU INDEX RENCANA PEMBAYARAN',
        ];

        $this->settingPermission([
            'approve' => ["APPROVE RENCANA BAYAR"],
            'create' => [
                'CREATE INDEX RENCANA PEMBAYARAN',
            ],
            'update' => [
                'UPDATE INDEX RENCANA PEMBAYARAN',
            ],
            'delete' => [
                'DELETE INDEX RENCANA PEMBAYARAN',
            ],
            'list' => $viewMenu,
            'show' => $viewMenu,
            'print' => true,
        ]);
    }

    function total_voucher()
    {
        $dto = VoucherPaymentPlanFilterData::fromRequest(request());
        $repository = new VoucherPaymentPlanRepository();
        return response()->json($repository->getTotalVoucher($dto));
    }

    function index()
    {
        $this->crud->hasAccessOrFail('list');

        $columns = [
            [
                'name'      => 'row_number',
                'type'      => 'row_number',
                'label'     => 'No',
                'orderable' => false,
                'searchable' => false,
            ],
            [
                'name'      => 'bulk_action_all',
                'type'      => 'text',
                'label'     => '<input type="checkbox" class="form-check-input bulk_all_checkbox">',
                'orderable' => false,
                'searchable' => false,
                'visibleInExport' => false,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.bank.label'),
                'type'      => 'text',
                'name'      => 'bank_name',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.number_account.label'),
                'type'      => 'text',
                'name'      => 'bank_account',
                'orderable' => true,
            ],
            [
                'label' => 'A/N Rekening',
                'type'      => 'text',
                'name'      => 'account_holder_name',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.payment_transfer.label'),
                'type'      => 'text',
                'name'      => 'payment_transfer',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.no_voucher.label'),
                'type'      => 'text',
                'name'      => 'no_voucher',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.bill_number.label'),
                'type'      => 'text',
                'name'      => 'bill_number',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.payment_description.label'),
                'type'      => 'text',
                'name'      => 'payment_description',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.no_po_spk.label'),
                'type'      => 'text',
                'name'      => 'reference_id',
                'orderable' => false,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.factur_status.label'),
                'type'      => 'text',
                'name'      => 'factur_status',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.job_name.label'),
                'type'      => 'text',
                'name'      => 'job_name',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.due_date.label_2'),
                'type'      => 'text',
                'name'      => 'due_date',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.payment_type.label'),
                'type'      => 'text',
                'name'      => 'payment_type',
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
            [
                'name' => 'action',
                'type' => 'action',
                'label' =>  '',
            ]
        ];

        $columns_subkon = [
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
                'label' => trans('backpack::crud.voucher.column.voucher.bussines_entity_name.label'),
                'name' => 'subkon_id',
                'type'  => 'text',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.bill_number.label'),
                'name' => 'bill_number',
                'type' => 'text',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.payment_description.label'),
                'name' => 'payment_description',
                'type' => 'text',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.no_po_spk.label'),
                'name' => 'reference_id',
                'type'  => 'text',
                'orderable' => false,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.total.label'),
                'name' => 'total',
                'type' => 'text',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.job_name.label'),
                'name' => 'job_name',
                'type' => 'text',
                'orderable' => true,
            ],
            [
                'label' => trans('backpack::crud.voucher.column.voucher.due_date.label_2'),
                'name' => 'due_date',
                'type' => 'text',
                'orderable' => true,
            ],
        ];

        if (backpack_user()->hasRole('Super Admin')) {
            array_splice($columns, 2, 0, [[
                'label' => trans('backpack::crud.subkon.column.company'),
                'name' => 'company_name',
                'type' => 'text',
                'orderable' => true,
            ]]);

            array_splice($columns_subkon, 1, 0, [[
                'label' => trans('backpack::crud.subkon.column.company'),
                'name' => 'company_name',
                'type' => 'text',
                'orderable' => true,
            ]]);
        }

        $this->card->addCard([
            'name' => 'payment_plan',
            'line' => 'top',
            'view' => 'crud::components.card-tab',
            // 'title' => 'Rencana Pembayaran',
            'params' => [
                'tabs' => [
                    [
                        'name' => 'voucher_payment_plan_all',
                        'label' => 'Data',
                        'view' => 'crud::components.datatable',
                        'active' => true,
                        'params' => [
                            'filter' => true,
                            'crud_custom' => $this->crud,
                            'columns' => $columns,
                            'route' => backpack_url('voucher-payment-plan/search?tab=voucher_payment_plan_all&type=all'),
                            'route_export_pdf' => url($this->crud->route . '/export-pdf?tab=voucher_payment_plan_all'),
                            'title_export_pdf' => 'Voucher-payment-plan-all.pdf',
                            'route_export_excel' => url($this->crud->route . '/export-excel?tab=voucher_payment_plan_all'),
                            'title_export_excel' => 'Voucher-payment-plan-all.xlsx',
                        ]
                    ],
                    [
                        'name' => 'voucher_payment_plan_non_rutin',
                        'label' => 'Non Rutin',
                        'view' => 'crud::components.datatable',
                        'active' => false,
                        'params' => [
                            'filter' => true,
                            'crud_custom' => $this->crud,
                            'columns' => $columns_subkon,
                            'route' => backpack_url('voucher-payment-plan/search?tab=voucher_payment_plan_non_rutin&type=NON RUTIN'),
                            'route_export_pdf' => url($this->crud->route . '/export-pdf?tab=voucher_payment_plan_non_rutin'),
                            'title_export_pdf' => 'Voucher-payment-plan-non-rutin.pdf',
                            'route_export_excel' => url($this->crud->route . '/export-excel?tab=voucher_payment_plan_non_rutin'),
                            'title_export_excel' => 'Voucher-payment-plan-non-rutin.xlsx',
                        ]
                    ],
                    [
                        'name' => 'voucher_payment_plan_subkon',
                        'label' => 'Subkon',
                        'view' => 'crud::components.datatable',
                        'active' => false,
                        'params' => [
                            'filter' => true,
                            'crud_custom' => $this->crud,
                            'columns' => $columns_subkon,
                            'route' => backpack_url('voucher-payment-plan/search?tab=voucher_payment_plan_subkon&type=SUBKON'),
                            'route_export_pdf' => url($this->crud->route . '/export-pdf?tab=voucher_payment_plan_subkon'),
                            'title_export_pdf' => 'Voucher-payment-plan-subkon.pdf',
                            'route_export_excel' => url($this->crud->route . '/export-excel?tab=voucher_payment_plan_subkon'),
                            'title_export_excel' => 'Voucher-payment-plan-subkon.xlsx',
                        ]
                    ]
                ]
            ]
        ]);

        $this->card->addCard([
            'name' => 'voucher-payment-plugin',
            'line' => 'top',
            'view' => 'crud::components.voucher-payment-plan-plugin',
            'parent_view' => 'crud::components.filter-parent',
            'params' => [],
        ]);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.voucher_payment.title_modal_create');
        $this->data['title_modal_edit'] = trans('backpack::crud.voucher_payment.title_modal_edit');
        $this->data['title_modal_delete'] = trans('backpack::crud.voucher_payment.title_modal_delete');
        $this->data['cards'] = $this->card;

        $breadcrumbs = [
            'FA' => backpack_url('fa'),
            trans('backpack::crud.voucher_payment.title_header') => backpack_url($this->crud->route)
        ];

        $this->data['year_options'] = CustomHelper::getYearOptions('vouchers', 'date_voucher');
        $this->data['breadcrumbs'] = $breadcrumbs;

        $list = "crud::list-blank" ?? $this->crud->getListView();
        return view($list, $this->data);
    }


    protected function setupListOperation()
    {
        $dto = VoucherPaymentPlanFilterData::fromRequest(request());
        
        $this->setupListButtons();

        if ($dto->tab == 'voucher_payment_plan_subkon' || $dto->tab == 'voucher_payment_plan_non_rutin') {
            CRUD::setModel(Voucher::class);
            CRUD::disableResponsiveTable();
            
            (new VoucherPaymentPlanRepository())->applyListQuery($this->crud->query, $dto);
            $this->setVoucherColumns();
            return;
        }

        CRUD::setModel(PaymentVoucher::class);
        $this->setPlanBulkActions();
        
        (new VoucherPaymentPlanRepository())->applyListQuery($this->crud->query, $dto);
        $this->setPlanColumns();
    }

    private function setupListButtons()
    {
        CRUD::removeButton('delete');
        CRUD::addButtonFromView('top', 'filter_year', 'filter-year', 'beginning');
        CRUD::addButtonFromView('top', 'bulk-actions-payment-plan', 'bulk-actions-payment-plan', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf', 'export-pdf', 'end');
        CRUD::addButtonFromView('top', 'export-excel', 'export-excel', 'end');
        CRUD::addButtonFromView('line', 'delete-payment-plan', 'delete-payment-plan', 'beginning');
    }

    private function setPlanBulkActions()
    {
        $this->crud->set('is_approver', backpack_user()->hasPermissionTo('APPROVE RENCANA BAYAR'));
        $this->crud->set('has_bulk_delete', $this->crud->hasAccess('delete'));
    }

    private function setVoucherColumns()
    {
        $status_file = strpos(url()->current(), 'export-excel') ? 'excel' : 'pdf';
        $new_format_date = 'DD/MM/YYYY';
        $wrap_length = (in_array($status_file, ['excel', 'pdf'])) ? ['width_box' => '100%'] : [];

        CRUD::addColumn([
            'name'      => 'row_number',
            'type'      => 'row_number',
            'label'     => 'No',
            'orderable' => false,
        ])->makeFirstColumn();
        
        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::addColumn([
                'label' => trans('backpack::crud.subkon.column.company'),
                'name'  => 'company_name',
                'type'  => 'text',
            ])->afterColumn('row_number');
        }

        CRUD::addColumn([
            'label' => trans('backpack::crud.voucher.column.voucher.no_voucher.label'),
            'type'  => 'text',
            'name'  => 'no_voucher',
        ]);

        CRUD::addColumn([
            'label' => trans('backpack::crud.voucher.column.voucher.bussines_entity_name.label'),
            'name'  => 'subkon_id',
            'type'  => 'closure',
            'function' => fn($entry) => $entry?->subkon?->name
        ]);

        CRUD::addColumn([
            'label' => trans('backpack::crud.voucher.column.voucher.bill_number.label'),
            'name'  => 'bill_number',
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'label' => trans('backpack::crud.voucher.column.voucher.payment_description.label'),
            'name'  => 'payment_description',
            'type'  => 'wrap_text',
            ...$wrap_length,
        ]);

        CRUD::addColumn([
            'label' => trans('backpack::crud.voucher.column.voucher.no_po_spk.label'),
            'name'  => 'reference_id',
            'type'  => 'closure',
            'function' => function ($entry) {
                if ($entry->reference_type == Spk::class) {
                    return $entry?->reference?->no_spk;
                }
                return $entry?->reference?->po_number;
            }
        ]);

        CRUD::addColumn([
            'label' => trans('backpack::crud.voucher.column.voucher.total.label'),
            'name'  => 'total',
            'type'  => 'closure',
            'function' => fn($entry) => $this->priceFormatExport($status_file, $entry->total),
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
            'orderable'     => false,
        ]);

        CRUD::addColumn([
            'label' => trans('backpack::crud.voucher.column.voucher.job_name.label'),
            'name'  => 'job_name',
            'type'  => 'wrap_text',
            ...$wrap_length,
        ]);

        CRUD::addColumn([
            'label'  => trans('backpack::crud.voucher.column.voucher.due_date.label_2'),
            'name'   => 'due_date',
            'type'   => 'date',
            'format' => $new_format_date,
        ]);
    }

    private function setPlanColumns()
    {
        $settings = Setting::first();
        $new_format_date = 'DD/MM/YYYY';

        CRUD::addColumn([
            'name'      => 'row_number',
            'type'      => 'row_number',
            'label'     => 'No',
            'orderable' => false,
            'wrapper'   => ['element' => 'strong']
        ]);

        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::addColumn([
                'label' => trans('backpack::crud.subkon.column.company'),
                'name'  => 'company_name',
                'type'  => 'text',
            ])->afterColumn('row_number');
        }

        CRUD::addColumn([
            'name'     => 'bulk_checkbox',
            'label'    => '<input type="checkbox" id="bulk-select-all" class="bulk-checkbox-header" />',
            'type'     => 'custom_html',
            'value'    => function ($entry) {
                $noApprv = $entry->user_live_no_apprv ?? '';
                $userId = $entry->user_live_user_id ?? '';
                return '<input type="checkbox" class="form-check-input bulk-checkbox" data-id="' . $entry->voucer_edit_id . '" data-no-apprv="' . $noApprv . '" data-user-id="' . $userId . '" />';
            },
            'orderable'       => false,
            'searchable'      => false,
            'visibleInExport' => false,
            'escaped'         => false,
        ]);

        CRUD::addColumn([
            'label'     => trans('backpack::crud.voucher.column.voucher.bank.label'),
            'name'      => 'bank_name',
            'type'      => 'closure',
            'width_box' => '70px',
            'function'  => fn($entry) => $entry?->voucher?->subkon?->bank_name,
            'orderLogic' => function ($query, $column, $order) {
                return $query->leftJoin('subkons', 'subkons.id', '=', 'vouchers.subkon_id')
                    ->orderBy('subkons.bank_name', $order);
            }
        ]);

        CRUD::addColumn([
            'label'     => trans('backpack::crud.voucher.column.voucher.number_account.label'),
            'name'      => 'bank_account',
            'type'      => 'closure',
            'width_box' => '120px',
            'function'  => fn($entry) => $entry?->voucher?->subkon?->bank_account,
            'orderLogic' => function ($query, $column, $order) {
                return $query->leftJoin('subkons', 'subkons.id', '=', 'vouchers.subkon_id')
                    ->orderBy('subkons.bank_account', $order);
            }
        ]);

        CRUD::addColumn([
            'label'    => 'A/N Rekening',
            'name'     => 'account_holder_name',
            'type'     => 'closure',
            'function' => fn($entry) => $entry->account_holder_name ?? $entry?->voucher?->reference?->subkon?->account_holder_name,
        ]);

        CRUD::addColumn([
            'label'         => trans('backpack::crud.voucher.column.voucher.payment_transfer.label'),
            'name'          => 'payment_transfer',
            'type'          => 'number',
            'prefix'        => $settings?->currency_symbol ?? "Rp.",
            'decimals'      => 2,
            'dec_point'     => ',',
            'thousands_sep' => '.',
            'orderLogic'    => fn($query, $column, $order) => $query->orderBy('vouchers.payment_transfer', $order)
        ]);

        CRUD::addColumn([
            'label'      => 'No. Voucher',
            'name'       => 'no_voucher',
            'type'       => 'text',
            'orderLogic' => fn($query, $column, $order) => $query->orderBy('vouchers.no_voucher', $order)
        ]);

        CRUD::addColumn([
            'label' => trans('backpack::crud.voucher.column.voucher.bill_number.label'),
            'name'  => 'bill_number',
            'type'  => 'text',
        ]);

        CRUD::addColumn([
            'label' => trans('backpack::crud.voucher.column.voucher.payment_description.label'),
            'name'  => 'payment_description',
            'type'  => 'wrap_text',
        ]);

        CRUD::addColumn([
            'label'    => trans('backpack::crud.voucher.column.voucher.no_po_spk.label'),
            'name'     => 'reference_id',
            'type'     => 'closure',
            'function' => function ($entry) {
                if ($entry?->voucher?->reference_type == 'App\Models\Spk') {
                    return $entry?->voucher?->reference?->no_spk;
                }
                return $entry?->voucher?->reference?->po_number;
            },
        ]);

        CRUD::addColumn([
            'label'      => trans('backpack::crud.voucher.column.voucher.factur_status.label'),
            'name'       => 'factur_status',
            'type'       => 'text',
            'orderLogic' => fn($query, $column, $order) => $query->orderBy('vouchers.factur_status', $order)
        ]);

        CRUD::addColumn([
            'label' => trans('backpack::crud.voucher.column.voucher.job_name.label'),
            'name'  => 'job_name',
            'type'  => 'wrap_text',
        ]);

        CRUD::addColumn([
            'label'      => trans('backpack::crud.voucher.column.voucher.due_date.label_2'),
            'name'       => 'due_date',
            'type'       => 'date',
            'format'     => $new_format_date,
            'orderLogic' => fn($query, $column, $order) => $query->orderBy('vouchers.due_date', $order)
        ]);

        CRUD::addColumn([
            'label'      => trans('backpack::crud.voucher.column.voucher.payment_type.label'),
            'name'       => 'payment_type',
            'type'       => 'text',
            'orderLogic' => fn($query, $column, $order) => $query->orderBy('vouchers.payment_type', $order)
        ]);

        CRUD::addColumn([
            'label' => trans('backpack::crud.voucher.column.voucher.status.label'),
            'name'  => 'status',
            'type'  => 'approval-voucher',
        ]);

        CRUD::addColumn([
            'name'  => 'user_approval',
            'label' => trans('backpack::crud.voucher.column.voucher.user_approval.label'),
            'type'  => 'custom_html',
            'value' => function ($entry) {
                $approvals = Approval::where('model_type', PaymentVoucherPlan::class)
                    ->where('model_id', $entry->voucer_edit_id)
                    ->orderBy('no_apprv', 'ASC')
                    ->get();
                return "<ul>" . $approvals->map(function ($item) {
                    $class = ($item->status == Approval::APPROVED) ? 'text-success' : '';
                    return "<li class='{$class}'>" . $item->user->name . "</li>";
                })->implode('') . "</ul>";
            },
        ]);
    }

    private function setupListExport()
    {
        $settings = Setting::first();
        CRUD::setModel(PaymentVoucher::class);
        CRUD::disableResponsiveTable();
        CRUD::addButtonFromView('line', 'approve_payment', 'approve_payment', 'end');

        $user_id = backpack_user()->id;
        $user_approval = \App\Models\User::permission(['APPROVE RENCANA BAYAR'])
            ->where('id', $user_id)
            ->get();

        $p_v_p = DB::table('payment_voucher_plan')
            ->select(DB::raw('MAX(id) as id'), 'payment_voucher_id')
            ->groupBy('payment_voucher_id');

        $this->crud->query = $this->crud->query
            ->leftJoin('vouchers', 'vouchers.id', '=', 'payment_vouchers.voucher_id')
            ->leftJoinSub($p_v_p, 'p_v_p', function ($join) {
                $join->on('p_v_p.payment_voucher_id', '=', 'payment_vouchers.id');
            })
            ->leftJoin('payment_voucher_plan', 'payment_voucher_plan.id', '=', 'p_v_p.id');

        if ($user_approval->count() > 0) {
            $this->crud->query = $this->crud->query
                ->leftJoin('approvals', function ($join) use ($user_id) {
                    $join->on('approvals.model_id', '=', 'payment_voucher_plan.id')
                        ->where('approvals.model_type', 'App\\Models\\PaymentVoucherPlan')
                        ->where('approvals.user_id', $user_id);
                });
        } else {
            $a_p = DB::table('approvals')
                ->select(DB::raw('MAX(id) as id'), 'model_type', 'model_id')
                ->groupBy('model_type', 'model_id');

            $this->crud->query = $this->crud->query
                ->leftJoinSub($a_p, 'a_p', function ($join) {
                    $join->on('a_p.model_id', '=', 'payment_voucher_plan.id')
                        ->where('a_p.model_type', '=', DB::raw('"App\\\\Models\\\\PaymentVoucherPlan"'));
                })
                ->leftJoin('approvals', 'approvals.id', '=', 'a_p.id');
        }

        $this->crud->query = $this->crud->query
            ->leftJoin('spk', function ($join) {
                $join->on('spk.id', '=', 'vouchers.reference_id')
                    ->where('vouchers.reference_type', '=', DB::raw('"App\\\\Models\\\\Spk"'));
            })
            ->leftJoin('purchase_orders', function ($join) {
                $join->on('purchase_orders.id', '=', 'vouchers.reference_id')
                    ->where('vouchers.reference_type', '=', DB::raw('"App\\\\Models\\\\PurchaseOrder"'));
            });

        if (request()->has('filter_year') && request()->filter_year != 'all') {
            $this->crud->query = $this->crud->query->whereYear('vouchers.date_voucher', request()->filter_year);
        }

        CRUD::addClause('select', [
            DB::raw("
                    vouchers.*,
                    spk.no_spk as spk_no,
                    purchase_orders.po_number as po_no,
                    approvals.approved_at as approval_approved_at,
                    approvals.status as approval_status,
                    approvals.user_id as approval_user_id,
                    approvals.no_apprv as approval_no_apprv,
                    payment_voucher_plan.id as voucer_edit_id,
                    payment_vouchers.voucher_id,
                    payment_vouchers.id as payment_voucher_id
                ")
        ]);

        $request = request();

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
            'orderLogic' => function ($query, $column, $order) {
                return $query->orderBy('vouchers.no_voucher', $order);
            }
        ]);

        CRUD::column(
            [
                'label' => trans('backpack::crud.voucher.column.voucher.bussines_entity_name.label'),
                'name' => 'subkon_id',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry?->voucher?->subkon?->name;
                },
                'orderLogic' => function ($query, $column, $order) {
                    return $query->leftJoin('subkons', 'subkons.id', '=', 'vouchers.subkon_id')
                        ->orderBy('subkons.name', $order);
                }
            ],
        );

        CRUD::column(
            [
                'label' => trans('backpack::crud.voucher.column.voucher.bank.label'),
                'name' => 'bank_name',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry?->voucher?->subkon?->bank_name;
                },
                'orderLogic' => function ($query, $column, $order) {
                    return $query->leftJoin('subkons', 'subkons.id', '=', 'vouchers.subkon_id')
                        ->orderBy('subkons.bank_name', $order);
                }
            ],
        );

        CRUD::column(
            [
                'label' => trans('backpack::crud.voucher.column.voucher.number_account.label'),
                'name' => 'bank_account',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry?->voucher?->subkon?->bank_account;
                },
                'orderLogic' => function ($query, $column, $order) {
                    return $query->leftJoin('subkons', 'subkons.id', '=', 'vouchers.subkon_id')
                        ->orderBy('subkons.bank_account', $order);
                }
            ],
        );

        CRUD::column(
            [
                'label' => trans('backpack::crud.voucher.column.voucher.bill_number.label'),
                'name' => 'bill_number',
                'type'  => 'text',
            ],
        );

        CRUD::column(
            [
                'label' => trans('backpack::crud.voucher.column.voucher.payment_description.label'),
                'name' => 'payment_description',
                'type'  => 'text',
            ],
        );

        CRUD::column(
            [
                'label' => trans('backpack::crud.voucher.column.voucher.no_po_spk.label'),
                'name' => 'reference_id',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry?->voucher?->reference?->po_number;
                },
            ], // BELUM FILTER
        );

        CRUD::column([
            'label' => trans('backpack::crud.voucher.column.voucher.payment_transfer.label_2'),
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

        CRUD::column(
            [
                'label' => trans('backpack::crud.voucher.column.voucher.factur_status.label'),
                'name' => 'factur_status',
                'type'  => 'text',
                'orderLogic' => function ($query, $column, $order) {
                    return $query->orderBy('vouchers.factur_status', $order);
                }
            ],
        );

        CRUD::column(
            [
                'label' => trans('backpack::crud.voucher.column.voucher.job_name.label'),
                'name' => 'job_name',
                'type'  => 'closure',
                'function' => function ($entry) {
                    return $entry?->voucher?->reference?->job_name;
                },
            ], // BELUM FILTER
        );

        CRUD::column([
            'label' => trans('backpack::crud.voucher.column.voucher.due_date.label_2'),
            'name' => 'due_date',
            'type'  => 'date',
            'format' => 'D MMM Y',
            'orderLogic' => function ($query, $column, $order) {
                return $query->orderBy('vouchers.due_date', $order);
            }
        ]);

        CRUD::column(
            [
                'label' => trans('backpack::crud.voucher.column.voucher.payment_type.label'),
                'name' => 'payment_type',
                'type'  => 'text',
                'orderLogic' => function ($query, $column, $order) {
                    return $query->orderBy('vouchers.payment_type', $order);
                }
            ],
        );
    }

    public function searchDummy()
    {
        // === DUMMY DATA (hapus block ini untuk production) ===
        $draw = (int) request()->input('draw', 1);
        $dummyRows = [];
        $dummyData = [
            ['VCR-2026-001', 'PT Subkon Abadi', 'Bank Mandiri', '1234567890', 'INV-001', 'Pembayaran Termin 1', 'PO-2026-001', 50000000, 'Faktur Pajak', 'Gedung A Lt.3', '2026-03-15', 'Transfer'],
            ['VCR-2026-002', 'CV Maju Jaya', 'Bank BCA', '9876543210', 'INV-002', 'Pembayaran Material', 'SPK-2026-005', 32500000, 'Non Faktur', 'Jembatan Kali Besar', '2026-03-20', 'Transfer'],
            ['VCR-2026-003', 'PT Bangun Karya', 'Bank BRI', '5551234567', 'INV-003', 'Termin 2 Pekerjaan Sipil', 'PO-2026-008', 78000000, 'Faktur Pajak', 'Rumah Sakit Baru', '2026-04-01', 'Giro'],
            ['VCR-2026-004', 'PT Sumber Rejeki', 'Bank Mandiri', '7778889990', 'INV-004', 'Pembayaran Sewa Alat', 'SPK-2026-012', 15000000, 'Non Faktur', 'Proyek Tol Selatan', '2026-03-25', 'Transfer'],
            ['VCR-2026-005', 'CV Teknik Andal', 'Bank BNI', '3334445556', 'INV-005', 'Termin 3 Finishing', 'PO-2026-015', 42000000, 'Faktur Pajak', 'Apartemen Citra', '2026-04-10', 'Transfer'],
            ['VCR-2026-001', 'PT Subkon Abadi', 'Bank Mandiri', '1234567890', 'INV-001', 'Pembayaran Termin 1', 'PO-2026-001', 50000000, 'Faktur Pajak', 'Gedung A Lt.3', '2026-03-15', 'Transfer'],
            ['VCR-2026-002', 'CV Maju Jaya', 'Bank BCA', '9876543210', 'INV-002', 'Pembayaran Material', 'SPK-2026-005', 32500000, 'Non Faktur', 'Jembatan Kali Besar', '2026-03-20', 'Transfer'],
            ['VCR-2026-003', 'PT Bangun Karya', 'Bank BRI', '5551234567', 'INV-003', 'Termin 2 Pekerjaan Sipil', 'PO-2026-008', 78000000, 'Faktur Pajak', 'Rumah Sakit Baru', '2026-04-01', 'Giro'],
            ['VCR-2026-004', 'PT Sumber Rejeki', 'Bank Mandiri', '7778889990', 'INV-004', 'Pembayaran Sewa Alat', 'SPK-2026-012', 15000000, 'Non Faktur', 'Proyek Tol Selatan', '2026-03-25', 'Transfer'],
            ['VCR-2026-005', 'CV Teknik Andal', 'Bank BNI', '3334445556', 'INV-005', 'Termin 3 Finishing', 'PO-2026-015', 42000000, 'Faktur Pajak', 'Apartemen Citra', '2026-04-10', 'Transfer'],
            ['VCR-2026-001', 'PT Subkon Abadi', 'Bank Mandiri', '1234567890', 'INV-001', 'Pembayaran Termin 1', 'PO-2026-001', 50000000, 'Faktur Pajak', 'Gedung A Lt.3', '2026-03-15', 'Transfer'],
            ['VCR-2026-002', 'CV Maju Jaya', 'Bank BCA', '9876543210', 'INV-002', 'Pembayaran Material', 'SPK-2026-005', 32500000, 'Non Faktur', 'Jembatan Kali Besar', '2026-03-20', 'Transfer'],
            ['VCR-2026-003', 'PT Bangun Karya', 'Bank BRI', '5551234567', 'INV-003', 'Termin 2 Pekerjaan Sipil', 'PO-2026-008', 78000000, 'Faktur Pajak', 'Rumah Sakit Baru', '2026-04-01', 'Giro'],
            ['VCR-2026-004', 'PT Sumber Rejeki', 'Bank Mandiri', '7778889990', 'INV-004', 'Pembayaran Sewa Alat', 'SPK-2026-012', 15000000, 'Non Faktur', 'Proyek Tol Selatan', '2026-03-25', 'Transfer'],
            ['VCR-2026-005', 'CV Teknik Andal', 'Bank BNI', '3334445556', 'INV-005', 'Termin 3 Finishing', 'PO-2026-015', 42000000, 'Faktur Pajak', 'Apartemen Citra', '2026-04-10', 'Transfer'],
            ['VCR-2026-001', 'PT Subkon Abadi', 'Bank Mandiri', '1234567890', 'INV-001', 'Pembayaran Termin 1', 'PO-2026-001', 50000000, 'Faktur Pajak', 'Gedung A Lt.3', '2026-03-15', 'Transfer'],
            ['VCR-2026-002', 'CV Maju Jaya', 'Bank BCA', '9876543210', 'INV-002', 'Pembayaran Material', 'SPK-2026-005', 32500000, 'Non Faktur', 'Jembatan Kali Besar', '2026-03-20', 'Transfer'],
            ['VCR-2026-003', 'PT Bangun Karya', 'Bank BRI', '5551234567', 'INV-003', 'Termin 2 Pekerjaan Sipil', 'PO-2026-008', 78000000, 'Faktur Pajak', 'Rumah Sakit Baru', '2026-04-01', 'Giro'],
            ['VCR-2026-004', 'PT Sumber Rejeki', 'Bank Mandiri', '7778889990', 'INV-004', 'Pembayaran Sewa Alat', 'SPK-2026-012', 15000000, 'Non Faktur', 'Proyek Tol Selatan', '2026-03-25', 'Transfer'],
            ['VCR-2026-005', 'CV Teknik Andal', 'Bank BNI', '3334445556', 'INV-005', 'Termin 3 Finishing', 'PO-2026-015', 42000000, 'Faktur Pajak', 'Apartemen Citra', '2026-04-10', 'Transfer'],
            ['VCR-2026-004', 'PT Sumber Rejeki', 'Bank Mandiri', '7778889990', 'INV-004', 'Pembayaran Sewa Alat', 'SPK-2026-012', 15000000, 'Non Faktur', 'Proyek Tol Selatan', '2026-03-25', 'Transfer'],
            ['VCR-2026-005', 'CV Teknik Andal', 'Bank BNI', '3334445556', 'INV-005', 'Termin 3 Finishing', 'PO-2026-015', 42000000, 'Faktur Pajak', 'Apartemen Citra', '2026-04-10', 'Transfer'],
        ];

        $start = (int) request()->input('start');
        $length = (int) request()->input('length');

        $dataset = collect($dummyData);

        $total = $dataset->count();

        if ($start) {
            $dataset = $dataset->skip($start);
        }

        if ($length) {
            $dataset = $dataset->take($length);
        }

        foreach ($dataset->all() as $i => $d) {
            $fakeId = $i + 101; // fake voucer_edit_id
            $checkbox = '<input type="checkbox" class="form-check-input bulk-checkbox" data-id="' . $fakeId . '" data-no-apprv="1" />';
            $statusBadge = ($i % 2 == 0)
                ? '<span class="badge bg-warning text-dark">Pending</span>'
                : '<span class="badge bg-success">Approved</span>';
            $approvalList = '<ul><li class="text-success">Admin 1</li><li>Admin 2</li></ul>';
            $actionButtons = '<a href="javascript:void(0)" class="btn btn-sm btn-link"><i class="la la-trash"></i></a>';

            $dummyRows[] = [
                $checkbox,                                          // bulk_checkbox
                '<strong>' . ($i + 1) . '</strong>',               // row_number
                $d[0],                                              // no_voucher
                $d[1],                                              // bussines_entity_name
                $d[2],                                              // bank
                $d[3],                                              // number_account
                $d[4],                                              // bill_number
                $d[5],                                              // payment_description
                $d[6],                                              // no_po_spk
                number_format($d[7], 0, ',', '.'),                  // payment_transfer
                $d[8],                                              // factur_status
                $d[9],                                              // job_name
                $d[10],                                             // due_date
                $d[11],                                             // payment_type
                $statusBadge,                                       // status
                $approvalList,                                      // user_approval
                $actionButtons,                                     // action
            ];
        }

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $total,
            'recordsFiltered' => $total,
            'data' => $dummyRows,
        ]);
        // === END DUMMY DATA ===
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

    private function ruleValidation()
    {
        return [
            'voucher' => [
                'required',
                'array',
                'min:1',
                function ($attr, $value, $fail) {
                    foreach ($value as $id_voucher) {
                        $payment_voucher = PaymentVoucher::where('voucher_id', $id_voucher)->first();
                        if ($payment_voucher != null) {
                            $fail(trans('backpack::crud.voucher_payment.voucher_payment_exists'));
                        }
                    }
                }
            ],
        ];
    }

    protected function setupCreateOperation()
    {
        CRUD::setValidation(VoucherPaymentPlanRequest::class);

        CRUD::addField([
            'name'  => 'voucher',
            'label' => '',
            'type'  => 'voucher-list-ajax',
        ]);
    }

    public function datatableVoucher()
    {
        $dto = VoucherPaymentPlanFilterData::fromRequest(request());
        $repository = new VoucherPaymentPlanRepository();
        return response()->json($repository->getDatatableVoucher($dto));
    }



    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        $this->crud->validateRequest();
        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {
            $dto   = VoucherPaymentPlanStoreData::fromRequest(request());
            $event = (new VoucherPaymentPlanService())->store($dto);

            \Alert::success(trans('backpack::crud.insert_success'))->flash();
            $this->crud->setSaveAction();

            DB::commit();
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data'    => [],
                    'events'  => $event,
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function storeSingle()
    {
        $this->crud->hasAccessOrFail('create');
        request()->validate([
            'id' => 'required|exists:vouchers,id',
        ]);

        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {
            $dto   = VoucherPaymentPlanStoreSingleData::fromRequest(request());
            $event = (new VoucherPaymentPlanService())->storeSingle($dto);

            \Alert::success(trans('backpack::crud.insert_success'))->flash();
            $this->crud->setSaveAction();

            DB::commit();
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data'    => [],
                    'events'  => $event,
                ]);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function approvedStore($id)
    {
        $this->crud->hasAccessOrFail('create');
        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {
            $dto   = VoucherPaymentPlanApproveData::fromRequest(request());
            $event = (new VoucherPaymentPlanService())->approvedStore((int) $id, $dto);

            DB::commit();
            return response()->json([
                'success' => true,
                'events'  => $event,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status'  => false,
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $this->crud->hasAccessOrFail('delete');

            $event    = (new VoucherPaymentPlanService())->destroy((int) $id);
            $messages = [
                'success' => [trans('backpack::crud.delete_confirmation_message')],
                'events'  => $event,
            ];

            DB::commit();
            return response()->json($messages);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'type'    => 'errors',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function bulkApprove()
    {
        $this->crud->hasAccessOrFail('create');

        if (!backpack_user()->hasPermissionTo('APPROVE RENCANA BAYAR')) {
            return response()->json([
                'success' => false,
                'error'   => trans('backpack::crud.voucher_payment_plan.error_no_permission'),
            ], 403);
        }

        $dto = VoucherPaymentPlanBulkApproveData::fromRequest(request());

        if (empty($dto->entries)) {
            return response()->json([
                'success' => false,
                'error'   => trans('backpack::crud.voucher_payment_plan.error_no_entries'),
            ]);
        }

        DB::beginTransaction();
        try {
            $result = (new VoucherPaymentPlanService())->bulkApprove($dto);

            DB::commit();
            return response()->json([
                'success'        => true,
                'approved_count' => $result['approved_count'],
                'events'         => $result['event'],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ]);
        }
    }

    public function bulkDelete()
    {
        $this->crud->hasAccessOrFail('delete');

        $dto = VoucherPaymentPlanBulkDeleteData::fromRequest(request());

        if (empty($dto->entries)) {
            return response()->json([
                'success' => false,
                'error'   => trans('backpack::crud.voucher_payment_plan.error_no_entries'),
            ]);
        }

        DB::beginTransaction();
        try {
            $result = (new VoucherPaymentPlanService())->bulkDelete($dto);

            DB::commit();
            return response()->json([
                'success'       => true,
                'deleted_count' => $result['deleted_count'],
                'events'        => $result['event'],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }



    public function addTransaction($voucher_id)
    {
        $voucher = Voucher::find($voucher_id);
        $po = $voucher->reference;
        $po_type = $voucher->reference_type;
        $invoice = InvoiceClient::where('client_po_id', $voucher->client_po_id)->first();
        $client_po = $voucher->client_po;

        if ($client_po->status == 'TANPA PO') {
            // ada po
            $account = Account::where('code', "50222")->first();
            $price_general_loan = $po->load_general_value;
            CustomHelper::updateOrCreateJournalEntry([
                'account_id' => $account->id,
                'reference_id' => $po->id,
                'reference_type' => $po_type,
                'description' => "Transaksi tanpa PO " . $po->work_code,
                'date' => Carbon::now(),
                'debit' => $price_general_loan,
                // 'credit' => ($status == CastAccount::OUT) ? $nominal_transaction : 0,
            ], [
                'account_id' => $account->id,
                'reference_id' => $po->id,
                'reference_type' => $po_type,
            ]);
        }

        // periksa jenis voucher
        if ($voucher->reference_type == "App\Models\PurchaseOrder") {
            if ($invoice == null) {
                $account = Account::where('code', "504")->first();
                $payment_transfer = $voucher->payment_transfer;
                CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $account->id,
                    'reference_id' => $voucher_id,
                    'reference_type' => Voucher::class,
                    'description' => "Beban dalam proses pekerjaan voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'debit' => $payment_transfer,
                    'credit' => 0,
                    // 'credit' => ($status == CastAccount::OUT) ? $nominal_transaction : 0,
                ], [
                    'account_id' => $account->id,
                    'reference_id' => $voucher_id,
                    'reference_type' => Voucher::class,
                ]);
            } else {
                $account = Account::where('code', "501")->first();
                $payment_transfer = $voucher->payment_transfer;

                $invoice->status = 'Paid';
                $invoice->save();

                $transaksi = new AccountTransaction;
                $transaksi->cast_account_id = $voucher->account_source_id;
                $transaksi->reference_type = Voucher::class;
                $transaksi->reference_id = $voucher_id;
                $transaksi->date_transaction = Carbon::now()->format('Y-m-d');
                $transaksi->nominal_transaction = $payment_transfer;
                $transaksi->total_saldo_before = 0;
                $transaksi->total_saldo_after = 0;
                $transaksi->status = CastAccount::ENTER;
                $transaksi->kdp = $client_po?->work_code;
                $transaksi->job_name = $voucher?->job_name;
                $transaksi->save();

                CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $account->id,
                    'reference_id' => $transaksi->id,
                    'reference_type' => AccountTransaction::class,
                    'description' => $transaksi->kdp,
                    'date' => Carbon::now(),
                    'debit' => $payment_transfer,
                    'credit' => 0,
                    // 'credit' => ($status == CastAccount::OUT) ? $nominal_transaction : 0,
                ], [
                    'account_id' => $account->id,
                    'reference_id' => $voucher_id,
                    'reference_type' => AccountTransaction::class,
                ]);
            }
        } else if ($voucher->reference_type == "App\Models\ClientPo") {
            if ($invoice == null) {
                // jika tidak ada invoice di PO
                $account = Account::where('code', "504")->first();
                $payment_transfer = $voucher->payment_transfer;
                CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $account->id,
                    'reference_id' => $voucher_id,
                    'reference_type' => Voucher::class,
                    'description' => "Beban dalam proses pekerjaan voucher " . $voucher->no_voucher,
                    'date' => Carbon::now(),
                    'debit' => $payment_transfer,
                    'credit' => 0,
                    // 'credit' => ($status == CastAccount::OUT) ? $nominal_transaction : 0,
                ], [
                    'account_id' => $account->id,
                    'reference_id' => $voucher_id,
                    'reference_type' => Voucher::class,
                ]);
            } else {
                $account = Account::where('code', "501")->first();
                $payment_transfer = $voucher->payment_transfer;

                $invoice->status = 'Paid';
                $invoice->save();

                $transaksi = new AccountTransaction;
                $transaksi->cast_account_id = $voucher->account_source_id;
                $transaksi->reference_type = Voucher::class;
                $transaksi->reference_id = $voucher_id;
                $transaksi->date_transaction = Carbon::now()->format('Y-m-d');
                $transaksi->nominal_transaction = $payment_transfer;
                $transaksi->total_saldo_before = 0;
                $transaksi->total_saldo_after = 0;
                $transaksi->status = CastAccount::ENTER;
                $transaksi->kdp = $po?->work_code;
                $transaksi->job_name = $po?->job_name;
                $transaksi->save();

                CustomHelper::updateOrCreateJournalEntry([
                    'account_id' => $account->id,
                    'reference_id' => $transaksi->id,
                    'reference_type' => AccountTransaction::class,
                    'description' => $transaksi->kdp,
                    'date' => Carbon::now(),
                    'debit' => $payment_transfer,
                    'credit' => 0,
                    // 'credit' => ($status == CastAccount::OUT) ? $nominal_transaction : 0,
                ], [
                    'account_id' => $account->id,
                    'reference_id' => $voucher_id,
                    'reference_type' => AccountTransaction::class,
                ]);
            }
        }
    }

    public function exportPdf()
    {
        $this->setupListOperation();

        CRUD::removeColumn('bulk_checkbox');
        CRUD::removeColumn('user_approval');
        CRUD::removeColumn('document_path');

        $columns = $this->crud->columns();
        $items   = $this->crud->getEntries();

        $row_number = 0;
        $all_items  = [];

        foreach ($items as $item) {
            $row_items = [];
            $row_number++;
            foreach ($columns as $column) {
                if ($column['name'] === 'row_number') {
                    $item_value = $row_number;
                } elseif ($column['name'] === 'due_date') {
                    $item_value = $item->due_date ? \Carbon\Carbon::parse($item->due_date)->format('d/m/Y') : '-';
                } else {
                    $item_value = $this->crud->getCellView($column, $item, $row_number);
                }

                $item_value = str_replace('<span>', '', $item_value);
                $item_value = str_replace('</span>', '', $item_value);
                $item_value = str_replace("\n", '', $item_value);
                $item_value = CustomHelper::clean_html($item_value);
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $title = 'VOUCHER RENCANA PEMBAYARAN';

        $pdf = Pdf::loadView('exports.table-pdf', [
            'columns' => $columns,
            'items'   => $all_items,
            'title'   => $title,
        ])->setPaper('A4', 'landscape');

        $fileName = 'vendor_po_' . now()->format('Ymd_His') . '.pdf';

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, $fileName, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportExcel()
    {
        $this->setupListOperation();

        CRUD::removeColumn('bulk_checkbox');
        CRUD::removeColumn('user_approval');
        CRUD::removeColumn('document_path');

        $columns = $this->crud->columns();
        $items   = $this->crud->getEntries();

        $row_number = 0;
        $all_items  = [];

        foreach ($items as $item) {
            $row_items = [];
            $row_number++;
            foreach ($columns as $column) {
                if ($column['name'] === 'row_number') {
                    $item_value = $row_number;
                } elseif ($column['name'] === 'payment_transfer') {
                    $item_value = $item->payment_transfer;
                } elseif ($column['name'] === 'due_date') {
                    $item_value = $item->due_date ? \Carbon\Carbon::parse($item->due_date)->format('d/m/Y') : '-';
                } else {
                    $item_value = $this->crud->getCellView($column, $item, $row_number);
                }

                $item_value = str_replace('<span>', '', $item_value);
                $item_value = str_replace('</span>', '', $item_value);
                $item_value = str_replace("\n", '', $item_value);
                $item_value = CustomHelper::clean_html($item_value);
                $row_items[] = trim($item_value);
            }
            $all_items[] = $row_items;
        }

        $name = 'VOUCHER RENCANA PEMBAYARAN';

        return response()->streamDownload(function () use ($columns, $all_items) {
            echo Excel::raw(new ExportExcel(
                $columns,
                $all_items
            ), \Maatwebsite\Excel\Excel::XLSX);
        }, $name, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
        ]);
    }
}

