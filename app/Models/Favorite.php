<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Favorite extends Model
{
    use SoftDeletes;

    protected $table = 'favorites';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'history_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function history()
    {
        return $this->belongsTo(History::class, 'history_id', 'id');
    }
}
