<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeasonRental extends Model
{
    protected $table = 'season_rental';

    protected $fillable = [
        'season_name',
        'start_date',
        'end_date',
        'status',
        'display_order',
        'cid',
        'cdate',
        'mid',
        'mdate',
    ];

    public $timestamps = false; // no created_at or updated_at

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'cdate' => 'datetime',
        'mdate' => 'datetime',
    ];
}
