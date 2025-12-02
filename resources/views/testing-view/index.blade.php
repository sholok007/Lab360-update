@extends('layout.app')
@section('title', 'Lab360::TestingView')

@section('content')

<style>

h1 {
  text-align: center;
  color: #58a6ff;
  margin-bottom: 20px;
}

.rx-section, .tx-section {
  background: #161b22;
  border-radius: 8px;
  padding: 15px;
  margin-bottom: 25px;
  box-shadow: 0 0 10px rgba(88,166,255,0.2);
}

.rx-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

#clearBtn {
  background: #d32f2f;
  color: #fff;
  border: none;
  padding: 6px 12px;
  border-radius: 6px;
  cursor: pointer;
}

#clearBtn:hover {
  background: #b71c1c;
}

.rx-window {
  background: #0d1117;
  border: 1px solid #30363d;
  border-radius: 6px;
  height: 300px;
  overflow: auto;
  white-space: pre-wrap;
  padding: 10px;
  margin-top: 10px;
  font-family: monospace;
  color: #00e676;
}

.command-card {
  background: #21262d;
  border: 1px solid #30363d;
  border-radius: 8px;
  padding: 12px;
  margin-bottom: 15px;
}

.command-card h3 {
  margin-top: 0;
  color: #58a6ff;
}

.endpoint, .json-data {
  width: 100%;
  margin-top: 5px;
  margin-bottom: 10px;
  background: #0d1117;
  border: 1px solid #30363d;
  color: #c9d1d9;
  border-radius: 6px;
  padding: 6px;
  font-family: monospace;
}

.sendBtn {
  background: #238636;
  color: #fff;
  border: none;
  padding: 8px 16px;
  border-radius: 6px;
  cursor: pointer;
}

.sendBtn:hover {
  background: #2ea043;
}

