<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FleetMaintenanceEngineOil extends Model
{
    protected $table = 'fleet_maintenance_engineoil';

    public $timestamps = false;

    protected $fillable = [
        'vehicle_id',
        'mileage',
        'next_due',
        'next_due_limit',
        'status',
        'cid',
        'cdate',
        'mid',
        'mdate',
    ];

    // Optional: Relationship
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class, 'vehicle_id');
    }
}
