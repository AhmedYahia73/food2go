<!-- <!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reverb Test</title> -->
    <!-- أولاً: مكتبة Pusher -->
<!-- <script src="https://cdn.jsdelivr.net/npm/pusher-js@7.2.0/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.js"></script>
  -->
<!-- </head>
<body>
    <h1>Reverb Test Page</h1>  -->
<!-- 
<script>
const echo = new Echo({
    broadcaster: 'pusher',
    key: 'foo2go123', 
    wsHost: 'bcknd.food2go.online',
    wsPort: 443, // إذا كان اتصال WSS/HTTPS عبر Proxy
    forceTLS: true,
    // wssPort: 443, // يمكنك أيضاً تجربتها
    disableStats: true,
    enabledTransports: ['ws', 'wss'],
});

echo.channel('print_order')
    .listen('.PrintOrder', (data) => {
        console.log("📡 Received:", data);
    });
</script> -->
<!-- 
</body>
</html> -->
<!-- أولاً: socket.io-client -->
<script src="https://cdn.jsdelivr.net/npm/socket.io-client@4.7.2/dist/socket.io.min.js"></script>

<!-- ثانياً: laravel-echo -->
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.11.0/dist/echo.iife.js"></script>

<script>
    // استخدم window.Echo
    const echo = new window.Echo({
        broadcaster: 'socket.io',
        host: 'http://127.0.0.1:6001'
    });

    echo.channel('print_order')
        .listen('.printed', (e) => {
            console.log('New Order:', e.order);
        });
</script>

