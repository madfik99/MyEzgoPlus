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
        'created',
    ];

    protected $casts = [
        'deposit'      => 'decimal:2',
        'total_sale'   => 'decimal:2',
        'pickup_date'  => 'datetime',
        'return_date'  => 'datetime',
        'modified'     => 'datetime',
        'created'      => 'datetime',
    ];

    /** Relationships */
    public function booking()
    {
        return $this->belongsTo(BookingTrans::class, 'booking_trans_id');
    }

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }

    public function saleLogs()
    {
        return $this->hasMany(SaleLog::class, 'sale_id');
    }
    
    public function logs()
    {
        return $this->hasMany(\App\Models\SaleLog::class, 'sale_id');
    }

    public function journalLogs()
    {
        return $this->hasMany(JournalLog::class, 'sale_id');
    }

    public function outstandingSales()
    {
        return $this->hasMany(OutstandingSale::class, 'sale_id');
    }
}
