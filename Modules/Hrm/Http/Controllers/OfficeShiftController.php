<?php

namespace Modules\Hrm\Http\Controllers;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Models\OfficeShift;
use App\Models\Company;
use Carbon\Carbon;
use DateTime;
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
        $this->authorizeForUser($this->getAuthUser($request), 'view', OfficeShift::class);

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

        foreach ($office_shifts as $office_shift) {

            $item['id'] = $office_shift->id;
            $item['name'] = $office_shift->name;
            $item['company_id'] = $office_shift['company']->id;
            $item['company_name'] = $office_shift['company']->name;
            $item['monday_in'] = $office_shift->monday_in?substr($office_shift->monday_in, 0, -2):NULL;
            $item['monday_out'] = $office_shift->monday_out?substr($office_shift->monday_out, 0, -2):NULL;
            $item['tuesday_in'] = $office_shift->tuesday_in?substr($office_shift->tuesday_in, 0, -2):NULL;
            $item['tuesday_out'] = $office_shift->tuesday_out?substr($office_shift->tuesday_out, 0, -2):NULL;
            $item['wednesday_in'] = $office_shift->wednesday_in?substr($office_shift->wednesday_in, 0, -2):NULL;
            $item['wednesday_out'] = $office_shift->wednesday_out?substr($office_shift->wednesday_out, 0, -2):NULL;
            $item['thursday_in'] = $office_shift->thursday_in?substr($office_shift->thursday_in, 0, -2):NULL;
            $item['thursday_out'] = $office_shift->thursday_out?substr($office_shift->thursday_out, 0, -2):NULL;
            $item['friday_in'] = $office_shift->friday_in?substr($office_shift->friday_in, 0, -2):NULL;
            $item['friday_out'] = $office_shift->friday_out?substr($office_shift->friday_out, 0, -2):NULL;
            $item['saturday_in'] = $office_shift->saturday_in?substr($office_shift->saturday_in, 0, -2):NULL;
            $item['saturday_out'] = $office_shift->saturday_out?substr($office_shift->saturday_out, 0, -2):NULL;
            $item['sunday_in'] = $office_shift->sunday_in?substr($office_shift->sunday_in, 0, -2):NULL;
            $item['sunday_out'] = $office_shift->sunday_out?substr($office_shift->sunday_out, 0, -2):NULL;
            $data[] = $item;
        }


        if ($request->expectsJson()) {
            return response()->json([
                'office_shifts' => $data,
                'totalRows'   => $totalRows,
            ]);
        }

        return view('hrm::office_shifts.index');
    }

    public function create(Request $request)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'create', OfficeShift::class);

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
        $this->authorizeForUser($this->getAuthUser($request), 'create', OfficeShift::class);

        request()->validate([
            'name'           => 'required|string',
            'company_id'     => 'required',
        ]);

    // Only create DateTime instances when values are present to avoid exceptions
    $monday_in = $request['monday_in'] ? new DateTime($request['monday_in']) : null;
    $monday_out = $request['monday_out'] ? new DateTime($request['monday_out']) : null;
    $tuesday_in = $request['tuesday_in'] ? new DateTime($request['tuesday_in']) : null;
    $tuesday_out = $request['tuesday_out'] ? new DateTime($request['tuesday_out']) : null;
    $wednesday_in = $request['wednesday_in'] ? new DateTime($request['wednesday_in']) : null;
    $wednesday_out = $request['wednesday_out'] ? new DateTime($request['wednesday_out']) : null;
    $thursday_in = $request['thursday_in'] ? new DateTime($request['thursday_in']) : null;
    $thursday_out = $request['thursday_out'] ? new DateTime($request['thursday_out']) : null;
    $friday_in = $request['friday_in'] ? new DateTime($request['friday_in']) : null;
    $friday_out = $request['friday_out'] ? new DateTime($request['friday_out']) : null;
    $saturday_in = $request['saturday_in'] ? new DateTime($request['saturday_in']) : null;
    $saturday_out = $request['saturday_out'] ? new DateTime($request['saturday_out']) : null;
    $sunday_in = $request['sunday_in'] ? new DateTime($request['sunday_in']) : null;
    $sunday_out = $request['sunday_out'] ? new DateTime($request['sunday_out']) : null;

        OfficeShift::create([
            'company_id'     => $request['company_id'],
            'name'           => $request['name'],
            'monday_in'      => $request['monday_in'] && $monday_in ? $monday_in->format('H:iA') : Null,
            'monday_out'     => $request['monday_out'] && $monday_out ? $monday_out->format('H:iA') : Null,
            'tuesday_in'     => $request['tuesday_in'] && $tuesday_in ? $tuesday_in->format('H:iA') : Null,
            'tuesday_out'    => $request['tuesday_out'] && $tuesday_out ? $tuesday_out->format('H:iA') : Null,
            'wednesday_in'   => $request['wednesday_in'] && $wednesday_in ? $wednesday_in->format('H:iA') : Null,
            'wednesday_out'  => $request['wednesday_out'] && $wednesday_out ? $wednesday_out->format('H:iA') : Null,
            'thursday_in'    => $request['thursday_in'] && $thursday_in ? $thursday_in->format('H:iA') : Null,
            'thursday_out'   => $request['thursday_out'] && $thursday_out ? $thursday_out->format('H:iA') : Null,
            'friday_in'      => $request['friday_in'] && $friday_in ? $friday_in->format('H:iA') : Null,
            'friday_out'     => $request['friday_out'] && $friday_out ? $friday_out->format('H:iA') : Null,
            'saturday_in'    => $request['saturday_in'] && $saturday_in ? $saturday_in->format('H:iA') : Null,
            'saturday_out'   => $request['saturday_out'] && $saturday_out ? $saturday_out->format('H:iA') : Null,
            'sunday_in'      => $request['sunday_in'] && $sunday_in ? $sunday_in->format('H:iA') : Null,
            'sunday_out'     => $request['sunday_out'] && $sunday_out ? $sunday_out->format('H:iA') : Null,
        ]);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.office_shifts.index')->with('success', 'Office shift created');
    }

    //------------ function show -----------\\

    public function show($id){
        //
        
    }

    //------------ function edit -----------\\

    public function edit(Request $request , $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'update', OfficeShift::class);

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
        $this->authorizeForUser($this->getAuthUser($request), 'update', OfficeShift::class);

        //monday_in
        if(strlen($request['monday_in']) == 5){
            $monday_in = new DateTime($request['monday_in']);
        }else{
            $monday_in =  new DateTime(substr($request['monday_in'], 0, -2));
        }

         //monday_out
        if(strlen($request['monday_out']) == 5){
            $monday_out = new DateTime($request['monday_out']);
        }else{
            $monday_out =  new DateTime(substr($request['monday_out'], 0, -2));
        }

        //tuesday_in
        if(strlen($request['tuesday_in']) == 5){
            $tuesday_in = new DateTime($request['tuesday_in']);
        }else{
            $tuesday_in =  new DateTime(substr($request['tuesday_in'], 0, -2));
        }

        //tuesday_out
        if(strlen($request['tuesday_out']) == 5){
            $tuesday_out = new DateTime($request['tuesday_out']);
        }else{
            $tuesday_out =  new DateTime(substr($request['tuesday_out'], 0, -2));
        }

        //wednesday_in
        if(strlen($request['wednesday_in']) == 5){
            $wednesday_in = new DateTime($request['wednesday_in']);
        }else{
            $wednesday_in =  new DateTime(substr($request['wednesday_in'], 0, -2));
        }

        //wednesday_out
        if(strlen($request['wednesday_out']) == 5){
            $wednesday_out = new DateTime($request['wednesday_out']);
        }else{
            $wednesday_out =  new DateTime(substr($request['wednesday_out'], 0, -2));
        }

        //thursday_in
        if(strlen($request['thursday_in']) == 5){
            $thursday_in = new DateTime($request['thursday_in']);
        }else{
            $thursday_in =  new DateTime(substr($request['thursday_in'], 0, -2));
        }

        //thursday_out
        if(strlen($request['thursday_out']) == 5){
            $thursday_out = new DateTime($request['thursday_out']);
        }else{
            $thursday_out =  new DateTime(substr($request['thursday_out'], 0, -2));
        }

        //friday_in
        if(strlen($request['friday_in']) == 5){
            $friday_in = new DateTime($request['friday_in']);
        }else{
            $friday_in =  new DateTime(substr($request['friday_in'], 0, -2));
        }

        //friday_out
        if(strlen($request['friday_out']) == 5){
            $friday_out = new DateTime($request['friday_out']);
        }else{
            $friday_out =  new DateTime(substr($request['friday_out'], 0, -2));
        }

        //saturday_in
        if(strlen($request['saturday_in']) == 5){
            $saturday_in = new DateTime($request['saturday_in']);
        }else{
            $saturday_in =  new DateTime(substr($request['saturday_in'], 0, -2));
        }

        //saturday_out
        if(strlen($request['saturday_out']) == 5){
            $saturday_out = new DateTime($request['saturday_out']);
        }else{
            $saturday_out =  new DateTime(substr($request['saturday_out'], 0, -2));
        }

        //sunday_in
        if(strlen($request['sunday_in']) == 5){
            $sunday_in = new DateTime($request['sunday_in']);
        }else{
            $sunday_in =  new DateTime(substr($request['sunday_in'], 0, -2));
        }

        //sunday_out
        if(strlen($request['sunday_out']) == 5){
            $sunday_out = new DateTime($request['sunday_out']);
        }else{
            $sunday_out =  new DateTime(substr($request['sunday_out'], 0, -2));
        }

        OfficeShift::whereId($id)->update([
            'company_id'     => $request['company_id'],
            'name'           => $request['name'],
            'monday_in'      => $request['monday_in']?$monday_in->format('H:iA'):Null,
            'monday_out'     => $request['monday_out']?$monday_out->format('H:iA'):Null,
            'tuesday_in'     => $request['tuesday_in']?$tuesday_in->format('H:iA'):Null,
            'tuesday_out'    => $request['tuesday_out']?$tuesday_out->format('H:iA'):Null,
            'wednesday_in'   => $request['wednesday_in']?$wednesday_in->format('H:iA'):Null,
            'wednesday_out'  => $request['wednesday_out']?$wednesday_out->format('H:iA'):Null,
            'thursday_in'    => $request['thursday_in']?$thursday_in->format('H:iA'):Null,
            'thursday_out'   => $request['thursday_out']?$thursday_out->format('H:iA'):Null,
            'friday_in'      => $request['friday_in']?$friday_in->format('H:iA'):Null,
            'friday_out'     => $request['friday_out']?$friday_out->format('H:iA'):Null,
            'saturday_in'    => $request['saturday_in']?$saturday_in->format('H:iA'):Null,
            'saturday_out'   => $request['saturday_out']?$saturday_out->format('H:iA'):Null,
            'sunday_in'      => $request['sunday_in']?$sunday_in->format('H:iA'):Null,
            'sunday_out'     => $request['sunday_out']?$sunday_out->format('H:iA'):Null,
        ]);


        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.office_shifts.index')->with('success', 'Office shift updated');
    }

    //----------- Delete  office_shift --------------\\

    public function destroy(Request $request, $id)
    {
        $this->authorizeForUser($this->getAuthUser($request), 'delete', OfficeShift::class);

        \DB::transaction(function () use ($id) {

            OfficeShift::whereId($id)->update([
                'deleted_at' => Carbon::now(),
            ]);

        }, 10);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

    return redirect()->route('hrm.office_shifts.index')->with('success', 'Office shift deleted');
    }

    //-------------- Delete by selection  ---------------\\

    public function delete_by_selection(Request $request)
    {

        $this->authorizeForUser($this->getAuthUser($request), 'delete', OfficeShift::class);

        $selectedIds = $request->selectedIds;
        foreach ($selectedIds as $office_shift_id) {
            OfficeShift::whereId($office_shift_id)->update([
                'deleted_at' => Carbon::now(),
            ]);
        }

        return response()->json(['success' => true]);
    }

}
