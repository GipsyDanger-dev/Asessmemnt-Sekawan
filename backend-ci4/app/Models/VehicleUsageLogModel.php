<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleUsageLogModel extends Model
{
    protected $table = 'vehicle_usage_logs';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = ['booking_id', 'vehicle_id', 'driver_id', 'started_at', 'ended_at'];
}
