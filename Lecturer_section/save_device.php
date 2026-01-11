<?php

header("Content-Type: text/plain");
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../../API/db.connect.php");

$data = json_decode(file_get_contents("php://input"), true);
if (!$data) {
  die(" Invalid or no data received");
}

$device_name = $conn->real_escape_string($data['platform'] ?? 'Unknown');
$device_user = $conn->real_escape_string($data['userAgent'] ?? 'Unknown');
$ip = $_SERVER['REMOTE_ADDR'];
$screen = intval($data['screenWidth'] ?? 0) . "x" . intval($data['screenHeight'] ?? 0);
$language = $conn->real_escape_string($data['language'] ?? 'Unknown');
$timezone = $conn->real_escape_string($data['timezone'] ?? 'Unknown');
$timestamp = date("Y-m-d H:i:s");

$check = $conn->query("SELECT * FROM devices WHERE device_name='$device_name' AND ip_address='$ip' LIMIT 1");

if ($check->num_rows > 0) {
    $row = $check->fetch_assoc();
    $status = strtolower($row['status']);
    $student_name = $conn->real_escape_string($row['device_user']);
    $admission_no = $conn->real_escape_string($row['id']);

    if ($status === 'active') {
        $conn->query("INSERT INTO attendance (student_name, admission_no, status, timestamp)
                      VALUES ('$student_name', '$admission_no', 'Present', '$timestamp')");
        echo "Attendance recorded for $student_name at $timestamp";
    }
    elseif ($status === 'blocked') {
        $reason = "Blocked device tried to scan";
        $conn->query("INSERT INTO device_alerts (device_user, device_name, ip_address, reason)
                      VALUES ('$device_user', '$device_name', '$ip', '$reason')");
        echo "⚠This device is blocked. Admin has been notified.";
    }
    elseif ($status === 'pending') {
        $reason = "Pending device tried to scan before approval";
        $conn->query("INSERT INTO device_alerts (device_user, device_name, ip_address, reason)
                      VALUES ('$device_user', '$device_name', '$ip', '$reason')");
        echo "⚠Device pending approval. Admin has been notified.";
    }
}
else {
    $conn->query("INSERT INTO devices (device_user, device_name, ip_address, status)
                  VALUES ('$device_user', '$device_name', '$ip', 'pending')");
    $reason = "Unknown device detected (auto-added as pending)";
    $conn->query("INSERT INTO device_alerts (device_user, device_name, ip_address, reason)
                  VALUES ('$device_user', '$device_name', '$ip', '$reason')");
    echo "⚠New device detected — added as pending and admin alerted.";
}

$conn->close();
?>
