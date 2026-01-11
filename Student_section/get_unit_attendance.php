<?php
session_start();
include("../../API/db.connect.php");

if (!isset($_SESSION['student'])) {
    header("Location: student_log.php");
    exit();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

$adm = $_SESSION['student'];

// Get student info using admission number
$studentSql = "SELECT id, full_name, admission_no FROM students WHERE admission_no = '$adm' LIMIT 1";
$studentResult = $conn->query($studentSql);

if ($studentResult->num_rows > 0) {
    $student = $studentResult->fetch_assoc();
    $student_id = $student['id'];
    $student_name = $student['full_name'];
    $admission_no = $student['admission_no'];
} else {
    echo "<div style='background: #f44336; color: white; padding: 20px; margin: 20px; border-radius: 8px;'>";
    echo "<h3> Student Not Found</h3>";
    echo "<p><strong>Admission Number Used:</strong> " . htmlspecialchars($adm) . "</p>";
    echo "<p>Please contact administrator or try logging in again.</p>";
    echo "<a href='student_log.php' style='color: white; text-decoration: underline;'>Return to Login</a>";
    echo "</div>";
    exit();
}

$unit_code = $_GET['unit_code'] ?? '';

if (empty($unit_code)) {
    echo '<div class="no-data">Unit code is required. Please specify a unit.</div>';
    exit();
}

// Fetch unit details
$unitSql = "SELECT unit_code, unit_name, credit_hours, semester, lecturer FROM units WHERE unit_code = '$unit_code'";
$unitResult = $conn->query($unitSql);
$unit = $unitResult->fetch_assoc();

// Mock data for assignments, CATs, etc. (In real scenario, this would come from database)
$assignments = [
    ['id' => 1, 'title' => 'Research Paper on AI Ethics', 'due_date' => '2025-02-15', 'status' => 'pending', 'weight' => '15%', 'submitted' => false],
    ['id' => 2, 'title' => 'Programming Assignment 1', 'due_date' => '2025-02-28', 'status' => 'in_progress', 'weight' => '10%', 'submitted' => false],
    ['id' => 3, 'title' => 'Group Project Proposal', 'due_date' => '2025-01-20', 'status' => 'submitted', 'weight' => '5%', 'submitted' => true, 'score' => '8/10']
];

$cats = [
    ['id' => 1, 'title' => 'CAT 1 - Fundamentals', 'date' => '2025-03-10', 'weight' => '15%', 'score' => null],
    ['id' => 2, 'title' => 'CAT 2 - Advanced Topics', 'date' => '2025-04-05', 'weight' => '15%', 'score' => null]
];

$resources = [
    ['type' => 'textbook', 'title' => 'Core Course Textbook', 'author' => 'Dr. Smith Johnson', 'edition' => '3rd'],
    ['type' => 'slides', 'title' => 'Lecture Slides Week 1-6', 'author' => 'Prof. Sarah Wilson'],
    ['type' => 'video', 'title' => 'Introduction to Course Concepts', 'duration' => '45 min'],
    ['type' => 'article', 'title' => 'Recent Research Papers Collection', 'source' => 'IEEE Library']
];

$schedule = [
    ['day' => 'Monday', 'time' => '10:00 - 12:00', 'room' => 'LT-101', 'type' => 'Lecture'],
    ['day' => 'Wednesday', 'time' => '14:00 - 16:00', 'room' => 'LAB-A', 'type' => 'Practical'],
    ['day' => 'Friday', 'time' => '09:00 - 10:00', 'room' => 'LT-101', 'type' => 'Tutorial']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($unit_code); ?> - Unit Portal</title>
  <link rel="stylesheet" href="../CSS/Admin.CSS">
  <style>
    .workspace { padding: 20px; max-width: 1200px; margin: 0 auto; }
    .unit-header {
        background: linear-gradient(135deg, rgba(30,60,114,0.95), rgba(20,40,80,0.95));
        padding: 30px;
        border-radius: 15px;
        margin-bottom: 30px;
        border-left: 6px solid #ffcc00;
        box-shadow: 0 8px 25px rgba(0,0,0,0.3);
    }
    .unit-code { 
        color: #ffcc00; 
        font-size: 14px;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    .unit-name {
        color: white;
        font-size: 28px;
        margin: 10px 0;
        font-weight: 300;
    }
    .unit-meta {
        display: flex;
        gap: 20px;
        margin-top: 15px;
        flex-wrap: wrap;
    }
    .meta-item {
        background: rgba(255,255,255,0.1);
        padding: 8px 15px;
        border-radius: 20px;
        font-size: 12px;
        border: 1px solid rgba(255,255,255,0.2);
    }
    .dashboard-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
        gap: 25px;
        margin-bottom: 30px;
    }
    .dashboard-card {
        background: rgba(255,255,255,0.05);
        border-radius: 12px;
        padding: 25px;
        border: 1px solid rgba(255,255,255,0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    .dashboard-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.2);
    }
    .card-header {
        display: flex;
        align-items: center;
        margin-bottom: 20px;
        padding-bottom: 15px;
        border-bottom: 2px solid rgba(255,255,255,0.1);
    }
    .card-icon {
        font-size: 24px;
        margin-right: 15px;
        color: #ffcc00;
    }
    .card-title {
        color: #ffcc00;
        font-size: 18px;
        font-weight: bold;
        margin: 0;
    }
    .assignment-item, .cat-item, .resource-item, .schedule-item {
        background: rgba(255,255,255,0.08);
        padding: 15px;
        margin: 12px 0;
        border-radius: 8px;
        border-left: 4px solid;
        transition: all 0.3s ease;
    }
    .assignment-item:hover, .cat-item:hover, .resource-item:hover, .schedule-item:hover {
        background: rgba(255,255,255,0.12);
        transform: translateX(5px);
    }
    .assignment-pending { border-left-color: #f44336; }
    .assignment-in_progress { border-left-color: #FF9800; }
    .assignment-submitted { border-left-color: #4CAF50; }
    .cat-item { border-left-color: #2196F3; }
    .resource-item { border-left-color: #9C27B0; }
    .schedule-item { border-left-color: #00BCD4; }
    .item-title {
        color: white;
        font-weight: bold;
        margin-bottom: 8px;
        font-size: 14px;
    }
    .item-meta {
        display: flex;
        gap: 15px;
        font-size: 12px;
        color: #ccc;
        flex-wrap: wrap;
    }
    .badge {
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 10px;
        font-weight: bold;
        text-transform: uppercase;
    }
    .badge-pending { background: #f44336; color: white; }
    .badge-in_progress { background: #FF9800; color: white; }
    .badge-submitted { background: #4CAF50; color: white; }
    .badge-weight { background: rgba(255,204,0,0.2); color: #ffcc00; border: 1px solid #ffcc00; }
    .badge-score { background: rgba(33,150,243,0.2); color: #2196F3; border: 1px solid #2196F3; }
    .progress-section {
        margin-top: 30px;
    }
    .progress-bar {
        width: 100%;
        height: 8px;
        background: rgba(255,255,255,0.1);
        border-radius: 4px;
        overflow: hidden;
        margin: 10px 0;
    }
    .progress-fill {
        height: 100%;
        background: linear-gradient(90deg, #4CAF50, #45a049);
        border-radius: 4px;
        transition: width 0.5s ease;
    }
    .back-btn {
        background: #ffcc00;
        color: #000;
        border: none;
        padding: 12px 25px;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        margin-bottom: 20px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    .back-btn:hover {
        background: #e6b800;
        transform: translateX(-3px);
        text-decoration: none;
        color: #000;
    }
    .empty-state {
        text-align: center;
        padding: 40px 20px;
        color: rgba(255,255,255,0.5);
    }
    .empty-state .icon {
        font-size: 48px;
        margin-bottom: 15px;
        opacity: 0.5;
    }
    .resource-type {
        font-size: 10px;
        text-transform: uppercase;
        color: #ffcc00;
        font-weight: bold;
        margin-bottom: 5px;
    }
    .quick-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
        gap: 15px;
        margin: 20px 0;
    }
    .stat-box {
        background: rgba(255,255,255,0.08);
        padding: 15px;
        border-radius: 8px;
        text-align: center;
        border: 1px solid rgba(255,255,255,0.1);
    }
    .stat-number {
        font-size: 24px;
        font-weight: bold;
        color: #ffcc00;
        margin-bottom: 5px;
    }
    .stat-label {
        font-size: 11px;
        color: #ccc;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    @media (max-width: 768px) {
        .dashboard-grid {
            grid-template-columns: 1fr;
        }
        .unit-meta {
            flex-direction: column;
            gap: 10px;
        }
        .workspace {
            padding: 15px;
        }
    }
  </style>
</head>
<body>
<div class="workspace">
  <a href="dashboard.php" class="back-btn">← Back to Dashboard</a>
  
  <!-- Unit Header -->
  <div class="unit-header">
    <div class="unit-code"><?php echo htmlspecialchars($unit_code); ?></div>
    <h1 class="unit-name"><?php echo htmlspecialchars($unit['unit_name'] ?? 'Unit Name'); ?></h1>
    <div class="unit-meta">
        <div class="meta-item">📚 <?php echo $unit['credit_hours'] ?? '3'; ?> Credit Hours</div>
        <div class="meta-item">🎓 <?php echo $unit['lecturer'] ?? 'Dr. Professor Name'; ?></div>
        <div class="meta-item">📅 <?php echo $unit['semester'] ?? 'Semester 1 2025'; ?></div>
        <div class="meta-item">👤 <?php echo htmlspecialchars($student_name); ?></div>
    </div>
  </div>

  <!-- Quick Stats -->
  <div class="quick-stats">
    <div class="stat-box">
        <div class="stat-number"><?php echo count($assignments); ?></div>
        <div class="stat-label">Assignments</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?php echo count($cats); ?></div>
        <div class="stat-label">CATs</div>
    </div>
    <div class="stat-box">
        <div class="stat-number"><?php echo count($resources); ?></div>
        <div class="stat-label">Resources</div>
    </div>
    <div class="stat-box">
        <div class="stat-number">75%</div>
        <div class="stat-label">Progress</div>
    </div>
  </div>

  <!-- Main Dashboard Grid -->
  <div class="dashboard-grid">
    
    <!-- Assignments Section -->
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-icon">📝</div>
            <h3 class="card-title">Assignments</h3>
        </div>
        <?php if (!empty($assignments)): ?>
            <?php foreach ($assignments as $assignment): ?>
                <div class="assignment-item assignment-<?php echo $assignment['status']; ?>">
                    <div class="item-title"><?php echo htmlspecialchars($assignment['title']); ?></div>
                    <div class="item-meta">
                        <span>📅 <?php echo date('M j, Y', strtotime($assignment['due_date'])); ?></span>
                        <span class="badge badge-<?php echo $assignment['status']; ?>">
                            <?php echo str_replace('_', ' ', $assignment['status']); ?>
                        </span>
                        <span class="badge badge-weight"><?php echo $assignment['weight']; ?></span>
                        <?php if ($assignment['submitted'] && isset($assignment['score'])): ?>
                            <span class="badge badge-score">Score: <?php echo $assignment['score']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">📝</div>
                <p>No assignments posted yet</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- CATs Section -->
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-icon">📊</div>
            <h3 class="card-title">Continuous Assessment Tests</h3>
        </div>
        <?php if (!empty($cats)): ?>
            <?php foreach ($cats as $cat): ?>
                <div class="cat-item">
                    <div class="item-title"><?php echo htmlspecialchars($cat['title']); ?></div>
                    <div class="item-meta">
                        <span>📅 <?php echo date('M j, Y', strtotime($cat['date'])); ?></span>
                        <span class="badge badge-weight"><?php echo $cat['weight']; ?></span>
                        <?php if ($cat['score']): ?>
                            <span class="badge badge-score">Score: <?php echo $cat['score']; ?></span>
                        <?php else: ?>
                            <span class="badge badge-pending">Upcoming</span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">📊</div>
                <p>No CATs scheduled</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Resources Section -->
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-icon">📚</div>
            <h3 class="card-title">Learning Resources</h3>
        </div>
        <?php if (!empty($resources)): ?>
            <?php foreach ($resources as $resource): ?>
                <div class="resource-item">
                    <div class="resource-type"><?php echo ucfirst($resource['type']); ?></div>
                    <div class="item-title"><?php echo htmlspecialchars($resource['title']); ?></div>
                    <div class="item-meta">
                        <?php if (isset($resource['author'])): ?>
                            <span>👤 <?php echo htmlspecialchars($resource['author']); ?></span>
                        <?php endif; ?>
                        <?php if (isset($resource['edition'])): ?>
                            <span>Edition: <?php echo $resource['edition']; ?></span>
                        <?php endif; ?>
                        <?php if (isset($resource['duration'])): ?>
                            <span>⏱️ <?php echo $resource['duration']; ?></span>
                        <?php endif; ?>
                        <?php if (isset($resource['source'])): ?>
                            <span>📖 <?php echo $resource['source']; ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">📚</div>
                <p>No resources available</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Schedule Section -->
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-icon">🕒</div>
            <h3 class="card-title">Class Schedule</h3>
        </div>
        <?php if (!empty($schedule)): ?>
            <?php foreach ($schedule as $session): ?>
                <div class="schedule-item">
                    <div class="item-title"><?php echo htmlspecialchars($session['type']); ?> Session</div>
                    <div class="item-meta">
                        <span>📅 <?php echo $session['day']; ?></span>
                        <span>🕐 <?php echo $session['time']; ?></span>
                        <span>🏠 <?php echo $session['room']; ?></span>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="empty-state">
                <div class="icon">🕒</div>
                <p>No schedule available</p>
            </div>
        <?php endif; ?>
    </div>

  </div>

  <!-- Progress Section -->
  <div class="progress-section">
    <div class="dashboard-card">
        <div class="card-header">
            <div class="card-icon">📈</div>
            <h3 class="card-title">Course Progress</h3>
        </div>
        <div style="margin: 20px 0;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <span style="color: #ffcc00; font-weight: bold;">Overall Progress</span>
                <span style="color: #4CAF50; font-weight: bold;">75%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: 75%;"></div>
            </div>
        </div>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px;">
            <div style="text-align: center;">
                <div style="color: #4CAF50; font-size: 18px; font-weight: bold;">3/4</div>
                <div style="font-size: 11px; color: #ccc;">Topics Completed</div>
            </div>
            <div style="text-align: center;">
                <div style="color: #2196F3; font-size: 18px; font-weight: bold;">8/12</div>
                <div style="font-size: 11px; color: #ccc;">Lectures Attended</div>
            </div>
            <div style="text-align: center;">
                <div style="color: #FF9800; font-size: 18px; font-weight: bold;">2/3</div>
                <div style="font-size: 11px; color: #ccc;">Assignments Done</div>
            </div>
        </div>
    </div>
  </div>

</div>

</body>
</html>