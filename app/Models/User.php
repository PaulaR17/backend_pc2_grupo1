<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
// use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = [
        'name',
        'mail',
        'rol',
        'status',
    ];

    protected $hidden = [
        //para ocultar el pssw y token
    ];

    protected $casts = [
        'status' => 'boolean',
    ];

    public function profile()
    {
        return $this->hasOne(UserProfile::class, 'user_id', 'id');
    }

    public function vehicles()
    {
        return $this->hasMany(Vehicle::class, 'user_id', 'id');
    }

    public function history()
    {
        return $this->hasMany(History::class, 'user_id', 'id');
    }

    public function favorites()
    {
        return $this->hasMany(Favorite::class, 'user_id', 'id');
    }

    public function pet()
    {
        return $this->hasOne(Pet::class, 'user_id', 'id');
    }
    
    public function badges()
    {
        return $this->hasMany(Badge::class, 'user_id', 'id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_id', 'id');
    }
}