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
            'payload' => '{"id":"178678837738113020","date":"2026-08-15","user_id":null,"branch_id":4.0,"amount":320.0,"order_status":"pending","order_type":"take_away","payment_status":"unpaid","total_tax":0.0,"total_discount":0.0,"pos":1.0,"delivery_id":null,"address_id":null,"notes":"","coupon_discount":0,"order_number":null,"payment_method_id":null,"receipt":null,"status":1.0,"points":0,"order_details":"[{\"product_id\":\"110\",\"count\":\"1\",\"note\":\"\",\"price\":\"150.00\",\"variation\":[],\"addons\":[],\"extra_id\":[],\"exclude_id\":[]},{\"product_id\":\"111\",\"count\":\"1\",\"note\":\"\",\"price\":\"170.00\",\"variation\":[],\"addons\":[],\"extra_id\":[],\"exclude_id\":[]}]","rejected_reason":null,"transaction_id":null,"customer_cancel_reason":null,"admin_cancel_reason":null,"captain_id":null,"table_id":null,"cashier_man_id":3.0,"cashier_id":null,"shift":1439.0,"admin_id":null,"operation_status":"pending","sechedule_slot_id":null,"canceled_noti":null,"customer_id":null,"deleted_at":null,"source":null,"take_away_status":"preparing","delivery_status":"watting","order_active":1.0,"coupon_id":null,"from_table_order":null,"due":null,"dicount_id":null,"preparation_read_status":null,"due_from_delivery":null,"module_id":null,"void_financial_id":null,"is_void":null,"rate":null,"free_discount":0.0,"due_module":0.0,"service_fees":0.0,"prepare_order":1.0}',
            'created_at' => '2026-08-15 12:00:00'
        ]
    ]
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
$res = curl_exec($ch);
echo "Response:\n";
var_dump($res);
