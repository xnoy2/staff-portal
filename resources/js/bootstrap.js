import axios from 'axios';
import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

window.Pusher = Pusher;

const reverbKey    = import.meta.env.VITE_REVERB_APP_KEY;
const reverbHost   = import.meta.env.VITE_REVERB_HOST;
const reverbPort   = Number(import.meta.env.VITE_REVERB_PORT ?? 443);
const reverbScheme = import.meta.env.VITE_REVERB_SCHEME ?? 'https';

if (reverbKey && reverbHost) {
    window.Echo = new Echo({
        broadcaster:       'reverb',
        key:               reverbKey,
        wsHost:            reverbHost,
        wsPort:            reverbPort,
        wssPort:           reverbPort,
        forceTLS:          reverbScheme === 'https',
        enabledTransports: ['ws', 'wss'],
    });
}
