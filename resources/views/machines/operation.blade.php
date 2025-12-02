@extends('layout.app')
@section('title', 'Lab360::Machine Operation-Auto Test Schedule')

@section('content')
<style>
.schedule-container {
  background: #fff;
  border-radius: 10px;
  padding: 25px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.1);
}
.schedule-container h2 {
  font-size: 20px;
  font-weight: 600;
  margin-bottom: 20px;
  text-align: center;
}
.form-row {
  display: flex;
  flex-wrap: wrap;
  align-items: center;
  gap: 15px;
  margin-bottom: 20px;
}
.days label {
  margin-right: 10px;
  font-size: 14px;
}
.days input {
  margin-right: 4px;
}
#alarmList li, #historyList li {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: #f8f9fa;
  border-radius: 6px;
  padding: 10px;
  margin-bottom: 8px;
}
#alarmList button {
  border: none;
  background: #dc3545;
  color: white;
  border-radius: 4px;
  padding: 4px 10px;
  cursor: pointer;
}
</style>

<div class="container-fluid">
  <div id="esp32-echo-window" style="background:#181818;color:#fff;padding:12px 18px;margin-bottom:18px;border-radius:8px;min-height:40px;">
      <b>ESP32 Acknowledgment (ECHO) Log:</b>
      <button type="button" onclick="clearEchoLog()" style="margin-left:16px;background:#ff5555;color:#fff;border:none;padding:4px 12px;border-radius:5px;cursor:pointer;font-size:14px;">Clear Log</button>
      <div id="esp32EchoLog" style="margin-top:8px;"></div>
  </div>
  <div class="content-wrapper">
    <div class="row justify-content-center">
      <div class="col-md-12">
        <h1>Machine Name - {{ $machine->machine_name }}</h1>
      </div>

      <div class="col-md-12 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            @if(session('success'))
              <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            <div class="col-md-12">
              <h2>Auto Test Schedule</h2>

              <!-- Input Row -->
              <div class="form-row p-5">
                <!-- Test Name Select -->
                <select id="testName" class="form-control col-md-3" style="flex:1">
                  <option value="">Select Test Name</option>
                  @foreach($tests as $test)
                    <option value="{{ $test->name }}">{{ $test->name }}</option>
                  @endforeach
                </select>

                <!-- Day Selection -->
                <div class="days d-flex flex-wrap" style="flex:1">
                  <label><input type="checkbox" value="Sun"> Sun</label>
                  <label><input type="checkbox" value="Mon"> Mon</label>
                  <label><input type="checkbox" value="Tue"> Tue</label>
                  <label><input type="checkbox" value="Wed"> Wed</label>
                  <label><input type="checkbox" value="Thu"> Thu</label>
                  <label><input type="checkbox" value="Fri"> Fri</label>
                  <label><input type="checkbox" value="Sat"> Sat</label>
                </div>

                <!-- Time Picker -->
                <input type="time" id="alarmTime" class="form-control" style="width:130px">

                <!-- Add Button -->
                <button class="btn btn-success btn-sm" onclick="addAlarm()">Add Test</button>
              </div>
              <!-- Scheduled List -->
              <div class="col-md-12 text-center"><h4 class="mt-4">Scheduled Tests <br/>History</h4></div>
              <ul id="alarmList"></ul>

              <ul id="historyList"></ul>
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
// ESP32 Log WebSocket Listener
document.addEventListener('DOMContentLoaded', function() {
  var mac_id = @json(str_replace(':', '', $machine->mac_id));
  window.Echo.channel('machine.' + mac_id)
    .listen('.device.data', (e) => {
      const echoLog = document.getElementById('esp32EchoLog');
      if (echoLog) {
        const div = document.createElement('div');
        div.textContent = `[${new Date().toLocaleTimeString()}] ESP32 TX: ` + JSON.stringify(e.data);
        div.style.color = '#00bfff';
        echoLog.appendChild(div);
        echoLog.scrollTop = echoLog.scrollHeight;
      }
    });
});

function clearEchoLog() {
  const echoLog = document.getElementById('esp32EchoLog');
  if (echoLog) {
    echoLog.innerHTML = '';
  }
}
let alarms = [];
let history = [];

window.onload = () => {
  if (localStorage.getItem("alarms")) {
    alarms = JSON.parse(localStorage.getItem("alarms"));
    renderAlarms();
  }
  if (localStorage.getItem("history")) {
    history = JSON.parse(localStorage.getItem("history"));
    renderHistory();
  }

  setInterval(checkAlarms, 30000); // check every 30s
};

function addAlarm() {
  const time = document.getElementById("alarmTime").value;
  const days = Array.from(document.querySelectorAll(".days input:checked")).map(c => c.value);
  const name = document.getElementById("testName").value;

  if (!time || !name || days.length === 0) {
    alert("Please select test name, time, and days.");
    return;
  }

  // prevent duplicates
  for (let alarm of alarms) {
    if (alarm.time === time && alarm.name === name && days.some(d => alarm.days.includes(d))) {
      alert("Duplicate schedule not allowed.");
      return;
    }
  }

  const newAlarm = { time, days, name };
  alarms.push(newAlarm);
  renderAlarms();
  saveAlarms();
}

function renderAlarms() {
  const list = document.getElementById("alarmList");
  list.innerHTML = "";
  alarms.forEach((a, i) => {
    const li = document.createElement("li");
    li.innerHTML = `
      <div>
        <strong>${a.name}</strong> - ${a.time}<br>
        <small>${a.days.join(", ")}</small>
      </div>
      <button onclick="deleteAlarm(${i})">Delete</button>
    `;
    list.appendChild(li);
  });
}

function renderHistory() {
  const list = document.getElementById("historyList");
  list.innerHTML = "";
  history.forEach((a) => {
    const li = document.createElement("li");
    li.innerHTML = `<div><strong>${a.name}</strong> - ${a.time}<br><small>${a.days.join(", ")}</small></div>`;
    list.appendChild(li);
  });
}

function deleteAlarm(i) {
  alarms.splice(i, 1);
  renderAlarms();
  saveAlarms();
}

function saveAlarms() {
  localStorage.setItem("alarms", JSON.stringify(alarms));
  localStorage.setItem("history", JSON.stringify(history));
}

function checkAlarms() {
  const now = new Date();
  const today = now.toLocaleString("en-US", { weekday: "short" });

  alarms.forEach((a, i) => {
    const [hour, min] = a.time.split(":").map(Number);

    if (a.days.includes(today) && now.getHours() === hour && now.getMinutes() === min) {
      alert(`⏰ Running test: ${a.name}`);
      history.push(a);
      alarms.splice(i, 1);
      renderAlarms();
      renderHistory();
      saveAlarms();
    }
  });
}
</script>
@endsection
