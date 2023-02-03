<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientHistory extends Model
{
    use HasFactory;

    protected $connection = "mysql";
    protected $table = "patient_history";
    protected $fillable = [
        'prescription_id',
        'hospital_id',
        'name',
        'birthdate',
        'sex',
    ];
}
