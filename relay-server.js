const WebSocket = require('ws');
const wss = new WebSocket.Server({ port: 9000 });

const clients = {}; // { channel: [ws, ws, ...] }

wss.on('connection', function connection(ws) {
    ws.on('message', function incoming(message) {
        try {
            const data = JSON.parse(message);
            // Example: { event: ".device.acknowledged", data: { mac_id: "...", ... } }
            if (data.event === '.device.acknowledged') {
                console.log('ESP32 Acknowledgment (ECHO) Log:', data);
            }
            if (data.event && data.data && data.data.mac_id) {
                const safeMac = data.data.mac_id.replace(/:/g, '');
                const channel = 'machine.' + safeMac;

                // Register client to channel
                ws.channel = channel;
                if (!clients[channel]) clients[channel] = [];
                if (!clients[channel].includes(ws)) clients[channel].push(ws);

                // Relay .device.acknowledged to all clients on this channel
                if (data.event === '.device.acknowledged') {
                    clients[channel].forEach(client => {
                        if (client !== ws && client.readyState === WebSocket.OPEN) {
                            client.send(JSON.stringify({
                                event: '.device.acknowledged',
                                data: data.data
                            }));
                        }
                    });
                }
            }
        } catch (e) {
            console.error('Invalid message:', message);
        }
    });

    ws.on('close', function() {
        // Remove ws from all channels
        Object.keys(clients).forEach(channel => {
            clients[channel] = clients[channel].filter(client => client !== ws);
        });
    });
});

console.log('Relay WebSocket server running on ws://0.0.0.0:9000');
