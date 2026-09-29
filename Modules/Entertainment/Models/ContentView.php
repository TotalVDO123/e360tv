<?php

namespace Modules\Entertainment\Models;

use Illuminate\Database\Eloquent\Model;

class ContentView extends Model
{
    protected $table = 'content_views';

    protected $fillable = [
        'content_type',
        'content_id',
        'user_id',
        'device_type',
        'device_id',
    ];
}
