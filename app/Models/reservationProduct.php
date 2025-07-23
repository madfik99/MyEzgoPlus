<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class reservationProduct extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_type',
        'product_id',
        'reservation_id',
        'quantity',
        'tax',
        'discount',
        'price',
        'description',
    ];

    public function product()
    {
        $reservation =  $this->hasMany(reservation::class, 'id', 'reservation_id')->first();

        if(!empty($reservation) && $reservation->reservation_module == "account")
        {
            if(module_is_active('ProductService'))
            {
                return $this->hasOne(\Workdo\ProductService\Entities\ProductService::class, 'id', 'product_id')->first();

            }
            else
            {
                return [];
            }
        }
        elseif(!empty($reservation) && $reservation->reservation_module == "taskly")
        {
            if(module_is_active('Taskly'))
            {
                return  $this->hasOne(\Workdo\Taskly\Entities\Task::class, 'id', 'product_id')->first();
            }
            else
            {
                return [];
            }
        }
        elseif(!empty($reservation) && $reservation->reservation_module == "cmms")
        {
            if(module_is_active('ProductService'))
            {
                return $this->hasOne(\Workdo\ProductService\Entities\ProductService::class, 'id', 'product_id')->first();
            }
            else
            {
                return [];
            }
        }

    }
}


