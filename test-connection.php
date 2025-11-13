<?php

echo "=== BOOKSTORE DATABASE CONNECTION TEST ===\n\n";

// Step 1: Check config file
echo "STEP 1: Checking config file...\n";
if (file_exists('config/config.php')) {
    echo "✓ config/config.php found\n";
    require_once 'config/config.php';
    
    echo "  - DB_HOST: " . DB_HOST . "\n";
    echo "  - DB_USER: " . DB_USER . "\n";
    echo "  - DB_PASS: " . (empty(DB_PASS) ? "(empty/default)" : "***") . "\n";
    echo "  - DB_NAME: " . DB_NAME . "\n";
    echo "  - SITE_URL: " . SITE_URL . "\n\n";
} else {
    die("✗ config/config.php NOT FOUND\n");
}

// Step 2: Check if MySQL extension is installed
echo "STEP 2: Checking MySQLi extension...\n";
if (extension_loaded('mysqli')) {
    echo "✓ MySQLi extension is loaded\n\n";
} else {
    die("✗ MySQLi extension NOT loaded. Enable it in php.ini\n");
}

// Step 3: Try to connect to MySQL
echo "STEP 3: Attempting database connection...\n";
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    echo "✗ Connection FAILED!\n";
    echo "  Error: " . $conn->connect_error . "\n";
    echo "  Error Code: " . $conn->connect_errno . "\n\n";
    
    echo "POSSIBLE SOLUTIONS:\n";
    echo "1. Make sure MySQL/MariaDB service is running\n";
    echo "2. Check if credentials in config.php are correct\n";
    echo "3. Make sure database 'bookstore_db' exists\n";
    echo "4. Try connecting with default credentials:\n";
    echo "   - Host: localhost\n";
    echo "   - User: root\n";
    echo "   - Password: (empty)\n";
    echo "   - Database: bookstore_db\n";
    die();
} else {
    echo "✓ Connection SUCCESSFUL!\n\n";
}

// Step 4: Check database exists
echo "STEP 4: Checking database...\n";
$result = $conn->query("SELECT DATABASE()");
$row = $result->fetch_row();
echo "✓ Current database: " . $row[0] . "\n\n";

// Step 5: Check tables
echo "STEP 5: Checking tables...\n";
$tables = ['users', 'categories', 'books', 'cart', 'orders', 'order_items', 'reviews', 'wishlist'];
$missing_tables = [];

foreach ($tables as $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        echo "✓ Table '$table' exists\n";
    } else {
        echo "✗ Table '$table' NOT FOUND\n";
        $missing_tables[] = $table;
    }
}

if (!empty($missing_tables)) {
    echo "\n⚠ MISSING TABLES: " . implode(', ', $missing_tables) . "\n";
    echo "  → Need to import database.sql\n";
} else {
    echo "\n✓ All tables exist!\n\n";
}

// Step 6: Check admin user
echo "STEP 6: Checking admin user...\n";
$result = $conn->query("SELECT id, username, email, role FROM users WHERE role = 'admin'");

if ($result->num_rows > 0) {
    $admin = $result->fetch_assoc();
    echo "✓ Admin user found!\n";
    echo "  - ID: " . $admin['id'] . "\n";
    echo "  - Username: " . $admin['username'] . "\n";
    echo "  - Email: " . $admin['email'] . "\n";
    echo "  - Role: " . $admin['role'] . "\n\n";
} else {
    echo "✗ Admin user NOT found!\n";
    echo "  → Need to import database or create admin manually\n\n";
}

// Step 7: Check all users
echo "STEP 7: All users in database:\n";
$result = $conn->query("SELECT id, username, email, role FROM users");
echo "Total users: " . $result->num_rows . "\n";
while ($row = $result->fetch_assoc()) {
    echo "  - " . $row['email'] . " (" . $row['role'] . ")\n";
}

// Step 8: Check books
echo "\nSTEP 8: Sample books:\n";
$result = $conn->query("SELECT COUNT(*) as total FROM books");
$row = $result->fetch_assoc();
echo "Total books: " . $row['total'] . "\n";

// Step 9: Check categories
echo "\nSTEP 9: Categories:\n";
$result = $conn->query("SELECT COUNT(*) as total FROM categories");
$row = $result->fetch_assoc();
echo "Total categories: " . $row['total'] . "\n";

// Final summary
echo "\n=== SUMMARY ===\n";
echo "✓ Database connection: OK\n";
echo "✓ All required tables: " . (empty($missing_tables) ? "OK" : "MISSING") . "\n";
echo "✓ Admin user: " . ($conn->query("SELECT id FROM users WHERE role='admin'")->num_rows > 0 ? "EXISTS" : "NOT FOUND") . "\n";

$book_count = $conn->query("SELECT COUNT(*) as c FROM books")->fetch_assoc()['c'];
echo "✓ Sample data: " . $book_count . " books\n";

$cat_count = $conn->query("SELECT COUNT(*) as c FROM categories")->fetch_assoc()['c'];
echo "✓ Categories: " . $cat_count . " categories\n";

$conn->close();
echo "\n✓ Connection test completed! Database is ready to use.\n";
echo "\nYou can now access:\n";
echo "- Admin Login: http://localhost/bookstore/public/login.php\n";
echo "  Email: admin@bookstore.com\n";
echo "  Password: admin123456\n";
?>