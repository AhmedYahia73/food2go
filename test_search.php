<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://bcknd.food2go.online/captain/search_products?branch_id=4&locale=ar&module=take_away&search=".urlencode("سن"));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$out = curl_exec($ch);
curl_close($ch);
echo substr($out, 0, 1000);
?>
