<?php

#esto dice como me comunico con la base de datos 
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
    protected $table='students';
    protected $fillable=['name','lastname','email'];
    use SoftDeletes;
}
