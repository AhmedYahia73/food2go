<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://bcknd.food2go.online/captain/lists?branch_id=4&locale=en&module=take_away");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$out = curl_exec($ch);
curl_close($ch);
$json = json_decode($out, true);
echo json_encode(array_slice($json['favourite_products'] ?? [], 0, 1));
?>
