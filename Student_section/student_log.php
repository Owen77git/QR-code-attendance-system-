<?php
session_start();
include("../../API/db.connect.php");


error_reporting(E_ALL);
ini_set('display_errors', 1);

$error = "";


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $admission_no = trim($_POST['admission_no']);
    $password = md5($_POST['password']); // hashing for security (same as admin side)

    $sql = "SELECT * FROM students WHERE admission_no = '$admission_no' AND password = '$password'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $_SESSION['student'] = $admission_no;
        header("Location: home.php"); // redirect to student dashboard
        exit();
    } else {
        $error = " Invalid Admission Number or Password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - University Portal</title>
  <link rel="stylesheet" href="../../CSS/Login.CSS">
  <script src="https://kit.fontawesome.com/yourkitid.js" crossorigin="anonymous"></script>
  <style>
    .error-msg {
      display: none;
      background: #d9534f;
      color: #fff;
      padding: 10px;
      margin-top: 10px;
      border-radius: 6px;
      text-align: center;
    }
  </style>
</head>
<body>
  <div class="login-box">
    <h1>LINDA TUTION!!</h1>
    <form method="POST" action="">
      <div class="input-box"><p><b>Admission Number</b></p>
        <input type="text" name="admission_no" placeholder="Admission Number" required>
        <i class="fas fa-user"></i>
      </div>
      <div class="input-box"><p><b>PASSWORD</b></p>
        <input type="password" name="password" placeholder="Password" required>
        <i class="fas fa-lock"></i>
      </div>

      <div class="options">
        <label><input type="checkbox"><b> Remember me</b></label>
        <a href="reset password.php"><b>Reset password?</b></a>
      </div>

      <button type="submit" class="login-btn">Login</button>

    
      <div class="error-msg" id="errorBox"><?php echo $error; ?></div>
    </form>
  </div>

  <script>
   
    const errorBox = document.getElementById("errorBox");
    if (errorBox.innerText.trim() !== "") {
      errorBox.style.display = "block";
      setTimeout(() => {
        errorBox.style.display = "none";
      }, 3000);
    }
  </script>
</body>
</html>
