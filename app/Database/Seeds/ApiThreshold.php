<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ApiThreshold extends Seeder
{
    public function run()
    {
        $data = [
                    ['name' => 'Tracking No'],
                    ['name' => 'Reference No'],
                    ['name' => 'Last Status Date'],
                    ['name' => 'Last Status'],
                    ['name' => 'Booking Date'],
                    ['name' => 'Origin'],
                    ['name' => 'Destination'],
                    ['name' => 'No. of pieces'],
                    ['name' => 'Packing Contents'],
                    ['name' => 'Service Type'],
                    ['name' => 'Receiver Name'],
                    ['name' => 'Relationship'],
                    ['name' => 'Email'],
                    ['name' => 'Activity'],
                    ['name' => 'Pick up'],
                    ['name' => 'Booked & Dispatch'],
                    ['name' => 'In Transit'],
                    ['name' => 'at Destination'],
                    ['name' => 'Out of Delivery'],
                    ['name' => 'Delivered'],
                    ['name' => 'Destination Pincode'],
                    ['name' => 'Customer Discount'],
                    ['name' => 'Pincode'],
                    ['name' => 'Branch Name'],
                    ['name' => 'Branch Location'],


        ];

        // Using Query Builder
        $this->db->table('api_threshold')->insertBatch($data);
    }
}
