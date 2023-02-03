<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PrescriptionLog extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'prescription_logs';

    protected $fillable = [
        'prescription_id',
        'released_by',
        'released_to',
        'date_forwarded'
    ];
}
