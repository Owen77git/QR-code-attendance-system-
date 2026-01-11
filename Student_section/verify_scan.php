<?php
header('Content-Type: application/json');
error_reporting(0);
session_start();
include("../../API/db.connect.php");


if (!isset($_SESSION['student'])) {
    echo json_encode([
        "status" => "error",
        "message" => "Not logged in"
    ]);
    exit();
}

$student_id = $_SESSION['student'];
$qrcode = $_POST['qr_code'] ?? null;
$current_time = time();

if (!$qrcode) {
    echo json_encode([
        "status" => "error",
        "message" => "No QR code received"
    ]);
    exit();
}


$conn->query("
CREATE TABLE IF NOT EXISTS scans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(100) NOT NULL,
    qr_code TEXT NOT NULL,
    scan_time INT NOT NULL
)
");


$stmt = $conn->prepare("SELECT scan_time FROM scans WHERE student_id = ? AND qr_code = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("ss", $student_id, $qrcode);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_time = intval($row['scan_time']);
    $elapsed = $current_time - $last_time;

    if ($elapsed < 3600) {
        echo json_encode([
            "status" => "locked",
            "last_time" => $last_time,
            "remaining" => 3600 - $elapsed
        ]);
        exit();
    }
}

$stmt = $conn->prepare("INSERT INTO scans (student_id, qr_code, scan_time) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $student_id, $qrcode, $current_time);
$stmt->execute();

echo json_encode([
    "status" => "success",
    "message" => "QR scan saved successfully"
]);
exit();
?>
