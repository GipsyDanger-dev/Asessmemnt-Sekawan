<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleServiceModel extends Model
{
    protected $table = 'vehicle_services'; protected $primaryKey = 'id'; protected $returnType = 'array'; protected $useTimestamps = true;
    protected $allowedFields = ['vehicle_id', 'scheduled_at', 'completed_at', 'service_type', 'odometer_km', 'vendor_name', 'cost', 'status', 'notes', 'created_by'];
}
