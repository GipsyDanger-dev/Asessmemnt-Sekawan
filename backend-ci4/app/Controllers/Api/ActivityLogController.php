<?php

namespace App\Controllers\Api;

use App\Controllers\BaseController;
use App\Models\ActivityLogModel;

class ActivityLogController extends BaseController
{
    public function index()
    {
        $perPage = min(max((int) ($this->request->getGet('per_page') ?? 20), 1), 100);
        $model = (new ActivityLogModel())->select('activity_logs.*, users.name AS user_name')->join('users', 'users.id = activity_logs.user_id', 'left');
        if ($module = $this->request->getGet('module')) $model->where('activity_logs.module', $module);
        return $this->response->setJSON(['data' => $model->orderBy('activity_logs.created_at', 'DESC')->paginate($perPage), 'meta' => ['total' => $model->pager->getTotal(), 'page' => $model->pager->getCurrentPage()]]);
    }
}
