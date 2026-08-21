<?php

namespace App\Controllers;

use App\Models\ProductModel;

class Products extends BaseController
{
    protected ProductModel $productModel;

    public function __construct()
    {
        $this->productModel = new ProductModel();
    }

    /** จำนวนรายการต่อหน้า — เครื่องสเปคน้อยจะได้ไม่ต้องวาดตารางเป็นพัน ๆ แถวทีเดียว */
    private const PER_PAGE = 100;

    public function index()
    {
        $search   = $this->request->getGet('search');
        $category = $this->request->getGet('category');
        $showAll  = $this->request->getGet('per') === 'all';

        $builder = $this->productModel->orderBy('name');

        if ($search) {
            $builder->groupStart()
                    ->like('name', $search)
                    ->orLike('barcode', $search)
                    ->groupEnd();
        }
        if ($category) {
            $builder->where('category', $category);
        }

        if ($showAll) {
            $products  = $builder->findAll();
            $pager     = null;
            $total     = count($products);
            $rowOffset = 0;
        } else {
            $products  = $builder->paginate(self::PER_PAGE);
            $pager     = $this->productModel->pager;
            $total     = $pager->getTotal();
            $rowOffset = (max(1, $pager->getCurrentPage()) - 1) * self::PER_PAGE;
        }

        $categories = $this->productModel->select('category')
                                         ->where('category IS NOT NULL')
                                         ->groupBy('category')
                                         ->orderBy('category')
                                         ->findAll();

        return view('products/index', [
            'title'      => 'จัดการสินค้า',
            'products'   => $products,
            'categories' => array_column($categories, 'category'),
            'search'     => $search,
            'category'   => $category,
            'pager'      => $pager,
            'total'      => $total,
            'row_offset' => $rowOffset,
            'show_all'   => $showAll,
            'per_page'   => self::PER_PAGE,
        ]);
    }

    public function create()
    {
        return view('products/create', ['title' => 'เพิ่มสินค้าใหม่']);
    }

    public function store()
    {
        $rules = [
            'name'  => 'required|max_length[255]',
            'price' => 'required|numeric|greater_than_equal_to[0]',
            'stock' => 'required|integer|greater_than_equal_to[0]',
        ];

        if (! $this->validate($rules)) {
            return view('products/create', [
                'title'      => 'เพิ่มสินค้าใหม่',
                'validation' => $this->validator,
                'old'        => $this->request->getPost(),
            ]);
        }

        $barcode        = trim($this->request->getPost('barcode'));
        $wholesalePrice = $this->request->getPost('wholesale_price');
        $cost           = $this->request->getPost('cost');

        if ($barcode) {
            $this->productModel->releaseBarcodeFromDeleted($barcode);
        }
        if ($barcode && $this->productModel->where('barcode', $barcode)->first()) {
            return view('products/create', [
                'title'      => 'เพิ่มสินค้าใหม่',
                'validation' => $this->validator,
                'old'        => $this->request->getPost(),
                'error'      => 'บาร์โค้ด ' . esc($barcode) . ' มีอยู่ในระบบแล้ว',
            ]);
        }

        $this->productModel->insert([
            'name'            => trim($this->request->getPost('name')),
            'barcode'         => $barcode ?: null,
            'price'           => $this->request->getPost('price'),
            'wholesale_price' => ($wholesalePrice !== '' && $wholesalePrice !== null) ? (float)$wholesalePrice : 0,
            'cost'            => ($cost !== '' && $cost !== null) ? (float)$cost : 0,
            'stock'           => $this->request->getPost('stock'),
            'category'        => trim($this->request->getPost('category')) ?: null,
            'description'     => trim($this->request->getPost('description')) ?: null,
        ]);

        return redirect()->to('/products')->with('success', 'เพิ่มสินค้าสำเร็จ');
    }

    public function edit(int $id)
    {
        $product = $this->productModel->find($id);
        if (! $product) {
            return redirect()->to('/products')->with('error', 'ไม่พบสินค้า');
        }

        return view('products/edit', [
            'title'   => 'แก้ไขสินค้า',
            'product' => $product,
        ]);
    }

    public function update(int $id)
    {
        $product = $this->productModel->find($id);
        if (! $product) {
            return redirect()->to('/products')->with('error', 'ไม่พบสินค้า');
        }

        $rules = [
            'name'  => 'required|max_length[255]',
            'price' => 'required|numeric|greater_than_equal_to[0]',
            'stock' => 'required|integer|greater_than_equal_to[0]',
        ];

        if (! $this->validate($rules)) {
            return view('products/edit', [
                'title'      => 'แก้ไขสินค้า',
                'product'    => array_merge($product, $this->request->getPost()),
                'validation' => $this->validator,
            ]);
        }

        $barcode        = trim($this->request->getPost('barcode'));
        $wholesalePrice = $this->request->getPost('wholesale_price');
        $cost           = $this->request->getPost('cost');

        if ($barcode) {
            $this->productModel->releaseBarcodeFromDeleted($barcode);
            $existing = $this->productModel->where('barcode', $barcode)->where('id !=', $id)->first();
            if ($existing) {
                return view('products/edit', [
                    'title'      => 'แก้ไขสินค้า',
                    'product'    => array_merge($product, $this->request->getPost()),
                    'validation' => $this->validator,
                    'error'      => 'บาร์โค้ด ' . esc($barcode) . ' มีอยู่ในระบบแล้ว',
                ]);
            }
        }

        $this->productModel->update($id, [
            'name'            => trim($this->request->getPost('name')),
            'barcode'         => $barcode ?: null,
            'price'           => $this->request->getPost('price'),
            'wholesale_price' => ($wholesalePrice !== '' && $wholesalePrice !== null) ? (float)$wholesalePrice : 0,
            'cost'            => ($cost !== '' && $cost !== null) ? (float)$cost : 0,
            'stock'           => $this->request->getPost('stock'),
            'category'        => trim($this->request->getPost('category')) ?: null,
            'description'     => trim($this->request->getPost('description')) ?: null,
        ]);

        return redirect()->to('/products')->with('success', 'แก้ไขสินค้าสำเร็จ');
    }

