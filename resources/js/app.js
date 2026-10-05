import Echo from 'laravel-echo';

import Pusher from 'pusher-js';
window.Pusher = Pusher;

window.Echo = new Echo({
    broadcaster: 'pusher',
    key: PUSHER_APP_KEY,
    cluster: PUSHER_APP_CLUSTER,
    wsHost: `ws-${PUSHER_APP_CLUSTER}.pusher.com`,
    wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
    wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
    authEndpoint: DYNAMIC_URL
});

window.Echo.join('online')
    .here(users => {
        users.forEach(user => {
            $(`.start-chat[data-id="${user.id}"] span.status`).addClass('active');
        });
    })
    .joining(user => {
        $(`.start-chat[data-id="${user.id}"] span.status`).addClass('active');
    })
    .leaving(user => {
        $(`.start-chat[data-id="${user.id}"] span.status`).removeClass('active');
    });

window.Echo.private(`live-chat.${AUTH_ID}`)
    .listen('.MessageSendEvent', e => {
        appendMessage(e.data.sender_id, e.data, false);
    });