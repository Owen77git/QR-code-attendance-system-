<?php
session_start();
if (!isset($_SESSION['lecturer'])) {
    header("Location: Lecturer~Login.php");
    exit();
}

include("../../API/db.connect.php");
$alerts = $conn->query("SELECT * FROM device_alerts ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>⚠ Device Alerts</title>
<link rel="stylesheet" href="../../CSS/Admin.CSS">
<style>
table { width:100%; border-collapse:collapse; margin-top:20px; }
th, td { padding:10px; border-bottom:1px solid rgba(255,255,255,0.1); }
th { background:rgba(255,255,255,0.1); color:#ffcc00; }
tr:hover { background:rgba(255,255,255,0.05); }
</style>
</head>
<body>

<div class="navbar">
  <h2 class="logo">Alerts</h2>
  <nav class="menu">
    <a href="Attendance.php">QR Management</a>
    <a href="device.scan.php">Device Data</a>
    <a href="device_alerts.php" class="active">Alerts</a>
    <a href="Lecturer~Logout.php">Logout</a>
  </nav>
</div>

<div class="workspace">
  <h3>Suspicious Device Scans</h3>
  <table>
    <tr>
      <th>ID</th>
      <th>Device User</th>
      <th>Device Name</th>
      <th>IP Address</th>
      <th>Reason</th>
      <th>Status</th>
      <th>Timestamp</th>
    </tr>
    <?php if ($alerts->num_rows > 0): ?>
      <?php while($a = $alerts->fetch_assoc()): ?>
        <tr>
          <td><?= $a['id'] ?></td>
          <td><?= htmlspecialchars($a['device_user']) ?></td>
          <td><?= htmlspecialchars($a['device_name']) ?></td>
          <td><?= htmlspecialchars($a['ip_address']) ?></td>
          <td><?= htmlspecialchars($a['reason']) ?></td>
          <td><?= htmlspecialchars($a['status']) ?></td>
          <td><?= $a['created_at'] ?></td>
        </tr>
      <?php endwhile; ?>
    <?php else: ?>
      <tr><td colspan="7" style="text-align:center;">No alerts found </td></tr>
    <?php endif; ?>
  </table>
</div>

</body>
</html>
