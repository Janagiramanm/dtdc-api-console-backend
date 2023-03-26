<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCustomerApiThresholdTable extends Migration
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
            'customer_id' => [
                'type' => 'INT',
                'constraint' => 10,
                
            ],
            'api_threshold_id' => [
                'type' => 'INT',
                'unsigned' => true,
                'constraint' => 10,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
           
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('customer_api_threshold');
    }

    public function down()
    {
        $forge = \Config\Database::forge();

        // Drop the table if it exists
        if ($forge->tableExists('customer_api_threshold'))
        {
            $forge->dropTable('customer_api_threshold');
        }
    }
}
