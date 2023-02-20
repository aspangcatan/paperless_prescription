<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturnedMeds extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'returned_meds';
    protected $fillable = ['prescribed_meds_id', 'returned_by', 'returned_amount'];
}
