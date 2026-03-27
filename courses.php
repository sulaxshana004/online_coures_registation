<?php
session_start(); 
include("config/db.php"); 
include("config/layout.php");

if(!isset($_SESSION['admin'])){
    header("Location: index.php");
    exit();
}

$ay = get_active_year($conn); 
$yid = $ay['Year_ID'];
$admin_id = $_SESSION['admin_id']; 
$admin_nm = $_SESSION['admin'];

// DELETE
if(isset($_GET['delete_id'])){
    $id = (int)$_GET['delete_id'];
    mysqli_query($conn,"DELETE FROM registrations WHERE Cou_ID=$id");
    mysqli_query($conn,"DELETE FROM courses WHERE Cou_ID=$id");
    audit_log($conn,'Admin',$admin_id,$admin_nm,'DELETE_COURSE','Course',$id,"Deleted course ID $id");
    header("Location: courses.php"); 
    exit();
}

// ADD
if(isset($_POST['do_add'])){
    $nvq = in_array($_POST['nvq_level'],['4','5']) ? $_POST['nvq_level'] : '4';
    $dept = $_POST['dept_id'] ? (int)$_POST['dept_id'] : null;
    $seats = (int)($_POST['max_seats'] ?? 30);
    $cnt = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM courses WHERE Dept_ID='$dept' AND NVQ_Level='$nvq' AND Year_ID='$yid' AND Is_Active=1"))['c'];
    if($cnt >= 6){
        $_SESSION['flash'] = 'error:Department Cap Reached — Maximum 6 courses per Department per NVQ Level per Academic Year.';
    } else {
        $stmt = mysqli_prepare($conn,"INSERT INTO courses(Cou_Name,Cou_Duration,Cou_Description,Cou_Qualification,NVQ_Level,Dept_ID,Year_ID,Max_Seats) VALUES(?,?,?,?,?,?,?,?)");
        $name = trim($_POST['name']); 
        $dur = trim($_POST['duration'] ?? ''); 
        $desc = trim($_POST['desc'] ?? ''); 
        $qual = trim($_POST['qual'] ?? '');
        mysqli_stmt_bind_param($stmt,"sssssiis",$name,$dur,$desc,$qual,$nvq,$dept,$yid,$seats);
        mysqli_stmt_execute($stmt);
        $new_id = mysqli_insert_id($conn);
        audit_log($conn,'Admin',$admin_id,$admin_nm,'ADD_COURSE','Course',$new_id,"Added course: {$_POST['name']} L$nvq");
        $_SESSION['flash'] = 'success:Course added successfully.';
    }
    header("Location: courses.php");
    exit();
}

// EDIT
if(isset($_POST['do_edit'])){
    $id = (int)$_POST['edit_id'];
    $nvq = in_array($_POST['nvq_level'],['4','5']) ? $_POST['nvq_level'] : '4';
    $dept = $_POST['dept_id'] ? (int)$_POST['dept_id'] : null;
    $seats = (int)($_POST['max_seats'] ?? 30);
    $stmt = mysqli_prepare($conn,"UPDATE courses SET Cou_Name=?,Cou_Duration=?,Cou_Description=?,Cou_Qualification=?,NVQ_Level=?,Dept_ID=?,Max_Seats=? WHERE Cou_ID=?");
    $name = trim($_POST['name']); 
    $dur = trim($_POST['duration'] ?? ''); 
    $desc = trim($_POST['desc'] ?? ''); 
    $qual = trim($_POST['qual'] ?? '');
    mysqli_stmt_bind_param($stmt,"sssssisi",$name,$dur,$desc,$qual,$nvq,$dept,$seats,$id);
    mysqli_stmt_execute($stmt);
    audit_log($conn,'Admin',$admin_id,$admin_nm,'EDIT_COURSE','Course',$id,"Edited course: {$_POST['name']}");
    $_SESSION['flash'] = 'success:Course updated.';
    header("Location: courses.php");
    exit();
}

$flash = $_SESSION['flash'] ?? ''; 
unset($_SESSION['flash']);

$search = isset($_GET['s']) ? trim($_GET['s']) : '';
$fd = isset($_GET['dept']) ? (int)$_GET['dept'] : 0;
$fn = isset($_GET['nvq']) ? $_GET['nvq'] : '';

$where = "WHERE 1=1";
if($search) $where .= " AND c.Cou_Name LIKE '%".mysqli_real_escape_string($conn,$search)."%'";
if($fd) $where .= " AND c.Dept_ID=$fd";
if(in_array($fn,['4','5'])) $where .= " AND c.NVQ_Level='$fn'";

$courses = mysqli_query($conn,"SELECT c.*,d.Dept_Name,d.Dept_Icon,(SELECT COUNT(*) FROM registrations WHERE Cou_ID=c.Cou_ID AND status NOT IN('Rejected','Withdrawn')) AS enrolled FROM courses c LEFT JOIN departments d ON c.Dept_ID=d.Dept_ID $where ORDER BY c.NVQ_Level,d.Dept_Name,c.Cou_Name");

$total = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM courses"))['c'];

