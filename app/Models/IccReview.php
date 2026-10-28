<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IccReview extends Model
{
    use HasFactory;

    protected $connection = 'mysql';
    protected $table = 'icc_reviews';
    protected $fillable = ['prescription_id'];
}
