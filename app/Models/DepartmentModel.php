<?php
namespace App\Models;
use CodeIgniter\Model;

class DepartmentModel extends Model
{
    protected $table = 'department';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'tamil_name', 'wastage', 'department_group_id'];
    protected $returnType = 'array';
}
