<?php

namespace App\Controllers\Admin;

use App\Core\Config;
use App\Models\Product;

class ProductController extends BaseController
{
    private Product $productModel;
    
    public function __construct()
    {
        parent::__construct();
        $this->productModel = new Product();
    }
    
    public function index()
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = (int) Config::get('admin.list_per_page', 15);
        $search = trim($_GET['search'] ?? '');

        $result = $this->productModel->getListForAdmin(['search' => $search], $page, $limit);

        $this->render('products/index', [
            'title' => Config::get('messages.titles.admin_products'),
            'products' => $result['rows'],
            'page' => $page,
            'total' => $result['total'],
            'limit' => $limit,
            'search' => $search,
        ]);
    }

    public function create()
    {
        $this->render('products/form', [
            'title' => Config::get('messages.titles.admin_product_create'),
            'product' => null
        ]);
    }

    public function edit(int $id)
    {
        $id = (int) $id;
        $product = $this->productModel->find($id);
        if (!$product) {
            $this->setError(Config::get('messages.admin.product_not_found'));
            $this->redirect('/admin/products');
            return;
        }
        
        $this->render('products/form', [
            'title' => Config::get('messages.titles.admin_product_edit'),
            'product' => $product
        ]);
    }

    public function save()
    {
        if (!$this->requireAdminCsrf()) {
            $this->setError(Config::get('messages.admin_login.csrf_invalid'));
            $this->redirect('/admin/products');
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $stock = (int)($_POST['stock'] ?? 0);
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $listedAt = $this->parseListedAtFromPost();
        $isRecommended = !empty($_POST['is_recommended']) ? 1 : 0;
        $recommendedSort = max(0, min(999999, (int) ($_POST['recommended_sort'] ?? 0)));

        if (empty($name)) {
            $this->setError(Config::get('messages.admin.product_name_required'));
            $this->redirect($id ? "/admin/products/edit/{$id}" : '/admin/products/create');
            return;
        }
        
        if ($price <= 0) {
            $this->setError(Config::get('messages.admin.price_positive'));
            $this->redirect($id ? "/admin/products/edit/{$id}" : '/admin/products/create');
            return;
        }

        $imagePath = null;
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['image'];
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
            if (!in_array($file['type'], $allowedTypes)) {
                $this->setError(Config::get('messages.admin.image_type_invalid'));
                $this->redirect($id ? "/admin/products/edit/{$id}" : '/admin/products/create');
                return;
            }
            
            $maxBytes = (int) Config::get('upload.max_size_bytes', 2 * 1024 * 1024);
            if ($file['size'] > $maxBytes) {
                $this->setError(sprintf(Config::get('messages.admin.image_size_max'), round($maxBytes / 1024 / 1024, 1)));
                $this->redirect($id ? "/admin/products/edit/{$id}" : '/admin/products/create');
                return;
            }

            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $ext;
            $imagesPath = trim((string) Config::get('upload.images_path', 'images'), '/');
            $uploadDir = dirname(__DIR__, 3) . '/' . ($imagesPath !== '' ? $imagesPath . '/' : 'images/');
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
                $imagePath = $filename;
            }
        }
        
        try {
            if ($id > 0) {
                $sql = "UPDATE items SET name = ?, price = ?, stock_quantity = ?, category = ?, description = ?,
                    listed_at = ?, is_recommended = ?, recommended_sort = ?";
                $params = [$name, $price, $stock, $category, $description, $listedAt, $isRecommended, $recommendedSort];

                if ($imagePath) {
                    $sql .= ", image_path = ?";
                    $params[] = $imagePath;
                }

                $sql .= " WHERE id = ?";
                $params[] = $id;
                
                $stmt = $this->productModel->getPdo()->prepare($sql);
                $stmt->execute($params);
                
                $this->setSuccess(Config::get('messages.admin.product_update_success'));
            } else {
                $stmt = $this->productModel->getPdo()->prepare(
                    'INSERT INTO items (name, price, stock_quantity, category, description, image_path, listed_at, is_recommended, recommended_sort)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $name,
                    $price,
                    $stock,
                    $category,
                    $description,
                    $imagePath,
                    $listedAt,
                    $isRecommended,
                    $recommendedSort,
                ]);
                
                $this->setSuccess(Config::get('messages.admin.product_create_success'));
            }
        } catch (\PDOException $e) {
            error_log('Product save error: ' . $e->getMessage());
            $this->setError(Config::get('messages.admin.operation_failed'));
        }
        
        $this->redirect('/admin/products');
    }

    /**
     * Normalize datetime-local (YYYY-MM-DDTHH:mm) or SQL datetime to MySQL datetime string.
     */
    private function parseListedAtFromPost(): string
    {
        $raw = trim((string) ($_POST['listed_at'] ?? ''));
        if ($raw === '') {
            return date('Y-m-d H:i:s');
        }
        $raw = str_replace('T', ' ', $raw);
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $raw)) {
            return $raw . ':00';
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $raw)) {
            return $raw;
        }

        return date('Y-m-d H:i:s');
    }

    public function delete(int $id)
    {
        if (!$this->requireAdminCsrf()) {
            $this->setError(Config::get('messages.admin_login.csrf_invalid'));
            $this->redirect('/admin/products');
            return;
        }

        $id = (int) $id;
        try {
            $product = $this->productModel->find($id);
            if ($product && !empty($product['image_path'])) {
                $imagesPath = trim((string) Config::get('upload.images_path', 'images'), '/');
                $baseDir = dirname(__DIR__, 3) . '/' . ($imagesPath !== '' ? $imagesPath . '/' : 'images/');
                $imagePath = $baseDir . $product['image_path'];
                if (file_exists($imagePath)) {
                    unlink($imagePath);
                }
            }
            $stmt = $this->productModel->getPdo()->prepare("DELETE FROM items WHERE id = ?");
            $stmt->execute([$id]);
            
            $this->setSuccess(Config::get('messages.admin.product_deleted'));
        } catch (\PDOException $e) {
            error_log('Product delete error: ' . $e->getMessage());
            $this->setError(Config::get('messages.admin.delete_failed'));
        }
        
        $this->redirect('/admin/products');
    }
}