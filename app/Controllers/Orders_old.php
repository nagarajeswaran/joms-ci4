<?php

namespace App\Controllers;

class Orders extends BaseController
{
    protected $orderModel;
    protected $clientModel;
    
    public function __construct()
    {
        $this->orderModel = model('OrderModel');
        $this->clientModel = model('ClientModel');
    }
    
    public function index()
    {
        // Simple query without complex joins
        $data = [
            'title' => 'Orders Management',
            'orders' => $this->orderModel->findAll()
        ];
        
        return view('orders/index', $data);
    }
    
    public function add_order()
    {
        $data = [
            'title' => 'Add Order',
            'clients' => $this->clientModel->findAll()
        ];
        
        return view('orders/add', $data);
    }
    
    public function view($id)
    {
        $data = [
            'title' => 'Order Details',
            'order' => $this->orderModel->find($id)
        ];
        
        return view('orders/view', $data);
    }
}
