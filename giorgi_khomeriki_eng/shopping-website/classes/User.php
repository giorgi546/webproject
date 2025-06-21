<?php
// classes/User.php - User management class with OOP principles

class User {
    private $db;
    private $table = 'users';
    
    // User properties
    public $id;
    public $email;
    public $password_hash;
    public $first_name;
    public $last_name;
    public $phone;
    public $role;
    public $email_verified;
    public $verification_token;
    public $profile_image;
    public $created_at;
    
    public function __construct($database) {
        $this->db = $database;
    }
    
    // Register new user
    public function register($data) {
        // Validate input
        if (!$this->validateRegistrationData($data)) {
            return false;
        }
        
        // Check if email already exists
        if ($this->emailExists($data['email'])) {
            $_SESSION['error'] = "Email already exists";
            return false;
        }
        
        // Hash password
        $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Generate verification token
        $verification_token = bin2hex(random_bytes(32));
        
        $sql = "INSERT INTO " . $this->table . " 
                (email, password_hash, first_name, last_name, phone, verification_token) 
                VALUES (?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([
            $data['email'],
            $password_hash,
            $data['first_name'],
            $data['last_name'],
            $data['phone'] ?? null,
            $verification_token
        ])) {
            $this->id = $this->db->lastInsertId();
            
            // Send verification email
            $this->sendVerificationEmail($data['email'], $verification_token);
            
            $_SESSION['success'] = "Registration successful! Please check your email to verify your account.";
            return true;
        }
        
        $_SESSION['error'] = "Registration failed";
        return false;
    }
    
    // Login user
    public function login($email, $password, $remember = false) {
        $sql = "SELECT * FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            
            if (password_verify($password, $user['password_hash'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['logged_in'] = true;
                
                // Set remember me cookie if requested
                if ($remember) {
                    $token = bin2hex(random_bytes(32));
                    setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/'); // 30 days
                    
                    // Save token to database (you'd need to add this field)
                    $this->updateRememberToken($user['id'], $token);
                }
                
                // Update last login time
                $this->updateLastLogin($user['id']);
                
                return true;
            }
        }
        
        $_SESSION['error'] = "Invalid email or password";
        return false;
    }
    
    // Logout user
    public function logout() {
        // Clear remember me cookie
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Clear session
        session_destroy();
        return true;
    }
    
    // Check if user is logged in
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    // Check if user is admin
    public static function isAdmin() {
        return self::isLoggedIn() && $_SESSION['user_role'] === 'admin';
    }
    
    // Get current user info
    public static function getCurrentUser() {
        if (self::isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['user_email'],
                'role' => $_SESSION['user_role'],
                'name' => $_SESSION['user_name']
            ];
        }
        return null;
    }
    
    // Get user by ID
    public function getById($id) {
        $sql = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch();
            $this->fillProperties($user);
            return $user;
        }
        return false;
    }
    
    // Update user profile
    public function updateProfile($id, $data) {
        $sql = "UPDATE " . $this->table . " 
                SET first_name = ?, last_name = ?, phone = ?, updated_at = NOW() 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            $data['first_name'],
            $data['last_name'],
            $data['phone'],
            $id
        ]);
    }
    
    // Change password
    public function changePassword($id, $current_password, $new_password) {
        // Verify current password
        $user = $this->getById($id);
        if (!$user || !password_verify($current_password, $user['password_hash'])) {
            $_SESSION['error'] = "Current password is incorrect";
            return false;
        }
        
        $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
        
        $sql = "UPDATE " . $this->table . " SET password_hash = ? WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute([$new_hash, $id])) {
            $_SESSION['success'] = "Password changed successfully";
            return true;
        }
        
        $_SESSION['error'] = "Failed to change password";
        return false;
    }
    
    // Get all users (admin only)
    public function getAllUsers($limit = null, $offset = 0) {
        $sql = "SELECT id, email, first_name, last_name, role, email_verified, created_at 
                FROM " . $this->table . " ORDER BY created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$limit, $offset]);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        
        return $stmt->fetchAll();
    }
    
    // Verify email
    public function verifyEmail($token) {
        $sql = "UPDATE " . $this->table . " 
                SET email_verified = 1, verification_token = NULL 
                WHERE verification_token = ?";
        
        $stmt = $this->db->prepare($sql);
        if ($stmt->execute([$token]) && $stmt->rowCount() > 0) {
            $_SESSION['success'] = "Email verified successfully! You can now login.";
            return true;
        }
        
        $_SESSION['error'] = "Invalid verification token";
        return false;
    }
    
    // Upload profile image
    public function uploadProfileImage($id, $file) {
        $upload_result = $this->handleFileUpload($file, 'profile_image');
        
        if ($upload_result['success']) {
            $sql = "UPDATE " . $this->table . " SET profile_image = ? WHERE id = ?";
            $stmt = $this->db->prepare($sql);
            
            if ($stmt->execute([$upload_result['filename'], $id])) {
                return $upload_result;
            }
        }
        
        return $upload_result;
    }
    
    // Private helper methods
    private function emailExists($email) {
        $sql = "SELECT id FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        return $stmt->rowCount() > 0;
    }
    
    private function validateRegistrationData($data) {
        $errors = [];
        
        if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Valid email is required";
        }
        
        if (empty($data['password']) || strlen($data['password']) < PASSWORD_MIN_LENGTH) {
            $errors[] = "Password must be at least " . PASSWORD_MIN_LENGTH . " characters";
        }
        
        if (empty($data['first_name']) || empty($data['last_name'])) {
            $errors[] = "First name and last name are required";
        }
        
        if (!empty($errors)) {
            $_SESSION['error'] = implode(', ', $errors);
            return false;
        }
        
        return true;
    }
    
    private function fillProperties($user) {
        $this->id = $user['id'];
        $this->email = $user['email'];
        $this->first_name = $user['first_name'];
        $this->last_name = $user['last_name'];
        $this->phone = $user['phone'];
        $this->role = $user['role'];
        $this->email_verified = $user['email_verified'];
        $this->profile_image = $user['profile_image'];
        $this->created_at = $user['created_at'];
    }
    
    private function updateLastLogin($id) {
        $sql = "UPDATE " . $this->table . " SET updated_at = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
    }
    
    private function updateRememberToken($id, $token) {
        // You'd need to add remember_token field to users table
        // $sql = "UPDATE " . $this->table . " SET remember_token = ? WHERE id = ?";
        // $stmt = $this->db->prepare($sql);
        // $stmt->execute([$token, $id]);
    }
    
    private function sendVerificationEmail($email, $token) {
        // Basic email sending (you'd want to use PHPMailer for production)
        $verification_link = SITE_URL . "/verify.php?token=" . $token;
        $subject = "Verify your email - " . SITE_NAME;
        $message = "Click this link to verify your email: " . $verification_link;
        
        // For now, just log it (replace with actual email sending)
        error_log("Verification email for $email: $verification_link");
        
        // In production, use PHPMailer or similar
        // mail($email, $subject, $message);
    }
    
    private function handleFileUpload($file, $type) {
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
        
        $filename = uniqid() . '.' . $extension;
        $destination = UPLOAD_DIR . $type . '/' . $filename;
        
        if (!is_dir(dirname($destination))) {
            mkdir(dirname($destination), 0755, true);
        }
        
        if (move_uploaded_file($file['tmp_name'], $destination)) {
            return ['success' => true, 'filename' => $filename, 'path' => $destination];
        }
        
        return ['success' => false, 'message' => 'Failed to save file'];
    }
}
?>