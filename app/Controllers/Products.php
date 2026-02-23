<?php

namespace App\Controllers;

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
        
        return view('products/index', [
            'title' => 'Products',
            'products' => $products
        ]);
    }
    
    public function add_product()
    {
        $productTypes = $this->db->query('SELECT * FROM product_type ORDER BY name')->getResultArray();
        $bodies = $this->db->query('SELECT * FROM body ORDER BY name')->getResultArray();
        $parts = $this->db->query('SELECT * FROM part ORDER BY name')->getResultArray();
        $podies = $this->db->query('SELECT * FROM podi ORDER BY name')->getResultArray();
        $variations = $this->db->query('SELECT * FROM variation ORDER BY id')->getResultArray();
        
        return view('products/add', [
            'title' => 'Add Product',
            'productTypes' => $productTypes,
            'bodies' => $bodies,
            'parts' => $parts,
            'podies' => $podies,
            'variations' => $variations
        ]);
    }
    
    public function get_variations_by_product_type()
    {
        $productTypeId = $this->request->getPost('product_type_id');
        
        if (!$productTypeId) {
            return $this->response->setJSON(['success' => false, 'variations' => []]);
        }
        
        $productType = $this->db->query('SELECT variations FROM product_type WHERE id = ?', [$productTypeId])->getRowArray();
        
        if (!$productType || empty($productType['variations'])) {
            return $this->response->setJSON(['success' => true, 'variations' => []]);
        }
        
        $variationIds = array_map('trim', explode(',', $productType['variations']));
        $placeholders = implode(',', array_fill(0, count($variationIds), '?'));
        
        $variations = $this->db->query("SELECT * FROM variation WHERE id IN ($placeholders) ORDER BY id", $variationIds)->getResultArray();
        
        return $this->response->setJSON(['success' => true, 'variations' => $variations]);
    }
    
    public function save_product()
    {
        $data = $this->request->getPost();
        
        $productData = [
            'name' => $data['name'] ?? '',
            'tamil_name' => $data['tamil_name'] ?? '',
            'product_type_id' => $data['product_type'] ?? null,
            'body_id' => $data['body'] ?? null,
            'pidi' => $data['pidi'] ?? '',
            'main_part_id' => $data['main_part'] ?? null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];
        
        $image = $this->request->getFile('product_image');
        if ($image && $image->isValid() && !$image->hasMoved()) {
            $newName = $image->getRandomName();
            $uploadPath = FCPATH . 'assets/images/products';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0777, true);
            }
            $image->move($uploadPath, $newName);
            $productData['image'] = $newName;
        }
        
        $count = $this->db->query(
            'SELECT COUNT(*) as cnt FROM product WHERE name = ? AND product_type_id = ? AND body_id = ? AND pidi = ?',
            [$productData['name'], $productData['product_type_id'], $productData['body_id'], $productData['pidi']]
        )->getRowArray()['cnt'];
        
        if ($count > 0) {
            return $this->response->setJSON([
                'error' => 1,
                'message' => 'Product already exists with similar data.'
            ]);
        }
        
        try {
            $this->db->transStart();
            
            $this->db->table('product')->insert($productData);
            $productId = $this->db->insertID();
            
            if (!empty($data['bom_part_name'])) {
                $totalBom = count($data['bom_part_name']);
                for ($i = 0; $i < $totalBom; $i++) {
                    if (!empty($data['bom_part_name'][$i])) {
                        $variationGroup = '';
                        if (isset($data['bom_variation_group'][$i]) && is_array($data['bom_variation_group'][$i])) {
                            $variationGroup = implode(',', $data['bom_variation_group'][$i]);
                        } elseif (isset($data['bom_variation_group'][$i])) {
                            $variationGroup = $data['bom_variation_group'][$i];
                        }
                        
                        $bomData = [
                            'product_id' => $productId,
                            'part_id' => $data['bom_part_name'][$i],
                            'part_pcs' => $data['bom_part_pcs'][$i] ?? null,
                            'scale' => $data['bom_scale'][$i] ?? null,
                            'main_group' => $data['bom_main_group'][$i] ?? 'All',
                            'variation_group' => $variationGroup,
                            'podi_id' => $data['bom_podi'][$i] ?? null,
                            'podi_pcs' => $data['bom_podi_pcs'][$i] ?? null
                        ];
                        
                        $this->db->table('product_bill_of_material')->insert($bomData);
                    }
                }
            }
            
            if (!empty($data['cbom_part_name'])) {
                $productType = $this->db->query('SELECT variations FROM product_type WHERE id = ?', [$data['product_type']])->getRowArray();
                $variations = [];
                if ($productType && !empty($productType['variations'])) {
                    $variations = array_map('trim', explode(',', $productType['variations']));
                }
                
                $totalCbom = count($data['cbom_part_name']);
                for ($i = 0; $i < $totalCbom; $i++) {
                    if (!empty($data['cbom_part_name'][$i])) {
                        $cbomData = [
                            'product_id' => $productId,
                            'part_id' => $data['cbom_part_name'][$i],
                            'podi_id' => $data['cbom_podi_name'][$i] ?? null,
                            'cbom_main_group' => $data['cbom_main_group'][$i] ?? 'All'
                        ];
                        
                        $this->db->table('product_customize_bill_of_material')->insert($cbomData);
                        $cbomId = $this->db->insertID();
                        
                        foreach ($variations as $variationId) {
                            $partQtyKey = 'cbom_part_quantity_' . $variationId;
                            $podiQtyKey = 'cbom_podi_quantity_' . $variationId;
                            
                            $cbomQtyData = [
                                'product_customize_bill_of_material_id' => $cbomId,
                                'variation_id' => $variationId,
                                'part_quantity' => isset($data[$partQtyKey][$i]) ? $data[$partQtyKey][$i] : null,
                                'podi_quantity' => isset($data[$podiQtyKey][$i]) ? $data[$podiQtyKey][$i] : null
                            ];
                            
                            $this->db->table('product_customize_bill_of_material_quantity')->insert($cbomQtyData);
                        }
                    }
                }
            }
            
            $this->db->transComplete();
            
            if ($this->db->transStatus() === false) {
                return $this->response->setJSON([
                    'error' => 1,
                    'message' => 'Database transaction failed'
                ]);
            }
            
            return $this->response->setJSON([
                'error' => 0,
                'message' => 'Product Added Successfully',
                'redirect' => '/joms-ci4/public/products'
            ]);
            
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'error' => 1,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
    
    public function view_product($id)
    {
        $product = $this->db->query('
            SELECT p.*, pt.name as product_type_name, b.name as body_name, mp.name as main_part_name
            FROM product p
            LEFT JOIN product_type pt ON p.product_type_id = pt.id
            LEFT JOIN body b ON p.body_id = b.id
            LEFT JOIN part mp ON p.main_part_id = mp.id
            WHERE p.id = ?
        ', [$id])->getRowArray();
        
        $bom = $this->db->query('
            SELECT bom.*, p.name as part_name, po.name as podi_name
            FROM product_bill_of_material bom
            LEFT JOIN part p ON bom.part_id = p.id
            LEFT JOIN podi po ON bom.podi_id = po.id
            WHERE bom.product_id = ?
        ', [$id])->getResultArray();
        
        $cbom = $this->db->query('
            SELECT cbom.*, p.name as part_name, po.name as podi_name
            FROM product_customize_bill_of_material cbom
            LEFT JOIN part p ON cbom.part_id = p.id
            LEFT JOIN podi po ON cbom.podi_id = po.id
            WHERE cbom.product_id = ?
        ', [$id])->getResultArray();
        
        foreach ($cbom as &$c) {
            $c['quantities'] = $this->db->query('
                SELECT q.*, v.name as variation_name
                FROM product_customize_bill_of_material_quantity q
                LEFT JOIN variation v ON q.variation_id = v.id
                WHERE q.product_customize_bill_of_material_id = ?
            ', [$c['id']])->getResultArray();
        }
        
        return view('products/view', [
            'title' => 'View Product',
            'product' => $product,
            'bom' => $bom,
            'cbom' => $cbom
        ]);
    }
    
    public function edit($id)
    {
        $product = $this->db->query('SELECT * FROM product WHERE id = ?', [$id])->getRowArray();
        $productTypes = $this->db->query('SELECT * FROM product_type ORDER BY name')->getResultArray();
        $bodies = $this->db->query('SELECT * FROM body ORDER BY name')->getResultArray();
        $parts = $this->db->query('SELECT * FROM part ORDER BY name')->getResultArray();
        $podies = $this->db->query('SELECT * FROM podi ORDER BY name')->getResultArray();
        
        $variations = [];
        if ($product && $product['product_type_id']) {
            $productType = $this->db->query('SELECT variations FROM product_type WHERE id = ?', [$product['product_type_id']])->getRowArray();
            if ($productType && !empty($productType['variations'])) {
                $variationIds = array_map('trim', explode(',', $productType['variations']));
                $placeholders = implode(',', array_fill(0, count($variationIds), '?'));
                $variations = $this->db->query("SELECT * FROM variation WHERE id IN ($placeholders) ORDER BY id", $variationIds)->getResultArray();
            }
        }
        
        $bom = $this->db->query('SELECT * FROM product_bill_of_material WHERE product_id = ?', [$id])->getResultArray();
        $cbom = $this->db->query('SELECT * FROM product_customize_bill_of_material WHERE product_id = ?', [$id])->getResultArray();
        
        foreach ($cbom as &$c) {
            $c['quantities'] = $this->db->query('SELECT * FROM product_customize_bill_of_material_quantity WHERE product_customize_bill_of_material_id = ?', [$c['id']])->getResultArray();
        }
        
        return view('products/edit', [
            'title' => 'Edit Product',
            'product' => $product,
            'productTypes' => $productTypes,
            'bodies' => $bodies,
            'parts' => $parts,
            'podies' => $podies,
            'variations' => $variations,
            'bom' => $bom,
            'cbom' => $cbom
        ]);
    }
    
    public function delete($id)
    {
        try {
            $this->db->transStart();
            
            $cbomIds = $this->db->query('SELECT id FROM product_customize_bill_of_material WHERE product_id = ?', [$id])->getResultArray();
            foreach ($cbomIds as $cbom) {
                $this->db->query('DELETE FROM product_customize_bill_of_material_quantity WHERE product_customize_bill_of_material_id = ?', [$cbom['id']]);
            }
            
            $this->db->query('DELETE FROM product_customize_bill_of_material WHERE product_id = ?', [$id]);
            $this->db->query('DELETE FROM product_bill_of_material WHERE product_id = ?', [$id]);
            $this->db->query('DELETE FROM product WHERE id = ?', [$id]);
            
            $this->db->transComplete();
            
            return redirect()->to('/joms-ci4/public/products')->with('success', 'Product deleted successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to delete: ' . $e->getMessage());
        }
    }
}
