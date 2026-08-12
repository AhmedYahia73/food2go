<?php
$db = new PDO('sqlite:C:/xampp/htdocs/electronPOS/userData/cache/db.sqlite');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Get schema
$stmt = $db->query('PRAGMA table_info(cashier_men)');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

// We will also insert the user here
$username = 'ola';
$password = password_hash('123', PASSWORD_BCRYPT);
$branchId = 1; // Assuming branch 1 exists

echo "Hashed password: $password\n";
