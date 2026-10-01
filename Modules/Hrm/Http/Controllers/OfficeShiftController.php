<?php

namespace Modules\Hrm\Http\Controllers;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\OfficeShift;
use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Support\Facades\Schema;

class OfficeShiftController extends Controller
{

    protected function businessId()
    {
        return session('business.id');
    }

    protected function hasDayColumns(): bool
    {
        return Schema::hasColumn('office_shifts', 'monday_in');
    }

    protected function companies()
    {
        $businessId = $this->businessId();
        $columns = ['id', 'name'];
        if (Schema::hasColumn('companies', 'business_id')) {
            $columns[] = 'business_id';
        }

        return Company::query()
            ->whereNull('deleted_at')
            ->when($businessId && Schema::hasColumn('companies', 'business_id'), function ($query) use ($businessId) {
                return $query->where(function ($tenantQuery) use ($businessId) {
                    $tenantQuery->where('business_id', $businessId)
                        ->orWhereNull('business_id');
                });
            })
            ->orderByDesc('id')
                ->get($columns);
    }

    protected function normalizeTimeInput(?string $value): ?string
    {
        if (empty($value)) {
            return null;
        }

        $clean = preg_replace('/\s*[AP]M$/i', '', trim($value));
        $ts = strtotime($clean);

        return $ts !== false ? date('H:i', $ts) : null;
    }

    protected function officeShiftPayload(Request $request): array
    {
        $payload = [
            'company_id' => $request->input('company_id'),
            'name' => $request->input('name'),
        ];

        if ($this->hasDayColumns()) {
            foreach (['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'] as $day) {
                $payload[$day . '_in'] = $this->normalizeTimeInput($request->input($day . '_in'));
                $payload[$day . '_out'] = $this->normalizeTimeInput($request->input($day . '_out'));
            }
        } else {
            if (Schema::hasColumn('office_shifts', 'start_time')) {
                $payload['start_time'] = $this->normalizeTimeInput($request->input('start_time'));
            }
            if (Schema::hasColumn('office_shifts', 'end_time')) {
                $payload['end_time'] = $this->normalizeTimeInput($request->input('end_time'));
            }
            if (Schema::hasColumn('office_shifts', 'break_minutes')) {
                $payload['break_minutes'] = $request->filled('break_minutes') ? (int) $request->input('break_minutes') : null;
            }
            if (Schema::hasColumn('office_shifts', 'notes')) {
                $payload['notes'] = $request->input('notes');
            }
        }

        return $payload;
    }

    protected function getAuthUser($request)
    {
        return $request->user('api') ?? $request->user() ?? auth()->user();
    }

    //----------- GET ALL  office_shift --------------\\

    public function index(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.office_shifts'))) {
            abort(403);
        }

        // Avoid errors if office_shifts table is not present
        if (!Schema::hasTable('office_shifts')) {
            if ($request->expectsJson()) {
                return response()->json(['office_shifts' => [], 'totalRows' => 0]);
            }
            return view('hrm::office_shifts.index');
        }

        // How many items do you want to display.
        $perPage = $request->limit;
        $pageStart = \Request::get('page', 1);
        // Start displaying items from this number;
        $offSet = ($pageStart * $perPage) - $perPage;
        $order = $request->SortField;
        $dir = $request->SortType;
        $data = array();

        if (!in_array(strtolower($dir ?? ''), ['asc', 'desc'])) {
            $dir = 'desc';
        }
        $allowed = ['id', 'name', 'company_id', 'created_at'];
        if (empty($order) || !in_array($order, $allowed)) {
            $order = 'id';
        }

        $hasDayCols = $this->hasDayColumns();

        // if company relation/table or column missing, avoid eager load join errors
        $office_shifts = OfficeShift::where('deleted_at', '=', null)
            ->when(Schema::hasTable('companies') && Schema::hasColumn('office_shifts','company_id'), function($q){
                return $q->with('company:id,name');
            })

        // Search With Multiple Param
            ->where(function ($query) use ($request) {
                return $query->when($request->filled('search'), function ($query) use ($request) {
                    return $query->where('name', 'LIKE', "%{$request->search}%");
                });
            });
        $totalRows = $office_shifts->count();
        if ($perPage == "-1") {
            $perPage = $totalRows;
        }

        if (is_numeric($perPage) && intval($perPage) > 0) {
            $office_shifts = $office_shifts->offset($offSet)
                ->limit(intval($perPage))
                ->orderBy($order, $dir)
                ->get();
        } else {
            $office_shifts = $office_shifts->orderBy($order, $dir)->get();
        }

