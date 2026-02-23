<?php
namespace App\Models;
use CodeIgniter\Model;

class PartRequirementsModel extends Model
{
    protected $table = 'part_requirements';
    protected $primaryKey = 'id';
    protected $allowedFields = ['order_id', 'part_id', 'quantity_required'];
}
