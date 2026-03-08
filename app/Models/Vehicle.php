<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    protected $table = 'vehicles';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'label_id',
        'type',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function label()
    {
        return $this->belongsTo(VehicleLabel::class, 'label_id', 'id');
    }
}