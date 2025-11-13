<?php
// FILE: classes/User.php

class User
{
    private $db;

    public function __construct()
    {
        $this->db = new Database();
    }

    // ============================================
    // REGISTER - FIXED VERSION
    // ============================================
    /**
     * Register user baru dengan phone support
     * FIXED: Menggunakan insert() method dan return array
     * 
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $fullName
     * @param string $phone (optional)
     * @return array ['success' => bool, 'message' => string, 'user_id' => int]
     */
    public function register($username, $email, $password, $fullName, $phone = '')
    {
        try {
            // Check if email already exists
            if ($this->emailExists($email)) {
                return [
                    'success' => false,
                    'message' => 'Email sudah terdaftar',
                    'user_id' => null
                ];
            }
            
            // Check if username already exists
            if ($this->usernameExists($username)) {
                return [
                    'success' => false,
                    'message' => 'Username sudah digunakan',
                    'user_id' => null
                ];
            }
            
            // Hash password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // Prepare data untuk insert
            $data = [
                'username' => $username,
                'email' => $email,
                'password' => $hashedPassword,
                'full_name' => $fullName,
                'role' => 'customer'
            ];
            
            // Tambahkan phone jika ada
            if (!empty($phone)) {
                $data['phone'] = $phone;
            }
            
            // Insert menggunakan method insert dari Database class
            $userId = $this->db->insert('users', $data);
            
            if ($userId > 0) {
                return [
                    'success' => true,
                    'message' => 'Registrasi berhasil',
                    'user_id' => $userId
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Gagal menyimpan data',
                    'user_id' => null
                ];
            }
            
        } catch (Exception $e) {
            error_log("Register Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Terjadi kesalahan sistem',
                'user_id' => null
            ];
        }
    }

    // ============================================
    // LOGIN
    // ============================================
    public function login($email, $password)
    {
        try {
            $user = $this->db->fetch(
                "SELECT id, username, email, full_name, role, profile_image, password FROM users WHERE email = ?",
                [$email]
            );

            if ($user && password_verify($password, $user['password'])) {
                unset($user['password']);
                return $user;
            }
            return false;
            
        } catch (Exception $e) {
            error_log("Login Error: " . $e->getMessage());
            return false;
        }
    }

    // ============================================
    // GET USER BY ID
    // ============================================
    public function getUserById($id)
    {
        try {
            return $this->db->fetch("SELECT * FROM users WHERE id = ?", [$id]);
        } catch (Exception $e) {
            error_log("Get User Error: " . $e->getMessage());
            return null;
        }
    }

    // ============================================
    // UPDATE PROFILE - FIXED
    // ============================================
    /**
     * Update profile user
     * FIXED: Menggunakan update() method yang benar
     */
    public function updateProfile($id, $fullName, $phone = '', $address = '')
    {
        try {
            $data = [
                'full_name' => $fullName
            ];
            
            if (!empty($phone)) {
                $data['phone'] = $phone;
            }
            
            if (!empty($address)) {
                $data['address'] = $address;
            }
            
            return $this->db->update('users', $data, 'id = ?', [$id]);
            
        } catch (Exception $e) {
            error_log("Update Profile Error: " . $e->getMessage());
            return false;
        }
    }

    // ============================================
    // CHECK EMAIL EXISTS
    // ============================================
    public function emailExists($email)
    {
        try {
            return $this->db->exists('users', 'email = ?', [$email]);
        } catch (Exception $e) {
            error_log("Email Check Error: " . $e->getMessage());
            return false;
        }
    }

    // ============================================
    // CHECK USERNAME EXISTS - NEW
    // ============================================
    public function usernameExists($username)
    {
        try {
            return $this->db->exists('users', 'username = ?', [$username]);
        } catch (Exception $e) {
            error_log("Username Check Error: " . $e->getMessage());
            return false;
        }
    }

    // ============================================
    // GET ALL USERS
    // ============================================
    public function getAllUsers()
    {
        try {
            $sql = "SELECT id, username, email, full_name, phone, role, created_at 
                    FROM users 
                    ORDER BY id DESC";
            return $this->db->fetchAll($sql);
        } catch (Exception $e) {
            error_log("Get All Users Error: " . $e->getMessage());
            return [];
        }
    }

