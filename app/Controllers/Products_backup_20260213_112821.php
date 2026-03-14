<?php

namespace App\Controllers;

use CodeIgniter\HTTP\ResponseInterface;

class Products extends BaseController
{
    protected $db;
    
    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }
    
    public function index()
    {
        try {
            $query = $this->db->query('
                SELECT p.*, 
                       pt.name as product_type_name,
                       b.name as body_name
                FROM product p
                LEFT JOIN product_type pt ON p.product_type_id = pt.id
                LEFT JOIN body b ON p.body_id = b.id
                ORDER BY p.id DESC
                LIMIT 100
            ');
            $products = $query->getResultArray();
        } catch (\Exception $e) {
            $products = [];
        }
        
        $data = [
            'title' => 'Products',
            'products' => $products
        ];
        
        return view('products/index', $data);
    }
    
    public function add_product()
    {
        // Get all dropdown data
        $productTypes = $this->db->query('SELECT * FROM product_type ORDER BY name')->getResultArray();
        $bodies = $this->db->query('SELECT * FROM body ORDER BY name')->getResultArray();
        $parts = $this->db->query('SELECT * FROM part ORDER BY name')->getResultArray();
        $podies = $this->db->query('SELECT * FROM podi ORDER BY name')->getResultArray();
        $variations = $this->db->query('SELECT * FROM variation ORDER BY id')->getResultArray();
        
        $data = [
            'title' => 'Add Product',
            'productTypes' => $productTypes,
            'bodies' => $bodies,
            'parts' => $parts,
            'podies' => $podies,
            'variations' => $variations
        ];
        
        return view('products/add', $data);
    }
    
    // AJAX: Get variations by product type
    public function get_variations_by_product_type()
    {
        $productTypeId = $this->request->getPost('product_type_id');
        
        if (!$productTypeId) {
            return $this->response->setJSON(['success' => false, 'variations' => []]);
        }
        
        // Get the variation IDs from product_type
        $productType = $this->db->query('SELECT variations FROM product_type WHERE id = ?', [$productTypeId])->getRowArray();
        
        if (!$productType || empty($productType['variations'])) {
            return $this->response->setJSON(['success' => false, 'variations' => []]);
        }
        
        // Get variations by IDs
        $variationIds = explode(',', $productType['variations']);
        $variationIds = array_map('trim', $variationIds);
        $placeholders = implode(',', array_fill(0, count($variationIds), '?'));
        
        $variations = $this->db->query("SELECT * FROM variation WHERE id IN ($placeholders) ORDER BY id", $variationIds)->getResultArray();
        
        return $this->response->setJSON(['success' => true, 'variations' => $variations]);
    }
    
    // AJAX: Get bodies (can add filtering later if needed)
    public function get_bodies()
    {
        $bodies = $this->db->query('SELECT * FROM body ORDER BY name')->getResultArray();
        return $this->response->setJSON(['success' => true, 'bodies' => $bodies]);
    }
    
    // AJAX: Get parts
    public function get_parts()
    {
        $parts = $this->db->query('SELECT * FROM part ORDER BY name')->getResultArray();
        return $this->response->setJSON(['success' => true, 'parts' => $parts]);
    }
    
    public function save_product()
    {
        // Get basic product data
        $productData = [
            'name' => $this->request->getPost('name'),
            'tamil_name' => $this->request->getPost('tamil_name'),
            'product_type_id' => $this->request->getPost('product_type_id') ?: null,
            'body_id' => $this->request->getPost('body_id') ?: null,
            'pidi' => $this->request->getPost('pidi'),
            'main_part_id' => $this->request->getPost('main_part_id') ?: null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        // Handle image upload
        $image = $this->request->getFile('image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            $newName = $image->getRandomName();
            $image->move(WRITEPATH . 'uploads', $newName);
            $productData['image'] = $newName;
        }
        
        try {
            // Start transaction
            $this->db->transStart();
            
            // Insert product
            $this->db->table('product')->insert($productData);
            $productId = $this->db->insertID();
            
            // Insert Bill of Materials
            $bomParts = $this->request->getPost('bom_part_id') ?? [];
            $bomVariations = $this->request->getPost('bom_variation_id') ?? [];
            $bomPodi = $this->request->getPost('bom_podi_id') ?? [];
            $bomPartPcs = $this->request->getPost('bom_part_pcs') ?? [];
            $bomPodiPcs = $this->request->getPost('bom_podi_pcs') ?? [];
            $bomScale = $this->request->getPost('bom_scale') ?? [];
            $bomMainGroup = $this->request->getPost('bom_main_group') ?? [];
            
            for ($i = 0; $i < count($bomParts); $i++) {
                if (!empty($bomParts[$i])) {
                    $this->db->table('product_bill_of_material')->insert([
                        'product_id' => $productId,
                        'part_id' => $bomParts[$i],
                        'variation_group' => $bomVariations[$i] ?? null,
                        'podi_id' => $bomPodi[$i] ?? null,
                        'part_pcs' => $bomPartPcs[$i] ?? null,
                        'podi_pcs' => $bomPodiPcs[$i] ?? null,
                        'scale' => $bomScale[$i] ?? null,
                        'main_group' => $bomMainGroup[$i] ?? 'All'
                    ]);
                }
            }
            
            // Insert Customize Bill of Materials
            $cbomParts = $this->request->getPost('cbom_part_name') ?? [];
            $cbomPodi = $this->request->getPost('cbom_podi_name') ?? [];
            $cbomMainGroup = $this->request->getPost('cbom_main_group') ?? [];
            
            for ($i = 0; $i < count($cbomParts); $i++) {
                if (!empty($cbomParts[$i])) {
                    $cbomId = $this->db->table('product_customize_bill_of_material')->insert([
                        'product_id' => $productId,
                        'part_id' => $cbomParts[$i],
                        'podi_id' => $cbomPodi[$i] ?? null,
                        'cbom_main_group' => $cbomMainGroup[$i] ?? 'All'
                    ]);
                    
                    // Insert quantity variations
                    $cbomQty = $this->request->getPost("cbom_qty_{$i}") ?? [];
                    $cbomVarId = $this->request->getPost("cbom_var_id_{$i}") ?? [];
                    
                    $cbomInsertId = $this->db->insertID();
                    
                    for ($j = 0; $j < count($cbomQty); $j++) {
                        if (!empty($cbomQty[$j])) {
                            $this->db->table('product_customize_bill_of_material_quantity')->insert([
                                'product_customize_bill_of_material_id' => $cbomInsertId,
                                'variation_id' => $cbomVarId[$j] ?? null,
                                'quantity' => $cbomQty[$j]
                            ]);
                        }
                    }
                }
            }
            
            $this->db->transComplete();
            
            if ($this->db->transStatus() === false) {
                return $this->response->setJSON(['success' => false, 'message' => 'Transaction failed']);
            }
            
            return $this->response->setJSON(['success' => true, 'message' => 'Product saved successfully', 'product_id' => $productId]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    
    public function edit($id)
    {
        $product = $this->db->query('SELECT * FROM product WHERE id = ?', [$id])->getRowArray();
        $productTypes = $this->db->query('SELECT * FROM product_type ORDER BY name')->getResultArray();
        $bodies = $this->db->query('SELECT * FROM body ORDER BY name')->getResultArray();
        $parts = $this->db->query('SELECT * FROM part ORDER BY name')->getResultArray();
        $podies = $this->db->query('SELECT * FROM podi ORDER BY name')->getResultArray();
        $variations = $this->db->query('SELECT * FROM variation ORDER BY id')->getResultArray();
        
        // Get existing BOM
        $bom = $this->db->query('SELECT * FROM product_bill_of_material WHERE product_id = ?', [$id])->getResultArray();
        
        // Get existing CBOM
        $cbom = $this->db->query('SELECT * FROM product_customize_bill_of_material WHERE product_id = ?', [$id])->getResultArray();
        
        $data = [
            'title' => 'Edit Product',
            'product' => $product,
            'productTypes' => $productTypes,
            'bodies' => $bodies,
            'parts' => $parts,
            'podies' => $podies,
            'variations' => $variations,
            'bom' => $bom,
            'cbom' => $cbom
        ];
        
        return view('products/edit', $data);
    }
    
    public function delete($id)
    {
        try {
            $this->db->transStart();
            
            // Delete related BOM entries first
            $this->db->query('DELETE FROM product_bill_of_material WHERE product_id = ?', [$id]);
            
            // Delete CBOM quantities
            $cbomIds = $this->db->query('SELECT id FROM product_customize_bill_of_material WHERE product_id = ?', [$id])->getResultArray();
            foreach ($cbomIds as $cbom) {
                $this->db->query('DELETE FROM product_customize_bill_of_material_quantity WHERE product_customize_bill_of_material_id = ?', [$cbom['id']]);
            }
            
            // Delete CBOM
            $this->db->query('DELETE FROM product_customize_bill_of_material WHERE product_id = ?', [$id]);
            
            // Delete product
            $this->db->query('DELETE FROM product WHERE id = ?', [$id]);
            
            $this->db->transComplete();
            
            return redirect()->to('/joms-ci4/public/products')->with('success', 'Product deleted');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }
}
