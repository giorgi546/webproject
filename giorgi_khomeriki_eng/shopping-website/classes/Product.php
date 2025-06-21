<?php
// classes/Product.php - Product management class

class Product {
    private $db;
    private $table = 'products';
    
    // Product properties
    public $id;
    public $name;
    public $description;
    public $short_description;
    public $price;
    public $sale_price;
    public $sku;
    public $stock_quantity;
    public $main_image;
    public $gallery_images;
    public $category_id;
    public $weight;
    public $dimensions;
    public $status;
    public $featured;
    public $created_at;
    public $updated_at;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    /**
     * Get product by ID
     */
    public function getById($id) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM " . $this->table . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ? AND p.status = 'active'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            $product = $stmt->fetch();
            $this->fillProperties($product);
            return $product;
        }
        return false;
    }
    
    /**
     * Get all products with optional filters
     */
    public function getAll($filters = [], $limit = null, $offset = 0) {
        $where_conditions = ['p.status = :status'];
        $params = ['status' => 'active'];
        
        // Apply filters
        if (!empty($filters['category'])) {
            $where_conditions[] = 'c.name = :category';
            $params['category'] = $filters['category'];
        }
        
        if (!empty($filters['search'])) {
            $where_conditions[] = '(p.name LIKE :search OR p.description LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['min_price'])) {
            $where_conditions[] = 'COALESCE(p.sale_price, p.price) >= :min_price';
            $params['min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $where_conditions[] = 'COALESCE(p.sale_price, p.price) <= :max_price';
            $params['max_price'] = $filters['max_price'];
        }
        
        if (isset($filters['featured'])) {
            $where_conditions[] = 'p.featured = 1';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT p.*, c.name as category_name 
                FROM " . $this->table . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE {$where_clause}
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        
        $stmt = $this->db->prepare($sql);
        
        // Bind parameters
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        if ($limit) {
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        }
        
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Get featured products
     */
    public function getFeatured($limit = 6) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM " . $this->table . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.featured = 1 AND p.status = 'active' 
                ORDER BY p.created_at DESC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get related products from same category
     */
    public function getRelated($product_id, $category_id, $limit = 4) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM " . $this->table . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.category_id = ? AND p.id != ? AND p.status = 'active' 
                ORDER BY RAND() 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$category_id, $product_id, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Search products
     */
    public function search($query, $limit = 10) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM " . $this->table . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE (p.name LIKE ? OR p.description LIKE ?) 
                AND p.status = 'active' 
                ORDER BY p.name ASC 
                LIMIT ?";
        
        $search_term = '%' . $query . '%';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$search_term, $search_term, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Check if product is in stock
     */
    public function isInStock($product_id, $quantity = 1) {
        $sql = "SELECT stock_quantity FROM " . $this->table . " WHERE id = ? AND status = 'active'";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$product_id]);
        
        if ($stmt->rowCount() > 0) {
            $product = $stmt->fetch();
            return $product['stock_quantity'] >= $quantity;
        }
        return false;
    }
    
    /**
     * Update stock quantity
     */
    public function updateStock($product_id, $quantity) {
        $sql = "UPDATE " . $this->table . " 
                SET stock_quantity = stock_quantity - ?, updated_at = NOW() 
                WHERE id = ? AND status = 'active'";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$quantity, $product_id]);
    }
    
    /**
     * Create new product
     */
    public function create($data) {
        $sql = "INSERT INTO " . $this->table . " 
                (name, description, short_description, price, sale_price, sku, 
                 stock_quantity, main_image, gallery_images, category_id, 
                 weight, dimensions, status, featured) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([
            $data['name'],
            $data['description'],
            $data['short_description'] ?? null,
            $data['price'],
            $data['sale_price'] ?? null,
            $data['sku'] ?? null,
            $data['stock_quantity'] ?? 0,
            $data['main_image'] ?? null,
            $data['gallery_images'] ?? null,
            $data['category_id'] ?? null,
            $data['weight'] ?? null,
            $data['dimensions'] ?? null,
            $data['status'] ?? 'active',
            $data['featured'] ?? 0
        ])) {
            $this->id = $this->db->lastInsertId();
            return $this->id;
        }
        
        return false;
    }
    
    /**
     * Update product
     */
    public function update($id, $data) {
        $sql = "UPDATE " . $this->table . " 
                SET name = ?, description = ?, short_description = ?, 
                    price = ?, sale_price = ?, sku = ?, stock_quantity = ?, 
                    main_image = ?, gallery_images = ?, category_id = ?, 
                    weight = ?, dimensions = ?, status = ?, featured = ?, 
                    updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute([
            $data['name'],
            $data['description'],
            $data['short_description'] ?? null,
            $data['price'],
            $data['sale_price'] ?? null,
            $data['sku'] ?? null,
            $data['stock_quantity'] ?? 0,
            $data['main_image'] ?? null,
            $data['gallery_images'] ?? null,
            $data['category_id'] ?? null,
            $data['weight'] ?? null,
            $data['dimensions'] ?? null,
            $data['status'] ?? 'active',
            $data['featured'] ?? 0,
            $id
        ]);
    }
    
    /**
     * Delete product (soft delete)
     */
    public function delete($id) {
        $sql = "UPDATE " . $this->table . " SET status = 'deleted' WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$id]);
    }
    
    /**
     * Get products by category
     */
    public function getByCategory($category_id, $limit = null) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM " . $this->table . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.category_id = ? AND p.status = 'active' 
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ?";
        }
        
        $stmt = $this->db->prepare($sql);
        
        if ($limit) {
            $stmt->execute([$category_id, $limit]);
        } else {
            $stmt->execute([$category_id]);
        }
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get low stock products
     */
    public function getLowStock($threshold = 5) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM " . $this->table . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.stock_quantity <= ? AND p.status = 'active' 
                ORDER BY p.stock_quantity ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$threshold]);
        return $stmt->fetchAll();
    }
    
    /**
     * Get total product count
     */
    public function getTotalCount($filters = []) {
        $where_conditions = ['p.status = :status'];
        $params = ['status' => 'active'];
        
        // Apply same filters as getAll method
        if (!empty($filters['category'])) {
            $where_conditions[] = 'c.name = :category';
            $params['category'] = $filters['category'];
        }
        
        if (!empty($filters['search'])) {
            $where_conditions[] = '(p.name LIKE :search OR p.description LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }
        
        if (!empty($filters['min_price'])) {
            $where_conditions[] = 'COALESCE(p.sale_price, p.price) >= :min_price';
            $params['min_price'] = $filters['min_price'];
        }
        
        if (!empty($filters['max_price'])) {
            $where_conditions[] = 'COALESCE(p.sale_price, p.price) <= :max_price';
            $params['max_price'] = $filters['max_price'];
        }
        
        if (isset($filters['featured'])) {
            $where_conditions[] = 'p.featured = 1';
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT COUNT(*) as total 
                FROM " . $this->table . " p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE {$where_clause}";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch();
        return $result['total'];
    }
    
    /**
     * Upload product image
     */
    public function uploadImage($file, $type = 'main') {
        $upload_result = $this->handleFileUpload($file, 'products');
        
        if ($upload_result['success']) {
            return $upload_result;
        }
        
        return $upload_result;
    }
    
    /**
     * Get price for display (sale price if available, otherwise regular price)
     */
    public function getDisplayPrice($product) {
        return $product['sale_price'] ?: $product['price'];
    }
    
    /**
     * Check if product is on sale
     */
    public function isOnSale($product) {
        return !empty($product['sale_price']) && $product['sale_price'] < $product['price'];
    }
    
    /**
     * Get discount percentage
     */
    public function getDiscountPercentage($product) {
        if ($this->isOnSale($product)) {
            $savings = $product['price'] - $product['sale_price'];
            return round(($savings / $product['price']) * 100);
        }
        return 0;
    }
    
    /**
     * Private helper methods
     */
    private function fillProperties($product) {
        $this->id = $product['id'];
        $this->name = $product['name'];
        $this->description = $product['description'];
        $this->short_description = $product['short_description'] ?? '';
        $this->price = $product['price'];
        $this->sale_price = $product['sale_price'];
        $this->sku = $product['sku'];
        $this->stock_quantity = $product['stock_quantity'];
        $this->main_image = $product['main_image'];
        $this->gallery_images = $product['gallery_images'];
        $this->category_id = $product['category_id'];
        $this->weight = $product['weight'];
        $this->dimensions = $product['dimensions'];
        $this->status = $product['status'];
        $this->featured = $product['featured'];
        $this->created_at = $product['created_at'];
        $this->updated_at = $product['updated_at'] ?? null;
    }
    
    private function handleFileUpload($file, $folder) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload error'];
        }
        
        if ($file['size'] > MAX_FILE_SIZE) {
            return ['success' => false, 'message' => 'File too large'];
        }
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, ALLOWED_IMAGE_TYPES)) {
            return ['success' => false, 'message' => 'Invalid file type'];
        }
        
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $destination = UPLOAD_DIR . $folder . '/' . $filename;
        
        if (!is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0755, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return [
                'success' => true,
                'filename' => $filename,
                'path' => $destination,
                'url' => $destination
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to save file'];
    }
}
?>