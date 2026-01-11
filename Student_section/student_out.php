<?php
session_start();


$_SESSION = [];


session_destroy();


header("Location: student_log.php");
exit();
?>
