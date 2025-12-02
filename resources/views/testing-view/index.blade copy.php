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
</style>
@vite('resources/js/app.js')

 
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
                        <div class="row">
                            <div class="col-md-6 mt-2">
                                <div class="command-card" data-id="1">
                                <h3>Command 1</h3>
                                <input type="text" class="endpoint" placeholder="Enter endpoint URL">
                                <textarea class="json-data" rows="3" placeholder='{"example":"data"}'></textarea>
                                <button class="sendBtn">Send</button>
                        </div>
                            </div>
                            <div class="col-md-6 mt-2">
                                <div class="command-card" data-id="2">
                                    <h3>Command 2</h3>
                                    <input type="text" class="endpoint" placeholder="Enter endpoint URL">
                                    <textarea class="json-data" rows="3" placeholder='{"cmd":"test"}'></textarea>
                                    <button class="sendBtn">Send</button>
                                </div>
                            </div>
                            <div class="col-md-6 mt-2">
                                <div class="command-card" data-id="3">
                                    <h3>Command 3</h3>
                                    <input type="text" class="endpoint" placeholder="Enter endpoint URL">
                                    <textarea class="json-data" rows="3" placeholder='{"cmd":"status"}'></textarea>
                                    <button class="sendBtn">Send</button>
                                </div>
                            </div>
                            <div class="col-md-6 mt-2">
                                <div class="command-card" data-id="4">
                                    <h3>Command 4</h3>
                                    <input type="text" class="endpoint" placeholder="Enter endpoint URL">
                                    <textarea class="json-data" rows="3" placeholder='{"cmd":"reset"}'></textarea>
                                    <button class="sendBtn">Send</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
              </div>
            </div>
        </div>
    
@endsection      

@section('script')
<script>
/*const rxWindow = document.getElementById('rxWindow');
const sendButtons = document.querySelectorAll('.sendBtn');
const clearBtn = document.getElementById('clearBtn');

sendButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        const card = btn.closest('.command-card');
        const endpoint = card.querySelector('.endpoint').value.trim();
        let jsonData = card.querySelector('.json-data').value.trim();

        if (!endpoint) return alert("Please enter endpoint URL");

        try {
            jsonData = JSON.parse(jsonData);
        } catch(e) {
            return alert("Invalid JSON format");
        }

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
            div.textContent = JSON.stringify(data);
            rxWindow.appendChild(div);
            rxWindow.scrollTop = rxWindow.scrollHeight;
        })
        .catch(err => {
            console.error(err);
            const div = document.createElement('div');
            div.textContent = "Error: " + err;
            rxWindow.appendChild(div);
        });
    });


    
    clearBtn.addEventListener('click', () => {
        rxWindow.innerHTML = '';
    });
});*/

const rxWindow = document.getElementById('rxWindow');
const sendButtons = document.querySelectorAll('.sendBtn');
const clearBtn = document.getElementById('clearBtn');

// Helper function to format time (HH:MM:SS)
function getCurrentTime() {
    const now = new Date();
    return now.toLocaleTimeString('en-US', { hour12: false });
}

// Send buttons functionality
sendButtons.forEach(btn => {
    btn.addEventListener('click', () => {
        const card = btn.closest('.command-card');
        const endpoint = card.querySelector('.endpoint').value.trim();
        let jsonData = card.querySelector('.json-data').value.trim();

        if (!endpoint) return alert("Please enter endpoint URL");

        // JSON validation
        try {
            jsonData = JSON.parse(jsonData);
        } catch(e) {
            return alert("Invalid JSON format");
        }

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
            div.innerHTML = `<strong>[${getCurrentTime()}]</strong> → ${JSON.stringify(data)}`;
            rxWindow.appendChild(div);
            rxWindow.scrollTop = rxWindow.scrollHeight;
        })
        .catch(err => {
            console.error(err);
            const div = document.createElement('div');
            div.innerHTML = `<strong>[${getCurrentTime()}]</strong> ❌ Error: ${err}`;
            rxWindow.appendChild(div);
        });
    });
});

// Clear button functionality
clearBtn.addEventListener('click', () => {
    rxWindow.innerHTML = '';
});


</script>
@endsection