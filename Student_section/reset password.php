<?php
session_start();
include("../../API/db.connect.php");

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require '../../PHPMailer-master/src/Exception.php';
require '../../PHPMailer-master/src/PHPMailer.php';
require '../../PHPMailer-master/src/SMTP.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

$msg = "";
$step = "email"; // default step

// Step 1 – Request OTP
if (isset($_POST['get_otp'])) {
    $admission_no = trim($_POST['admission_no']);
    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT * FROM students WHERE admission_no=? AND email=? LIMIT 1");
    $stmt->bind_param("ss", $admission_no, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();

    if ($result->num_rows == 0) {
        $msg = " No account found with that admission number and email!";
    } else {
        $otp = rand(100000, 999999);
        $_SESSION['otp_email'] = $email;
        $_SESSION['otp_adm'] = $admission_no;
        $_SESSION['otp_code'] = $otp;
        $_SESSION['otp_expire'] = time() + 600; // 10 minutes validity

        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'oweneshuchi77@gmail.com';
            $mail->Password = 'zpifeqpnmmlnbzyd'; // App password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            $mail->setFrom('oweneshuchi77@gmail.com', 'Linda Tuition Portal');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = "Password Reset OTP - Linda Tuition";
            $mail->Body = "
<div style='font-family:Segoe UI, sans-serif; background:#f3f4f6; padding:40px 0; text-align:center;'>
  <div style='max-width:600px; margin:auto; background:#ffffff; border-radius:16px; overflow:hidden; box-shadow:0 4px 20px rgba(0,0,0,0.1);'>
    
    <!-- Header -->
    <div style='background:linear-gradient(135deg, #0b132b, #1c2541, #5bc0be); color:#fff; padding:30px 20px; text-align:left;'>
      <h2 style='margin:0; font-weight:bold; letter-spacing:1px;'>Linda Tuition</h2>
      <p style='margin:0; font-size:14px; opacity:0.8;'>Password Reset OTP</p>
    </div>
    
    <!-- Body -->
    <div style='padding:40px 30px; color:#333; text-align:center;'>
      <h2 style='margin-bottom:10px; color:#111;'>Your OTP</h2>
      <p style='font-size:15px; color:#444;'>Hey <b>$admission_no</b>,</p>
      <p style='font-size:14px; color:#555; line-height:1.6;'>
        Thank you for using <b>Linda Tuition Portal</b>. Use the following OTP to complete your password reset process.<br>
        The OTP is valid for <b>10 minutes</b>. Do not share this code with anyone, including Linda Tuition staff.
      </p>

      <!-- OTP Code -->
      <div style='font-size:40px; font-weight:bold; color:#d63031; letter-spacing:10px; margin:25px 0;'>$otp</div>

      <p style='font-size:13px; color:#999;'>If you didn't request this, you can safely ignore this email.</p>
    </div>

    <!-- Footer -->
    <div style='background:#0b132b; color:#ccc; font-size:12px; padding:12px 0; text-align:center;'>
      © ".date('Y')." Linda Tuition — Empowering Learners.
    </div>

  </div>
</div>";

            $mail->send();
            $msg = " OTP sent to your email!";
            $step = "verify";
        } catch (Exception $e) {
            $msg = "Failed to send OTP. Try again.";
        }
    }
}

// Step 2 – Verify OTP
if (isset($_POST['verify_otp'])) {
    $entered = trim($_POST['otp']);
    if (!isset($_SESSION['otp_code']) || time() > $_SESSION['otp_expire']) {
        $msg = " OTP expired. Please request again.";
        $step = "email";
    } elseif ($entered != $_SESSION['otp_code']) {
        $msg = "Invalid OTP!";
        $step = "verify";
    } else {
        $msg = " OTP verified. Enter your new password.";
        $step = "reset";
    }
}

