<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class History extends Model
{
    use SoftDeletes;

    protected $table = 'history';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'origin_lat',
        'origin_lon',
        'dest_lat',
        'dest_lon',
        'distance_km',
        'duration_min',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class, 'history_id', 'id');
    }
}
