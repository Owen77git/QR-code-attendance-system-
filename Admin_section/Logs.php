<?php
session_start();
if (!isset($_SESSION['admin'])) {
    header("Location: admin~login.php");
    exit();
}

include("../../API/db.connect.php");
$msg = "";

if (isset($_POST['delete_student']) && !empty($_POST['student_ids'])) {
    foreach ($_POST['student_ids'] as $id) {
        $id = $conn->real_escape_string($id);
        $conn->query("DELETE FROM students WHERE id = '$id'");
    }
    $msg = "Selected students deleted.";
}

if (isset($_POST['edit_student']) && !empty($_POST['student_ids'])) {
    $id = $conn->real_escape_string($_POST['student_ids'][0]);
    $fullName = $conn->real_escape_string($_POST['edit_full_name']);
    $email    = $conn->real_escape_string($_POST['edit_email']);
    $admission= $conn->real_escape_string($_POST['edit_admission_no']);

    if (!empty($fullName) || !empty($email) || !empty($admission)) {
        $updates = [];
        if (!empty($fullName)) $updates[] = "full_name='$fullName'";
        if (!empty($email)) $updates[] = "email='$email'";
        if (!empty($admission)) $updates[] = "admission_no='$admission'";
        $update_sql = implode(", ", $updates);
        $conn->query("UPDATE students SET $update_sql WHERE id='$id'");
        $msg = "Student updated successfully.";
    }
}

if (isset($_POST['delete_lecturer']) && !empty($_POST['lecturer_ids'])) {
    foreach ($_POST['lecturer_ids'] as $id) {
        $id = $conn->real_escape_string($id);
        $conn->query("DELETE FROM lecturers WHERE id = '$id'");
    }
    $msg = "Selected lecturers deleted.";
}

if (isset($_POST['edit_lecturer']) && !empty($_POST['lecturer_ids'])) {
    $id = $conn->real_escape_string($_POST['lecturer_ids'][0]);
    $fullName = $conn->real_escape_string($_POST['edit_full_name']);
    $email    = $conn->real_escape_string($_POST['edit_email']);
    $code     = $conn->real_escape_string($_POST['edit_code']);
    $unitAssigned = $conn->real_escape_string($_POST['edit_unit_assigned']);
    $group    = $conn->real_escape_string($_POST['edit_group']);

    if (!empty($fullName) || !empty($email) || !empty($code) || !empty($unitAssigned) || !empty($group)) {
        $updates = [];
        if (!empty($fullName)) $updates[] = "full_name='$fullName'";
        if (!empty($email)) $updates[] = "email='$email'";
        if (!empty($code)) $updates[] = "code='$code'";
        if (!empty($unitAssigned)) $updates[] = "unit_assigned='$unitAssigned'";
        if (!empty($group)) $updates[] = "group_no='$group'";
        $update_sql = implode(", ", $updates);
        $conn->query("UPDATE lecturers SET $update_sql WHERE id='$id'");
        $msg = "Lecturer updated successfully.";
    }
}

