<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndicationUseDetails extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'indication_use_details';

    protected $fillable = [
        'indication_id',
        'row_id',
        'organism'
    ];

}
