<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;

class Extend extends Model
{
    protected $table = 'extend';

    protected $fillable = [
        'sale_id',
        'booking_trans_id',
        'vehicle_id',
        'extend_type',
        'auto_extend_status',
        'extend_from_date',
        'extend_from_time',
        'extend_to_date',
        'extend_to_time',
        'payment_status',
        'payment_type',
        'discount_coupon',
        'discount_amount',
        'price',
        'total',
        'payment',
        'mid',
        'm_date',
        'cid',
        'c_date',
    ];

    public $timestamps = false;

    protected $casts = [
        'extend_from_date' => 'datetime',
        'extend_to_date' => 'datetime',
        'm_date' => 'datetime',
        'c_date' => 'datetime',
    ];

    // Optional: relationships
    public function booking()
    {
        return $this->belongsTo(BookingTrans::class, 'booking_trans_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }
    public function sale()
    {
        return $this->belongsTo(\App\Models\Sale::class, 'sale_id');
    }
}
