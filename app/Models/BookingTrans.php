<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BookingTrans extends Model
{
    protected $table = 'booking_trans';

    public $timestamps = false; // if you're using `created` instead of `created_at`

    protected $primaryKey = 'id'; // ID is manually managed (not auto-increment)
    public $incrementing = true;

    protected $fillable = [
        'sequence', 'month', 'year', 'agreement_no', 'pickup_date', 'pickup_location', 'pickup_time',
        'return_date', 'return_date_final', 'return_location', 'return_time',
        'p_cost', 'p_address', 'p_address2', 'r_cost', 'r_address', 'r_address2',
        'charges', 'option_rental_id', 'discount_coupon', 'discount_amount',
        'vehicle_id', 'cdw_log_id', 'day', 'sub_total', 'payment_status', 'payment_type',
        'est_total', 'customer_id', 'created', 'refund_dep', 'refund_dep_payment', 'refund_dep_status',
        'type', 'balance', 'other_details', 'other_details_payment_type', 'other_details_price',
        'damage_charges', 'damage_charges_details', 'damage_charges_payment_type',
        'missing_items_charges', 'missing_items_charges_details', 'missing_items_charges_payment_type',
        'additional_cost', 'additional_cost_details', 'additional_cost_payment_type',
        'damage_total_cost', 'damage_payment_made', 'damage_payment_type',
        'outstanding_extend_cost', 'outstanding_extend', 'outstanding_extend_type_of_payment',
        'agent_id', 'delete_status', 'reason', 'available', 'remark',
        'branch', 'staff_id', 'insert_type', 'payment_id'
    ];

    public function staff() {
        return $this->belongsTo(User::class, 'staff_id');
    }
    
     // 🔁 NEW: BookingTrans has many extensions
    public function extensions()
    {
        return $this->hasMany(Extend::class, 'booking_trans_id');
    }

    // 🔁 NEW: BookingTrans belongs to a vehicle
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    // 🔁 (Optional) If you have a Customer model:
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function sales()
    {
        return $this->hasMany(Sale::class, 'booking_trans_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    public function pickupLocation()
    {
        return $this->belongsTo(LocationBooking::class, 'pickup_location','id');
    }

    public function returnLocation()
    {
        return $this->belongsTo(LocationBooking::class, 'return_location','id');
    }
    public function checklist()
    {
        return $this->hasOne(Checklist::class, 'booking_trans_id');
    }

    // ✅ CORRECT
    public function cdwLog()
    {
        return $this->hasOne(CDWLog::class, 'booking_trans_id');
    }

    
    public function class()
    {
        return $this->belongsTo(ClassModel::class, 'class_id');
    }

    public function uploads()    
    { 
        return $this->hasMany(UploadData::class, 'booking_trans_id'); 
    }

}
