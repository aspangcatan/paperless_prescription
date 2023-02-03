<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Immunocompromising extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'immunocompromising_conditions';

    protected $fillable = [
        'prescription_id',
        'description'
    ];
}
