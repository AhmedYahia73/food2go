<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=lamada_food2go;port=3306', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT id, order_number, created_at FROM orders ORDER BY id DESC LIMIT 5");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "Latest 5 orders:\n";
    foreach ($orders as $order) {
        echo "ID: {$order['id']} - Order Number: {$order['order_number']} - Created At: {$order['created_at']}\n";
    }

    $stmt2 = $pdo->query("SELECT id FROM orders WHERE id > 1000000 ORDER BY id ASC LIMIT 1");
    $huge = $stmt2->fetch(PDO::FETCH_ASSOC);
    if ($huge) {
        echo "\nFirst Huge ID: {$huge['id']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