$dept_list = mysqli_query($conn,"SELECT Dept_ID,Dept_Name,Dept_Icon FROM departments ORDER BY Dept_Name");
$dept_rows = []; 
while($dr = mysqli_fetch_assoc($dept_list)) $dept_rows[] = $dr;

// <-- Removed the question mark here -->
$dept_opts = '<option value="">No Department</option>';
foreach($dept_rows as $dr) {
    $dept_opts .= "<option value='{$dr['Dept_ID']}'>{$dr['Dept_Icon']} {$dr['Dept_Name']}</option>";
}

page_head('Courses');
?>
<?php sidebar('courses.php'); ?>
<div class="main">
<?php topbar('📚 Courses','Manage all courses — max 6 per dept per NVQ level',$ay['Year_Label']); ?>
<div class="content">
<?php if($flash):[$ft,$fm]=explode(':',$flash,2); echo "<div class='alert alert-".($ft=='success'?'success':'error')."'>".($ft=='success'?'✅':'⚠️')." ".htmlspecialchars($fm)."</div>"; endif; ?>

<!-- ADD MODAL -->
<div class="modal-overlay" id="addModal"><div class="modal modal-lg">
  <h3>➕ Add New Course</h3>
  <form method="POST">
    <div class="field-row">
      <div class="field"><label>Course Name *</label><input type="text" name="name" required placeholder="e.g. Network Engineering"></div>
      <div class="field"><label>Duration</label><input type="text" name="duration" placeholder="e.g. 12 Months"></div>
    </div>
    <div class="field-row3">
      <div class="field"><label>NVQ Level *</label><select name="nvq_level" required><option value="4">NVQ Level 4</option><option value="5">NVQ Level 5</option></select></div>
      <div class="field"><label>Department</label><select name="dept_id"><?php echo $dept_opts;?></select></div>
      <div class="field"><label>Max Seats</label><input type="number" name="max_seats" value="30" min="1" max="200"></div>
    </div>
    <div class="field"><label>Description</label><textarea name="desc" placeholder="Course description..."></textarea></div>
    <div class="field"><label>Qualification Requirements</label><textarea name="qual" placeholder="e.g. O/L Passed with minimum 6 subjects..." style="min-height:90px"></textarea></div>
    <p style="font-size:11px;color:var(--muted);margin-top:6px;">⚠️ System enforces maximum 6 courses per Department per NVQ Level per Academic Year.</p>
    <div class="modal-foot"><button type="button" class="btn btn-outline" onclick="closeModal('addModal')">Cancel</button><button type="submit" name="do_add" class="btn btn-primary">➕ Add Course</button></div>
  </form>
</div></div>

<!-- EDIT MODAL -->
<div class="modal-overlay" id="editModal"><div class="modal modal-lg">
  <h3>✏️ Edit Course</h3>
  <form method="POST">
    <input type="hidden" name="edit_id" id="ei">
    <div class="field-row"><div class="field"><label>Course Name *</label><input type="text" name="name" id="en" required></div><div class="field"><label>Duration</label><input type="text" name="duration" id="ed"></div></div>
    <div class="field-row3">
      <div class="field"><label>NVQ Level *</label><select name="nvq_level" id="envq"><option value="4">NVQ Level 4</option><option value="5">NVQ Level 5</option></select></div>
      <div class="field"><label>Department</label><select name="dept_id" id="edept"><?php echo $dept_opts;?></select></div>
      <div class="field"><label>Max Seats</label><input type="number" name="max_seats" id="eseats" min="1"></div>
    </div>
    <div class="field"><label>Description</label><textarea name="desc" id="edesc"></textarea></div>
    <div class="field"><label>Qualification Requirements</label><textarea name="qual" id="equal" placeholder="e.g. O/L Passed with minimum 6 subjects..." style="min-height:90px"></textarea></div>
    <div class="modal-foot"><button type="button" class="btn btn-outline" onclick="closeModal('editModal')">Cancel</button><button type="submit" name="do_edit" class="btn btn-primary">💾 Save Changes</button></div>
  </form>
</div></div>

<form method="GET">
<div class="filter-bar">
  <input type="text" name="s" placeholder="🔍 Search courses..." value="<?php echo htmlspecialchars($search);?>">
  <select name="dept">
      <option value="">All Departments</option>
      <?php foreach($dept_rows as $dr): ?>
          <option value="<?php echo $dr['Dept_ID']; ?>" <?php echo $fd==$dr['Dept_ID']?'selected':''; ?>>
              <?php echo $dr['Dept_Icon'].' '.htmlspecialchars($dr['Dept_Name']); ?>
          </option>
      <?php endforeach; ?>
  </select>
  <select name="nvq"><option value="">All NVQ Levels</option><option value="4" <?php echo $fn=='4'?'selected':'';?>>NVQ Level 4</option><option value="5" <?php echo $fn=='5'?'selected':'';?>>NVQ Level 5</option></select>
  <button type="submit" class="btn btn-primary btn-sm">Filter</button>
  <?php if($search||$fd||$fn):?><a href="courses.php" style="font-size:13px;color:var(--danger);font-weight:600;text-decoration:none">✕ Clear</a><?php endif;?>
  <span class="badge badge-blue" style="margin-left:auto">Total: <?php echo $total;?></span>
  <button type="button" class="btn btn-primary" onclick="openModal('addModal')">➕ Add Course</button>
