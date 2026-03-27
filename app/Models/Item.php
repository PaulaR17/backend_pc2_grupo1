<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Item extends Model
{
    use SoftDeletes;

    protected $table = 'items';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'type',
        'rarity',
        'description',
        'image',
        'price',
        'active',
    ];

    protected $casts = [
        'price' => 'integer',
        'active' => 'boolean',
    ];
}
