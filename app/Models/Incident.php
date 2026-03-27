<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Incident extends Model
{
    use SoftDeletes;

    protected $table = 'incidents';
    public $timestamps = false;

    protected $fillable = [
        'type',
        'title',
        'description',
        'lat',
        'lon',
        'active',
        'created_at',
    ];

    protected $casts = [
        'active' => 'boolean',
        'created_at' => 'datetime',
    ];
}
