<?php
// classes/Order.php
class Order
{
    private $db;

    public function __construct()
    {
        try {
            $this->db = new Database();
        } catch (Exception $e) {
            error_log("Database connection error in Order class: " . $e->getMessage());
            throw new Exception("Tidak dapat terhubung ke database. Pastikan MySQL/XAMPP sudah berjalan!");
        }
    }

    /**
     * Buat pesanan baru dari keranjang
     */
    public function createOrderFromCart($userId, $paymentMethod, $shippingAddress, $shippingCost = 0)
    {
        try {
            // Generate order number
            $orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid()), 0, 6));

            // Ambil data cart dengan koneksi yang fresh
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT c.*, b.title, b.price, b.discount_percent, b.stock 
                FROM cart c 
                JOIN books b ON c.book_id = b.id 
                WHERE c.user_id = ?
            ");

            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }

            $stmt->bind_param("i", $userId);

            if (!$stmt->execute()) {
                throw new Exception("Failed to execute cart query: " . $stmt->error);
            }

            $result = $stmt->get_result();
            $cartItems = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            if (empty($cartItems)) {
                return ['success' => false, 'message' => 'Keranjang kosong'];
            }

            // Hitung total
            $totalAmount = 0;
            foreach ($cartItems as $item) {
                $discount = isset($item['discount_percent']) ? (int) $item['discount_percent'] : 0;
                $discount = max(0, min(100, $discount)); // Ensure 0-100

                $finalPrice = $item['price'] * (100 - $discount) / 100;
                $totalAmount += $finalPrice * $item['quantity'];

                // Cek stok
                if ($item['stock'] < $item['quantity']) {
                    return ['success' => false, 'message' => 'Stok buku "' . $item['title'] . '" tidak mencukupi'];
                }
            }

            $finalAmount = $totalAmount + $shippingCost;

            // Begin transaction untuk memastikan atomicity
            $conn->begin_transaction();

            try {
                // Insert order
                $stmt = $conn->prepare("
                    INSERT INTO orders (user_id, order_number, total_amount, shipping_cost, final_amount, 
                                       payment_method, shipping_address, status, created_at) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");

                if (!$stmt) {
                    throw new Exception("Failed to prepare order insert: " . $conn->error);
                }

                $stmt->bind_param(
                    "isdddss",
                    $userId,
                    $orderNumber,
                    $totalAmount,
                    $shippingCost,
                    $finalAmount,
                    $paymentMethod,
                    $shippingAddress
                );

                if (!$stmt->execute()) {
                    throw new Exception("Failed to insert order: " . $stmt->error);
                }

                $orderId = $stmt->insert_id;
                $stmt->close();

                if (!$orderId) {
                    throw new Exception("Failed to get order ID");
                }

                // Insert order items & update stock
                foreach ($cartItems as $item) {
                    $discount = isset($item['discount_percent']) ? (int) $item['discount_percent'] : 0;
                    $discount = max(0, min(100, $discount));

                    // Insert order item (tanpa discount_percent jika kolom tidak ada)
                    // Cek apakah kolom discount_percent ada
                    $checkColumn = $conn->query("SHOW COLUMNS FROM order_items LIKE 'discount_percent'");

                    if ($checkColumn && $checkColumn->num_rows > 0) {
                        // Jika kolom ada, insert dengan discount_percent
                        $stmt = $conn->prepare("
                            INSERT INTO order_items (order_id, book_id, quantity, price, discount_percent) 
                            VALUES (?, ?, ?, ?, ?)
                        ");

                        if (!$stmt) {
                            throw new Exception("Failed to prepare order item insert: " . $conn->error);
                        }

                        $stmt->bind_param(
                            "iiidi",
                            $orderId,
                            $item['book_id'],
                            $item['quantity'],
                            $item['price'],
                            $discount
                        );
                    } else {
                        // Jika kolom tidak ada, insert tanpa discount_percent
                        $stmt = $conn->prepare("
                            INSERT INTO order_items (order_id, book_id, quantity, price) 
                            VALUES (?, ?, ?, ?)
                        ");

                        if (!$stmt) {
                            throw new Exception("Failed to prepare order item insert: " . $conn->error);
                        }

                        $stmt->bind_param(
                            "iiid",
                            $orderId,
                            $item['book_id'],
                            $item['quantity'],
                            $item['price']
                        );
                    }

                    if (!$stmt->execute()) {
                        throw new Exception("Failed to insert order item: " . $stmt->error);
                    }
                    $stmt->close();

                    // Update stock
                    $stmt = $conn->prepare("UPDATE books SET stock = stock - ? WHERE id = ?");

                    if (!$stmt) {
                        throw new Exception("Failed to prepare stock update: " . $conn->error);
                    }

                    $stmt->bind_param("ii", $item['quantity'], $item['book_id']);

                    if (!$stmt->execute()) {
                        throw new Exception("Failed to update stock: " . $stmt->error);
                    }
                    $stmt->close();
                }

                // Hapus cart
                $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ?");

                if (!$stmt) {
                    throw new Exception("Failed to prepare cart delete: " . $conn->error);
                }

                $stmt->bind_param("i", $userId);

                if (!$stmt->execute()) {
                    throw new Exception("Failed to delete cart: " . $stmt->error);
                }
                $stmt->close();

                // Commit transaction
                $conn->commit();

                return [
                    'success' => true,
                    'message' => 'Pesanan berhasil dibuat',
                    'order_id' => $orderId,
                    'order_number' => $orderNumber
                ];

            } catch (Exception $e) {
                // Rollback jika ada error
                $conn->rollback();
                throw $e;
            }

        } catch (Exception $e) {
            error_log("Order creation error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Gagal membuat pesanan: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Ambil pesanan user
     */
    public function getUserOrders($userId)
    {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT o.*, u.full_name, u.email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                WHERE o.user_id = ? 
                ORDER BY o.created_at DESC
            ");

            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }

            $stmt->bind_param("i", $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            $orders = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return $orders;
        } catch (Exception $e) {
            error_log("Get user orders error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil detail pesanan
     */
    public function getOrderById($orderId, $userId = null)
    {
        try {
            $conn = $this->db->getConnection();

            if ($userId) {
                $stmt = $conn->prepare("
                    SELECT o.*, u.full_name, u.email, u.phone 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    WHERE o.id = ? AND o.user_id = ?
                ");
                $stmt->bind_param("ii", $orderId, $userId);
            } else {
                $stmt = $conn->prepare("
                    SELECT o.*, u.full_name, u.email, u.phone 
                    FROM orders o 
                    JOIN users u ON o.user_id = u.id 
                    WHERE o.id = ?
                ");
                $stmt->bind_param("i", $orderId);
            }

            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
            $stmt->close();

            return $order;
        } catch (Exception $e) {
            error_log("Get order by ID error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ambil items dari pesanan
     */
    public function getOrderItems($orderId)
    {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT oi.*, b.title, b.author, b.image 
                FROM order_items oi 
                JOIN books b ON oi.book_id = b.id 
                WHERE oi.order_id = ?
            ");

            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }

            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $result = $stmt->get_result();
            $items = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return $items;
        } catch (Exception $e) {
            error_log("Get order items error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil semua pesanan (admin)
     */
    public function getAllOrders($limit = 20, $offset = 0)
    {
        try {
            $conn = $this->db->getConnection();
            $stmt = $conn->prepare("
                SELECT o.*, u.full_name, u.email 
                FROM orders o 
                JOIN users u ON o.user_id = u.id 
                ORDER BY o.created_at DESC 
                LIMIT ? OFFSET ?
            ");

            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }

            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            $orders = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();

            return $orders;
        } catch (Exception $e) {
            error_log("Get all orders error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update status pesanan
     */
    public function updateOrderStatus($orderId, $status)
    {
        $allowedStatuses = ['pending', 'processing', 'shipped', 'delivered', 'completed', 'cancelled'];

        if (!in_array($status, $allowedStatuses)) {
            return false;
        }

        try {
            $conn = $this->db->getConnection();

            // Jika status dibatalkan, kembalikan stok
            if ($status === 'cancelled') {
                $items = $this->getOrderItems($orderId);
                foreach ($items as $item) {
                    $stmt = $conn->prepare("UPDATE books SET stock = stock + ? WHERE id = ?");
                    if ($stmt) {
                        $stmt->bind_param("ii", $item['quantity'], $item['book_id']);
                        $stmt->execute();
                        $stmt->close();
                    }
                }
            }

            $stmt = $conn->prepare("UPDATE orders SET status = ?, updated_at = NOW() WHERE id = ?");

            if (!$stmt) {
                throw new Exception("Failed to prepare statement: " . $conn->error);
            }

            $stmt->bind_param("si", $status, $orderId);
            $result = $stmt->execute();
            $stmt->close();

            return $result;
        } catch (Exception $e) {
            error_log("Update order status error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Batalkan pesanan (user)
     */
    public function cancelOrder($orderId, $userId)
    {
        try {
            // Cek apakah order milik user dan statusnya pending
            $order = $this->getOrderById($orderId, $userId);

            if (!$order) {
                return ['success' => false, 'message' => 'Pesanan tidak ditemukan'];
            }

            if ($order['status'] !== 'pending') {
                return ['success' => false, 'message' => 'Pesanan tidak dapat dibatalkan'];
            }

            // Update status dan kembalikan stok
            if ($this->updateOrderStatus($orderId, 'cancelled')) {
                return ['success' => true, 'message' => 'Pesanan berhasil dibatalkan'];
            }

            return ['success' => false, 'message' => 'Gagal membatalkan pesanan'];
        } catch (Exception $e) {
            error_log("Cancel order error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()];
        }
    }

    /**
     * Dapatkan statistik pesanan (admin)
     */
    public function getOrderStats()
    {
        try {
            $conn = $this->db->getConnection();
            $stats = [];

            // Total pesanan
            $result = $conn->query("SELECT COUNT(*) as total FROM orders");
            $stats['total_orders'] = $result ? $result->fetch_assoc()['total'] : 0;

            // Pesanan pending
            $result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status = 'pending'");
            $stats['pending_orders'] = $result ? $result->fetch_assoc()['total'] : 0;

            // Pesanan selesai
            $result = $conn->query("SELECT COUNT(*) as total FROM orders WHERE status IN ('completed', 'delivered')");
            $stats['completed_orders'] = $result ? $result->fetch_assoc()['total'] : 0;

            // Total pendapatan
            $result = $conn->query("SELECT SUM(final_amount) as total FROM orders WHERE status IN ('completed', 'delivered')");
            $stats['total_revenue'] = $result ? ($result->fetch_assoc()['total'] ?? 0) : 0;

            return $stats;
        } catch (Exception $e) {
            error_log("Get order stats error: " . $e->getMessage());
            return [
                'total_orders' => 0,
                'pending_orders' => 0,
                'completed_orders' => 0,
                'total_revenue' => 0
            ];
        }
    }
    public function getOrderByNumber($orderNumber, $userId = null)
    {
        try {
            $sql = "SELECT * FROM orders WHERE order_number = ?";

            if ($userId) {
                $sql .= " AND user_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("si", $orderNumber, $userId);
            } else {
                $stmt = $this->db->prepare($sql);
                $stmt->bind_param("s", $orderNumber);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_assoc();

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Get order with items by order number
     */
    public function getOrderWithItems($orderNumber, $userId = null)
    {
        try {
            // Get order data
            $order = $this->getOrderByNumber($orderNumber, $userId);

            if (!$order) {
                return null;
            }

            // Get order items
            $stmt = $this->db->prepare("
            SELECT oi.*, b.title, b.author, b.image 
            FROM order_items oi 
            LEFT JOIN books b ON oi.book_id = b.id 
            WHERE oi.order_id = ?
        ");
            $stmt->bind_param("i", $order['id']);
            $stmt->execute();
            $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            $order['items'] = $items;
            return $order;

        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Update order status
     */
    

    /**
     * Update payment status
     */
    public function updatePaymentStatus($orderId, $paymentStatus)
    {
        try {
            $validStatuses = ['unpaid', 'pending', 'paid', 'failed', 'refunded'];

            if (!in_array($paymentStatus, $validStatuses)) {
                return ['success' => false, 'message' => 'Payment status tidak valid'];
            }

            $stmt = $this->db->prepare("UPDATE orders SET payment_status = ? WHERE id = ?");
            $stmt->bind_param("si", $paymentStatus, $orderId);

            if ($stmt->execute()) {
                return ['success' => true];
            }

            return ['success' => false, 'message' => 'Gagal update payment status'];

        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Get user orders
     */
    

    /**
     * Get order statistics for user
     */
    public function getUserOrderStats($userId)
    {
        try {
            $stmt = $this->db->prepare("
            SELECT 
                COUNT(*) as total_orders,
                SUM(CASE WHEN status = 'delivered' THEN 1 ELSE 0 END) as completed_orders,
                SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_orders,
                SUM(CASE WHEN payment_status = 'paid' THEN final_amount ELSE 0 END) as total_spent
            FROM orders 
            WHERE user_id = ?
        ");
            $stmt->bind_param("i", $userId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();

        } catch (Exception $e) {
            return [
                'total_orders' => 0,
                'completed_orders' => 0,
                'cancelled_orders' => 0,
                'total_spent' => 0
            ];
        }
    }

    /**
     * Cancel order
     */
    

    /**
     * Restore stock when order is cancelled
     */
    private function restoreOrderStock($orderId)
    {
        try {
            // Get order items
            $stmt = $this->db->prepare("SELECT book_id, quantity FROM order_items WHERE order_id = ?");
            $stmt->bind_param("i", $orderId);
            $stmt->execute();
            $items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            // Restore stock for each item
            foreach ($items as $item) {
                $stmt = $this->db->prepare("UPDATE books SET stock = stock + ? WHERE id = ?");
                $stmt->bind_param("ii", $item['quantity'], $item['book_id']);
                $stmt->execute();
            }

            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Check if order exists and belongs to user
     */
    public function validateOrder($orderId, $userId)
    {
        try {
            $stmt = $this->db->prepare("SELECT id FROM orders WHERE id = ? AND user_id = ?");
            $stmt->bind_param("ii", $orderId, $userId);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->num_rows > 0;

        } catch (Exception $e) {
            return false;
        }
    }
}
