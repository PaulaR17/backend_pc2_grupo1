<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Prediction extends Model
{
    use SoftDeletes;

    protected $table = 'predictions';
    public $timestamps = false;

    protected $fillable = [
        'district',
        'for_date',
        'probability',
        'level',
        'predicted_at',
        'model_type',
        'target_type',
    ];

    protected $casts = [
        'district' => 'integer',
        'for_date' => 'date',
        'probability' => 'decimal:4',
        'predicted_at' => 'datetime',
    ];
}
