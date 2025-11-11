<!-- لازم Pusher الأول -->
<script src="https://cdn.jsdelivr.net/npm/pusher-js@latest/dist/web/pusher.min.js"></script>

<!-- بعد كده Laravel Echo -->
<script src="https://cdn.jsdelivr.net/npm/laravel-echo@latest/dist/echo.iife.js"></script>

<script>
const echo = new Echo({
    broadcaster: 'reverb',
    key: 'foo2go123', // نفس REVERB_APP_KEY من .env
    wsHost: window.location.hostname,
    wsPort: 6001,       // أو 443 لو بتستخدم https
    forceTLS: false,    // true لو الموقع عندك https
    disableStats: true,
});

echo.channel('print_order')
    .listen('.print_order.printed', (data) => {
        console.log("✅ Received:", data);
    });
</script>
