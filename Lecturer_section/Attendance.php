<?php
session_start();

if (!isset($_SESSION['lecturer'])) {
    header("Location: Lecturer~Login.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../../API/db.connect.php");

$msg = "";

// Get lecturer info
$lecturer_email = $_SESSION['lecturer'];
$lecturer_query = $conn->query("SELECT * FROM lecturers WHERE email = '$lecturer_email'");
$lecturer = $lecturer_query->fetch_assoc();
$lecturer_unit = $lecturer ? $lecturer['unit_assigned'] : '';

$today = date("Y-m-d");

// Initialize scan blocking status
if (!isset($_SESSION['scan_blocked'])) {
    $_SESSION['scan_blocked'] = false;
}

// Handle scan blocking toggle
if (isset($_POST['toggle_scan_block'])) {
    $_SESSION['scan_blocked'] = !$_SESSION['scan_blocked'];
    $status = $_SESSION['scan_blocked'] ? 'BLOCKED' : 'ACCEPTED';
    $msg = "Scan status changed to: $status";
}

// Handle reset session
if (isset($_POST['reset_session'])) {
    // Clear all session data except login info
    $lecturer_email = $_SESSION['lecturer'];
    session_unset();
    $_SESSION['lecturer'] = $lecturer_email;
    $_SESSION['scan_blocked'] = false;
    $msg = "Session reset successfully! All filters and temporary data cleared.";
}

// Filters
$selected_unit = isset($_POST['unit_selected']) ? $_POST['unit_selected'] : $lecturer_unit;
$selected_group = isset($_POST['group_filter']) ? $_POST['group_filter'] : '';
$selected_codes = isset($_POST['code_filter']) ? $_POST['code_filter'] : '';
$class_time = isset($_POST['class_time']) ? $_POST['class_time'] : '';

// Initialize matched students array
$matched_students = [];

// ADDED: Hash comparison when Compare button is clicked (only if scans are not blocked)
if (isset($_POST['compare_hash']) && !$_SESSION['scan_blocked']) {
    $device_scans = $conn->query("
        SELECT ds.admission_no, ds.user_agent, ds.ip_address, ds.platform, 
               ds.screen_resolution, ds.language, ds.timezone, ds.timestamp 
        FROM device_scans ds 
        WHERE DATE(ds.timestamp) = '$today'
        ORDER BY ds.timestamp DESC
    ");
    
    $comparison_count = 0;
    $match_count = 0;
    
    if ($device_scans && $device_scans->num_rows > 0) {
        while ($scan = $device_scans->fetch_assoc()) {
            $admission_no = $conn->real_escape_string($scan['admission_no']);
            $device_string = $scan['user_agent'] . $scan['platform'] . $scan['screen_resolution'] . 
                            $scan['language'] . $scan['timezone'];
            $generated_hash = hash('sha256', $device_string);
            $match_status = 'mismatched';
            
            $device_check = $conn->query("
                SELECT id, admission_no, hash_256, status 
                FROM devices 
                WHERE admission_no = '$admission_no' 
                AND status IN ('active', 'pending')
                AND hash_256 = '$generated_hash'
                LIMIT 1
            ");
            
            if ($device_check && $device_check->num_rows > 0) {
                $device = $device_check->fetch_assoc();
                $match_status = 'matched';
                $match_count++;
                
                // Store matched student admission numbers
                $matched_students[] = $admission_no;
            } else {
                $match_status = 'mismatched';
            }
            
            // Store comparison data
            $conn->query("
                INSERT INTO device_comparison 
                (admission_no, hash_256, match_status, scanned_data) 
                VALUES ('$admission_no', '$generated_hash', '$match_status', '$device_string')
            ");
            
            $comparison_count++;
        }
        
        $msg = "Hash comparison completed! Compared $comparison_count devices. Found $match_count matches.";
        
        // Store matched students in session to maintain state after page reload
        $_SESSION['matched_students'] = $matched_students;
        $_SESSION['last_comparison_time'] = time();
        
    } else {
        $msg = "No device scans found for today to compare.";
        unset($_SESSION['matched_students']);
    }
} elseif (isset($_POST['compare_hash']) && $_SESSION['scan_blocked']) {
    $msg = "Scan blocking is active. Cannot perform comparison while scans are blocked.";
}

// Build student query - use admission_no directly (from second page)
$student_query = "
    SELECT s.admission_no, s.full_name, 
           (SELECT a.status FROM attendance a 
            WHERE a.student_id = s.admission_no 
            AND a.unit_code = '$selected_unit'
            AND a.class_time = '$class_time'
            ORDER BY a.timestamp DESC LIMIT 1) as today_status 
    FROM students s 
";

$where_added = false;

if (!empty($selected_group)) {
    $student_query .= " WHERE s.admission_no LIKE '%$selected_group%'";
    $where_added = true;
}

// Apply code filter if selected - now using text input
if (!empty($selected_codes)) {
    // Split by comma or space and trim each code
    $codes_array = preg_split('/[\s,]+/', $selected_codes);
    $code_conditions = [];
    
    foreach ($codes_array as $code) {
        $trimmed_code = trim($code);
        if (!empty($trimmed_code)) {
            $escaped_code = $conn->real_escape_string($trimmed_code);
            $code_conditions[] = "s.admission_no LIKE '%$escaped_code%'";
        }
    }
    
    if (!empty($code_conditions)) {
        if ($where_added) {
            $student_query .= " AND (" . implode(' OR ', $code_conditions) . ")";
        } else {
            $student_query .= " WHERE (" . implode(' OR ', $code_conditions) . ")";
            $where_added = true;
        }
    }
}

$student_query .= " ORDER BY s.full_name ASC";
$students = $conn->query($student_query);

// SIMPLIFIED SAVING LOGIC - Allows multiple saves like the first page
if (isset($_POST['save_attendance'])) {
    // Check if class time is selected
    if (empty($class_time)) {
        $msg = "Please select class time before saving attendance!";
    } elseif (empty($selected_unit)) {
        $msg = "Please select a unit before saving attendance!";
    } else {
        $saved_count = 0;
        
        foreach ($_POST['status'] as $admission_no => $status) {
            $admission_no = $conn->real_escape_string($admission_no);
            $status = $conn->real_escape_string($status);
            $unit_code = $conn->real_escape_string($selected_unit);
            $class_time_escaped = $conn->real_escape_string($class_time);
            
            // ALWAYS INSERT NEW RECORD - allows multiple saves (from second page)
            $result = $conn->query("
                INSERT INTO attendance (student_id, status, timestamp, unit_code, class_time) 
                VALUES ('$admission_no', '$status', NOW(), '$unit_code', '$class_time_escaped')
            ");
            
            if ($result) {
                $saved_count++;
            }
        }
        
        // ADDED: Delete device scan data after saving attendance
        $delete_result = $conn->query("DELETE FROM device_scans WHERE DATE(timestamp) = '$today'");
        $deleted_count = $conn->affected_rows;
        
        $msg = "Attendance recorded successfully for Unit: $selected_unit | Class Time: $class_time ($saved_count records saved)";
        $msg .= "<br>Device scan data cleared ($deleted_count records deleted).";
        
        // Clear matched students after saving
        unset($_SESSION['matched_students']);
    }
}

// Get stats for the selected unit - show latest records only (from second page)
$stats_query = "
    SELECT status, COUNT(*) as count 
    FROM (
        SELECT a1.status 
        FROM attendance a1 
        WHERE a1.unit_code = '$selected_unit'
        AND a1.class_time = '$class_time'
        AND a1.timestamp = (
            SELECT MAX(a2.timestamp) 
            FROM attendance a2 
            WHERE a2.student_id = a1.student_id 
            AND a2.unit_code = '$selected_unit'
            AND a2.class_time = '$class_time'
        )
    ) AS latest_attendance 
    GROUP BY status
";
$stats = $conn->query($stats_query);

$present = $absent = $excused = 0;
if ($stats) {
    while ($stat = $stats->fetch_assoc()) {
        if ($stat['status'] == 'present') $present = $stat['count'];
        if ($stat['status'] == 'absent') $absent = $stat['count'];
        if ($stat['status'] == 'excused') $excused = $stat['count'];
    }
}
$total = $present + $absent + $excused;

// Get groups for filter dropdown
$groups_result = $conn->query("
    SELECT DISTINCT SUBSTRING(admission_no, 1, 3) as group_code 
    FROM students 
    ORDER BY group_code
");
$groups = [];
if ($groups_result) {
    while ($group = $groups_result->fetch_assoc()) {
        $groups[] = $group['group_code'];
    }
}

// Get unit codes and names from 'units' table for dropdown
$units_result = $conn->query("SELECT unit_code, unit_name FROM units ORDER BY unit_name");
$units = [];
if ($units_result) {
    while ($unit = $units_result->fetch_assoc()) {
        $units[] = $unit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lecturer Dashboard</title>
  <link rel="stylesheet" href="../../CSS/Admin.CSS">
  <style>
    .workspace { display:flex; flex-direction:column; gap:20px; padding:20px; }
    .btn { display:inline-block; background:#ffcc00; color:#000; font-weight:bold; padding:12px 24px; border:none; border-radius:8px; cursor:pointer; margin-top:10px; transition:0.3s; }
    .btn:hover { background:#e6b800; }
    table { width:100%; border-collapse:collapse; margin-top:10px; }
    th, td { padding:10px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.1); }
    th { background: rgba(255,255,255,0.1); }
    select { padding:6px; border-radius:6px; border:none; }
    .status-present { color: #4CAF50; font-weight: bold; }
    .status-absent { color: #f44336; font-weight: bold; }
    .status-excused { color: #FF9800; font-weight: bold; }
    #slideshow img { max-width:100%; border-radius:8px; margin-top:10px; cursor:pointer; }
    #fullscreenContainer {
      display:none;
      position:fixed;
      top:0; left:0;
      width:100%; height:100%;
      background:#000;
      justify-content:center;
      align-items:center;
      z-index:9999;
    }
    #fullscreenContainer img {
      max-width:95%;
      max-height:95%;
      cursor:pointer;
    }
    .stats { display: flex; gap: 15px; margin-bottom: 20px; }
    .stat-card { background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; flex: 1; text-align: center; }
    .stat-number { font-size: 24px; font-weight: bold; }
    .message { padding: 15px; border-radius: 8px; margin: 10px 0; }
    .message.success { background: rgba(76, 175, 80, 0.2); border-left: 4px solid #4CAF50; }
    .message.info { background: rgba(33, 150, 243, 0.2); border-left: 4px solid #2196F3; }
    .message.warning { background: rgba(255, 152, 0, 0.2); border-left: 4px solid #FF9800; }
    .filter-section { background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    .filter-row { display: flex; gap: 15px; margin-bottom: 10px; align-items: end; }
    .filter-group { flex: 1; }
    .filter-group label { display: block; margin-bottom: 5px; font-weight: bold; }
    .filter-group select, .filter-group input { width: 100%; padding: 8px; border-radius: 6px; border: none; }
    .code-input { margin-top: 5px; }
    .code-input input { width: 100%; padding: 8px; border-radius: 6px; border: none; }
    .code-hint { font-size: 12px; color: #ccc; margin-top: 5px; }
    .apply-filters { margin-top: 10px; }
    .current-selection { background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: bold; }
    .class-time-section { background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; margin-bottom: 20px; }
    .class-time-buttons { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; margin-top: 10px; }
    .class-time-btn { background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.3); color: white; padding: 12px; border-radius: 8px; cursor: pointer; transition: all 0.3s ease; text-align: center; }
    .class-time-btn:hover, .class-time-btn.active { background: #2196F3; color: #000; transform: scale(1.05); }
    .compare-btn { background: #2196F3; color: white; margin-right: 10px; }
    .compare-btn:hover { background: #1976D2; }
    .block-btn { background: #f44336; color: white; margin-right: 10px; }
    .block-btn:hover { background: #d32f2f; }
    .accept-btn { background: #4CAF50; color: white; margin-right: 10px; }
    .accept-btn:hover { background: #45a049; }
    .reset-btn { background: #FF9800; color: white; margin-right: 10px; }
    .reset-btn:hover { background: #e68900; }
    .auto-present { background-color: rgba(76, 175, 80, 0.1) !important; border-left: 3px solid #4CAF50 !important; }
    .scan-status { 
        display: inline-block; 
        padding: 8px 16px; 
        border-radius: 20px; 
        font-weight: bold; 
        margin-left: 10px;
        font-size: 14px;
    }
    .scan-status.blocked { 
        background: #f44336; 
        color: white;
    }
    .scan-status.accepted { 
        background: #4CAF50; 
        color: white;
    }
    .control-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 15px;
        flex-wrap: wrap;
    }
      @media (max-width: 768px) {
  .workspace {
    padding: 10px;
    gap: 15px;
  }

  .stats {
    flex-direction: column;
    gap: 10px;
  }

  .stat-card {
    flex: 1 1 100%;
    font-size: 14px;
    padding: 10px;
  }

  .card {
    padding: 15px;
  }

  #slideshow img {
    max-width: 100%;
    height: auto;
  }

  table {
    display: block;
    width: 100%;
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  th, td {
    padding: 8px 6px;
    font-size: 13px;
  }

  select {
    width: 100%;
    margin-top: 5px;
    margin-bottom: 5px;
  }

  .btn {
    width: 100%;
    padding: 10px;
    font-size: 14px;
    margin-top: 8px;
  }

  .filter-row {
    flex-direction: column;
    gap: 10px;
  }

  .filter-group {
    width: 100%;
  }

  .class-time-buttons {
    grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
  }

  .control-buttons {
    flex-direction: column;
  }
}

  </style>
</head>
<body>

<div class="navbar">
  <h2 class="logo">Dashboard</h2>
  <nav class="menu">
   <a href="Attendance.php" class="active">QR Management</a>
   <a href="device.scan.php">Device Data</a>
   <a href="hash_comparison.php">Hash Comparison</a>
   <a href="Lecturer~Logout.php">Logout</a>
  </nav>
  <div class="hamburger" id="hamburger">&#9776;</div>
</div>

<nav class="mobile-menu" id="mobileMenu">
  <a href="Attendance.php" class="active">QR Management</a>
  <a href="device.scan.php">Device Data</a>
  <a href="hash_comparison.php">Hash Comparison</a>
  <a href="Lecturer~Logout.php">Logout</a>
</nav>

<div class="workspace">

  <div class="stats">
    <div class="stat-card">
      <div class="stat-number"><?php echo $present; ?></div>
      <div>Present</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?php echo $absent; ?></div>
      <div>Absent</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?php echo $excused; ?></div>
      <div>Excused</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?php echo $total; ?></div>
      <div>Total</div>
    </div>
  </div>

  <?php if (!empty($msg)): ?>
    <div class="message <?php echo strpos($msg, 'blocked') !== false ? 'warning' : 'success'; ?>">
      <?php echo $msg; ?>
    </div>
  <?php endif; ?>

  <!-- ADDED: Control Buttons Section -->
  <div class="card">
    <h3>System Controls</h3>
    <div class="control-buttons">
      <form method="POST" style="display: inline;">
        <button type="submit" class="btn <?php echo $_SESSION['scan_blocked'] ? 'accept-btn' : 'block-btn'; ?>" name="toggle_scan_block">
          <?php echo $_SESSION['scan_blocked'] ? 'Accept Scans' : 'Block Scans'; ?>
        </button>
        <span class="scan-status <?php echo $_SESSION['scan_blocked'] ? 'blocked' : 'accepted'; ?>">
          Scans: <?php echo $_SESSION['scan_blocked'] ? 'BLOCKED' : 'ACCEPTED'; ?>
        </span>
      </form>
      
      <form method="POST" style="display: inline;">
        <button type="submit" class="btn reset-btn" name="reset_session">Reset Session</button>
      </form>
    </div>
    <div style="font-size: 12px; color: #ccc; margin-top: 10px;">
      <strong>Block Scans:</strong> Prevents any new device scans from being processed<br>
      <strong>Reset Session:</strong> Clears all filters and temporary data without logging out
    </div>
  </div>

  <div class="card">
    <h3>QR Code Management</h3>
    <input type="file" id="qrUpload" accept="image/*" multiple><br>

    <label>Slide Interval (seconds)</label>
    <select id="qrInterval" style="padding:10px;width:100%;border-radius:6px;margin-top:5px;">
      <option value="2">2 Seconds</option>
      <option value="4">4 Seconds</option>
      <option value="6">6 Seconds</option>
      <option value="8">8 Seconds</option>
      <option value="10">10 Seconds</option>
    </select>

    <button type="button" class="btn" id="uploadBtn">Upload & Start Slideshow</button>
    <button type="button" class="btn btn-info" id="fullscreenBtn">Fullscreen</button>

    <div id="slideshow"><img id="slideImage" src=""></div>
  </div>

  <div class="card">
    <h3>Attendance Management</h3>
    
    <!-- Current Selection Display -->
    <div class="current-selection">
      <?php if ($selected_unit): ?>
        <div>Selected Unit: <span style="color: #ffcc00;"><?php echo htmlspecialchars($selected_unit); ?></span></div>
      <?php endif; ?>
      <?php if ($class_time): ?>
        <div>Selected Class Time: <span style="color: #2196F3;"><?php echo htmlspecialchars($class_time); ?></span></div>
      <?php endif; ?>
    </div>

    <!-- Filter Section -->
    <div class="filter-section">
      <form method="POST" id="filterForm">
        <div class="filter-row">
          <div class="filter-group">
            <label for="unit_selected">Select Unit:</label>
            <select id="unit_selected" name="unit_selected">
              <option value="">Select Unit</option>
              <?php foreach ($units as $unit): ?>
                <option value="<?php echo $unit['unit_code']; ?>" <?php echo $selected_unit == $unit['unit_code'] ? 'selected' : ''; ?>>
                  <?php echo $unit['unit_name']; ?> (<?php echo $unit['unit_code']; ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="group_filter">Filter by Group:</label>
            <select id="group_filter" name="group_filter">
              <option value="">All Groups</option>
              <?php foreach ($groups as $group): ?>
                <option value="<?php echo $group; ?>" <?php echo $selected_group == $group ? 'selected' : ''; ?>>
                  <?php echo $group; ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="filter-group">
            <label for="code_filter">Filter by Student Codes:</label>
            <div class="code-input">
              <input type="text" id="code_filter" name="code_filter" 
                     value="<?php echo htmlspecialchars($selected_codes); ?>" 
                     placeholder="Enter admission numbers (separate by comma or space)">
              <div class="code-hint">Example: ABC123, DEF456, GHI789 or ABC123 DEF456 GHI789</div>
            </div>
          </div>
        </div>
        
        <!-- Class Time Selection -->
        <div class="class-time-section">
          <h4>Select Class Time</h4>
          <div class="class-time-buttons">
            <button type="submit" name="class_time" value="Morning (8:00-11:00)" 
                    class="class-time-btn <?php echo $class_time == 'Morning (8:00-11:00)' ? 'active' : ''; ?>">
              Morning (8:00-11:00)
            </button>
            <button type="submit" name="class_time" value="Afternoon (11:00-14:00)" 
                    class="class-time-btn <?php echo $class_time == 'Afternoon (11:00-14:00)' ? 'active' : ''; ?>">
              Afternoon (11:00-14:00)
            </button>
            <button type="submit" name="class_time" value="Evening (14:00-17:00)" 
                    class="class-time-btn <?php echo $class_time == 'Evening (14:00-17:00)' ? 'active' : ''; ?>">
              Evening (14:00-17:00)
            </button>
          </div>
        </div>
        
        <button type="submit" class="btn apply-filters">Apply Filters</button>
      </form>
    </div>

    <form method="POST" id="attendanceForm">
      <!-- Include current filters in the form -->
      <input type="hidden" name="unit_selected" value="<?php echo htmlspecialchars($selected_unit); ?>">
      <input type="hidden" name="group_filter" value="<?php echo htmlspecialchars($selected_group); ?>">
      <input type="hidden" name="class_time" value="<?php echo htmlspecialchars($class_time); ?>">
      <input type="hidden" name="code_filter" value="<?php echo htmlspecialchars($selected_codes); ?>">

      <table>
        <thead>
          <tr>
            <th>Student Name</th>
            <th>Admission No</th>
            <th>Latest Status</th>
            <th>Manual Update</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($students && $students->num_rows > 0): ?>
            <?php 
            // Get matched students from session
            $current_matched_students = isset($_SESSION['matched_students']) ? $_SESSION['matched_students'] : [];
            
            while($row = $students->fetch_assoc()){ 
              $today_status = $row['today_status'] ? $row['today_status'] : 'absent';
              
              // AUTO-UPDATE: If student is in matched list and was absent, change to present
              $display_status = $today_status;
              $is_auto_present = false;
              
              if (in_array($row['admission_no'], $current_matched_students) && $today_status == 'absent') {
                $display_status = 'present';
                $is_auto_present = true;
              }
              
              $status_class = 'status-' . strtolower($display_status);
              if ($is_auto_present) {
                $status_class .= ' auto-present';
              }
            ?>
            <tr class="<?php echo $is_auto_present ? 'auto-present' : ''; ?>">
              <td><?php echo htmlspecialchars($row['full_name']); ?></td>
              <td><?php echo htmlspecialchars($row['admission_no']); ?></td>
              <td class="<?php echo $status_class; ?>">
                <?php echo ucfirst($display_status); ?>
                <?php if ($is_auto_present): ?>
                  <span style="font-size: 10px; color: #4CAF50;">(Auto)</span>
                <?php endif; ?>
              </td>
              <td>
                <!-- Use admission_no as the key (from second page) -->
                <select name="status[<?php echo $row['admission_no']; ?>]">
                  <option value="present" <?php echo $display_status == 'present' ? 'selected' : ''; ?>>Present</option>
                  <option value="absent" <?php echo $display_status == 'absent' ? 'selected' : ''; ?>>Absent</option>
                  <option value="excused" <?php echo $display_status == 'excused' ? 'selected' : ''; ?>>Excused</option>
                </select>
              </td>
            </tr>
            <?php } ?>
          <?php else: ?>
            <tr>
              <td colspan="4" style="text-align: center;">No students found with the selected filters.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
      
      <?php if ($students && $students->num_rows > 0): ?>
        <!-- ADDED: Compare button before the Save Attendance button -->
        <button type="submit" class="btn compare-btn" name="compare_hash" id="compareHashBtn">Compare</button>
        <button type="submit" class="btn" name="save_attendance" id="saveAttendanceBtn">Save Attendance for <?php echo $selected_unit ?: 'Selected Unit'; ?><?php echo $class_time ? " - $class_time" : ''; ?></button>
      <?php endif; ?>
    </form>
  </div>

</div>

<div id="fullscreenContainer">
  <img id="fullscreenImage" src="">
</div>

<script>
document.getElementById("hamburger").addEventListener("click", function() {
  document.getElementById("mobileMenu").classList.toggle("show");
});

let images = [];
let currentIndex = 0;
let slideInterval;
let intervalSeconds = 2;

function startSlideshow() {
  if (images.length > 0) {
    currentIndex = 0;
    document.getElementById("slideImage").src = images[currentIndex];
    document.getElementById("fullscreenImage").src = images[currentIndex];
    clearInterval(slideInterval);
    slideInterval = setInterval(() => {
      currentIndex = (currentIndex + 1) % images.length;
      document.getElementById("slideImage").src = images[currentIndex];
      document.getElementById("fullscreenImage").src = images[currentIndex];
    }, intervalSeconds * 1000);
  }
}

document.getElementById("uploadBtn").addEventListener("click", function() {
  const files = document.getElementById("qrUpload").files;
  if (files.length > 10) { alert("Maximum 10 images allowed."); return; }

  images = [];
  for (let i = 0; i < files.length; i++) {
    images.push(URL.createObjectURL(files[i]));
  }

  intervalSeconds = parseInt(document.getElementById("qrInterval").value);
  startSlideshow();
});

document.getElementById("fullscreenBtn").addEventListener("click", function() {
  if (images.length > 0) {
    document.getElementById("fullscreenContainer").style.display = "flex";
  }
});

document.getElementById("fullscreenImage").addEventListener("click", function() {
  document.getElementById("fullscreenContainer").style.display = "none";
});

document.addEventListener("keydown", function(e) {
  if (e.key === "Escape") {
    document.getElementById("fullscreenContainer").style.display = "none";
  }
});

setInterval(() => {
  location.reload();
}, 30000);
</script>

</body>
</html>