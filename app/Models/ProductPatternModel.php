<?php
namespace App\Models;
use CodeIgniter\Model;

class ProductPatternModel extends Model
{
    protected $table = 'product_pattern';
    protected $primaryKey = 'id';
    protected $allowedFields = ['product_id', 'pattern_name_id', 'pattern_code', 'name', 'short_name', 'tamil_name', 'is_default', 'image'];
    protected $returnType = 'array';
}
