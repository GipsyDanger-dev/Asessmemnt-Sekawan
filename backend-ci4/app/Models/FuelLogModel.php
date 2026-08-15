<?php

namespace App\Models;

use CodeIgniter\Model;

class FuelLogModel extends Model
{
    protected $table = 'fuel_logs'; protected $primaryKey = 'id'; protected $returnType = 'array'; protected $useTimestamps = true;
    protected $allowedFields = ['vehicle_id', 'logged_at', 'odometer_km', 'liters', 'price_per_liter', 'total_cost', 'station_name', 'notes', 'created_by'];
}
