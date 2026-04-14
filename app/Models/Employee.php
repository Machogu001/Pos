<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasFactory;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'id','firstname','lastname','username','email','gender','phone','remaining_leave','total_leave',
        'birth_date','department_id','designation_id','office_shift_id','joining_date',
        'leaving_date','marital_status','employment_type','city','province','zipcode','address','resume','avatar','document',
        'country','company_id','business_id','facebook','skype','whatsapp','twitter','linkedin','hourly_rate','basic_salary',
        'system_user_id','is_system_user','sync_disabled'
    ];

    protected $casts = [
        'id'     => 'integer',
        'company_id'     => 'integer',
        'business_id'    => 'integer',
        'department_id'  => 'integer',
        'designation_id' => 'integer',
        'office_shift_id' => 'integer',
        'system_user_id' => 'integer',
        'is_system_user' => 'boolean',
        'sync_disabled' => 'boolean',
        'hourly_rate' => 'double',
        'basic_salary' => 'integer',
        'remaining_leave' => 'integer',
        'total_leave' => 'integer',
    ];


    public function company()
    {
        return $this->belongsTo('App\Models\Company', 'company_id');
    }

    public function department()
    {
        return $this->belongsTo('App\Models\Department', 'department_id');
    }

    public function designation()
    {
        return $this->belongsTo('App\Models\Designation', 'designation_id');
    }

    public function office_shift()
    {
        return $this->belongsTo('App\Models\OfficeShift', 'office_shift_id');
    }

    // camelCase alias so both snake_case and camelCase eager-load work
    public function officeShift()
    {
        return $this->belongsTo('App\Models\OfficeShift', 'office_shift_id');
    }

    
    public function attendance()
    {
        return $this->hasMany(Attendance::class);
    }

    public function leave()
    {
        return $this->hasMany(Leave::class)
        ->select('id','employee_id','start_date','end_date','status')
        ->where('status' , 'approved');
    }

}
