<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_SESSION['admin'])) {
    header("Location: admin~login.php");
    exit();
}

include("../../API/db.connect.php");

$msg = "";

if (isset($_POST['register_student'])) {
    $fullName   = $_POST['full_name'];
    $email       = $_POST['email'];
    $phone       = $_POST['phone'];
    $admissionNo = $_POST['admission_no'];
    $password    = md5($_POST['password']); // hash password

    $sql = "INSERT INTO students (full_name, email, phone, admission_no, password) 
            VALUES ('$fullName', '$email', '$phone', '$admissionNo', '$password')";

    $msg = ($conn->query($sql)) ? "Student registered successfully!" : " Student Error: " . $conn->error;
}

if (isset($_POST['register_lecturer'])) {
    $fullName = $_POST['full_name'];
    $email    = $_POST['email'];
    $code     = $_POST['code'];
    $unitAssigned = $_POST['unit_assigned'];
    $group    = $_POST['group'];
    $password = md5($_POST['password']);

    $sql = "INSERT INTO lecturers (full_name, email, code, unit_assigned, group_no, password) 
            VALUES ('$fullName', '$email', '$code', '$unitAssigned', '$group', '$password')";

    $msg = ($conn->query($sql)) ? " Lecturer registered successfully!" : " Lecturer Error: " . $conn->error;
}

if (isset($_POST['register_device'])) {
    $admissionNo = $_POST['admission_no'];
    $hash256     = $_POST['hash_256'];
    $status      = $_POST['status'];

    $sql = "INSERT INTO devices (admission_no, hash_256, status) 
            VALUES ('$admissionNo', '$hash256', '$status')";

    $msg = ($conn->query($sql)) ? "Device registered successfully!" : " Device Error: " . $conn->error;
}

if (isset($_POST['register_unit'])) {
    $unitName = $_POST['unit_name'];
    $unitCode = $_POST['unit_code'];

    $sql = "INSERT INTO units (unit_name, unit_code) 
            VALUES ('$unitName', '$unitCode')";

    $msg = ($conn->query($sql)) ? "Unit registered successfully!" : " Unit Error: " . $conn->error;
}

// Fetch available units for lecturer registration
$units = $conn->query("SELECT unit_name FROM units ORDER BY unit_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Registration</title>
<link rel="stylesheet" href="../../CSS/Admin.CSS">
</head>
<body>

<!-- Navbar -->
<div class="navbar">
  <h2 class="logo">Admin</h2>
  <nav class="menu">
    <a href="admin.php">Dashboard</a>
    <a href="Reports.php">Attendance Reports</a>
    <a href="dev_reg.php" class="active">Registration</a>
    <a href="Device.php">Devices</a>
    <a href="Logs.php">System Logs</a>
    <a href="admin~logout.php">Logout</a>
  </nav>
  <div class="hamburger" id="hamburger">&#9776;</div>
</div>

<!-- Mobile Menu -->
<nav class="mobile-menu" id="mobileMenu">
  <a href="admin.php">Dashboard</a>
  <a href="Reports.php">Attendance Reports</a>
  <a href="dev_reg.php" class="active">Registration</a>
  <a href="Device.php">Devices</a>
  <a href="Logs.php">System Logs</a>
  <a href="admin~logout.php">Logout</a>
</nav>

<?php if (!empty($msg)) { ?>
  <div class="popup-message" id="popupMessage"><?php echo $msg; ?></div>
<?php } ?>

<!-- Workspace -->
<div class="workspace">

  <div class="card">
    <h3>Device Registration</h3>
    <form method="post">
      <label>Admission Number</label>
      <input type="text" name="admission_no" placeholder="e.g. DCF-01-01136" required>

      <label>256 Hash</label>
      <input type="text" name="hash_256" placeholder="Enter SHA-256 Hash" required>

      <label>Status</label>
      <select name="status" required>
        <option value="active">Active</option>
        <option value="blocked">Blocked</option>
        <option value="pending">Pending</option>
      </select>

      <button type="submit" name="register_device" class="btn-submit">Register Device</button>
    </form>
  </div>

  <div class="card">
    <h3>Unit Registration</h3>
    <form method="post">
      <label>Unit Name</label>
      <input type="text" name="unit_name" placeholder="e.g. Software Engineering" required>

      <label>Unit Code</label>
      <input type="text" name="unit_code" placeholder="e.g. DCS-202" required>

      <button type="submit" name="register_unit" class="btn-submit">Register Unit</button>
    </form>
  </div>

  <div class="card">
    <h3>Lecturer Registration</h3>
    <form method="post">
      <label>Full Name</label>
      <input type="text" name="full_name" placeholder="e.g. Mr. Francis" required>

      <label>Email Address</label>
      <input type="email" name="email" placeholder="e.g. brenda@gmail.com" required>

      <label>Code</label>
      <input type="text" name="code" placeholder="e.g. LI-245" required>

      <label>Unit Assigned</label>
      <select name="unit_assigned" required>
        <option value="">Select Unit</option>
        <?php 
        if($units && $units->num_rows>0){
          while($unit = $units->fetch_assoc()){
            echo "<option value='".$unit['unit_name']."'>".$unit['unit_name']."</option>";
          }
        }
        ?>
      </select>

      <label>Group</label>
      <select name="group" required>
        <option value="">Select Group</option>
        <option value="Group 1">Group 1</option>
        <option value="Group 2">Group 2</option>
        <option value="Group 3">Group 3</option>
        <option value="Group 4">Group 4</option>
        <option value="Group 5">Group 5</option>
      </select>

      <label>Password</label>
      <input type="password" name="password" placeholder="Enter password" required>

      <button type="submit" name="register_lecturer" class="btn-submit">Register Lecturer</button>
    </form>
  </div>

  <div class="card">
    <h3>Student Registration</h3>
    <form method="post">
      <label>Full Name</label>
      <input type="text" name="full_name" placeholder="e.g. Owen" required>

      <label>Email Address</label>
      <input type="email" name="email" placeholder="e.g. student@zetech.ac.ke" required>

      <label>Phone Number</label>
      <input type="text" name="phone" placeholder="e.g. 0712345678" required>

      <label>Admission Number</label>
      <input type="text" name="admission_no" placeholder="e.g. DCF-01-01136" required>

      <label>Password</label>
      <input type="password" name="password" placeholder="Enter password" required>

      <button type="submit" name="register_student" class="btn-submit">Register Student</button>
    </form>
  </div>

</div>

<script>
  // Hamburger menu toggle
  const hamburger = document.getElementById("hamburger");
  const mobileMenu = document.getElementById("mobileMenu");

  hamburger.addEventListener("click", () => {
    mobileMenu.classList.toggle("show");
  });

  // Hide popup message after 2 seconds
  const popup = document.getElementById("popupMessage");
  if(popup){
    setTimeout(()=>{popup.style.display="none"},2000);
  }
</script>
</body>
</html>