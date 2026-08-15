<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use DomainException;

class BookingController extends BaseController
{
    public function index()
    {
        $perPage = min(max((int) ($this->request->getGet('per_page') ?? 10), 1), 100);
        $model = (new \App\Models\BookingModel())
            ->select('vehicle_bookings.*, vehicles.license_plate, vehicles.vehicle_type, drivers.name AS driver_name, regions.name AS region_name')
            ->join('vehicles', 'vehicles.id = vehicle_bookings.vehicle_id')
            ->join('drivers', 'drivers.id = vehicle_bookings.driver_id')
            ->join('regions', 'regions.id = vehicle_bookings.region_id');

        if ($status = $this->request->getGet('status')) {
            $model->where('vehicle_bookings.status', $status);
        }
        if ($search = trim((string) $this->request->getGet('search'))) {
            $model->groupStart()->like('vehicle_bookings.booking_number', $search)->orLike('vehicle_bookings.requester_name', $search)->orLike('vehicles.license_plate', $search)->groupEnd();
        }

        return $this->response->setJSON([
            'data' => $model->orderBy('vehicle_bookings.created_at', 'DESC')->paginate($perPage),
            'meta' => ['page' => $model->pager->getCurrentPage(), 'per_page' => $perPage, 'total' => $model->pager->getTotal()],
        ]);
    }

    public function create()
    {
        $rules = [
            'requester_name' => 'required|max_length[150]', 'requester_nik' => 'required|max_length[50]',
            'department' => 'required|max_length[150]', 'region_id' => 'required|is_natural_no_zero',
            'vehicle_id' => 'required|is_natural_no_zero', 'driver_id' => 'required|is_natural_no_zero',
            'purpose' => 'required', 'destination' => 'required|max_length[255]', 'start_at' => 'required', 'end_at' => 'required',
            'passenger_count' => 'required|is_natural_no_zero', 'requested_vehicle_category' => 'required|in_list[PASSENGER,CARGO]',
            'approver_level_1_id' => 'required|is_natural_no_zero', 'approver_level_2_id' => 'required|is_natural_no_zero', 'notes' => 'permit_empty',
        ];
        if (! $this->validateData($this->request->getJSON(true) ?? [], $rules)) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Validation failed.', 'errors' => $this->validator->getErrors()]);
        }
        try {
            $user = service('jwtService')->authenticatedUser();
            $booking = service('bookingWorkflow')->create($this->validator->getValidated(), (int) $user['sub'], $this->request->getIPAddress());
            return $this->response->setStatusCode(201)->setJSON(['data' => $booking]);
        } catch (DomainException $exception) {
            return $this->response->setStatusCode(422)->setJSON(['message' => $exception->getMessage()]);
        }
    }

    public function approve(int $id)
    {
        return $this->decide($id, 'APPROVED');
    }

    public function reject(int $id)
    {
        return $this->decide($id, 'REJECTED');
    }

    public function complete(int $id)
    {
        try {
            $user = service('jwtService')->authenticatedUser();
            return $this->response->setJSON(['data' => service('bookingWorkflow')->complete($id, (int) $user['sub'], $this->request->getIPAddress())]);
        } catch (DomainException $exception) {
            return $this->response->setStatusCode(422)->setJSON(['message' => $exception->getMessage()]);
        }
    }

    private function decide(int $id, string $decision)
    {
        try {
            $user = service('jwtService')->authenticatedUser();
            $remarks = ($this->request->getJSON(true) ?? [])['remarks'] ?? null;
            return $this->response->setJSON(['data' => service('bookingWorkflow')->decide(
                $id, (int) $user['sub'], (int) $user['approval_level'], $decision, $remarks, $this->request->getIPAddress(),
            )]);
        } catch (DomainException $exception) {
            return $this->response->setStatusCode(422)->setJSON(['message' => $exception->getMessage()]);
        }
    }
}
