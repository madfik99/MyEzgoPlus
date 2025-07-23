<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CDWRate extends Model
{
    protected $table = 'cdw_rate';

    protected $fillable = [
        'name',
        'image',
        'rate',
        'mid',
        'modified',
        'cid',
        'created',
    ];

    public $timestamps = false;

    public function cdws()
    {
        return $this->hasMany(CDW::class, 'cdw_rate_id');
    }
}
