<?php

namespace App\Database\Seeds;

use App\Models\DriverModel;
use App\Models\RegionModel;
use App\Models\VehicleModel;
use CodeIgniter\Database\Seeder;

class BookingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call('IdentitySeeder');

        $region = (new RegionModel())->where('code', 'HQ')->first();
        $regionId = (int) $region['id'];

        $vehicleModel = new VehicleModel();
        $vehicleModel->insert([
            'region_id' => $regionId,
            'vehicle_code' => 'VH-HQ-001',
            'license_plate' => 'B 1234 VBS',
            'vehicle_type' => 'Toyota Innova',
            'category' => 'PASSENGER',
            'ownership_status' => 'COMPANY',
            'operational_status' => 'AVAILABLE',
        ]);
        $vehicleModel->insert([
            'region_id' => $regionId,
            'vehicle_code' => 'VH-HQ-002',
            'license_plate' => 'B 5678 VBS',
            'vehicle_type' => 'Mitsubishi L300',
            'category' => 'CARGO',
            'ownership_status' => 'RENTAL',
            'operational_status' => 'AVAILABLE',
        ]);

        $driverModel = new DriverModel();
        $driverModel->insert([
            'region_id' => $regionId,
            'name' => 'Budi Santoso',
            'phone_number' => '081234567890',
            'license_number' => 'SIM-A-001',
            'license_expires_at' => '2028-12-31',
            'is_active' => true,
        ]);
        $driverModel->insert([
            'region_id' => $regionId,
            'name' => 'Andi Pratama',
            'phone_number' => '081234567891',
            'license_number' => 'SIM-B-001',
            'license_expires_at' => '2028-12-31',
            'is_active' => true,
        ]);
    }
}
