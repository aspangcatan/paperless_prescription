<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InterventionDetail extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'intervention_details';
    protected $fillable = [
        'prescription_id',
        'clinical_stability',
        'oral_intake'
    ];
}
