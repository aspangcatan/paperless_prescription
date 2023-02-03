<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SepsisCriteria extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'sepsis_criterias';

    protected $fillable = [
        'prescription_id',
        'description'
    ];
}
