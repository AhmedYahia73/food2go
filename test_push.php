<?php
$ch = curl_init('https://bcknd.food2go.online/api/sync/push');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Secret-Key: Food2go@Sync2024'
]);

$data = [
    'clientId' => 'test_client',
    'secret_key' => 'Food2go@Sync2024',
    'changes' => [
        [
            'id' => 'change_127',
            'table_name' => 'orders',
            'record_id' => '999993',
            'op' => 'insert',
            'payload' => json_encode([
                'id' => '999993',
                'amount' => 150,
                'order_status' => 'pending',
                'order_type' => 'take_away',
                'delivery_status' => 'watting',
                'branch_id' => null
            ]),
            'created_at' => '2026-08-15 12:00:00'
        ]
    ]
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$res = curl_exec($ch);
echo "Response:\n";
var_dump($res);