    // ============================================
    // DELETE USER - FIXED
    // ============================================
    /**
     * Delete user
     * FIXED: Menggunakan delete() method yang benar
     */
    public function deleteUser($id)
    {
        try {
            // Prevent deletion of primary admin (id = 1)
            if ($id == 1) {
                error_log("Cannot delete primary admin");
                return false;
            }
            
            return $this->db->delete('users', 'id = ?', [$id]);
            
        } catch (Exception $e) {
            error_log("Delete User Error: " . $e->getMessage());
            return false;
        }
    }

    // ============================================
    // ADDITIONAL HELPFUL METHODS
    // ============================================

    /**
     * Change password
     */
    public function changePassword($userId, $oldPassword, $newPassword)
    {
        try {
            // Get current password
            $sql = "SELECT password FROM users WHERE id = ?";
            $user = $this->db->fetch($sql, [$userId]);
            
            if (!$user) {
                return false;
            }
            
            // Verify old password
            if (!password_verify($oldPassword, $user['password'])) {
                return false;
            }
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            return $this->db->update('users', ['password' => $hashedPassword], 'id = ?', [$userId]);
            
        } catch (Exception $e) {
            error_log("Change Password Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user by email
     */
    public function getUserByEmail($email)
    {
        try {
            $sql = "SELECT id, username, email, full_name, phone, address, role, created_at 
                    FROM users WHERE email = ?";
            return $this->db->fetch($sql, [$email]);
        } catch (Exception $e) {
            error_log("Get User By Email Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get user by username
     */
    public function getUserByUsername($username)
    {
        try {
            $sql = "SELECT id, username, email, full_name, phone, address, role, created_at 
                    FROM users WHERE username = ?";
            return $this->db->fetch($sql, [$username]);
        } catch (Exception $e) {
            error_log("Get User By Username Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update profile image
     */
    public function updateProfileImage($id, $imagePath)
    {
        try {
            return $this->db->update('users', ['profile_image' => $imagePath], 'id = ?', [$id]);
        } catch (Exception $e) {
            error_log("Update Profile Image Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if user is admin
     */
    public function isAdmin($userId)
    {
        try {
            $user = $this->getUserById($userId);
            return $user && $user['role'] === 'admin';
        } catch (Exception $e) {
            error_log("Check Admin Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get total users
     */
    public function getTotalUsers()
    {
        try {
            return $this->db->count('users');
        } catch (Exception $e) {
            error_log("Get Total Users Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get total customers
     */
    public function getTotalCustomers()
    {
        try {
            return $this->db->count('users', "role = 'customer'");
        } catch (Exception $e) {
            error_log("Get Total Customers Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Validate email format
     */
    public function validateEmail($email)
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate phone number (Indonesia format)
     */
    public function validatePhone($phone)
    {
        // Format: 08xxxxxxxxxx atau +628xxxxxxxxxx
        // Minimal 10 digit, maksimal 13 digit
        return preg_match('/^(\+62|62|0)[0-9]{9,12}$/', $phone);
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin($userId)
    {
        try {
            $sql = "UPDATE users SET last_login = NOW() WHERE id = ?";
            return $this->db->execute($sql, [$userId]);
        } catch (Exception $e) {
            error_log("Update Last Login Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Search users by keyword
     */
    public function searchUsers($keyword, $limit = 50)
    {
        try {
            $searchTerm = "%{$keyword}%";
            $sql = "SELECT id, username, email, full_name, phone, role, created_at 
                    FROM users 
                    WHERE username LIKE ? 
                       OR email LIKE ? 
                       OR full_name LIKE ? 
                    ORDER BY created_at DESC 
                    LIMIT ?";
            return $this->db->fetchAll($sql, [$searchTerm, $searchTerm, $searchTerm, $limit]);
        } catch (Exception $e) {
            error_log("Search Users Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get users by role
     */
    public function getUsersByRole($role)
    {
        try {
            $sql = "SELECT id, username, email, full_name, phone, created_at 
                    FROM users 
                    WHERE role = ? 
                    ORDER BY created_at DESC";
            return $this->db->fetchAll($sql, [$role]);
        } catch (Exception $e) {
            error_log("Get Users By Role Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent users
     */
    public function getRecentUsers($limit = 10)
    {
        try {
            $sql = "SELECT id, username, email, full_name, role, created_at 
                    FROM users 
                    ORDER BY created_at DESC 
                    LIMIT ?";
            return $this->db->fetchAll($sql, [$limit]);
        } catch (Exception $e) {
            error_log("Get Recent Users Error: " . $e->getMessage());
            return [];
        }
    }
}