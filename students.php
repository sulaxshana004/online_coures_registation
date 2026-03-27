<?php
session_start();
include("config/db.php");
include("config/layout.php");

if(!isset($_SESSION['admin'])){
    header("Location: index.php");
    exit();
}

$ay = get_active_year($conn);
$admin_id = $_SESSION['admin_id'] ?? 0;
$admin_nm = $_SESSION['admin'] ?? '';

/* ---------------- DELETE STUDENT ---------------- */
if(isset($_GET['delete_id'])){
    $id = (int)$_GET['delete_id'];
    mysqli_query($conn,"DELETE FROM registrations WHERE Stu_ID=$id");
    mysqli_query($conn,"DELETE FROM students WHERE Stu_ID=$id");
    audit_log($conn, "Admin", $admin_id, $admin_nm, "DELETE_STUDENT", "Student", $id, "Deleted student");
    header("Location: students.php");
    exit();
}

/* ---------------- EDIT STUDENT ---------------- */
if(isset($_POST['do_edit'])){
    $id = (int)$_POST['edit_id'];
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $nic = trim($_POST['nic']);
    $nvq = $_POST['nvq_level'];
    $dept = $_POST['dept_id'] ? (int)$_POST['dept_id'] : NULL;

    $stmt = mysqli_prepare($conn,"UPDATE students SET Stu_Name=?, Stu_Email=?, Stu_NIC=?, NVQ_Level=?, Dept_ID=? WHERE Stu_ID=?");
    mysqli_stmt_bind_param($stmt,"ssssii",$name,$email,$nic,$nvq,$dept,$id);
    mysqli_stmt_execute($stmt);

    audit_log($conn, "Admin", $admin_id, $admin_nm, "EDIT_STUDENT", "Student", $id, "Edited student");
    header("Location: students.php");
    exit();
}

/* ---------------- STATUS ACTIONS ---------------- */
if(isset($_GET['suspend'])){
    $id=(int)$_GET['suspend'];
    mysqli_query($conn,"UPDATE students SET Account_Status='Suspended' WHERE Stu_ID=$id");
    header("Location: students.php");
    exit();
}

if(isset($_GET['activate'])){
    $id=(int)$_GET['activate'];
    mysqli_query($conn,"UPDATE students SET Account_Status='Active' WHERE Stu_ID=$id");
    header("Location: students.php");
    exit();
}

if(isset($_GET['approve'])){
    $id=(int)$_GET['approve'];
    mysqli_query($conn,"UPDATE students SET Account_Status='Active' WHERE Stu_ID=$id");

    notify_student($conn, $id, '✅ Account Approved', 'Your account has been approved! You can now log in and apply for courses.', 'success');
    audit_log($conn, "Admin", $admin_id, $admin_nm, "APPROVE_STUDENT", "Student", $id, "Student approved");
    header("Location: students.php");
    exit();
}

if(isset($_GET['reject'])){
    $id=(int)$_GET['reject'];
    mysqli_query($conn,"UPDATE students SET Account_Status='Rejected' WHERE Stu_ID=$id");

    notify_student($conn, $id, '❌ Registration Rejected', 'Your registration was not approved. Contact admin for details.', 'error');
    audit_log($conn, "Admin", $admin_id, $admin_nm, "REJECT_STUDENT", "Student", $id, "Student rejected");
    header("Location: students.php");
    exit();
}

/* ---------------- FILTERS ---------------- */
$fs = $_GET['status'] ?? '';
$search = isset($_GET['s']) ? trim($_GET['s']) : '';
$fn = $_GET['nvq'] ?? '';
$fd = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;

$where="WHERE 1=1";
if($search){
    $esc=mysqli_real_escape_string($conn,$search);
    $where.=" AND (s.Stu_Name LIKE '%$esc%' OR s.Stu_NIC LIKE '%$esc%' OR s.Stu_Email LIKE '%$esc%')";
}
if(in_array($fn,['4','5'])) $where.=" AND s.NVQ_Level='$fn'";
if($fd) $where.=" AND s.Dept_ID=$fd";
if(in_array($fs,['Pending','Active','Suspended','Rejected'])) $where.=" AND s.Account_Status='$fs'";

