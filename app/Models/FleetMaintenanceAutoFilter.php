<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetMaintenanceAutoFilter extends Model
{
    protected $table = 'fleet_maintenance_autofilter';

    public $timestamps = false;

    protected $fillable = [
        'vehicle_id',
        'mileage',
        'next_due',
        'next_due_limit',
        'status',
        'mid',
        'mdate',
        'cid',
        'cdate',
    ];

    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
}
