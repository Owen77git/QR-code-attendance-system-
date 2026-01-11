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

// Handle session reset
if (isset($_POST['reset_session'])) {
    // Store lecturer email before clearing session
    $lecturer_email = $_SESSION['lecturer'];
    
    // Clear all session data
    session_unset();
    
    // Restore only the login session
    $_SESSION['lecturer'] = $lecturer_email;
    
    $msg = "Session reset successfully! All filters and temporary data cleared.";
    
    // Redirect to clear POST data and refresh page
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle filters
$filter_date = isset($_GET['date']) ? $_GET['date'] : date('Y-m-d');
$filter_status = isset($_GET['status']) ? $_GET['status'] : 'all';
$filter_admission = isset($_GET['admission_no']) ? $_GET['admission_no'] : '';

// Build WHERE clause for filters
$where_conditions = [];
$params = [];

if (!empty($filter_date)) {
    $where_conditions[] = "DATE(dc.timestamp) = ?";
    $params[] = $filter_date;
}

if ($filter_status !== 'all') {
    $where_conditions[] = "dc.match_status = ?";
    $params[] = $filter_status;
}

if (!empty($filter_admission)) {
    $where_conditions[] = "dc.admission_no LIKE ?";
    $params[] = "%$filter_admission%";
}

$where_clause = "";
if (!empty($where_conditions)) {
    $where_clause = "WHERE " . implode(" AND ", $where_conditions);
}

// Get comparison data
$query = "
    SELECT 
        dc.id,
        dc.admission_no,
        dc.hash_256 as scanned_hash,
        d.hash_256 as stored_hash,
        dc.match_status,
        dc.timestamp,
        dc.scanned_data,
        s.full_name,
        ds.user_agent,
        ds.platform,
        ds.screen_resolution,
        ds.language,
        ds.timezone,
        ds.ip_address
    FROM device_comparison dc
    LEFT JOIN devices d ON dc.admission_no = d.admission_no AND d.hash_256 = dc.hash_256
    LEFT JOIN students s ON dc.admission_no = s.admission_no
    LEFT JOIN device_scans ds ON dc.admission_no = ds.admission_no AND DATE(ds.timestamp) = DATE(dc.timestamp)
    $where_clause
    ORDER BY dc.timestamp DESC
";

$stmt = $conn->prepare($query);
if ($stmt) {
    if (!empty($params)) {
        $types = str_repeat('s', count($params));
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $comparisons = $stmt->get_result();
} else {
    $comparisons = $conn->query($query);
}

// Get statistics
$stats_query = "
    SELECT 
        match_status,
        COUNT(*) as count
    FROM device_comparison 
    WHERE DATE(timestamp) = ?
    GROUP BY match_status
";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param('s', $filter_date);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();

$matched_count = 0;
$mismatched_count = 0;
$total_comparisons = 0;

while ($stat = $stats_result->fetch_assoc()) {
    if ($stat['match_status'] == 'matched') {
        $matched_count = $stat['count'];
    } else {
        $mismatched_count = $stat['count'];
    }
    $total_comparisons += $stat['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced Hash Comparison</title>
    <link rel="stylesheet" href="../../CSS/Admin.CSS">
    <style>
        .workspace { display:flex; flex-direction:column; gap:20px; padding:20px; }
        .filters { background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .filter-group { display: flex; gap: 15px; align-items: end; flex-wrap: wrap; }
        .filter-item { display: flex; flex-direction: column; }
        .filter-item label { margin-bottom: 5px; font-weight: bold; }
        .filter-item input, .filter-item select { padding: 8px 12px; border-radius: 6px; border: 1px solid #ccc; }
        .btn { background:#ffcc00; color:#000; font-weight:bold; padding:10px 20px; border:none; border-radius:6px; cursor:pointer; transition:0.3s; }
        .btn:hover { background:#e6b800; }
        .btn-reset { background:#6c757d; color:white; }
        .btn-reset:hover { background:#5a6268; }
        .btn-delete { background:#dc3545; color:white; }
        .btn-delete:hover { background:#c82333; }
        .btn-session-reset { background:#FF9800; color:white; margin-left: 10px; }
        .btn-session-reset:hover { background:#e68900; }
        
        table { width:100%; border-collapse:collapse; margin-top:10px; background: rgba(255,255,255,0.05); }
        th, td { padding:12px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.1); }
        th { background: rgba(255,255,255,0.15); font-weight: bold; }
        
        .status-matched { color: #4CAF50; font-weight: bold; }
        .status-mismatched { color: #f44336; font-weight: bold; }
        
        .stats { display: flex; gap: 15px; margin-bottom: 20px; }
        .stat-card { background: rgba(255,255,255,0.1); padding: 20px; border-radius: 8px; flex: 1; text-align: center; }
        .stat-number { font-size: 28px; font-weight: bold; margin-bottom: 5px; }
        .stat-label { font-size: 14px; opacity: 0.8; }
        
        .hash-cell { max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; cursor: pointer; }
        .hash-cell:hover { overflow: visible; white-space: normal; background: rgba(255,255,255,0.1); z-index: 1; position: relative; }
        
        .comparison-details { background: rgba(255,255,255,0.05); padding: 15px; border-radius: 6px; margin: 10px 0; }
        .detail-row { display: flex; margin-bottom: 8px; }
        .detail-label { font-weight: bold; width: 150px; flex-shrink: 0; }
        .detail-value { flex: 1; font-family: monospace; font-size: 12px; word-break: break-all; }
        
        .device-info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 15px; margin: 10px 0; }
        .device-info-card { background: rgba(255,255,255,0.05); padding: 15px; border-radius: 6px; }
        .device-info-card h4 { margin-top: 0; margin-bottom: 10px; color: #ffcc00; }
        
        .no-data { text-align: center; padding: 40px; color: rgba(255,255,255,0.6); font-style: italic; }
        
        .mobile-menu { display: none; }
        .session-controls { background: rgba(255,255,255,0.1); padding: 15px; border-radius: 8px; margin-bottom: 20px; }
        .session-controls h3 { margin-top: 0; margin-bottom: 10px; }
        .control-buttons { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; }
        
       /* ---------- Mobile Responsiveness (Max-width: 768px) ---------- */
@media (max-width: 768px) {
    /* Workspace and filters */
    .workspace { padding: 10px; gap: 15px; }
    .filters { padding: 10px; }
    .filter-group { flex-direction: column; align-items: stretch; gap: 10px; }
    .filter-item { width: 100%; }

    /* Stats cards */
    .stats { flex-direction: column; gap: 10px; }
    .stat-card { flex: 1 1 100%; padding: 12px; font-size: 14px; }

    /* Table adjustments */
    table { display: block; width: 100%; overflow-x: auto; -webkit-overflow-scrolling: touch; font-size: 13px; }
    th, td { padding: 6px 8px; white-space: nowrap; }
    .hash-cell { max-width: 120px; font-size: 12px; }

    /* Device info grid and cards */
    .device-info-grid { grid-template-columns: 1fr; gap: 10px; }
    .device-info-card { padding: 10px; font-size: 12px; }

    /* Comparison details */
    .comparison-details { padding: 10px; }

    /* Labels and values */
    .detail-label { width: 120px; font-size: 12px; }
    .detail-value { font-size: 12px; }

    /* Buttons full width */
    .btn { width: 100%; padding: 10px; font-size: 14px; margin: 5px 0 !important; }

    /* No data message */
    .no-data { padding: 20px; font-size: 14px; }

    /* Mobile menu */
    .mobile-menu { display: flex; flex-direction: column; }
    
    /* Session controls */
    .session-controls { padding: 10px; }
    .control-buttons { flex-direction: column; }
}

    </style>
</head>
<body>

<div class="navbar">
    <h2 class="logo">Comparison</h2>
    <nav class="menu">
        <a href="Attendance.php">QR Management</a>
        <a href="device.scan.php">Device Data</a>
        <a href="hash_comparison.php"class="active">Hash Comparison</a>
        <a href="Lecturer~Logout.php">Logout</a>
    </nav>
    <div class="hamburger" id="hamburger">&#9776;</div>
</div>

<nav class="mobile-menu" id="mobileMenu">
   <a href="Attendance.php">QR Management</a>
        <a href="device.scan.php">Device Data</a>
    <a href="hash_comparison.php"class="active">Hash Comparison</a>
        <a href="Lecturer~Logout.php">Logout</a>
</nav>

<div class="workspace">

    <!-- Session Reset Controls -->
    <div class="session-controls">
        <h3>Session Management</h3>
        <div class="control-buttons">
            <form method="POST" style="display: inline;">
                <button type="submit" class="btn btn-session-reset" name="reset_session" onclick="return confirm('Are you sure you want to reset all session data? This will clear all filters and temporary data but keep you logged in.')">
                    Reset Session Data
                </button>
            </form>
            <span style="font-size: 12px; color: #ccc;">
                Clears all filters, temporary data, and resets the page to default state without logging out.
            </span>
        </div>
    </div>

    <!-- Statistics -->
    <div class="stats">
        <div class="stat-card">
            <div class="stat-number"><?php echo $total_comparisons; ?></div>
            <div class="stat-label">Total Comparisons</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #4CAF50;"><?php echo $matched_count; ?></div>
            <div class="stat-label">Matched logs</div>
        </div>
        <div class="stat-card">
            <div class="stat-number" style="color: #f44336;"><?php echo $mismatched_count; ?></div>
            <div class="stat-label">Mismatched logs</div>
        </div>
        <div class="stat-card">
            <div class="stat-number">
                <?php 
                if ($total_comparisons > 0) {
                    echo round(($matched_count / $total_comparisons) * 100, 1) . '%';
                } else {
                    echo '0%';
                }
                ?>
            </div>
            <div class="stat-label">Success Rate</div>
        </div>
    </div>

    <!-- Display message if any -->
    <?php if (!empty($msg)): ?>
        <div class="card" style="background: <?php echo strpos($msg, '✅') !== false ? 'rgba(76,175,80,0.2)' : 'rgba(255,152,0,0.2)'; ?>; border-left: 4px solid <?php echo strpos($msg, '✅') !== false ? '#4CAF50' : '#FF9800'; ?>;">
            <p style="margin: 0; padding: 15px; font-weight: bold;"><?php echo $msg; ?></p>
        </div>
    <?php endif; ?>

    <!-- Filters -->
    <div class="filters">
        <h3>Filter Results</h3>
        <form method="GET" class="filter-group">
            <div class="filter-item">
                <label for="date">Date</label>
                <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($filter_date); ?>">
            </div>
            
            <div class="filter-item">
                <label for="status">Match Status</label>
                <select id="status" name="status">
                    <option value="all" <?php echo $filter_status == 'all' ? 'selected' : ''; ?>>All Status</option>
                    <option value="matched" <?php echo $filter_status == 'matched' ? 'selected' : ''; ?>>Matched</option>
                    <option value="mismatched" <?php echo $filter_status == 'mismatched' ? 'selected' : ''; ?>>Mismatched</option>
                </select>
            </div>
            
            <div class="filter-item">
                <label for="admission_no">Admission Number</label>
                <input type="text" id="admission_no" name="admission_no" 
                       value="<?php echo htmlspecialchars($filter_admission); ?>" 
                       placeholder="Search admission no...">
            </div>
            
            <div class="filter-item">
                <button type="submit" class="btn">Apply Filters</button>
            </div>
        </form>
        
        <!-- Delete All Form -->
        <form method="POST" id="deleteForm" style="display: none;">
            <input type="hidden" name="delete_all_comparisons" value="1">
        </form>
    </div>

    <!-- Results Table -->
    <div class="card">
        <h3>Enhanced Hash Comparison Results</h3>
        
        <?php if ($comparisons && $comparisons->num_rows > 0): ?>
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Admission No</th>
                        <th>Student Name</th>
                        <th>Scanned Hash</th>
                        <th>Stored Hash</th>
                        <th>Result</th>
                        <th>Attendance</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($row = $comparisons->fetch_assoc()): 
                        $attendance_status = $row['match_status'] == 'matched' ? 'Present' : 'Absent';
                        $attendance_class = $row['match_status'] == 'matched' ? 'status-present' : 'status-absent';
                    ?>
                        <tr>
                            <td><?php echo date('M j, Y H:i:s', strtotime($row['timestamp'])); ?></td>
                            <td><strong><?php echo htmlspecialchars($row['admission_no']); ?></strong></td>
                            <td><?php echo htmlspecialchars($row['full_name'] ?? 'N/A'); ?></td>
                            <td class="hash-cell" title="<?php echo htmlspecialchars($row['scanned_hash']); ?>">
                                <?php echo substr($row['scanned_hash'], 0, 20) . '...'; ?>
                            </td>
                            <td class="hash-cell" title="<?php echo htmlspecialchars($row['stored_hash'] ?? 'N/A'); ?>">
                                <?php 
                                if ($row['stored_hash']) {
                                    echo substr($row['stored_hash'], 0, 20) . '...';
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </td>
                            <td class="status-<?php echo $row['match_status']; ?>">
                                <?php echo ucfirst($row['match_status']); ?>
                            </td>
                            <td class="<?php echo $attendance_class; ?>">
                                <?php echo $attendance_status; ?>
                            </td>
                        </tr>
                        
                        <!-- Detailed comparison row -->
                        <tr style="background: rgba(255,255,255,0.02);">
                            <td colspan="7">
                                <div class="comparison-details">
                                    <h4>Device Information & Hash Details</h4>
                                    
                                    <div class="device-info-grid">
                                        <div class="device-info-card">
                                            <h4>Scanned Device Data</h4>
                                            <div class="detail-row">
                                                <div class="detail-label">User Agent:</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($row['user_agent'] ?? 'N/A'); ?></div>
                                            </div>
                                            <div class="detail-row">
                                                <div class="detail-label">Platform:</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($row['platform'] ?? 'N/A'); ?></div>
                                            </div>
                                            <div class="detail-row">
                                                <div class="detail-label">Screen:</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($row['screen_resolution'] ?? 'N/A'); ?></div>
                                            </div>
                                            <div class="detail-row">
                                                <div class="detail-label">Language:</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($row['language'] ?? 'N/A'); ?></div>
                                            </div>
                                            <div class="detail-row">
                                                <div class="detail-label">Timezone:</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($row['timezone'] ?? 'N/A'); ?></div>
                                            </div>
                                            <div class="detail-row">
                                                <div class="detail-label">IP Address:</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($row['ip_address'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                        
                                        <div class="device-info-card">
                                            <h4>Hash Comparison</h4>
                                            <div class="detail-row">
                                                <div class="detail-label">Full Scanned Hash:</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($row['scanned_hash']); ?></div>
                                            </div>
                                            <?php if ($row['stored_hash']): ?>
                                            <div class="detail-row">
                                                <div class="detail-label">Full Stored Hash:</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($row['stored_hash']); ?></div>
                                            </div>
                                            <?php endif; ?>
                                            <div class="detail-row">
                                                <div class="detail-label">Raw Data Hashed:</div>
                                                <div class="detail-value"><?php echo htmlspecialchars($row['scanned_data'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="no-data">
                <h3>No hash comparison data found</h3>
                <p>Try adjusting your filters or check if any scans have been processed today.</p>
            </div>
        <?php endif; ?>
    </div>

</div>

<script>
document.getElementById("hamburger").addEventListener("click", function() {
    document.getElementById("mobileMenu").classList.toggle("show");
});

// Auto-refresh page every 60 seconds to show new comparisons
setTimeout(() => {
    location.reload();
}, 60000);

// Expand hash cells on click
document.querySelectorAll('.hash-cell').forEach(cell => {
    cell.addEventListener('click', function() {
        this.style.whiteSpace = this.style.whiteSpace === 'normal' ? 'nowrap' : 'normal';
        this.style.overflow = this.style.overflow === 'visible' ? 'hidden' : 'visible';
    });
});

// Confirm and delete all comparisons
function confirmDelete() {
    if (confirm('⚠️ Are you sure you want to delete ALL comparison records?\n\nThis action cannot be undone and will remove all hash comparison data from the database.')) {
        document.getElementById('deleteForm').submit();
    }
}
</script>

</body>
</html>