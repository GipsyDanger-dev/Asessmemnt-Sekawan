<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFleetMonitoring extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'vehicle_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'logged_at' => ['type' => 'DATETIME'],
            'odometer_km' => ['type' => 'INT', 'unsigned' => true],
            'liters' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'price_per_liter' => ['type' => 'DECIMAL', 'constraint' => '12,2'],
            'total_cost' => ['type' => 'DECIMAL', 'constraint' => '14,2'],
            'station_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey(['vehicle_id', 'logged_at']);
        $this->forge->addForeignKey('vehicle_id', 'vehicles', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('fuel_logs');

        $this->forge->addField([
            'id' => ['type' => 'BIGINT', 'unsigned' => true, 'auto_increment' => true],
            'vehicle_id' => ['type' => 'BIGINT', 'unsigned' => true],
            'scheduled_at' => ['type' => 'DATE'],
            'completed_at' => ['type' => 'DATETIME', 'null' => true],
            'service_type' => ['type' => 'VARCHAR', 'constraint' => 150],
            'odometer_km' => ['type' => 'INT', 'unsigned' => true, 'null' => true],
            'vendor_name' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true],
            'cost' => ['type' => 'DECIMAL', 'constraint' => '14,2', 'default' => 0],
            'status' => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'SCHEDULED'],
            'notes' => ['type' => 'TEXT', 'null' => true],
            'created_by' => ['type' => 'BIGINT', 'unsigned' => true],
            'created_at' => ['type' => 'DATETIME', 'null' => true],
            'updated_at' => ['type' => 'DATETIME', 'null' => true],
        ]);
        $this->forge->addKey('id', true); $this->forge->addKey(['vehicle_id', 'scheduled_at']); $this->forge->addKey('status');
        $this->forge->addForeignKey('vehicle_id', 'vehicles', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->addForeignKey('created_by', 'users', 'id', 'RESTRICT', 'CASCADE');
        $this->forge->createTable('vehicle_services');
    }

    public function down(): void { $this->forge->dropTable('vehicle_services', true); $this->forge->dropTable('fuel_logs', true); }
}
