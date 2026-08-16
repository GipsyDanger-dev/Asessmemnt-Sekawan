<?php

namespace App\Tests\Database;

use App\Models\UserModel;
use App\Services\BookingWorkflowService;
use DomainException;
use Tests\Support\Database\BookingWorkflowTestCase;

/**
 * Menguji aturan bisnis approval (decide) dan completion
 * pada BookingWorkflowService — DomainException serta alur positifnya.
 *
 * @internal
 */
final class BookingWorkflowApprovalTest extends BookingWorkflowTestCase
{
    private function createBooking(string $date = '2026-12-01'): array
    {
        return (new BookingWorkflowService())->create($this->payload($date), $this->adminId);
    }

    // ---------- decide(): approval ----------

    public function testApproveByWrongLevelThrowsDomainException(): void
    {
        $booking = $this->createBooking();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Booking is not awaiting this approval level.');

        (new BookingWorkflowService())->decide((int) $booking['id'], $this->levelTwoId, 2, 'APPROVED', null);
    }

    public function testRejectWithoutReasonThrowsDomainException(): void
    {
        $booking = $this->createBooking();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('A rejection reason is required.');

        (new BookingWorkflowService())->decide((int) $booking['id'], $this->levelOneId, 1, 'REJECTED', '   ');
    }

    public function testUnassignedApproverThrowsDomainException(): void
    {
        $booking = $this->createBooking();

        // Approver level 1 lain yang tidak ditugaskan ke booking ini.
        $otherLevelOne = (new UserModel())->insert([
            'role_id' => (new \App\Models\RoleModel())->where('code', 'approver')->first()['id'],
            'name' => 'Other Manager', 'email' => 'other-manager@test.local',
            'password_hash' => password_hash('Password123!', PASSWORD_DEFAULT), 'approval_level' => 1,
        ]);

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('You are not assigned to this approval.');

        (new BookingWorkflowService())->decide((int) $booking['id'], (int) $otherLevelOne, 1, 'APPROVED', null);
    }

    public function testApproveAfterRejectionThrowsDomainException(): void
    {
        $booking = $this->createBooking();
        $service = new BookingWorkflowService();
        $service->decide((int) $booking['id'], $this->levelOneId, 1, 'REJECTED', 'Tidak sesuai budget');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Booking is not awaiting this approval level.');

        $service->decide((int) $booking['id'], $this->levelOneId, 1, 'APPROVED', null);
    }

    public function testApproveFlowAdvancesThroughLevels(): void
    {
        $booking = $this->createBooking();
        $service = new BookingWorkflowService();

        $afterLevelOne = $service->decide((int) $booking['id'], $this->levelOneId, 1, 'APPROVED', null);
        $this->assertSame('PENDING_LEVEL_2', $afterLevelOne['status']);

        $afterLevelTwo = $service->decide((int) $booking['id'], $this->levelTwoId, 2, 'APPROVED', null);
        $this->assertSame('APPROVED', $afterLevelTwo['status']);
    }

    // ---------- complete(): penyelesaian ----------

    public function testCompletePendingBookingThrowsDomainException(): void
    {
        $booking = $this->createBooking();

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Only approved bookings can be completed.');

        (new BookingWorkflowService())->complete((int) $booking['id'], $this->adminId);
    }

    public function testCompleteRejectedBookingThrowsDomainException(): void
    {
        $booking = $this->createBooking();
        $service = new BookingWorkflowService();
        $service->decide((int) $booking['id'], $this->levelOneId, 1, 'REJECTED', 'Tidak sesuai budget');

        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('Only approved bookings can be completed.');

        $service->complete((int) $booking['id'], $this->adminId);
    }

    public function testCompleteApprovedBookingSucceedsAndLogsUsage(): void
    {
        $booking = $this->createBooking();
        $service = new BookingWorkflowService();
        $service->decide((int) $booking['id'], $this->levelOneId, 1, 'APPROVED', null);
        $service->decide((int) $booking['id'], $this->levelTwoId, 2, 'APPROVED', null);

        $completed = $service->complete((int) $booking['id'], $this->adminId);

        $this->assertSame('COMPLETED', $completed['status']);
        $this->assertNotNull($completed['completed_at']);

        $usage = (new \App\Models\VehicleUsageLogModel())->where('booking_id', $booking['id'])->first();
        $this->assertNotNull($usage);
        $this->assertSame($this->vehicleId, (int) $usage['vehicle_id']);
    }
}
