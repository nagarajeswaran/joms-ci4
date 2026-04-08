<?php
namespace App\Models;
use CodeIgniter\Model;

class VariationModel extends Model
{
    protected $table = 'variation';
    protected $primaryKey = 'id';
    protected $allowedFields = ['group_name', 'group_tamil_name', 'name', 'size'];
    protected $returnType = 'array';
}
