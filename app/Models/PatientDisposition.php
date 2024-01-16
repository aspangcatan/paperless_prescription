<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientDisposition extends Model
{
    use HasFactory;

    protected $connection = "mysql";
    protected $table = "patient_disposition_history";
}
