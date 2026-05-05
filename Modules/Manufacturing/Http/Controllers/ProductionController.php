<?php

namespace Modules\Manufacturing\Http\Controllers;

use App\BusinessLocation;
use App\Product;
use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Modules\Manufacturing\Entities\ManufacturingProduction;
use Modules\Manufacturing\Entities\ManufacturingRecipe;
use Yajra\DataTables\Facades\DataTables;

class ProductionController extends Controller
{
    public function __construct()
    {
        $this->module_util = new ModuleUtil();
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->module_util->hasThePermissionInSubscription($business_id, 'manufacturing_module', 'superadmin_package'))) {
            abort(403, 'Unauthorized action.');
        }

        if (! auth()->user()->can('manufacturing.view_production') && ! auth()->user()->can('manufacturing.view_recipe')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $productions = ManufacturingProduction::with('recipe.product')
                ->where('manufacturing_productions.business_id', $business_id)
                ->select('manufacturing_productions.*');

            return DataTables::of($productions)
                ->addColumn('recipe_name', fn ($r) => optional(optional($r->recipe)->product)->name ?? '-')
                ->editColumn('status', function ($row) {
                    $map = [
                        'pending'     => '<span class="label label-warning">' . __('manufacturing::lang.status_pending') . '</span>',
                        'in_progress' => '<span class="label label-info">' . __('manufacturing::lang.status_in_progress') . '</span>',
                        'completed'   => '<span class="label label-success">' . __('manufacturing::lang.status_completed') . '</span>',
                    ];

                    return $map[$row->status] ?? $row->status;
                })
                ->editColumn('created_at', fn ($r) => $r->created_at->format('d M Y'))
                ->addColumn('action', function ($row) {
                    $html = '';
                    if (auth()->user()->can('manufacturing.create_production')) {
                        $html .= '<button type="button" class="btn btn-xs btn-primary edit_production_btn" data-id="' . $row->id . '">'
                            . '<i class="fas fa-edit"></i> ' . __('messages.edit') . '</button>&nbsp;';
                        $html .= '<button type="button" class="btn btn-xs btn-danger delete_production_btn" data-id="' . $row->id . '">'
                            . '<i class="fas fa-trash"></i> ' . __('messages.delete') . '</button>';
                    }

                    return $html;
                })
                ->rawColumns(['status', 'action'])
                ->make(true);
        }

        $recipes = ManufacturingRecipe::with('product')
            ->where('business_id', $business_id)
            ->get()
            ->map(fn ($r) => ['id' => $r->id, 'name' => optional($r->product)->name ?? "Recipe #{$r->id}"]);

        $locations = BusinessLocation::where('business_id', $business_id)->pluck('name', 'id');

        return view('manufacturing::productions.index', compact('recipes', 'locations'));
    }

    public function store(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        if (! auth()->user()->can('manufacturing.create_production')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'recipe_id'         => 'required|integer',
            'quantity_produced' => 'required|numeric|min:0.0001',
            'status'            => 'required|in:pending,in_progress,completed',
            'location_id'       => 'nullable|integer',
            'notes'             => 'nullable|string|max:1000',
        ]);

        ManufacturingProduction::create([
            'business_id'       => $business_id,
            'recipe_id'         => $request->recipe_id,
            'quantity_produced' => $request->quantity_produced,
            'status'            => $request->status,
            'location_id'       => $request->location_id,
            'notes'             => $request->notes,
            'created_by'        => auth()->id(),
            'produced_at'       => $request->status === 'completed' ? now() : null,
        ]);

        return response()->json(['success' => true, 'msg' => __('lang_v1.success')]);
    }

    public function show($id)
    {
        $business_id = request()->session()->get('user.business_id');
        $production = ManufacturingProduction::where('business_id', $business_id)->findOrFail($id);

        return response()->json($production);
    }

    public function update(Request $request, $id)
    {
        $business_id = $request->session()->get('user.business_id');

        if (! auth()->user()->can('manufacturing.create_production')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'recipe_id'         => 'required|integer',
            'quantity_produced' => 'required|numeric|min:0.0001',
            'status'            => 'required|in:pending,in_progress,completed',
            'notes'             => 'nullable|string|max:1000',
        ]);

        $production = ManufacturingProduction::where('business_id', $business_id)->findOrFail($id);
        $production->update([
            'recipe_id'         => $request->recipe_id,
            'quantity_produced' => $request->quantity_produced,
            'status'            => $request->status,
            'notes'             => $request->notes,
            'produced_at'       => $request->status === 'completed' ? ($production->produced_at ?? now()) : $production->produced_at,
        ]);

        return response()->json(['success' => true, 'msg' => __('lang_v1.success')]);
    }

    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! auth()->user()->can('manufacturing.create_production')) {
            abort(403, 'Unauthorized action.');
        }

        ManufacturingProduction::where('business_id', $business_id)->findOrFail($id)->delete();

        return response()->json(['success' => true, 'msg' => __('lang_v1.success')]);
    }
}
