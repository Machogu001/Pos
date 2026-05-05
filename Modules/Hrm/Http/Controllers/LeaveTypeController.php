<?php

namespace Modules\Hrm\Http\Controllers;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\LeaveType;
use App\Models\Leave;
use Carbon\Carbon;

class LeaveTypeController extends Controller
{

    protected function getAuthUser($request)
    {
        // Prefer api user if present (for API calls), otherwise fall back to web user
        return $request->user('api') ?? $request->user() ?? auth()->user();
    }

    //----------- GET ALL  Leave type --------------\\

    public function index(Request $request)
    {
    // Canonical web entrypoint for leave types is the Essentials flow.
    if (! $request->wantsJson() && ! $request->expectsJson()) {
        $target = url('/hrm/leave-type');
        if ($request->getQueryString()) {
            $target .= '?' . $request->getQueryString();
        }

        return redirect($target);
    }

    $this->authorizeForUser($this->getAuthUser($request), 'view', Leave::class);

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number; only computed when perPage is numeric
        $offSet = 0;
        if (is_numeric($perPage) && intval($perPage) > 0) {
            $offSet = ($pageStart * intval($perPage)) - intval($perPage);
        }

        // sanitize ordering inputs
        $order = $request->SortField;
        $dir = $request->SortType;
        if (!in_array(strtolower($dir ?? ''), ['asc', 'desc'])) {
            $dir = 'desc';
        }
    $allowed = ['id', 'name', 'title', 'created_at', 'updated_at'];
        if (empty($order) || !in_array($order, $allowed)) {
            $order = 'id';
        }

        // Prefer 'name' column if present in DB, otherwise fall back to 'title'
        $labelCol = \Illuminate\Support\Facades\Schema::hasColumn('leave_types', 'name') ? 'name' : (\Illuminate\Support\Facades\Schema::hasColumn('leave_types', 'title') ? 'title' : null);

        $q = LeaveType::where('deleted_at', '=', null);
        // Search across name/title whichever present
        $q = $q->where(function ($query) use ($request) {
            return $query->when($request->filled('search'), function ($query) use ($request) {
                $s = $request->search;
                $query->whereRaw("COALESCE(name, '') LIKE ?", ["%{$s}%"])
                    ->orWhereRaw("COALESCE(title, '') LIKE ?", ["%{$s}%"]);
            });
        });

        $totalRows = $q->count();
        if ($perPage == "-1") { $perPage = $totalRows; }

        if ($request->wantsJson() || $request->expectsJson()) {
            if (is_numeric($perPage) && intval($perPage) > 0) {
                $items = $q->offset($offSet)->limit(intval($perPage))->orderBy($order, $dir)->get();
            } else {
                $items = $q->orderBy($order, $dir)->get();
            }

            // Normalize name/title to a single 'name' key for clients
            $norm = $items->map(function($t) use ($labelCol) {
                $label = $labelCol ? ($t->{$labelCol} ?? '') : ($t->name ?? $t->title ?? '');
                return ['id' => $t->id, 'name' => $label, 'raw' => $t];
            });

            return response()->json([
                'leave_types' => $norm,
                'totalRows' => $totalRows,
            ]);
        }

        // For browser requests render a simple paginated view
        $perPageForPaginator = (string)$request->limit === '-1' ? ($totalRows > 0 ? $totalRows : 1) : (is_numeric($perPage) && intval($perPage) > 0 ? intval($perPage) : 10);
        $pageStart = max(1, (int) request()->get('page', 1));
        $paginator = $q->orderBy($order, $dir)->paginate($perPageForPaginator, ['*'], 'page', $pageStart);

        $data = [];
        foreach ($paginator->items() as $t) {
            $label = $labelCol ? ($t->{$labelCol} ?? '') : ($t->name ?? $t->title ?? '');
            $data[] = ['id' => $t->id, 'name' => $label, 'raw' => $t];
        }

        return view('hrm::leave_types.index', ['leave_types' => $data, 'totalRows' => $totalRows, 'paginator' => $paginator]);
    }

    //----------- Store new Leave --------------\\

    public function store(Request $request)
    {
    $this->authorizeForUser($this->getAuthUser($request), 'create', Leave::class);

        request()->validate([
            // accept either 'name' or 'title' from client
            'name' => 'nullable|string',
            'title' => 'nullable|string',
        ]);

        $value = $request->input('name', $request->input('title'));
        $col = \Illuminate\Support\Facades\Schema::hasColumn('leave_types', 'name') ? 'name' : 'title';
        LeaveType::create([$col => $value]);
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('hrm.leave_types.index')->with('status', __('lang_v1.success') . ': ' . __('messages.added_successfully'));
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
        }

    // Show create form (or JSON for AJAX)
    public function create(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'create', Leave::class);
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['ok' => true]);
        }
        return view('hrm::leave_types.create');
    }

    // Show edit form (or JSON for AJAX)
    public function edit(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', Leave::class);
        $leave_type = LeaveType::where('deleted_at', '=', null)->findOrFail($id);
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['leave_type' => $leave_type]);
        }
        return view('hrm::leave_types.edit');
    }

    //-----------Update Leave --------------\\

    public function update(Request $request, $id)
    {
    $this->authorizeForUser($this->getAuthUser($request), 'update', Leave::class);

        request()->validate([
            'name' => 'nullable|string',
            'title' => 'nullable|string',
        ]);
        $value = $request->input('name', $request->input('title'));
        $col = \Illuminate\Support\Facades\Schema::hasColumn('leave_types', 'name') ? 'name' : 'title';
        LeaveType::whereId($id)->update([$col => $value]);
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->route('hrm.leave_types.index')->with('status', __('lang_v1.success') . ': ' . __('messages.updated_successfully'));
    }

    //----------- Delete  Leave --------------\\

    public function destroy(Request $request, $id)
    {
    $this->authorizeForUser($this->getAuthUser($request), 'delete', Leave::class);

        LeaveType::whereId($id)->update([
            'deleted_at' => Carbon::now(),
        ]);
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('status', __('messages.deleted_successfully'));
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

    $this->authorizeForUser($this->getAuthUser($request), 'delete', Leave::class);

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $leave_type_id) {
            LeaveType::whereId($leave_type_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }
        if ($request->wantsJson() || $request->expectsJson()) {
            return response()->json(['success' => true]);
        }
        return redirect()->back()->with('status', __('messages.deleted_successfully'));
    }


}