if (isset($_POST['reset_pass'])) {
    $pass = $_POST['password'];
    $confirm = $_POST['confirm'];

    if ($pass !== $confirm) {
        $msg = " Passwords do not match!";
        $step = "reset";
    } else {
        if (!isset($_SESSION['otp_email']) || !isset($_SESSION['otp_adm'])) {
            $msg = " Session expired. Please restart password reset.";
            $step = "email";
        } else {
            $email = $_SESSION['otp_email'];
            $adm = $_SESSION['otp_adm'];
            $hashed = md5($pass);

            $stmt = $conn->prepare("UPDATE students SET password=? WHERE admission_no=? AND email=?");
            $stmt->bind_param("sss", $hashed, $adm, $email);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                $msg = " Password updated successfully! Redirecting...";
                unset($_SESSION['otp_email'], $_SESSION['otp_adm'], $_SESSION['otp_code'], $_SESSION['otp_expire']);
                echo "<script>
                    setTimeout(()=>window.location='student_log.php',2500);
                </script>";
                $step = "done";
            } else {
                $msg = " Failed to update password! Try again.";
                $step = "reset";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password | Linda Tuition</title>
<style>
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: 'Segoe UI', sans-serif;
  min-height: 100vh;
  display: flex;
  justify-content: center;
  align-items: center;
  background-image: url("../../Assets/ZDS.jpg");
  background-position: center;
  background-size: cover;
  overflow: hidden;
  position: relative;
  color: white;
  padding: 15px;
}

body::before {
  content: "";
  position: absolute;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: url('https://www.transparenttextures.com/patterns/stardust.png');
  opacity: 0.2;
  z-index: 0;
}

.login-container {
  width: 100%;
  max-width: 500px;
  display: flex;
  justify-content: center;
  align-items: center;
}

.login-box {
  position: relative;
  z-index: 1;
  width: 100%;
  padding: 40px;
  background: rgba(255,255,255,0.1);
  backdrop-filter: blur(12px);
  border-radius: 15px;
  box-shadow: 0 8px 32px rgba(0,0,0,0.3);
  text-align: center;
  color: purple;
}
.login-box h1 {
  margin-bottom: 25px;
  font-size: 24px;
  font-weight: bold;
  color: white;
}

.input-box {
  position: relative;
  margin: 15px 0;
}
.input-box input {
  width: 100%;
  padding: 12px 40px 12px 15px;
  border: none;
  border-radius: 25px;
  outline: none;
  font-size: 16px; /* Prevents zoom on iOS */
  background: rgba(255,255,255,0.2);
  color: white;
}
.input-box input::placeholder { color: rgba(255,255,255,0.7); }
.input-box i {
  position: absolute;
  right: 15px;
  top: 50%;
  transform: translateY(-50%);
  color: #ffd369;
}
.input-box > p {
  text-align: left;
  color: purple;
  font-weight: bold;
}

.login-btn {
  width: 100%;
  padding: 12px;
  background: white;
  color: #000;
  border: none;
  border-radius: 25px;
  font-weight: bold;
  font-size: 16px;
  cursor: pointer;
  transition: 0.3s;
  margin-top: 10px;
}
.login-btn:hover {
  background: #ffcc00;
  color: #000;
}

.back-login {
  display: block;
  margin-top: 10px;
  color: #001F8D;
  font-weight: bold;
  text-decoration: none;
}
.back-login:hover {
  color: #0034cc;
}

.error-text {
  color: #ff4444;
  font-size: 0.9rem;
  margin-top: 15px;
  height: 20px;
  transition: opacity 0.5s;
}

@media (max-width: 480px) {
  body {
    padding: 10px;
  }
  
  .login-box {
    padding: 30px 25px;
  }
  
  .login-box h1 {
    font-size: 22px;
    margin-bottom: 20px;
  }
  
  .input-box {
    margin: 12px 0;
  }
  
  .input-box input {
    padding: 14px 40px 14px 15px;
  }
}

@media (max-width: 360px) {
  .login-box {
    padding: 25px 20px;
  }
  
  .login-box h1 {
    font-size: 20px;
  }
  
  .input-box input {
    padding: 12px 35px 12px 12px;
  }
  
  .input-box i {
    right: 12px;
  }
}

@media (max-height: 500px) and (orientation: landscape) {
  body {
    padding: 10px;
  }
  
  .login-box {
    padding: 20px 25px;
    max-width: 90%;
  }
  
  .input-box {
    margin: 10px 0;
  }
}
</style>
<script>
setTimeout(() => {
  const error = document.querySelector('.error-text');
  if (error) error.style.opacity = 0;
}, 3000);
</script>
</head>
<body>
<div class="login-container">
  <div class="login-box">
    <?php if ($step === "email"): ?>
        <h1>Reset Password</h1>
        <form method="POST">
            <div class="input-box">
                <p>Admission Number</p>
                <input type="text" name="admission_no" placeholder="Enter your admission number" required>
                <i class="fas fa-id-card"></i>
            </div>
            <div class="input-box">
                <p>School Email</p>
                <input type="email" name="email" placeholder="Enter your school email" required>
                <i class="fas fa-envelope"></i>
            </div>
            <button class="login-btn" name="get_otp">Get OTP</button>
            <a href="student_log.php" class="back-login">Go back to login</a>
            <p class="error-text"><?php echo $msg; ?></p>
        </form>

    <?php elseif ($step === "verify"): ?>
        <h1>Verify OTP</h1>
        <form method="POST">
            <div class="input-box">
                <p>Enter 6-Digit OTP</p>
                <input type="text" name="otp" maxlength="6" required placeholder="e.g. 123456">
                <i class="fas fa-key"></i>
            </div>
            <button class="login-btn" name="verify_otp">Verify OTP</button>
            <p class="error-text"><?php echo $msg; ?></p>
        </form>

    <?php elseif ($step === "reset"): ?>
        <h1>New Password</h1>
        <form method="POST">
            <div class="input-box">
                <p>New Password</p>
                <input type="password" name="password" required placeholder="Enter new password">
                <i class="fas fa-lock"></i>
            </div>
            <div class="input-box">
                <p>Confirm Password</p>
                <input type="password" name="confirm" required placeholder="Confirm new password">
                <i class="fas fa-lock"></i>
            </div>
            <button class="login-btn" name="reset_pass">Update Password</button>
            <a href="student_log.php" class="back-login">Back to Login</a>
            <p class="error-text"><?php echo $msg; ?></p>
        </form>
    <?php endif; ?>
  </div>
</div>
</body>
</html>
