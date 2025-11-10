<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Socket</title>
    @vite('resources/js/app.js') {{-- مهم جداً يكون فوق أي كود JS --}}
</head>
<body>
   
</body>

<script>
    const listenOrder = () => {
        if (window.Echo) {
            window.Echo.channel('new_order')
                .listen('OrderEvent', (e) => {
                    console.log("📦 New Order Received:", e);
                });
        } else {
            console.log('⏳ Waiting for Echo to load...');
            setTimeout(listenOrder, 500); // إعادة التجربة بعد نصف ثانية
        }
    };

    listenOrder();
</script>
</html>
