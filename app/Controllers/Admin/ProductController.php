<?php

namespace App\Controllers\Admin;

use App\Models\Product;

class ProductController extends BaseController
{
    private Product $productModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
    }
    
    /**
     * 商品列表
     */
    public function index()
    {
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = 15;
        $offset = ($page - 1) * $limit;
        $search = trim($_GET['search'] ?? '');
        
        // 构建查询
        $sql = "SELECT * FROM items";
        $countSql = "SELECT COUNT(*) FROM items";
        $params = [];
        
        if (!empty($search)) {
            $sql .= " WHERE name LIKE ? OR category LIKE ?";
            $countSql .= " WHERE name LIKE ? OR category LIKE ?";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }
        
        $sql .= " ORDER BY id DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        // 获取商品列表
        $stmt = $this->productModel->getPdo()->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();
        
        // 获取总数
        $stmt = $this->productModel->getPdo()->prepare($countSql);
        $stmt->execute($search ? ["%{$search}%", "%{$search}%"] : []);
        $total = (int)$stmt->fetchColumn();
        
        $this->render('products/index', [
            'title' => '商品管理',
            'products' => $products,
            'page' => $page,
            'total' => $total,
            'limit' => $limit,
            'search' => $search
        ]);
    }
    
    /**
     * 新增商品页面
     */
    public function create()
    {
        $this->render('products/form', [
            'title' => '新增商品',
            'product' => null
        ]);
    }
    
    /**
     * 编辑商品页面
     */
    public function edit(int $id)
    {
        $product = $this->productModel->find($id);
        if (!$product) {
            $this->setError('商品不存在');
            $this->redirect('/admin/products');
            return;
        }
        
        $this->render('products/form', [
            'title' => '编辑商品',
            'product' => $product
        ]);
    }
    
    /**
     * 保存商品
     */
    public function save()
    {
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        
        // 验证
        if (empty($name)) {
            $this->setError('商品名称不能为空');
            $this->redirect($id ? "/admin/products/edit/{$id}" : '/admin/products/create');
            return;
        }
        
        if ($price <= 0) {
            $this->setError('价格必须大于0');
            $this->redirect($id ? "/admin/products/edit/{$id}" : '/admin/products/create');
            return;
        }
        
        // 处理图片上传
        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            
            // 验证图片类型
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                $this->setError('只支持 JPG、PNG、GIF 格式的图片');
                $this->redirect($id ? "/admin/products/edit/{$id}" : '/admin/products/create');
                return;
            }
            
            // 验证图片大小（最大2MB）
            if ($file['size'] > 2 * 1024 * 1024) {
                $this->setError('图片大小不能超过2MB');
                $this->redirect($id ? "/admin/products/edit/{$id}" : '/admin/products/create');
                return;
            }
            
            // 生成文件名
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $uploadDir = __DIR__ . '/../../../images/';
            
            // 确保目录存在
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // 移动文件
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $imagePath = $filename;
            }
        }
        
        try {
            if ($id > 0) {
                // 更新
                $sql = "UPDATE items SET name = ?, price = ?, stock_quantity = ?, category = ?, description = ?";
                $params = [$name, $price, $stock, $category, $description];
                
                if ($imagePath) {
                    $sql .= ", image_path = ?";
                    $params[] = $imagePath;
                }
                
                $sql .= " WHERE id = ?";
                $params[] = $id;
                
                $stmt = $this->productModel->getPdo()->prepare($sql);
                $stmt->execute($params);
                
                $this->setSuccess('商品更新成功');
            } else {
                // 新增
                $stmt = $this->productModel->getPdo()->prepare(
                    "INSERT INTO items (name, price, stock_quantity, category, description, image_path) 
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $stmt->execute([$name, $price, $stock, $category, $description, $imagePath]);
                
                $this->setSuccess('商品新增成功');
            }
        } catch (\PDOException $e) {
            error_log('Product save error: ' . $e->getMessage());
            $this->setError('操作失败，请稍后重试');
        }
        
        $this->redirect('/admin/products');
    }
    
    /**
     * 删除商品
     */
    public function delete(int $id)
    {
        try {
            // 先获取商品信息，用于删除图片
            $product = $this->productModel->find($id);
            
            if ($product && !empty($product['image_path'])) {
                // 删除图片文件
                $imagePath = __DIR__ . '/../../../images/' . $product['image_path'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            
            // 删除商品
            $stmt = $this->productModel->getPdo()->prepare("DELETE FROM items WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->setSuccess('商品已删除');
        } catch (\PDOException $e) {
            error_log('Product delete error: ' . $e->getMessage());
            $this->setError('删除失败，请稍后重试');
        }
        
        $this->redirect('/admin/products');
    }
}