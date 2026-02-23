<?php
namespace App\Models;
use CodeIgniter\Model;

class PartModel extends Model
{
    protected $table = 'part';
    protected $primaryKey = 'id';
    protected $allowedFields = ['part_name', 'part_code', 'description'];
}
