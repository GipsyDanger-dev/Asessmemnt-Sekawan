<?php

namespace App\Services;

use App\Models\BookingApprovalModel;
use App\Models\BookingModel;
use App\Models\DriverModel;
use App\Models\UserModel;
use App\Models\VehicleModel;
use App\Models\VehicleUsageLogModel;
use DomainException;

class BookingWorkflowService
{
    public function create(array $input, int $adminId, ?string $ipAddress = null): array
    {
        $this->assertSchedule($input['start_at'], $input['end_at']);
        $vehicle = (new VehicleModel())->find($input['vehicle_id']);
        $driver = (new DriverModel())->find($input['driver_id']);

        if ($vehicle === null || $vehicle['operational_status'] !== 'AVAILABLE') {
            throw new DomainException('Selected vehicle is not available.');
        }
        if ($vehicle['category'] !== $input['requested_vehicle_category']) {
            throw new DomainException('Selected vehicle does not match the requested category.');
        }
        if ($driver === null || ! $driver['is_active'] || $driver['license_expires_at'] < date('Y-m-d')) {
            throw new DomainException('Selected driver is not active or has an expired license.');
        }

        $bookingModel = new BookingModel();
        if ($this->hasConflict($bookingModel, 'vehicle_id', (int) $vehicle['id'], $input['start_at'], $input['end_at'])
            || $this->hasConflict($bookingModel, 'driver_id', (int) $driver['id'], $input['start_at'], $input['end_at'])) {
            throw new DomainException('Vehicle or driver already has an overlapping booking.');
        }

        $levelOne = $this->approver((int) $input['approver_level_1_id'], 1);
        $levelTwo = $this->approver((int) $input['approver_level_2_id'], 2);
        if ($levelOne['id'] === $levelTwo['id']) {
            throw new DomainException('Level 1 and Level 2 approvers must be different users.');
        }

        $db = db_connect();
        $db->transStart();
        $bookingId = $bookingModel->insert([
            ...$input,
            'booking_number' => $this->bookingNumber(),
            'status' => 'PENDING_LEVEL_1',
            'created_by' => $adminId,
        ]);
        $approvalModel = new BookingApprovalModel();
        $approvalModel->insert(['booking_id' => $bookingId, 'approver_id' => $levelOne['id'], 'approval_level' => 1]);
        $approvalModel->insert(['booking_id' => $bookingId, 'approver_id' => $levelTwo['id'], 'approval_level' => 2]);
        $db->transComplete();

        if (! $db->transStatus()) {
            throw new DomainException('Booking could not be created.');
        }

        service('activityLog')->record($adminId, 'BOOKING_CREATED', 'booking', (int) $bookingId, 'Created booking '.$bookingModel->find($bookingId)['booking_number'], $ipAddress);
        return $bookingModel->find($bookingId);
    }

    public function decide(int $bookingId, int $approverId, int $approvalLevel, string $decision, ?string $remarks, ?string $ipAddress = null): array
    {
        $bookingModel = new BookingModel();
        $booking = $bookingModel->find($bookingId);
        $expectedStatus = 'PENDING_LEVEL_'.$approvalLevel;
        if ($booking === null || $booking['status'] !== $expectedStatus) {
            throw new DomainException('Booking is not awaiting this approval level.');
        }
        if ($decision === 'REJECTED' && trim((string) $remarks) === '') {
            throw new DomainException('A rejection reason is required.');
        }

        $approvalModel = new BookingApprovalModel();
        $approval = $approvalModel->where([
            'booking_id' => $bookingId, 'approver_id' => $approverId, 'approval_level' => $approvalLevel, 'status' => 'PENDING',
        ])->first();
        if ($approval === null) {
            throw new DomainException('You are not assigned to this approval.');
        }

        $approved = $decision === 'APPROVED';
        $approvalModel->update($approval['id'], [
            'status' => $decision,
            'approved_at' => $approved ? date('Y-m-d H:i:s') : null,
            'rejected_at' => $approved ? null : date('Y-m-d H:i:s'),
            'remarks' => $remarks,
        ]);
        $nextStatus = $approved ? ($approvalLevel === 1 ? 'PENDING_LEVEL_2' : 'APPROVED') : 'REJECTED';
        $bookingModel->update($bookingId, ['status' => $nextStatus]);
        service('activityLog')->record($approverId, 'BOOKING_'.$decision, 'booking', $bookingId, "Booking {$booking['booking_number']} {$decision} at level {$approvalLevel}.", $ipAddress);

        return $bookingModel->find($bookingId);
    }

    public function complete(int $bookingId, int $adminId, ?string $ipAddress = null): array
    {
        $bookingModel = new BookingModel();
        $booking = $bookingModel->find($bookingId);
        if ($booking === null || $booking['status'] !== 'APPROVED') {
            throw new DomainException('Only approved bookings can be completed.');
        }

        $now = date('Y-m-d H:i:s');
        $bookingModel->update($bookingId, ['status' => 'COMPLETED', 'completed_at' => $now]);
        (new VehicleUsageLogModel())->insert([
            'booking_id' => $bookingId, 'vehicle_id' => $booking['vehicle_id'], 'driver_id' => $booking['driver_id'],
            'started_at' => $booking['start_at'], 'ended_at' => $now,
        ]);
        service('activityLog')->record($adminId, 'BOOKING_COMPLETED', 'booking', $bookingId, "Completed booking {$booking['booking_number']}.", $ipAddress);
        return $bookingModel->find($bookingId);
    }

    private function hasConflict(BookingModel $model, string $column, int $resourceId, string $startAt, string $endAt): bool
    {
        return $model->where($column, $resourceId)
            ->whereIn('status', ['PENDING_LEVEL_1', 'PENDING_LEVEL_2', 'APPROVED'])
            ->where('start_at <', $endAt)->where('end_at >', $startAt)->countAllResults() > 0;
    }

    private function approver(int $id, int $level): array
    {
        $user = (new UserModel())->select('users.*, roles.code AS role_code')->join('roles', 'roles.id = users.role_id')->find($id);
        if ($user === null || ! $user['is_active'] || $user['role_code'] !== 'approver' || (int) $user['approval_level'] !== $level) {
            throw new DomainException("Selected Level {$level} approver is invalid.");
        }
        return $user;
    }

    private function assertSchedule(string $startAt, string $endAt): void
    {
        if (strtotime($startAt) === false || strtotime($endAt) === false || strtotime($startAt) >= strtotime($endAt)) {
            throw new DomainException('End time must be after start time.');
        }
    }

    private function bookingNumber(): string
    {
        return 'VB-'.date('Ymd').'-'.random_int(100000, 999999);
    }
}
