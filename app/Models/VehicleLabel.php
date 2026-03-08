<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleLabel extends Model
{
    protected $table = 'vehicle_labels';
    public $timestamps = false;

    protected $fillable = [
        'code',
        'name',
    ];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'label_id', 'id');
    }
}