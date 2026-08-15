<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\FuelLogModel;
use App\Models\VehicleModel;
use App\Models\VehicleServiceModel;
use App\Models\VehicleUsageLogModel;
use DomainException;

class FleetMonitoringController extends BaseController
{
    public function fuelIndex()
    {
        $model = (new FuelLogModel())->select('fuel_logs.*, vehicles.license_plate, vehicles.vehicle_type')->join('vehicles', 'vehicles.id = fuel_logs.vehicle_id');
        if ($vehicleId = $this->request->getGet('vehicle_id')) $model->where('fuel_logs.vehicle_id', $vehicleId);
        return $this->response->setJSON(['data' => $model->orderBy('fuel_logs.logged_at', 'DESC')->findAll()]);
    }

    public function createFuel()
    {
        $input = $this->validated(['vehicle_id' => 'required|is_natural_no_zero', 'logged_at' => 'required', 'odometer_km' => 'required|is_natural_no_zero', 'liters' => 'required|decimal', 'price_per_liter' => 'required|decimal', 'station_name' => 'permit_empty|max_length[150]', 'notes' => 'permit_empty']);
        if ($input === null) return $this->response->setStatusCode(422)->setJSON(['message' => 'Validation failed.', 'errors' => $this->validator->getErrors()]);
        try {
            $this->vehicle((int) $input['vehicle_id']); $user = service('jwtService')->authenticatedUser();
            $id = (new FuelLogModel())->insert([...$input, 'total_cost' => (float) $input['liters'] * (float) $input['price_per_liter'], 'created_by' => (int) $user['sub']]);
            service('activityLog')->record((int) $user['sub'], 'FUEL_LOG_CREATED', 'fuel_log', (int) $id, 'Recorded fuel consumption.', $this->request->getIPAddress());
            return $this->response->setStatusCode(201)->setJSON(['data' => (new FuelLogModel())->find($id)]);
        } catch (DomainException $e) { return $this->response->setStatusCode(422)->setJSON(['message' => $e->getMessage()]); }
    }

    public function serviceIndex()
    {
        $model = (new VehicleServiceModel())->select('vehicle_services.*, vehicles.license_plate, vehicles.vehicle_type')->join('vehicles', 'vehicles.id = vehicle_services.vehicle_id');
        if ($status = $this->request->getGet('status')) $model->where('vehicle_services.status', $status);
        return $this->response->setJSON(['data' => $model->orderBy('vehicle_services.scheduled_at')->findAll()]);
    }

    public function createService()
    {
        $input = $this->validated(['vehicle_id' => 'required|is_natural_no_zero', 'scheduled_at' => 'required|valid_date[Y-m-d]', 'service_type' => 'required|max_length[150]', 'odometer_km' => 'permit_empty|is_natural', 'vendor_name' => 'permit_empty|max_length[150]', 'cost' => 'permit_empty|decimal', 'notes' => 'permit_empty']);
        if ($input === null) return $this->response->setStatusCode(422)->setJSON(['message' => 'Validation failed.', 'errors' => $this->validator->getErrors()]);
        try {
            $this->vehicle((int) $input['vehicle_id']); $user = service('jwtService')->authenticatedUser();
            $id = (new VehicleServiceModel())->insert([...$input, 'cost' => $input['cost'] ?: 0, 'status' => 'SCHEDULED', 'created_by' => (int) $user['sub']]);
            service('activityLog')->record((int) $user['sub'], 'SERVICE_SCHEDULED', 'vehicle_service', (int) $id, 'Scheduled vehicle service.', $this->request->getIPAddress());
            return $this->response->setStatusCode(201)->setJSON(['data' => (new VehicleServiceModel())->find($id)]);
        } catch (DomainException $e) { return $this->response->setStatusCode(422)->setJSON(['message' => $e->getMessage()]); }
    }

    public function completeService(int $id)
    {
        $model = new VehicleServiceModel(); $service = $model->find($id);
        if ($service === null || $service['status'] !== 'SCHEDULED') return $this->response->setStatusCode(422)->setJSON(['message' => 'Only scheduled services can be completed.']);
        $user = service('jwtService')->authenticatedUser(); $model->update($id, ['status' => 'COMPLETED', 'completed_at' => date('Y-m-d H:i:s')]);
        service('activityLog')->record((int) $user['sub'], 'SERVICE_COMPLETED', 'vehicle_service', $id, 'Completed vehicle service.', $this->request->getIPAddress());
        return $this->response->setJSON(['data' => $model->find($id)]);
    }

    public function usageIndex()
    {
        $data = (new VehicleUsageLogModel())->select('vehicle_usage_logs.*, vehicles.license_plate, vehicles.vehicle_type, drivers.name AS driver_name, vehicle_bookings.booking_number, vehicle_bookings.destination')->join('vehicles', 'vehicles.id = vehicle_usage_logs.vehicle_id')->join('drivers', 'drivers.id = vehicle_usage_logs.driver_id')->join('vehicle_bookings', 'vehicle_bookings.id = vehicle_usage_logs.booking_id')->orderBy('vehicle_usage_logs.ended_at', 'DESC')->findAll();
        return $this->response->setJSON(['data' => $data]);
    }

    private function validated(array $rules): ?array { return $this->validateData($this->request->getJSON(true) ?? [], $rules) ? $this->validator->getValidated() : null; }
    private function vehicle(int $id): void { if ((new VehicleModel())->find($id) === null) throw new DomainException('Vehicle not found.'); }
}
