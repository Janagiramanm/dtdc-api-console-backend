<?php

namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\RawSql;


class Tracking extends Model
{


 
    public function getStatus($data){
            $consg_number = trim($data->consg_number);
            $data->customer_id = 2;
            if(isset($data->customer_id)){
                $customer_id = $data->customer_id;
                $customer = new Customer();
                $result = $customer->where('user_id', $customer_id)->first();
                $date = date('Y-m-d',strtotime($result['expiry_date']));
                $currentDate = date('Y-m-d');
                if($result['available_count'] > 0  && ($date > $currentDate)){
                    $data1 = [
                        'used_count' => $result['used_count'] + 1,
                        'available_count' => $result['available_count'] - 1,
                    ];
                    $this->db->table('customers')->where('id', $customer_id)->update($data1);
                    $response = $this->getConsginStatus($consg_number);
                }else{
                    $response = 0;
                }
            }else{
                $response = $this->getConsginStatus($consg_number);
            }
            return $response;
    }  

    public function getConsginStatus($consg_number){
            $db2 = \Config\Database::connect('second_db');
            $consgNumArray = explode(',',$consg_number);
           
            if($consgNumArray){
                foreach($consgNumArray as $key => $consgnumber){
                        $consgNumber =trim($consgnumber).'%';
                        $sql ="SELECT 'DMC' AS T, CONSG_NUMBER as 'CN', 'RTO' as DELIVERY_STATUS ,OFFICE_ID , DATE_FORMAT(TRANS_CREATED_DATE,'%H:%i %d %M %y') AS DATE_TIME,RTO_CONSG_NUMBER,null as NO_OF_PIECES FROM dtdc_f_dmc where RTO_CONSG_NUMBER is not null and CONSG_NUMBER LIKE ? 
                        UNION
                        SELECT 'Delivery' AS T, CONSG_NUMBER as 'CN',
                        case
                        when(DELIVERY_STATUS = 'D') then ('Delivered')
                        when(DELIVERY_STATUS = 'O') then ('Out for Delivery')
                        when(DELIVERY_STATUS = 'R') then ('Out for Delivery')
                        ELSE 'Out for Delivery'
                        end as DELIVERY_STATUS,OFFICE_ID, DATE_FORMAT(RECORD_UPDATED_TIME,'%H:%i %d %M %y')  AS DATE_TIME , null as RTO_CONSG_NUMBER,null as NO_OF_PIECES FROM DTDC_F_DELIVERY WHERE CONSG_NUMBER LIKE ? and CONSG_STATUS = 'A'
                        UNION
                        SELECT 'Manifest' AS T, CONSG_NUMBER as 'CN' ,'In Transit' as DELIVERY_STATUS ,null as OFFICE_ID, DATE_FORMAT(CONCAT(MANIFEST_DATE,' ' ,MANIFEST_TIME),'%H:%i %d %M %y') AS DATE_TIME , null as RTO_CONSG_NUMBER,NO_OF_PIECES FROM DTDC_F_manifest WHERE CONSG_NUMBER LIKE ? AND MANIFEST_TYPE_DEFN != 'POD'
                        UNION
                        SELECT 'Booking' AS T, CONSG_NUMBER as 'CN', 'Booked' as DELIVERY_STATUS , BRNCH_OFF_ID as OFFICE_ID,DATE_FORMAT(CONCAT(BOOKING_DATE,' ' ,BOOKING_TIME),'%H:%i %d %M %y') AS DATE_TIME, null as RTO_CONSG_NUMBER,NO_OF_PIECES FROM DTDC_F_BOOKING WHERE CONSG_NUMBER LIKE ?
                        LIMIT 1";
                        $query = $db2->query($sql,[$consgNumber, $consgNumber, $consgNumber, $consgNumber]);
                        $result= $query->getResultArray();
                        if($result){
                            $result[0]['CONSG_NUMBER'] = $consgnumber;
                            $result[0]['consg_count'] = $this->getNumberOfPieces($consgnumber,$db2);
                            if($result[0]['consg_count'] > 1){
                               $result[0]['child'] = $this->getChildConsignments($consgnumber,$db2);
                            }
                            foreach($result as $key1 => $value){
                                if($value['RTO_CONSG_NUMBER'] != null){
                                   
                                    $rtStatus = $this->getRTOStatus1($value['RTO_CONSG_NUMBER'],$db2);
                                    $result[0]['rtostatus'] = $rtStatus;
                               }
                            }
                        }
                        $res[] = $result;
                }
            }
            
             return $res;
           

    }

