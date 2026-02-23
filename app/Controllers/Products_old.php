<?php

namespace App\Controllers;

use App\Models\ProductModel;

class Products extends BaseController
{
    protected $productModel;
    
    public function __construct()
    {
        $this->productModel = new ProductModel();
    }
    
    public function index()
    {
        $data = [
            'title' => 'Products',
            'products' => $this->productModel->findAll()
        ];
        
        return view('products/index', $data);
    }
    
    public function add_product()
    {
        $data = [
            'title' => 'Add Product'
        ];
        
        return view('products/add', $data);
    }
    
    public function create()
    {
        $validation = $this->validate([
            'product_name' => 'required',
            'product_code' => 'required|is_unique[product.product_code]'
        ]);
        
        if (!$validation) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }
        
        $this->productModel->save($this->request->getPost());
        
        return redirect()->to('/products')->with('success', 'Product added successfully');
    }
    
    public function edit($id)
    {
        $data = [
            'title' => 'Edit Product',
            'product' => $this->productModel->find($id)
        ];
        
        return view('products/edit', $data);
    }
    
    public function update($id)
    {
        $this->productModel->update($id, $this->request->getPost());
        
        return redirect()->to('/products')->with('success', 'Product updated successfully');
    }
    
    public function delete($id)
    {
        $this->productModel->delete($id);
        
        return redirect()->to('/products')->with('success', 'Product deleted successfully');
    }
}
