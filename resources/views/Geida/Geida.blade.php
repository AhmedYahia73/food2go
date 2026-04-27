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
            font-family: Arial, sans-serif;
        }
        #geidea-checkout { width: 100%; max-width: 500px; min-height: 400px; }
        .loading { text-align: center; padding: 20px; }
        .error { color: red; text-align: center; padding: 20px; }
    </style>
</head>
<body>
    <div id="container">
        <div class="loading" id="loading">جاري تحميل صفحة الدفع...</div>
        <div id="geidea-checkout"></div>
        <div class="error" id="error" style="display:none;"></div>
    </div>

    <script>
        const params = new URLSearchParams(window.location.search);
        const sessionId   = params.get('session_id');
        const merchantKey = params.get('merchant_key');

        console.log('Session ID:', sessionId);
        console.log('Merchant Key:', merchantKey);

        if (!sessionId || !merchantKey) {
            document.getElementById('loading').style.display = 'none';
            document.getElementById('error').style.display = 'block';
            document.getElementById('error').innerHTML = '<h2>بيانات الدفع غير صحيحة</h2><p>Session ID: ' + sessionId + '</p><p>Merchant Key: ' + merchantKey + '</p>';
        } else {
            // Set timeout for script loading
            const scriptTimeout = setTimeout(function() {
                document.getElementById('loading').style.display = 'none';
                document.getElementById('error').style.display = 'block';
                document.getElementById('error').innerHTML = '<h2>فشل تحميل صفحة الدفع</h2><p>يرجى المحاولة مرة أخرى</p>';
            }, 15000); // 15 seconds timeout

            var script = document.createElement('script');
            script.src = 'https://www.merchant.geidea.net/hpp/geideaCheckout.min.js';
            
            script.onerror = function() {
                clearTimeout(scriptTimeout);
                document.getElementById('loading').style.display = 'none';
                document.getElementById('error').style.display = 'block';
                document.getElementById('error').innerHTML = '<h2>فشل تحميل Geidea</h2><p>تأكد من الاتصال بالإنترنت</p>';
            };
            
            script.onload = function() {
                clearTimeout(scriptTimeout);
                document.getElementById('loading').style.display = 'none';
                
                try {
                    var checkout = new GeideaCheckout({
                        sessionId: sessionId,
                        merchantKey: merchantKey,
                        containerId: "geidea-checkout",
                        onSuccess: function(data) {
                            console.log('Payment Success:', data);
                            window.location.href = "{{ env('WEB_LINK') }}/orders/order_tracking";
                        },
                        onError: function(data) {
                            console.log('Payment Error:', data);
                            document.getElementById('error').style.display = 'block';
                            document.getElementById('error').innerHTML = '<h2>فشل الدفع</h2><p>' + JSON.stringify(data) + '</p>';
                        },
                        onCancel: function() {
                            console.log('Payment Cancelled');
                            window.history.back();
                        }
                    });
                    checkout.startPayment();
                } catch(e) {
                    console.error('Geidea Error:', e);
                    document.getElementById('error').style.display = 'block';
                    document.getElementById('error').innerHTML = '<h2>خطأ في تهيئة الدفع</h2><p>' + e.message + '</p>';
                }
            };
            
            document.head.appendChild(script);
        }
    </script>
</body>
</html>