<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NurseStation extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'nurse_stations';
}
