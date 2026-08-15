<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReportController extends BaseController
{
    public function bookings()
    {
        $perPage = min(max((int) ($this->request->getGet('per_page') ?? 10), 1), 100);
        $model = $this->query()->orderBy('vehicle_bookings.start_at', 'DESC');
        return $this->response->setJSON([
            'data' => $model->paginate($perPage),
            'meta' => ['page' => $model->pager->getCurrentPage(), 'per_page' => $perPage, 'total' => $model->pager->getTotal()],
        ]);
    }

    public function export()
    {
        $rows = $this->query()->orderBy('vehicle_bookings.start_at', 'DESC')->findAll();
        $sheet = (new Spreadsheet())->getActiveSheet();
        $sheet->fromArray(['Booking Number', 'Requester', 'Department', 'Destination', 'Vehicle', 'Driver', 'Start', 'End', 'Status'], null, 'A1');
        foreach ($rows as $index => $row) $sheet->fromArray([$row['booking_number'], $row['requester_name'], $row['department'], $row['destination'], $row['license_plate'], $row['driver_name'], $row['start_at'], $row['end_at'], $row['status']], null, 'A'.($index + 2));
        foreach (range('A', 'I') as $column) $sheet->getColumnDimension($column)->setAutoSize(true);
        $filename = 'vehicle-booking-report-'.($this->request->getGet('from') ?: date('Ymd')).'-'.($this->request->getGet('to') ?: date('Ymd')).'.xlsx';
        return $this->response->download($filename, null)->setFileName($filename)->setHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')->setBody((function () use ($sheet) { ob_start(); (new Xlsx($sheet->getParent()))->save('php://output'); return ob_get_clean(); })());
    }

    private function query()
    {
        $model = (new \App\Models\BookingModel())->select('vehicle_bookings.*, vehicles.license_plate, vehicles.vehicle_type, vehicles.category AS vehicle_category, drivers.name AS driver_name, regions.name AS region_name')->join('vehicles', 'vehicles.id = vehicle_bookings.vehicle_id')->join('drivers', 'drivers.id = vehicle_bookings.driver_id')->join('regions', 'regions.id = vehicle_bookings.region_id');
        foreach (['status', 'region_id', 'vehicle_id'] as $filter) if ($value = $this->request->getGet($filter)) $model->where('vehicle_bookings.'.$filter, $value);
        if ($category = $this->request->getGet('vehicle_category')) $model->where('vehicles.category', $category);
        if ($search = trim((string) $this->request->getGet('search'))) $model->groupStart()->like('vehicle_bookings.booking_number', $search)->orLike('vehicle_bookings.requester_name', $search)->orLike('vehicle_bookings.destination', $search)->orLike('vehicles.license_plate', $search)->groupEnd();
        if ($from = $this->request->getGet('from')) $model->where('vehicle_bookings.start_at >=', $from.' 00:00:00');
        if ($to = $this->request->getGet('to')) $model->where('vehicle_bookings.start_at <=', $to.' 23:59:59');
        return $model;
    }
}
