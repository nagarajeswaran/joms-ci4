<?php

namespace App\Models;

use CodeIgniter\Model;

class StampModel extends Model
{
    protected $table = 'stamp';
    protected $primaryKey = 'id';
    protected $allowedFields = ['name', 'description'];
    protected $useTimestamps = true;
    protected $returnType = 'object';
}