    public function delete(int $id)
    {
        $this->productModel->delete($id);
        return redirect()->to('/products')->with('success', 'ลบสินค้าสำเร็จ');
    }

    public function importTemplate()
    {
        $response = $this->response
            ->setHeader('Content-Type', 'text/csv; charset=utf-8')
            ->setHeader('Content-Disposition', 'attachment; filename="products_template.csv"');

        $csv = "\xEF\xBB\xBF" // BOM
             . "ชื่อสินค้า,บาร์โค้ด,ราคาขาย,ต้นทุน,จำนวนสต๊อก,หมวดหมู่\r\n"
             . "ข้าวผัด,8851111234567,65,30,100,อาหาร\r\n"
             . "น้ำดื่ม,,10,,200,เครื่องดื่ม\r\n";

        return $response->setBody($csv);
    }

    public function importForm()
    {
        return view('products/import', ['title' => 'นำเข้าสินค้า CSV']);
    }

    public function doImport()
    {
        $file = $this->request->getFile('csv_file');
        if (! $file || ! $file->isValid()) {
            return redirect()->back()->with('error', 'กรุณาเลือกไฟล์ CSV');
        }

        $ext = strtolower($file->getClientExtension());
        if (! in_array($ext, ['csv', 'txt'])) {
            return redirect()->back()->with('error', 'รองรับเฉพาะไฟล์ .csv');
        }

        $fp = fopen($file->getTempName(), 'r');
        // ข้าม BOM ถ้ามี
        $bom = fread($fp, 3);
        if ($bom !== "\xEF\xBB\xBF") {
            rewind($fp);
        }

        $header  = fgetcsv($fp); // skip header row
        $inserted = $updated = $errors = 0;
        $errorList = [];

        while (($row = fgetcsv($fp)) !== false) {
            if (count($row) < 3) continue;
            [$name, $barcode, $price, $cost, $stock, $category] = array_pad($row, 6, '');
            $name     = trim($name);
            $barcode  = trim($barcode);
            $price    = (float) str_replace(',', '', $price);
            $cost     = $cost  !== '' ? (float) str_replace(',', '', $cost)  : null;
            $stock    = $stock !== '' ? (int)   $stock : 0;
            $category = trim($category) ?: null;

            if ($name === '' || $price < 0) {
                $errors++;
                $errorList[] = "แถว: " . implode(',', $row) . " — ชื่อหรือราคาไม่ถูกต้อง";
                continue;
            }

            // ถ้ามีบาร์โค้ด ให้ตรวจว่ามีอยู่แล้วไหม
            if ($barcode) {
                $this->productModel->releaseBarcodeFromDeleted($barcode);
            }
            $existing = $barcode ? $this->productModel->findByBarcode($barcode) : null;
            if ($existing) {
                $this->productModel->update($existing['id'], [
                    'name'     => $name,
                    'price'    => $price,
                    'cost'     => $cost,
                    'stock'    => $stock,
                    'category' => $category,
                ]);
                $updated++;
            } else {
                $this->productModel->insert([
                    'name'     => $name,
                    'barcode'  => $barcode ?: null,
                    'price'    => $price,
                    'cost'     => $cost,
                    'stock'    => $stock,
                    'category' => $category,
                ]);
                $inserted++;
            }
        }
        fclose($fp);

        $msg = "นำเข้าสำเร็จ: เพิ่ม {$inserted} รายการ, อัปเดต {$updated} รายการ";
        if ($errors > 0) {
            $msg .= ", ข้าม {$errors} แถว (ข้อมูลไม่ครบ)";
        }

        return redirect()->to('/products')->with('success', $msg);
    }

    // AJAX: search products by name or barcode
    public function search()
    {
        $q = $this->request->getGet('q') ?? '';
        if (strlen($q) < 1) {
            return $this->response->setJSON([]);
        }
        $products = $this->productModel->searchByNameOrBarcode($q);
        return $this->response->setJSON($products);
    }

    // AJAX: find product by exact barcode
    public function getByBarcode()
    {
        $barcode = $this->request->getGet('barcode') ?? '';
        $product = $this->productModel->findByBarcode($barcode);
        if ($product) {
            return $this->response->setJSON(['success' => true, 'product' => $product]);
        }
        return $this->response->setJSON(['success' => false, 'message' => 'ไม่พบสินค้า (บาร์โค้ด: ' . esc($barcode) . ')']);
    }
}
