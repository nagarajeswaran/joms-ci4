<?php
namespace App\Models;
use CodeIgniter\Model;

class ProductTypeModel extends Model
{
    protected $table = 'product_type';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'tamil_name', 'variations', 'multiplication_factor'];
    protected $returnType = 'array';
}
