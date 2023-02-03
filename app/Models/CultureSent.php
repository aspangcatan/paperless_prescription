<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CultureSent extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'culture_sents';

    protected $fillable = [
        'prescription_id',
        'description'
    ];
}
