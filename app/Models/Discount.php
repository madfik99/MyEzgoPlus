<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Discount extends Model
{
    protected $table = 'discount'; // Table name if not plural

    // If your primary key is NOT auto-increment, add this line, otherwise you can remove it
    public $incrementing = false;

    // If your primary key is NOT 'id', specify:
    // protected $primaryKey = 'id';

    // Mass assignable fields
    protected $fillable = [
        'code',
        'image',
        'start_date',
        'end_date',
        'value_in',
        'rate',
        'conditions',
        'user_limit',
        'count',
        'user_type',
        'cid',
        'cdate',
        'mid',
        'mdate',
    ];

    // (Optional) If you want to use date mutators for the date columns:
    protected $dates = [
        'start_date', 'end_date', 'cdate', 'mdate',
    ];
}
