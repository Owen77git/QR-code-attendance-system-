<?php
session_start();
include("../../API/db.connect.php");

if (!isset($_SESSION['student'])) {
    header("Location: student_log.php");
    exit();
}

$student_id = $_SESSION['student'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Device Registration</title>
<style>
  * { margin:0; padding:0; box-sizing:border-box; }
  body {
    background:#000;
    color:#fff;
    font-family:"Segoe UI",Arial,sans-serif;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    min-height:100vh;
    text-align:center;
    padding:20px;
  }
  h2 {
    color:#ffcc00;
    margin-bottom:1rem;
  }
  p {
    margin-bottom:1.5rem;
    line-height:1.4;
  }
  button {
    background:#ffcc00;
    color:#000;
    border:none;
    padding:12px 20px;
    border-radius:8px;
    font-size:1rem;
    cursor:pointer;
    width:90%;
    max-width:320px;
    font-weight:600;
    transition:0.2s;
  }
  button:hover {
    background:#e6b800;
  }
  #countdown {
    margin-top:1rem;
    color:#00ff99;
    font-size:1.1rem;
  }
  #status {
    margin-top:1rem;
    font-size:0.95rem;
    word-wrap:break-word;
    max-width:350px;
  }
  #cameraPreview {
    margin:1rem 0;
    max-width:300px;
    border:2px solid #ffcc00;
    border-radius:8px;
    display:none;
  }
  #captureBtn {
    background:#00ff99;
    color:#000;
    margin-top:0.5rem;
    display:none;
  }
  #retakeBtn {
    background:#ff4444;
    color:#fff;
    margin-top:0.5rem;
    display:none;
  }
</style>
</head>
<body>
<h2>Device Registration Scan</h2>
<p>Click below to send your device information securely.</p>
<button id="sendBtn">Allow & Send Device Info</button>
<video id="cameraPreview" autoplay playsinline></video>
<canvas id="photoCanvas" style="display:none;"></canvas>
<button id="captureBtn">Capture Selfie</button>
<button id="retakeBtn">Retake Photo</button>
<p id="countdown"></p>
<p id="status"></p>

<script>
const studentId = "<?php echo $student_id; ?>";
const btn = document.getElementById("sendBtn");
const status = document.getElementById("status");
const countdownEl = document.getElementById("countdown");
const video = document.getElementById("cameraPreview");
const canvas = document.getElementById("photoCanvas");
const captureBtn = document.getElementById("captureBtn");
const retakeBtn = document.getElementById("retakeBtn");

const lastScanKey = "lastScan_" + studentId;

const lastScan = localStorage.getItem(lastScanKey);
if (lastScan) {
  const diff = Date.now() - parseInt(lastScan);
  const remaining = 3600000 - diff; // 1 hour = 3600000ms
  if (remaining > 0) {
    startCountdown(remaining);
  }
}

let stream = null;
let capturedPhoto = null;

btn.addEventListener("click", () => {
  const lastScan = localStorage.getItem(lastScanKey);
  if (lastScan && Date.now() - parseInt(lastScan) < 3600000) {
    const remaining = 3600000 - (Date.now() - parseInt(lastScan));
    const minutes = Math.floor(remaining / 60000);
    const seconds = Math.floor((remaining % 60000) / 1000);
    status.textContent = `...Please wait ${minutes}m ${seconds}s before retrying.`;
    status.style.color = "#ff4444";
    return;
  }

  startCamera();
});

captureBtn.addEventListener("click", capturePhoto);
retakeBtn.addEventListener("click", startCamera);

function startCamera() {
  status.textContent = "Starting camera for selfie...";
  status.style.color = "#fff";
  
  btn.style.display = "none";
  captureBtn.style.display = "none";
  retakeBtn.style.display = "none";
  
  navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" }, audio: false })
    .then(mediaStream => {
      stream = mediaStream;
      video.srcObject = stream;
      video.style.display = "block";
      captureBtn.style.display = "block";
      status.textContent = "Camera ready - Click 'Capture Selfie'";
    })
    .catch(err => {
      console.error("Camera error:", err);
      status.textContent = "⚠ Camera access denied, continuing without selfie.";
      status.style.color = "#ff4444";
      collectDeviceInfo(null);
    });
}

