<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DysfunctionCriteria extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'dysfunction_criterias';

    protected $fillable = [
        'prescription_id',
        'description'
    ];
}
