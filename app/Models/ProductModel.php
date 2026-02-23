<?php

namespace App\Models;

use CodeIgniter\Model;

class ProductModel extends Model
{
    protected $table = 'product';
    protected $primaryKey = 'id';
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'product_name',
        'product_code',
        'product_type_id',
        'description',
        'price',
        'status'
    ];
    
    protected $useTimestamps = false;
    
    protected $validationRules = [
        'product_name' => 'required|min_length[3]',
        'product_code' => 'required|is_unique[product.product_code]'
    ];
    
    protected $validationMessages = [
        'product_name' => [
            'required' => 'Product name is required',
        ],
        'product_code' => [
            'required' => 'Product code is required',
            'is_unique' => 'Product code already exists',
        ],
    ];
}
