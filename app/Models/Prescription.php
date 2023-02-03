<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;
    protected $connection = "mysql";
    protected $table = "prescriptions";

    protected $fillable = [
        'hospital_id',
        'physician_id',
        'station',
        'ward',
        'bed_no'
    ];
}
