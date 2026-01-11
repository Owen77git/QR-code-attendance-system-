<?php
session_start();

$_SESSION = [];

session_destroy();

header("Location: Lecturer~Login.php");
exit();
?>