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

    // determine if per-day columns exist; otherwise fall back to generic start_time/end_time
    $hasDayCols = Schema::hasColumn('office_shifts', 'monday_in');
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
                $item['start_time'] = isset($office_shift->start_time) ? $office_shift->start_time : null;
                $item['end_time'] = isset($office_shift->end_time) ? $office_shift->end_time : null;
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

    $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
    return view('hrm::office_shifts.index', compact('office_shifts_for_view', 'companies', 'totalRows', 'perPage', 'pageStart'));
    }

    public function create(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.office_shifts'))) {
            abort(403);
        }

        $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
        if ($request->expectsJson()) {
            return response()->json([
                'companies' =>$companies,
            ]);
        }

        return view('hrm::office_shifts.create', compact('companies'));

    }

    //----------- Store new office_shift --------------\\

    public function store(Request $request)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.office_shifts'))) {
            abort(403);
        }

        request()->validate([
            'name'           => 'required|string',
            'company_id'     => 'required',
        ]);

    // Only create DateTime instances when values are present to avoid exceptions
    $hasDayCols = Schema::hasColumn('office_shifts', 'monday_in');
    $createData = [
        'company_id' => $request['company_id'],
        'name' => $request['name'],
    ];

    if ($hasDayCols) {
        $parseTime = function ($val) {
            if (empty($val)) {
                return null;
            }
            $ts = strtotime((string) $val);
            return $ts !== false ? date('H:i', $ts) : null;
        };

        $createData = array_merge($createData, [
            'monday_in' => $parseTime($request['monday_in']),
            'monday_out' => $parseTime($request['monday_out']),
            'tuesday_in' => $parseTime($request['tuesday_in']),
            'tuesday_out' => $parseTime($request['tuesday_out']),
            'wednesday_in' => $parseTime($request['wednesday_in']),
            'wednesday_out' => $parseTime($request['wednesday_out']),
            'thursday_in' => $parseTime($request['thursday_in']),
            'thursday_out' => $parseTime($request['thursday_out']),
            'friday_in' => $parseTime($request['friday_in']),
            'friday_out' => $parseTime($request['friday_out']),
            'saturday_in' => $parseTime($request['saturday_in']),
            'saturday_out' => $parseTime($request['saturday_out']),
            'sunday_in' => $parseTime($request['sunday_in']),
            'sunday_out' => $parseTime($request['sunday_out']),
        ]);
    } else {
        // fallback to generic fields if they exist
        if (Schema::hasColumn('office_shifts', 'start_time') && $request->filled('start_time')) {
            $createData['start_time'] = $request->input('start_time');
        }
        if (Schema::hasColumn('office_shifts', 'end_time') && $request->filled('end_time')) {
            $createData['end_time'] = $request->input('end_time');
        }
        if (Schema::hasColumn('office_shifts', 'break_minutes') && $request->filled('break_minutes')) {
            $createData['break_minutes'] = $request->input('break_minutes');
        }
    }

        OfficeShift::create($createData);

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

        $companies = Company::where('deleted_at', '=', null)->get(['id','name']);
        if ($request->expectsJson()) {
            return response()->json([
                'companies' =>$companies,
            ]);
        }

        $office_shift = OfficeShift::findOrFail($id);
        return view('hrm::office_shifts.edit', compact('companies', 'office_shift'));

    }

    //-----------Update office_shift --------------\\

    public function update(Request $request, $id)
    {
        $user = $this->getAuthUser($request);
        if (!$user || (! $user->can('hrm.access') && ! $user->can('hrm.office_shifts'))) {
            abort(403);
        }

        $hasDayCols = Schema::hasColumn('office_shifts', 'monday_in');

        // Helper: safely parse a time string like "08:00" or "08:00AM" → "H:i", or return null
        $parseTime = function (?string $val): ?string {
            if (empty($val)) {
                return null;
            }
            // Strip AM/PM suffix if present so strtotime handles it cleanly
            $clean = preg_replace('/[AP]M$/i', '', trim($val));
            $ts = strtotime($clean);
            return $ts !== false ? date('H:i', $ts) : null;
        };

        $updateData = [
            'company_id' => $request['company_id'],
            'name' => $request['name'],
        ];

        if ($hasDayCols) {
            $updateData = array_merge($updateData, [
                'monday_in'     => $parseTime($request['monday_in']),
                'monday_out'    => $parseTime($request['monday_out']),
                'tuesday_in'    => $parseTime($request['tuesday_in']),
                'tuesday_out'   => $parseTime($request['tuesday_out']),
                'wednesday_in'  => $parseTime($request['wednesday_in']),
                'wednesday_out' => $parseTime($request['wednesday_out']),
                'thursday_in'   => $parseTime($request['thursday_in']),
                'thursday_out'  => $parseTime($request['thursday_out']),
                'friday_in'     => $parseTime($request['friday_in']),
                'friday_out'    => $parseTime($request['friday_out']),
                'saturday_in'   => $parseTime($request['saturday_in']),
                'saturday_out'  => $parseTime($request['saturday_out']),
                'sunday_in'     => $parseTime($request['sunday_in']),
                'sunday_out'    => $parseTime($request['sunday_out']),
            ]);
        } else {
            if (Schema::hasColumn('office_shifts', 'start_time') && $request->filled('start_time')) {
                $updateData['start_time'] = $request->input('start_time');
            }
            if (Schema::hasColumn('office_shifts', 'end_time') && $request->filled('end_time')) {
                $updateData['end_time'] = $request->input('end_time');
            }
            if (Schema::hasColumn('office_shifts', 'break_minutes') && $request->filled('break_minutes')) {
                $updateData['break_minutes'] = $request->input('break_minutes');
            }
        }

        OfficeShift::whereId($id)->update($updateData);


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
