<?php
namespace App\Controllers;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\RoundBlockSizeMode;

class Stock extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    private function getOrCreateQr(int $productId, int $patternId, int $variationId): array
    {
        // Fast path — already exists
        $existing = $this->db->query(
            'SELECT * FROM qr_codes WHERE product_id=? AND pattern_id=? AND variation_id=?',
            [$productId, $patternId, $variationId]
        )->getRowArray();
        if ($existing) return $existing;

        // Race-safe: lock the table for number reservation
        $this->db->transStart();

        $row      = $this->db->query('SELECT COALESCE(MAX(qr_number), 10000) + 1 AS next_num FROM qr_codes FOR UPDATE')->getRowArray();
        $qrNumber = (int)$row['next_num'];

        $qrCode = new QrCode(
            data: (string)$qrNumber,
            encoding: new Encoding('UTF-8'),
            errorCorrectionLevel: ErrorCorrectionLevel::High,
            size: 300,
            margin: 10,
            roundBlockSizeMode: RoundBlockSizeMode::Margin,
            foregroundColor: new Color(0, 0, 0),
            backgroundColor: new Color(255, 255, 255)
        );

        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        $b64    = base64_encode($result->getString());
        $now    = date('Y-m-d H:i:s');

        // INSERT IGNORE: if unique constraint fires (race), silently skip
        $this->db->query(
            'INSERT IGNORE INTO qr_codes (qr_number, product_id, pattern_id, variation_id, qr_image, generated_at) VALUES (?,?,?,?,?,?)',
            [$qrNumber, $productId, $patternId, $variationId, $b64, $now]
        );

        $this->db->transComplete();

        // Re-fetch (handles race: another process may have inserted first)
        return $this->db->query(
            'SELECT * FROM qr_codes WHERE product_id=? AND pattern_id=? AND variation_id=?',
            [$productId, $patternId, $variationId]
        )->getRowArray();
    }

    public function index()
    {
        $locations = $this->db->query('SELECT * FROM stock_location WHERE is_active=1 ORDER BY name')->getResultArray();

        // Fetch all stock rows with product image
        $rows = $this->db->query('
            SELECT ps.*,
                   p.name AS product_name, p.sku, p.image AS product_image,
                   pp.name AS pattern_name, pp.tamil_name AS pattern_tamil_name,
                   pp.is_default AS pat_is_default, pp.pattern_code,
                   v.name AS variation_name, v.size AS variation_size,
                   sl.name AS location_name, sl.code AS location_code
            FROM product_stock ps
            JOIN product p          ON p.id  = ps.product_id
            JOIN product_pattern pp ON pp.id = ps.pattern_id
            JOIN variation v        ON v.id  = ps.variation_id
            JOIN stock_location sl  ON sl.id = ps.location_id
            WHERE sl.is_active = 1
            ORDER BY p.name, pp.name, v.size, sl.name
        ')->getResultArray();

        // Group into nested structure: product → pattern → variations[]
        $products = [];
        foreach ($rows as $r) {
            $pid  = $r['product_id'];
            $ptid = $r['pattern_id'];
            if (!isset($products[$pid])) {
                $products[$pid] = [
                    'product'   => [
                        'id'    => $pid,
                        'name'  => $r['product_name'],
                        'sku'   => $r['sku'],
                        'image' => $r['product_image'],
                    ],
                    'total_qty' => 0,
                    'low_count' => 0,
                    'patterns'  => [],
                ];
            }
            if (!isset($products[$pid]['patterns'][$ptid])) {
                $products[$pid]['patterns'][$ptid] = [
                    'pattern'    => [
                        'id'           => $ptid,
                        'name'         => $r['pattern_name'],
                        'is_default'   => $r['pat_is_default'],
                        'pattern_code' => $r['pattern_code'] ?? '',
                        'tamil_name'   => $r['pattern_tamil_name'] ?? '',
                    ],
                    'variations' => [],
                ];
            }
            $isLow = $r['min_qty'] > 0 && $r['qty'] < $r['min_qty'];
            $products[$pid]['total_qty']           += (int)$r['qty'];
            if ($isLow) $products[$pid]['low_count']++;
            $products[$pid]['patterns'][$ptid]['variations'][] = [
                'id'             => $r['variation_id'],
                'name'           => $r['variation_name'],
                'size'           => $r['variation_size'],
                'location_id'    => $r['location_id'],
                'location_name'  => $r['location_name'],
                'qty'            => (int)$r['qty'],
                'min_qty'        => (int)$r['min_qty'],
                'stock_id'       => $r['id'],
                'is_low'         => $isLow,
                'updated_at'     => $r['updated_at'],
            ];
        }

        $lowCount  = (int)$this->db->query(
            'SELECT COUNT(*) AS c FROM product_stock WHERE min_qty > 0 AND qty <= min_qty'
        )->getRowArray()['c'];
        $totalQty  = array_sum(array_column($products, 'total_qty'));

        $title = 'Stock Overview';
        return view('stock/index', compact('products', 'locations', 'lowCount', 'totalQty', 'title'));
    }

    public function entry()
    {
        $locations = $this->db->query('SELECT * FROM stock_location WHERE is_active=1 ORDER BY name')->getResultArray();
        $products  = $this->db->query('SELECT id, name, sku, image FROM product ORDER BY name')->getResultArray();
        $preProduct = (int)($this->request->getGet('product_id') ?? 0);
        $title = 'Stock Entry';
        return view('stock/entry', compact('locations', 'products', 'preProduct', 'title'));
    }

    public function getEntryGrid()
    {
        $productId  = (int)$this->request->getPost('product_id');
        $patternId  = (int)$this->request->getPost('pattern_id');
        $locationId = (int)$this->request->getPost('location_id');
        if (!$productId || !$patternId || !$locationId) {
            return $this->response->setJSON(['error' => 'Missing params']);
        }

        $product = $this->db->query(
            'SELECT p.*, pt.variations FROM product p LEFT JOIN product_type pt ON pt.id=p.product_type_id WHERE p.id=?',
            [$productId]
        )->getRowArray();
        $pattern = $this->db->query('SELECT * FROM product_pattern WHERE id=?', [$patternId])->getRowArray();
        if (!$product || !$pattern) return $this->response->setJSON(['error' => 'Not found']);

        $vidList = array_filter(array_map('intval', explode(',', $product['variations'] ?? '')));
        if (empty($vidList)) return $this->response->setJSON(['variations' => []]);

        $ph = implode(',', array_fill(0, count($vidList), '?'));
        $variations = $this->db->query("SELECT * FROM variation WHERE id IN ($ph) ORDER BY size", $vidList)->getResultArray();

        foreach ($variations as &$v) {
            $stock = $this->db->query(
                'SELECT qty, min_qty FROM product_stock WHERE product_id=? AND pattern_id=? AND variation_id=? AND location_id=?',
                [$productId, $patternId, $v['id'], $locationId]
            )->getRowArray();
            $v['current_qty'] = $stock['qty'] ?? 0;
            $v['min_qty']     = $stock['min_qty'] ?? 0;
        }
        unset($v);

        return $this->response->setJSON(['product' => $product, 'pattern' => $pattern, 'variations' => $variations]);
    }

    public function getPatterns()
    {
        $productId = (int)$this->request->getPost('product_id');
        $patterns  = $this->db->query(
            'SELECT id, name, pattern_code, tamil_name, is_default FROM product_pattern WHERE product_id=? ORDER BY is_default DESC, name',
            [$productId]
        )->getResultArray();
        return $this->response->setJSON($patterns);
    }

    public function saveEntry()
    {
        $data       = $this->request->getPost();
        $locationId = (int)($data['location_id'] ?? 0);
        $productId  = (int)($data['product_id'] ?? 0);
        $patternId  = (int)($data['pattern_id'] ?? 0);
        $entryType  = $data['entry_type'] ?? 'add';
        $note       = $data['note'] ?? '';

        $variationIds = $data['variation_id'] ?? [];
        $qtys         = $data['qty'] ?? [];

        $saved = 0;
        foreach ($variationIds as $i => $vid) {
            $vid  = (int)$vid;
            $qty  = (int)($qtys[$i] ?? 0);
            if ($qty === 0) continue;

            $existing = $this->db->query(
                'SELECT * FROM product_stock WHERE product_id=? AND pattern_id=? AND variation_id=? AND location_id=?',
                [$productId, $patternId, $vid, $locationId]
            )->getRowArray();

            if ($entryType === 'set') {
                $newQty = $qty;
                $txType = 'adjustment';
            } else {
                $newQty = ($existing['qty'] ?? 0) + $qty;
                $txType = 'in';
            }

            $now = date('Y-m-d H:i:s');
            if ($existing) {
                $this->db->query(
                    'UPDATE product_stock SET qty=?, updated_at=? WHERE id=?',
                    [$newQty, $now, $existing['id']]
                );
            } else {
                $this->db->query(
                    'INSERT INTO product_stock (product_id,pattern_id,variation_id,location_id,qty,min_qty,updated_at) VALUES (?,?,?,?,?,0,?)',
                    [$productId, $patternId, $vid, $locationId, $newQty, $now]
                );
            }

            if ($qty !== 0) {
                $this->db->query(
                    'INSERT INTO stock_transaction (type,product_id,pattern_id,variation_id,location_id,qty,note,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?)',
                    [$txType, $productId, $patternId, $vid, $locationId, $qty, $note ?: 'Manual entry', 'system', $now]
                );
            }
            $saved++;
        }

        return redirect()->to('stock/entry')->with('success', "Saved $saved variation(s).");
    }

    public function minStock()
    {
        $locations   = $this->db->query('SELECT * FROM stock_location WHERE is_active=1 ORDER BY name')->getResultArray();
        $products    = $this->db->query('SELECT id, name, sku, image FROM product ORDER BY name')->getResultArray();
        $preProduct  = (int)($this->request->getGet('product_id')  ?? 0);
        $prePattern  = (int)($this->request->getGet('pattern_id')  ?? 0);
        $preLocation = (int)($this->request->getGet('location_id') ?? 0);
        return view('stock/min_stock', [
            'title'       => 'Set Minimum Stock',
            'locations'   => $locations,
            'products'    => $products,
            'preProduct'  => $preProduct,
            'prePattern'  => $prePattern,
            'preLocation' => $preLocation,
        ]);
    }

    public function saveMinStock()
    {
        $productId  = (int)$this->request->getPost('product_id');
        $patternId  = (int)$this->request->getPost('pattern_id');
        $locationId = (int)$this->request->getPost('location_id');
        $varIds     = $this->request->getPost('variation_id') ?? [];
        $minQtys    = $this->request->getPost('min_qty') ?? [];

        foreach ($varIds as $i => $vid) {
            $vid    = (int)$vid;
            $minQty = max(0, (int)($minQtys[$i] ?? 0));
            $exists = $this->db->query(
                'SELECT id FROM product_stock WHERE product_id=? AND pattern_id=? AND variation_id=? AND location_id=?',
                [$productId, $patternId, $vid, $locationId]
            )->getRowArray();
            if ($exists) {
                $this->db->query(
                    'UPDATE product_stock SET min_qty=? WHERE product_id=? AND pattern_id=? AND variation_id=? AND location_id=?',
                    [$minQty, $productId, $patternId, $vid, $locationId]
                );
            } else {
                $this->db->table('product_stock')->insert([
                    'product_id'   => $productId,
                    'pattern_id'   => $patternId,
                    'variation_id' => $vid,
                    'location_id'  => $locationId,
                    'qty'          => 0,
                    'min_qty'      => $minQty,
                    'updated_at'   => date('Y-m-d H:i:s'),
                ]);
            }
        }
        return redirect()->to('stock/min-stock')->with('success', 'Minimum stock levels saved.');
    }

    public function qrImage($productId, $patternId, $variationId)
    {
        $qr = $this->getOrCreateQr((int)$productId, (int)$patternId, (int)$variationId);
        $pngData = base64_decode($qr['qr_image']);
        return $this->response
            ->setHeader('Content-Type', 'image/png')
            ->setBody($pngData);
    }

    public function labelGenerate()
    {
        $productTypes = $this->db->query('SELECT id, name FROM product_type ORDER BY name')->getResultArray();
        $title = 'Generate QR Labels';
        return view('stock/label_generate', compact('productTypes', 'title'));
    }

    public function generateLabels()
    {
        $json  = $this->request->getJSON(true);
        $items = $json['items'] ?? null;

        if ($items === null) {
            $raw     = (string)file_get_contents('php://input');
            $decoded = json_decode($raw, true);
            $items   = $decoded['items'] ?? null;
        }

        $items  = (array)$items;
        $labels = [];

        foreach ($items as $item) {
            $pid   = (int)($item['product_id'] ?? 0);
            $patid = (int)($item['pattern_id'] ?? 0);
            $vid   = (int)($item['variation_id'] ?? 0);
            $qty   = max(1, (int)($item['qty'] ?? 1));
            if (!$pid || !$patid || !$vid) continue;

            $product   = $this->db->query('SELECT id, name, short_name, sku FROM product WHERE id=?', [$pid])->getRowArray();
            $pattern   = $this->db->query('SELECT id, name, short_name, pattern_code, is_default FROM product_pattern WHERE id=?', [$patid])->getRowArray();
            $variation = $this->db->query('SELECT id, name, size FROM variation WHERE id=?', [$vid])->getRowArray();
            if (!$product || !$pattern || !$variation) continue;

            try {
                $qr = $this->getOrCreateQr($pid, $patid, $vid);
            } catch (\Throwable $e) {
                continue;
            }

            $labels[] = [
                'qr_number'       => $qr['qr_number'],
                'qr_image_base64' => $qr['qr_image'],
                'product_name'    => $product['name'],
                'product_label'   => ($product['short_name'] ?? '') ?: $product['name'],
                'sku'             => $product['sku'] ?? '',
                'pattern_name'    => $pattern['name'],
                'pattern_label'   => ($pattern['short_name'] ?? '') ?: $pattern['name'],
                'pattern_code'    => $pattern['pattern_code'] ?? '',
                'pat_is_default'  => (bool)$pattern['is_default'],
                'variation_name'  => $variation['name'],
                'size'            => $variation['size'],
                'qty'             => $qty,
            ];
        }

        return $this->response->setJSON(['labels' => $labels]);
    }

    public function labels($productId)
    {
        $product = $this->db->query(
            'SELECT p.*, pt.variations FROM product p LEFT JOIN product_type pt ON pt.id=p.product_type_id WHERE p.id=?',
            [$productId]
        )->getRowArray();
        if (!$product) return redirect()->to('stock')->with('error', 'Product not found');

        $patterns = $this->db->query(
            'SELECT * FROM product_pattern WHERE product_id=? ORDER BY is_default DESC, name',
            [$productId]
        )->getResultArray();

        $vidList = array_filter(array_map('intval', explode(',', $product['variations'] ?? '')));
        $variations = [];
        if ($vidList) {
            $ph = implode(',', array_fill(0, count($vidList), '?'));
            $variations = $this->db->query("SELECT * FROM variation WHERE id IN ($ph) ORDER BY size", $vidList)->getResultArray();
        }

        $title = 'QR Labels - ' . $product['name'];
        return view('stock/labels', compact('product', 'patterns', 'variations', 'title'));
    }

    public function scan()
    {
        $locations = $this->db->query('SELECT * FROM stock_location WHERE is_active=1 ORDER BY name')->getResultArray();
        $title = 'Scan QR - Deduct Stock';
        return view('stock/scan', compact('locations', 'title'));
    }

    public function getStockInfo()
    {
        $raw = trim($this->request->getPost('qr_data') ?? '');

        if (ctype_digit($raw) && strlen($raw) >= 4) {
            $qrRow = $this->db->query('SELECT * FROM qr_codes WHERE qr_number=?', [(int)$raw])->getRowArray();
            if ($qrRow) {
                $productId   = (int)$qrRow['product_id'];
                $patternId   = (int)$qrRow['pattern_id'];
                $variationId = (int)$qrRow['variation_id'];
            }
        }

        if (!isset($productId)) {
            if (preg_match('/JOMS:P(\d+):PAT(\d+):V(\d+)/', $raw, $m)) {
                $productId   = (int)$m[1];
                $patternId   = (int)$m[2];
                $variationId = (int)$m[3];
            } else {
                $productId   = (int)$this->request->getPost('product_id');
                $patternId   = (int)$this->request->getPost('pattern_id');
                $variationId = (int)$this->request->getPost('variation_id');
            }
        }

        $product   = $this->db->query('SELECT id, name, sku, image FROM product WHERE id=?', [$productId])->getRowArray();
        $pattern   = $this->db->query('SELECT id, name, is_default FROM product_pattern WHERE id=?', [$patternId])->getRowArray();
        $variation = $this->db->query('SELECT id, name, size FROM variation WHERE id=?', [$variationId])->getRowArray();

        if (!$product || !$pattern || !$variation) {
            return $this->response->setJSON(['error' => 'QR code not recognised. Check number and try again.']);
        }

        $stocks = $this->db->query(
            'SELECT ps.qty, sl.id as loc_id, sl.name as loc_name FROM product_stock ps JOIN stock_location sl ON sl.id=ps.location_id WHERE ps.product_id=? AND ps.pattern_id=? AND ps.variation_id=? AND sl.is_active=1',
            [$productId, $patternId, $variationId]
        )->getResultArray();

        return $this->response->setJSON([
            'product'   => $product,
            'pattern'   => $pattern,
            'variation' => $variation,
            'stocks'    => $stocks,
        ]);
    }

    public function deduct()
    {
        $productId   = (int)$this->request->getPost('product_id');
        $patternId   = (int)$this->request->getPost('pattern_id');
        $variationId = (int)$this->request->getPost('variation_id');
        $locationId  = (int)$this->request->getPost('location_id');
        $qty         = (int)$this->request->getPost('qty');

        if ($qty <= 0) return $this->response->setJSON(['error' => 'Quantity must be > 0']);

        $stock = $this->db->query(
            'SELECT * FROM product_stock WHERE product_id=? AND pattern_id=? AND variation_id=? AND location_id=?',
            [$productId, $patternId, $variationId, $locationId]
        )->getRowArray();

        $currentQty = $stock['qty'] ?? 0;
        if ($currentQty < $qty) {
            return $this->response->setJSON(['error' => "Insufficient stock. Available: $currentQty"]);
        }

        $now = date('Y-m-d H:i:s');
        if ($stock) {
            $this->db->query('UPDATE product_stock SET qty=qty-?, updated_at=? WHERE id=?', [$qty, $now, $stock['id']]);
        } else {
            $this->db->query(
                'INSERT INTO product_stock (product_id,pattern_id,variation_id,location_id,qty,updated_at) VALUES (?,?,?,?,?,?)',
                [$productId, $patternId, $variationId, $locationId, -$qty, $now]
            );
        }

        $this->db->query(
            'INSERT INTO stock_transaction (type,product_id,pattern_id,variation_id,location_id,qty,note,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?)',
            ['out', $productId, $patternId, $variationId, $locationId, $qty, 'QR scan sale', 'system', $now]
        );

        return $this->response->setJSON(['success' => true, 'new_qty' => $currentQty - $qty]);
    }


    public function getTransferPatterns()
    {
        $productId  = (int)$this->request->getPost('product_id');
        $locationId = (int)$this->request->getPost('from_location_id');
        if (!$productId || !$locationId) return $this->response->setJSON([]);
        $rows = $this->db->query('
            SELECT DISTINCT pp.id, pp.name, pp.is_default
            FROM product_stock ps
            JOIN product_pattern pp ON pp.id = ps.pattern_id
            WHERE ps.product_id = ? AND ps.location_id = ? AND ps.qty > 0
            ORDER BY pp.is_default DESC, pp.name
        ', [$productId, $locationId])->getResultArray();
        return $this->response->setJSON($rows);
    }

    public function getTransferStock()
    {
        $productId  = (int)$this->request->getPost('product_id');
        $patternId  = (int)$this->request->getPost('pattern_id');
        $locationId = (int)$this->request->getPost('from_location_id');
        if (!$productId || !$patternId || !$locationId) return $this->response->setJSON(['variations' => []]);
        $rows = $this->db->query('
            SELECT v.id, v.name, v.size, ps.qty AS available_qty
            FROM product_stock ps
            JOIN variation v ON v.id = ps.variation_id
            WHERE ps.product_id = ? AND ps.pattern_id = ? AND ps.location_id = ? AND ps.qty > 0
            ORDER BY v.size
        ', [$productId, $patternId, $locationId])->getResultArray();
        return $this->response->setJSON(['variations' => $rows]);
    }
    public function transfer()
    {
        $locations = $this->db->query('SELECT * FROM stock_location WHERE is_active=1 ORDER BY name')->getResultArray();
        $products  = $this->db->query('SELECT id, name, sku, image FROM product ORDER BY name')->getResultArray();
        $title = 'Stock Transfer';
        return view('stock/transfer', compact('locations', 'products', 'title'));
    }

    public function saveTransfer()
    {
        $data    = $this->request->getPost();
        $fromLoc = (int)($data['from_location_id'] ?? 0);
        $toLoc   = (int)($data['to_location_id'] ?? 0);
        $note    = $data['note'] ?? '';

        if (!$fromLoc || !$toLoc || $fromLoc === $toLoc) {
            return redirect()->back()->withInput()->with('error', 'Invalid locations');
        }

        $pids   = $data['product_id'] ?? [];
        $patids = $data['pattern_id'] ?? [];
        $vids   = $data['variation_id'] ?? [];
        $qtys   = $data['qty'] ?? [];

        $errors = [];
        $items  = [];
        foreach ($pids as $i => $pid) {
            $pid   = (int)$pid;
            $patid = (int)($patids[$i] ?? 0);
            $vid   = (int)($vids[$i] ?? 0);
            $qty   = (int)($qtys[$i] ?? 0);
            if (!$pid || !$patid || !$vid || $qty <= 0) continue;

            $stock = $this->db->query(
                'SELECT qty FROM product_stock WHERE product_id=? AND pattern_id=? AND variation_id=? AND location_id=?',
                [$pid, $patid, $vid, $fromLoc]
            )->getRowArray();

            $avail = $stock['qty'] ?? 0;
            if ($avail < $qty) {
                $pname = $this->db->query('SELECT name FROM product WHERE id=?', [$pid])->getRowArray()['name'] ?? "P$pid";
                $errors[] = "$pname: need $qty, only $avail available";
            } else {
                $items[] = compact('pid', 'patid', 'vid', 'qty');
            }
        }

        if ($errors) return redirect()->back()->withInput()->with('error', implode('; ', $errors));
        if (empty($items)) return redirect()->back()->withInput()->with('error', 'No valid items');

        $now = date('Y-m-d H:i:s');
        $this->db->query(
            'INSERT INTO stock_transfer (from_location_id,to_location_id,note,created_by,created_at) VALUES (?,?,?,?,?)',
            [$fromLoc, $toLoc, $note, 'system', $now]
        );
        $transferId = $this->db->insertID();

        foreach ($items as $it) {
            $this->db->query('UPDATE product_stock SET qty=qty-?, updated_at=? WHERE product_id=? AND pattern_id=? AND variation_id=? AND location_id=?',
                [$it['qty'], $now, $it['pid'], $it['patid'], $it['vid'], $fromLoc]);

            $exist = $this->db->query('SELECT id FROM product_stock WHERE product_id=? AND pattern_id=? AND variation_id=? AND location_id=?',
                [$it['pid'], $it['patid'], $it['vid'], $toLoc])->getRowArray();
            if ($exist) {
                $this->db->query('UPDATE product_stock SET qty=qty+?, updated_at=? WHERE id=?', [$it['qty'], $now, $exist['id']]);
            } else {
                $this->db->query('INSERT INTO product_stock (product_id,pattern_id,variation_id,location_id,qty,updated_at) VALUES (?,?,?,?,?,?)',
                    [$it['pid'], $it['patid'], $it['vid'], $toLoc, $it['qty'], $now]);
            }

            $tNote = "Transfer #$transferId";
            $this->db->query('INSERT INTO stock_transaction (type,product_id,pattern_id,variation_id,location_id,qty,ref_transfer_id,note,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?)',
                ['transfer_out', $it['pid'], $it['patid'], $it['vid'], $fromLoc, $it['qty'], $transferId, $tNote, 'system', $now]);
            $this->db->query('INSERT INTO stock_transaction (type,product_id,pattern_id,variation_id,location_id,qty,ref_transfer_id,note,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?,?)',
                ['transfer_in', $it['pid'], $it['patid'], $it['vid'], $toLoc, $it['qty'], $transferId, $tNote, 'system', $now]);

            $this->db->query('INSERT INTO stock_transfer_item (transfer_id,product_id,pattern_id,variation_id,qty) VALUES (?,?,?,?,?)',
                [$transferId, $it['pid'], $it['patid'], $it['vid'], $it['qty']]);
        }

        return redirect()->to('stock')->with('success', 'Transfer #' . $transferId . ' completed: ' . count($items) . ' item(s) moved');
    }

    public function lowStock()
    {
        $rows = $this->db->query('
            SELECT ps.*,
                   p.name AS product_name, p.sku, p.image AS product_image,
                   pp.name AS pattern_name, pp.is_default AS pat_is_default,
                   v.name AS variation_name, v.size AS variation_size,
                   sl.name AS location_name
            FROM product_stock ps
            JOIN product p          ON p.id  = ps.product_id
            JOIN product_pattern pp ON pp.id = ps.pattern_id
            JOIN variation v        ON v.id  = ps.variation_id
            JOIN stock_location sl  ON sl.id = ps.location_id
            WHERE ps.min_qty > 0 AND ps.qty < ps.min_qty
            ORDER BY (ps.min_qty - ps.qty) DESC, p.name
        ')->getResultArray();

        $title = 'Low Stock Alert';
        return view('stock/low_stock', compact('rows', 'title'));
    }

    public function setMinQty()
    {
        $id     = (int)$this->request->getPost('id');
        $minQty = (int)$this->request->getPost('min_qty');
        $this->db->query('UPDATE product_stock SET min_qty=? WHERE id=?', [$minQty, $id]);
        return $this->response->setJSON(['success' => true]);
    }

    public function auditLog()
    {
        $locId = (int)($this->request->getGet('loc') ?? 0);
        $type  = $this->request->getGet('type') ?? '';
        $from  = $this->request->getGet('from') ?? '';
        $to    = $this->request->getGet('to') ?? '';
        $q     = $this->request->getGet('q') ?? '';

        $locations = $this->db->query('SELECT * FROM stock_location WHERE is_active=1 ORDER BY name')->getResultArray();

        $sql = '
            SELECT st.*,
                   p.name AS product_name, p.sku,
                   pp.name AS pattern_name, pp.is_default AS pat_is_default,
                   v.name AS variation_name, v.size AS variation_size,
                   sl.name AS location_name
            FROM stock_transaction st
            JOIN product p          ON p.id  = st.product_id
            JOIN product_pattern pp ON pp.id = st.pattern_id
            JOIN variation v        ON v.id  = st.variation_id
            JOIN stock_location sl  ON sl.id = st.location_id
            WHERE 1=1
        ';
        $params = [];
        if ($locId) { $sql .= ' AND st.location_id=?'; $params[] = $locId; }
        if ($type)  { $sql .= ' AND st.type=?';        $params[] = $type; }
        if ($from)  { $sql .= ' AND DATE(st.created_at) >= ?'; $params[] = $from; }
        if ($to)    { $sql .= ' AND DATE(st.created_at) <= ?'; $params[] = $to; }
        if ($q)     { $sql .= ' AND (p.name LIKE ? OR p.sku LIKE ?)'; $params[] = "%$q%"; $params[] = "%$q%"; }
        $sql .= ' ORDER BY st.created_at DESC LIMIT 500';

        $transactions = $this->db->query($sql, $params)->getResultArray();
        $title = 'Stock Audit Log';
        return view('stock/audit_log', compact('transactions', 'locations', 'locId', 'type', 'from', 'to', 'q', 'title'));
    }

    public function editNote($id)
    {
        $tx = $this->db->query('SELECT id FROM stock_transaction WHERE id = ?', [$id])->getRowArray();
        if (!$tx) return redirect()->to('stock/audit-log')->with('error', 'Transaction not found');

        $note = trim($this->request->getPost('note') ?? '');
        $this->db->query('UPDATE stock_transaction SET note = ? WHERE id = ?', [$note, $id]);

        return redirect()->to('stock/audit-log?' . http_build_query([
            'loc'  => $this->request->getPost('_loc')  ?? '',
            'type' => $this->request->getPost('_type') ?? '',
            'from' => $this->request->getPost('_from') ?? '',
            'to'   => $this->request->getPost('_to')   ?? '',
            'q'    => $this->request->getPost('_q')    ?? '',
        ]))->with('success', 'Note updated');
    }

    public function bulkDeduct()
    {
        $locationId = (int)$this->request->getPost('location_id');
        $partyName  = $this->request->getPost('party_name') ?? '';
        $notes      = $this->request->getPost('notes') ?? '';
        $items      = $this->request->getPost('items') ?? [];

        if (!$locationId) return $this->response->setJSON(['error' => 'Location required']);
        if (empty($items)) return $this->response->setJSON(['error' => 'No items to deduct']);
        if (empty(trim($notes))) return $this->response->setJSON(['error' => 'Notes are required for a sale/issue']);

        $now = date('Y-m-d H:i:s');
        $deducted = 0;
        $errors   = [];
        $noteText = 'QR scan sale' . ($partyName ? ' - ' . $partyName : '') . ($notes ? ' - ' . $notes : '');

        foreach ($items as $item) {
            $productId   = (int)($item['product_id'] ?? 0);
            $patternId   = (int)($item['pattern_id'] ?? 0);
            $variationId = (int)($item['variation_id'] ?? 0);
            $qty         = (int)($item['qty'] ?? 1);
            if (!$productId || !$patternId || !$variationId) continue;

            $row = $this->db->query(
                'SELECT qty FROM product_stock WHERE product_id=? AND pattern_id=? AND variation_id=? AND location_id=?',
                [$productId, $patternId, $variationId, $locationId]
            )->getRowArray();

            if (!$row) {
                $label = $this->db->query(
                    'SELECT p.sku, p.name AS pname, pp.name AS patname, pp.is_default, v.size, v.name AS vname
                     FROM product p
                     JOIN product_pattern pp ON pp.id = ?
                     JOIN variation v        ON v.id  = ?
                     WHERE p.id = ?',
                    [$patternId, $variationId, $productId]
                )->getRowArray();
                $errLabel = $label
                    ? (($label['sku'] ?: $label['pname']) . ' — ' . ($label['is_default'] ? 'Default' : $label['patname']) . ' — ' . ($label['size'] ? $label['size'].'"' : $label['vname']))
                    : "Product #{$productId}";
                $errors[] = "{$errLabel}: no stock at this location — please add stock first";
                continue;
            }
            if ($row['qty'] < $qty) {
                $errors[] = "Insufficient stock for product #{$productId}";
                continue;
            }

            $this->db->query(
                'UPDATE product_stock SET qty = qty - ? WHERE product_id=? AND pattern_id=? AND variation_id=? AND location_id=?',
                [$qty, $productId, $patternId, $variationId, $locationId]
            );
            $this->db->query(
                'INSERT INTO stock_transaction (type,product_id,pattern_id,variation_id,location_id,qty,note,created_by,created_at) VALUES (?,?,?,?,?,?,?,?,?)',
                ['out', $productId, $patternId, $variationId, $locationId, $qty, $noteText, 'system', $now]
            );
            $deducted++;
        }

        return $this->response->setJSON(['success' => true, 'deducted' => $deducted, 'errors' => $errors]);
    }

    public function fixQrDupes()
    {
        // Step 1: Remove duplicates — keep lowest qr_number per (product, pattern, variation)
        $this->db->query(
            'DELETE qc1 FROM qr_codes qc1
             INNER JOIN qr_codes qc2
               ON  qc2.product_id   = qc1.product_id
               AND qc2.pattern_id   = qc1.pattern_id
               AND qc2.variation_id = qc1.variation_id
               AND qc2.qr_number    < qc1.qr_number'
        );
        $deleted = $this->db->affectedRows();

        // Step 2: Add unique constraint (ignore error if already exists)
        $constraintStatus = 'already exists';
        try {
            $this->db->query(
                'ALTER TABLE qr_codes ADD UNIQUE KEY uq_prod_pat_var (product_id, pattern_id, variation_id)'
            );
            $constraintStatus = 'added';
        } catch (\Throwable $e) {
            // Duplicate key name — constraint already exists, that's fine
        }

        return $this->response->setJSON([
            'success'    => true,
            'deleted'    => $deleted,
            'constraint' => $constraintStatus,
            'message'    => "Cleanup done. {$deleted} duplicate rows removed. Unique constraint: {$constraintStatus}.",
        ]);
    }

    public function bulkGenerateQr()
    {
        $productIds = $this->request->getPost('product_ids') ?? [];
        if (empty($productIds)) {
            $all        = $this->db->query('SELECT id FROM product')->getResultArray();
            $productIds = array_column($all, 'id');
        }

        $created  = 0;
        $existing = 0;

        foreach ($productIds as $productId) {
            $productId = (int)$productId;

            $patterns = $this->db->query(
                'SELECT id FROM product_pattern WHERE product_id = ?', [$productId]
            )->getResultArray();
            if (empty($patterns)) continue;

            $ptRow = $this->db->query(
                'SELECT pt.variations FROM product p JOIN product_type pt ON pt.id = p.product_type_id WHERE p.id = ?',
                [$productId]
            )->getRowArray();
            if (!$ptRow || empty($ptRow['variations'])) continue;

            $varIds = array_filter(array_map('intval', explode(',', $ptRow['variations'])));
            if (empty($varIds)) continue;

            foreach ($patterns as $pat) {
                foreach ($varIds as $varId) {
                    $check = $this->db->query(
                        'SELECT id FROM qr_codes WHERE product_id=? AND pattern_id=? AND variation_id=?',
                        [$productId, $pat['id'], $varId]
                    )->getRowArray();

                    if ($check) {
                        $existing++;
                    } else {
                        $this->getOrCreateQr($productId, (int)$pat['id'], $varId);
                        $created++;
                    }
                }
            }
        }

        return $this->response->setJSON([
            'success'  => true,
            'created'  => $created,
            'existing' => $existing,
            'message'  => "Done! {$created} new QR codes created, {$existing} already existed.",
        ]);
    }

    public function qrRegistry()
    {
        $q         = $this->request->getGet('q');
        $productId = (int)$this->request->getGet('product_id');

        $sql = 'SELECT qc.qr_number, qc.generated_at,
                       p.name AS product_name, p.sku,
                       pp.name AS pattern_name, pp.is_default,
                       v.name  AS variation_name, v.size
                FROM qr_codes qc
                JOIN product         p  ON p.id  = qc.product_id
                JOIN product_pattern pp ON pp.id = qc.pattern_id
                JOIN variation       v  ON v.id  = qc.variation_id
                WHERE 1=1';
        $binds = [];
        if ($q) {
            $sql .= ' AND (p.name LIKE ? OR p.sku LIKE ? OR qc.qr_number LIKE ?)';
            $binds = array_merge($binds, ["%$q%", "%$q%", "%$q%"]);
        }
        if ($productId) {
            $sql .= ' AND qc.product_id = ?';
            $binds[] = $productId;
        }
        $sql .= ' ORDER BY qc.qr_number';

        $rows     = $this->db->query($sql, $binds)->getResultArray();
        $products = $this->db->query('SELECT id, name, sku FROM product ORDER BY name')->getResultArray();
        $title    = 'QR Code Registry';
        return view('stock/qr_registry', compact('rows', 'products', 'q', 'productId', 'title'));
    }
}
