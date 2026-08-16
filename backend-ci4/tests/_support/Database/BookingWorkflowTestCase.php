<?php

namespace Tests\Support\Database;

use App\Models\DriverModel;
use App\Models\RegionModel;
use App\Models\RoleModel;
use App\Models\UserModel;
use App\Models\VehicleModel;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Setup bersama untuk test BookingWorkflowService.
 *
 * DatabaseTestTrait bawaan hanya menjalankan migrasi yang menetapkan
 * $DBGroup = 'tests', sedangkan migrasi aplikasi tidak — sehingga skema
 * dijalankan manual di sini tanpa group filter, lalu tiap test diisolasi
 * dengan transaksi yang di-rollback di tearDown.
 */
abstract class BookingWorkflowTestCase extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate = false;

    private static bool $schemaReady = false;

    protected int $adminId;
    protected int $regionId;
    protected int $vehicleId;
    protected int $driverId;
    protected int $levelOneId;
    protected int $levelTwoId;

    protected function setUp(): void
    {
        parent::setUp();

        if (! self::$schemaReady) {
            $this->migrateAppSchema();
            self::$schemaReady = true;
        }

        $this->db->transBegin();
        $this->seedMasterData();
    }

    protected function tearDown(): void
    {
        $this->db->transRollback();

        parent::tearDown();
    }

    protected function migrateAppSchema(): void
    {
        $config = new \Config\Migrations();
        $config->enabled = true;

        $runner = service('migrations', $config, $this->db, false);
        $runner->setNamespace(null);
        $runner->latest(); // tanpa argumen group → tidak ada group filter → migrasi App ikut dijalankan
    }

    protected function seedMasterData(): void
    {
        $roleModel = new RoleModel();
        $adminRole = $roleModel->insert(['code' => 'admin', 'name' => 'Admin Pool']);
        $approverRole = $roleModel->insert(['code' => 'approver', 'name' => 'Approver']);

        $this->regionId = (new RegionModel())->insert(['code' => 'HQ', 'name' => 'Head Office']);

        $userModel = new UserModel();
        $hash = password_hash('Password123!', PASSWORD_DEFAULT);
        $this->adminId = $userModel->insert([
            'role_id' => $adminRole, 'name' => 'Admin', 'email' => 'admin@test.local', 'password_hash' => $hash,
        ]);
        $this->levelOneId = $userModel->insert([
            'role_id' => $approverRole, 'name' => 'Manager', 'email' => 'manager@test.local',
            'password_hash' => $hash, 'approval_level' => 1,
        ]);
        $this->levelTwoId = $userModel->insert([
            'role_id' => $approverRole, 'name' => 'Director', 'email' => 'director@test.local',
            'password_hash' => $hash, 'approval_level' => 2,
        ]);

        $this->vehicleId = (new VehicleModel())->insert([
            'region_id' => $this->regionId, 'vehicle_code' => 'VH-001', 'license_plate' => 'B 0001 QA',
            'vehicle_type' => 'MPV', 'category' => 'PASSENGER', 'ownership_status' => 'COMPANY',
            'operational_status' => 'AVAILABLE',
        ]);

        $this->driverId = (new DriverModel())->insert([
            'region_id' => $this->regionId, 'name' => 'Budi Santoso', 'phone_number' => '081234567890',
            'license_number' => 'SIM-001', 'license_expires_at' => '2030-12-31', 'is_active' => 1,
        ]);
    }

    /**
     * Payload booking yang valid untuk service, dengan tanggal mulai diberikan.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    protected function payload(string $date, array $overrides = []): array
    {
        return array_merge([
            'requester_name' => 'QA Tester', 'requester_nik' => 'NIK001', 'department' => 'QA',
            'region_id' => $this->regionId, 'vehicle_id' => $this->vehicleId, 'driver_id' => $this->driverId,
            'purpose' => 'Uji workflow booking', 'destination' => 'Site A',
            'start_at' => "{$date} 09:00:00", 'end_at' => "{$date} 17:00:00",
            'passenger_count' => 2, 'requested_vehicle_category' => 'PASSENGER',
            'approver_level_1_id' => $this->levelOneId, 'approver_level_2_id' => $this->levelTwoId, 'notes' => 'test',
        ], $overrides);
    }
}
