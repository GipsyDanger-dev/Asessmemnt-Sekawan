<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\DriverModel;
use App\Models\RegionModel;
use App\Models\UserModel;
use App\Models\VehicleModel;

class MasterDataController extends BaseController
{
    public function regions()
    {
        return $this->response->setJSON(['data' => (new RegionModel())->where('is_active', true)->orderBy('name')->findAll()]);
    }

    public function vehicles()
    {
        return $this->response->setJSON(['data' => (new VehicleModel())->where('operational_status', 'AVAILABLE')->orderBy('license_plate')->findAll()]);
    }

    public function drivers()
    {
        return $this->response->setJSON(['data' => (new DriverModel())->where('is_active', true)->orderBy('name')->findAll()]);
    }

    public function approvers()
    {
        $approvers = (new UserModel())
            ->select('users.id, users.name, users.email, users.approval_level')
            ->join('roles', 'roles.id = users.role_id')
            ->where('roles.code', 'approver')->where('users.is_active', true)
            ->orderBy('users.approval_level')->findAll();

        return $this->response->setJSON(['data' => $approvers]);
    }
}
