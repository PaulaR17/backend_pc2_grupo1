<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Incident extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'incidents';

    // La tabla `incidents` solo tiene `created_at` (con valor por defecto NOW).
    // No hay columna `updated_at`, así que desactivamos los timestamps
    // automáticos de Eloquent para que no intente escribirla.
    public $timestamps = false;

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