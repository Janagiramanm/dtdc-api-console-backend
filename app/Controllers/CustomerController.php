<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Customer;
use App\Models\UserModel;
use App\Models\CustomerType;
use DateTime;

class CustomerController extends ResourceController
{

    public function __construct()
    {
        helper('text'); // Load the 'text' helper
    }

    public function index()
    {
        $data = $this->request->getJSON();
        
        $customer= new Customer();
        $result = $customer->getCustomerList();
        // $result = $customer->findAll();
        // print_r($result);
        // exit;
        
        // $result = $customer->where('user_id','1')->get()->getResult();
        
        return $this->respond(['status' => 'success','data' => $result ]);
       
    }

    public function create(){
        $data = $this->request->getJSON();
        // echo "<pre>";
        // print_r($data);
        $user = new UserModel();
        $now = new DateTime();
        // $password = $this->getToken(10);
        $token = bin2hex(random_bytes(20));
        $username = strtolower($data->customer_name);
        if($data->customer_email!=''){
             $username = $data->customer_email;
        }
        $password = $data->customer_email;
        $email = $data->customer_email;
        $user_data = [
              'username'=> $username,
              'password' => HASH('sha256',$password),
              'email' => $email,
              'token' => $token,
              'created_at' => $now->format('Y-m-d H:i:s'),
              'updated_at' => $now->format('Y-m-d H:i:s')
        ];
      // print_r($user_data);
        $user->insert($user_data);
        $user_id = $user->insertID();
        //exit;
        $customer_id = 'D'.$user_id.random_string('numeric', 8);
      
       
        $customer= new Customer();
        $data = [
            'customer_id'=> $customer_id,
            'customer_name' => $data->customer_name,
            'customer_type_id'    => $data->customer_type,
            'user_id' => $user_id,
            'api_count' => $data->api_count,
            'used_count' => 0,
            'period'=> $data->period,
            'available_count' =>$data->api_count,
            'start_date' => $data->start_date,
            'expiry_date' => $data->expiry_date
        ];
        
        $customer->insert($data);

        return $this->respond(['status' => 'success']);
      
    }

    public function getCustomerType(){

        $customerType = new CustomerType();
        $result = $customerType->findAll();
        return $this->respond(['status' => 'success','data' => $result ]);

    }

//     public function getToken($length)
//     {
//         $token = "";
//         $codeAlphabet = "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
//         $codeAlphabet.= "abcdefghijklmnopqrstuvwxyz";
//         $codeAlphabet.= "0123456789";
//         $max = strlen($codeAlphabet); // edited

//         for ($i=0; $i < $length; $i++) {
//             $token .= $codeAlphabet[$this->crypto_rand_secure(0, $max-1)];
//         }

//         return $token;
//     }
//     public function crypto_rand_secure($min, $max)
// {
//     $range = $max - $min;
//     if ($range < 1) return $min; // not so random...
//     $log = ceil(log($range, 2));
//     $bytes = (int) ($log / 8) + 1; // length in bytes
//     $bits = (int) $log + 1; // length in bits
//     $filter = (int) (1 << $bits) - 1; // set all lower bits to 1
//     do {
//         $rnd = hexdec(bin2hex(openssl_random_pseudo_bytes($bytes)));
//         $rnd = $rnd & $filter; // discard irrelevant bits
//     } while ($rnd > $range);
//     return $min + $rnd;
// }
}
