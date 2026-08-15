<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\BookingApprovalModel;

class ApprovalController extends BaseController
{
    public function inbox()
    {
        $user = service('jwtService')->authenticatedUser();
        $level = (int) $user['approval_level'];
        $items = (new BookingApprovalModel())
            ->select('booking_approvals.id AS approval_id, booking_approvals.approval_level, vehicle_bookings.id AS booking_id, vehicle_bookings.booking_number, vehicle_bookings.requester_name, vehicle_bookings.department, vehicle_bookings.destination, vehicle_bookings.start_at, vehicle_bookings.end_at, vehicle_bookings.passenger_count, vehicles.license_plate, vehicles.vehicle_type, drivers.name AS driver_name')
            ->join('vehicle_bookings', 'vehicle_bookings.id = booking_approvals.booking_id')
            ->join('vehicles', 'vehicles.id = vehicle_bookings.vehicle_id')
            ->join('drivers', 'drivers.id = vehicle_bookings.driver_id')
            ->where('booking_approvals.approver_id', (int) $user['sub'])
            ->where('booking_approvals.approval_level', $level)
            ->where('booking_approvals.status', 'PENDING')
            ->where('vehicle_bookings.status', 'PENDING_LEVEL_'.$level)
            ->orderBy('vehicle_bookings.start_at')
            ->findAll();

        return $this->response->setJSON(['data' => $items]);
    }
}
