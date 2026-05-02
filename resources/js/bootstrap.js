import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Pusher = Pusher;

// ReverbConfig is injected by app.blade.php at runtime — avoids Vite build-time env issues
const cfg = window.ReverbConfig;

if (cfg?.key && cfg?.host) {
    window.Echo = new Echo({
        broadcaster:       'reverb',
        key:               cfg.key,
        wsHost:            cfg.host,
        wsPort:            cfg.port,
        wssPort:           cfg.port,
        forceTLS:          cfg.scheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
