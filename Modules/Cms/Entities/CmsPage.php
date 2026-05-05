<?php

namespace Modules\Cms\Entities;

use Illuminate\Database\Eloquent\Model;

class CmsPage extends Model
{
    protected $table = 'cms_pages';

    protected $fillable = [
        'business_id',
        'title',
        'slug',
        'content',
        'status',
        'created_by',
    ];
}
