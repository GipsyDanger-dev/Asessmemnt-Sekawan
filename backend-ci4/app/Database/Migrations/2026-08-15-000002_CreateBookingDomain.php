<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateBookingDomain extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'region_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'vehicle_code' => ['type' => 'VARCHAR', 'constraint' => 50],
            'license_plate' => ['type' => 'VARCHAR', 'constraint' => 20],
            'vehicle_type' => ['type' => 'VARCHAR', 'constraint' => 100],
            'category' => ['type' => 'VARCHAR', 'constraint' => 20],
            'ownership_status' => ['type' => 'VARCHAR', 'constraint' => 20],
            'operational_status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'AVAILABLE'],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('region_id');
        $this->forge->addUniqueKey('vehicle_code');
        $this->forge->addUniqueKey('license_plate');
        $this->forge->addForeignKey('region_id', 'regions', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('vehicles');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'region_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'phone_number' => ['type' => 'VARCHAR', 'constraint' => 30],
            'license_number' => ['type' => 'VARCHAR', 'constraint' => 100],
            'license_expires_at' => ['type' => 'DATE'],
            'is_active' => ['type' => 'BOOLEAN', 'default' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('region_id');
        $this->forge->addUniqueKey('license_number');
        $this->forge->addForeignKey('region_id', 'regions', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('drivers');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'booking_number' => ['type' => 'VARCHAR', 'constraint' => 40],
            'requester_name' => ['type' => 'VARCHAR', 'constraint' => 150],
            'requester_nik' => ['type' => 'VARCHAR', 'constraint' => 50],
            'department' => ['type' => 'VARCHAR', 'constraint' => 150],
            'region_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'vehicle_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'driver_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'purpose' => ['type' => 'TEXT'],
            'destination' => ['type' => 'VARCHAR', 'constraint' => 255],
            'start_at' => ['type' => 'DATETIME'],
            'end_at' => ['type' => 'DATETIME'],
            'passenger_count' => ['type' => 'INT', 'unsigned' => true],
            'requested_vehicle_category' => ['type' => 'VARCHAR', 'constraint' => 20],
            'status' => ['type' => 'VARCHAR', 'constraint' => 30, 'default' => 'DRAFT'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['vehicle_id', 'start_at', 'end_at']);
        $this->forge->addKey(['driver_id', 'start_at', 'end_at']);
        $this->forge->addKey('region_id');
        $this->forge->addKey('status');
        $this->forge->addUniqueKey('booking_number');
        $this->forge->addForeignKey('region_id', 'regions', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('vehicle_id', 'vehicles', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('driver_id', 'drivers', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('vehicle_bookings');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'booking_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'approver_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'approval_level' => ['type' => 'TINYINT', 'unsigned' => true],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'PENDING'],
            'approved_at' => ['type' => 'DATETIME', 'null' => true],
            'rejected_at' => ['type' => 'DATETIME', 'null' => true],
            'remarks' => ['type' => 'TEXT', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('approver_id');
        $this->forge->addUniqueKey(['booking_id', 'approval_level']);
        $this->forge->addForeignKey('booking_id', 'vehicle_bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('approver_id', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('booking_approvals');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'booking_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'vehicle_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'driver_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'started_at' => ['type' => 'DATETIME'],
            'ended_at' => ['type' => 'DATETIME', 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('booking_id');
        $this->forge->addForeignKey('booking_id', 'vehicle_bookings', 'id', 'CASCADE', 'CASCADE');
        $this->forge->addForeignKey('vehicle_id', 'vehicles', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('driver_id', 'drivers', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('vehicle_usage_logs');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'user_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'action' => ['type' => 'VARCHAR', 'constraint' => 100],
            'module' => ['type' => 'VARCHAR', 'constraint' => 100],
            'entity_id' => ['type' => 'BIGINT', 'unsigned' => true, 'null' => true],
            'description' => ['type' => 'TEXT'],
            'ip_address' => ['type' => 'VARCHAR', 'constraint' => 45, 'null' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['module', 'entity_id']);
        $this->forge->addForeignKey('user_id', 'users', 'id', 'SET NULL', 'CASCADE');
        $this->forge->createTable('activity_logs');
    }

    public function down(): void
    {
        $this->forge->dropTable('activity_logs', true);
        $this->forge->dropTable('vehicle_usage_logs', true);
        $this->forge->dropTable('booking_approvals', true);
        $this->forge->dropTable('vehicle_bookings', true);
        $this->forge->dropTable('drivers', true);
        $this->forge->dropTable('vehicles', true);
    }
}
