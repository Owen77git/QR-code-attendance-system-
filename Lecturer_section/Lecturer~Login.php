<?php
session_start();
include("../../API/db.connect.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $code = $_POST['code']; // matches the form input
    $password = md5($_POST['password']); // only use md5 if DB stores hashed passwords

    $sql = "SELECT * FROM lecturers WHERE code='$code' AND password='$password'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {
        $_SESSION['lecturer'] = $code; // store lecturer code in session
        header("Location: Attendance.php");
        exit();
    } else {
        $error = "Invalid Lecturer Code or Password!";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lecturer Login</title>
  <link rel="stylesheet" href="../../CSS/Admin.CSS"> 
  <style>
    .login-container {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .login-box {
      background: rgba(255,255,255,0.05);
      backdrop-filter: blur(10px);
      padding: 40px;
      border-radius: 12px;
      width: 400px;
      text-align: center;
      box-shadow: 0 4px 20px rgba(0,0,0,0.3);
    }

    .login-box h2 {
      color: #ffcc00;
      margin-bottom: 20px;
    }

    .login-box input {
      width: 100%;
      padding: 12px;
      margin: 10px 0;
      border: none;
      border-radius: 8px;
      font-size: 1rem;
    }

    .btn {
      display: inline-block;
      background: #ffcc00;
      color: #000;
      font-size: 1rem;
      font-weight: bold;
      padding: 12px 24px;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      margin-top: 10px;
      width: 100%;
      transition: background 0.3s ease;
    }
    .btn:hover {
      background: #e6b800;
    }

    .login-box a {
      display: block;
      margin-top: 15px;
      font-size: 0.9rem;
      color: #ffcc00;
      text-decoration: none;
    }
    .login-box a:hover {
      text-decoration: underline;
    }

    .message {
      margin-top: 10px;
      font-weight: bold;
      color: #ffcc00;
    }
      
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-box">
      <h2>Lecturer Login</h2>
      <form method="post">
        <input type="text" name="code" placeholder="Lecturer Code" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="btn">Login</button>
        <a href="../Admin_section/admin~login.php">Log in as Admin?</a>
      </form>
      <?php if (!empty($error)) { ?>
        <p class="error-message"><?php echo $error; ?></p>
      <?php } ?>
    </div>
  </div>
</body>
</html>
