<?php

namespace Modules\Connector\Http\Controllers;

use App\Utils\ModuleUtil;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Str;
use Modules\Connector\Entities\ConnectorApiToken;
use Yajra\DataTables\Facades\DataTables;

class ApiTokenController extends Controller
{
    public function __construct()
    {
        $this->module_util = new ModuleUtil();
    }

    public function index()
    {
        $business_id = request()->session()->get('user.business_id');

        if (! (auth()->user()->can('superadmin') || $this->module_util->hasThePermissionInSubscription($business_id, 'connector_module', 'superadmin_package'))) {
            abort(403, 'Unauthorized action.');
        }

        if (! auth()->user()->can('connector.access_api') && ! auth()->user()->can('connector.manage_tokens')) {
            abort(403, 'Unauthorized action.');
        }

        if (request()->ajax()) {
            $tokens = ConnectorApiToken::where('business_id', $business_id)
                ->select('id', 'description', 'is_active', 'last_used_at', 'expires_at', 'created_at', 'user_id');

            return DataTables::of($tokens)
                ->editColumn('is_active', function ($row) {
                    return $row->is_active
                        ? '<span class="label label-success">' . __('messages.active') . '</span>'
                        : '<span class="label label-danger">' . __('messages.inactive') . '</span>';
                })
                ->editColumn('last_used_at', fn ($r) => $r->last_used_at ? $r->last_used_at->format('d M Y H:i') : '-')
                ->editColumn('expires_at', fn ($r) => $r->expires_at ? $r->expires_at->format('d M Y') : __('messages.never'))
                ->editColumn('created_at', fn ($r) => $r->created_at->format('d M Y'))
                ->addColumn('action', function ($row) {
                    $html = '';
                    if (auth()->user()->can('connector.manage_tokens')) {
                        $toggle = $row->is_active ? 'revoke' : 'activate';
                        $toggleLabel = $row->is_active ? __('connector::lang.revoke_token') : __('messages.activate');
                        $html .= '<button type="button" class="btn btn-xs btn-warning toggle_token_btn" data-id="' . $row->id . '" data-action="' . $toggle . '">'
                            . '<i class="fas fa-toggle-' . ($row->is_active ? 'off' : 'on') . '"></i> ' . $toggleLabel . '</button>&nbsp;';
                        $html .= '<button type="button" class="btn btn-xs btn-danger delete_token_btn" data-id="' . $row->id . '">'
                            . '<i class="fas fa-trash"></i> ' . __('messages.delete') . '</button>';
                    }

                    return $html;
                })
                ->rawColumns(['is_active', 'action'])
                ->make(true);
        }

        return view('connector::tokens.index');
    }

    public function store(Request $request)
    {
        $business_id = $request->session()->get('user.business_id');

        if (! auth()->user()->can('connector.manage_tokens')) {
            abort(403, 'Unauthorized action.');
        }

        $request->validate([
            'description' => 'nullable|string|max:255',
            'expires_at'  => 'nullable|date|after:today',
        ]);

        $rawToken = Str::random(60);

        $tokenRecord = ConnectorApiToken::create([
            'business_id' => $business_id,
            'user_id'     => auth()->id(),
            'token'       => $rawToken,
            'description' => $request->description,
            'is_active'   => true,
            'expires_at'  => $request->expires_at ?: null,
        ]);

        return response()->json([
            'success'   => true,
            'msg'       => __('lang_v1.success'),
            'token'     => $rawToken,
            'token_id'  => $tokenRecord->id,
        ]);
    }

    public function toggle($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! auth()->user()->can('connector.manage_tokens')) {
            abort(403, 'Unauthorized action.');
        }

        $token = ConnectorApiToken::where('business_id', $business_id)->findOrFail($id);
        $token->update(['is_active' => ! $token->is_active]);

        return response()->json(['success' => true, 'msg' => __('lang_v1.success')]);
    }

    public function destroy($id)
    {
        $business_id = request()->session()->get('user.business_id');

        if (! auth()->user()->can('connector.manage_tokens')) {
            abort(403, 'Unauthorized action.');
        }

        ConnectorApiToken::where('business_id', $business_id)->findOrFail($id)->delete();

        return response()->json(['success' => true, 'msg' => __('lang_v1.success')]);
    }
}
