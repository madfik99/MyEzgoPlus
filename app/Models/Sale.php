<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $table = 'sale';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'title',
        'type',
        'booking_trans_id',
        'vehicle_id',
        'total_day',
        'deposit',
        'total_sale',
        'payment_status',
        'payment_type',
        'image',
        'pickup_date',
        'return_date',
        'referral_number',
        'remark',
        'status',
        'staff_id',
        'mid',
        'modified',
        'created'
    ];

    // Example relationships
    public function booking()
    {
        return $this->belongsTo(BookingTrans::class, 'booking_trans_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
    
}
