<?php session_start(); include("config/db.php"); include("config/layout.php");
if(!isset($_SESSION['admin'])){header("Location: index.php");exit();}
$ay=get_active_year($conn); $yid=$ay['Year_ID'];

$total_stu=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM students"))['c'];
$total_approved=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM registrations WHERE status='Approved'"))['c'];
$total_pending=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM registrations WHERE status='Pending'"))['c'];
$total_waitlist=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM registrations WHERE status='Waitlisted'"))['c'];

$dept_report=mysqli_query($conn,"SELECT d.Dept_Name,d.Dept_Icon,
    (SELECT COUNT(*) FROM registrations r JOIN courses c ON r.Cou_ID=c.Cou_ID WHERE c.Dept_ID=d.Dept_ID AND r.status='Approved') AS approved,
    (SELECT COUNT(*) FROM registrations r JOIN courses c ON r.Cou_ID=c.Cou_ID WHERE c.Dept_ID=d.Dept_ID AND r.status='Pending') AS pending,
    (SELECT COUNT(*) FROM registrations r JOIN courses c ON r.Cou_ID=c.Cou_ID WHERE c.Dept_ID=d.Dept_ID AND r.status='Waitlisted') AS waitlisted,
    (SELECT COUNT(*) FROM courses WHERE Dept_ID=d.Dept_ID AND NVQ_Level='4' AND Is_Active=1) AS l4_courses,
    (SELECT COUNT(*) FROM courses WHERE Dept_ID=d.Dept_ID AND NVQ_Level='5' AND Is_Active=1) AS l5_courses
    FROM departments d ORDER BY d.Dept_Name");

page_head('Reports'); sidebar('reports.php');
?>
<div class="main">
<?php topbar('📊 Enrollment Reports','One-click academic statistics',$ay['Year_Label']); ?>
<div class="content">
<div class="stats-grid">
    <div class="stat-card"><div class="stat-icon" style="background:#eff6ff">👥</div><div><div class="stat-val"><?php echo $total_stu;?></div><div class="stat-lbl">Total Students</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#f0fdf4">✅</div><div><div class="stat-val"><?php echo $total_approved;?></div><div class="stat-lbl">Enrolled</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#fffbeb">⏳</div><div><div class="stat-val"><?php echo $total_pending;?></div><div class="stat-lbl">Pending</div></div></div>
    <div class="stat-card"><div class="stat-icon" style="background:#faf5ff">📋</div><div><div class="stat-val"><?php echo $total_waitlist;?></div><div class="stat-lbl">Waitlisted</div></div></div>
</div>
<div class="card">
<div class="card-head"><h3>📊 Enrollment by Department</h3>
<button class="btn btn-primary btn-sm" onclick="window.print()">🖨️ Print Report</button>
</div>
<div class="tbl-wrap"><table>
<thead><tr><th>Department</th><th>Level 4 Courses</th><th>Level 5 Courses</th><th>Approved</th><th>Pending</th><th>Waitlisted</th><th>Total</th></tr></thead>
<tbody>
<?php while($r=mysqli_fetch_assoc($dept_report)):
    $tot=$r['approved']+$r['pending']+$r['waitlisted'];
?>
<tr>
    <td><strong><?php echo $r['Dept_Icon'].' '.htmlspecialchars($r['Dept_Name']);?></strong></td>
    <td><span class="badge badge-yellow"><?php echo $r['l4_courses'];?></span></td>
    <td><span class="badge badge-green"><?php echo $r['l5_courses'];?></span></td>
    <td><span class="badge badge-green"><?php echo $r['approved'];?></span></td>
    <td><span class="badge badge-yellow"><?php echo $r['pending'];?></span></td>
    <td><span class="badge badge-purple"><?php echo $r['waitlisted'];?></span></td>
    <td><strong><?php echo $tot;?></strong></td>
</tr>
<?php endwhile;?>
</tbody>
</table></div></div>
</div></div>
</body></html>