/* ---------------- QUERY ---------------- */
$students=mysqli_query($conn,"
SELECT s.*, d.Dept_Name, d.Dept_Icon,
(SELECT App_ID FROM registrations WHERE Stu_ID=s.Stu_ID AND Year_ID='{$ay['Year_ID']}' LIMIT 1) AS App_ID,
(SELECT status FROM registrations WHERE Stu_ID=s.Stu_ID AND Year_ID='{$ay['Year_ID']}' LIMIT 1) AS reg_status
FROM students s
LEFT JOIN departments d ON s.Dept_ID=d.Dept_ID
$where
ORDER BY s.created_at DESC
");

$total=mysqli_num_rows($students);

/* ---------------- STATUS COUNT ---------------- */
function count_stu_status($conn,$st){
    $r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM students WHERE Account_Status='$st'"));
    return $r['c'];
}

/* ---------------- DEPARTMENTS ---------------- */
$dept_rows=[];
$dq=mysqli_query($conn,"SELECT Dept_ID, Dept_Name, Dept_Icon FROM departments ORDER BY Dept_Name");
while($r=mysqli_fetch_assoc($dq)) $dept_rows[]=$r;

/* ---------------- PAGE ---------------- */
page_head('Students');
sidebar('students.php');
?>

<div class="main">
<?php topbar('👥 Students','All registered students & account management',$ay['Year_Label']); ?>

<div class="content">

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal">
<div class="modal modal-lg">
<h3>✏️ Edit Student</h3>
<form method="POST">
<input type="hidden" name="edit_id" id="ei">
<div class="field-row">
    <div class="field">
        <label>Student Name *</label>
        <input type="text" name="name" id="en" required>
    </div>
    <div class="field">
        <label>Email *</label>
        <input type="email" name="email" id="ee" required>
    </div>
</div>
<div class="field-row3">
    <div class="field">
        <label>NIC *</label>
        <input type="text" name="nic" id="enic" required>
    </div>
    <div class="field">
        <label>NVQ Level</label>
        <select name="nvq_level" id="envq">
            <option value="4">NVQ Level 4</option>
            <option value="5">NVQ Level 5</option>
        </select>
    </div>
    <div class="field">
        <label>Department</label>
        <select name="dept_id" id="edept">
            <option value="">-- No Department --</option>
            <?php foreach($dept_rows as $dr): ?>
            <option value="<?php echo $dr['Dept_ID']; ?>">
                <?php echo $dr['Dept_Icon'].' '.htmlspecialchars($dr['Dept_Name']); ?>
            </option>
            <?php endforeach; ?>
        </select>
    </div>
</div>
<div class="modal-foot">
    <button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button>
    <button type="submit" name="do_edit" class="btn btn-primary">💾 Save Changes</button>
</div>
</form>
</div>
</div>

<!-- STUDENTS TABLE -->
<div class="card">
<div class="tbl-wrap">
<table>
<thead>
<tr>
<th>Student</th>
<th>NIC</th>
<th>NVQ</th>
<th>Department</th>
<th>Status</th>
<th>Joined</th>
<th>Actions</th>
</tr>
</thead>
<tbody>
<?php if($total>0): while($r=mysqli_fetch_assoc($students)): ?>
<tr>
<td>
<div style="font-weight:700"><?php echo htmlspecialchars($r['Stu_Name']); ?></div>
<div style="font-size:11px;color:var(--muted)"><?php echo htmlspecialchars($r['Stu_Email']); ?></div>
</td>
<td style="font-family:monospace;font-size:12px"><?php echo htmlspecialchars($r['Stu_NIC']); ?></td>
<td><?php echo $r['NVQ_Level']=='5'?'<span class="badge badge-green">L5</span>':'<span class="badge badge-yellow">L4</span>'; ?></td>
<td><?php echo $r['Dept_ID']?"<span class='badge badge-blue'>{$r['Dept_Icon']} ".htmlspecialchars($r['Dept_Name'])."</span>":'<span class="badge badge-gray">—</span>'; ?></td>
<td><span class="badge"><?php echo $r['Account_Status']; ?></span></td>
<td style="font-size:11px;color:var(--muted)"><?php echo date('d M Y',strtotime($r['created_at'])); ?></td>
<td>
<div style="display:flex;gap:6px">
<button class="btn btn-sm btn-outline"
    data-id="<?php echo $r['Stu_ID']; ?>"
    data-name="<?php echo htmlspecialchars($r['Stu_Name'],ENT_QUOTES); ?>"
    data-email="<?php echo htmlspecialchars($r['Stu_Email'],ENT_QUOTES); ?>"
    data-nic="<?php echo htmlspecialchars($r['Stu_NIC'],ENT_QUOTES); ?>"
    data-nvq="<?php echo $r['NVQ_Level']; ?>"
    data-dept="<?php echo (int)$r['Dept_ID']; ?>"
    onclick="openEdit(this)">✏️</button>

<a href="students.php?delete_id=<?php echo $r['Stu_ID']; ?>" class="btn btn-sm btn-danger"
    onclick="return confirm('Delete this student?')">🗑</a>
</div>
</td>
</tr>
<?php endwhile; else: ?>
<tr><td colspan="7"><div class="empty-state"><div class="empty-icon">👥</div><p>No students found.</p></div></td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</div>

<script>
function openModal(id){document.getElementById(id).classList.add('show');}
function closeModal(id){document.getElementById(id).classList.remove('show');}
function openEdit(b){
    document.getElementById('ei').value=b.dataset.id;
    document.getElementById('en').value=b.dataset.name;
    document.getElementById('ee').value=b.dataset.email;
    document.getElementById('enic').value=b.dataset.nic;
    document.getElementById('envq').value=b.dataset.nvq;
    document.getElementById('edept').value=b.dataset.dept;
    openModal('editModal');
}
document.querySelectorAll('.modal-overlay').forEach(e=>e.addEventListener('click',ev=>{if(ev.target===e)e.classList.remove('show');}));
</script>
</body>
</html>