</div>
</form>

<div class="card">
<div class="tbl-wrap">
<table>
<thead><tr><th>ID</th><th>Course Name</th><th>NVQ</th><th>Department</th><th>Seats</th><th>Duration</th><th>Qualification</th><th>Year</th><th>Actions</th></tr></thead>
<tbody>
<?php if(mysqli_num_rows($courses)>0): while($row=mysqli_fetch_assoc($courses)):
  $pct=$row['Max_Seats']>0?min(100,round($row['enrolled']/$row['Max_Seats']*100)):0;
  $full=$row['enrolled']>=$row['Max_Seats'];
?>
<tr>
  <td><span class="badge badge-navy">#<?php echo $row['Cou_ID'];?></span></td>
  <td style="font-weight:600"><?php echo htmlspecialchars($row['Cou_Name']);?></td>
  <td><?php echo $row['NVQ_Level']=='5'?'<span class="badge badge-green">⭐ Level 5</span>':'<span class="badge badge-yellow">🎓 Level 4</span>';?></td>
  <td><?php echo !empty($row['Dept_Name'])?"<span class='badge badge-blue'>{$row['Dept_Icon']} ".htmlspecialchars($row['Dept_Name'])."</span>":'<span class="badge badge-gray">—</span>';?></td>
  <td>
    <div style="font-size:12px;font-weight:600;color:<?php echo $full?'var(--danger)':'var(--text)';?>"><?php echo $row['enrolled'];?>/<?php echo $row['Max_Seats'];?></div>
    <div class="progress-bar" style="margin-top:4px;width:70px"><div class="progress-fill" style="width:<?php echo $pct;?>%;<?php echo $full?'background:#ef4444':'';?>"></div></div>
  </td>
  <td style="font-size:12px"><?php echo htmlspecialchars($row['Cou_Duration']??'—');?></td>
  <td style="font-size:12px;max-width:180px;">
    <?php if(!empty($row['Cou_Qualification'])): ?>
    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:5px 9px;color:#1d4ed8;font-size:11px;line-height:1.4;"><?php echo htmlspecialchars(substr($row['Cou_Qualification'],0,80)).(strlen($row['Cou_Qualification'])>80?'...':''); ?></div>
    <?php else: ?><span style="color:var(--muted);font-size:11px">—</span><?php endif; ?>
  </td>
  <td><span class="badge badge-gray" style="font-size:10px"><?php echo $ay['Year_Label'];?></span></td>
  <td>
    <div style="display:flex;gap:6px">
      <button class="btn btn-sm btn-outline" data-id="<?php echo $row['Cou_ID'];?>" data-name="<?php echo htmlspecialchars($row['Cou_Name'],ENT_QUOTES);?>" data-nvq="<?php echo $row['NVQ_Level'];?>" data-dept="<?php echo (int)($row['Dept_ID']??0);?>" data-duration="<?php echo htmlspecialchars($row['Cou_Duration']??'',ENT_QUOTES);?>" data-desc="<?php echo htmlspecialchars($row['Cou_Description']??'',ENT_QUOTES);?>" data-qual="<?php echo htmlspecialchars($row['Cou_Qualification']??'',ENT_QUOTES);?>" data-seats="<?php echo $row['Max_Seats'];?>" onclick="openEdit(this)">✏️</button>
      <a href="courses.php?delete_id=<?php echo $row['Cou_ID'];?>" class="btn btn-sm btn-danger" onclick="return confirm('Delete this course and all its registrations?')">🗑</a>
    </div>
  </td>
</tr>
<?php endwhile; else:?>
<tr><td colspan="9"><div class="empty-state"><div class="empty-icon">📚</div><p>No courses found.</p></div></td></tr>
<?php endif;?>
</tbody>
</table>
</div>
</div>
</div></div>
<script>
function openModal(id){document.getElementById(id).classList.add('show');}
function closeModal(id){document.getElementById(id).classList.remove('show');}
function openEdit(b){
  document.getElementById('ei').value=b.dataset.id;
  document.getElementById('en').value=b.dataset.name;
  document.getElementById('ed').value=b.dataset.duration;
  document.getElementById('edesc').value=b.dataset.desc;
  document.getElementById('equal').value=b.dataset.qual||'';
  document.getElementById('eseats').value=b.dataset.seats;
  var s=document.getElementById('envq');for(var i=0;i<s.options.length;i++)s.options[i].selected=(s.options[i].value==b.dataset.nvq);
  var d=document.getElementById('edept');for(var i=0;i<d.options.length;i++)d.options[i].selected=(d.options[i].value==b.dataset.dept);
  openModal('editModal');
}
document.querySelectorAll('.modal-overlay').forEach(e=>e.addEventListener('click',ev=>{if(ev.target===e)e.classList.remove('show');}));
</script>
</body></html>