<?php
namespace App\Controllers;

class ByproductStock extends BaseController
{
    public function index()
    {
        $db    = \Config\Database::connect();
        $items = $db->query('SELECT bs.*, bt.name as type_name FROM byproduct_stock bs LEFT JOIN byproduct_type bt ON bt.id = bs.byproduct_type_id ORDER BY bs.added_at DESC')->getResultArray();
        return view('byproduct_stock/index', ['title' => 'Byproduct Stock', 'items' => $items]);
    }
}
