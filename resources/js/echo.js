import Echo from 'laravel-echo';
 

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.REVERB_APP_KEY,
    wsHost: import.meta.env.REVERB_HOST,
    wsPort: import.meta.env.REVERB_PORT,
    forceTLS: false,
    enabledTransports: ['ws'],
});
