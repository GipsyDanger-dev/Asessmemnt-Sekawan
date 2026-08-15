<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function summary()
    {
        $db = db_connect();
        [$from, $to] = $this->dateRange();
        $base = $db->table('vehicle_bookings')->join('vehicles', 'vehicles.id = vehicle_bookings.vehicle_id')->where('start_at >=', $from.' 00:00:00')->where('start_at <=', $to.' 23:59:59');
        $this->applyBookingFilters($base);
        $rows = $base->select('status, COUNT(*) AS total')->groupBy('status')->get()->getResultArray();
        $counts = array_fill_keys(['PENDING_LEVEL_1', 'PENDING_LEVEL_2', 'APPROVED', 'REJECTED', 'COMPLETED'], 0);
        foreach ($rows as $row) $counts[$row['status']] = (int) $row['total'];
        $available = $db->table('vehicles')->where('operational_status', 'AVAILABLE');
        if ($regionId = $this->request->getGet('region_id')) $available->where('region_id', (int) $regionId);
        if ($vehicleType = $this->request->getGet('vehicle_type')) $available->where('vehicle_type', $vehicleType);
        $inUse = $db->table('vehicle_bookings')->join('vehicles', 'vehicles.id = vehicle_bookings.vehicle_id')->where('status', 'APPROVED')->where('start_at <=', date('Y-m-d H:i:s'))->where('end_at >=', date('Y-m-d H:i:s'));
        $this->applyBookingFilters($inUse);
        $attention = $db->table('vehicle_bookings')->select('vehicle_bookings.id, vehicle_bookings.booking_number, vehicle_bookings.destination, vehicle_bookings.start_at, vehicle_bookings.status, vehicles.license_plate')->join('vehicles', 'vehicles.id = vehicle_bookings.vehicle_id')->whereIn('vehicle_bookings.status', ['PENDING_LEVEL_1', 'PENDING_LEVEL_2', 'APPROVED'])->where('vehicle_bookings.start_at <=', date('Y-m-d H:i:s', strtotime('+7 days')));
        $this->applyBookingFilters($attention);
        $fuel = $db->table('fuel_logs')->selectSum('total_cost', 'total_cost')->where('logged_at >=', $from.' 00:00:00')->where('logged_at <=', $to.' 23:59:59');
        $servicesDue = $db->table('vehicle_services')->where('status', 'SCHEDULED')->where('scheduled_at <=', date('Y-m-d', strtotime('+14 days')));
        if ($regionId = $this->request->getGet('region_id')) { $fuel->join('vehicles', 'vehicles.id = fuel_logs.vehicle_id')->where('vehicles.region_id', (int) $regionId); $servicesDue->join('vehicles', 'vehicles.id = vehicle_services.vehicle_id')->where('vehicles.region_id', (int) $regionId); }
        if ($vehicleType = $this->request->getGet('vehicle_type')) { if (! $this->request->getGet('region_id')) { $fuel->join('vehicles', 'vehicles.id = fuel_logs.vehicle_id'); $servicesDue->join('vehicles', 'vehicles.id = vehicle_services.vehicle_id'); } $fuel->where('vehicles.vehicle_type', $vehicleType); $servicesDue->where('vehicles.vehicle_type', $vehicleType); }
        $fuelCost = (float) (($fuel->get()->getRowArray()['total_cost'] ?? 0));
        return $this->response->setJSON(['data' => ['total' => array_sum($counts), 'statuses' => $counts, 'vehicles_available' => $available->countAllResults(), 'vehicles_in_use' => $inUse->countAllResults(), 'fleet' => ['fuel_cost' => $fuelCost, 'services_due' => $servicesDue->countAllResults()], 'attention' => $attention->orderBy('vehicle_bookings.start_at')->limit(5)->get()->getResultArray()]]);
    }

    public function bookingTrend()
    {
        [$from, $to] = $this->dateRange(date('Y-m-d', strtotime('-5 months')), date('Y-m-t'));
        $query = db_connect()->table('vehicle_bookings')->select("DATE_FORMAT(start_at, '%Y-%m') AS month, COUNT(*) AS total", false)->where('start_at >=', $from.' 00:00:00')->where('start_at <=', $to.' 23:59:59');
        $this->applyBookingFilters($query);
        $rows = $query->groupBy("DATE_FORMAT(start_at, '%Y-%m')", false)->orderBy('month')->get()->getResultArray();
        return $this->response->setJSON(['data' => $rows]);
    }

    public function vehicleUsage()
    {
        $db = db_connect();
        [$from, $to] = $this->dateRange(date('Y-m-d', strtotime('-5 months')), date('Y-m-t'));
        $byCategory = $db->table('vehicle_bookings')->select('vehicles.category, COUNT(*) AS total')->join('vehicles', 'vehicles.id = vehicle_bookings.vehicle_id')->where('start_at >=', $from.' 00:00:00')->where('start_at <=', $to.' 23:59:59');
        $this->applyBookingFilters($byCategory);
        $byCategory = $byCategory->groupBy('vehicles.category')->get()->getResultArray();
        $topVehicles = $db->table('vehicle_bookings')->select('vehicles.license_plate, vehicles.vehicle_type, COUNT(*) AS total')->join('vehicles', 'vehicles.id = vehicle_bookings.vehicle_id')->where('start_at >=', $from.' 00:00:00')->where('start_at <=', $to.' 23:59:59');
        $this->applyBookingFilters($topVehicles);
        $topVehicles = $topVehicles->groupBy('vehicles.id')->orderBy('total', 'DESC')->limit(5)->get()->getResultArray();
        return $this->response->setJSON(['data' => ['by_category' => $byCategory, 'top_vehicles' => $topVehicles]]);
    }

    private function dateRange(?string $defaultFrom = null, ?string $defaultTo = null): array
    {
        return [$this->request->getGet('from') ?: ($defaultFrom ?? date('Y-m-01')), $this->request->getGet('to') ?: ($defaultTo ?? date('Y-m-t'))];
    }

    private function applyBookingFilters($query): void
    {
        if ($regionId = $this->request->getGet('region_id')) $query->where('vehicle_bookings.region_id', (int) $regionId);
        if ($vehicleType = $this->request->getGet('vehicle_type')) $query->where('vehicles.vehicle_type', $vehicleType);
    }
}
