<?php

namespace App\Controllers;

use App\Models\OrderModel;
use App\Models\ClientModel;
use App\Models\StampModel;
use App\Models\ProductTypeModel;
use App\Models\ProductModel;
use App\Models\VariationModel;
use App\Models\OrderDetailsModel;
use App\Models\OrderVariationDetailsModel;
use App\Models\PartRequirementsModel;
use App\Models\PartModel;
use App\Models\BodyModel;
use App\Models\DepartmentModel;

/**
 * Orders Controller
 * 
 * Converted from CI3 Orders.php
 * Date: 2026-02-10
 * 
 * INSTRUCTIONS:
 * Copy this file to: C:\programing\htdocs\joms-ci4\app\Controllers\Orders.php
 */
class Orders extends BaseController
{
    protected $orderModel;
    protected $clientModel;
    protected $stampModel;
    protected $productTypeModel;
    protected $productModel;
    protected $variationModel;
    protected $orderDetailsModel;
    protected $orderVariationDetailsModel;
    protected $partRequirementsModel;
    protected $partModel;
    protected $bodyModel;
    protected $departmentModel;
    
    public function __construct()
    {
        // Initialize models
        $this->orderModel = new OrderModel();
        $this->clientModel = new ClientModel();
        $this->stampModel = new StampModel();
        $this->productTypeModel = new ProductTypeModel();
        $this->productModel = new ProductModel();
        $this->variationModel = new VariationModel();
        $this->orderDetailsModel = new OrderDetailsModel();
        $this->orderVariationDetailsModel = new OrderVariationDetailsModel();
        $this->partRequirementsModel = new PartRequirementsModel();
        $this->partModel = new PartModel();
        $this->bodyModel = new BodyModel();
        $this->departmentModel = new DepartmentModel();
        
        // Check authentication
        if (!session()->get('user')) {
            return redirect()->to('/login');
        }
    }
    
    /**
     * List all orders
     */
    public function index()
    {
        $data = [];
        $data['page_title'] = 'Orders';
        session()->set('page', 'Orders');
        
        // Get search parameters
        $search_data = [];
        if ($this->request->getGet('client')) {
            $search_data['client'] = $this->request->getGet('client');
        }
        if ($this->request->getGet('stamp')) {
            $search_data['stamp'] = $this->request->getGet('stamp');
        }
        if (isset($_GET['status'])) {
            $search_data['status'] = $this->request->getGet('status');
        }
        
        // Pagination
        $jumlah_data = $this->orderModel->jumlah_data($search_data);
        $pager = \Config\Services::pager();
        
        $perPage = 200;
        $page = $this->request->getGet('page') ?? 1;
        $offset = ($page - 1) * $perPage;
        
        // Get orders
        $orders = $this->orderModel->getAllOrders($search_data, $perPage, $offset);
        
        // Process orders data (same logic as CI3)
        $output = [];
        $estWeightCache = [];
        
        foreach ($orders as $order) {
            $orderId = $order->order_id;
            
            if (!isset($output[$orderId])) {
                $output[$orderId] = [
                    'stamps' => [],
                    'created_at' => date('m/d/Y H:i:s', strtotime($order->created_at)),
                    'title' => $order->title,
                    'client_name' => $order->client_name,
                    'status' => $order->status,
                    'order_details' => [],
                    'variation_details' => [],
                ];
            }
            
            if (!isset($output[$orderId]['order_details'][$order->order_details_id])) {
                $output[$orderId]['order_details'][$order->order_details_id] = [];
            }
            
            if (!isset($output[$orderId]['variation_details'][$order->order_details_id][$order->order_variation_details_id])) {
                $output[$orderId]['variation_details'][$order->order_details_id][$order->order_variation_details_id] = [];
            }
            
            if (!in_array($order->stamp_name, $output[$orderId]['stamps'])) {
                $output[$orderId]['stamps'][] = $order->stamp_name;
            }
            
            $output[$orderId]['order_details'][$order->order_details_id][] = [
                'order_details_id' => $order->order_details_id,
                'stamp' => $order->stamp_name,
            ];
            
            $output[$orderId]['variation_details'][$order->order_details_id][$order->order_variation_details_id][] = [
                'order_variation_details_id' => $order->order_variation_details_id,
            ];
            
            // Use cached estimated weight if available
            if (!isset($estWeightCache[$orderId])) {
                $estWeightCache[$orderId] = $this->partRequirementsModel->getTotalRequiredWeightByOrderId($orderId);
            }
            $output[$orderId]['est_weight'] = $estWeightCache[$orderId];
        }
        
        $data['orders'] = $output;
        $data['clients'] = $this->clientModel->orderBy('name', 'asc')->findAll();
        $data['stamps'] = $this->stampModel->orderBy('name', 'asc')->findAll();
        $data['search_data'] = $search_data;
        
        // Pagination links
        $data['pager'] = $pager->makeLinks($page, $perPage, $jumlah_data);
        
        // Load JavaScript files
        $data['javascripts'] = [
            'js/order.js',
            'js/common.js'
        ];
        
        return view('orders/index', $data);
    }
    
