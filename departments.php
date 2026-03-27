<?php session_start(); include("config/db.php"); include("config/layout.php");
if(!isset($_SESSION['admin'])){header("Location: index.php");exit();}
$ay=get_active_year($conn);
if(isset($_POST['do_add'])){
    $stmt=mysqli_prepare($conn,"INSERT INTO Departments(Dept_Name,Dept_Code,Dept_Icon,Max_Seats) VALUES(?,?,?,?)");
    $seats=(int)$_POST['seats'];
    mysqli_stmt_bind_param($stmt,"sssi",$_POST['name'],$_POST['code'],$_POST['icon'],$seats);
    mysqli_stmt_execute($stmt);
    header("Location: departments.php"); exit();
}
if(isset($_POST['do_edit'])){
    $id=(int)$_POST['eid'];
    $seats=(int)$_POST['seats'];
    $stmt=mysqli_prepare($conn,"UPDATE Departments SET Dept_Name=?,Dept_Code=?,Dept_Icon=?,Max_Seats=? WHERE Dept_ID=?");
    mysqli_stmt_bind_param($stmt,"sssii",$_POST['name'],$_POST['code'],$_POST['icon'],$seats,$id);
    mysqli_stmt_execute($stmt);
    header("Location: departments.php"); exit();
}
$depts=mysqli_query($conn,"SELECT d.*,(SELECT COUNT(*) FROM courses WHERE Dept_ID=d.Dept_ID AND Is_Active=1) AS cou_count,(SELECT COUNT(*) FROM students WHERE Dept_ID=d.Dept_ID) AS stu_count FROM departments d ORDER BY d.Dept_Name");
page_head('Departments'); sidebar('departments.php');
?>
<div class="main">
<?php topbar('🏫 Departments','The 6 technology departments',$ay['Year_Label']); ?>
<div class="content">
<div style="display:flex;justify-content:flex-end;margin-bottom:18px"><button class="btn btn-primary" onclick="document.getElementById('addM').classList.add('show')">➕ Add Department</button></div>
<div class="modal-overlay" id="addM"><div class="modal"><h3>➕ Add Department</h3><form method="POST"><div class="field"><label>Name *</label><input type="text" name="name" required></div><div class="field-row"><div class="field"><label>Code *</label><input type="text" name="code" required maxlength="10"></div><div class="field"><label>Icon</label><input type="text" name="icon" value="🏫" maxlength="5"></div></div><div class="field"><label>Max Seats</label><input type="number" name="seats" value="30" min="1"></div><div class="modal-foot"><button type="button" class="btn btn-outline" onclick="document.getElementById('addM').classList.remove('show')">Cancel</button><button type="submit" name="do_add" class="btn btn-primary">Add</button></div></form></div></div>
<div class="modal-overlay" id="editM"><div class="modal"><h3>✏️ Edit Department</h3><form method="POST"><input type="hidden" name="eid" id="eid"><div class="field"><label>Name *</label><input type="text" name="name" id="edn" required></div><div class="field-row"><div class="field"><label>Code *</label><input type="text" name="code" id="edc" required maxlength="10"></div><div class="field"><label>Icon</label><input type="text" name="icon" id="edi" maxlength="5"></div></div><div class="field"><label>Max Seats</label><input type="number" name="seats" id="eds" min="1"></div><div class="modal-foot"><button type="button" class="btn btn-outline" onclick="document.getElementById('editM').classList.remove('show')">Cancel</button><button type="submit" name="do_edit" class="btn btn-primary">Save</button></div></form></div></div>
<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(300px,1fr));gap:18px">
<?php while($d=mysqli_fetch_assoc($depts)):?>
<div class="card" style="padding:22px">
  <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px">
    <div style="width:52px;height:52px;background:linear-gradient(135deg,#eff6ff,#dbeafe);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:26px;border:1px solid var(--border)"><?php echo $d['Dept_Icon'];?></div>
    <div><div style="font-family:'Space Grotesk',sans-serif;font-size:15px;font-weight:700;color:var(--navy2)"><?php echo htmlspecialchars($d['Dept_Name']);?></div><div style="font-size:11px;color:var(--muted);margin-top:2px">Code: <?php echo htmlspecialchars($d['Dept_Code']);?> · Max Seats: <?php echo $d['Max_Seats'];?></div></div>
  </div>
  <div style="display:flex;gap:12px;margin-bottom:16px">
    <div style="flex:1;background:var(--off);border-radius:10px;padding:11px;text-align:center"><div style="font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:700;color:var(--blue2)"><?php echo $d['cou_count'];?></div><div style="font-size:11px;color:var(--muted)">Active Courses</div></div>
    <div style="flex:1;background:var(--off);border-radius:10px;padding:11px;text-align:center"><div style="font-family:'Space Grotesk',sans-serif;font-size:20px;font-weight:700;color:var(--navy2)"><?php echo $d['stu_count'];?></div><div style="font-size:11px;color:var(--muted)">Students</div></div>
  </div>
  <div style="display:flex;gap:8px">
    <button class="btn btn-sm btn-outline" style="flex:1" data-id="<?php echo $d['Dept_ID'];?>" data-name="<?php echo htmlspecialchars($d['Dept_Name'],ENT_QUOTES);?>" data-code="<?php echo htmlspecialchars($d['Dept_Code'],ENT_QUOTES);?>" data-icon="<?php echo $d['Dept_Icon'];?>" data-seats="<?php echo $d['Max_Seats'];?>" onclick="openEditDept(this)">✏️ Edit</button>
    <a href="courses.php?dept=<?php echo $d['Dept_ID'];?>" class="btn btn-sm btn-primary" style="flex:1;text-align:center">📚 Courses</a>
  </div>
</div>
<?php endwhile;?>
</div>
</div></div>
<script>
function openEditDept(b){document.getElementById('eid').value=b.dataset.id;document.getElementById('edn').value=b.dataset.name;document.getElementById('edc').value=b.dataset.code;document.getElementById('edi').value=b.dataset.icon;document.getElementById('eds').value=b.dataset.seats;document.getElementById('editM').classList.add('show');}
document.querySelectorAll('.modal-overlay').forEach(e=>e.addEventListener('click',ev=>{if(ev.target===e)e.classList.remove('show');}));
</script>
</body></html>
