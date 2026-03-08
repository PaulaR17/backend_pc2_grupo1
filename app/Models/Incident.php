<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Incident extends Model
{
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