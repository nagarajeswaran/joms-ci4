<?php

namespace App\Controllers;

class Orders extends BaseController
{
    public function index()
    {
        // Direct simple query
        $db = \Config\Database::connect();
        
        try {
            $query = $db->query('SELECT * FROM order_management LIMIT 50');
            $orders = $query->getResultArray();
        } catch (\Exception $e) {
            $orders = [];
        }
        
        $data = [
            'title' => 'Orders',
            'orders' => $orders
        ];
        
        return view('orders/index', $data);
    }
    
    public function add_order()
    {
        $db = \Config\Database::connect();
        $query = $db->query('SELECT * FROM client');
        $clients = $query->getResultArray();
        
        $data = [
            'title' => 'Add Order',
            'clients' => $clients
        ];
        
        return view('orders/add', $data);
    }
}
