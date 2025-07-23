<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptionalRental extends Model
{
    use HasFactory;

    protected $table = 'option_rental';

    protected $fillable = [
        'description',
        'calculation',
        'missing_cond',
        'amount_type',
        'amount',
        'taxable',
        'pic',
        'cid',
        'cdate',
        'mid',
        'mdate',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'cdate' => 'datetime',
        'mdate' => 'datetime',
    ];

    public $timestamps = false; // Because you're using custom timestamp columns (cdate, mdate)
}
