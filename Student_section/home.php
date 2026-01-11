<?php
session_start();
include("../../API/db.connect.php");

if (!isset($_SESSION['student'])) {
    header("Location: student_log.php");
    exit();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

$admission_no = $_SESSION['student'];

$sql = "SELECT full_name FROM students WHERE admission_no = '$admission_no' LIMIT 1";
$result = $conn->query($sql);

$studentName = "Student";
if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $studentName = $row['full_name'];
}

$attendanceSQL = "
    SELECT COUNT(*) as total, 
           SUM(status='Present') as present
    FROM attendance
    WHERE student_id = (SELECT id FROM students WHERE admission_no = '$admission_no')
";
$attendanceResult = $conn->query($attendanceSQL);
$attendanceText = "No records yet";
if ($attendanceResult && $attendanceResult->num_rows > 0) {
    $att = $attendanceResult->fetch_assoc();
    if ($att['total'] > 0) {
        $percentage = round(($att['present'] / $att['total']) * 100, 1);
        $attendanceText = "{$percentage}% this semester";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Portal</title>
  <link rel="stylesheet" href="../../CSS/style.CSS">
  <style> .card a,h3,p{text-decoration:none;}</style>
</head>
<style>

</style>

<body>
  <div class="bg-container">
    <div style="background-image:url('../../Assets/images.jpeg')" class="show"></div>
    <div style="background-image:url('../../Assets/Zetech-02.jpg')"></div>
    <div style="background-image:url('../../Assets/zetech1.jpg')"></div>
    <div style="background-image:url('../../Assets/zetech5.jpg')"></div>
    <div style="background-image:url('../../Assets/zetec4-1.jpg')"></div>
  </div>
  <div class="overlay"></div>

  <div class="topnav">
  <h1>University Portal</h1>
  <nav class="menu">
    <a href="home.php" class="active">Home</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="scan.attendance.php">Attendance</a>
    <a href="student_out.php">Logout</a>
  </nav>
  <div class="hamburger" id="hamburger">&#9776;</div>
</div>

<div class="mobile-menu-overlay" id="mobileMenuOverlay">
  <div class="mobile-menu-panel">
    <span class="close-btn" id="closeMenu">&times;</span>
    <a href="home.php" class="active">Home</a>
    <a href="dashboard.php">Dashboard</a>
    <a href="scan.attendance.php">Attendance</a>
    <a href="student_out.php">Logout</a>
  </div>
</div>

  <div class="content">
    <header style="background: #eee7e760;">
      <h2 id="welcomeMessage">Welcome back, <?php echo htmlspecialchars($studentName); ?></h2><p>take advantage of school learning materials<p>
    </header>
    <div class="cards" >
      <div class="card" style="background:#eee7e788;"><h3>Today's Date</h3><p id="dateInfo"></p></div>
      <div class="card"style="background:#eee7e788;"><h3>Exam results released</h3><p>Contact your Hod for mor information</p></div>
      <a href="history.php"style="text-decoration: none;"> <div class="card" style="background:#eee7e788;"><h3>Attendance History</h3><p>click to view your progress</p></div></a>
    </div>
    <div class="card1"  style="color: blue;"><h1>LINDA TUTION CENTRE<BR>BUILDING MY FUTURE</h1></div>
  </div>
  <footer>Ensuring integrity in student education life
    <div id="logo"><img src="../../Assets/shield-removebg-preview.png"></div>
    &copy; 2025 Linda University 
  </footer>

<script src="../../JS/index.js"></script>

 <script>
  document.getElementById("dateInfo").textContent = new Date().toDateString();

  const hamburger = document.getElementById("hamburger");
  const mobileOverlay = document.getElementById("mobileMenuOverlay");
  const closeMenu = document.getElementById("closeMenu");

  hamburger.addEventListener("click", () => {
    mobileOverlay.classList.add("show");
  });

  closeMenu.addEventListener("click", () => {
    mobileOverlay.classList.remove("show");
  });

  mobileOverlay.addEventListener("click", (e) => {
    if (e.target === mobileOverlay) {
      mobileOverlay.classList.remove("show");
    }
  });
</script>

</body>
</html>
