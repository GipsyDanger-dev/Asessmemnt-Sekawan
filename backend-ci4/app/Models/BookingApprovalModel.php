<?php

namespace App\Models;

use CodeIgniter\Model;

class BookingApprovalModel extends Model
{
    protected $table = 'booking_approvals';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = true;
    protected $allowedFields = [
        'booking_id', 'approver_id', 'approval_level', 'status', 'approved_at', 'rejected_at', 'remarks',
    ];
}
