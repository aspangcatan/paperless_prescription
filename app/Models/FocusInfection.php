<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FocusInfection extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'focus_infections';

    protected $fillable = [
        'prescription_id',
        'description'
    ];
}
