
const WebSocket = require('ws');
const http = require('http');

// Create HTTP server to handle WebSocket upgrades on any path
const server = http.createServer();
const wss = new WebSocket.Server({ noServer: true });


server.on('upgrade', (request, socket, head) => {
    const path = request.url;
    console.log('Upgrade request for path:', path);
    // Accept only / and /app/localkey for extra safety
    if (path === '/' || path === '/app/localkey') {
        wss.handleUpgrade(request, socket, head, (ws) => {
            ws.upgradeReq = request;
            wss.emit('connection', ws, request);
        });
    } else {
        console.log('Rejected WebSocket connection on path:', path);
        socket.destroy();
    }
});

server.listen(9000, () => {
    console.log('Relay WebSocket server running on ws://0.0.0.0:9000 (any path)');
});

const clients = {}; // { channel: [ws, ws, ...] }

wss.on('connection', function connection(ws, req) {
    // Log the connection path for debugging
    const path = req && req.url ? req.url : '(unknown)';
    console.log('New WebSocket connection on path:', path);

    // Log every raw message received

        ws.on('message', function incoming(message) {
            // Decode Buffer to string if needed
            let msgString = message;
            if (Buffer.isBuffer(message)) {
                msgString = message.toString('utf8');
            }
            console.log('RAW MESSAGE RECEIVED:', msgString);
            try {
                const data = JSON.parse(msgString);
                // Print all acknowledgment (ECHO) messages for debugging
                if (data.event === 'device.acknowledged' || data.event === '.device.acknowledged') {
                    console.log('ESP32 Acknowledgment (ECHO) Log:', data);
                }
                // ...existing code...
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
                console.error('Invalid message:', msgString);
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
