<?php
// Simple test - no Laravel, just PHP
?>
<!DOCTYPE html>
<html>
<head>
    <title>Geidea Test</title>
</head>
<body>
    <h1>Geidea Payment Test Page</h1>
    <p>If you see this, the route is working!</p>
    <p>Session ID: <?php echo htmlspecialchars($_GET['session_id'] ?? 'Not provided'); ?></p>
    <p>Merchant Key: <?php echo htmlspecialchars($_GET['merchant_key'] ?? 'Not provided'); ?></p>
    <p>Order ID: <?php echo htmlspecialchars($_GET['order_id'] ?? 'Not provided'); ?></p>
</body>
</html>
