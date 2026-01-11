<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: admin~login.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../../API/db.connect.php");

// Initialize filter variables
$filter_admission = isset($_GET['admission_no']) ? $_GET['admission_no'] : '';
$filter_date = isset($_GET['date']) ? $_GET['date'] : '';
$filter_unit = isset($_GET['unit_code']) ? $_GET['unit_code'] : '';
$filter_class_time = isset($_GET['class_time']) ? $_GET['class_time'] : '';

// Build WHERE clause
$where_conditions = ["1=1"];
$params = [];

if (!empty($filter_admission)) {
    $where_conditions[] = "a.student_id LIKE ?";
    $params[] = "%$filter_admission%";
}

if (!empty($filter_date)) {
    $where_conditions[] = "DATE(a.timestamp) = ?";
    $params[] = $filter_date;
}

if (!empty($filter_unit)) {
    $where_conditions[] = "a.unit_code = ?";
    $params[] = $filter_unit;
}

if (!empty($filter_class_time)) {
    $where_conditions[] = "a.class_time = ?";
    $params[] = $filter_class_time;
}

$where_clause = implode(" AND ", $where_conditions);

// Fetch Attendance Records - FIXED: Use student_id directly (which now contains admission_no)
$query = "
    SELECT s.full_name AS student, s.admission_no, 
           a.status, a.timestamp, a.unit_code, a.class_time
    FROM attendance a
    JOIN students s ON a.student_id = s.admission_no
    WHERE $where_clause
    ORDER BY a.timestamp DESC
";

$stmt = $conn->prepare($query);
if ($stmt) {
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $attendance = $stmt->get_result();
} else {
    $attendance = $conn->query($query);
}

// Summary Counts - FIXED: Use student_id directly (which now contains admission_no)
$summary_query = "
    SELECT status, COUNT(*) as total
    FROM attendance a
    JOIN students s ON a.student_id = s.admission_no
    WHERE $where_clause
    GROUP BY status
";

$summary_stmt = $conn->prepare($summary_query);
if ($summary_stmt) {
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $summary_stmt->bind_param($types, ...$params);
    }
    $summary_stmt->execute();
    $summary = $summary_stmt->get_result();
} else {
    $summary = $conn->query($summary_query);
}

$counts = ["present" => 0, "absent" => 0, "excused" => 0];
if ($summary) {
    while ($row = $summary->fetch_assoc()) {
        $counts[strtolower($row['status'])] = $row['total'];
    }
}

// Get total records count - FIXED: Use student_id directly (which now contains admission_no)
$total_query = "
    SELECT COUNT(*) as total
    FROM attendance a
    JOIN students s ON a.student_id = s.admission_no
    WHERE $where_clause
";
$total_stmt = $conn->prepare($total_query);
if ($total_stmt) {
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $total_stmt->bind_param($types, ...$params);
    }
    $total_stmt->execute();
    $total_result = $total_stmt->get_result();
    $total_records = $total_result->fetch_assoc()['total'];
} else {
    $total_result = $conn->query($total_query);
    $total_records = $total_result->fetch_assoc()['total'];
}

// Get unique units and class times for filters
$units_query = "SELECT DISTINCT unit_code FROM attendance WHERE unit_code IS NOT NULL AND unit_code != '' ORDER BY unit_code";
$units_result = $conn->query($units_query);
$units = [];
while ($unit = $units_result->fetch_assoc()) {
    $units[] = $unit['unit_code'];
}

