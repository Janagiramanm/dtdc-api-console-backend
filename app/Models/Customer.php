<?php

namespace App\Models;

use CodeIgniter\Model;

class Customer extends Model
{
    protected $DBGroup          = 'default';
    protected $table            = 'customers';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $insertID         = 0;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = ['customer_name','customer_id','user_id','customer_type_id','api_count','period','used_count','available_count','start_date','expiry_date'];

    // Dates
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = [];

    public function user()
    {
        return $this->hasOne('App\Models\UserModel');
    }

    public function getCustomerList(){
        $sql ="select user.token,customer.*,type.customer_type from customers as customer,users as user,customer_type as type 
              where customer.user_id = user.id and customer.customer_type_id = type.id";
        $query = $this->db->query($sql);
        $result= $query->getResultArray();
        if($result){
            return $result;
        }else{
            return [];
        }

    }
}

