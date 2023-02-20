<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConcurrentTherapy extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'concurrent_therapy';

    protected $fillable = [
        'hospital_id',
        'concurrent_date',
        'antimicrobial',
        'dosing_regimen',
        'start_date',
        'end_date',
        'days_approved'
    ];

    public $timestamps = false;
}
