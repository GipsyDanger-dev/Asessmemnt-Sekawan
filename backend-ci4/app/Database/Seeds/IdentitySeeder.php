<?php

namespace App\Database\Seeds;

use App\Models\RegionModel;
use App\Models\RoleModel;
use App\Models\UserModel;
use CodeIgniter\Database\Seeder;

class IdentitySeeder extends Seeder
{
    public function run(): void
    {
        $roleModel = new RoleModel();
        $regionModel = new RegionModel();
        $userModel = new UserModel();

        $roles = [
            'admin' => $roleModel->insert(['code' => 'admin', 'name' => 'Admin Pool']),
            'approver' => $roleModel->insert(['code' => 'approver', 'name' => 'Approver']),
        ];

        $regionModel->insert(['code' => 'HQ', 'name' => 'Head Office']);

        $passwordHash = password_hash('Password123!', PASSWORD_DEFAULT);
        $userModel->insert([
            'role_id' => $roles['admin'],
            'name' => 'Admin Pool',
            'email' => 'admin@vehicle.test',
            'password_hash' => $passwordHash,
        ]);
        $userModel->insert([
            'role_id' => $roles['approver'],
            'name' => 'Manager Vehicle',
            'email' => 'manager@vehicle.test',
            'password_hash' => $passwordHash,
            'approval_level' => 1,
        ]);
        $userModel->insert([
            'role_id' => $roles['approver'],
            'name' => 'Director Vehicle',
            'email' => 'director@vehicle.test',
            'password_hash' => $passwordHash,
            'approval_level' => 2,
        ]);
    }
}
