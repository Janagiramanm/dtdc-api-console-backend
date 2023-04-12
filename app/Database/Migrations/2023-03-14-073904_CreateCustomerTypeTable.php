<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomerTypeTable extends Migration
{
    public function up()
    {
        //
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'customer_type' => [
                'type' => 'VARCHAR',
                'constraint' => '255'
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => '255'
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true
            ],
           
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('customer_type');
    }

    public function down()
    {
        //
        $forge = \Config\Database::forge();

        // Drop the table if it exists
        if ($forge->tableExists('customer_type'))
        {
            $forge->dropTable('customer_type');
        }
    }
}
