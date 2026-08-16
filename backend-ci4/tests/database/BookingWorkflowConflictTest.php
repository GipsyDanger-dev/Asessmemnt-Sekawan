<?php

namespace App\Tests\Database;

use App\Exceptions\ConflictException;
use App\Services\BookingWorkflowService;
use Tests\Support\Database\BookingWorkflowTestCase;

/**
 * Menguji logika konflik jadwal pada BookingWorkflowService.
 *
 * @internal
 */
final class BookingWorkflowConflictTest extends BookingWorkflowTestCase
{
    public function testCreateRejectsOverlappingScheduleWithConflictException(): void
    {
        $service = new BookingWorkflowService();
        $service->create($this->payload('2026-12-01'), $this->adminId);

        $this->expectException(ConflictException::class);
        $service->create($this->payload('2026-12-01'), $this->adminId);
    }

    public function testCreateAllowsNonOverlappingSchedule(): void
    {
        $service = new BookingWorkflowService();
        $first = $service->create($this->payload('2026-12-01'), $this->adminId);
        $second = $service->create($this->payload('2026-12-02'), $this->adminId);

        $this->assertSame('PENDING_LEVEL_1', $first['status']);
        $this->assertSame('PENDING_LEVEL_1', $second['status']);
    }

    public function testUpdateRejectsOverlappingAnotherBookingWithConflictException(): void
    {
        $service = new BookingWorkflowService();
        $service->create($this->payload('2026-12-01'), $this->adminId);
        $second = $service->create($this->payload('2026-12-02'), $this->adminId);

        $this->expectException(ConflictException::class);
        $service->update((int) $second['id'], $this->payload('2026-12-01', ['destination' => 'Moved into conflict']), $this->adminId);
    }

    public function testUpdateAllowsNonOverlappingReschedule(): void
    {
        $service = new BookingWorkflowService();
        $first = $service->create($this->payload('2026-12-01'), $this->adminId);

        $updated = $service->update((int) $first['id'], $this->payload('2026-12-03', ['destination' => 'Rescheduled']), $this->adminId);

        $this->assertSame('2026-12-03 09:00:00', $updated['start_at']);
        $this->assertSame('PENDING_LEVEL_1', $updated['status']);
    }

    public function testCompletedBookingDoesNotBlockNewSchedule(): void
    {
        $service = new BookingWorkflowService();
        $first = $service->create($this->payload('2026-12-01'), $this->adminId);
        $service->decide((int) $first['id'], $this->levelOneId, 1, 'APPROVED', null);
        $service->decide((int) $first['id'], $this->levelTwoId, 2, 'APPROVED', null);
        $service->complete((int) $first['id'], $this->adminId);

        $second = $service->create($this->payload('2026-12-01'), $this->adminId);

        $this->assertSame('PENDING_LEVEL_1', $second['status']);
    }
}
