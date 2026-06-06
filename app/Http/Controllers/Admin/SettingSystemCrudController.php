<?php

namespace App\Http\Controllers\Admin;

use App\Models\User;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;


class SettingSystemCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;


    public function setup()
    {
        CRUD::setModel(Setting::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/setting/system');
        CRUD::setEntityNameStrings(trans('backpack::crud.menu.setting_system'), trans('backpack::crud.menu.setting_system'));
    }

    function index()
    {
        $this->crud->hasAccessOrFail('list');

        $this->card->addCard([
            'name' => 'dashboard',
            'line' => 'top',
            'view' => 'crud::components.setting_system',
            'parent_view' => 'crud::components.filter-parent',
            'params' => [
                'setting' => Setting::first(),
            ]
        ]);

        $this->data['crud'] = $this->crud;
        $this->data['title'] = $this->crud->getTitle() ?? mb_ucfirst($this->crud->entity_name_plural);
        $this->data['title_modal_create'] = trans('backpack::crud.project_status.title_modal_create');
        $this->data['title_modal_edit'] = trans('backpack::crud.project_status.title_modal_edit');
        $this->data['title_modal_delete'] = trans('backpack::crud.project_status.title_modal_delete');
        $this->data['cards'] = $this->card;

        $breadcrumbs = [
            trans('backpack::crud.menu.setting') => backpack_url('setting'),
            trans('backpack::crud.menu.setting_system') => backpack_url($this->crud->route)
        ];
        $this->data['breadcrumbs'] = $breadcrumbs;

        // $list = "crud::list-custom" ?? $this->crud->getListView();
        $list = "crud::list-blank" ?? $this->crud->getListView();
        return view($list, $this->data);
    }


    function update_personal(Request $request)
    {

        $user = backpack_user();

        CRUD::setValidation([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $user->id],
        ]);

        $request = $this->crud->validateRequest();

        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {

            $user = User::find($user->id);
            $user->name = $request->name;
            $user->email = $request->email;
            $user->save();

            $this->data['entry'] = $this->crud->entry = $user;

            \Alert::success(trans('backpack::crud.update_success'))->flash();

            $events = [];

            $this->crud->setSaveAction();

            DB::commit();
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $user,
                    'events' => $events,
                ]);
            }

            return $this->crud->performSaveAction($user->getKey());
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    function update_password(Request $request)
    {
        $user = backpack_user();

        CRUD::setValidation([
            'old_password' => ['required'],
            'new_password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $request = $this->crud->validateRequest();

        $this->crud->registerFieldEvents();

        DB::beginTransaction();
        try {

            if (!Hash::check($request->old_password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'errors' => ['old_password' => ['The provided password was incorrect.']],
                ], 422);
            }

            $user->password = Hash::make($request->new_password);
            $user->save();

            $this->data['entry'] = $this->crud->entry = $user;

            \Alert::success(trans('backpack::crud.update_success'))->flash();

            $events = [];

            $this->crud->setSaveAction();

            DB::commit();
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $user,
                    'events' => $events,
                ]);
            }

            return $this->crud->performSaveAction($user->getKey());
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function updateSystem(Request $request)
    {

        CRUD::setValidation([
            'currency'              => 'required|string|max:10',
            'currency_symbol'       => 'nullable|string|max:10',
            'position_currency_symbol' => 'nullable|in:pre,post',
            'format_decimal_number' => 'nullable|integer',
            'po_prefix'             => 'nullable|string|max:10',
            'spk_prefix'            => 'nullable|string|max:10',
            'work_code_prefix'      => 'nullable|string|max:10',
            'vouhcer_prefix'        => 'nullable|string|max:10',
            'faktur_prefix'         => 'nullable|string|max:10',
            'invoice_prefix'        => 'nullable|string|max:10',
            'pi_prefix'             => 'nullable|string|max:10',
            'quotation_prefix'      => 'nullable|string|max:10',
            'surat_jalan_prefix'    => 'nullable|string|max:10',
            'bast_prefix'           => 'nullable|string|max:10',
        ]);

        $request = $this->crud->validateRequest();

        $this->crud->registerFieldEvents();

        DB::beginTransaction();

        try {

            $settings = Setting::first();

            if (!$settings) {
                $settings = new Setting();
            }

            $settings->fill($request->all())->save();

            $events = [];

            DB::commit();
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $settings,
                    'events' => $events,
                ]);
            }

            return $this->crud->performSaveAction($settings->getKey());
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    function updateCompany()
    {

        CRUD::setValidation([
            'name_company'          => 'nullable|string|max:100',
            'address'               => 'nullable|string|max:150',
            'city'                  => 'nullable|string|max:30',
            'province'              => 'nullable|string|max:30',
            'zip_code'              => 'nullable|string|max:20',
            'country'               => 'nullable|string|max:30',
            'telp'                  => 'nullable|string|max:25',
            'no_register_company'   => 'nullable|string|max:20',
            'start_time'            => 'nullable|date_format:H:i',
            'end_time'              => 'nullable|date_format:H:i',
            'no_fax'                => 'nullable',
        ]);

        $request = $this->crud->validateRequest();

        $this->crud->registerFieldEvents();

        DB::beginTransaction();

        try {

            $settings = Setting::first();

            if (!$settings) {
                $settings = new Setting();
            }

            $settings->fill($request->all());
            $settings->save();

            $events = [];

            DB::commit();
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $settings,
                    'events' => $events,
                ]);
            }

            return $this->crud->performSaveAction($settings->getKey());
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }

    function updateLogo()
    {
        CRUD::setValidation([
            'logo_dark'  => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'logo_light' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'favicon'    => 'nullable|image|mimes:jpeg,png,jpg,ico|max:2048',
        ]);

        $request = $this->crud->validateRequest();

        $this->crud->registerFieldEvents();

        DB::beginTransaction();

        try {

            $settings = Setting::first();

            if (!$settings) {
                $settings = new Setting();
            }

            if ($request->hasFile('logo_dark')) {
                if ($settings->logo_dark && Storage::disk('public')->exists('logos/' . $settings->logo_dark)) {
                    Storage::disk('public')->delete('logos/' . $settings->logo_dark);
                }

                $fileName = time() . '_dark.' . $request->file('logo_dark')->getClientOriginalExtension();
                $request->file('logo_dark')->storeAs('logos', $fileName, 'public'); // jangan pakai 'public/logos' di path

                $settings->logo_dark = $fileName;
            }

            if ($request->hasFile('logo_light')) {
                if ($settings->logo_light && Storage::disk('public')->exists('logos/' . $settings->logo_light)) {
                    Storage::disk('public')->delete('logos/' . $settings->logo_light);
                }

                $fileName = time() . '_light.' . $request->file('logo_light')->getClientOriginalExtension();
                $request->file('logo_light')->storeAs('logos', $fileName, 'public');

                $settings->logo_light = $fileName;
            }

            if ($request->hasFile('favicon')) {
                if ($settings->favicon && Storage::disk('public')->exists('logos/' . $settings->favicon)) {
                    Storage::disk('public')->delete('logos/' . $settings->favicon);
                }

                $fileName = time() . '_favicon.' . $request->file('favicon')->getClientOriginalExtension();
                $request->file('favicon')->storeAs('logos', $fileName, 'public');

                $settings->favicon = $fileName;
            }
            $settings->save();
            $events = [];

            DB::commit();
            if (request()->ajax()) {
                return response()->json([
                    'success' => true,
                    'data' => $settings,
                    'events' => $events,
                ]);
            }

            return $this->crud->performSaveAction($settings->getKey());
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => false,
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