    $to24Time = function ($value) {
        if (empty($value)) {
            return null;
        }
        $raw = trim((string) $value);
        $ts = strtotime($raw);
        if ($ts === false) {
            $raw = preg_replace('/\s*[AP]M$/i', '', $raw);
            $ts = strtotime($raw);
        }

        return $ts !== false ? date('H:i', $ts) : null;
    };
    foreach ($office_shifts as $office_shift) {

            $item['id'] = $office_shift->id;
            $item['name'] = $office_shift->name;
            $item['company_id'] = isset($office_shift['company']->id) ? $office_shift['company']->id : null;
            $item['company_name'] = isset($office_shift['company']->name) ? $office_shift['company']->name : null;

            if ($hasDayCols) {
                $item['monday_in'] = $to24Time($office_shift->monday_in);
                $item['monday_out'] = $to24Time($office_shift->monday_out);
                $item['tuesday_in'] = $to24Time($office_shift->tuesday_in);
                $item['tuesday_out'] = $to24Time($office_shift->tuesday_out);
                $item['wednesday_in'] = $to24Time($office_shift->wednesday_in);
                $item['wednesday_out'] = $to24Time($office_shift->wednesday_out);
                $item['thursday_in'] = $to24Time($office_shift->thursday_in);
                $item['thursday_out'] = $to24Time($office_shift->thursday_out);
                $item['friday_in'] = $to24Time($office_shift->friday_in);
                $item['friday_out'] = $to24Time($office_shift->friday_out);
                $item['saturday_in'] = $to24Time($office_shift->saturday_in);
                $item['saturday_out'] = $to24Time($office_shift->saturday_out);
                $item['sunday_in'] = $to24Time($office_shift->sunday_in);
                $item['sunday_out'] = $to24Time($office_shift->sunday_out);
            } else {
                // fallback to start_time/end_time if available
                $item['start_time'] = $to24Time($office_shift->start_time ?? null);
                $item['end_time'] = $to24Time($office_shift->end_time ?? null);
                $item['break_minutes'] = isset($office_shift->break_minutes) ? $office_shift->break_minutes : null;
            }

            $data[] = $item;
        }
        $office_shifts_for_view = collect($data);


        if ($request->expectsJson()) {
            return response()->json([
                'office_shifts' => $data,
                'totalRows'   => $totalRows,
            ]);
        }

    $companies = $this->companies();
    return view('hrm::office_shifts.index', compact('office_shifts_for_view', 'companies', 'totalRows', 'perPage', 'pageStart', 'hasDayCols'));
    }

    public function create(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.office_shifts'))) {
            abort(403);
        }

        $companies = $this->companies();
        $hasDayCols = $this->hasDayColumns();
        if ($request->expectsJson()) {
            return response()->json([
                'companies' =>$companies,
            ]);
        }

        return view('hrm::office_shifts.create', compact('companies', 'hasDayCols'));

    }

    //----------- Store new office_shift --------------\\

    public function store(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.office_shifts'))) {
            abort(403);
        }

        $rules = [
            'name' => 'required|string',
            'company_id' => 'required',
        ];
        if (! $this->hasDayColumns()) {
            $rules['start_time'] = 'required';
            $rules['end_time'] = 'required';
            $rules['break_minutes'] = 'nullable|integer|min:0';
        }

        request()->validate($rules);

        OfficeShift::create($this->officeShiftPayload($request));

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.office_shifts.index')->with('success', 'Created successfully');
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
    }

    //------------ function edit -----------\\

    public function edit(Request $request , $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.office_shifts'))) {
            abort(403);
        }

        $companies = $this->companies();
        $hasDayCols = $this->hasDayColumns();
        if ($request->expectsJson()) {
            return response()->json([
                'companies' =>$companies,
            ]);
        }

        $office_shift = OfficeShift::findOrFail($id);
        return view('hrm::office_shifts.edit', compact('companies', 'office_shift', 'hasDayCols'));

    }

    //-----------Update office_shift --------------\\

    public function update(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.office_shifts'))) {
            abort(403);
        }

        $rules = [
            'name' => 'required|string',
            'company_id' => 'required',
        ];
        if (! $this->hasDayColumns()) {
            $rules['start_time'] = 'required';
            $rules['end_time'] = 'required';
            $rules['break_minutes'] = 'nullable|integer|min:0';
        }

        request()->validate($rules);

        OfficeShift::whereId($id)->update($this->officeShiftPayload($request));


        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.office_shifts.index')->with('success', 'Updated successfully');
    }

    //----------- Delete  office_shift --------------\\

    public function destroy(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.office_shifts'))) {
            abort(403);
        }

        \DB::transaction(function () use ($id) {

            OfficeShift::whereId($id)->update([
                'deleted_at' => Carbon::now(),
            ]);

        }, 10);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.office_shifts.index')->with('success', 'Deleted successfully');
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || !$user->can('hrm.access')) {
            abort(403);
        }

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $office_shift_id) {
            OfficeShift::whereId($office_shift_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

}
