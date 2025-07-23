<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CDW extends Model
{
    protected $table = 'cdw';

    protected $fillable = [
        'cdw_class_id',
        'cdw_rate_id',
        'max_value',
        'mid',
        'modified',
        'cid',
        'created',
    ];

    public $timestamps = false;

    public function cdwClass()
    {
        return $this->belongsTo(CDWClass::class, 'cdw_class_id');
    }



    public function rate()
    {
        return $this->belongsTo(CDWRate::class, 'cdw_rate_id');
    }

    public function cdwRate()
    {
        return $this->belongsTo(CDWRate::class, 'cdw_rate_id');
    }
}
