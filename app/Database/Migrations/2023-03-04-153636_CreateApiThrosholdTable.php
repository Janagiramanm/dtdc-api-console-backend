<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateApiThrosholdTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 5,
                'unsigned' => true,
                'auto_increment' => true
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => '205'
            ],
            'active' => [
                'type' => 'Boolean',
                
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
        $this->forge->createTable('api_threshold');
    }

    public function down()
    {
        $forge = \Config\Database::forge();

        // Drop the table if it exists
        if ($forge->tableExists('api_threshold'))
        {
            $forge->dropTable('api_threshold');
        }
    }
}
