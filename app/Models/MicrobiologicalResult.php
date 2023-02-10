<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MicrobiologicalResult extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'microbiological_results';

    protected $fillable = [
        'prescription_id',
        'date',
        'specimen',
        'pathogen'
    ];

}
