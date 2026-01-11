<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: admin~login.php");
    exit();
}

include("../../API/db.connect.php");

$msg = "";

$totalDevices   = $conn->query("SELECT COUNT(*) AS total FROM devices")->fetch_assoc()['total'];
$activeDevices  = $conn->query("SELECT COUNT(*) AS total FROM devices WHERE status='active'")->fetch_assoc()['total'];
$pendingDevices = $conn->query("SELECT COUNT(*) AS total FROM devices WHERE status='pending'")->fetch_assoc()['total'];
$blockedDevices = $conn->query("SELECT COUNT(*) AS total FROM devices WHERE status='blocked'")->fetch_assoc()['total'];


if (isset($_POST['block_device'])) {
    $id = $_POST['device_id'];
    $sql = "UPDATE devices SET status='blocked' WHERE id='$id'";
    $msg = ($conn->query($sql)) ? "Device blocked!" : " Error: " . $conn->error;
}

if (isset($_POST['delete_device'])) {
    $id = $_POST['device_id'];
    $sql = "DELETE FROM devices WHERE id='$id'";
    $msg = ($conn->query($sql)) ? "Device deleted!" : " Error: " . $conn->error;
}

if (isset($_POST['activate_device'])) {
    $id = $_POST['device_id'];
    $sql = "UPDATE devices SET status='active' WHERE id='$id'";
    $msg = ($conn->query($sql)) ? " Device activated!" : "Error: " . $conn->error;
}

if (isset($_POST['pend_device'])) {
    $id = $_POST['device_id'];
    $sql = "UPDATE devices SET status='pending' WHERE id='$id'";
    $msg = ($conn->query($sql)) ? " Device set to pending!" : "Error: " . $conn->error;
}

