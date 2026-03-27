<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pet extends Model
{
    use SoftDeletes;

    protected $table = 'pet';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'name',
        'level',
        'xp',
        'image',
        'updated_at',
    ];

    protected $casts = [
        'level' => 'integer',
        'xp' => 'integer',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }
}
