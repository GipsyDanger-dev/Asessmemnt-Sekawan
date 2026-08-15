<?php

namespace App\Models;

use CodeIgniter\Model;

class VehicleModel extends Model
{
    protected $table = 'vehicles';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'region_id', 'vehicle_code', 'license_plate', 'vehicle_type', 'category', 'ownership_status', 'operational_status',
    ];
}
