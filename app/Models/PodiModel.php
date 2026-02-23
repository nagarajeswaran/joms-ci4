<?php
namespace App\Models;
use CodeIgniter\Model;

class PodiModel extends Model
{
    protected $table = 'podi';
    protected $primaryKey = 'id';
    protected $allowedFields = ['podi_name', 'description'];
}
