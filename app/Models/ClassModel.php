<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassModel extends Model
{
    protected $table = 'class';
    public $timestamps = false;

    protected $fillable = [
        'class_name',
        'tags',
        'class_image',
        'class_image_interior',
        'desc_1',
        'desc_2',
        'desc_3',
        'desc_4',
        'people_capacity',
        'baggage_capacity',
        'doors',
        'air_conditioned',
        'max_weight',
        'transmission',
        'min_rental_time',
        'cdw_class_id',
        'price_class_id',
        'status',
        'mid',
        'mdate',
        'cid',
        'cdate',
    ];

    public function priceClass()
    {
        return $this->belongsTo(PriceClass::class, 'price_class_id', 'id');
    }
    public function vehicle()
    {
        return $this->hasMany(Vehicle::class, 'class_id', 'id');
    }


}