function capturePhoto() {
  const context = canvas.getContext('2d');
  canvas.width = video.videoWidth;
  canvas.height = video.videoHeight;
  context.drawImage(video, 0, 0, canvas.width, canvas.height);
  
  capturedPhoto = canvas.toDataURL('image/jpeg', 0.8);
  
  // Stop camera stream
  if (stream) {
    stream.getTracks().forEach(track => track.stop());
  }
  
  video.style.display = "none";
  captureBtn.style.display = "none";
  retakeBtn.style.display = "block";
  
  status.textContent = "Selfie captured! Click 'Retake Photo' or collecting device info...";
  collectDeviceInfo(capturedPhoto);
}

function collectDeviceInfo(selfieData) {
  status.textContent = "Collecting device info...";
  
  const deviceData = {
    userAgent: navigator.userAgent,
    platform: navigator.platform,
    screenWidth: screen.width,
    screenHeight: screen.height,
    language: navigator.language,
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    timestamp: new Date().toISOString(),
    selfie: selfieData,
    batteryLevel: 0 // Default value
  };

  // Try to get battery level if supported
  if ('getBattery' in navigator) {
    navigator.getBattery().then(function(battery) {
      deviceData.batteryLevel = Math.round(battery.level * 100);
      getLocationAndSend();
    }).catch(function() {
      deviceData.batteryLevel = 0;
      getLocationAndSend();
    });
  } else {
    getLocationAndSend();
  }

  function getLocationAndSend() {
    if (navigator.geolocation) {
      navigator.geolocation.getCurrentPosition(
        pos => {
          deviceData.latitude = pos.coords.latitude;
          deviceData.longitude = pos.coords.longitude;
          sendToServer(deviceData);
        },
        () => {
          status.textContent = "⚠ Location denied, sending basic info.";
          sendToServer(deviceData);
        }
      );
    } else {
      status.textContent = "Geolocation not supported, sending basic info.";
      sendToServer(deviceData);
    }
  }
}

function sendToServer(data) {
  console.log("Sending data to server:", {
    hasSelfie: !!data.selfie,
    selfieLength: data.selfie ? data.selfie.length : 0,
    otherData: { ...data, selfie: 'BASE64_DATA_HIDDEN' }
  });
  
  fetch("save_device.php", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data)
  })
  .then(res => res.text())
  .then(response => {
    console.log("Server response:", response);
    status.innerHTML = response;
    
    // Fix: Check for successful response based on your PHP output
    if (response.includes("saved successfully") || response.includes("Device info")) {
      const now = Date.now();
      localStorage.setItem(lastScanKey, now);
      startCountdown(3600000);
    }
    
    // Reset UI
    btn.style.display = "block";
    retakeBtn.style.display = "none";
    video.style.display = "none";
    capturedPhoto = null;
  })
  .catch(err => {
    console.error("Fetch error:", err);
    status.textContent = " Failed to send device info.";
    
    // Reset UI on error
    btn.style.display = "block";
    retakeBtn.style.display = "none";
    video.style.display = "none";
  });
}

function startCountdown(ms) {
  btn.disabled = true;
  let remaining = ms;
  const interval = setInterval(() => {
    if (remaining <= 0) {
      clearInterval(interval);
      countdownEl.textContent = "";
      btn.disabled = false;
      status.textContent = "You can now send again.";
      status.style.color = "#00ff99";
      return;
    }
    const minutes = Math.floor(remaining / 60000);
    const seconds = Math.floor((remaining % 60000) / 1000);
    countdownEl.textContent = `Next scan available in ${minutes}m ${seconds}s`;
    remaining -= 1000;
  }, 1000);
}
</script>
</body>
</html>