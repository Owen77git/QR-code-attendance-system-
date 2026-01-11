<?php
session_start();

include("../../API/db.connect.php");

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = md5($_POST['password']); 

    $sql = "SELECT * FROM admin WHERE username='$username' AND password='$password'";
    $result = $conn->query($sql);

    if ($result && $result->num_rows == 1) {
        // ✅ Login success
        $_SESSION['admin'] = $username; 
        header("Location: admin.php"); 
        exit();
    } else {
        $error = "Invalid Username or Password!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Login</title>
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

    .error-message {
      color: red;
      margin-top: 15px;
      font-size: 0.9rem;
    }
  </style>
</head>
<body>
  <div class="login-container">
    <div class="login-box">
      <h2> Admin Login</h2>
      <form action="" method="post">
        <input type="text" name="username" placeholder="Admin Username" required>
        <input type="password" name="password" placeholder="Password" required>
        <button type="submit" class="btn">Login</button>
        <a href="../Lecturer_section/Lecturer~Login.php">Log in as Lecturer?</a>
      </form>

      <?php if (!empty($error)) { ?>
        <p class="error-message"><?php echo $error; ?></p>
      <?php } ?>
    </div>
  </div>
</body>
</html>
