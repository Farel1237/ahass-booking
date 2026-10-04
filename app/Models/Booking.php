<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'plate_number',
        'customer_name',
        'motorcycle_type',
        'service_time',
        'package_id',
        'booking_date',
        'status',
    ];

    protected $casts = [
        'booking_date' => 'date',
    ];

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
