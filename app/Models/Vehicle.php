<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Vehicle extends Model
{
    use SoftDeletes;

    protected $table = 'vehicles';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'vehicle_label_id',
        'type',
        'nickname',
        'brand',
        'model',
        'year',
        'plate',
        'fuel_type',
        'color_hex',
        'color_name',
        'is_electric',
        'is_default',
    ];

    protected $casts = [
        'year' => 'integer',
        'is_electric' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function label()
    {
        return $this->belongsTo(VehicleLabel::class, 'vehicle_label_id', 'id');
    }
}