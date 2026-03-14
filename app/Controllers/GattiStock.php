<?php
namespace App\Controllers;

class GattiStock extends BaseController
{
    public function index()
    {
        $db    = \Config\Database::connect();
        $items = $db->query('SELECT gs.*, mj.job_number FROM gatti_stock gs LEFT JOIN melt_job mj ON mj.id = gs.melt_job_id ORDER BY gs.created_at DESC')->getResultArray();
        return view('gatti_stock/index', ['title' => 'Gatti Stock', 'items' => $items]);
    }
}
