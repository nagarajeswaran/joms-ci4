<?php
namespace App\Models;
use CodeIgniter\Model;

class OrderVariationDetailsModel extends Model
{
    protected $table = 'order_variation_details';
    protected $primaryKey = 'id';
    protected $allowedFields = ['order_detail_id', 'variation_id', 'quantity'];
}