    public function getBookingsDetail($consg_number){
                    $db2 = \Config\Database::connect('second_db');
                    $no_of_pieces = $this->getNumberOfPieces($consg_number,$db2);
                    $booking['data'] = $this->bookingDetails($consg_number,$db2,$no_of_pieces);
                    $booking['consg_count'] = $no_of_pieces;
                    if($booking &&  $no_of_pieces==1){
                            $booking['data'][0]['origin'] = $this->getOriginOfficeDetails($booking['data'][0]['BRNCH_OFF_ID'],$db2);
                            $booking['data'][0]['destination'] = $this->bookingOfficeDetails($booking['data'][0]['pin_code'],$db2);
                            $booking['data'][0]['shipment'] = $this->getShipmentHistoryNew($consg_number,$db2);

                    }
                    return $booking;
    }


    public function getChildConsignments($consg_number,$db2){
                $sql = "SELECT CONSG_NUMBER,DATE_FORMAT(CONCAT(BOOKING_DATE,' ' ,BOOKING_TIME),'%H:%i %d %M %y') AS DATE_TIME FROM dtdc_f_booking WHERE PARENT_CONSG_NO= ? ";
                $query = $db2->query($sql,[$consg_number]);
                $res = $query->getResultArray();
                if($res){
                    foreach($res as $key => $value){
                      $res[$key]['status'] =  $this->getConsginStatus($value['CONSG_NUMBER'],$db2);
                    }
                }
                return $res;
    }

    // public function getChildShipmentHistory(){

    // }

   
    public function getNumberOfPieces($consg_number,$db2){
                $sql = "SELECT NO_OF_PIECES FROM dtdc_f_booking WHERE CONSG_NUMBER= ? ";
                $query = $db2->query($sql,[$consg_number]);
                return $res = $query->getResultArray()[0]['NO_OF_PIECES'];
    }

    public function bookingDetails($consg_number,$db2,$no_of_pieces){
                
                $res = [];
                $condition = 'consg_number';
                if($no_of_pieces > 1){
                    $condition = 'parent_consg_no';
                }
                $sql = "select book.BRNCH_OFF_ID,book.CUSTOMER_ID,book.FRANCHISEE_ID,book.CONSG_NUMBER,
                    DATE_FORMAT(CONCAT(book.BOOKING_DATE,' ' ,book.BOOKING_TIME),'%H:%i %d %M %y') AS DATE_TIME,
                    book.CONSIGNEE_ID,book.CONSIGNER_ID,book.NO_OF_PIECES,
                    off.office_code,off.office_name,pin.pin_code, pin.pin_code as address,service.service_name,book.commodity_id,
                    case
                    when(book.commodity_id = null) then ('null')
                    when(book.commodity_id != null) then (
                        select commodity_name from dtdc_d_commodity com where com.commodity_id = book.commodity_id limit 1 
                    )
                    ELSE null
                    END as commodity_name
                    from
                    dtdc_f_booking book,
                    dtdc_d_office off,
                    dtdc_d_pincode pin,
                    dtdc_d_service service                   
                    where book.".$condition." = ?
                    and book.BRNCH_OFF_ID = off.office_id
                    and book.DEST_PINCODE_ID = pin.pincode_id and book.service_id=service.service_id";
                $query = $db2->query($sql,[$consg_number]);
                return $res = $query->getResultArray();


    }

    public function bookingOfficeDetails($pin_code,$db2){
                $pinSql = "SELECT citydo3_.CITY_CODE AS col_0_0_,
                            areado0_.CITY_ID AS DEST_CITY_ID,
                            pincodedo1_.PINCODE_ID AS DEST_PINCODE_ID,
                            zonedo7_.ZONE_CODE AS col_3_0_,
                            statedo6_.STATE_NAME AS col_4_0_,
                            areado0_.AREA_ID AS col_5_0_,
                            pincodedo1_.SERVICEABLE AS col_6_0_,
                            areado0_.AREA_TYPE AS col_7_0_,
                            citydo3_.CITY_TYPE AS col_8_0_,
                            pincodedo1_.PIN_CODE_TYPE AS col_9_0_,
                            pincodedo1_.OFFICE_ID AS col_10_0_,
                            citydo3_.CITY_NAME AS DEST_CITY_NAME,
                            pincodedo1_.NONSERVICEABLE_SPL_PIN AS col_12_0_,
                                officedo13_.OFFICE_NAME AS col_13_0_,
                            pincodedo1_.PINCODE_GICA AS col_14_0_,
                            pincodedo1_.PINCODE_RLG AS col_15_0_,
                            pincodemap2_.LITE_SERVICEABLE AS col_16_0_,
                            officedo13_.REPORT_REGOFF_ID AS col_17_0_,
                            pincodedo1_.PIN_CODE AS PIN_CODE,
                            pincodedo1_.WEIGHT_KGS AS WEIGHT_KGS,
                            pincodedo1_.PER_PIECE AS PER_PIECE,
                            pincodedo1_.PIN_CODE AS PIN_CODE,               
                            pincodemap2_.B2C_SERVICEABLE AS col_18_0_,        
                            pincodemap2_.B2B_SERVICEABLE AS col_19_0_        

                                FROM
                                dtdc_d_area areado0_,
                                dtdc_d_city citydo3_,
                            dtdc_d_district districtdo5_,
                                dtdc_d_state statedo6_,
                                dtdc_d_zone zonedo7_ CROSS
                                JOIN
                                dtdc_d_pincode pincodedo1_, dtdc_d_office officedo13_ CROSS
                                JOIN
                            dtdc_d_pincode_mapping pincodemap2_
                                WHERE
                            areado0_.CITY_ID=citydo3_.CITY_ID
                                AND citydo3_.DISTRICT_ID=districtdo5_.DISTRICT_ID
                                AND districtdo5_.STATE_ID=statedo6_.STATE_ID
                                AND statedo6_.ZONE_ID=zonedo7_.ZONE_ID
                                AND pincodedo1_.OFFICE_ID=officedo13_.OFFICE_ID
                                AND areado0_.PINCODE_ID=pincodedo1_.PINCODE_ID
                                AND pincodedo1_.PINCODE_ID=pincodemap2_.PINCODE_ID
                                AND areado0_.SERVICEABLE='Y'
                                and pincodedo1_.Serviceable='Y'            
                                AND citydo3_.status='A'            
                                AND citydo3_.COUNTRY_ID=91
                                AND pincodedo1_.PIN_CODE= ? LIMIT 1";
                     $pinQuery = $db2->query($pinSql, [$pin_code]);
                    return $pinQuery->getResultArray();
    }


