<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UploadData extends Model
{
    protected $table = 'upload_data';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'position',
        'no',
        'sequence',
        'label',
        'remarks',
        'customer_id',
        'booking_trans_id',
        'file_name',
        'file_size',
        'file_type',
        'status',
        'vehicle_id',
        'modified',
        'mid',
        'created',
        'cid'
    ];

    public function booking()
    {
        return $this->belongsTo(BookingTrans::class, 'booking_trans_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
