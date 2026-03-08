<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prediction extends Model
{
    protected $table = 'predictions';
    public $timestamps = false;

    protected $fillable = [
        'district',
        'probability',
        'level',
        'predicted_at',
        'model_type',
        'target_type',
    ];

    protected $casts = [
        'district' => 'integer',
        'probability' => 'decimal:4',
        'predicted_at' => 'datetime',
    ];
}