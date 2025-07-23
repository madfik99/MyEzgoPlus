<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CDWLog extends Model
{
    protected $table = 'cdw_log';
    public $timestamps = false;

    protected $fillable = [
        'booking_trans_id',
        'cdw_id',
        'sale_id',
        'amount',
        'status',
        'mid',
        'modified',
        'cid',
        'created'
    ];

    // Relationships (optional)
    public function booking()
    {
        return $this->belongsTo(BookingTrans::class, 'booking_trans_id');
    }

    public function cdw()
    {
        return $this->belongsTo(CDW::class, 'cdw_id');
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
