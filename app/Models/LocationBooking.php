<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LocationBooking extends Model
{
    protected $table = 'location';
    public $timestamps = false;

    protected $fillable = [
        'description',
        'default',
        'status',
        'address',
        'phone_no',
        'email',
        'hint',
        'initial',
        'latitude',
        'longitude',
        'radius',
        'mid',
        'mdate',
        'cid',
        'cdate',
    ];

    public function bookingTrans()
    {
        return $this->hasMany(BookingTrans::class, 'pickup_location_id');
    }
}
