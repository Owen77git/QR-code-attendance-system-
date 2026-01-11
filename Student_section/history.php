<?php
session_start();
include("../../API/db.connect.php");

if (!isset($_SESSION['student'])) {
    header("Location: student_log.php");
    exit();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

$adm = $_SESSION['student'];

$studentSql = "SELECT id, full_name, admission_no FROM students WHERE admission_no = '$adm' LIMIT 1";
$studentResult = $conn->query($studentSql);

if ($studentResult->num_rows > 0) {
    $student = $studentResult->fetch_assoc();
    $student_id = $student['id'];
    $student_name = $student['full_name'];
    $admission_no = $student['admission_no'];
} else {
    echo "<div style='background: #f44336; color: white; padding: 20px; margin: 20px; border-radius: 8px;'>";
    echo "<h3> Student Not Found</h3>";
    echo "<p><strong>Admission Number Used:</strong> " . htmlspecialchars($adm) . "</p>";
    echo "<p>Please contact administrator or try logging in again.</p>";
    echo "<a href='student_log.php' style='color: white; text-decoration: underline;'>Return to Login</a>";
    echo "</div>";
    exit();
}

// Handle filter parameters
$status_filter = $_GET['status'] ?? '';
$unit_filter = $_GET['unit'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build base query
$baseSql = "FROM attendance WHERE student_id = '$admission_no'";
$whereConditions = [];

// Add filters to query
if (!empty($status_filter)) {
    $whereConditions[] = "status = '$status_filter'";
}
if (!empty($unit_filter)) {
    $whereConditions[] = "unit_code LIKE '%$unit_filter%'";
}
if (!empty($date_from)) {
    $whereConditions[] = "DATE(timestamp) >= '$date_from'";
}
if (!empty($date_to)) {
    $whereConditions[] = "DATE(timestamp) <= '$date_to'";
}

// Build final WHERE clause
if (!empty($whereConditions)) {
    $baseSql .= " AND " . implode(" AND ", $whereConditions);
}

// FIXED: Use admission_no instead of numeric student_id
$attendanceSql = "
    SELECT status, timestamp, DATE(timestamp) as date_only, unit_code, class_time
    FROM attendance 
    WHERE student_id = '$admission_no'
    ORDER BY timestamp DESC
";
$attendanceResult = $conn->query($attendanceSql);

// FIXED: Use admission_no instead of numeric student_id
$statsSql = "
    SELECT 
        COUNT(*) as total_records,
        COUNT(DISTINCT DATE(timestamp)) as unique_days,
        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_count,
        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_count,
        SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused_count
    FROM attendance 
    WHERE student_id = '$admission_no'
";
$statsResult = $conn->query($statsSql);
$stats = $statsResult->fetch_assoc();

// Calculate percentages
$total_records = $stats['total_records'];
$present_percentage = $total_records > 0 ? round(($stats['present_count'] / $total_records) * 100, 1) : 0;
$absent_percentage = $total_records > 0 ? round(($stats['absent_count'] / $total_records) * 100, 1) : 0;
$excused_percentage = $total_records > 0 ? round(($stats['excused_count'] / $total_records) * 100, 1) : 0;
$overall_attendance = $total_records > 0 ? round(($stats['present_count'] / $total_records) * 100, 1) : 0;

$attendanceByDate = [];
$allRecords = [];

if ($attendanceResult->num_rows > 0) {
    while ($row = $attendanceResult->fetch_assoc()) {
        $date = $row['date_only'];
        if (!isset($attendanceByDate[$date])) {
            $attendanceByDate[$date] = [];
        }
        $attendanceByDate[$date][] = $row;
        $allRecords[] = $row;
    }
}

// Get unique units for filter dropdown
$unitsSql = "SELECT DISTINCT unit_code FROM attendance WHERE student_id = '$admission_no' AND unit_code IS NOT NULL ORDER BY unit_code";
$unitsResult = $conn->query($unitsSql);
$unique_units = [];
if ($unitsResult) {
    while ($unit = $unitsResult->fetch_assoc()) {
        $unique_units[] = $unit['unit_code'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Attendance History</title>
  
  <style>
     body{ 
    background:url('https://img.freepik.com/free-vector/flat-background-world-teacher-s-day-celebration_23-2150722546.jpg?semt=ais_hybrid&w=740&q=80');
    background-position:center;
    background-size: cover;
    margin: 0;
    padding: 20px;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.workspace { 
    padding: 20px; 
    max-width: 1200px; 
    margin: 0 auto;
    background: rgba(255,255,255,0.95);
    border-radius: 15px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.2);
}

.filter-section {
    background: rgba(30,60,114,0.9);
    padding: 20px;
    border-radius: 10px;
    margin-bottom: 25px;
    border-left: 5px solid #ffcc00;
}

.filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 15px;
}

.filter-group {
    display: flex;
    flex-direction: column;
}

.filter-group label {
    color: #ffcc00;
    font-weight: bold;
    margin-bottom: 8px;
    font-size: 14px;
}

.filter-group select,
.filter-group input {
    padding: 10px;
    border-radius: 8px;
    border: 1px solid rgba(255,255,255,0.3);
    background: rgba(255,255,255,0.1);
    color: white;
    font-size: 14px;
}

.filter-group select option {
    background: rgba(30,60,114,0.95);
    color: white;
}

.filter-buttons {
    display: flex;
    gap: 10px;
    margin-top: 10px;
}

.filter-btn {
    background: #ffcc00;
    color: #000;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.3s ease;
}

.filter-btn:hover {
    background: #e6b800;
}

.reset-btn {
    background: rgba(255,255,255,0.2);
    color: white;
    border: 1px solid rgba(255,255,255,0.3);
}

.reset-btn:hover {
    background: rgba(255,255,255,0.3);
}

.stats-grid { 
    display: grid; 
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); 
    gap: 15px; 
    margin: 20px 0; 
}

.stat-card { 
    background: rgba(30,60,114,0.9); 
    padding: 20px; 
    border-radius: 10px; 
    text-align: center; 
    color: white;
    border: 2px solid transparent;
    transition: transform 0.3s ease, border-color 0.3s ease;
}

.stat-card:hover {
    transform: translateY(-5px);
    border-color: #ffcc00;
}

.stat-number { 
    font-size: 28px; 
    font-weight: bold; 
    margin-bottom: 5px; 
}

.stat-label { 
    font-size: 14px; 
    opacity: 0.9; 
    margin-bottom: 8px;
}

.stat-percentage {
    font-size: 16px;
    font-weight: bold;
    padding: 4px 8px;
    border-radius: 15px;
    background: rgba(255,255,255,0.2);
}

.percentage-present { color: #4CAF50; }
.percentage-absent { color: #f44336; }
.percentage-excused { color: #FF9800; }
.percentage-overall { color: #ffcc00; }

.table-container {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
    max-height: 600px;
    overflow-y: auto;
    margin-top: 20px;
    border: 1px solid #e0e0e0;
}

table { 
    width: 100%; 
    border-collapse: collapse;
    min-width: 800px;
}

th, td { 
    padding: 15px 12px; 
    border-bottom: 1px solid #e0e0e0; 
    text-align: left; 
}

th { 
    background: rgba(30,60,114,0.9); 
    color: #ffcc00;
    font-weight: bold;
    position: sticky;
    top: 0;
    z-index: 10;
}

tr:hover { 
    background: rgba(30,60,114,0.05); 
}

.status-present { color: #4CAF50; font-weight: bold; }
.status-absent { color: #f44336; font-weight: bold; }
.status-excused { color: #FF9800; font-weight: bold; }

.date-header { 
    background: rgba(255,204,0,0.1); 
    font-weight: bold; 
    color: #2c3e50;
    border-left: 4px solid #ffcc00;
}

.update-indicator {
    font-size: 11px;
    color: #666;
    font-style: italic;
}

.no-data { 
    text-align: center; 
    padding: 40px; 
    color: #666; 
    font-style: italic; 
    background: white;
    border-radius: 10px;
    margin: 20px 0;
}

.unit-code { 
    color: #2196F3; 
    font-weight: bold; 
    font-size: 12px;
}

.class-time { 
    color: #9C27B0; 
    font-weight: bold; 
    font-size: 12px;
}

.not-set {
    color: #999; 
    font-style: italic;
    font-size: 11px;
}

.summary-section {
    background: linear-gradient(135deg, rgba(30,60,114,0.9), rgba(20,40,80,0.9));
    padding: 25px;
    border-radius: 10px;
    margin: 25px 0;
    color: white;
    text-align: center;
    border-left: 5px solid #ffcc00;
}

.overall-percentage {
    font-size: 42px;
    font-weight: bold;
    color: #ffcc00;
    margin: 10px 0;
}

.progress-bar {
    width: 100%;
    height: 20px;
    background: rgba(255,255,255,0.2);
    border-radius: 10px;
    overflow: hidden;
    margin: 15px 0;
}

.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #4CAF50, #45a049);
    border-radius: 10px;
    transition: width 0.5s ease;
}

/* Scrollbar styling */
.table-container::-webkit-scrollbar {
    width: 8px;
    height: 8px;
}

.table-container::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb {
    background: rgba(30,60,114,0.7);
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background: rgba(30,60,114,0.9);
}

/* Mobile Responsiveness */
@media (max-width: 768px) {
    .workspace {
        padding: 15px;
        margin: 10px;
    }
    
    .filter-grid {
        grid-template-columns: 1fr;
        gap: 10px;
    }
    
    .stats-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
    }
    
    .stat-card {
        padding: 15px;
    }
    
    .stat-number {
        font-size: 22px;
    }
    
    .table-container {
        max-height: 500px;
        margin: 15px -10px;
        border-radius: 0;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }
    
    table {
        min-width: 700px;
    }
    
    th, td {
        padding: 10px 8px;
        font-size: 13px;
        white-space: nowrap;
    }
    
    .filter-buttons {
        flex-direction: column;
    }
    
    .overall-percentage {
        font-size: 32px;
    }
    
    /* Horizontal scroll indicator for mobile */
    .table-container::after {
        content: "← Scroll →";
        position: absolute;
        bottom: 5px;
        right: 10px;
        background: rgba(30,60,114,0.8);
        color: white;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 10px;
        opacity: 0.7;
        pointer-events: none;
    }
}

@media (max-width: 480px) {
    .stats-grid {
        grid-template-columns: 1fr;
    }
    
    .stat-number {
        font-size: 20px;
    }
    
    .table-container {
        max-height: 400px;
        margin: 15px -15px;
        border-radius: 0;
        overflow-x: auto;
    }
    
    table {
        min-width: 600px;
    }
    
    th, td {
        padding: 8px 6px;
        font-size: 12px;
        white-space: nowrap;
    }
    
    /* Ensure horizontal scrolling is smooth on mobile */
    .table-container::-webkit-scrollbar {
        height: 6px;
    }
    
    .table-container::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 3px;
    }
    
    .table-container::-webkit-scrollbar-thumb {
        background: rgba(30,60,114,0.7);
        border-radius: 3px;
    }
    
    .table-container::-webkit-scrollbar-thumb:hover {
        background: rgba(30,60,114,0.9);
    }
}

/* For very small screens */
@media (max-width: 360px) {
    .table-container {
        max-height: 350px;
    }
    
    table {
        min-width: 550px;
    }
    
    th, td {
        padding: 6px 4px;
        font-size: 11px;
    }
}
  </style>
</head>
<body>
<div class="workspace">
  <h2 style="color: #2c3e50; text-align: center; margin-bottom: 10px;">Complete Attendance History</h2>
  
  <p style="text-align: center; color: #666; margin-bottom: 25px;">
    <strong>Student:</strong> <?php echo htmlspecialchars($student_name); ?> | 
    <strong>Admission No:</strong> <?php echo htmlspecialchars($admission_no); ?>
  </p>

  <!-- Filter Section -->
  <div class="filter-section">
    <h3 style="color: #ffcc00; margin-top: 0; margin-bottom: 15px;"> Filter Attendance Records</h3>
    <form method="GET" action="">
        <div class="filter-grid">
            <div class="filter-group">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">All Statuses</option>
                    <option value="present" <?php echo $status_filter === 'present' ? 'selected' : ''; ?>>Present</option>
                    <option value="absent" <?php echo $status_filter === 'absent' ? 'selected' : ''; ?>>Absent</option>
                    <option value="excused" <?php echo $status_filter === 'excused' ? 'selected' : ''; ?>>Excused</option>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="unit">Unit Code</label>
                <select id="unit" name="unit">
                    <option value="">All Units</option>
                    <?php foreach ($unique_units as $unit): ?>
                        <option value="<?php echo htmlspecialchars($unit); ?>" <?php echo $unit_filter === $unit ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($unit); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="filter-group">
                <label for="date_from">From Date</label>
                <input type="date" id="date_from" name="date_from" value="<?php echo htmlspecialchars($date_from); ?>">
            </div>
            
            <div class="filter-group">
                <label for="date_to">To Date</label>
                <input type="date" id="date_to" name="date_to" value="<?php echo htmlspecialchars($date_to); ?>">
            </div>
        </div>
        
        <div class="filter-buttons">
            <button type="submit" class="filter-btn">Apply Filters</button>
            <a href="?" class="filter-btn reset-btn">Reset Filters</a>
        </div>
    </form>
  </div>

  <!-- Summary Section -->
  <div class="summary-section">
    <h3 style="margin-top: 0; color: #ffcc00;">Overall Attendance Summary</h3>
    <div class="overall-percentage"><?php echo $overall_attendance; ?>%</div>
    <p>Overall Attendance Rate</p>
    <div class="progress-bar">
        <div class="progress-fill" style="width: <?php echo $overall_attendance; ?>%;"></div>
    </div>
    <div style="display: flex; justify-content: space-between; font-size: 12px; color: #ccc;">
        <span>0%</span>
        <span>50%</span>
        <span>100%</span>
    </div>
  </div>

  <!-- Statistics -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-number"><?php echo $stats['total_records']; ?></div>
      <div class="stat-label">Total Records</div>
    </div>
    <div class="stat-card">
      <div class="stat-number"><?php echo $stats['unique_days']; ?></div>
      <div class="stat-label">Class Days</div>
    </div>
    <div class="stat-card">
      <div class="stat-number" style="color:#4CAF50;"><?php echo $stats['present_count']; ?></div>
      <div class="stat-label">Present</div>
      <div class="stat-percentage percentage-present"><?php echo $present_percentage; ?>%</div>
    </div>
    <div class="stat-card">
      <div class="stat-number" style="color:#f44336;"><?php echo $stats['absent_count']; ?></div>
      <div class="stat-label">Absent</div>
      <div class="stat-percentage percentage-absent"><?php echo $absent_percentage; ?>%</div>
    </div>
    <div class="stat-card">
      <div class="stat-number" style="color:#FF9800;"><?php echo $stats['excused_count']; ?></div>
      <div class="stat-label">Excused</div>
      <div class="stat-percentage percentage-excused"><?php echo $excused_percentage; ?>%</div>
    </div>
  </div>

  <?php if (!empty($allRecords)): ?>
    <h3 style="color: #2c3e50; margin-bottom: 15px;">All Attendance Records (<?php echo count($allRecords); ?> entries)</h3>
    
    <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Date</th>
              <th>Status</th>
              <th>Unit</th>
              <th>Class Time</th>
              <th>Notes</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $currentDate = null;
            foreach ($allRecords as $index => $record): 
                $recordDate = $record['date_only'];
                $isNewDate = $currentDate !== $recordDate;
                $currentDate = $recordDate;
                
                $isUpdate = false;
                if ($index > 0 && $recordDate === $allRecords[$index-1]['date_only']) {
                    $isUpdate = true;
                }
            ?>
                <?php if ($isNewDate): ?>
                    <tr class="date-header">
                        <td colspan="5">
                            📅 <?php echo date('F j, Y (l)', strtotime($recordDate)); ?>
                        </td>
                    </tr>
                <?php endif; ?>
                
                <tr>
                    <td>
                        <?php echo date('M j, Y', strtotime($recordDate)); ?>
                    </td>
                    <td class="status-<?php echo $record['status']; ?>">
                        <?php echo ucfirst($record['status']); ?>
                        <?php if ($isUpdate): ?>
                            <br><span class="update-indicator">(updated)</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($record['unit_code'])): ?>
                            <span class="unit-code"><?php echo htmlspecialchars($record['unit_code']); ?></span>
                        <?php else: ?>
                            <span class="not-set">Not set</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($record['class_time'])): ?>
                            <span class="class-time"><?php echo htmlspecialchars($record['class_time']); ?></span>
                        <?php else: ?>
                            <span class="not-set">Not set</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($index === 0 || $recordDate !== $allRecords[$index-1]['date_only']): ?>
                            <?php if (count($attendanceByDate[$recordDate]) > 1): ?>
                                <span class="update-indicator">Final status</span>
                            <?php else: ?>
                                <span class="update-indicator">Original entry</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="update-indicator">Previous status</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
    </div>
  <?php else: ?>
    <div class="no-data">
      <h3>No attendance records found</h3>
      <p>Your attendance history will appear here once records are available.</p>
    </div>
  <?php endif; ?>
</div>

</body>
</html>