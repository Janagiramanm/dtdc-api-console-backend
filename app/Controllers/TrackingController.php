<?php

namespace App\Controllers;

use CodeIgniter\RESTful\ResourceController;
use App\Models\Tracking;
// header('Access-Control-Allow-Origin: *');
class TrackingController extends ResourceController
{
    function __construct()
    {
        // parent::__construct();
        header('Access-Control-Allow-Origin: *');
        header("Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE");
       
        $this->Tracking = new Tracking();
        $this->db2 = \Config\Database::connect('second_db');
        // $dateObj = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
        // $this->datetime = $dateObj->format('Y-m-d H:i:s');
        

    }

    public function index()
    {
        $data = $this->request->getJSON();
        $tracking = $this->Tracking->getDetails($data);
        
        // return $tracking;
        return $this->respond($tracking);
       
    }
    public function getStatus()
    {
        $data = $this->request->getJSON();
        $tracking = $this->Tracking->getStatus($data);
        if($tracking == 0){
            $res = [
                'status' => 0,
                'data' => 'Your quota has been over. Please renew your quota'
            ];
         }else{
             $res = [
                 'status' => 1,
                 'data' => $tracking
             ];
         }
        return $this->respond($res);
       
    }
    public function getBookingsDetail()
    {
        $data = $this->request->getJSON();
        $tracking = $this->Tracking->getBookingsDetail($data->consg_number);
        return $this->respond($tracking);
       
    }

    public function getApiThreshold(){
        $tracking = $this->Tracking->getApiThreshold();
        return $this->respond($tracking);
    }
}
