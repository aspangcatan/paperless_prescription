<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomisPatient extends Model
{
    use HasFactory;

    protected $connection = 'homis';
    protected $table = 'hperson';
}
