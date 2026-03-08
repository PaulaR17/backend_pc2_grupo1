<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventory';
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'item_id',
        'quantity',
        'updated_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function item()
    {
        return $this->belongsTo(Item::class, 'item_id', 'id');
    }
}