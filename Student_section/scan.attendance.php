<?php
session_start();
include("../../API/db.connect.php");

if (!isset($_SESSION['student'])) {
    header("Location: Login.php");
    exit();
}

$admission_no = $_SESSION['student'];

$sql = "SELECT id, full_name FROM students WHERE admission_no='$admission_no' LIMIT 1";
$result = $conn->query($sql);
$student = $result->fetch_assoc();
$student_id = $student['id'];
$student_name = $student['full_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance Workstation</title>
  <link rel="stylesheet" href="../../CSS/Attendance.CS">
<style>
  body {
    font-family: 'Segoe UI', sans-serif;
    background: url('https://library.zetech.ac.ke/images/slide1.jpg') no-repeat center center fixed;
    background-size: cover;
    margin: 0;
  }
  * { margin: 0; padding: 0; box-sizing: border-box; }

  .navbar {
    background: rgba(30,60,114,0.9);
    color: white;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 30px;
    position: fixed;
    top: 0; left: 0;
    width: 100%;
    z-index: 1000;
  }
  .logo { color: #ffcc00; }

  .menu {
    display: flex;
    gap: 25px;
  }
  .menu a {
    color: white;
    font-weight: bold;
    text-decoration: none;
    font-size: 0.95rem;
    position: relative;
    padding-bottom: 5px;
    transition: color 0.3s ease;
  }
  .menu a::after {
    content: "";
    position: absolute;
    bottom: 0;
    left: 0;
    width: 0;
    height: 2px;
    background: #ffcc00;
    transition: width 0.3s ease;
  }
  .menu a:hover { color: #fff; }
  .menu a:hover::after, .menu a.active::after { width: 100%; }
  .menu a.active { color: #fff; }

  .hamburger {
    display: none;
    font-size: 28px;
    color: #ffcc00;
    cursor: pointer;
  }

  .workspace {
  display: flex;
  gap: 20px;
  justify-content: space-between;
  padding: 20px;
}
.panel {
  flex: 1; 
  background: white;
  border: 1px solid rgba(30,60,114,0.9);
  border-radius: 8px;
  padding: 20px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.2);
  text-align: center;
  margin-top:60px;
}

  .panel h3 { margin-bottom: 15px; color: black; }

  #reader {
    width: 100%;
    max-width: 350px;
    margin: 0 auto;
    border: 2px solid  rgba(30,60,114,0.9);;
    border-radius: 8px;
  }
  video {
    width: 100% !important;
    height: auto !important;
    border-radius: 8px;
  }

  .btn {
    background: rgba(30,60,114,0.9);
    color: #000;
    padding: 10px 20px;
    border: none;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    margin: 10px 5px;
  }
  .btn:hover { background: #e6b800; }
  .btn-danger {
    background: #d9534f;
    color: #fff;
  }
  .btn-danger:hover { background: #c9302c; }
  .btn-disabled {
    background: #6c757d;
    cursor: not-allowed;
    opacity: 0.6;
  }

  .history ul { list-style: none; }
  .history li {
    padding: 8px 0;
    border-bottom: 1px solid rgba(255,255,255,0.1);
  }
  .history li:last-child { border-bottom: none; }
  .history button {
    background: #0275d8;
    color: #fff;
    padding: 6px 12px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-size: 0.9rem;
  }
  .history button:hover { background: #025aa5; }

  input {
    padding: 8px;
    border-radius: 6px;
    border: none;
    width: 90%;
    margin-bottom: 8px;
  }
  table {
    width: 100%;
    margin-top: 10px;
    border-collapse: collapse;
  }
  th, td {
    border: 1px solid rgba(255,255,255,0.1);
    padding: 8px;
    text-align: left;
  }
  th { background: rgba(255,255,255,0.1); }

.mobile-bg-overlay {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0, 0, 0, 0.5);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.4s ease;
  z-index: 1500;
}
.mobile-bg-overlay.show {
  opacity: 1;
  pointer-events: all;
}

.mobile-menu-overlay {
  position: fixed;
  top: 0;
  right: 0;
  width: 80%;            /* covers 80% of screen width */
  max-width: 320px;
  height: 100%;          /* full page height */
  background: rgba(10, 10, 30, 0.97);
  transform: translateX(100%);
  opacity: 0;
  transition: transform 0.45s ease, opacity 0.45s ease;
  z-index: 2000;
  box-shadow: -5px 0 15px rgba(0, 0, 0, 0.4);
}
.mobile-menu-overlay.show {
  transform: translateX(0);
  opacity: 1;
}

.mobile-menu-panel {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 60px 20px;
  gap: 15px;
  height: 100%;
  overflow-y: auto;
}

.close-btn {
  position: absolute;
  top: 10px;
  right: 25px;
  font-size: 30px;
  color: #fff;
  cursor: pointer;
  font-weight: bold;
  transition: color 0.3s ease;
}
.close-btn:hover { color: #ffcc00; }

.mobile-menu-panel a {
  display: block;
  width: 80%;
  text-align: center;
  background: rgba(255,255,255,0.1);
  color: #fff;
  text-decoration: none;
  font-size: 18px;
  font-weight: 600;
  padding: 12px 0;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.2);
  transition: all 0.4s ease;
  box-shadow: 0 3px 6px rgba(0,0,0,0.2);
}
.mobile-menu-panel a:hover {
  background: #ffcc00;
  color: #000;
  transform: scale(1.03);
  border-color: #ffcc00;
}

.hamburger {
  display: none;
}

@media (max-width: 768px) {
  .menu { display: none; }
  .hamburger {
    display: block;
    font-size: 28px;
    cursor: pointer;
    color: #ffcc00;
  }
}

  .close-btn {
    position: absolute;
    top: 10px;
    right: 25px;
    font-size: 30px;
    color: #fff;
    cursor: pointer;
    font-weight: bold;
    transition: color 0.3s ease;
  }
  .close-btn:hover { color: #ffcc00; }
  .mobile-menu-panel a {
    display: block;
    width: 80%;
    text-align: center;
    background: rgba(255,255,255,0.1);
    color: #fff;
    text-decoration: none;
    font-size: 18px;
    font-weight: 600;
    padding: 12px 0;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.2);
    transition: all 0.4s ease;
    box-shadow: 0 3px 6px rgba(0,0,0,0.2);
  }
  .mobile-menu-panel a:hover {
    background: #ffcc00;
    color: #000;
    transform: scale(1.03);
    border-color: #ffcc00;
  }

  /* Responsive */
  @media(max-width: 768px) {
    .menu { display: none; }
    .menu.show { display: flex; flex-direction: column; }
    .hamburger { display: block; }
    .workspace {
      display: flex;
      flex-direction: column;
      gap: 20px;
    }
  }
</style>

</head>

<body>
  <!-- Navbar -->
  <div class="navbar">
    <h2 class="logo">Attendance</h2>
    <span class="hamburger" id="hamburger">&#9776;</span>
    <nav class="menu" id="menu">
      <a href="home.php">Home</a>
      <a href="dashboard.php">Dashboard</a>
      <a href="scan.attendance.php" class="active">Attendance</a>
      <a href="student_out.php">Logout</a>
    </nav>
  </div>

  <div class="mobile-menu-overlay" id="mobileMenuOverlay">
    <div class="mobile-menu-panel">
      <span class="close-btn" id="closeMenu">&times;</span>
      <a href="home.php">Home</a>
      <a href="dashboard.php">Dashboard</a>
      <a href="scan.attendance.php" class="active">Attendance</a>
      <a href="student_out.php">Logout</a>
    </div>
  </div>

  <section>
  <div class="workspace">
    <!-- Left Panel: Scanner -->
    <div class="panel">
      <h3> Scan QR Code</h3>
      <button class="btn" id="startScan">Start Camera</button>
      <button class="btn btn-danger" id="stopScan">Stop Camera</button>
      <div id="reader"></div>
      <p id="scanResult"></p>
    </div>

    <div class="panel">
      <h2><b>Guidlines<b></h2>
      <ol>
     <li> Ensure you connect to school wifi before scanning qr code </li>
       <li> Incase you lose device report to lecturer to be marked present physically</li>
          <li>  ensure you accept system to collect your device information</li>
              <li>  do not scan qr code twice </li>
                    <ol>
    
          <?php
          $history = $conn->query("SELECT full_name, qr_code, scan_time 
                                   FROM attendance_scans 
                                   WHERE student_id='$student_id'
                                   ORDER BY scan_time DESC LIMIT 10");
          while ($row = $history->fetch_assoc()) {
              echo "<tr>
                      <td><button>{$row['full_name']}</button></td>
                      <td>{$row['qr_code']}</td>
                      <td>{$row['scan_time']}</td>
                    </tr>";
          }
          ?>
        </table>
      </div>
    </div>
  </div>
  </section>
<script src="https://unpkg.com/html5-qrcode"></script>
<script>
/* -------------------------
  Persistent countdown + safe scan blocking
  - On load: check server for active lock; if locked -> show countdown immediately
  - If server unavailable: fallback to localStorage check
  - While countdown active: block scans and show remaining time
--------------------------*/

const studentId = "<?php echo $student_id; ?>";
let scanner = null;
const ONE_HOUR_MS = 60 * 60 * 1000;

let lockedUntil = null; // timestamp (ms) when current lock ends, or null
let countdownTimer = null;

// Ensure countdown element
let countdownElem = document.getElementById('qrCountdown');
if (!countdownElem) {
  countdownElem = document.createElement('p');
  countdownElem.id = 'qrCountdown';
  countdownElem.style.color = '#ff0000';
  countdownElem.style.fontWeight = 'bold';
  countdownElem.style.textAlign = 'center';
  countdownElem.style.marginTop = '10px';
  document.getElementById('reader').insertAdjacentElement('afterend', countdownElem);
}

function formatRemaining(ms) {
  const totalSec = Math.ceil(ms / 1000);
  const mins = Math.floor(totalSec / 60);
  const secs = totalSec % 60;
  return `${mins}m ${secs}s`;
}

function startCountdownTo(untilMs) {
  lockedUntil = untilMs;
  clearCountdown();
  updateCountdownOnce();
  countdownTimer = setInterval(updateCountdownOnce, 1000);
}

function updateCountdownOnce() {
  if (!lockedUntil) return clearCountdown();
  const now = Date.now();
  const diff = lockedUntil - now;
  if (diff <= 0) {
    clearCountdown();
    countdownElem.innerText = '';
    lockedUntil = null;
    cleanupLocalLocks();
    document.getElementById("scanResult").innerText = " Ready to scan.";
    return;
  }
  countdownElem.innerText = `⏳ Wait ${formatRemaining(diff)} before next allowed scan.`;
}

function clearCountdown() {
  if (countdownTimer) {
    clearInterval(countdownTimer);
    countdownTimer = null;
  }
}

function saveLocalLock(qrCode, lastTimeMs) {
  try {
    const key = `qrscan_ts_${studentId}_${encodeURIComponent(qrCode)}`;
    localStorage.setItem(key, String(lastTimeMs));
  } catch(e){ /* ignore */ }
}

function findLocalLock() {
  try {
    const now = Date.now();
    let foundUntil = null;
    for (let i = 0; i < localStorage.length; i++) {
      const k = localStorage.key(i);
      if (!k) continue;
      if (k.startsWith(`qrscan_ts_${studentId}_`)) {
        const ts = parseInt(localStorage.getItem(k) || "0", 10);
        if (!ts) { localStorage.removeItem(k); continue; }
        const until = ts + ONE_HOUR_MS;
        if (until > now) {
          if (!foundUntil || until > foundUntil) foundUntil = until;
        } else {
          localStorage.removeItem(k);
        }
      }
    }
    return foundUntil;
  } catch(e) { return null; }
}

function cleanupLocalLocks() {
  try {
    const now = Date.now();
    const toRemove = [];
    for (let i = 0; i < localStorage.length; i++) {
      const k = localStorage.key(i);
      if (!k) continue;
      if (k.startsWith(`qrscan_ts_${studentId}_`)) {
        const ts = parseInt(localStorage.getItem(k) || "0", 10);
        if (!ts) toRemove.push(k);
        else if (ts + ONE_HOUR_MS <= now) toRemove.push(k);
      }
    }
    toRemove.forEach(k => localStorage.removeItem(k));
  } catch(e) { /* ignore */ }
}


window.addEventListener("load", () => {
  fetch("verify_scan.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `student_id=${encodeURIComponent(studentId)}&action=check`
  })
  .then(resp => resp.json().catch(() => { throw new Error("Invalid JSON"); }))
  .then(data => {
    if (data && data.status === "locked") {
      let lastTimeMs = data.last_time;
      if (lastTimeMs && String(lastTimeMs).length === 10) lastTimeMs = lastTimeMs * 1000;
      // lockedUntil = lastTimeMs + ONE_HOUR_MS
      const until = (lastTimeMs || Date.now()) + ONE_HOUR_MS;
      startCountdownTo(until);
      document.getElementById("scanResult").innerText = `⚠ Countdown active — will allow scans after ${new Date(until).toLocaleTimeString()}.`;
    } else {
      const localUntil = findLocalLock();
      if (localUntil) {
        startCountdownTo(localUntil);
        document.getElementById("scanResult").innerText = `⚠ Countdown (local) active — will allow scans after ${new Date(localUntil).toLocaleTimeString()}.`;
      } else {
        document.getElementById("scanResult").innerText = " Ready to scan QR code.";
      }
    }
  })
  .catch(err => {
    console.warn("Server check failed:", err);
    const localUntil = findLocalLock();
    if (localUntil) {
      startCountdownTo(localUntil);
      document.getElementById("scanResult").innerText = `⚠ Countdown (local) active — will allow scans after ${new Date(localUntil).toLocaleTimeString()}.`;
    } else {
      document.getElementById("scanResult").innerText = "Ready to scan QR code.";
    }
  });
});


document.getElementById("startScan").addEventListener("click", () => {
  if (lockedUntil && lockedUntil > Date.now()) {
    alert(`... You must wait ${formatRemaining(lockedUntil - Date.now())} before scanning.`);
    return;
  }
  try {
    scanner = new Html5Qrcode("reader");
    scanner.start(
      { facingMode: "environment" },
      { fps: 10, qrbox: { width: 250, height: 250 } },
      (decodedText) => onDecoded(decodedText),
      (error) => { /* ignore per-frame decode errors */ }
    ).catch(err => alert("Camera Error: " + err));
  } catch(e) {
    alert("Camera init error: " + e);
  }
});

document.getElementById("stopScan").addEventListener("click", () => {
  if (scanner) {
    scanner.stop().then(() => {
      document.getElementById("scanResult").innerText = "Camera stopped.";
    }).catch(err => alert("Stop Error: " + err));
  }
});


function onDecoded(code) {
  if (lockedUntil && lockedUntil > Date.now()) {
    const rem = lockedUntil - Date.now();
    alert(` Wait ${formatRemaining(rem)} before scanning this QR again.`);
    return;
  }

  document.getElementById("scanResult").innerText = "⏳ Verifying...";

  fetch("verify_scan.php", {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: `student_id=${encodeURIComponent(studentId)}&qr_code=${encodeURIComponent(code)}`
  })
  .then(resp => resp.json().catch(async () => {
    // Not JSON — show server HTML for debugging
    const text = await resp.text();
    throw new Error("Invalid server response: " + text.slice(0,200));
  }))
  .then(data => {
    if (data.status === "locked") {
      let lastTimeMs = data.last_time;
      if (lastTimeMs && String(lastTimeMs).length === 10) lastTimeMs = lastTimeMs * 1000;
      const until = (lastTimeMs || Date.now()) + ONE_HOUR_MS;
      startCountdownTo(until);
      document.getElementById("scanResult").innerText = `⚠ Locked — try again after ${new Date(until).toLocaleTimeString()}`;
      
      saveLocalLock(code, lastTimeMs || Date.now());
      return;
    }

    if (data.status === "success") {
     
      const nowMs = Date.now();
      saveLocalLock(code, nowMs);
      startCountdownTo(nowMs + ONE_HOUR_MS);
      document.getElementById("scanResult").innerText = " Scan recorded. Countdown started.";
    
      if (isURL(code)) window.open(code, "_blank");
      else showPopup(`<div style='text-align:center;font-family:Segoe UI;'>
                       <h3>QR Code Data</h3>
                       <p style='word-wrap:break-word;'>${escapeHtml(code)}</p>
                     </div>`);
      return;
    }

    document.getElementById("scanResult").innerText = `Server: ${data.message || 'unknown'}`;
  })
  .catch(err => {
    console.error(err);
    alert("Server error: " + err.message);
    document.getElementById("scanResult").innerText = "⚠Server error. Try again.";
  });
}


function isURL(str) { return /^(https?:\/\/[^\s]+)/i.test(str); }
function escapeHtml(unsafe) {
  return unsafe.replace(/&/g,"&amp;").replace(/</g,"&lt;")
               .replace(/>/g,"&gt;").replace(/"/g,"&quot;")
               .replace(/'/g,"&#039;");
}
function showPopup(htmlContent) {
  const overlay = document.createElement("div");
  overlay.style.cssText = "position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.3);display:flex;justify-content:center;align-items:center;z-index:9999;";
  const box = document.createElement("div");
  box.style.cssText = "background:#fff;padding:20px;border-radius:10px;max-width:400px;color:#000;";
  box.innerHTML = htmlContent + `<br><button id="closePopupBtn" style="margin-top:10px;padding:8px 20px;background:#ffcc00;border:none;border-radius:6px;cursor:pointer;font-weight:bold;">Close</button>`;
  overlay.appendChild(box);
  document.body.appendChild(overlay);
  document.getElementById("closePopupBtn").addEventListener("click", () => document.body.removeChild(overlay));
}

const hamburger = document.getElementById("hamburger");
const mobileOverlay = document.getElementById("mobileMenuOverlay");
const closeMenu = document.getElementById("closeMenu");
hamburger.addEventListener("click", () => mobileOverlay.classList.add("show"));
closeMenu.addEventListener("click", () => mobileOverlay.classList.remove("show"));
mobileOverlay.addEventListener("click", (e) => {
  if (e.target === mobileOverlay) mobileOverlay.classList.remove("show");
});
</script>

</body>
</html>
