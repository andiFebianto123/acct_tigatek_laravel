<?php

namespace App\Http\Controllers\Admin;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Setting;
use App\Models\Quotation;
use App\Models\SetupClient;
use App\Models\SetupOffering;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\QuotationHistory;
use App\Http\Exports\ExportExcel;
use App\Http\Helpers\CustomHelper;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\App;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Controllers\CrudController;
use App\Http\Controllers\Operation\FormaterExport;
use App\Http\Controllers\Operation\PermissionAccess;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class QuotationCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use PermissionAccess;
    use FormaterExport;

    public function setup()
    {
        CRUD::setModel(Quotation::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/monitoring/quotation');
        CRUD::setEntityNameStrings(trans('backpack::crud.menu.list_quotation'), trans('backpack::crud.menu.list_quotation'));

        $base = 'INDEX MONITORING PROYEK DAFTAR PENAWARAN';
        $allAccess = ['AKSES SEMUA MENU PROJECT', 'AKSES SEMUA DAFTAR PENAWARAN PROJECT'];
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

    function index()
    {
        $this->crud->hasAccessOrFail('list');

        $this->card->addCard([
            'name' => 'quotation',
            'line' => 'top',
            'view' => 'crud::components.card-tab',
            'params' => [
                'tabs' => [
                    [
                        'name' => 'quotation',
                        'label' => trans('backpack::crud.quotation.tab.quotation'),
                        // 'class' => '',
                        'active' => true,
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
                                    'label' => trans('backpack::crud.quotation.column.no_rfq.label'),
                                    'type'      => 'text',
                                    'name'      => 'no_rfq',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.quotation.column.name_project.label'),
                                    'type' => 'text',
                                    'name' => 'name_project',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.quotation.column.rab.label'),
                                    'type' => 'text',
                                    'name' => 'rab',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.quotation.column.rap.label'),
                                    'type' => 'text',
                                    'name' => 'rap',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.quotation.column.client_id.label'),
                                    'type' => 'text',
                                    'name' => 'client_id',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.quotation.column.pic.label'),
                                    'type' => 'text',
                                    'name' => 'pic',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.quotation.column.user.label'),
                                    'type' => 'text',
                                    'name' => 'user',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.quotation.column.closing_date.label'),
                                    'type' => 'text',
                                    'name' => 'closing_date',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.quotation.column.rfq_date.label'),
                                    'type' => 'text',
                                    'name' => 'rfq_date',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.quotation.column.status.label'),
                                    'type' => 'text',
                                    'name' => 'status',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.quotation.column.information.label'),
                                    'type' => 'text',
                                    'name' => 'information',
                                    'orderable' => false,
                                ],
                                [
                                    'label' => 'Dokumen Penawaran',
                                    'type' => 'text',
                                    'name' => 'document_path',
                                    'orderable' => false,
                                ],
                                [
                                    'name' => 'action',
                                    'type' => 'action',
                                    'label' =>  trans('backpack::crud.actions'),
                                ]
                            ],
                            'route' => backpack_url('/monitoring/quotation/search?tab=quotation'),
                            'route_export_pdf' => url($this->crud->route . '/export-pdf?tab=quotation'),
                            'title_export_pdf' => 'Daftar-penawaran.pdf',
                            'route_export_excel' => url($this->crud->route . '/export-excel?tab=quotation'),
                            'title_export_excel' => 'Daftar-penawaran.xlsx',
                        ],
                    ],
                    [
                        'name' => 'quotation_history',
                        'label' => trans('backpack::crud.quotation.tab.quotation_history'),
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
                                    'label' => trans('backpack::crud.quotation.column.name_project.label_2'),
                                    'type'      => 'text',
                                    'name'      => 'name_project',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.quotation.column.user_id.label'),
                                    'type'      => 'text',
                                    'name'      => 'user_id',
                                    'orderable' => false,
                                ],
                                [
                                    'label' => trans('backpack::crud.quotation.column.date_update.label'),
                                    'type'      => 'text',
                                    'name'      => 'date_update',
                                    'orderable' => true,
                                ],
                                [
                                    'label' => trans('backpack::crud.quotation.column.history_update.label'),
                                    'type'      => 'text',
                                    'name'      => 'history_update',
                                    'orderable' => false,
                                ],
                            ],
                            'route' => backpack_url('/monitoring/quotation/search?tab=quotation_history'),
                            'route_export_pdf' => url($this->crud->route . '/export-pdf?tab=quotation_history'),
                            'title_export_pdf' => 'Daftar-penawaran-edit.pdf',
                            'route_export_excel' => url($this->crud->route . '/export-excel?tab=quotation_history'),
                            'title_export_excel' => 'Daftar-penawaran-edit.xlsx',
                        ]
                    ]
                ]
            ]
        ]);

        $this->card->addCard([
            'name' => 'hightlight',
            'line' => 'top',
            'label' => '',
            'parent_view' => 'crud::components.filter-parent',
            'view' => 'crud::components.hightligh-column',
        ]);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.quotation.title_modal_create');
        $this->data['title_modal_edit'] = trans('backpack::crud.quotation.title_modal_edit');
        $this->data['title_modal_delete'] = trans('backpack::crud.quotation.title_modal_delete');
        $this->data['cards'] = $this->card;

        $breadcrumbs = [
            trans('backpack::crud.menu.monitoring_project') => backpack_url('monitoring'),
            trans('backpack::crud.menu.list_quotation') => backpack_url($this->crud->route)
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;

        $yearOptionsQuo = Quotation::selectRaw('YEAR(created_at) as year')
            ->pluck('year')
            ->filter()
            ->toArray();

        $yearOptionsHistory = QuotationHistory::selectRaw('YEAR(created_at) as year')
            ->pluck('year')
            ->filter()
            ->toArray();

        $yearOptions = array_unique(array_merge($yearOptionsQuo, $yearOptionsHistory));
        rsort($yearOptions);

        $this->crud->year_options = $yearOptions;

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

    function ruleValidation()
    {
        return [
            'no_rfq' => 'required',
            'name_project' => 'required',
            'rab' => 'required|numeric',
            'rap' => 'required|numeric',
            'client_id' => 'required|exists:setup_clients,id',
            'pic' => 'required',
            'closing_date' => 'required',
            'rfq_date' => 'nullable|date',
            'status' => 'required',
        ];
    }

    protected function setupCreateOperation()
    {
        $settings = Setting::first();
        CRUD::setValidation($this->ruleValidation());
        CRUD::addField([
            'name' => 'no_rfq',
            'label' => trans('backpack::crud.quotation.field.no_rfq.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.quotation.field.no_rfq.placeholder'),
            ]
        ]);
        CRUD::addField([
            'name' => 'name_project',
            'label' => trans('backpack::crud.quotation.field.name_project.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-12',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.quotation.field.name_project.placeholder'),
            ]
        ]);
        CRUD::addField([
            'name' => 'rab',
            'label' =>  trans('backpack::crud.quotation.field.rab.label'),
            'type' => 'mask_currency',
            'currency_name' => 'rab_currency',
            'currency_options' => [
                'IDR' => 'IDR (Rp)',
                'USD' => 'USD ($)',
                'EUR' => 'EUR (€)',
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
            'name' => 'rap',
            'label' =>  trans('backpack::crud.quotation.field.rap.label'),
            'type' => 'mask_currency',
            'currency_name' => 'rap_currency',
            'currency_options' => [
                'IDR' => 'IDR (Rp)',
                'USD' => 'USD ($)',
                'EUR' => 'EUR (€)',
            ],
            'default_currency' => 'IDR',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => '000.000',
            ]
        ]);
        $client = SetupClient::all();
        $client_option = [
            '' => trans('backpack::crud.project.field.client_id.placeholder'),
        ];

        foreach ($client as $c) {
            $client_option[$c->id] = $c->name;
        }


        CRUD::addField([  // Select2
            'label'     => trans('backpack::crud.project.field.client_id.label'),
            'type'      => 'select2_array',
            'name'      => 'client_id',
            'options'   => $client_option, // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'pic',
            'label' => trans('backpack::crud.quotation.field.pic.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.quotation.field.pic.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'user',
            'label' => trans('backpack::crud.quotation.field.user.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.quotation.field.user.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'user',
            'label' => trans('backpack::crud.quotation.field.user.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.quotation.field.user.placeholder'),
            ]
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'closing_date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.quotation.field.closing_date.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        CRUD::addField([   // date_picker
            'name'  => 'rfq_date',
            'type'  => 'date_picker',
            'label' => trans('backpack::crud.quotation.field.rfq_date.label'),

            // optional:
            'date_picker_options' => [
                'language' => App::getLocale(),
            ],
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
        ]);

        $status_po = SetupOffering::pluck('name', 'name');

        $status_po_option = [
            '' => trans('backpack::crud.quotation.field.status.placeholder'),
            ...$status_po->map(function ($value, $key) {
                $name = $value;
                return $name;
            }),
        ];

        CRUD::addField([  // Select2
            'label'     => trans('backpack::crud.quotation.field.status.label'),
            'type'      => 'select2_array',
            'name'      => 'status',
            'options'   => $status_po_option, // force the related options to be a custom query, instead of all(); you can use this to filter the results show in the select
            'wrapper' => [
                'class' => 'form-group col-md-6'
            ]
        ]);

        CRUD::addField([
            'name' => 'information',
            'label' => trans('backpack::crud.quotation.field.information.label'),
            'type' => 'text',
            'wrapper'   => [
                'class' => 'form-group col-md-6',
            ],
            'attributes' => [
                'placeholder' => trans('backpack::crud.quotation.field.information.placeholder'),
            ]
        ]);

        CRUD::addField([
            'name' => 'document_path',
            'label' => 'Dokumen Penawaran',
            'type' => 'upload',
            'wrapper'   => [
                'class' => 'form-group col-md-6'
            ],
            'withFiles' => [
                'disk' => 'public',
                'path' => 'document_quotation',
                'deleteWhenEntryIsDeleted' => true,
            ],
        ]);
    }

    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function setupListOperation()
    {
        $type = request()->tab;
        // $settings = Setting::first();

        $status_file = '';
        if (strpos(url()->current(), 'excel')) {
            $status_file = 'excel';
        } else {
            $status_file = 'pdf';
        }

        if (!request()->has('tab')) {
            $type = 'quotation';
        }

        // $new_format_date = 'D MMM Y';
        $new_format_date = 'DD/MM/YYYY';

        CRUD::addButtonFromView('top', 'filter-year', 'filter-year', 'beginning');
        CRUD::addButtonFromView('top', 'export-excel', 'export-excel', 'beginning');
        CRUD::addButtonFromView('top', 'export-pdf', 'export-pdf', 'beginning');

        if (request()->has('filter_year') && request()->filter_year != 'all') {
            if ($type == 'quotation') {
                CRUD::addClause('whereYear', 'created_at', request()->filter_year);
            } else if ($type == 'quotation_history') {
                CRUD::addClause('whereYear', 'created_at', request()->filter_year);
            }
        }
        if ($type == 'quotation') {
            CRUD::setModel(Quotation::class);
            CRUD::disableResponsiveTable();
            CRUD::addColumn([
                'name'      => 'row_number',
                'type'      => 'row_number',
                'label'     => 'No',
                'orderable' => false,
                'wrapper' => [
                    'element' => 'strong',
                ]
            ])->makeFirstColumn();
            CRUD::column(
                [
                    'label' => trans('backpack::crud.quotation.column.no_rfq.label'),
                    'name' => 'no_rfq',
                    'type'  => 'wrap_text'
                ],
            );
            CRUD::column(
                [
                    'label' => trans('backpack::crud.quotation.column.name_project.label'),
                    'name' => 'name_project',
                    'type'  => 'wrap_text'
                ],
            );
            CRUD::column([
                'label' => trans('backpack::crud.quotation.column.rab.label'),
                'name' => 'rab',
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return $this->priceFormatExport($status_file, $entry->rab);
                },
                // 'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ]);
            CRUD::column([
                'label' => trans('backpack::crud.quotation.column.rap.label'),
                'name' => 'rap',
                'type'  => 'closure',
                'function' => function ($entry) use ($status_file) {
                    return $this->priceFormatExport($status_file, $entry->rap);
                },
                // 'prefix' => ($settings?->currency_symbol) ? $settings->currency_symbol : 'Rp.',
                'decimals'      => 2,
                'dec_point'     => ',',
                'thousands_sep' => '.',
            ]);
            CRUD::column([
                // 1-n relationship
                'label' => trans('backpack::crud.client_po.column.client_id'),
                'type'      => 'select',
                'name'      => 'client_id', // the column that contains the ID of that connected entity;
                'entity'    => 'setup_client', // the method that defines the relationship in your Model
                'attribute' => 'name', // foreign key attribute that is shown to user
                'model'     => "App\Models\SetupClient", // foreign key model
                // OPTIONAL
                'limit' => 50, // Limit the number of characters shown
            ]);
            CRUD::column(
                [
                    'label' => trans('backpack::crud.quotation.column.pic.label'),
                    'name' => 'pic',
                    'type'  => 'text'
                ],
            );
            CRUD::column(
                [
                    'label' => trans('backpack::crud.quotation.column.user.label'),
                    'name' => 'user',
                    'type'  => 'text'
                ],
            );

            $date_format = ($status_file == 'excel') ? 'DD/MM/YYYY' : 'D MMM Y';
            CRUD::column([
                'label' => trans('backpack::crud.quotation.column.closing_date.label'),
                'name' => 'closing_date',
                'type'  => 'date',
                'format' => $new_format_date
            ]);

            CRUD::column([
                'label' => trans('backpack::crud.quotation.column.rfq_date.label'),
                'name' => 'rfq_date',
                'type'  => 'date',
                'format' => $new_format_date
            ]);

            CRUD::column(
                [
                    'label' => trans('backpack::crud.quotation.column.status.label'),
                    'name' => 'status',
                    'type'  => 'closure',
                    'function' => function ($entry) {
                        return strtoupper($entry->status);
                    }
                ],
            );
            CRUD::column(
                [
                    'label' => trans('backpack::crud.quotation.column.information.label'),
                    'name' => 'information',
                    'type'  => 'wrap_text'
                ],
            );

            CRUD::column([
                'label'  => 'Dokumen Penawaran',
                'name' => 'document_path',
                'type'  => 'text',
                'wrapper'   => [
                    'element' => 'a', // the element will default to "a" so you can skip it here
                    'href' => function ($crud, $column, $entry, $related_key) {
                        if ($entry->document_path != '') {
                            return url('storage/' . $entry->document_path);
                        }
                        return "javascript:void(0)";
                    },
                    'target' => '_blank',
                    // 'class' => 'some-class',
                ],
            ]);
        } else if ($type == 'quotation_history') {
            CRUD::setModel(QuotationHistory::class);
            CRUD::disableResponsiveTable();
            CRUD::addColumn([
                'name'      => 'row_number',
                'type'      => 'row_number',
                'label'     => 'No',
                'orderable' => false,
                'wrapper' => [
                    'element' => 'strong',
                ]
            ])->makeFirstColumn();
            CRUD::column(
                [
                    'label' => trans('backpack::crud.quotation.column.name_project.label_2'),
                    'name' => 'name_project',
                    'type'  => 'wrap_text'
                ],
            );
            CRUD::column([
                // 1-n relationship
                'label' => trans('backpack::crud.quotation.column.user_id.label'),
                'type'      => 'select',
                'name'      => 'user_id', // the column that contains the ID of that connected entity;
                'entity'    => 'user', // the method that defines the relationship in your Model
                'attribute' => 'name', // foreign key attribute that is shown to user
                'model'     => "App\Models\User", // foreign key model
                // OPTIONAL
                // 'limit' => 32, // Limit the number of characters shown
            ]);
            $date_format_history = ($status_file == 'excel') ? 'DD/MM/YYYY' : 'DD MMM YYYY HH:mm';
            CRUD::column([
                'label' => trans('backpack::crud.quotation.column.date_update.label'),
                'name' => 'date_update',
                'type'  => 'date',
                'format' => $date_format_history
            ]);
            CRUD::column([
                'label' => trans('backpack::crud.quotation.column.history_update.label'),
                'name' => 'history_update',
                'type'  => 'wrap_text',
            ]);
        }
    }

    public function store()
    {
        $this->crud->hasAccessOrFail('create');

        $request = $this->crud->validateRequest();

        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {

            $event = [];
            $item = new Quotation;
            $item->no_rfq = $request->no_rfq;
            $item->name_project = $request->name_project;
            $item->rab = $request->rab;
            $item->rap = $request->rap;
            $item->client_id = $request->client_id;
            $item->pic = $request->pic;
            $item->user = $request->user;
            $item->closing_date = $request->closing_date;
            $item->rfq_date = $request->rfq_date;
            $item->status = $request->status;
            $item->information = $request->information;
            $item->save();

            $item_history = new QuotationHistory;
            $item_history->quotation_id = $item->id;
            $item_history->name_project = $item->name_project;
            $item_history->user_id = backpack_auth()->user()->id;
            $item_history->date_update = Carbon::now();
            $item_history->history_update = "Menambahkan data penawaran baru";
            $item_history->save();

            // $item = $aset;
            // $this->data['entry'] = $this->crud->entry = $item;

            \Alert::success(trans('backpack::crud.insert_success'))->flash();
            $event['crudTable-quotation_create_success'] = true;
            $event['crudTable-quotation_history_create_success'] = true;

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

    public function update()
    {
        $this->crud->hasAccessOrFail('update');

        $request = $this->crud->validateRequest();

        $this->crud->registerFieldEvents();


        DB::beginTransaction();
        try {

            $old = DB::table('quotations')->where('id', $this->crud->getCurrentEntryId())->first();
            $item = Quotation::find($this->crud->getCurrentEntryId());

            if ($old->no_rfq != $request->no_rfq) {
                $flag_update = true;
            }
            $item->no_rfq = $request->no_rfq;
            if ($old->name_project != $request->name_project) {
                $flag_update = true;
            }
            $item->name_project = $request->name_project;
            if ($old->rab != $request->rab) {
                $flag_update = true;
            }
            $item->rab = $request->rab;
            if ($old->rap != $request->rap) {
                $flag_update = true;
            }
            $item->rap = $request->rap;
            if ($old->client_id != $request->client_id) {
                $flag_update = true;
            }
            $item->client_id = $request->client_id;
            if ($old->pic != $request->pic) {
                $flag_update = true;
            }
            $item->pic = $request->pic;
            if ($old->user != $request->user) {
                $flag_update = true;
            }
            $item->user = $request->user;
            if ($old->closing_date != $request->closing_date) {
                $flag_update = true;
            }
            $item->closing_date = $request->closing_date;
            if ($old->rfq_date != $request->rfq_date) {
                $flag_update = true;
            }
            $item->rfq_date = $request->rfq_date;
            if ($old->status != $request->status) {
                $flag_update = true;
            }
            $item->status = $request->status;
            if ($old->information != $request->information) {
                $flag_update = true;
            }
            $item->information = $request->information;
            $item->save();

            if (isset($flag_update)) {
                $item_history = new QuotationHistory;
                $item_history->quotation_id = $item->id;
                $item_history->name_project = $item->name_project;
                $item_history->user_id = backpack_auth()->user()->id;
                $item_history->date_update = Carbon::now();
                $item_history->history_update = "Mengedit data penawaran";
                $item_history->save();
            }

            $this->data['entry'] = $this->crud->entry = $item;




            \Alert::success(trans('backpack::crud.update_success'))->flash();


            $this->crud->setSaveAction();

            DB::commit();
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $item,
                    'events' => [
                        'crudTable-quotation_updated_success' => $item,
                        'crudTable-quotation_history_updated_success' => $item,
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
        DB::beginTransaction();
        try {
            $this->crud->hasAccessOrFail('delete');

            $item = Quotation::find($id);

            $item_history = new QuotationHistory;
            $item_history->quotation_id = $item->id;
            $item_history->name_project = $item->name_project;
            $item_history->user_id = backpack_auth()->user()->id;
            $item_history->date_update = Carbon::now();
            $item_history->history_update = "Menghapus data penawaran";
            $item_history->save();

            $item->delete();

            $messages['success'][] = trans('backpack::crud.delete_confirmation_message');
            $messages['events'] = [
                'crudTable-quotation_updated_success' => 1,
                'crudTable-quotation_history_edit_updated_success' => 1,
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
        $this->setupListOperation();
        CRUD::column('row_number')->remove();
        CRUD::column('document_path')->remove();
        CRUD::column([
            'label'  => 'Dokumen Penawaran',
            'name' => 'document_path',
            'type'  => 'text',
            'wrapper'   => [
                'element' => 'a', // the element will default to "a" so you can skip it here
                'href' => function ($crud, $column, $entry, $related_key) {
                    if ($entry->document_path != '') {
                        return url('storage/document_quotation/' . $entry->document_path);
                    }
                    return "javascript:void(0)";
                },
                'target' => '_blank',
                // 'class' => 'some-class',
            ],
        ])->after('information');
    }

    public function exportPdf()
    {
        $type = request()->tab;

        $this->setupListOperation();

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

        if ($type == 'quotation_history') {
            $title = 'Daftar Penawaran - Edit';
        } else {
            $title = 'Daftar Penawaran';
        }

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

        $this->setupListOperation();
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

        if ($type == 'quotation_history') {
            $title = 'Daftar Penawaran - Edit';
        } else {
            $title = 'Daftar Penawaran';
        }

        $name = 'project_list.xlsx';

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
}
