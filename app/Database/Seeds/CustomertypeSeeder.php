<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CustomertypeSeeder extends Seeder
{
    public function run()
    {
        
            $data = [
                        [
                            'customer_type' => 'CP Customer',
                            'description' => 'CP customer is customer partner'
                        ],
                        [
                            'customer_type' => 'DP Customer',
                            'description' => 'DP customer is direct party'
                        ],
                        [
                            'customer_type' => 'CPDP Customer',
                            'description' => 'CPDP is customer parter and direct party'
                        ],
                        [
                            'customer_type' => 'Ecom Customer',
                            'description' => 'Online'
                        ]
            ];
    
            // Using Query Builder
            $this->db->table('customer_type')->insertBatch($data);
    }
}
