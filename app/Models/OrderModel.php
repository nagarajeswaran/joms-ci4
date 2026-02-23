<?php

namespace App\Models;

use CodeIgniter\Model;

class OrderModel extends Model
{
    protected $table = 'order_management';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'title',
        'client_id',
        'status',
        'order_date',
        'delivery_date',
        'notes'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $validationRules = [
        'title' => 'required|min_length[3]',
        'client_id' => 'required|numeric'
    ];
    
    protected $validationMessages = [
        'title' => [
            'required' => 'Order title is required',
        ],
        'client_id' => [
            'required' => 'Client is required',
        ],
    ];
}
