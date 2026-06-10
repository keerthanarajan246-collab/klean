<?php
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_USER', 'postgres');
define('DB_PASS', 'postgres123');
define('DB_NAME', 'klean_db');

try {
    $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    $pdo->exec("UPDATE courses SET price = 1499.00, discount_price = 599.00 WHERE id = 1");
    $pdo->exec("UPDATE courses SET price = 1999.00, discount_price = 699.00 WHERE id = 2");
    $pdo->exec("UPDATE courses SET price = 1299.00, discount_price = 499.00 WHERE id = 3");
    $pdo->exec("UPDATE courses SET price = 1099.00, discount_price = 399.00 WHERE id = 4");
    $pdo->exec("UPDATE courses SET price = 1499.00, discount_price = 599.00 WHERE id = 5");
    $pdo->exec("UPDATE courses SET price = 999.00, discount_price = 349.00 WHERE id = 6");
    $pdo->exec("UPDATE courses SET price = 1199.00, discount_price = 449.00 WHERE id = 7");
    $pdo->exec("UPDATE courses SET price = 1099.00, discount_price = 399.00 WHERE id = 8");
    
    echo "Prices updated successfully.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
