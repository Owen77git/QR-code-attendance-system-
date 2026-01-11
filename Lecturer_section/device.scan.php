<?php
session_start();

if (!isset($_SESSION['lecturer'])) {
    header("Location: Lecturer~Login.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../../API/db.connect.php");

$result = $conn->query("SELECT * FROM device_scans ORDER BY id DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Scanned Devices</title>
  <link rel="stylesheet" href="../../CSS/Admin.CSS"> <!-- Global Admin CSS -->
  <style>
    .table-container {
      width: 100%;
      overflow-x: auto;
      margin-top: 15px;
      border: 1px solid rgba(255,255,255,0.1);
      border-radius: 8px;
      background: rgba(255,255,255,0.05);
    }
    table {
      width: 100%;
      border-collapse: collapse;
      min-width: 1200px; /* Ensures table doesn't shrink too much */
    }
    th, td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid rgba(255,255,255,0.1);
      white-space: nowrap;
    }
    th {
      background: rgba(255,255,255,0.1);
      color: #ffcc00;
      position: sticky;
      top: 0;
    }
    tr:hover {
      background: rgba(255,255,255,0.05);
    }
   .user-agent {
  max-width: none; /* remove width restriction */
  white-space: normal; /* allow wrapping */
  overflow: visible; /* show everything */
  word-break: break-word; /* wrap long strings */
}
    }
    .user-agent:hover {
      white-space: normal;
      overflow: visible;
      background: rgba(255,255,255,0.1);
      z-index: 1;
      position: relative;
    }
    .admission-no {
      font-weight: bold;
      color: #ffcc00;
    }
    .scroll-hint {
      text-align: center;
      padding: 10px;
      color: #ffcc00;
      font-style: italic;
      background: rgba(255,204,0,0.1);
      border-radius: 4px;
      margin-bottom: 10px;
      display: none;
    }
    @media (max-width: 768px) {
      .scroll-hint {
        display: block;
      }
      th, td {
        padding: 8px 10px;
        font-size: 14px;
      }
    }
  </style>
</head>
<body>

  <div class="navbar">
    <h2 class="logo">Admin</h2>
    <nav class="menu">
      <a href="Attendance.php">QR Management</a>
      <a href="device.scan.php" class="active">Device Scans</a>
      <a href="hash_comparison.php">Hash Comparison</a>
      <a href="Lecturer~Logout.php">Logout</a>
    </nav>
    <div class="hamburger" id="hamburger">&#9776;</div>
  </div>

  <nav class="mobile-menu" id="mobileMenu">
    <a href="Attendance.php">QR Management</a>
    <a href="device.scan.php" class="active">Device Scans</a>
    <a href="hash_comparison.php">Hash Comparison</a>
    <a href="Lecturer~Logout.php">Logout</a>
  </nav>

  <div class="workspace">
    <div class="card" style="grid-column: span 2;">
      <h3>📲 Device Scan Records</h3>
      <p>Showing all device scans from the <code>device_scans</code> table</p>
      
      <div class="scroll-hint">
        <--> Scroll horizontally to view all columns →
      </div>
      
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>ID</th>
              <th>Admission No</th>
              <th>IP Address</th>
              <th>User Agent</th>
              <th>Platform</th>
              <th>Screen</th>
              <th>Language</th>
              <th>Timezone</th>
              <th>Connection</th>
              <th>Battery</th>
              <th>Geolocation</th>
              <th>Scan Time</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result && $result->num_rows > 0) { ?>
              <?php while ($row = $result->fetch_assoc()) { ?>
                <tr>
                  <td><?php echo htmlspecialchars($row['id']); ?></td>
                  <td class="admission-no"><?php echo htmlspecialchars($row['admission_no']); ?></td>
                  <td><?php echo htmlspecialchars($row['ip_address']); ?></td>
                 <td class="user-agent" title="<?php echo htmlspecialchars($row['user_agent']); ?>">
  <?php echo htmlspecialchars($row['user_agent']); ?>
</td>

                  <td><?php echo htmlspecialchars($row['platform']); ?></td>
                  <td><?php echo htmlspecialchars($row['screen_resolution']); ?></td>
                  <td><?php echo htmlspecialchars($row['language']); ?></td>
                  <td><?php echo htmlspecialchars($row['timezone']); ?></td>
                  <td><?php echo htmlspecialchars($row['connection_info'] ?? 'N/A'); ?></td>
                  <td><?php echo htmlspecialchars($row['battery_level'] ?? 'N/A'); ?></td>
                  <td><?php echo htmlspecialchars($row['geolocation'] ?? 'N/A'); ?></td>
                  <td><?php echo date('M j, Y H:i:s', strtotime($row['timestamp'])); ?></td>
                </tr>
              <?php } ?>
            <?php } else { ?>
              <tr>
                <td colspan="12" style="text-align:center; padding: 20px;">
                  No device scans found in the database
                </td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
    document.getElementById("hamburger").addEventListener("click", function() {
      document.getElementById("mobileMenu").classList.toggle("show");
    });

    document.querySelectorAll('.user-agent').forEach(cell => {
      cell.addEventListener('click', function() {
        this.style.whiteSpace = this.style.whiteSpace === 'normal' ? 'nowrap' : 'normal';
        this.style.overflow = this.style.overflow === 'visible' ? 'hidden' : 'visible';
      });
    });

    const tableContainer = document.querySelector('.table-container');
    const scrollHint = document.querySelector('.scroll-hint');
    
    if (tableContainer.scrollWidth > tableContainer.clientWidth) {
      scrollHint.style.display = 'block';
    }

    tableContainer.addEventListener('scroll', function() {
      scrollHint.style.opacity = '0';
      setTimeout(() => {
        scrollHint.style.display = 'none';
      }, 500);
    });
  </script>

</body>
</html>

<?php $conn->close(); ?>