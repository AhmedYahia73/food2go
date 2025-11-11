<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reverb Test</title>
    <!-- أولاً: مكتبة Pusher -->
<script src="https://cdn.jsdelivr.net/npm/pusher-js@7.2.0/dist/web/pusher.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.15.0/dist/echo.iife.js"></script>
 
</head>
<body>
    <h1>Reverb Test Page</h1> 

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
    .listen('.print_order.printed', (data) => {
        console.log("📡 Received:", data);
    });
</script>

</body>
</html>
