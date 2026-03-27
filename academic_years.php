<?php session_start(); include("config/db.php"); include("config/layout.php");
if(!isset($_SESSION['admin'])){header("Location: index.php");exit();}
if(isset($_POST['do_add'])){
    $label=trim($_POST['label']);
    $active=isset($_POST['is_active'])?1:0;
    if($active) mysqli_query($conn,"UPDATE academic_years SET Is_Active=0");
    $stmt=mysqli_prepare($conn,"INSERT INTO Academic_Years(Year_Label,Is_Active,Start_Date,End_Date) VALUES(?,?,?,?)");
    mysqli_stmt_bind_param($stmt,"siss",$label,$active,$_POST['start'],$_POST['end']);
    mysqli_stmt_execute($stmt);
    header("Location: academic_years.php"); exit();
}
if(isset($_GET['activate'])){
    $id=(int)$_GET['activate'];
    mysqli_query($conn,"UPDATE academic_years SET Is_Active=0");
    mysqli_query($conn,"UPDATE academic_years SET Is_Active=1 WHERE Year_ID=$id");
    header("Location: academic_years.php"); exit();
}
$years=mysqli_query($conn,"SELECT y.*,(SELECT COUNT(*) FROM courses WHERE Year_ID=y.Year_ID) AS cou_count,(SELECT COUNT(*) FROM registrations WHERE Year_ID=y.Year_ID) AS reg_count FROM academic_years y ORDER BY Year_ID DESC");
$ay=get_active_year($conn);
page_head('Academic Years'); sidebar('academic_years.php');
?>
<div class="main">
<?php topbar('📅 Academic Years','Manage academic year cycles &amp; version control',$ay['Year_Label']); ?>
<div class="content">
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <div style="font-size:13px;color:var(--muted)">Active Year: <strong style="color:var(--navy2)"><?php echo $ay['Year_Label'];?></strong></div>
  <button class="btn btn-primary" onclick="document.getElementById('addModal').classList.add('show')">➕ New Academic Year</button>
</div>
<div class="modal-overlay" id="addModal"><div class="modal">
  <h3>📅 Add Academic Year</h3>
  <form method="POST">
    <div class="field"><label>Year Label *</label><input type="text" name="label" required placeholder="e.g. 2026/2027"></div>
    <div class="field-row">
      <div class="field"><label>Start Date</label><input type="date" name="start"></div>
      <div class="field"><label>End Date</label><input type="date" name="end"></div>
    </div>
    <div class="field" style="display:flex;align-items:center;gap:10px"><input type="checkbox" name="is_active" id="ia" style="width:auto"><label for="ia" style="text-transform:none;font-size:13px">Set as active year</label></div>
    <div class="modal-foot"><button type="button" class="btn btn-outline" onclick="document.getElementById('addModal').classList.remove('show')">Cancel</button><button type="submit" name="do_add" class="btn btn-primary">Add Year</button></div>
  </form>
</div></div>
<div class="card">
<div class="tbl-wrap"><table>
<thead><tr><th>Year</th><th>Status</th><th>Start</th><th>End</th><th>Courses</th><th>Registrations</th><th>Action</th></tr></thead>
<tbody>
<?php while($y=mysqli_fetch_assoc($years)):?>
<tr>
  <td style="font-family:'Space Grotesk',sans-serif;font-weight:700;font-size:15px"><?php echo htmlspecialchars($y['Year_Label']);?></td>
  <td><?php echo $y['Is_Active']?'<span class="badge badge-green">✅ Active</span>':'<span class="badge badge-gray">Inactive</span>';?></td>
  <td style="font-size:12px"><?php echo $y['Start_Date']?date('d M Y',strtotime($y['Start_Date'])):'—';?></td>
  <td style="font-size:12px"><?php echo $y['End_Date']?date('d M Y',strtotime($y['End_Date'])):'—';?></td>
  <td><span class="badge badge-blue"><?php echo $y['cou_count'];?></span></td>
  <td><span class="badge badge-navy"><?php echo $y['reg_count'];?></span></td>
  <td><?php if(!$y['Is_Active']):?><a href="academic_years.php?activate=<?php echo $y['Year_ID'];?>" class="btn btn-sm btn-success" onclick="return confirm('Set as active year?')">Activate</a><?php else:?><span style="font-size:12px;color:var(--success);font-weight:600">● Active</span><?php endif;?></td>
</tr>
<?php endwhile;?>
</tbody>
</table></div>
</div>
</div></div>
</body></html>