    public function getOriginOfficeDetails($branch_off_id,$db2){
                    $sql = "SELECT 
                            office.street_1,office.street_2,office.street_3,office.office_name,
                            pin.pin_code,area.area_name,city.city_name
                            FROM dtdc_d_office office, dtdc_d_area area,dtdc_d_pincode pin,dtdc_d_city city 
                            WHERE office.area_id=area.area_id AND area.pincode_id=pin.pincode_id AND area.city_id=city.city_id AND office.office_id= ? ";
                    $query = $db2->query($sql,[$branch_off_id]);
                    return $res = $query->getResultArray();

    }

    public function getDestOfficeDetails($consg_number, $db2){
                    $sql = "SELECT city.city_name,pin.pin_code FROM dtdc_ctbs_plus.dtdc_f_booking booking,dtdc_ctbs_plus.dtdc_d_city city,dtdc_ctbs_plus.dtdc_d_pincode pin 
                            WHERE booking.dest_city_id = city.city_id AND booking.dest_pincode_id=pin.pincode_id AND booking.consg_number = ? ";
                    $query = $db2->query($sql,[$consg_number]);
                    return $res = $query->getResultArray();
    }

    public function getShipmentHistoryNew($consg_number,$db2){
           
                  $dmc = $this->getDmc($consg_number,$db2);
                  $booking = $this->getBooking($consg_number,$db2);
                  $delivery = $this->getDelivery($consg_number,$db2);
                  $manifest = $this->getManifest($consg_number,$db2);
              
                  $dispatch = $this->getDispatch($manifest,$db2);
                  $dispatchRes=[];
                  if($dispatch){
                       $dispatchRes['date_time'] = $dispatch[0]['DATE_TIME'];
                       $dispatchRes['origin'] = $this->getBranchOfficeDetails($dispatch[0]['ORIGIN_OFFICE_ID'],$db2);
                       $dispatchRes['destination'] = $this->getBranchOfficeDetails($dispatch[0]['DEST_OFFICE_ID'],$db2);
                  }
                  $receive  = $this->getReceive($manifest,$db2);
                  $receiveRes= [];
                  if($receive){
                       $receiveRes['date_time'] = $receive[0]['DATE_TIME'];
                       $receiveRes['origin'] = $this->getBranchOfficeDetails($receive[0]['ORIGIN_OFFICE_ID'],$db2);
                       $receiveRes['destination'] = $this->getBranchOfficeDetails($receive[0]['DEST_OFFICE_ID'],$db2);
                  }

                 
                  $pickup = $this->getPickup($consg_number,$db2);
                
                  return $res = [
                    'dmc' => $dmc,
                    'delivery' => $delivery,
                    'receive'  => $receiveRes,
                    'dispatch' => $dispatchRes,
                    'manifest' => $manifest,
                    'booking'  => $booking,
                    'pickup' => $pickup
                  ];


    }