$students = $conn->query("SELECT id, full_name, email, admission_no, created_at FROM students ORDER BY created_at DESC");
$lecturers = $conn->query("SELECT id, full_name, email, code, unit_assigned, group_no, created_at FROM lecturers ORDER BY created_at DESC");
$units = $conn->query("SELECT unit_name FROM units ORDER BY unit_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Logs - Admin</title>
<link rel="stylesheet" href="../../CSS/Admin.CSS">
<style>
  table { width:100%; border-collapse: collapse; margin-top:15px; }
  th, td { padding:12px; text-align:left; border-bottom:1px solid rgba(255,255,255,0.1); }
  th { background: rgba(255,255,255,0.1); color:#ffcc00; }
  tr:hover { background: rgba(255,255,255,0.05); }
  .edit-box { margin-top:10px; padding:15px; background:rgba(255,255,255,0.05); border-radius:6px; border-left:4px solid #ffcc00; }
  .edit-box h4 { margin:0 0 10px 0; color:#ffcc00; }
  .edit-box input, .edit-box select { padding:8px 12px; margin:5px; border-radius:4px; border:1px solid #ccc; width:200px; }
  .btn-submit { background:#ffcc00; color:#000; padding:10px 20px; border:none; border-radius:6px; cursor:pointer; font-weight:bold; margin:5px; }
  .btn-submit.delete { background:#dc3545; color:white; }
  .btn-submit:hover { opacity:0.9; }
  .action-buttons { margin-top:15px; display:flex; gap:10px; flex-wrap:wrap; }

 /* Make tables scrollable on small screens */
  @media(max-width: 768px) {
    .workspace {
      grid-template-columns: 1fr; /* stack cards vertically */
      padding: 10px;
    }

    table {
      display: block;
      overflow-x: auto;
      white-space: nowrap;
    }

    table th, table td {
      padding: 8px;
      font-size: 0.85rem;
    }

    .edit-box input, .edit-box select {
      width: 100%; /* full width for inputs */
      margin: 5px 0;
    }

    .action-buttons {
      flex-direction: column;
    }

    .btn-submit {
      width: 100%; /* buttons full width */
      margin-bottom: 10px;
    }
  }

</style>
</head>
<body>
<div class="navbar">
  <h2 class="logo">Admin</h2>
  <nav class="menu">
    <a href="admin.php">Dashboard</a>
    <a href="Reports.php">Attendance Reports</a>
    <a href="Dev~Reg.php">Registration</a>
    <a href="Device.php">Devices</a>
    <a href="Logs.php" class="active">System Logs</a>
    <a href="admin~logout.php">Logout</a>
  </nav>
  <div class="hamburger" id="hamburger">&#9776;</div>
</div>

<nav class="mobile-menu" id="mobileMenu">
  <a href="admin.php">Dashboard</a>
  <a href="Reports.php">Attendance Reports</a>
  <a href="Dev~Reg.php">Registration</a>
  <a href="Device.php">Devices</a>
  <a href="Logs.php" class="active">System Logs</a>
  <a href="admin~logout.php">Logout</a>
</nav>

<?php if(!empty($msg)) { ?>
  <div class="popup-message" id="popupMessage"><?php echo $msg; ?></div>
<?php } ?>

<div class="workspace">

  <!-- Students Section -->
  <div class="card" style="grid-column: span 2;">
    <h3>Registered Students</h3>
    <form method="post">
      <table>
        <tr>
          <th><input type="checkbox" id="checkAllStudents"></th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Admission No</th>
          <th>Date Created</th>
        </tr>
        <?php if($students && $students->num_rows>0){
          while($row=$students->fetch_assoc()){ ?>
          <tr>
            <td><input type="checkbox" name="student_ids[]" value="<?php echo $row['id']; ?>"></td>
            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['admission_no']); ?></td>
            <td><?php echo $row['created_at']; ?></td>
          </tr>
        <?php }} else { ?>
        <tr>
          <td colspan="5" style="text-align:center; padding:20px;">No students registered yet</td>
        </tr>
        <?php } ?>
      </table>

      <div class="action-buttons">
        <button type="submit" name="delete_student" class="btn-submit delete">Delete Selected Students</button>
      </div>

      <div class="edit-box">
        <h4>Edit Student (select one student first)</h4>
        <input type="text" name="edit_full_name" placeholder="New Full Name">
        <input type="email" name="edit_email" placeholder="New Email">
        <input type="text" name="edit_admission_no" placeholder="New Admission No">
        <button type="submit" name="edit_student" class="btn-submit">Save Student Changes</button>
      </div>
    </form>
  </div>

  <!-- Lecturers Section -->
  <div class="card" style="grid-column: span 2;">
    <h3>Registered Lecturers</h3>
    <form method="post">
      <table>
        <tr>
          <th><input type="checkbox" id="checkAllLecturers"></th>
          <th>Full Name</th>
          <th>Email</th>
          <th>Code</th>
          <th>Unit Assigned</th>
          <th>Group</th>
          <th>Date Created</th>
        </tr>
        <?php if($lecturers && $lecturers->num_rows>0){
          while($row=$lecturers->fetch_assoc()){ ?>
          <tr>
            <td><input type="checkbox" name="lecturer_ids[]" value="<?php echo $row['id']; ?>"></td>
            <td><?php echo htmlspecialchars($row['full_name']); ?></td>
            <td><?php echo htmlspecialchars($row['email']); ?></td>
            <td><?php echo htmlspecialchars($row['code']); ?></td>
            <td><?php echo htmlspecialchars($row['unit_assigned']); ?></td>
            <td><?php echo htmlspecialchars($row['group_no']); ?></td>
            <td><?php echo $row['created_at']; ?></td>
          </tr>
        <?php }} else { ?>
        <tr>
          <td colspan="7" style="text-align:center; padding:20px;">No lecturers registered yet</td>
        </tr>
        <?php } ?>
      </table>

      <div class="action-buttons">
        <button type="submit" name="delete_lecturer" class="btn-submit delete">Delete Selected Lecturers</button>
      </div>

      <div class="edit-box">
        <h4>Edit Lecturer (select one lecturer first)</h4>
        <input type="text" name="edit_full_name" placeholder="New Full Name">
        <input type="email" name="edit_email" placeholder="New Email">
        <input type="text" name="edit_code" placeholder="New Code">
        
        <select name="edit_unit_assigned">
          <option value="">Select Unit</option>
          <?php 
          if($units && $units->num_rows>0){
            while($unit = $units->fetch_assoc()){
              echo "<option value='".$unit['unit_name']."'>".$unit['unit_name']."</option>";
            }
          }
          ?>
        </select>
        
        <select name="edit_group">
          <option value="">Select Group</option>
          <option value="Group 1">Group 1</option>
          <option value="Group 2">Group 2</option>
          <option value="Group 3">Group 3</option>
          <option value="Group 4">Group 4</option>
          <option value="Group 5">Group 5</option>
        </select>
        
        <button type="submit" name="edit_lecturer" class="btn-submit">Save Lecturer Changes</button>
      </div>
    </form>
  </div>

</div>

<script>
  // Hamburger menu
  document.getElementById("hamburger").addEventListener("click", function(){
    document.getElementById("mobileMenu").classList.toggle("show");
  });

  // Check all students
  document.getElementById("checkAllStudents").addEventListener("change", function(){
    document.querySelectorAll("input[name='student_ids[]']").forEach(cb=>cb.checked=this.checked);
  });

  // Check all lecturers
  document.getElementById("checkAllLecturers").addEventListener("change", function(){
    document.querySelectorAll("input[name='lecturer_ids[]']").forEach(cb=>cb.checked=this.checked);
  });

  // Auto-hide popup
  const popup = document.getElementById("popupMessage");
  if(popup){ setTimeout(()=>popup.style.display="none",3000); }

  // Ensure exactly one selected for edit
  document.querySelectorAll('form').forEach(form=>{
    form.addEventListener('submit', function(e){
      const editButtons = this.querySelectorAll('button[type="submit"][name^="edit_"]');
      editButtons.forEach(button=>{
        if(button===e.submitter && button.name.startsWith('edit_')){
          const checkboxes = this.querySelectorAll('input[type="checkbox"]:checked');
          if(checkboxes.length!==1){ e.preventDefault(); alert('Please select exactly one item to edit.'); }
        }
      });
    });
  });
</script>
</body>
</html>