<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomerTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'customer_name' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'customer_type_id' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'period' => [
                'type' => 'VARCHAR',
                'constraint' => '255',
            ],
            'api_count' => [
                'type' => 'INT',
                'constraint' => 10,
            ],
            'used_count' => [
                'type' => 'INT',
                'constraint' => 10,
            ],
            'available_count' => [
                'type' => 'INT',
                'constraint' => 10,
            ],
            'start_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'expiry_date' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('customers');
    }

    public function down()
    {
        $forge = \Config\Database::forge();

        // Drop the table if it exists
        if ($forge->tableExists('customers'))
        {
            $forge->dropTable('customers');
        }
    }
}