    public function getDispatch($manifest,$db2){
        //   echo '<pre>';
        //   print_r($booking[0]['OFFICE_ID']);
          $manifest_number = $manifest[0]['MANIFEST_NUMBER'];
          $orig_branch_id = $manifest[0]['ORIG_BRNCH_ID'];
        //   exit;
        //   $sql = "select manifest_number,manifest_type,dest_brnch_id,ORIG_BRNCH_ID from dtdc_ctbs_plus.dtdc_f_manifest where consg_number = ? and ORIG_BRNCH_ID = ?";
        //   $sql = "select manifest_number,manifest_type,dest_brnch_id from dtdc_ctbs_plus.dtdc_f_manifest,dtdc_d_office office where consg_number = ? and ORIG_BRNCH_ID = ?";
          $sql ="select ORIGIN_OFFICE_ID,DEST_OFFICE_ID,DATE_FORMAT(RECORD_UPDATED_DATETIME,'%H:%i %d %M %y') AS DATE_TIME from dtdc_ctbs_plus.dtdc_f_disp_bag_mnfst_dtls where BAG_MANIFEST_NUMBER = ? and ORIGIN_OFFICE_ID = ? ";
        $query = $db2->query($sql,[$manifest_number,$orig_branch_id]);
          return $res = $query->getResultArray();

    }

    public function getReceive($manifest,$db2){
          $manifest_number = $manifest[0]['MANIFEST_NUMBER'];
          $dest_branch_id = $manifest[0]['DEST_BRNCH_ID'];
          $sql ="select ORIGIN_OFFICE_ID,DEST_OFFICE_ID,DATE_FORMAT(RECORD_UPDATED_DATETIME,'%H:%i %d %M %y') AS DATE_TIME from dtdc_ctbs_plus.dtdc_f_cd_recv_mnfst_dtls where DEST_OFFICE_ID = ? and BAG_MANIFEST_NUMBER = ? ";
          $query = $db2->query($sql,[$dest_branch_id,$manifest_number]);
          return $res = $query->getResultArray();

    }



//     public function getShipmentHistory($consg_number,$db2){

//                 $sql ="SELECT 'DMC' AS T, CONSG_NUMBER, 'RTO' as DELIVERY_STATUS ,OFFICE_ID , DATE_FORMAT(TRANS_CREATED_DATE,'%H:%i %d %M %y') AS DATE_TIME,RTO_CONSG_NUMBER, null as ORIG_BRNCH_ID,null as DEST_BRNCH_ID,null as MANIFEST_TYPE FROM dtdc_f_dmc where RTO_CONSG_NUMBER is not null and CONSG_NUMBER = ? 
//                         UNION
//                         SELECT 'Delivery' AS T, CONSG_NUMBER,
//                         case
//                         when(DELIVERY_STATUS = 'D') then ('Delivered')
//                         when(DELIVERY_STATUS = 'O') then ('Out for Delivery')
//                         ELSE 'Out for Delivery'
//                         end as DELIVERY_STATUS,OFFICE_ID, DATE_FORMAT(RECORD_UPDATED_TIME,'%H:%i %d %M %y')  AS DATE_TIME , null as RTO_CONSG_NUMBER, null as ORIG_BRNCH_ID,null as DEST_BRNCH_ID,null as MANIFEST_TYPE FROM DTDC_F_DELIVERY WHERE CONSG_NUMBER = ? and CONSG_STATUS = 'A'
//                         UNION
//                         SELECT 'Manifest' AS T, CONSG_NUMBER ,'In Transit' as DELIVERY_STATUS ,null as OFFICE_ID, DATE_FORMAT(CONCAT(MANIFEST_DATE,' ' ,MANIFEST_TIME),'%H:%i %d %M %y') AS DATE_TIME , null as RTO_CONSG_NUMBER,ORIG_BRNCH_ID, DEST_BRNCH_ID, MANIFEST_TYPE FROM DTDC_F_manifest WHERE CONSG_NUMBER = ? and MANIFEST_TYPE_DEFN != 'POD' 
//                         UNION
//                         SELECT 'Booking' AS T, CONSG_NUMBER , 'Booked' as DELIVERY_STATUS , BRNCH_OFF_ID as OFFICE_ID,DATE_FORMAT(CONCAT(BOOKING_DATE,' ' ,BOOKING_TIME),'%H:%i %d %M %y') AS DATE_TIME, null as RTO_CONSG_NUMBER, null as ORIG_BRNCH_ID,null as DEST_BRNCH_ID,null as MANIFEST_TYPE FROM DTDC_F_BOOKING WHERE CONSG_NUMBER = ? ";
//                 $query = $db2->query($sql,[$consg_number, $consg_number, $consg_number, $consg_number]);
//                 $res = $query->getResultArray();
//                 if($res){
//                     $res[0]['rtostatus'] =[];
//                     foreach($res as $key => $value){
//                                     // if($value['T'] == 'Manifest'){
//                                     //         $res[$key]['office_details'] = [];
//                                     //         $res[$key]['office_details']['origin'] = [];
//                                     //         $res[$key]['office_details']['destination'] = [];
//                                     //         if($value['ORIG_BRNCH_ID']!=null){
//                                     //             $res[$key]['office_details']['origin'] = $this->getBranchOfficeDetails($value['ORIG_BRNCH_ID'],$db2);
//                                     //         }
//                                     //         if($value['DEST_BRNCH_ID']!=null){
//                                     //             $res[$key]['office_details']['destination'] = $this->getBranchOfficeDetails($value['DEST_BRNCH_ID'],$db2);
//                                     //         }
                                       
//                                     // }else{
//                                     //     if($value['OFFICE_ID']!=null){
//                                     //         $res[$key]['office_details'] = $this->getBranchOfficeDetails($value['OFFICE_ID'],$db2);
//                                     //     }
//                                     // }
//                                     if($value['RTO_CONSG_NUMBER'] != null){
//                                         $rtStatus = $this->getRTOStatus1($value['RTO_CONSG_NUMBER'],$db2);
//                                         $res[0]['rtostatus'] = $rtStatus;
//                                     }
//                     }
//                     return $res;
//                 }else{
//                     return [];
//                 }
//   }

