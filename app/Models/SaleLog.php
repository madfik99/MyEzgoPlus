<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleLog extends Model
{
    protected $table = 'sale_log';

    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $keyType = 'int';

    public $timestamps = false;

    protected $fillable = [
        'sale_id',
        'daily_sale',
        'hour',
        'day',
        'type',
        'week1','week2','week3','week4','week5',
        'jan','feb','march','apr','may','june','july','aug','sept','oct','nov','dis',
        'year',
        'date',
        'created',
    ];

    protected $casts = [
        'daily_sale' => 'decimal:2',
        'week1' => 'decimal:2',
        'week2' => 'decimal:2',
        'week3' => 'decimal:2',
        'week4' => 'decimal:2',
        'week5' => 'decimal:2',
        'jan'   => 'decimal:2',
        'feb'   => 'decimal:2',
        'march' => 'decimal:2',
        'apr'   => 'decimal:2',
        'may'   => 'decimal:2',
        'june'  => 'decimal:2',
        'july'  => 'decimal:2',
        'aug'   => 'decimal:2',
        'sept'  => 'decimal:2',
        'oct'   => 'decimal:2',
        'nov'   => 'decimal:2',
        'dis'   => 'decimal:2',
        'year'  => 'integer',
        'date'  => 'datetime',
        'created' => 'datetime',
    ];

    // If you have a Sale model, keep this. Otherwise, comment it out.
    public function sale()
    {
        return $this->belongsTo(Sale::class, 'sale_id');
    }
}
