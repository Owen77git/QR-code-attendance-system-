<?php
session_start();
include("../../API/db.connect.php");

if (!isset($_SESSION['student'])) {
    header("Location: student_log.php");
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// 🧩 Get the student's admission code (e.g., dcf111) from session
$admission_no = $_SESSION['student'];
$student_id = "";
$full_name = "";

// ✅ Fetch student info
$sql = "SELECT id, full_name, admission_no FROM students WHERE admission_no = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $admission_no);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $student = $result->fetch_assoc();
    $student_id = $student['id'];
    $full_name = $student['full_name'];
}

// ✅ Get first 3 characters of admission number for unit matching
$admission_prefix = substr($admission_no, 0, 3);

// ✅ Fetch units that match the first 3 characters of student's admission code
$units = [];
$sql_units = "SELECT unit_code, unit_name FROM units WHERE unit_code LIKE ?";
$stmt_units = $conn->prepare($sql_units);
$search_pattern = $admission_prefix . '%';
$stmt_units->bind_param("s", $search_pattern);
$stmt_units->execute();
$result_units = $stmt_units->get_result();

if ($result_units && $result_units->num_rows > 0) {
    while ($row = $result_units->fetch_assoc()) {
        $units[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Student Dashboard</title>
<link rel="stylesheet" href="../../CSS/Dashboard.CSS">
<style>
body {
  font-family: 'Segoe UI', sans-serif;
  background: url('https://library.zetech.ac.ke/images/slide1.jpg') no-repeat center center fixed;
  background-size: cover;
  margin: 0;
  color: #fff;
}
.topnav {
  background: rgba(30,60,114,0.9);
  color: white;
  font-weight: bold;
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 30px;
  position: fixed;
  top: 0; left: 0;
  width: 100%;
  z-index: 1000;
}
.menu { display: flex; gap: 20px; }
.menu a {
  color: white;
  text-decoration: none;
  position: relative;
}
.menu a::after {
  content: "";
  position: absolute;
  bottom: -2px;
  left: 0;
  width: 0;
  height: 2px;
  background: #ffcc00;
  transition: width 0.3s;
}
.menu a:hover::after { width: 100%; }
.menu a.active::after { width: 100%; }
.mobile-bg-overlay {
  position: fixed;
  top: 0; left: 0;
  width: 100%; height: 100%;
  background: rgba(0,0,0,0.5);
  opacity: 0;
  pointer-events: none;
  transition: opacity 0.4s ease;
  z-index: 1500;
}
.mobile-bg-overlay.show {
  opacity: 1;
  pointer-events: all;
}
.mobile-menu-overlay {
  position: fixed;
  top: 0;
  right: 0;
  width: 80%;
  max-width: 320px;
  height: 100%;
  background: rgba(10, 10, 30, 0.97);
  transform: translateX(100%);
  opacity: 0;
  transition: transform 0.5s ease, opacity 0.5s ease;
  z-index: 2000;
  box-shadow: -5px 0 15px rgba(0,0,0,0.4);
}
.mobile-menu-overlay.show {
  transform: translateX(0);
  opacity: 1;
}
.mobile-menu-panel {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 60px 20px;
  gap: 15px;
  position: relative;
  height: 100%;
  overflow-y: auto;
}
.close-btn {
  position: absolute;
  top: 10px;
  right: 25px;
  font-size: 30px;
  color: #fff;
  cursor: pointer;
  font-weight: bold;
  transition: color 0.3s ease;
}
.close-btn:hover { color: #ffcc00; }
.mobile-menu-panel a {
  display: block;
  width: 80%;
  text-align: center;
  background: rgba(255,255,255,0.1);
  color: #fff;
  text-decoration: none;
  font-size: 18px;
  font-weight: 600;
  padding: 12px 0;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,0.2);
  transition: all 0.4s ease;
  box-shadow: 0 3px 6px rgba(0,0,0,0.2);
}
.mobile-menu-panel a:hover {
  background: #ffcc00;
  color: #000;
  transform: scale(1.03);
  border-color: #ffcc00;
}
.unit {
  cursor: pointer;
  transition: transform 0.3s ease;
}
.unit:hover {
  transform: translateY(-5px);
}
.unit-link {
  text-decoration: none;
  color: inherit;
  display: block;
  width: 100%;
  height: 100%;
}
.modal {
  display: none;
  position: fixed;
  z-index: 3000;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  background-color: rgba(0,0,0,0.8);
}
.modal-content {
  background: rgba(30,60,114,0.95);
  margin: 2% auto;
  padding: 30px;
  border-radius: 15px;
  width: 90%;
  max-width: 1000px;
  max-height: 90vh;
  overflow-y: auto;
  color: white;
  box-shadow: 0 10px 30px rgba(0,0,0,0.5);
  border: 2px solid #ffcc00;
}
.close-modal {
  color: #ffcc00;
  float: right;
  font-size: 32px;
  font-weight: bold;
  cursor: pointer;
  transition: color 0.3s ease;
}
.close-modal:hover {
  color: white;
}
.unit-header {
  background: linear-gradient(135deg, rgba(255,204,0,0.1), rgba(255,204,0,0.05));
  padding: 25px;
  border-radius: 10px;
  margin-bottom: 25px;
  border-left: 5px solid #ffcc00;
}
.unit-code-popup { 
  color: #ffcc00; 
  font-size: 16px;
  font-weight: bold;
  text-transform: uppercase;
  letter-spacing: 1px;
}
.unit-name-popup {
  color: white;
  font-size: 32px;
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
  grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
  gap: 20px;
  margin-bottom: 25px;
}
.dashboard-card {
  background: rgba(255,255,255,0.05);
  border-radius: 10px;
  padding: 20px;
  border: 1px solid rgba(255,255,255,0.1);
  transition: transform 0.3s ease;
}
.dashboard-card:hover {
  transform: translateY(-3px);
}
.card-header {
  display: flex;
  align-items: center;
  margin-bottom: 15px;
  padding-bottom: 10px;
  border-bottom: 1px solid rgba(255,255,255,0.1);
}
.card-icon {
  font-size: 20px;
  margin-right: 12px;
  color: #ffcc00;
}
.card-title {
  color: #ffcc00;
  font-size: 16px;
  font-weight: bold;
  margin: 0;
}
.assignment-item, .cat-item, .resource-item, .schedule-item {
  background: rgba(255,255,255,0.08);
  padding: 12px;
  margin: 8px 0;
  border-radius: 6px;
  border-left: 4px solid;
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
  margin-bottom: 5px;
  font-size: 13px;
}
.item-meta {
  display: flex;
  gap: 10px;
  font-size: 11px;
  color: #ccc;
  flex-wrap: wrap;
}
.badge {
  padding: 2px 8px;
  border-radius: 10px;
  font-size: 9px;
  font-weight: bold;
  text-transform: uppercase;
}
.badge-pending { background: #f44336; color: white; }
.badge-in_progress { background: #FF9800; color: white; }
.badge-submitted { background: #4CAF50; color: white; }
.badge-weight { background: rgba(255,204,0,0.2); color: #ffcc00; border: 1px solid #ffcc00; }
.badge-score { background: rgba(33,150,243,0.2); color: #2196F3; border: 1px solid #2196F3; }
.empty-state {
  text-align: center;
  padding: 20px;
  color: rgba(255,255,255,0.5);
  font-style: italic;
}
.resource-type {
  font-size: 9px;
  text-transform: uppercase;
  color: #ffcc00;
  font-weight: bold;
  margin-bottom: 3px;
}
.quick-stats {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
  gap: 12px;
  margin: 15px 0;
}
.stat-box {
  background: rgba(255,255,255,0.08);
  padding: 12px;
  border-radius: 6px;
  text-align: center;
  border: 1px solid rgba(255,255,255,0.1);
}
.stat-number {
  font-size: 18px;
  font-weight: bold;
  color: #ffcc00;
  margin-bottom: 3px;
}
.stat-label {
  font-size: 10px;
  color: #ccc;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
@media (max-width: 768px) {
  .menu { display: none; }
  .hamburger {
    display: block;
    font-size: 28px;
    cursor: pointer;
    color: white;
  }
  .modal-content {
    width: 95%;
    margin: 1% auto;
    padding: 20px;
  }
  .dashboard-grid {
    grid-template-columns: 1fr;
  }
  .unit-meta {
    flex-direction: column;
    gap: 8px;
  }
}
@media (max-width: 768px) {
  .unit-list {
    grid-template-columns: 1fr;
    padding: 10px 15px;
    margin-top: 10px;
  }
  .unit {
    margin: 10px 5px;
  }
}
    /* ✅ Mobile Responsive Popup with Safe Padding */
@media (max-width: 768px) {
  .modal-content {
    width: 94%;
    margin: 4% auto;
    padding: 18px 20px; /* ✅ Adds left/right padding */
    border-radius: 12px;
    font-size: 14px;
    max-height: 90vh;
    overflow-y: auto;
    box-sizing: border-box;
  }

  .unit-header {
    padding: 15px 18px; /* ✅ Consistent internal spacing */
    margin-bottom: 15px;
  }

  .unit-name-popup {
    font-size: 22px;
    text-align: center;
    margin-bottom: 10px;
    word-wrap: break-word;
  }

  .unit-meta {
    flex-direction: column;
    align-items: flex-start;
    gap: 10px;
    padding: 0 5px; /* ✅ Prevents items from touching edges */
  }

  .dashboard-grid {
    grid-template-columns: 1fr;
    gap: 15px;
    padding: 0 5px;
  }

  .dashboard-card {
    padding: 15px;
    border-radius: 10px;
  }

  .quick-stats {
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
    margin: 10px 0;
    padding: 0 5px;
  }

  .close-modal {
    font-size: 28px;
    position: absolute;
    top: 10px;
    right: 15px;
  }
}

/* ✅ Ultra-small screens (below 400px wide) */
@media (max-width: 400px) {
  .modal-content {
    width: 96%;
    margin: 3% auto;
    padding: 15px 16px;
    font-size: 13px;
  }

  .unit-name-popup {
    font-size: 18px;
  }

  .dashboard-card {
    padding: 12px;
  }

  .card-title {
    font-size: 14px;
  }

  .item-title {
    font-size: 12px;
  }

  .item-meta {
    font-size: 10px;
    gap: 5px;
  }
}

</style>
</head>

<body>
  <div class="topnav">
    <div class="logo"><b>Student Portal</b></div>
    <nav class="menu">
      <a href="home.php">Home</a>
      <a href="dashboard.php" class="active">Dashboard</a>
      <a href="scan.attendance.php">Attendance</a>
      <a href="student_out.php">Logout</a>
    </nav>
    <div class="hamburger" id="hamburger">&#9776;</div>
  </div>

  <div class="mobile-bg-overlay" id="mobileBg"></div>
  <div class="mobile-menu-overlay" id="mobileMenuOverlay">
    <div class="mobile-menu-panel">
      <span class="close-btn" id="closeMenu">&times;</span>
      <a href="home.php">Home</a>
      <a href="dashboard.php" class="active">Dashboard</a>
      <a href="scan.attendance.php">Attendance</a>
      <a href="student_out.php">Logout</a>
    </div>
  </div>

  <!-- Unit Popup Modal -->
  <div id="unitModal" class="modal">
    <div class="modal-content">
      <span class="close-modal">&times;</span>
      <div id="unitModalContent">
        <!-- Unit content will be loaded here dynamically -->
      </div>
    </div>
  </div>

  <section style="margin-top:80px;">
    <h1>My Courses</h1>
    <div class="unit-list">
      <?php if (count($units) > 0): ?>
        <?php foreach ($units as $unit): ?>
          <div class="unit" onclick="showUnitPopup('<?php echo $unit['unit_code']; ?>', '<?php echo $unit['unit_name']; ?>')">
            <div class="unit-icon">🎓</div>
            <div class="unit-info">
              <div class="unit-code">| <?php echo $unit['unit_code']; ?></div>
              <div class="unit-name"><?php echo $unit['unit_name']; ?></div>
              <div class="unit-semester">SEMESTER 1 2025/2026</div>
            </div>
            <div class="unit-progress"><?php echo rand(5, 80); ?>% complete</div>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="unit">
          <div class="unit-icon">🎓</div>
          <div class="unit-info">
            <div class="unit-code">| No Units</div>
            <div class="unit-name">No courses found for your admission group (<?php echo htmlspecialchars($admission_prefix); ?>)</div>
            <div class="unit-semester">Contact Administrator</div>
          </div>
          <div class="unit-progress">0% complete</div>
        </div>
      <?php endif; ?>
    </div>
  </section>

  <footer>
    <div class="footer">
      <div class="b1"><h1>Contact Us :</h1>
        <u><b>📌 Academic & Learning Support</b></u><br>
        Linda Digital School (LDS):...<br>
        +254714588863 / +254769582811...<br>
        Email: elearning@Linda.ac.ke / lds@linda.ac.ke
      </div>
      <div class="b2"><u><b>📌 Administration & Student Services</b></u><br>
        Registrar's Office: +254721211174<br>
        Dean of Students (Faith Githinji): 0721411140 | faith.githinji@zetech.ac.ke<br>
        Student Finance (Town): +254727598626<br>
        Student Finance (TRC): +254769581885<br>
        Student Finance (Mangu): +254743830180
      </div>
      <div class="b3"><u><b>📌 Campuses, Support & Operations</b></u><br>
        EASS TRC: +254746627590 / 0795617907<br>
        Main Campus: +254714588869<br>
        IT Support: itsupport@linda.ac.ke<br>
        Procurement: 0757311482 | procument@linda.ac.ke
      </div>
      <div id="logo"><img src="../../Assets/school-removebg-preview.png"></div>
    </div>
  </footer>

  <p>© 2025 Linda Tuition | Build your future. All Rights Reserved.</p>

  <script>
  const hamburger = document.getElementById("hamburger");
  const mobileOverlay = document.getElementById("mobileMenuOverlay");
  const closeMenu = document.getElementById("closeMenu");
  const mobileBg = document.getElementById("mobileBg");
  const unitModal = document.getElementById("unitModal");
  const closeModal = document.querySelector(".close-modal");
  const unitModalContent = document.getElementById("unitModalContent");

  hamburger.addEventListener("click", () => {
    mobileOverlay.classList.add("show");
    mobileBg.classList.add("show");
  });
  closeMenu.addEventListener("click", () => {
    mobileOverlay.classList.remove("show");
    mobileBg.classList.remove("show");
  });
  mobileBg.addEventListener("click", () => {
    mobileOverlay.classList.remove("show");
    mobileBg.classList.remove("show");
  });

  // Mock data for unit information
  const unitData = {
    assignments: [
      {id: 1, title: 'Research Paper on AI Ethics', due_date: '2025-02-15', status: 'pending', weight: '15%', submitted: false},
      {id: 2, title: 'Programming Assignment 1', due_date: '2025-02-28', status: 'in_progress', weight: '10%', submitted: false},
      {id: 3, title: 'Group Project Proposal', due_date: '2025-01-20', status: 'submitted', weight: '5%', submitted: true, score: '8/10'}
    ],
    cats: [
      {id: 1, title: 'CAT 1 - Fundamentals', date: '2025-03-10', weight: '15%', score: null},
      {id: 2, title: 'CAT 2 - Advanced Topics', date: '2025-04-05', weight: '15%', score: null}
    ],
    resources: [
      {type: 'textbook', title: 'Core Course Textbook', author: 'Dr. Smith Johnson', edition: '3rd'},
      {type: 'slides', title: 'Lecture Slides Week 1-6', author: 'Prof. Sarah Wilson'},
      {type: 'video', title: 'Introduction to Course Concepts', duration: '45 min'},
      {type: 'article', title: 'Recent Research Papers Collection', source: 'IEEE Library'}
    ],
    schedule: [
      {day: 'Monday', time: '10:00 - 12:00', room: 'LT-101', type: 'Lecture'},
      {day: 'Wednesday', time: '14:00 - 16:00', room: 'LAB-A', type: 'Practical'},
      {day: 'Friday', time: '09:00 - 10:00', room: 'LT-101', type: 'Tutorial'}
    ]
  };

  function showUnitPopup(unitCode, unitName) {
    // Create unit content HTML
    const unitContent = `
      <div class="unit-header">
        <div class="unit-code-popup">${unitCode}</div>
        <h1 class="unit-name-popup">${unitName}</h1>
        <div class="unit-meta">
          <div class="meta-item">📚 3 Credit Hours</div>
          <div class="meta-item">🎓 Dr. Professor Name</div>
          <div class="meta-item">📅 Semester 1 2025</div>
          <div class="meta-item">👤 <?php echo htmlspecialchars($full_name); ?></div>
        </div>
      </div>

      <div class="quick-stats">
        <div class="stat-box">
          <div class="stat-number">${unitData.assignments.length}</div>
          <div class="stat-label">Assignments</div>
        </div>
        <div class="stat-box">
          <div class="stat-number">${unitData.cats.length}</div>
          <div class="stat-label">CATs</div>
        </div>
        <div class="stat-box">
          <div class="stat-number">${unitData.resources.length}</div>
          <div class="stat-label">Resources</div>
        </div>
        <div class="stat-box">
          <div class="stat-number">75%</div>
          <div class="stat-label">Progress</div>
        </div>
      </div>

      <div class="dashboard-grid">
        <!-- Assignments Section -->
        <div class="dashboard-card">
          <div class="card-header">
            <div class="card-icon">📝</div>
            <h3 class="card-title">Assignments</h3>
          </div>
          ${unitData.assignments.length > 0 ? unitData.assignments.map(assignment => `
            <div class="assignment-item assignment-${assignment.status}">
              <div class="item-title">${assignment.title}</div>
              <div class="item-meta">
                <span>📅 ${new Date(assignment.due_date).toLocaleDateString()}</span>
                <span class="badge badge-${assignment.status}">${assignment.status.replace('_', ' ')}</span>
                <span class="badge badge-weight">${assignment.weight}</span>
                ${assignment.submitted && assignment.score ? `<span class="badge badge-score">Score: ${assignment.score}</span>` : ''}
              </div>
            </div>
          `).join('') : '<div class="empty-state">No assignments posted yet</div>'}
        </div>

        <!-- CATs Section -->
        <div class="dashboard-card">
          <div class="card-header">
            <div class="card-icon">📊</div>
            <h3 class="card-title">Continuous Assessment Tests</h3>
          </div>
          ${unitData.cats.length > 0 ? unitData.cats.map(cat => `
            <div class="cat-item">
              <div class="item-title">${cat.title}</div>
              <div class="item-meta">
                <span>📅 ${new Date(cat.date).toLocaleDateString()}</span>
                <span class="badge badge-weight">${cat.weight}</span>
                ${cat.score ? `<span class="badge badge-score">Score: ${cat.score}</span>` : '<span class="badge badge-pending">Upcoming</span>'}
              </div>
            </div>
          `).join('') : '<div class="empty-state">No CATs scheduled</div>'}
        </div>

        <!-- Resources Section -->
        <div class="dashboard-card">
          <div class="card-header">
            <div class="card-icon">📚</div>
            <h3 class="card-title">Learning Resources</h3>
          </div>
          ${unitData.resources.length > 0 ? unitData.resources.map(resource => `
            <div class="resource-item">
              <div class="resource-type">${resource.type}</div>
              <div class="item-title">${resource.title}</div>
              <div class="item-meta">
                ${resource.author ? `<span>👤 ${resource.author}</span>` : ''}
                ${resource.edition ? `<span>Edition: ${resource.edition}</span>` : ''}
                ${resource.duration ? `<span>⏱️ ${resource.duration}</span>` : ''}
                ${resource.source ? `<span>📖 ${resource.source}</span>` : ''}
              </div>
            </div>
          `).join('') : '<div class="empty-state">No resources available</div>'}
        </div>

        <!-- Schedule Section -->
        <div class="dashboard-card">
          <div class="card-header">
            <div class="card-icon">🕒</div>
            <h3 class="card-title">Class Schedule</h3>
          </div>
          ${unitData.schedule.length > 0 ? unitData.schedule.map(session => `
            <div class="schedule-item">
              <div class="item-title">${session.type} Session</div>
              <div class="item-meta">
                <span>📅 ${session.day}</span>
                <span>🕐 ${session.time}</span>
                <span>🏠 ${session.room}</span>
              </div>
            </div>
          `).join('') : '<div class="empty-state">No schedule available</div>'}
        </div>
      </div>
    `;

    unitModalContent.innerHTML = unitContent;
    unitModal.style.display = "block";
  }

  closeModal.addEventListener("click", () => {
    unitModal.style.display = "none";
  });
  
  window.addEventListener("click", (event) => {
    if (event.target === unitModal) {
      unitModal.style.display = "none";
    }
  });
  </script>
</body>
</html>