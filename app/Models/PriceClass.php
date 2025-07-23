<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PriceClass extends Model
{
    protected $table = 'price_class';
    protected $primaryKey = 'id';
    public $timestamps = false;

    protected $fillable = [
        'type',
        'class_name',
        'booking',
        'hour',
        'hour2',
        'hour3',
        'hour4',
        'hour5',
        'hour6',
        'hour7',
        'hour8',
        'hour9',
        'hour10',
        'hour11',
        'halfday',
        'hour13',
        'hour14',
        'hour15',
        'hour16',
        'hour17',
        'hour18',
        'hour19',
        'hour20',
        'hour21',
        'hour22',
        'hour23',
        'oneday',
        'twoday',
        'threeday',
        'fourday',
        'fiveday',
        'sixday',
        'weekly',
        'eightday',
        'nineday',
        'tenday',
        'elevenday',
        'twelveday',
        'thirteenday',
        'fourteenday',
        'fifteenday',
        'sixteenday',
        'seventeenday',
        'eighteenday',
        'nineteenday',
        'twentyday',
        'twentyoneday',
        'twentytwoday',
        'twentythreeday',
        'twentyfourday',
        'twentyfiveday',
        'twentysixday',
        'twentysevenday',
        'twentyeightday',
        'twentynineday',
        'monthly',
        'mid',
        'modified',
        'cid',
        'created'
    ];

    // Example relationship to ClassModel
    public function classes()
    {
        return $this->hasMany(ClassModel::class, 'price_class_id');
    }
}
