<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CDWClass extends Model
{
    protected $table = 'cdw_class';

    protected $fillable = [
        'name',
        'mid',
        'modified',
        'cid',
        'created',
    ];

    public $timestamps = false;

    public function cdws()
    {
        return $this->hasMany(CDW::class, 'cdw_class_id');
    }

    // Optional: if you want CDWClass -> ClassModel mapping
    public function classes()
    {
        return $this->hasMany(ClassModel::class, 'cdw_class_id');
    }
    public function rate()
    {
        return $this->belongsTo(CDWRate::class, 'cdw_rate_id');
    }

}