  public function getDmc($consg_number, $db2){
                     $sql = "SELECT 'DMC' AS T, CONSG_NUMBER, 'RTO' as DELIVERY_STATUS ,OFFICE_ID , DATE_FORMAT(TRANS_CREATED_DATE,'%H:%i %d %M %y') AS DATE_TIME,RTO_CONSG_NUMBER, null as ORIG_BRNCH_ID,null as DEST_BRNCH_ID,null as MANIFEST_TYPE FROM dtdc_f_dmc where RTO_CONSG_NUMBER is not null and CONSG_NUMBER = ? ";
                     $query = $db2->query($sql,[$consg_number]);
                     $res = $query->getResultArray();
                     if($res){
                        $res[0]['rtostatus'] =[];
                        foreach($res as $key => $value){
                                        if($value['RTO_CONSG_NUMBER'] != null){
                                            $rtStatus = $this->getRTOStatus1($value['RTO_CONSG_NUMBER'],$db2);
                                            $res[0]['rtostatus'] = $rtStatus;
                                        }
                        }
                       
                    }
                    return $res;


  }
  public function getDelivery($consg_number, $db2){
                     
                    $sql = "SELECT 'Delivery' AS T, CONSG_NUMBER,
                            case
                            when(DELIVERY_STATUS = 'D') then ('Delivered')
                            when(DELIVERY_STATUS = 'O') then ('Out for Delivery')
                            when(DELIVERY_STATUS = 'N') then ('Not Delivered')
                            ELSE 'Out for Delivery'                    
                            end as DELIVERY_STATUS,OFFICE_ID, DATE_FORMAT(RECORD_UPDATED_TIME,'%H:%i %d %M %y')  AS DATE_TIME , null as RTO_CONSG_NUMBER, null as ORIG_BRNCH_ID,null as DEST_BRNCH_ID,null as MANIFEST_TYPE 
                            FROM dtdc_ctbs_plus.DTDC_F_DELIVERY WHERE CONSG_NUMBER = ? order by DELIVERY_ID DESC";
                    $query = $db2->query($sql,[$consg_number]);
                    $res = $query->getResultArray();
                    if($res){
                        foreach($res as $key => $value){
                            $res[$key]['office_details'] = [];
                            if($value['OFFICE_ID']!=null){
                                $res[$key]['office_details'] = $this->getBranchOfficeDetails($value['OFFICE_ID'],$db2);
                            }
                        }
                       
                    }
                    return $res;

  }
  public function getManifest($consg_number, $db2){
                    $sql = "SELECT 'Manifest' AS T, CONSG_NUMBER ,'In Transit' as DELIVERY_STATUS ,null as OFFICE_ID, DATE_FORMAT(CONCAT(MANIFEST_DATE,' ' ,MANIFEST_TIME),'%H:%i %d %M %y') AS DATE_TIME , null as RTO_CONSG_NUMBER,ORIG_BRNCH_ID, DEST_BRNCH_ID, MANIFEST_TYPE,MANIFEST_NUMBER FROM DTDC_F_manifest WHERE CONSG_NUMBER = ? and MANIFEST_TYPE_DEFN != 'POD' ORDER BY RECORD_ENTRY_DATETIME DESC ";
                    $query = $db2->query($sql,[$consg_number]);
                    $res = $query->getResultArray();
                    if($res){
                        foreach($res as $key => $value){
                                $res[$key]['office_details']['origin'] = [];
                                $res[$key]['office_details']['destination'] = [];
                                if($value['ORIG_BRNCH_ID']!=null){
                                    $res[$key]['office_details']['origin'] = $this->getBranchOfficeDetails($value['ORIG_BRNCH_ID'],$db2);
                                }
                                if($value['DEST_BRNCH_ID']!=null){
                                    $res[$key]['office_details']['destination'] = $this->getBranchOfficeDetails($value['DEST_BRNCH_ID'],$db2);
                                }
                        }
                       
                    }
                    return $res;

  }
  public function getBooking($consg_number, $db2){
                    $sql = "SELECT 'Booking' AS T, CONSG_NUMBER , 'Booked' as DELIVERY_STATUS , BRNCH_OFF_ID as OFFICE_ID,DATE_FORMAT(CONCAT(BOOKING_DATE,' ' ,BOOKING_TIME),'%H:%i %d %M %y') AS DATE_TIME, null as RTO_CONSG_NUMBER, null as ORIG_BRNCH_ID,null as DEST_BRNCH_ID,null as MANIFEST_TYPE FROM DTDC_F_BOOKING WHERE CONSG_NUMBER = ? ";
                    $query = $db2->query($sql,[$consg_number]);
                    $res = $query->getResultArray();
                    if($res){
                        foreach($res as $key => $value){
                            $res[$key]['office_details'] = [];
                            if($value['OFFICE_ID']!=null){
                                $res[$key]['office_details'] = $this->getBranchOfficeDetails($value['OFFICE_ID'],$db2);
                            }
                        }
                       
                    }
                    return $res;
                   
  }
  public function getPickup($consg_number, $db2){
                   $sql = "SELECT HUB_CODE,HUB_TYPE,CUSTOMER_ID,REMARKS,ARRIVAL_DATE_TIME,
                            DATE_FORMAT(RECORD_ENTRY_DATETIME,'%H:%i %d %M %y') AS DATE_TIME,REF_NUMBER FROM dtdc_f_spl_cust_data WHERE consg_number = ? ";
                   $query = $db2->query($sql,[$consg_number]);
                   return $res = $query->getResultArray();
  }
  