.rx-window { 
    background: #0d1117; color: #00e676; padding: 10px; height: 300px; overflow: auto; 
}
.sendBtn { background: #238636; color: #fff; border-radius:6px; padding:8px 16px; }
#clearBtn { background:#d32f2f; color:#fff; border-radius:6px; padding:6px 12px; }
</style>

<div class="container-fluid" id="app">
    <div class="content-wrapper">
        <div class="row">
            <div class="container">
              
                <h1>Test Terminal</h1>

                <!-- RX Terminal Window -->
                <div class="rx-section">
                    <div class="rx-header">
                        <h2>Received Data</h2>
                        <button id="clearBtn">Clear</button>
                    </div>
                    <div id="rxWindow" class="rx-window"></div>
                </div>

                <!-- TX Command Buttons -->
                <div class="tx-section">
                    <h2>Send Commands</h2>
                    
                    <!-- ESP32 Test Command -->
                    <div class="row">
                        <div class="col-md-6 mt-2">
                            <div class="command-card" data-type="esp32">
                                <h3>📱 Test ESP32 (8C:4F:00:AC:26:EC)</h3>
                                <input type="hidden" class="endpoint" value="/api/device/send-command">
                                <select class="form-control mb-2" id="commandSelect">
                                    <option value="">Select a command...</option>
                                    <option value="ping">Ping</option>
                                    <option value="status">Get Status</option>
                                    <option value="start_test">Start Test</option>
                                    <option value="stop_test">Stop Test</option>
                                    <option value="reset">Reset</option>
                                    <option value="custom">Custom Command</option>
                                </select>
                                <input type="text" class="form-control mb-2" id="customCommand" placeholder="Custom command (if needed)" style="display:none;">
                                <textarea class="json-data" rows="2" placeholder='{"payload":{}}'></textarea>
                                <button class="sendBtn" id="esp32SendBtn">Send Command to ESP32</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Other Commands -->
                    <div class="row">
                        @for ($i = 1; $i <= 4; $i++)
                            <div class="col-md-6 mt-2">
                                <div class="command-card" data-id="{{ $i }}">
                                    <h3>Command {{ $i }}</h3>
                                    <input type="text" class="endpoint" placeholder="Enter endpoint URL">
                                    <textarea class="json-data" rows="3" placeholder='{"cmd":"test{{ $i }}"}'></textarea>
                                    <button class="sendBtn">Send</button>
                                </div>
                            </div>
                        @endfor
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection      

{{-- Load compiled JS --}}
@vite(['resources/js/app.js'])

@section('script')
<script>

const ESP32_MAC = '8C:4F:00:AC:26:EC';
// sanitized channel name (remove ':' which is invalid in Pusher channel names)
const ESP32_MAC_SAFE = ESP32_MAC.replace(/:/g, '');
// Expose for app.js
window.DEVICE_MAC = ESP32_MAC;

document.addEventListener("DOMContentLoaded", () => {
    const rxWindow = document.getElementById('rxWindow');
    const clearBtn = document.getElementById('clearBtn');
    const sendButtons = document.querySelectorAll('.sendBtn');
    const esp32SendBtn = document.getElementById('esp32SendBtn');
    const commandSelect = document.getElementById('commandSelect');
    const customCommand = document.getElementById('customCommand');

    // Show/hide custom command input
    commandSelect.addEventListener('change', () => {
        if (commandSelect.value === 'custom') {
            customCommand.style.display = 'block';
        } else {
            customCommand.style.display = 'none';
        }
    });

    // ✅ Clear RX window
    clearBtn.addEventListener('click', () => {
        rxWindow.innerHTML = '';
        const div = document.createElement('div');
        div.textContent = `[${new Date().toLocaleTimeString()}] 🧹 Terminal cleared`;
        rxWindow.appendChild(div);
    });

    // ✅ ESP32 Send Command button
    esp32SendBtn.addEventListener('click', () => {
        const selectedCmd = commandSelect.value;
        if (!selectedCmd) return alert("Please select a command");

        const command = selectedCmd === 'custom' ? customCommand.value.trim() : selectedCmd;
        if (!command) return alert("Please enter a command");

        const card = esp32SendBtn.closest('.command-card');
        let jsonData;
        
        try {
            jsonData = JSON.parse(card.querySelector('.json-data').value.trim() || '{}');
        } catch(e) {
            return alert("Invalid JSON in payload");
        }

        const sendData = {
            mac_id: ESP32_MAC,
            command: command,
            payload: jsonData
        };

        // Show sending message
        const div = document.createElement('div');
        div.innerHTML = `<span style="color:#ffd700;">[${new Date().toLocaleTimeString()}] 📨 Sending to ESP32 → ${JSON.stringify(sendData)}</span>`;
        rxWindow.appendChild(div);
        rxWindow.scrollTop = rxWindow.scrollHeight;

        // First test if API is reachable
        fetch('/api/health')
            .then(res => res.json())
            .then(data => {
                console.log('✅ API Health OK:', data);
                // Now send the command
                sendCommandToESP32(sendData);
            })
            .catch(err => {
                const div = document.createElement('div');
                div.innerHTML = `<span style="color:#ff6b6b;">[${new Date().toLocaleTimeString()}] ⚠️ API Health Check Failed: ${err.message}</span>`;
                rxWindow.appendChild(div);
                rxWindow.scrollTop = rxWindow.scrollHeight;
            });
    });

    function sendCommandToESP32(sendData) {
        // Use relative URL - works from any host
        const apiUrl = '/api/device/send-command';
        
        // POST request
        fetch(apiUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify(sendData)
        })
        .then(res => {
            // Log the actual response status and content-type
            console.log('Response Status:', res.status);
            console.log('Content-Type:', res.headers.get('content-type'));
            
            if (!res.ok) {
                return res.text().then(text => {
                    throw new Error(`HTTP ${res.status}: ${text.substring(0, 200)}`);
                });
            }
            return res.json();
        })
        .then(data => {
            const div = document.createElement('div');
            div.innerHTML = `<span style="color:#00ff00;">[${new Date().toLocaleTimeString()}] ✅ Response → ${JSON.stringify(data)}</span>`;
            rxWindow.appendChild(div);
            rxWindow.scrollTop = rxWindow.scrollHeight;
        })
        .catch(err => {
            const div = document.createElement('div');
            div.innerHTML = `<span style="color:#ff6b6b;">[${new Date().toLocaleTimeString()}] ❌ Error: ${err.message}</span>`;
            rxWindow.appendChild(div);
            rxWindow.scrollTop = rxWindow.scrollHeight;
            console.error('Full error:', err);
        });
    }

    // ✅ Send Command button (old generic ones)
    sendButtons.forEach(btn => {
        if (btn.id === 'esp32SendBtn') return; // Skip ESP32 button
        
        btn.addEventListener('click', () => {
            const card = btn.closest('.command-card');
            const endpoint = card.querySelector('.endpoint').value.trim();
            let jsonData = card.querySelector('.json-data').value.trim();

            if (!endpoint) return alert("Please enter endpoint URL");

            try {
                jsonData = JSON.parse(jsonData);
            } catch(e) {
                return alert("Invalid JSON");
            }

            // Show sending message
            const div = document.createElement('div');
            div.textContent = `[${new Date().toLocaleTimeString()}] 📨 Sending → ${JSON.stringify(jsonData)}`;
            rxWindow.appendChild(div);
            rxWindow.scrollTop = rxWindow.scrollHeight;

            // POST request
            fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: JSON.stringify(jsonData)
            })
            .then(res => res.json())
            .then(data => {
                const div = document.createElement('div');
                div.textContent = `[${new Date().toLocaleTimeString()}] ✅ Response → ${JSON.stringify(data)}`;
                rxWindow.appendChild(div);
                rxWindow.scrollTop = rxWindow.scrollHeight;
            })
            .catch(err => {
                const div = document.createElement('div');
                div.textContent = `[${new Date().toLocaleTimeString()}] ❌ ${err}`;
                rxWindow.appendChild(div);
                rxWindow.scrollTop = rxWindow.scrollHeight;
            });
        });
    });

    // ✅ WebSocket (Reverb) connection
    const ws = new WebSocket("ws://10.0.0.169:9000/app/localkey?protocol=7&client=js&version=7.0.0");

    ws.onopen = () => {
        console.log("✅ WebSocket connected");
        ws.send(JSON.stringify({ event: "pusher:subscribe", data: { channel: "public-channel" } }));
    };

    ws.onmessage = (event) => {
        const div = document.createElement('div');
        div.textContent = `[${new Date().toLocaleTimeString()}] 🌐 ${event.data}`;
        rxWindow.appendChild(div);
        rxWindow.scrollTop = rxWindow.scrollHeight;
    };

    ws.onerror = (error) => console.error("WebSocket error:", error);
    ws.onclose = () => console.warn("WebSocket closed");

    // ✅ Laravel Echo listen - ESP32 data reception
    if (typeof window.Echo !== 'undefined') {
        // Listen for device data from ESP32
        window.Echo.channel(`machine.${ESP32_MAC_SAFE}`)
            .listen('.device.data', e => {
                const div = document.createElement('div');
                div.innerHTML = `<span style="color:#00ff00;">[${new Date().toLocaleTimeString()}] 📡 Device Data → ${JSON.stringify(e.data || e)}</span>`;
                rxWindow.appendChild(div);
                rxWindow.scrollTop = rxWindow.scrollHeight;
            });
        
        // Listen for command responses from ESP32
        window.Echo.channel(`device.${ESP32_MAC_SAFE}`)
            .listen('.device.command', e => {
                const div = document.createElement('div');
                div.innerHTML = `<span style="color:#58a6ff;">[${new Date().toLocaleTimeString()}] 🔔 Command Event → ${JSON.stringify(e.data || e)}</span>`;
                rxWindow.appendChild(div);
                rxWindow.scrollTop = rxWindow.scrollHeight;
            });
            
        console.log('✅ Echo listening on device channels');
    }
});
</script>
@endsection