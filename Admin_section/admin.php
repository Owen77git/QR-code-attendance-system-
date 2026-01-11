<?php
session_start();


error_reporting(E_ALL);
ini_set('display_errors', 1);


if (!isset($_SESSION['admin']) || empty($_SESSION['admin'])) {
    header("Location: admin~login.php");
    exit();
}


include("../../API/db.connect.php");

$studentCount    = $conn->query("SELECT COUNT(*) AS total FROM students")->fetch_assoc()['total'];
$lecturerCount   = $conn->query("SELECT COUNT(*) AS total FROM lecturers")->fetch_assoc()['total'];
$deviceCount     = $conn->query("SELECT COUNT(*) AS total FROM devices")->fetch_assoc()['total'];
$adminCount      = $conn->query("SELECT COUNT(*) AS total FROM admin")->fetch_assoc()['total'];
$blockedDevices  = $conn->query("SELECT COUNT(*) AS total FROM devices WHERE status='blocked'")->fetch_assoc()['total'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="../../CSS/Admin.CSS">
</head>
<body>

  <div class="navbar">
    <h2 class="logo">Admin</h2>
    <nav class="menu" id="menu">
      <a href="admin.php" class="active">Dashboard</a>
      <a href="Reports.php">Attendance Reports</a>
      <a href="Dev~Reg.php">Registration</a>
      <a href="Device.php">Devices</a>
      <a href="Logs.php">System Logs</a>
      <a href="admin~logout.php">Logout</a>
    </nav>
    <div class="hamburger" id="hamburger">&#9776;</div>
  </div>


  <nav class="mobile-menu" id="mobileMenu">
    <a href="admin.php" class="active">Dashboard</a>
    <a href="Reports.php">Attendance Reports</a>
    <a href="dev_reg.php">Registration</a>
    <a href="Device.php">Devices</a>
    <a href="Logs.php">System Logs</a>
    <a href="admin~logout.php">Logout</a>
  </nav>

  <div class="workspace">
    <div class="card">
      <h3>Total Users</h3>
      <p>Students: <?php echo $studentCount; ?></p>
      <p>Lecturers: <?php echo $lecturerCount; ?></p>
      <p>Admins: <?php echo $adminCount; ?></p>
    </div>

    <div class="card">
      <h3>Device Management</h3>
      <p>Registered Devices: <?php echo $deviceCount; ?></p>
      <p>Blocked Devices: <?php echo $blockedDevices; ?></p>
    </div>
  </div>
</body>
  <script>
    const hamburger = document.getElementById("hamburger");
    const mobileMenu = document.getElementById("mobileMenu");

    if (hamburger && mobileMenu) {
      hamburger.addEventListener("click", () => {
        mobileMenu.classList.toggle("show");
      });
    }
  </script>
</body>
</html>
