<?php
session_start();
header("Content-Type: text/plain");
error_reporting(E_ALL);
ini_set('display_errors', 1);

include("../../API/db.connect.php");

if (!isset($_SESSION['student'])) {
    die(" Session expired. Please log in again.");
}

$admission_no = $_SESSION['student'];

$rawData = file_get_contents("php://input");
$data = json_decode($rawData, true);

if (!$data) {
    die(" No valid data received.");
}

// Validate and sanitize input data
$userAgent = $conn->real_escape_string($data['userAgent'] ?? 'Unknown');
$platform = $conn->real_escape_string($data['platform'] ?? 'Unknown');
$screenWidth = intval($data['screenWidth'] ?? 0);
$screenHeight = intval($data['screenHeight'] ?? 0);
$language = $conn->real_escape_string($data['language'] ?? 'Unknown');
$timezone = $conn->real_escape_string($data['timezone'] ?? 'Unknown');
$latitude = isset($data['latitude']) ? floatval($data['latitude']) : 0;
$longitude = isset($data['longitude']) ? floatval($data['longitude']) : 0;
$batteryLevel = isset($data['batteryLevel']) ? floatval($data['batteryLevel']) : 0;
$selfieData = $data['selfie'] ?? null;
$ip = $_SERVER['REMOTE_ADDR'];
$screenResolution = $screenWidth . "x" . $screenHeight;
$geo = $latitude . "," . $longitude;
$connInfo = 'N/A';
$timestamp = date("Y-m-d H:i:s");

// Handle selfie image
$selfieImage = null;
$hasSelfie = false;
if ($selfieData && !empty($selfieData)) {
    // Remove data:image/jpeg;base64, prefix
    $selfieData = preg_replace('#^data:image/\w+;base64,#i', '', $selfieData);
    $selfieImage = base64_decode($selfieData);
    
    if ($selfieImage !== false && !empty($selfieImage)) {
        $hasSelfie = true;
        // Validate it's a reasonable image size (max 2MB)
        if (strlen($selfieImage) > 2 * 1024 * 1024) {
            $hasSelfie = false;
            $selfieImage = null;
        }
    } else {
        $selfieImage = null;
    }
}

// Check if user has scanned recently
$sqlCheck = "SELECT timestamp FROM device_scans WHERE admission_no = ? ORDER BY timestamp DESC LIMIT 1";
$stmtCheck = $conn->prepare($sqlCheck);
$stmtCheck->bind_param("s", $admission_no);
$stmtCheck->execute();
$result = $stmtCheck->get_result();

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastScan = strtotime($row['timestamp']);
    $now = time();
    $diffMinutes = ($now - $lastScan) / 60;

    if ($diffMinutes < 60) {
        $remaining = 60 - floor($diffMinutes);
        $stmtCheck->close();
        die("... Please wait $remaining more minutes before scanning again.");
    }
}
$stmtCheck->close();

// Prepare SQL based on whether we have a selfie or not
if ($hasSelfie) {
    $sqlInsert = "INSERT INTO device_scans (
        admission_no, ip_address, user_agent, platform, screen_resolution, language,
        connection_info, battery_level, geolocation, timezone, timestamp, selfie_image
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sqlInsert);
    if ($stmt) {
        $null = null;
        $stmt->bind_param("sssssssdsssb", 
            $admission_no, $ip, $userAgent, $platform, $screenResolution, $language,
            $connInfo, $batteryLevel, $geo, $timezone, $timestamp, $null
        );
        
        // Send the blob data
        $stmt->send_long_data(11, $selfieImage);
        
        if ($stmt->execute()) {
            echo " Device info and selfie saved successfully. You can scan again in 1 hour.";
        } else {
            echo " Database Error: " . $stmt->error;
        }
        
        $stmt->close();
    } else {
        echo " Database Error: Failed to prepare statement.";
    }
} else {
    $sqlInsert = "INSERT INTO device_scans (
        admission_no, ip_address, user_agent, platform, screen_resolution, language,
        connection_info, battery_level, geolocation, timezone, timestamp
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($sqlInsert);
    if ($stmt) {
        $stmt->bind_param("sssssssdsss", 
            $admission_no, $ip, $userAgent, $platform, $screenResolution, $language,
            $connInfo, $batteryLevel, $geo, $timezone, $timestamp
        );
        
        if ($stmt->execute()) {
            if ($selfieData && !$hasSelfie) {
                echo " Device info saved successfully (selfie failed). You can scan again in 1 hour.";
            } else {
                echo " Device info saved successfully. You can scan again in 1 hour.";
            }
        } else {
            echo " Database Error: " . $stmt->error;
        }
        
        $stmt->close();
    } else {
        echo " Database Error: Failed to prepare statement.";
    }
}

$conn->close();
?>