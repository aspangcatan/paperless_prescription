<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescribedMedicine extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'prescribed_medicines';
    protected $fillable = [
        'prescription_id',
        'description',
        'unit',
        'dosage_strength',
        'dosage_unit',
        'frequency',
        'quantity_ordered',
        'quantity_returned'
    ];

    public $timestamps = false;
}
