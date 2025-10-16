<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutstandingSale extends Model
{
    protected $table = 'outstanding_sale';

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    // Table has `created`/`modified` (not created_at/updated_at)
    public $timestamps = false;

    protected $fillable = [
        'booking_trans_id',
        'sale_id',
        'sale_log_id',
        'coupon',
        'sale_before',
        'sale_after',
        'reason_modified',
        'mid',
        'modified',
        'cid',
        'created',
    ];

    protected $casts = [
        'sale_before' => 'decimal:2',
        'sale_after'  => 'decimal:2',
        'created'     => 'datetime',
        'modified'    => 'datetime',
    ];

    /** Relationships */
    public function bookingTrans()
    {
        return $this->belongsTo(BookingTrans::class, 'booking_trans_id');
    }

    // If you have a Sale model, keep this. Otherwise, comment it out.
    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }

    public function saleLog()
    {
        return $this->belongsTo(SaleLog::class, 'sale_log_id');
    }
}
