<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VehicleLabel extends Model
{
    protected $table = 'vehicle_labels';
    public $timestamps = false;

    protected $fillable = [
        'name',
        'description',
    ];

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'vehicle_label_id', 'id');
    }
}