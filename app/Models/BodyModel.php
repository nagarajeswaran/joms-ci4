<?php
namespace App\Models;
use CodeIgniter\Model;

class BodyModel extends Model
{
    protected $table = 'body';
    protected $primaryKey = 'id';
    protected $allowedFields = ['body_name', 'description'];
}
