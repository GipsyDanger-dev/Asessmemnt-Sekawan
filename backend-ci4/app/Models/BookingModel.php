<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingModel extends Model
{
    protected $table = 'vehicle_bookings';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'booking_number', 'requester_name', 'requester_nik', 'department', 'region_id', 'vehicle_id', 'driver_id',
        'purpose', 'destination', 'start_at', 'end_at', 'passenger_count', 'requested_vehicle_category', 'status',
        'notes', 'completed_at', 'created_by',
    ];
}
