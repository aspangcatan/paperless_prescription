<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescribedMedicine extends Model
{
    use HasFactory;

    const STATUS_PENDING = 0;
    const STATUS_APPROVED = 1;
    const STATUS_REJECTED = 2;
    const STATUS_WITHDRAWN = 3;

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
        'days_requested'
    ];

    public $timestamps = false;
}
