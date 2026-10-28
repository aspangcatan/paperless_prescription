<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PharmacyReview extends Model
{
    use HasFactory;
    protected $connection = 'mysql';
    protected $table = 'pharmacy_reviews';
    protected $fillable = ['prescription_id'];

}
