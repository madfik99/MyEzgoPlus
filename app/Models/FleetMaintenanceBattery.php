<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetMaintenanceBattery extends Model
{
    protected $table = 'fleet_maintenance_battery';

    public $timestamps = false;

    protected $fillable = [
        'vehicle_id',
        'last_update',
        'next_update',
        'next_due_limit',
        'battery_type',
        'battery_brand',
        'status',
        'cid',
        'cdate',
        'mid',
        'mdate',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
}
