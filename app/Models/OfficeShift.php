<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OfficeShift extends Model
{
    use HasFactory;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name','company_id','monday_in','monday_out',
        'tuesday_in','tuesday_out','wednesday_in','wednesday_out',
        'thursday_in','thursday_out','friday_in','friday_out',
        'saturday_in','saturday_out','sunday_in','sunday_out',
        'start_time','end_time','break_minutes','notes'

    ];

    protected $casts = [
        'company_id'  => 'integer',
    ];


    public function company()
    {
        return $this->hasOne('App\Models\Company', 'id', 'company_id');
    }

    public function employees()
    {
        return $this->hasMany('App\Models\Employee', 'office_shift_id');
    }

    /**
     * Get the shift times for a given day name (e.g. "Monday").
     * Returns ['in' => '08:00', 'out' => '17:00'] or ['in' => null, 'out' => null] when off.
     */
    public function getTimesForDay(string $dayName): array
    {
        $key = strtolower($dayName);
        $inCol  = $key . '_in';
        $outCol = $key . '_out';
        return [
            'in'  => $this->$inCol  ?? null,
            'out' => $this->$outCol ?? null,
        ];
    }

    /**
     * Return true when the given day is an active working day on this shift.
     */
    public function isWorkingDay(string $dayName): bool
    {
        $times = $this->getTimesForDay($dayName);
        return ! empty($times['in']) && ! empty($times['out']);
    }


}
