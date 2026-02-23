<?php
namespace App\Models;
use CodeIgniter\Model;

class VariationModel extends Model
{
    protected $table = 'variation';
    protected $primaryKey = 'id';
    protected $allowedFields = ['variation_name', 'product_id', 'description'];
}
