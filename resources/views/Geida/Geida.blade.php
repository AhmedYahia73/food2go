<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>إتمام الدفع</title>
    <style>
        body {
            margin: 0; min-height: 100vh;
            display: flex; align-items: center;
            justify-content: center; background: #f5f5f5;
        }
        #geidea-checkout { width: 100%; max-width: 500px; min-height: 400px; }
    </style>
</head>
<body>
    <div id="geidea-checkout"></div>

    <script>
        const params = new URLSearchParams(window.location.search);

        // ✅ نفس أسماء الـ params في الـ URL
        const sessionId   = params.get('session_id');
        const merchantKey = params.get('merchant_key');

        // ✅ تأكد إنهم موجودين
        console.log('sessionId:', sessionId);
        console.log('merchantKey:', merchantKey);

        if (!sessionId || !merchantKey) {
            document.body.innerHTML = '<h2>بيانات الدفع غير صحيحة</h2>';
        } else {
            var script    = document.createElement('script');
            script.src    = 'https://www.merchant.geidea.net/hpp/geideaCheckout.min.js';
            script.onload = function () {
                var checkout = new GeideaCheckout({
                    sessionId:   sessionId,
                    merchantKey: merchantKey,
                    containerId: "geidea-checkout",
                    onSuccess: function (data) {
                        window.location.href = "/orders/order_tracking";
                    },
                    onError: function (data) {
                        console.log('Error:', data);
                        window.location.href = "/payment/failed";
                    },
                    onCancel: function () {
                        window.history.back();
                    }
                });
                checkout.startPayment();
            };
            document.head.appendChild(script);
        }
    </script>
</body>
</html>