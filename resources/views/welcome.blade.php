<script src="https://cdn.jsdelivr.net/npm/laravel-echo@latest/dist/echo.iife.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pusher-js@latest/dist/web/pusher.min.js"></script>

<script>
const echo = new Echo({
    broadcaster: 'reverb',
    key: 'foo2go123', // نفس الموجود في .env
    wsHost: window.location.hostname,
    wsPort: 6001,
    forceTLS: false,
    disableStats: true,
});

echo.channel('print_order').listen('.print_order.printed', (data) => {
    console.log("Received:", data);
});
</script>
