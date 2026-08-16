<?php

namespace App\Tests\Unit;

use App\Controllers\Api\BookingController;
use App\Exceptions\ConflictException;
use App\Services\BookingWorkflowService;
use App\Services\JwtService;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\ControllerTestTrait;
use Config\Services;
use DomainException;

/**
 * @internal
 */
final class BookingControllerConflictTest extends CIUnitTestCase
{
    use ControllerTestTrait;

    private function validPayload(): array
    {
        return [
            'requester_name' => 'QA Tester', 'requester_nik' => 'NIK001', 'department' => 'QA',
            'region_id' => 1, 'vehicle_id' => 1, 'driver_id' => 1,
            'purpose' => 'Uji 409', 'destination' => 'Site A',
            'start_at' => '2026-12-20 09:00:00', 'end_at' => '2026-12-20 17:00:00',
            'passenger_count' => 2, 'requested_vehicle_category' => 'PASSENGER',
            'approver_level_1_id' => 2, 'approver_level_2_id' => 3, 'notes' => 'test',
        ];
    }

    private function injectWorkflow(callable $behaviour): void
    {
        $workflow = $this->createMock(BookingWorkflowService::class);
        $behaviour($workflow);
        Services::injectMock('bookingWorkflow', $workflow);

        $jwt = $this->createMock(JwtService::class);
        $jwt->method('authenticatedUser')->willReturn(['sub' => 1, 'role' => 'admin', 'approval_level' => null]);
        Services::injectMock('jwtService', $jwt);
    }

    public function testCreateReturns409WhenScheduleConflicts(): void
    {
        $this->injectWorkflow(static function (BookingWorkflowService $workflow): void {
            $workflow->method('create')->willThrowException(
                new ConflictException('Vehicle or driver already has an overlapping booking.')
            );
        });

        $result = $this->withBody(json_encode($this->validPayload()))
            ->controller(BookingController::class)
            ->execute('create');

        $result->assertStatus(409);
        $this->assertStringContainsString('overlapping booking', $result->getBody());
    }

    public function testUpdateReturns409WhenScheduleConflicts(): void
    {
        $this->injectWorkflow(static function (BookingWorkflowService $workflow): void {
            $workflow->method('update')->willThrowException(
                new ConflictException('Vehicle or driver already has an overlapping booking.')
            );
        });

        $result = $this->withBody(json_encode($this->validPayload()))
            ->controller(BookingController::class)
            ->execute('update', 10);

        $result->assertStatus(409);
        $this->assertStringContainsString('overlapping booking', $result->getBody());
    }

    public function testOtherDomainErrorsStillReturn422(): void
    {
        $this->injectWorkflow(static function (BookingWorkflowService $workflow): void {
            $workflow->method('create')->willThrowException(new DomainException('End time must be after start time.'));
        });

        $result = $this->withBody(json_encode($this->validPayload()))
            ->controller(BookingController::class)
            ->execute('create');

        $result->assertStatus(422);
        $this->assertStringContainsString('End time must be after start time', $result->getBody());
    }
}
