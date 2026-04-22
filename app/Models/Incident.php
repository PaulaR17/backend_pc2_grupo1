<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Incident extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'incidents';
    public $timestamps = true; 
    protected $fillable = [
        'type',
        'title',
        'description',
        'lat',
        'lon',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
        'lat' => 'float',
        'lon' => 'float',
    ];

}