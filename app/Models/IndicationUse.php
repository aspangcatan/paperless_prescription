<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicationUse extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'indication_use';

    protected $fillable = [
        'prescription_id',
        'description'
    ];
}