  public function getBranchOfficeDetails($office_id, $db2){
                $sql ="SELECT office.office_name,city.city_name FROM dtdc_d_office office,dtdc_d_city city WHERE office.city_id = city.city_id and office_id = ? ";
                                $query = $db2->query($sql,[$office_id]);
                                $res = $query->getResultArray();
                                if($res){
                                return $res;
                                }else{
                                return [];
                }
  }

  public function getRTOStatus1($consg_number,$db2){

                $sql ="SELECT 'DMC' AS T, CONSG_NUMBER,  'RTO' as DELIVERY_STATUS ,OFFICE_ID , DATE_FORMAT(TRANS_CREATED_DATE,'%H:%i %d %M %y') AS DATE_TIME,RTO_CONSG_NUMBER FROM dtdc_f_dmc where RTO_CONSG_NUMBER is not null and CONSG_NUMBER = ? 
                UNION
                SELECT 'Delivery' AS T, CONSG_NUMBER,
                case
                        when(DELIVERY_STATUS = 'D') then ('RTO Delivered')
                        when(DELIVERY_STATUS = 'O') then ('RTO Out for Delivery')
                        when(DELIVERY_STATUS = 'R') then ('RTO Out for Delivery')
                        ELSE 'RTO initiated'
                        end as DELIVERY_STATUS ,OFFICE_ID, DATE_FORMAT(RECORD_UPDATED_TIME,'%H:%i %d %M %y')  AS DATE_TIME , null as RTO_CONSG_NUMBER FROM DTDC_F_DELIVERY WHERE CONSG_NUMBER = ? and CONSG_STATUS = 'A'
                UNION
                SELECT 'Manifest' AS T, CONSG_NUMBER ,'RTO In Transit' as DELIVERY_STATUS ,null as OFFICE_ID, DATE_FORMAT(CONCAT(MANIFEST_DATE,' ' ,MANIFEST_TIME),'%H:%i %d %M %y') AS DATE_TIME , null as RTO_CONSG_NUMBER FROM DTDC_F_manifest WHERE CONSG_NUMBER = ? 
                UNION
                SELECT 'Booking' AS T, CONSG_NUMBER , 'RTO Initiated' as DELIVERY_STATUS , BRNCH_OFF_ID as OFFICE_ID,DATE_FORMAT(CONCAT(BOOKING_DATE,' ' ,BOOKING_TIME),'%H:%i %d %M %y') AS DATE_TIME, null as RTO_CONSG_NUMBER FROM DTDC_F_BOOKING WHERE CONSG_NUMBER = ? 
                LIMIT 1 ";
                $query = $db2->query($sql,[$consg_number, $consg_number, $consg_number, $consg_number]);
                $res = $query->getResultArray();
                if($res){
                return $res;
                }else{
                return [];
                }
}

public function getApiThreshold(){
    $sql = "SELECT id,name,active FROM api_threshold";
    $query = $this->db->query($sql);
    return $results = $query->getResultArray();

 }


