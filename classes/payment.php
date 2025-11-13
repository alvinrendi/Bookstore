<?php
// classes/Payment.php

class Payment {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Buat pembayaran baru dari order
     */
    public function createPayment($orderId, $orderNumber, $userId, $paymentMethod, $paymentAmount) {
        try {
            // Set expired date (24 jam dari sekarang)
            $expiredDate = date('Y-m-d H:i:s', strtotime('+24 hours'));
            
            // Generate unique payment ID
            $transactionId = $this->generateTransactionId($paymentMethod);
            
            $stmt = $this->db->prepare("
                INSERT INTO payments 
                (order_id, order_number, user_id, payment_method, payment_amount, 
                 payment_status, transaction_id, expired_date) 
                VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)
            ");
            
            $stmt->bind_param(
                "isissss", 
                $orderId, 
                $orderNumber, 
                $userId, 
                $paymentMethod, 
                $paymentAmount,
                $transactionId,
                $expiredDate
            );
            
            if ($stmt->execute()) {
                $paymentId = $this->db->insert_id;
                
                // Log pembayaran
                $this->logPayment($paymentId, 'pending', 'Pembayaran dibuat');
                
                return [
                    'success' => true,
                    'payment_id' => $paymentId,
                    'transaction_id' => $transactionId,
                    'expired_date' => $expiredDate
                ];
            }
            
            return ['success' => false, 'message' => 'Gagal membuat pembayaran'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Generate transaction ID unik
     */
    private function generateTransactionId($method) {
        $prefix = strtoupper(substr($method, 0, 3));
        $timestamp = time();
        $random = mt_rand(1000, 9999);
        return $prefix . $timestamp . $random;
    }
    
    /**
     * Get payment by order number
     */
    public function getPaymentByOrderNumber($orderNumber, $userId = null) {
        $sql = "SELECT p.*, o.total_amount as order_total 
                FROM payments p 
                JOIN orders o ON p.order_id = o.id 
                WHERE p.order_number = ?";
        
        if ($userId) {
            $sql .= " AND p.user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("si", $orderNumber, $userId);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("s", $orderNumber);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Get payment by ID
     */
    public function getPaymentById($paymentId, $userId = null) {
        $sql = "SELECT * FROM payments WHERE id = ?";
        
        if ($userId) {
            $sql .= " AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("ii", $paymentId, $userId);
        } else {
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param("i", $paymentId);
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Get bank accounts
     */
    public function getBankAccounts() {
        $stmt = $this->db->prepare("SELECT * FROM bank_accounts WHERE is_active = 1");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get e-wallet accounts
     */
    public function getEwalletAccounts() {
        $stmt = $this->db->prepare("SELECT * FROM ewallet_accounts WHERE is_active = 1");
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get e-wallet by name
     */
    public function getEwalletByName($name) {
        $stmt = $this->db->prepare("SELECT * FROM ewallet_accounts WHERE ewallet_name = ? AND is_active = 1");
        $stmt->bind_param("s", $name);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }
    
    /**
     * Upload payment proof
     */
    public function uploadPaymentProof($paymentId, $file) {
        try {
            $uploadDir = '../uploads/payments/';
            
            // Create directory if not exists
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
            $maxSize = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($file['type'], $allowedTypes)) {
                return ['success' => false, 'message' => 'Format file tidak valid. Gunakan JPG, PNG, atau GIF'];
            }
            
            if ($file['size'] > $maxSize) {
                return ['success' => false, 'message' => 'Ukuran file terlalu besar. Maksimal 5MB'];
            }
            
            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $fileName = 'payment_' . $paymentId . '_' . time() . '.' . $extension;
            $filePath = $uploadDir . $fileName;
            
            if (move_uploaded_file($file['tmp_name'], $filePath)) {
                // Update payment record
                $stmt = $this->db->prepare("
                    UPDATE payments 
                    SET payment_proof = ?, 
                        payment_status = 'processing',
                        payment_date = NOW() 
                    WHERE id = ?
                ");
                $stmt->bind_param("si", $fileName, $paymentId);
                
                if ($stmt->execute()) {
                    $this->logPayment($paymentId, 'processing', 'Bukti pembayaran diupload');
                    return ['success' => true, 'filename' => $fileName];
                }
            }
            
            return ['success' => false, 'message' => 'Gagal upload file'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Confirm payment
     */
    public function confirmPayment($paymentId, $notes = '') {
        try {
            $stmt = $this->db->prepare("
                UPDATE payments 
                SET payment_status = 'completed',
                    confirmed_date = NOW(),
                    notes = ?
                WHERE id = ?
            ");
            $stmt->bind_param("si", $notes, $paymentId);
            
            if ($stmt->execute()) {
                // Update order status
                $payment = $this->getPaymentById($paymentId);
                if ($payment) {
                    $orderStmt = $this->db->prepare("
                        UPDATE orders 
                        SET payment_status = 'paid',
                            status = 'processing'
                        WHERE id = ?
                    ");
                    $orderStmt->bind_param("i", $payment['order_id']);
                    $orderStmt->execute();
                }
                
                $this->logPayment($paymentId, 'completed', 'Pembayaran dikonfirmasi: ' . $notes);
                return ['success' => true];
            }
            
            return ['success' => false, 'message' => 'Gagal konfirmasi pembayaran'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Cancel/reject payment
     */
    public function rejectPayment($paymentId, $reason) {
        try {
            $stmt = $this->db->prepare("
                UPDATE payments 
                SET payment_status = 'failed',
                    notes = ?
                WHERE id = ?
            ");
            $stmt->bind_param("si", $reason, $paymentId);
            
            if ($stmt->execute()) {
                $this->logPayment($paymentId, 'failed', 'Pembayaran ditolak: ' . $reason);
                return ['success' => true];
            }
            
            return ['success' => false, 'message' => 'Gagal menolak pembayaran'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Check expired payments
     */
    public function checkExpiredPayments() {
        try {
            $stmt = $this->db->prepare("
                UPDATE payments 
                SET payment_status = 'expired' 
                WHERE payment_status = 'pending' 
                AND expired_date < NOW()
            ");
            $stmt->execute();
            return $stmt->affected_rows;
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Log payment activity
     */
    private function logPayment($paymentId, $status, $message, $userId = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO payment_logs (payment_id, status, message, created_by) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("issi", $paymentId, $status, $message, $userId);
            $stmt->execute();
        } catch (Exception $e) {
            // Silent fail untuk logging
        }
    }
    
    /**
     * Get payment logs
     */
    public function getPaymentLogs($paymentId) {
        $stmt = $this->db->prepare("
            SELECT pl.*, u.username 
            FROM payment_logs pl 
            LEFT JOIN users u ON pl.created_by = u.id 
            WHERE pl.payment_id = ? 
            ORDER BY pl.created_at DESC
        ");
        $stmt->bind_param("i", $paymentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
    
    /**
     * Get payment statistics
     */
    public function getPaymentStats($userId = null) {
        $sql = "SELECT 
                    payment_status,
                    COUNT(*) as count,
                    SUM(payment_amount) as total
                FROM payments";
        
        if ($userId) {
            $sql .= " WHERE user_id = ?";
            $stmt = $this->db->prepare($sql . " GROUP BY payment_status");
            $stmt->bind_param("i", $userId);
        } else {
            $stmt = $this->db->prepare($sql . " GROUP BY payment_status");
        }
        
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }
}
?>