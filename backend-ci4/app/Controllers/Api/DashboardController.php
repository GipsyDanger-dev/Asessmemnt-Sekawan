<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function summary()
    {
        $db = db_connect();
        $from = $this->request->getGet('from') ?: date('Y-m-01');
        $to = $this->request->getGet('to') ?: date('Y-m-t');
        $regionId = $this->request->getGet('region_id');
        $base = $db->table('vehicle_bookings')->where('start_at >=', $from.' 00:00:00')->where('start_at <=', $to.' 23:59:59');
        if ($regionId) $base->where('region_id', (int) $regionId);
        $rows = $base->select('status, COUNT(*) AS total')->groupBy('status')->get()->getResultArray();
        $counts = array_fill_keys(['PENDING_LEVEL_1', 'PENDING_LEVEL_2', 'APPROVED', 'REJECTED', 'COMPLETED'], 0);
        foreach ($rows as $row) $counts[$row['status']] = (int) $row['total'];
        $available = $db->table('vehicles')->where('operational_status', 'AVAILABLE')->countAllResults();
        $inUse = $db->table('vehicle_bookings')->where('status', 'APPROVED')->where('start_at <=', date('Y-m-d H:i:s'))->where('end_at >=', date('Y-m-d H:i:s'))->countAllResults();
        return $this->response->setJSON(['data' => ['total' => array_sum($counts), 'statuses' => $counts, 'vehicles_available' => $available, 'vehicles_in_use' => $inUse]]);
    }

    public function bookingTrend()
    {
        $rows = db_connect()->query("SELECT DATE_FORMAT(start_at, '%Y-%m') AS month, COUNT(*) AS total FROM vehicle_bookings WHERE start_at >= DATE_SUB(CURDATE(), INTERVAL 5 MONTH) GROUP BY DATE_FORMAT(start_at, '%Y-%m') ORDER BY month")->getResultArray();
        return $this->response->setJSON(['data' => $rows]);
    }

    public function vehicleUsage()
    {
        $db = db_connect();
        $byCategory = $db->table('vehicle_bookings')->select('vehicles.category, COUNT(*) AS total')->join('vehicles', 'vehicles.id = vehicle_bookings.vehicle_id')->groupBy('vehicles.category')->get()->getResultArray();
        $topVehicles = $db->table('vehicle_bookings')->select('vehicles.license_plate, vehicles.vehicle_type, COUNT(*) AS total')->join('vehicles', 'vehicles.id = vehicle_bookings.vehicle_id')->groupBy('vehicles.id')->orderBy('total', 'DESC')->limit(5)->get()->getResultArray();
        return $this->response->setJSON(['data' => ['by_category' => $byCategory, 'top_vehicles' => $topVehicles]]);
    }
}
