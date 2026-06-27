<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\PermissionAccess;
use App\Models\BillingNotification;
use App\DTOs\ClientManagement\BillingNotificationFilterData;
use App\Repositories\ClientManagement\BillingNotificationRepository;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Prologue\Alerts\Facades\Alert;

class BillingNotificationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;

    protected $repository;

    public function __construct(BillingNotificationRepository $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     */
    public function setup()
    {
        CRUD::setModel(BillingNotification::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/billing/billing-notification');
        CRUD::setEntityNameStrings(
            trans('backpack::crud.billing_notification.title_header') ?? 'Notifikasi Tagihan',
            trans('backpack::crud.billing_notification.title_header') ?? 'Notifikasi Tagihan'
        );

        $allAccess = [
            'AKSES SEMUA MENU ACCOUNTING',
            'AKSES MENU CLIENT',
        ];

        $viewMenu = [
            'MENU INDEX CLIENT NOTIFIKASI TAGIHAN',
        ];

        $this->settingPermission([
            'delete' => [
                'DELETE INDEX CLIENT NOTIFIKASI TAGIHAN',
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
        ];

        if (backpack_user()->hasRole('Super Admin')) {
            $columns[] = [
                'label' => trans('backpack::crud.billing_notification.column.company') ?? 'Milik Perusahaan',
                'type'  => 'text',
                'name'  => 'company.name',
            ];
        }

        $columns = array_merge($columns, [
            [
                'name'  => 'billable_type_label',
                'type'  => 'text',
                'label' => trans('backpack::crud.billing_notification.column.billable_type') ?? 'Jenis Tagihan',
            ],
            [
                'name'  => 'billable_target',
                'type'  => 'text',
                'label' => trans('backpack::crud.billing_notification.column.billable_id') ?? 'Item Tagihan',
            ],
            [
                'name'   => 'notification_date',
                'type'   => 'date',
                'label'  => trans('backpack::crud.billing_notification.column.notification_date') ?? 'Tanggal Notifikasi',
                'format' => 'DD/MM/YYYY',
            ],
            [
                'name'  => 'message',
                'type'  => 'text',
                'label' => trans('backpack::crud.billing_notification.column.message') ?? 'Pesan',
            ],
            [
                'name'  => 'action',
                'type'  => 'action',
                'label' => trans('backpack::crud.actions') ?? 'Aksi',
            ]
        ]);

        $this->card->addCard([
            'name'   => 'billing_notification',
            'line'   => 'top',
            'view'   => 'crud::components.datatable-origin',
            'params' => [
                'filter'       => true,
                'crud_custom'  => $this->crud,
                'hide_title'   => true,
                'columns'      => $columns,
                'filter_table' => collect($this->crud->filters()),
                'route'        => backpack_url('/billing/billing-notification/search'),
            ]
        ]);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.billing_notification.title_header') ?? 'Notifikasi Tagihan';
        $this->data['title_modal_edit'] = trans('backpack::crud.billing_notification.title_header') ?? 'Notifikasi Tagihan';
        $this->data['title_modal_delete'] = trans('backpack::crud.billing_notification.title_header') ?? 'Notifikasi Tagihan';
        $this->data['cards'] = $this->card;
        
        $breadcrumbs = [
            'Management Billing' => backpack_url('billing/billing-notification'),
            'Notifikasi Tagihan' => backpack_url($this->crud->route)
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

        $filters = BillingNotificationFilterData::fromRequest(request());
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

        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::column([
                'label'     => trans('backpack::crud.billing_notification.column.company') ?? 'Milik Perusahaan',
                'type'      => 'select',
                'name'      => 'company_id',
                'entity'    => 'company',
                'attribute' => 'name',
                'model'     => "App\Models\Company",
            ]);
        }

        CRUD::column([
            'label' => trans('backpack::crud.billing_notification.column.billable_type') ?? 'Jenis Tagihan',
            'name'  => 'billable_type_label',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_notification.column.billable_id') ?? 'Item Tagihan',
            'name'  => 'billable_target',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.billing_notification.column.notification_date') ?? 'Tanggal Notifikasi',
            'name'   => 'notification_date',
            'type'   => 'date',
            'format' => 'DD/MM/YYYY',
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_notification.column.message') ?? 'Pesan',
            'name'  => 'message',
            'type'  => 'text'
        ]);
    }

    /**
     * Handle the AJAX Search for the custom datatable.
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
     * AJAX Delete Operation handler.
     */
    public function destroy($id)
    {
        $this->crud->hasAccessOrFail('delete');

        $id = $this->crud->getCurrentEntryId() ?? $id;

        $this->crud->delete($id);

        $messages['success'][] = trans('backpack::crud.delete_confirmation_message') ?? 'Item has been deleted.';
        $messages['events'] = [
            'crudTable-billing_notification_create_success' => true,
        ];
        return response()->json($messages);
    }

    /**
     * AJAX modal Show details handler.
     */
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

    /**
     * Setup fields & columns sync for Show/Preview modal.
     */
    protected function setupShowOperation()
    {
        $this->setupCreateOperation();

        if (backpack_user()->hasRole('Super Admin')) {
            CRUD::column([
                'label'     => trans('backpack::crud.billing_notification.column.company') ?? 'Milik Perusahaan',
                'type'      => 'select',
                'name'      => 'company_id',
                'entity'    => 'company',
                'attribute' => 'name',
                'model'     => "App\Models\Company",
            ]);
        }

        CRUD::column([
            'label' => trans('backpack::crud.billing_notification.column.billable_type') ?? 'Jenis Tagihan',
            'name'  => 'billable_type_label',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_notification.column.billable_id') ?? 'Item Tagihan',
            'name'  => 'billable_target',
            'type'  => 'text'
        ]);

        CRUD::column([
            'label'  => trans('backpack::crud.billing_notification.column.notification_date') ?? 'Tanggal Notifikasi',
            'name'   => 'notification_date',
            'type'   => 'date',
            'format' => 'DD/MM/YYYY',
        ]);

        CRUD::column([
            'label' => trans('backpack::crud.billing_notification.column.message') ?? 'Pesan',
            'name'  => 'message',
            'type'  => 'text'
        ]);
    }

    /**
     * Define read-only create/update fields configuration. Used by Show modal fallback.
     */
    protected function setupCreateOperation()
    {
        if (backpack_user()->hasRole('Super Admin')) {
            $companies = \App\Models\Company::pluck('name', 'id')->toArray();
            CRUD::addField([
                'label'   => trans('backpack::crud.billing_notification.column.company') ?? 'Milik Perusahaan',
                'type'    => 'select2_array',
                'name'    => 'company_id',
                'options' => ['' => 'All (Semua Perusahaan)'] + $companies,
                'wrapper' => [
                    'class' => 'form-group col-md-6',
                ],
            ]);
        }

        CRUD::addField([
            'name'  => 'billable_type_label',
            'type'  => 'text',
            'label' => trans('backpack::crud.billing_notification.column.billable_type') ?? 'Jenis Tagihan',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'billable_target',
            'type'  => 'text',
            'label' => trans('backpack::crud.billing_notification.column.billable_id') ?? 'Item Tagihan',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
        ]);

        CRUD::addField([
            'name'  => 'notification_date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.billing_notification.column.notification_date') ?? 'Tanggal Notifikasi',
            'wrapper' => [
                'class' => 'form-group col-md-6',
            ],
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
        ]);

        CRUD::addField([
            'name'  => 'message',
            'type'  => 'textarea',
            'label' => trans('backpack::crud.billing_notification.column.message') ?? 'Pesan',
            'wrapper' => [
                'class' => 'form-group col-md-12',
            ],
        ]);
    }
}
