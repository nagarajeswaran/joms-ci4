<?php
namespace App\Models;
use CodeIgniter\Model;

class DepartmentGroupModel extends Model
{
    protected $table = 'department_group';
    protected $primaryKey = 'id';
    protected $allowedFields = ['group_name', 'department_id'];
}
