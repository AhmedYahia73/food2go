<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>فحص Reverb Real-Time</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pusher/8.3.0/pusher.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.1/dist/echo.iife.js"></script>
</head>
<body>

    <div style="text-align: center; margin-top: 50px; font-family: sans-serif;">
        <h1 style="color: #2d3748;">📡 صفحة فحص البث اللحظي (Reverb)</h1>
        <p style="color: #4a5568; font-size: 18px;">افتح الـ **Console (F12)** وراقب المخرجات عند إرسال طلب جديد!</p>
        
        <div id="status" style="display: inline-block; padding: 10px 20px; background: #feebc8; color: #c05621; border-radius: 5px; font-weight: bold;">
            جاري الاتصال بسيرفر Reverb...
        </div>
    </div>

    <script>
        // 1. إعداد واجهة Laravel Echo للربط مع سيرفر Reverb
        window.Echo = new window.Echo({
            broadcaster: 'reverb', // الأفضل استخدام 'reverb' مباشرة
            key: "6756764554", // المفتاح من ملف الـ .env الخاص بك
            wsHost: "anlatech.mazoom.online", // أو '127.0.0.1' إذا كنت تجرب محلياً
            wsPort: 8080,
            forceTLS: false, // لأنك تستخدم http وليس https
            enabledTransports: ['ws'],
            cluster: 'mt1' 
        });

        // تحديث واجهة الصفحة عند نجاح الاتصال
        window.Echo.connector.pusher.connection.bind('connected', function() {
            console.log('✅ Connected to Reverb Successfully on port 8080!');
        });

        // 2. الاستماع للقناة والحدث
        // تأكد من أن اسم الـ Channel هو 'newOrder' واسم الـ Event هو 'NewOrderEvent'
        window.Echo.channel('newOrder')
            .listen('.NewOrderEvent', (data) => {
                console.log('🎯 وصّلت نوتيفيكيشن جديدة لايف!!');
                console.log('📦 Data Received:', data);
                
                alert('تم استلام طلب جديد برقم: ' + data.order_id);
            });
    </script>
</body>
</html>