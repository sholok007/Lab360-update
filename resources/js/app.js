import './bootstrap';

import Echo from 'laravel-echo';
import io from 'socket.io-client';

// Listen for events globally
document.addEventListener("DOMContentLoaded", () => {
        // Handle missing images gracefully
        document.addEventListener('error', function(e) {
            if (e.target.tagName === 'IMG') {
                console.warn('[IMAGE ERROR] Not found:', e.target.src);
                e.target.alt = 'Image not found';
                e.target.style.border = '2px solid red';
            }
        }, true);
    // Debug: log env variables before Echo init
    const reverbKey = import.meta.env.VITE_REVERB_APP_KEY;
    const reverbHost = import.meta.env.VITE_REVERB_HOST;
    const reverbPort = import.meta.env.VITE_REVERB_PORT;
    const reverbScheme = import.meta.env.VITE_REVERB_SCHEME || 'ws';
    console.log('[DEBUG] VITE_REVERB_APP_KEY:', reverbKey);
    console.log('[DEBUG] VITE_REVERB_HOST:', reverbHost);
    console.log('[DEBUG] VITE_REVERB_PORT:', reverbPort);
    console.log('[DEBUG] VITE_REVERB_SCHEME:', reverbScheme);

    // Check for missing env vars
    const missingVars = [];
    if (!reverbKey) missingVars.push('VITE_REVERB_APP_KEY');
    if (!reverbHost) missingVars.push('VITE_REVERB_HOST');
    if (!reverbPort) missingVars.push('VITE_REVERB_PORT');
    if (!reverbScheme) missingVars.push('VITE_REVERB_SCHEME');
    if (missingVars.length > 0) {
        const msg = `[ERROR] Missing environment variables: ${missingVars.join(', ')}. Echo will not initialize.`;
        console.error(msg);
        const rxWindow = document.getElementById('rxWindow');
        if (rxWindow) {
            const errorDiv = document.createElement('div');
            errorDiv.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
            errorDiv.style.color = '#ff0000';
            rxWindow.appendChild(errorDiv);
        }
        return;
    }

    // Initialize Echo inside DOMContentLoaded to ensure order
    window.io = io;
    try {
        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: reverbKey,
            wsHost: reverbHost,
            wsPort: reverbPort,
            wsPath: '',
            wsScheme: reverbScheme,
            forceTLS: reverbScheme === 'wss',
            disableStats: true,
            encrypted: reverbScheme === 'wss'
        });
        console.log('[DEBUG] Echo initialized:', window.Echo);
    } catch (err) {
        console.error('[ERROR] Failed to initialize Echo:', err);
        const rxWindow = document.getElementById('rxWindow');
        if (rxWindow) {
            const errorDiv = document.createElement('div');
            errorDiv.textContent = `[${new Date().toLocaleTimeString()}] ERROR: Echo initialization failed!`;
            errorDiv.style.color = '#ff0000';
            rxWindow.appendChild(errorDiv);
        }
        return;
    }
    const rxWindow = document.getElementById('rxWindow');
    if (!rxWindow) return;

    // Get MAC address from global JS variable or fallback
    let mac = window.DEVICE_MAC || '8C:4F:00:AC:26:EC'; // Set this variable from backend or config as needed
    let safeMac = mac.replace(/:/g, '');
    let channelName = 'machine.' + safeMac;
    console.log('[DEBUG] Subscribing to channel:', channelName, 'with MAC:', mac, 'safeMac:', safeMac);

    if (!window.Echo) {
        console.error('[ERROR] window.Echo is undefined. Check that Echo is imported and initialized correctly.');
        const errorDiv = document.createElement('div');
        errorDiv.textContent = `[${new Date().toLocaleTimeString()}] ERROR: Echo is not initialized!`;
        errorDiv.style.color = '#ff0000';
        rxWindow.appendChild(errorDiv);
        return;
    }

    const echoChannel = window.Echo.channel(channelName);
    console.log('[DEBUG] Subscribed to channel:', channelName);

    // Listen for all events on the channel for debugging
    if (echoChannel.listenForWhisper) {
        echoChannel.listenForWhisper('*', (e) => {
            console.log('[WHISPER EVENT]', e);
        });
    }
    if (echoChannel.listen) {
        echoChannel.listen(/.*/, (event, data) => {
            console.log('[ALL EVENTS]', event, data);
        });
    }

    echoChannel
        .listen('.device.data', (e) => {
            // Debug: log full event
            console.log('[.device.data] FULL EVENT:', e);
            const debugDiv = document.createElement('div');
            debugDiv.textContent = `[${new Date().toLocaleTimeString()}] RAW [.device.data]: ${JSON.stringify(e)}`;
            debugDiv.style.color = '#8888ff';
            rxWindow.appendChild(debugDiv);

            // Support both direct and nested payloads
            let payload = e && e.data ? e.data : e;
            // If payload is a stringified JSON, parse it
            if (typeof payload === 'string') {
                try {
                    payload = JSON.parse(payload);
                } catch (err) {
                    // Not JSON, leave as is
                }
            }

            const div = document.createElement('div');
            div.textContent = `[${new Date().toLocaleTimeString()}] ${JSON.stringify(payload)}`;
            rxWindow.appendChild(div);
            rxWindow.scrollTop = rxWindow.scrollHeight;
        })
        .listen('.device.acknowledged', (e) => {
            // Debug: log full event
            console.log('[.device.acknowledged] FULL EVENT:', e);
            const debugDiv = document.createElement('div');
            debugDiv.textContent = `[${new Date().toLocaleTimeString()}] RAW [.device.acknowledged]: ${JSON.stringify(e)}`;
            debugDiv.style.color = '#ff88ff';
            rxWindow.appendChild(debugDiv);

            // Support both direct and nested payloads
            const ack = e && e.mac_id ? e : (e.data && e.data.mac_id ? e.data : e);
            const div = document.createElement('div');
            div.textContent = `[${new Date().toLocaleTimeString()}] ACK: [${ack.status}] ${ack.message || ''} (tx: ${ack.transaction_id || ''})`;
            div.style.color = ack.status === 'acknowledged' ? '#00ff00' : '#ff6b6b';
            rxWindow.appendChild(div);
            rxWindow.scrollTop = rxWindow.scrollHeight;
        });
});