    //*** OLD DEV */

  
 /* public function getDetails($data){
                  $consg_number = $data->consg_number;
                  if(isset($data->customer_id)){
                        $customer_id = $data->customer_id;
                        $customer = new Customer();
                        $result = $customer->where('user_id', $customer_id)->first();
                        $data1 = [
                          'used_count' => $result['used_count'] + 1,
                          'available_count' => $result['available_count'] - 1,
                        ];
                        $this->db->table('customers')->where('id', $customer_id)->update($data1);
                        $response = $this->getBookingDetails($consg_number);
                  }else{
                        $response = $this->getBookingDetails($consg_number);
                  }
                  return $response;
  }    

  public function getBookingDetails($consg_number){
                    $consgNumArray = explode(',',$consg_number);
                    if($consgNumArray){
                        foreach($consgNumArray as $key => $consgnumber){
                              $db2 = \Config\Database::connect('second_db');
                              $sql = "select book.BRNCH_OFF_ID,book.CUSTOMER_ID,book.FRANCHISEE_ID,book.CONSG_NUMBER,
                                  book.BOOKING_DATE,book.BOOKING_TIME,
                                  off.office_code,off.office_name,pin.pin_code, pin.pin_code as address
                                  from
                                  dtdc_f_booking book,
                                  dtdc_d_office off,
                                  dtdc_d_pincode pin
                                  where book.consg_number = ?
                                  and book.BRNCH_OFF_ID = off.office_id
                                  and book.DEST_PINCODE_ID = pin.pincode_id";
                              $query = $db2->query($sql,[$consgnumber]);
                              $res = $query->getResultArray();
                            
                              $pinSql = "SELECT citydo3_.CITY_CODE AS col_0_0_,
                                      areado0_.CITY_ID AS DEST_CITY_ID,
                                      pincodedo1_.PINCODE_ID AS DEST_PINCODE_ID,
                                      zonedo7_.ZONE_CODE AS col_3_0_,
                                      statedo6_.STATE_NAME AS col_4_0_,
                                      areado0_.AREA_ID AS col_5_0_,
                                      pincodedo1_.SERVICEABLE AS col_6_0_,
                                      areado0_.AREA_TYPE AS col_7_0_,
                                      citydo3_.CITY_TYPE AS col_8_0_,
                                      pincodedo1_.PIN_CODE_TYPE AS col_9_0_,
                                      pincodedo1_.OFFICE_ID AS col_10_0_,
                                      citydo3_.CITY_NAME AS DEST_CITY_NAME,
                                      pincodedo1_.NONSERVICEABLE_SPL_PIN AS col_12_0_,
                                          officedo13_.OFFICE_NAME AS col_13_0_,
                                      pincodedo1_.PINCODE_GICA AS col_14_0_,
                                      pincodedo1_.PINCODE_RLG AS col_15_0_,
                                      pincodemap2_.LITE_SERVICEABLE AS col_16_0_,
                                      officedo13_.REPORT_REGOFF_ID AS col_17_0_,
                                      pincodedo1_.PIN_CODE AS PIN_CODE,
                                      pincodedo1_.WEIGHT_KGS AS WEIGHT_KGS,
                                      pincodedo1_.PER_PIECE AS PER_PIECE,
                                      pincodedo1_.PIN_CODE AS PIN_CODE,               
                                      pincodemap2_.B2C_SERVICEABLE AS col_18_0_,        
                                      pincodemap2_.B2B_SERVICEABLE AS col_19_0_        
          
                                          FROM
                                          dtdc_d_area areado0_,
                                          dtdc_d_city citydo3_,
                                      dtdc_d_district districtdo5_,
                                          dtdc_d_state statedo6_,
                                          dtdc_d_zone zonedo7_ CROSS
                                          JOIN
                                          dtdc_d_pincode pincodedo1_, dtdc_d_office officedo13_ CROSS
                                          JOIN
                                      dtdc_d_pincode_mapping pincodemap2_
                                          WHERE
                                      areado0_.CITY_ID=citydo3_.CITY_ID
                                          AND citydo3_.DISTRICT_ID=districtdo5_.DISTRICT_ID
                                          AND districtdo5_.STATE_ID=statedo6_.STATE_ID
                                          AND statedo6_.ZONE_ID=zonedo7_.ZONE_ID
                                          AND pincodedo1_.OFFICE_ID=officedo13_.OFFICE_ID
                                          AND areado0_.PINCODE_ID=pincodedo1_.PINCODE_ID
                                          AND pincodedo1_.PINCODE_ID=pincodemap2_.PINCODE_ID
                                          AND areado0_.SERVICEABLE='Y'
                                          and pincodedo1_.Serviceable='Y'            
                                          AND citydo3_.status='A'            
                                          AND citydo3_.COUNTRY_ID=91
                                          AND pincodedo1_.PIN_CODE= ? LIMIT 1";
                              $pinQuery = $db2->query($pinSql, [$res[0]['pin_code']]);
                              $res[0]['address'] = $pinQuery->getResultArray();
                              $status = $this->getConsignmentStatus($consgnumber);
                              if($status){
                                $res[0]['status'] = $status;
                                $res[0]['rtostatus'] =[];
                                foreach($status as $key => $value){
                                    if($value['RTO_CONSG_NUMBER'] != null){
                                         $rtStatus = $this->getRtoStatus($value['RTO_CONSG_NUMBER']);
                                         $res[0]['rtostatus'] = $rtStatus;
                                    }
                                }
                              }
                              $results[]= $res;
                        }
                    }
                    return $results;
  }

  public function getConsignmentStatus($consg_number){

                $db2 = \Config\Database::connect('second_db');
                $sql ="SELECT 'DMC' AS T, CONSG_NUMBER, 'RTO' as DELIVERY_STATUS ,OFFICE_ID , DATE_FORMAT(TRANS_CREATED_DATE,'%H:%i %d %M %y') AS DATE_TIME,RTO_CONSG_NUMBER FROM dtdc_f_dmc where RTO_CONSG_NUMBER is not null and CONSG_NUMBER = ? 
                UNION
                SELECT 'Delivery' AS T, CONSG_NUMBER,DELIVERY_STATUS ,OFFICE_ID, DATE_FORMAT(RECORD_UPDATED_TIME,'%H:%i %d %M %y')  AS DATE_TIME , null as RTO_CONSG_NUMBER FROM DTDC_F_DELIVERY WHERE CONSG_NUMBER = ? and CONSG_STATUS = 'A'
                UNION
                SELECT 'Manifest' AS T, CONSG_NUMBER ,'In Transit' as DELIVERY_STATUS ,null as OFFICE_ID, DATE_FORMAT(CONCAT(MANIFEST_DATE,' ' ,MANIFEST_TIME),'%H:%i %d %M %y') AS DATE_TIME , null as RTO_CONSG_NUMBER FROM DTDC_F_manifest WHERE CONSG_NUMBER = ? 
                UNION
                SELECT 'Booking' AS T, CONSG_NUMBER , 'Booked' as DELIVERY_STATUS , BRNCH_OFF_ID as OFFICE_ID,DATE_FORMAT(CONCAT(BOOKING_DATE,' ' ,BOOKING_TIME),'%H:%i %d %M %y') AS DATE_TIME, null as RTO_CONSG_NUMBER FROM DTDC_F_BOOKING WHERE CONSG_NUMBER = ? ";
                $query = $db2->query($sql,[$consg_number, $consg_number, $consg_number, $consg_number]);
                $res = $query->getResultArray();
                if($res){
                    return $res;
                }else{
                    return [];
                }
  }

  public function getRtoStatus($consg_number){

                $db2 = \Config\Database::connect('second_db');
                $sql ="SELECT 'DMC' AS T, CONSG_NUMBER,  'RTO' as DELIVERY_STATUS ,OFFICE_ID , DATE_FORMAT(TRANS_CREATED_DATE,'%H:%i %d %M %y') AS DATE_TIME,RTO_CONSG_NUMBER FROM dtdc_f_dmc where RTO_CONSG_NUMBER is not null and CONSG_NUMBER = ? 
                UNION
                SELECT 'Delivery' AS T, CONSG_NUMBER,DELIVERY_STATUS ,OFFICE_ID, DATE_FORMAT(RECORD_UPDATED_TIME,'%H:%i %d %M %y')  AS DATE_TIME , null as RTO_CONSG_NUMBER FROM DTDC_F_DELIVERY WHERE CONSG_NUMBER = ? and CONSG_STATUS = 'A'
                UNION
                SELECT 'Manifest' AS T, CONSG_NUMBER ,'RTO In Transit' as DELIVERY_STATUS ,null as OFFICE_ID, DATE_FORMAT(CONCAT(MANIFEST_DATE,' ' ,MANIFEST_TIME),'%H:%i %d %M %y') AS DATE_TIME , null as RTO_CONSG_NUMBER FROM DTDC_F_manifest WHERE CONSG_NUMBER = ? 
                UNION
                SELECT 'Booking' AS T, CONSG_NUMBER , 'RTO Initiated' as DELIVERY_STATUS , BRNCH_OFF_ID as OFFICE_ID,DATE_FORMAT(CONCAT(BOOKING_DATE,' ' ,BOOKING_TIME),'%H:%i %d %M %y') AS DATE_TIME, null as RTO_CONSG_NUMBER FROM DTDC_F_BOOKING WHERE CONSG_NUMBER = ? 
                LIMIT 1 ";
                $query = $db2->query($sql,[$consg_number, $consg_number, $consg_number, $consg_number]);
                $res = $query->getResultArray();
                if($res){
                return $res;
                }else{
                return [];
                }
}

  public function getApiThreshold(){
     $sql = "SELECT id,name FROM api_threshold";
     $query = $this->db->query($sql);
     return $results = $query->getResultArray();

  }

  */
}