// Get devices with student information
$devices = $conn->query("
    SELECT d.*, s.full_name, s.admission_no 
    FROM devices d 
    LEFT JOIN students s ON d.admission_no = s.admission_no 
    ORDER BY d.id DESC
");

// Get selected device details when a device is selected
$selected_device = null;
if (isset($_POST['device_id']) && !empty($_POST['device_id'])) {
    $device_id = $_POST['device_id'];
    $selected_device = $conn->query("
        SELECT d.*, s.full_name, s.admission_no 
        FROM devices d 
        LEFT JOIN students s ON d.admission_no = s.admission_no 
        WHERE d.id = '$device_id'
    ")->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Devices - Admin</title>
  <link rel="stylesheet" href="../../CSS/Admin.CSS">
  <style>
    .button-grid {
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;
      margin-top: 15px;
    }
    .btn-submit {
      text-align: center;
      padding: 10px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-weight: bold;
      transition: 0.3s;
    }
    .device-details {
      background: rgba(255,255,255,0.05);
      padding: 20px;
      border-radius: 8px;
      margin-top: 20px;
      border-left: 4px solid #ffcc00;
    }
    .detail-row {
      display: flex;
      margin-bottom: 10px;
      padding: 8px 0;
      border-bottom: 1px solid rgba(255,255,255,0.1);
    }
    .detail-label {
      font-weight: bold;
      width: 150px;
      color: #ffcc00;
    }
    .detail-value {
      flex: 1;
      word-break: break-all;
    }
    .hash-value {
      font-family: monospace;
      font-size: 12px;
      background: rgba(0,0,0,0.3);
      padding: 8px;
      border-radius: 4px;
      margin-top: 5px;
    }
    .status-active { color: #4CAF50; font-weight: bold; }
    .status-pending { color: #FF9800; font-weight: bold; }
    .status-blocked { color: #f44336; font-weight: bold; }
    .no-selection {
      text-align: center;
      padding: 40px;
      color: rgba(255,255,255,0.6);
      font-style: italic;
    }
  </style>
</head>
<body>

  <div class="navbar">
    <h2 class="logo">Admin</h2>
    <nav class="menu">
      <a href="admin.php">Dashboard</a>
      <a href="Reports.php">Attendance Reports</a>
      <a href="Dev~Reg.php">Registration</a>
      <a href="Device.php" class="active">Devices</a>
      <a href="Logs.php">System Logs</a>
      <a href="admin~logout.php">Logout</a>
    </nav>
    <div class="hamburger" id="hamburger">&#9776;</div>
  </div>

  <!-- Mobile Menu -->
  <nav class="mobile-menu" id="mobileMenu">
    <a href="admin.php">Dashboard</a>
    <a href="Reports.php">Attendance Reports</a>
    <a href="Dev~Reg.php">Registration</a>
    <a href="Device.php" class="active">Devices</a>
    <a href="Logs.php">System Logs</a>
    <a href="admin~logout.php">Logout</a>
  </nav>

  <?php if (!empty($msg)) { ?>
    <div class="popup-message" id="popupMessage"><?php echo $msg; ?></div>
  <?php } ?>

  <div class="workspace">


    <!-- Manage Devices -->
    <div class="card">
      <h3>Manage Devices</h3>
      <form method="post">
        <label>Select Device</label>
        <select name="device_id" required onchange="this.form.submit()">
          <option value="">-- Choose Device --</option>
          <?php 
          $devices->data_seek(0); // Reset pointer
          while ($row = $devices->fetch_assoc()) { 
            $selected = isset($_POST['device_id']) && $_POST['device_id'] == $row['id'] ? 'selected' : '';
          ?>
            <option value="<?php echo $row['id']; ?>" <?php echo $selected; ?>>
              <?php echo $row['admission_no'] . " - " . ($row['full_name'] ?? 'Unknown') . " (" . $row['status'] . ")"; ?>
            </option>
          <?php } ?>
        </select>

        <div class="button-grid">
          <button type="submit" name="block_device" class="btn-submit" style="background:#ff6600;color:white;">Block</button>
          <button type="submit" name="delete_device" class="btn-submit" style="background:red;color:white;">Delete</button>
          <button type="submit" name="activate_device" class="btn-submit" style="background:green;color:white;">Activate</button>
          <button type="submit" name="pend_device" class="btn-submit" style="background:orange;color:white;">Pend</button>
        </div>
      </form>

      <!-- Device Details Section -->
      <?php if ($selected_device): ?>
        <div class="device-details">
          <h4>📱 Device Details</h4>
          
          <div class="detail-row">
            <div class="detail-label">Admission No:</div>
            <div class="detail-value"><?php echo htmlspecialchars($selected_device['admission_no']); ?></div>
          </div>
          
          <div class="detail-row">
            <div class="detail-label">Student Name:</div>
            <div class="detail-value"><?php echo htmlspecialchars($selected_device['full_name'] ?? 'Not Linked'); ?></div>
          </div>
          
          <div class="detail-row">
            <div class="detail-label">Device User:</div>
            <div class="detail-value"><?php echo htmlspecialchars($selected_device['device_user']); ?></div>
          </div>
          
          <div class="detail-row">
            <div class="detail-label">Status:</div>
            <div class="detail-value status-<?php echo $selected_device['status']; ?>">
              <?php echo ucfirst($selected_device['status']); ?>
            </div>
          </div>
          
          <div class="detail-row">
            <div class="detail-label">Stored Hash (SHA-256):</div>
            <div class="detail-value">
              <div class="hash-value" title="<?php echo htmlspecialchars($selected_device['hash_256']); ?>">
                <?php echo htmlspecialchars($selected_device['hash_256']); ?>
              </div>
            </div>
          </div>
          
          <div class="detail-row">
            <div class="detail-label">Hash Code:</div>
            <div class="detail-value">
              <div class="hash-value" title="<?php echo htmlspecialchars($selected_device['hash_code']); ?>">
                <?php echo htmlspecialchars($selected_device['hash_code']); ?>
              </div>
            </div>
          </div>
          
          <div class="detail-row">
            <div class="detail-label">Created:</div>
            <div class="detail-value"><?php echo date('M j, Y H:i:s', strtotime($selected_device['created_at'])); ?></div>
          </div>
        </div>
      <?php else: ?>
        <div class="no-selection">
          <p>Select a device to view details</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- All Devices Table -->
    <div class="card" style="grid-column: span 2;">
      <h3>All Registered Devices</h3>
      <div style="overflow-x: auto;">
        <table style="width: 100%;">
          <thead>
            <tr>
              <th>ID</th>
              <th>Admission No</th>
              <th>Student Name</th>
              <th>Device User</th>
              <th>Stored Hash</th>
              <th>Hash Code</th>
              <th>Status</th>
              <th>Created</th>
            </tr>
          </thead>
          <tbody>
            <?php 
            $devices->data_seek(0); // Reset pointer
            if ($devices->num_rows > 0): 
              while ($device = $devices->fetch_assoc()): 
            ?>
              <tr>
                <td><?php echo $device['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($device['admission_no']); ?></strong></td>
                <td><?php echo htmlspecialchars($device['full_name'] ?? 'N/A'); ?></td>
                <td><?php echo htmlspecialchars(substr($device['device_user'], 0, 30) . '...'); ?></td>
                <td title="<?php echo htmlspecialchars($device['hash_256']); ?>">
                  <?php echo substr($device['hash_256'], 0, 20) . '...'; ?>
                </td>
                <td title="<?php echo htmlspecialchars($device['hash_code']); ?>">
                  <?php echo substr($device['hash_code'], 0, 20) . '...'; ?>
                </td>
                <td class="status-<?php echo $device['status']; ?>">
                  <?php echo ucfirst($device['status']); ?>
                </td>
                <td><?php echo date('M j, Y', strtotime($device['created_at'])); ?></td>
              </tr>
            <?php 
              endwhile;
            else: 
            ?>
              <tr>
                <td colspan="8" style="text-align: center; padding: 20px;">
                  No devices registered yet
                </td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

  </div>

  <script>
    document.getElementById("hamburger").addEventListener("click", function() {
      document.getElementById("mobileMenu").classList.toggle("show");
    });

    // Auto-hide popup
    const popup = document.getElementById("popupMessage");
    if (popup) {
      setTimeout(() => popup.style.display = "none", 2000);
    }

    // Auto-submit form when device is selected
    document.querySelector('select[name="device_id"]').addEventListener('change', function() {
      if (this.value) {
        this.form.submit();
      }
    });
  </script>
</body>
</html>