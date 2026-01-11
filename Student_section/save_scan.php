<?php
header('Content-Type: application/json');
session_start();
include("../../API/db.connect.php");

if (!isset($_SESSION['student'])) {
    echo json_encode(["success" => false, "error" => "Not logged in"]);
    exit();
}

$admission_no = $_SESSION['student'];
$qrcode_data = $_POST['qrcode'] ?? null;
$current_time = time();

if (!$qrcode_data) {
    echo json_encode(["success" => false, "error" => "No QR data received"]);
    exit();
}

$conn->query("
CREATE TABLE IF NOT EXISTS scan_records (
    id INT AUTO_INCREMENT PRIMARY KEY,
    admission_no VARCHAR(50) NOT NULL,
    qrcode_data TEXT NOT NULL,
    scan_time INT NOT NULL
)
");

$sql = "SELECT scan_time FROM scan_records 
        WHERE admission_no = ? AND qrcode_data = ?
        ORDER BY id DESC LIMIT 1";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $admission_no, $qrcode_data);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $last_scan_time = $row['scan_time'];

    if ($current_time - $last_scan_time < 3600) {
        $remaining = 3600 - ($current_time - $last_scan_time);
        echo json_encode([
            "success" => false,
            "error" => "Cooldown active",
            "remaining" => $remaining
        ]);
        exit();
    }
}

$stmt = $conn->prepare("INSERT INTO scan_records (admission_no, qrcode_data, scan_time) VALUES (?, ?, ?)");
$stmt->bind_param("ssi", $admission_no, $qrcode_data, $current_time);
$stmt->execute();

echo json_encode(["success" => true]);
