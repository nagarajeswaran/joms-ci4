<?php
namespace App\Models;
use CodeIgniter\Model;

class PartModel extends Model
{
    protected $table = 'part';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'tamil_name', 'weight', 'pcs', 'is_main_part', 'department_id', 'podi_id', 'gatti', 'image'];
    protected $returnType = 'array';
}
