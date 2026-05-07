<?php

namespace App\Http\Controllers;

use App\Account;
use App\BusinessLocation;
use App\InvoiceLayout;
use App\InvoiceScheme;
use App\SellingPriceGroup;
use App\Utils\ModuleUtil;
use App\Utils\Util;
use App\VariationLocationDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class BusinessLocationController extends Controller
{
    protected $moduleUtil;

    protected $commonUtil;

    /**
     * Constructor
     *
     * @param  ModuleUtil  $moduleUtil
     * @return void
     */
    public function __construct(ModuleUtil $moduleUtil, Util $commonUtil)
    {
        $this->moduleUtil = $moduleUtil;
        $this->commonUtil = $commonUtil;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if (! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $business_id = request()->session()->get('user.business_id');

            $locations = BusinessLocation::where('business_locations.business_id', $business_id)
                ->leftjoin(
                    'invoice_schemes as ic',
                    'business_locations.invoice_scheme_id',
                    '=',
                    'ic.id'
                )
                ->leftjoin(
                    'invoice_layouts as il',
                    'business_locations.invoice_layout_id',
                    '=',
                    'il.id'
                )
                ->leftjoin(
                    'invoice_layouts as sil',
                    'business_locations.sale_invoice_layout_id',
                    '=',
                    'sil.id'
                )
                ->leftjoin(
                    'selling_price_groups as spg',
                    'business_locations.selling_price_group_id',
                    '=',
                    'spg.id'
                )
                ->select(['business_locations.name', 'location_id', 'landmark', 'city', 'zip_code', 'state',
                    'country', 'business_locations.id', 'spg.name as price_group', 'ic.name as invoice_scheme', 'il.name as invoice_layout', 'sil.name as sale_invoice_layout', 'business_locations.is_active', ]);

            $permitted_locations = auth()->user()->permitted_locations();
            if ($permitted_locations != 'all') {
                $locations->whereIn('business_locations.id', $permitted_locations);
            }

            return Datatables::of($locations)
                ->addColumn(
                    'action',
                    '<button type="button" data-href="{{action(\'App\Http\Controllers\BusinessLocationController@edit\', [$id])}}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline tw-dw-btn-primary btn-modal" data-container=".location_edit_modal"><i class="glyphicon glyphicon-edit"></i> @lang("messages.edit")</button>
                    <a href="{{route(\'location.settings\', [$id])}}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline  tw-dw-btn-accent"><i class="fa fa-wrench"></i> @lang("messages.settings")</a>

                    <button type="button" data-href="{{action(\'App\Http\Controllers\BusinessLocationController@activateDeactivateLocation\', [$id])}}" class="tw-dw-btn tw-dw-btn-xs tw-dw-btn-outline   activate-deactivate-location @if($is_active) tw-dw-btn-error @else tw-dw-btn-accent @endif tw-w-max"><i class="fa fa-power-off"></i> @if($is_active) @lang("lang_v1.deactivate_location") @else @lang("lang_v1.activate_location") @endif </button>
                    '
                )
                ->removeColumn('id')
                ->removeColumn('is_active')
                ->rawColumns([11])
                ->make(false);
        }

        return view('business_location.index');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        if (! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }
        $business_id = request()->session()->get('user.business_id');

        //Check if subscribed or not, then check for location quota
        if (! $this->moduleUtil->isSubscribed($business_id)) {
            return $this->moduleUtil->expiredResponse();
        } elseif (! $this->moduleUtil->isQuotaAvailable('locations', $business_id)) {
            return $this->moduleUtil->quotaExpiredResponse('locations', $business_id);
        }

        $invoice_layouts = InvoiceLayout::where('business_id', $business_id)
                            ->get()
                            ->pluck('name', 'id');

        $invoice_schemes = InvoiceScheme::where('business_id', $business_id)
                            ->get()
                            ->pluck('name', 'id');

        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $payment_types = $this->commonUtil->payment_types(null, false, $business_id);

        //Accounts
        $accounts = [];
        if ($this->commonUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }

        $existing_locations = BusinessLocation::where('business_id', $business_id)
                                ->Active()
                                ->select(DB::raw("IF(location_id IS NULL OR location_id='', name, CONCAT(name, ' (', location_id, ')')) AS name"), 'id')
                                ->get()
                                ->pluck('name', 'id');

        return view('business_location.create')
                    ->with(compact(
                        'invoice_layouts',
                        'invoice_schemes',
                        'price_groups',
                        'payment_types',
                        'accounts',
                        'existing_locations'
                    ));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = $request->session()->get('user.business_id');

            //Check if subscribed or not, then check for location quota
            if (! $this->moduleUtil->isSubscribed($business_id)) {
                return $this->moduleUtil->expiredResponse();
            } elseif (! $this->moduleUtil->isQuotaAvailable('locations', $business_id)) {
                return $this->moduleUtil->quotaExpiredResponse('locations', $business_id);
            }

            $input = $request->only(['name', 'landmark', 'city', 'state', 'country', 'zip_code', 'invoice_scheme_id',
                'invoice_layout_id', 'mobile', 'alternate_number', 'email', 'website', 'custom_field1', 'custom_field2', 'custom_field3', 'custom_field4', 'location_id', 'selling_price_group_id', 'default_payment_accounts', 'featured_products', 'sale_invoice_layout_id', 'sale_invoice_scheme_id']);

            $input['business_id'] = $business_id;

            $input['default_payment_accounts'] = ! empty($input['default_payment_accounts']) ? json_encode($input['default_payment_accounts']) : null;

            //Update reference count
            $ref_count = $this->moduleUtil->setAndGetReferenceCount('business_location');

            if (empty($input['location_id'])) {
                $input['location_id'] = $this->moduleUtil->generateReferenceNumber('business_location', $ref_count);
            }

            $location = BusinessLocation::create($input);

            //Create a new permission related to the created location
            $permission = Permission::create(['name' => 'location.'.$location->id]);

            //Auto-grant the new location permission to the Admin role for this business
            try {
                $admin_role = Role::where('name', 'Admin#'.$business_id)->first();
                if ($admin_role) {
                    $admin_role->givePermissionTo($permission);
                }
            } catch (\Exception $e) {
                \Log::warning('Could not auto-grant location permission to Admin role: '.$e->getMessage());
            }

            //Copy products from an existing location if requested
            $copy_from_location_id = $request->input('copy_products_from_location');
            if (!empty($copy_from_location_id)) {
                $copy_from = BusinessLocation::where('business_id', $business_id)
                                ->find((int) $copy_from_location_id);
                if ($copy_from) {
                    //Copy product_locations entries (make products available in new location)
                    $existing_product_ids = DB::table('product_locations')
                        ->where('location_id', $copy_from->id)
                        ->pluck('product_id')
                        ->toArray();

                    if (!empty($existing_product_ids)) {
                        $product_location_rows = array_map(function ($pid) use ($location) {
                            return ['product_id' => $pid, 'location_id' => $location->id];
                        }, $existing_product_ids);

                        foreach (array_chunk($product_location_rows, 500) as $chunk) {
                            DB::table('product_locations')->insertOrIgnore($chunk);
                        }

                        //Copy variation_location_details (with zero stock)
                        $source_vld = VariationLocationDetails::where('location_id', $copy_from->id)
                            ->whereIn('product_id', $existing_product_ids)
                            ->get();

                        $new_vld_rows = [];
                        foreach ($source_vld as $vld) {
                            $new_vld_rows[] = [
                                'product_id'           => $vld->product_id,
                                'product_variation_id' => $vld->product_variation_id,
                                'variation_id'         => $vld->variation_id,
                                'location_id'          => $location->id,
                                'qty_available'        => 0,
                                'created_at'           => now(),
                                'updated_at'           => now(),
                            ];
                        }

                        foreach (array_chunk($new_vld_rows, 500) as $chunk) {
                            DB::table('variation_location_details')->insertOrIgnore($chunk);
                        }
                    }
                }
            }

            $output = ['success' => true,
                'msg' => __('business.business_location_added_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\StoreFront  $storeFront
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\StoreFront  $storeFront
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        $business_id = request()->session()->get('user.business_id');
        $location = BusinessLocation::where('business_id', $business_id)
                                    ->find($id);
        $invoice_layouts = InvoiceLayout::where('business_id', $business_id)
                            ->get()
                            ->pluck('name', 'id');
        $invoice_schemes = InvoiceScheme::where('business_id', $business_id)
                            ->get()
                            ->pluck('name', 'id');

        $price_groups = SellingPriceGroup::forDropdown($business_id);

        $payment_types = $this->commonUtil->payment_types(null, false, $business_id);

        //Accounts
        $accounts = [];
        if ($this->commonUtil->isModuleEnabled('account')) {
            $accounts = Account::forDropdown($business_id, true, false);
        }
        $featured_products = $location->getFeaturedProducts(true, false);

        return view('business_location.edit')
                ->with(compact(
                    'location',
                    'invoice_layouts',
                    'invoice_schemes',
                    'price_groups',
                    'payment_types',
                    'accounts',
                    'featured_products'
                ));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\StoreFront  $storeFront
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        if (! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $input = $request->only(['name', 'landmark', 'city', 'state', 'country',
                'zip_code', 'invoice_scheme_id',
                'invoice_layout_id', 'mobile', 'alternate_number', 'email', 'website', 'custom_field1', 'custom_field2', 'custom_field3', 'custom_field4', 'location_id', 'selling_price_group_id', 'default_payment_accounts', 'featured_products', 'sale_invoice_layout_id', 'sale_invoice_scheme_id' ]);

            $business_id = $request->session()->get('user.business_id');

            $input['default_payment_accounts'] = ! empty($input['default_payment_accounts']) ? json_encode($input['default_payment_accounts']) : null;

            $input['featured_products'] = ! empty($input['featured_products']) ? json_encode($input['featured_products']) : null;

            BusinessLocation::where('business_id', $business_id)
                            ->where('id', $id)
                            ->update($input);

            $output = ['success' => true,
                'msg' => __('business.business_location_updated_success'),
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\StoreFront  $storeFront
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    /**
     * Checks if the given location id already exist for the current business.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function checkLocationId(Request $request)
    {
        $location_id = $request->input('location_id');

        $valid = 'true';
        if (! empty($location_id)) {
            $business_id = $request->session()->get('user.business_id');
            $hidden_id = $request->input('hidden_id');

            $query = BusinessLocation::where('business_id', $business_id)
                            ->where('location_id', $location_id);
            if (! empty($hidden_id)) {
                $query->where('id', '!=', $hidden_id);
            }
            $count = $query->count();
            if ($count > 0) {
                $valid = 'false';
            }
        }
        echo $valid;
        exit;
    }

    /**
     * Function to activate or deactivate a location.
     *
     * @param  int  $location_id
     * @return json
     */
    public function activateDeactivateLocation($location_id)
    {
        if (! auth()->user()->can('business_settings.access')) {
            abort(403, 'Unauthorized action.');
        }

        try {
            $business_id = request()->session()->get('user.business_id');

            $business_location = BusinessLocation::where('business_id', $business_id)
                            ->findOrFail($location_id);

            $business_location->is_active = ! $business_location->is_active;
            $business_location->save();

            $msg = $business_location->is_active ? __('lang_v1.business_location_activated_successfully') : __('lang_v1.business_location_deactivated_successfully');

            $output = ['success' => true,
                'msg' => $msg,
            ];
        } catch (\Exception $e) {
            \Log::emergency('File:'.$e->getFile().'Line:'.$e->getLine().'Message:'.$e->getMessage());

            $output = ['success' => false,
                'msg' => __('messages.something_went_wrong'),
            ];
        }

        return $output;
    }
}
