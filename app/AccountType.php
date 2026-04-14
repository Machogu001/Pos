<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AccountType extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    public static function majorTypeOrderCase($column = 'name')
    {
        return "CASE
            WHEN LOWER({$column}) LIKE '%asset%' THEN 1
            WHEN LOWER({$column}) LIKE '%liabilit%' THEN 2
            WHEN LOWER({$column}) LIKE '%equity%' OR LOWER({$column}) LIKE '%capital%' THEN 3
            WHEN LOWER({$column}) LIKE '%income%' OR LOWER({$column}) LIKE '%revenue%' THEN 4
            WHEN LOWER({$column}) LIKE '%cost of goods%' OR LOWER({$column}) LIKE '%cogs%' THEN 5
            WHEN LOWER({$column}) LIKE '%expense%' THEN 6
            ELSE 99
        END";
    }

    public function scopeOrderedForChart($query, $column = 'name')
    {
        return $query->orderByRaw(self::majorTypeOrderCase($column))
            ->orderBy($column);
    }

    public function sub_types()
    {
        return $this->hasMany(\App\AccountType::class, 'parent_account_type_id')
            ->orderByRaw(self::majorTypeOrderCase('name'))
            ->orderBy('name');
    }

    public function parent_account()
    {
        return $this->belongsTo(\App\AccountType::class, 'parent_account_type_id');
    }
}
