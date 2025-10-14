<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Vehicle extends Model
{
    // Table name
    protected $table = 'vehicle';

    // Primary key
    protected $primaryKey = 'id';

    // Timestamps are not used (no created_at or updated_at)
    public $timestamps = false;

    // Mass assignable attributes
    protected $fillable = [
        'class_id',
        'location_id',
        'investor_id',
        'reg_no',
        'engine_no',
        'chasis_no',
        'make',
        'model',
        'min_rental_time',
        'year',
        'color',
        'sale_date',
        'sale_price',
        'current_mileage',
        'sold_to',
        'engine',
        'fuel_charge',
        'fuel_consumption',
        'emission',
        'availability',
        'rate_type',
        'display_order',
        'power_type',
        'roadtax',
        'property',
        'cdate',
        'branch',
        'mid',
        'mdate',
        'cid',
    ];

    /**
     * Optional: Define relationships (examples)
     */

    // If you have a Class model for vehicle class
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    // Returns the PriceClass that belongs to this vehicle via its ClassModel
    public function priceClassViaClass()
    {
        return optional($this->class)->priceClass;
    }

    // If you have a Location model
    public function location()
    {
        return $this->belongsTo(LocationBooking::class, 'location_id');
    }

    // If you have bookings associated
    public function bookings()
    {
        return $this->hasMany(BookingTrans::class, 'vehicle_id');
    }

    public function latestBooking()
    {
        return $this->hasOne(BookingTrans::class, 'vehicle_id')->latest('id');
    }


    // If you have sales associated
    public function sales()
    {
        return $this->hasMany(Sale::class, 'vehicle_id');
    }

    /**
     * Add computed attributes
     */
    protected $appends = ['car_name'];

    // Computed car_name attribute
    public function getCarNameAttribute()
    {
        return "{$this->make} {$this->model}";
    }
}