$class_times_query = "SELECT DISTINCT class_time FROM attendance WHERE class_time IS NOT NULL AND class_time != '' ORDER BY class_time";
$class_times_result = $conn->query($class_times_query);
$class_times = [];
while ($class_time = $class_times_result->fetch_assoc()) {
    $class_times[] = $class_time['class_time'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance Reports - Admin</title>
  <link rel="stylesheet" href="../../CSS/Admin.CSS">
  <style>
    table { width:100%; border-collapse:collapse; margin-top:15px; }
    th, td { padding:12px; border-bottom:1px solid rgba(255,255,255,0.1); text-align:left; }
    th { background:rgba(255,255,255,0.1); color:#ffcc00; }
    tr:hover { background:rgba(255,255,255,0.05); }
    .summary { display:flex; gap:15px; margin:20px 0; }
    .summary div { background:rgba(255,255,255,0.1); padding:20px; border-radius:8px; flex:1; text-align:center; }
    .summary-number { font-size:24px; font-weight:bold; margin-bottom:5px; }
    .filters { background:rgba(255,255,255,0.1); padding:20px; border-radius:8px; margin-bottom:20px; }
    .filter-group { display:flex; gap:15px; align-items:end; flex-wrap:wrap; }
    .filter-item { display:flex; flex-direction:column; }
    .filter-item label { margin-bottom:5px; font-weight:bold; }
    .filter-item input, .filter-item select { padding:10px; border-radius:6px; border:1px solid #ccc; }
    .btn { background:#ffcc00; color:#000; font-weight:bold; padding:10px 20px; border:none; border-radius:6px; cursor:pointer; }
    .btn:hover { background:#e6b800; }
    .btn-reset { background:#6c757d; color:white; }
      
    .btn-reset:hover { background:#5a6268; }
    .status-present { color:#4CAF50; font-weight:bold; }
    .status-absent { color:#f44336; font-weight:bold; }
    .status-excused { color:#FF9800; font-weight:bold; }
    .total-records { margin:10px 0; color:#ffcc00; font-weight:bold; }
    .unit-code { color:#2196F3; font-weight:bold; }
    .class-time { color:#9C27B0; font-weight:bold; }
 
    .timezone-indicator { 
        background: rgba(255, 204, 0, 0.2); 
        color: #ffcc00; 
        padding: 4px 8px; 
        border-radius: 4px; 
        font-size: 12px; 
        margin-left: 5px;
    }
      @media (max-width: 768px) {
    .workspace .card {
        padding: 15px;
        max-width: 95%;
    }

    .filters .filter-group {
        flex-direction: column;
        gap: 10px;
    }

    .filters .filter-item {
        width: 100%;
    }

    table {
        font-size: 13px;
    }

    th, td {
        padding: 8px 6px;
    }

    .summary {
        flex-direction: column;
        gap: 10px;
    }

    .summary div {
        font-size: 14px;
        padding: 12px;
    }
    
    .timezone-indicator {
        display: block;
        margin-left: 0;
        margin-top: 2px;
    }
}
      .table-container {
  width: 100%;
  overflow-x: auto;
  -webkit-overflow-scrolling: touch;
}

.table-container table {
  min-width: 700px; /* ensures proper scroll on small devices */
}

@media (max-width: 768px) {
  .table-container {
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 6px;
  }
}

  </style>
</head>
<body>
  <!-- Navbar -->
  <div class="navbar">
    <h2 class="logo">Admin</h2>
    <nav class="menu">
      <a href="admin.php">Dashboard</a>
      <a href="Reports.php" class="active">Attendance Reports</a>
      <a href="Dev~Reg.php">Registration</a>
      <a href="Device.php">Devices</a>
      <a href="Logs.php">System Logs</a>
      <a href="admin~logout.php">Logout</a>
    </nav>
    <div class="hamburger" id="hamburger">&#9776;</div>
  </div>

  <!-- Mobile Menu -->
  <nav class="mobile-menu" id="mobileMenu">
    <a href="admin.php">Dashboard</a>
    <a href="Reports.php" class="active">Attendance Reports</a>
    <a href="Dev~Reg.php">Registration</a>
    <a href="Device.php">Devices</a>
    <a href="Logs.php">System Logs</a>
    <a href="admin~logout.php">Logout</a>
  </nav>

  <!-- Workspace -->
  <div class="workspace">
    <div class="card" style="grid-column: span 2;">
      <h3>Attendance Reports</h3>
      
      <!-- Filters -->
      <div class="filters">
        <h4>Filter Results</h4>
        <form method="GET" class="filter-group">
          <div class="filter-item">
            <label for="admission_no">Admission Number</label>
            <input type="text" id="admission_no" name="admission_no" 
                   value="<?php echo htmlspecialchars($filter_admission); ?>" 
                   placeholder="Enter admission number...">
          </div>
          
          <div class="filter-item">
            <label for="date">Date</label>
            <input type="date" id="date" name="date" 
                   value="<?php echo htmlspecialchars($filter_date); ?>">
          </div>
          
          <div class="filter-item">
            <label for="unit_code">Unit Code</label>
            <select id="unit_code" name="unit_code">
              <option value="">All Units</option>
              <?php foreach ($units as $unit): ?>
                <option value="<?php echo htmlspecialchars($unit); ?>" 
                        <?php echo $filter_unit == $unit ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($unit); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="filter-item">
            <label for="class_time">Class Time</label>
            <select id="class_time" name="class_time">
              <option value="">All Class Times</option>
              <?php foreach ($class_times as $time): ?>
                <option value="<?php echo htmlspecialchars($time); ?>" 
                        <?php echo $filter_class_time == $time ? 'selected' : ''; ?>>
                  <?php echo htmlspecialchars($time); ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          
          <div class="filter-item">
            <button type="submit" class="btn">Apply Filters</button>
          </div>
          
          <div class="filter-item">
            <a href="?" class="btn btn-reset">Reset</a>
          </div>
        </form>
      </div>

      <!-- Summary -->
      <div class="summary">
        <div>
          <div class="summary-number" style="color:#4CAF50;"><?php echo $counts['present']; ?></div>
          <div>Present</div>
        </div>
        <div>
          <div class="summary-number" style="color:#f44336;"><?php echo $counts['absent']; ?></div>
          <div>Absent</div>
        </div>
        <div>
          <div class="summary-number" style="color:#FF9800;"><?php echo $counts['excused']; ?></div>
          <div>Excused</div>
        </div>
        <div>
          <div class="summary-number" style="color:#ffcc00;"><?php echo $total_records; ?></div>
          <div>Total Records</div>
        </div>
      </div>

      <!-- Attendance Table -->
      <div class="total-records">
        Showing <?php echo $total_records; ?> attendance record(s)
      </div>

      <table>
        <thead>
          <tr>
            <th>Student Name</th>
            <th>Admission No</th>
            <th>Status</th>
            <th>Unit Code</th>
            <th>Class Time</th>
            <th>Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($attendance && $attendance->num_rows > 0): ?>
            <?php while ($row = $attendance->fetch_assoc()): ?>
              <tr>
                <td><?php echo htmlspecialchars($row['student']); ?></td>
                <td><strong><?php echo htmlspecialchars($row['admission_no']); ?></strong></td>
                <td class="status-<?php echo $row['status']; ?>">
                  <?php echo ucfirst($row['status']); ?>
                </td>
                <td>
                  <?php if (!empty($row['unit_code'])): ?>
                    <span class="unit-code"><?php echo htmlspecialchars($row['unit_code']); ?></span>
                  <?php else: ?>
                    <span style="color:#666; font-style:italic;">Not set</span>
                  <?php endif; ?>
                </td>
                <td>
                  <?php if (!empty($row['class_time'])): ?>
                    <span class="class-time"><?php echo htmlspecialchars($row['class_time']); ?></span>
                  <?php else: ?>
                    <span style="color:#666; font-style:italic;">Not set</span>
                  <?php endif; ?>
                </td>
                <td><?php echo date('M j, Y', strtotime($row['timestamp'])); ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" style="text-align:center; padding:30px;">
                No attendance records found
                <?php if (!empty($filter_admission) || !empty($filter_date) || !empty($filter_unit) || !empty($filter_class_time)): ?>
                  <br><small>Try adjusting your filters</small>
                <?php endif; ?>
              </td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <script>
    document.getElementById("hamburger").addEventListener("click", function() {
      document.getElementById("mobileMenu").classList.toggle("show");
    });

    // Set today's date as default if no date is selected
    document.addEventListener('DOMContentLoaded', function() {
      const dateInput = document.getElementById('date');
      if (!dateInput.value) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.value = today;
      }
    });
  </script>
</body>
</html>