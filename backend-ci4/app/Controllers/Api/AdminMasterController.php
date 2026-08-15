<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\DriverModel;
use App\Models\RegionModel;
use App\Models\RoleModel;
use App\Models\UserModel;
use App\Models\VehicleModel;
use CodeIgniter\Database\Exceptions\DatabaseException;

class AdminMasterController extends BaseController
{
    public function index(string $resource)
    {
        $model = $this->model($resource);
        if ($resource === 'users') $model->select('users.id, users.name, users.email, users.approval_level, users.is_active, roles.code AS role, roles.name AS role_name')->join('roles', 'roles.id = users.role_id');
        return $this->response->setJSON(['data' => $model->orderBy('id', 'DESC')->findAll()]);
    }

    public function create(string $resource)
    {
        return $this->save($resource);
    }

    public function update(string $resource, int $id)
    {
        return $this->save($resource, $id);
    }

    public function delete(string $resource, int $id)
    {
        try {
            $this->model($resource)->delete($id);
            $this->audit('MASTER_DELETED', $resource, $id);
            return $this->response->setJSON(['message' => 'Deleted.']);
        } catch (DatabaseException) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Data tidak dapat dihapus karena sudah dipakai transaksi.']);
        }
    }

    private function save(string $resource, ?int $id = null)
    {
        $input = $this->request->getJSON(true) ?? [];
        $validation = $this->rules($resource, $id !== null);
        if (! $this->validateData($input, $validation)) return $this->response->setStatusCode(422)->setJSON(['message' => 'Validation failed.', 'errors' => $this->validator->getErrors()]);
        $data = $this->validator->getValidated();
        if ($resource === 'users') {
            $role = (new RoleModel())->where('code', $data['role'])->first();
            if ($role === null) return $this->response->setStatusCode(422)->setJSON(['message' => 'Role tidak valid.']);
            $data['role_id'] = $role['id']; unset($data['role']);
            if (! empty($data['password'])) { $data['password_hash'] = password_hash($data['password'], PASSWORD_DEFAULT); unset($data['password']); } else unset($data['password']);
        }
        $model = $this->model($resource);
        try {
            $result = $id === null ? $model->insert($data) : ($model->update($id, $data) ? $id : null);
            if (! $result) return $this->response->setStatusCode(422)->setJSON(['message' => 'Data tidak dapat disimpan.', 'errors' => $model->errors()]);
            $this->audit($id === null ? 'MASTER_CREATED' : 'MASTER_UPDATED', $resource, (int) $result);
            return $this->response->setStatusCode($id === null ? 201 : 200)->setJSON(['data' => $model->find($result)]);
        } catch (DatabaseException) {
            return $this->response->setStatusCode(422)->setJSON(['message' => 'Nilai unik atau relasi data tidak valid.']);
        }
    }

    private function model(string $resource): RegionModel|VehicleModel|DriverModel|UserModel
    {
        return match ($resource) { 'regions' => new RegionModel(), 'vehicles' => new VehicleModel(), 'drivers' => new DriverModel(), 'users' => new UserModel(), default => throw new \CodeIgniter\Exceptions\PageNotFoundException() };
    }

    private function rules(string $resource, bool $isUpdate): array
    {
        $required = $isUpdate ? 'permit_empty' : 'required';
        return match ($resource) {
            'regions' => ['code' => "$required|max_length[30]", 'name' => "$required|max_length[100]", 'is_active' => 'permit_empty|in_list[0,1]'],
            'vehicles' => ['region_id' => "$required|is_natural_no_zero", 'vehicle_code' => "$required|max_length[50]", 'license_plate' => "$required|max_length[20]", 'vehicle_type' => "$required|max_length[100]", 'category' => "$required|in_list[PASSENGER,CARGO]", 'ownership_status' => "$required|in_list[COMPANY,RENTAL]", 'operational_status' => 'permit_empty|in_list[AVAILABLE,MAINTENANCE,INACTIVE]'],
            'drivers' => ['region_id' => "$required|is_natural_no_zero", 'name' => "$required|max_length[150]", 'phone_number' => "$required|max_length[30]", 'license_number' => "$required|max_length[100]", 'license_expires_at' => "$required|valid_date", 'is_active' => 'permit_empty|in_list[0,1]'],
            'users' => ['name' => "$required|max_length[150]", 'email' => "$required|valid_email|max_length[191]", 'role' => "$required|in_list[admin,approver]", 'approval_level' => 'permit_empty|in_list[1,2]', 'is_active' => 'permit_empty|in_list[0,1]', 'password' => $isUpdate ? 'permit_empty|min_length[8]' : 'required|min_length[8]'],
            default => [],
        };
    }

    private function audit(string $action, string $module, int $id): void
    {
        $user = service('jwtService')->authenticatedUser();
        service('activityLog')->record((int) $user['sub'], $action, $module, $id, "$action on $module #$id", $this->request->getIPAddress());
    }
}
