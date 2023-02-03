<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InfectiousDiagnosis extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'infectious_diagnosis';

    protected $fillable = [
        'prescription_id',
        'description'
    ];
}