    /**
     * Add new order
     */
    public function add_order()
    {
        $data = [];
        $data['page_title'] = 'Add Order';
        session()->set('page', 'Add Order');
        
        $data['user'] = session()->get('user');
        $data['product_types'] = $this->productTypeModel->orderBy('name', 'asc')->findAll();
        $data['stamps'] = $this->stampModel->orderBy('name', 'asc')->findAll();
        $data['clients'] = $this->clientModel->orderBy('name', 'asc')->findAll();
        
        return view('orders/add_order', $data);
    }
    
    /**
     * View order details
     */
    public function view_order($id = null)
    {
        $data = [];
        $data['page_title'] = 'View Order';
        
        if (!empty($id)) {
            $output = [];
            $order_details = $this->orderModel->getOrderById($id);
            
            if (!empty($order_details)) {
                foreach ($order_details as $order) {
                    if (!isset($output['order_details'][$order->order_details_id])) {
                        $output['order_details'][$order->order_details_id] = [];
                        $output['order_details'][$order->order_details_id] = [
                            'order_details_id' => $order->order_details_id,
                            'stamp' => $order->stamp_name,
                            'product_type_id' => $order->product_type_id,
                            'product_type_name' => $order->product_type_name,
                            'body_id' => $order->body_id,
                            'body_name' => $order->body_name,
                            'pidi' => $order->pidi ?? null,
                            'product_id' => $order->product_id,
                            'product_name' => $order->product_name,
                            'product_image' => $order->product_image,
                            'qty_plain' => $order->qty_plain ?? 0,
                            'qty_bunch' => $order->qty_bunch ?? 0,
                            'qty_total' => $order->qty_total ?? 0
                        ];
                    }
                    
                    if (!isset($output['order_details'][$order->order_details_id]['variation_details'][$order->group_name][$order->order_variation_details_id])) {
                        $output['order_details'][$order->order_details_id]['variation_details'][$order->group_name][$order->order_variation_details_id] = [];
                        $output['order_details'][$order->order_details_id]['variation_details'][$order->group_name][$order->order_variation_details_id] = [
                            'order_variation_details_id' => $order->order_variation_details_id,
                            'variation_id' => $order->variation_id,
                            'variation_name' => $order->variation_name,
                            'size' => $order->size,
                            'plain_quantity' => $order->plain_quantity ?? 0,
                            'bunch_quantity' => $order->bunch_quantity ?? 0
                        ];
                    }
                    
                    // Store order header info
                    $output['order_id'] = $order->order_id;
                    $output['title'] = $order->title;
                    $output['client_name'] = $order->client_name;
                    $output['order_date'] = date('m/d/Y', strtotime($order->order_date));
                }
                
                $data['order'] = $output;
            }
        }
        
        return view('orders/view_order', $data);
    }
    
    /**
     * Edit order
     */
    public function edit_order($id = null)
    {
        $data = [];
        $data['page_title'] = 'Edit Order';
        
        if (!empty($id)) {
            $data['order_id'] = $id;
            $data['order'] = $this->orderModel->find($id);
            $data['clients'] = $this->clientModel->orderBy('name', 'asc')->findAll();
            $data['product_types'] = $this->productTypeModel->orderBy('name', 'asc')->findAll();
            $data['stamps'] = $this->stampModel->orderBy('name', 'asc')->findAll();
            
            return view('orders/edit_order', $data);
        }
        
        return redirect()->to('/orders');
    }
    
    /**
     * Delete order
     */
    public function delete_order($id = null)
    {
        if (!empty($id)) {
            $this->orderModel->delete($id);
            session()->setFlashdata('success', 'Order deleted successfully');
        }
        
        return redirect()->to('/orders');
    }
}
