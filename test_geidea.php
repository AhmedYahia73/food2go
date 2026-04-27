<?php
// Test file to check if Geidea page is accessible
echo "Geidea Test Page - If you see this, the route is working!";
echo "<br>";
echo "Session ID: " . ($_GET['session_id'] ?? 'Not provided');
echo "<br>";
echo "Merchant Key: " . ($_GET['merchant_key'] ?? 'Not provided');
echo "<br>";
echo "Order ID: " . ($_GET['order_id'] ?? 'Not provided');
