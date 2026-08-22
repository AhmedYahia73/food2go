<?php
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://bcknd.food2go.online/captain/search_products?branch_id=4&locale=ar&module=take_away&search=".urlencode("بيتزا"));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
$out = curl_exec($ch);
curl_close($ch);
if (strpos($out, '<title>Laravel</title>') !== false) {
    preg_match('/<div class="exception_message">(.*?)<\/div>/is', $out, $matches2);
    echo "Exception: " . strip_tags($matches2[1] ?? 'None') . "\n";
} else {
    echo substr($out, 0, 1000);
}
?>
