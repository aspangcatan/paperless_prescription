<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory;

    #REVIEW STATUS: AGGREGATE OF THE RX'S MEDICINE STATUSES (SEE PrescriptionWorkflow::refreshReviewStatus)
    const REVIEW_PENDING = 0;
    const REVIEW_PARTIALLY_APPROVED = 1;
    const REVIEW_APPROVED = 2;
    const REVIEW_REJECTED = 3;

    protected $connection = "mysql";
    protected $table = "prescriptions";

    protected $fillable = [
        'hospital_id',
        'physician_id',
        'attending_physician',
        'station',
        'ward',
        'bed_no'
    ];
}
