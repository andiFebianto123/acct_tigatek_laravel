<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\ClientManagement\BillingReminderFilterData;
use App\DTOs\ClientManagement\BillingReminderSaveData;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\PermissionAccess;
use App\Models\BillingReminder;
use App\Repositories\ClientManagement\BillingReminderRepository;
use App\Services\ClientManagement\BillingReminderService;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingReminderCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use PermissionAccess;

    protected BillingReminderService $service;
    protected BillingReminderRepository $repository;

    public function __construct(
        BillingReminderService $service,
        BillingReminderRepository $repository
    ) {
        parent::__construct();
        $this->service = $service;
        $this->repository = $repository;
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup()
    {
        CRUD::setModel(BillingReminder::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/billing/billing-reminder');
        CRUD::setEntityNameStrings(
            trans('backpack::crud.billing_reminder.title_header') ?? 'Reminder',
            trans('backpack::crud.billing_reminder.title_header') ?? 'Reminder'
        );

        $allAccess = [
            'AKSES SEMUA MENU ACCOUNTING',
            'AKSES MENU CLIENT',
        ];

        $viewMenu = [
            'MENU INDEX CLIENT BILLING REMINDER',
        ];

        $this->settingPermission([
            'update' => [
                'UPDATE INDEX CLIENT BILLING REMINDER',
                ...$allAccess
            ],
            'list' => $viewMenu,
            'show' => $viewMenu,
        ]);
    }

    /**
     * Display the index list using custom blank page containing custom table card.
     */
    public function index()
    {
        $this->crud->hasAccessOrFail('list');

        $columns = [
            [
                'name'      => 'row_number',
                'type'      => 'row_number',
                'label'     => 'No',
                'orderable' => false,
            ],
            [
                'label' => trans('backpack::crud.billing_reminder.column.company') ?? 'Milik Perusahaan',
                'type'  => 'text',
                'name'  => 'company.name',
            ],
            [
                'name'  => 'code_billing',
                'type'  => 'text',
                'label' => trans('backpack::crud.billing_reminder.column.code_billing') ?? 'Kode Billing',
            ],
            [
                'name'     => 'total_items',
                'type'     => 'closure',
                'label'    => trans('backpack::crud.billing_reminder.column.total_items') ?? 'Jumlah Item',
                'function' => function ($entry) {
                    $dev = (int) ($entry->total_devices ?? 0);
                    $sim = (int) ($entry->total_simcards ?? 0);
                    $total = (int) ($entry->total_items ?? ($dev + $sim));

                    $details = [];
                    if ($dev > 0) $details[] = "{$dev} Device";
                    if ($sim > 0) $details[] = "{$sim} SIMCARD";

                    $sub = !empty($details) ? '<br><small class="text-muted">(' . implode(', ', $details) . ')</small>' : '';
                    return '<strong>' . $total . ' Item</strong>' . $sub;
                }
            ],
            [
                'name'     => 'installation_date',
                'type'     => 'closure',
                'label'    => trans('backpack::crud.billing_reminder.column.installation_date') ?? 'Instalasi Date',
                'function' => function ($entry) {
                    return !empty($entry->installation_date) ? Carbon::parse($entry->installation_date)->format('d/m/Y') : '-';
                }
            ],
            [
                'name'     => 'start_date',
                'type'     => 'closure',
                'label'    => trans('backpack::crud.billing_reminder.column.start_date') ?? 'Start Date',
                'function' => function ($entry) {
                    return !empty($entry->start_date) ? Carbon::parse($entry->start_date)->format('d/m/Y') : '-';
                }
            ],
            [
                'name'     => 'end_date',
                'type'     => 'closure',
                'label'    => trans('backpack::crud.billing_reminder.column.end_date') ?? 'End Date',
                'function' => function ($entry) {
                    return !empty($entry->end_date) ? Carbon::parse($entry->end_date)->format('d/m/Y') : '-';
                }
            ],
            [
                'name'     => 'period',
                'type'     => 'closure',
                'label'    => trans('backpack::crud.billing_reminder.column.period') ?? 'Periode',
                'function' => function ($entry) {
                    if (empty($entry->period)) return '-';
                    return match ($entry->period) {
                        '1_month'  => 'Sebulan',
                        '3_months' => '3 Bulan',
                        '6_months' => '6 Bulan',
                        '1_year'   => 'Setahun',
                        default    => $entry->period,
                    };
                }
            ],
            [
                'name'  => 'action',
                'type'  => 'action',
                'label' => trans('backpack::crud.actions') ?? 'Aksi',
            ]
        ];

        $this->card->addCard([
            'name'   => 'billing_reminder',
            'line'   => 'top',
            'view'   => 'crud::components.datatable-origin',
            'params' => [
                'filter'       => true,
                'crud_custom'  => $this->crud,
                'hide_title'   => true,
                'columns'      => $columns,
                'filter_table' => collect($this->crud->filters()),
                'route'        => backpack_url('/billing/billing-reminder/search'),
            ]
        ]);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? 'Reminder';
        $this->data['title_modal_create'] = trans('backpack::crud.billing_reminder.title_header') ?? 'Reminder';
        $this->data['title_modal_edit'] = trans('backpack::crud.billing_reminder.title_modal_edit') ?? 'Form Reminder';
        $this->data['title_modal_delete'] = trans('backpack::crud.billing_reminder.title_header') ?? 'Reminder';
        $this->data['cards'] = $this->card;

        $breadcrumbs = [
            'Management Billing' => backpack_url('billing/billing-reminder'),
            'Reminder'           => backpack_url($this->crud->route)
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;

        $list = "crud::list-blank" ?? $this->crud->getListView();

        return view($list, $this->data);
    }

    /**
     * Define what happens when the List operation is loaded.
     */
    protected function setupListOperation()
    {
        CRUD::disableResponsiveTable();

        $filters = BillingReminderFilterData::fromRequest(request());
        $this->crud->query = $this->repository->getFilteredData($filters);

        CRUD::addColumn([
            'name'      => 'row_number',
            'type'      => 'row_number',
            'label'     => 'No',
            'orderable' => false,
            'wrapper'   => [
                'element' => 'strong',
            ]
        ])->makeFirstColumn();

        $user = backpack_user();
        if ($user && !$user->canAccessAllCompanies()) {
            $accessibleCompanyIds = $user->getAccessibleCompanyIds();
            $this->crud->addClause('whereIn', 'items.company_id', $accessibleCompanyIds);
        }

        CRUD::column([
            'label'     => trans('backpack::crud.billing_reminder.column.company') ?? 'Milik Perusahaan',
            'type'      => 'select',
            'name'      => 'company_id',
            'entity'    => 'company',
            'attribute' => 'name',
            'model'     => "App\Models\Company",
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_reminder.column.code_billing') ?? 'Kode Billing',
            'name'  => 'code_billing',
            'type'  => 'text',
        ]);

        CRUD::column([
            'label'    => trans('backpack::crud.billing_reminder.column.total_items') ?? 'Jumlah Item',
            'name'     => 'total_items',
            'type'     => 'closure',
            'function' => function ($entry) {
                $dev = (int) ($entry->total_devices ?? 0);
                $sim = (int) ($entry->total_simcards ?? 0);
                $total = (int) ($entry->total_items ?? ($dev + $sim));

                $details = [];
                if ($dev > 0) $details[] = "{$dev} Device";
                if ($sim > 0) $details[] = "{$sim} SIMCARD";

                $sub = !empty($details) ? '<br><small class="text-muted">(' . implode(', ', $details) . ')</small>' : '';
                return '<strong>' . $total . ' Item</strong>' . $sub;
            },
            'escaped'  => false,
        ]);

        CRUD::column([
            'label'    => trans('backpack::crud.billing_reminder.column.installation_date') ?? 'Instalasi Date',
            'name'     => 'installation_date',
            'type'     => 'closure',
            'function' => function ($entry) {
                return !empty($entry->installation_date) ? Carbon::parse($entry->installation_date)->format('d/m/Y') : '-';
            },
        ]);

        CRUD::column([
            'label'    => trans('backpack::crud.billing_reminder.column.start_date') ?? 'Start Date',
            'name'     => 'start_date',
            'type'     => 'closure',
            'function' => function ($entry) {
                return !empty($entry->start_date) ? Carbon::parse($entry->start_date)->format('d/m/Y') : '-';
            },
        ]);

        CRUD::column([
            'label'    => trans('backpack::crud.billing_reminder.column.end_date') ?? 'End Date',
            'name'     => 'end_date',
            'type'     => 'closure',
            'function' => function ($entry) {
                return !empty($entry->end_date) ? Carbon::parse($entry->end_date)->format('d/m/Y') : '-';
            },
        ]);

        CRUD::column([
            'label'    => trans('backpack::crud.billing_reminder.column.period') ?? 'Periode',
            'name'     => 'period',
            'type'     => 'closure',
            'function' => function ($entry) {
                if (empty($entry->period)) return '-';
                return match ($entry->period) {
                    '1_month'  => 'Sebulan',
                    '3_months' => '3 Bulan',
                    '6_months' => '6 Bulan',
                    '1_year'   => 'Setahun',
                    default    => $entry->period,
                };
            },
        ]);

        CRUD::removeButton('update');
        CRUD::addButtonFromView('line', 'edit_reminder', 'edit_reminder', 'beginning');
    }

    /**
     * Handle the AJAX Search for DataTables.
     */
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

        if ($search && ($search['value'] ?? false)) {
            $this->crud->applySearchTerm($search['value']);
        }
        if ($start) {
            $this->crud->skip($start);
        }
        if ($length) {
            $this->crud->take($length);
        }
        $this->crud->applyDatatableOrder();

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

    /**
     * AJAX Edit Modal handler.
     */
    public function edit($id)
    {
        $this->crud->hasAccessOrFail('update');

        $codeBilling = request('code_billing');
        $companyId = request('company_id') ? (int) request('company_id') : null;

        // Try to find existing reminder by code_billing or id
        $reminder = null;
        if (!empty($codeBilling)) {
            $reminder = $this->repository->findByCodeBilling($codeBilling, $companyId);
        }
        if (!$reminder && is_numeric($id) && (int) $id > 0) {
            $reminder = BillingReminder::find($id);
            if ($reminder && empty($codeBilling)) {
                $codeBilling = $reminder->code_billing;
                $companyId = $reminder->company_id;
            }
        }

        // Aggregate info from devices and simcards if reminder not found or missing dates
        $agg = $this->repository->getAggregateForCodeBilling($codeBilling, $companyId);

        $installationDate = $reminder->installation_date 
            ?? ($agg->installation_date ?? null);

        $startDate = $reminder->start_date ?? null;
        $endDate = $reminder->end_date ?? ($agg->expired_date ?? null);

        $startDateVal = $startDate ? Carbon::parse($startDate)->format('Y-m-d') : '';
        $endDateVal = $endDate ? Carbon::parse($endDate)->format('Y-m-d') : '';
        $installationDateVal = $installationDate ? Carbon::parse($installationDate)->format('Y-m-d') : '';

        $actionUrl = backpack_url('billing/billing-reminder/save');

        $html = view('vendor.backpack.crud.forms.billing_reminder_edit', [
            'entry'             => $reminder,
            'code_billing'      => $codeBilling,
            'company_id'        => $companyId ?? ($agg->company_id ?? null),
            'installation_date' => $installationDateVal,
            'start_date_val'    => $startDateVal,
            'end_date_val'      => $endDateVal,
            'actionUrl'         => $actionUrl,
        ])->render();

        return response()->json([
            'html' => $html,
        ]);
    }

    /**
     * Save reminder setting (called via AJAX from Edit Modal).
     */
    public function save(Request $request)
    {
        $this->crud->hasAccessOrFail('update');

        $request->validate([
            'code_billing' => 'required|string',
            'start_date'   => 'nullable|date',
            'end_date'     => 'nullable|date',
            'period'       => 'nullable|string',
        ]);

        try {
            $dto = BillingReminderSaveData::fromRequest($request);
            $reminder = $this->service->saveReminder($dto);

            return response()->json([
                'status'  => true,
                'success' => true,
                'message' => trans('backpack::crud.update_success') ?? 'Pengaturan reminder berhasil disimpan.',
                'data'    => $reminder,
            ]);
        } catch (\Exception $e) {
            Log::error('BillingReminderCrudController::save error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'status'  => false,
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
}
