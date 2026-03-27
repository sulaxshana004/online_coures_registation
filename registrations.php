<?php
session_start(); include("config/db.php"); include("config/layout.php");
if(!isset($_SESSION['admin'])){header("Location: index.php");exit();}
$admin_id  = $_SESSION['admin_id'];
$admin_nm  = $_SESSION['admin'];
$ay        = get_active_year($conn);
$yid       = $ay['Year_ID'];

// ── APPROVE ──────────────────────────────────────────────────
if(isset($_POST['do_approve'])){
    $rid=(int)$_POST['reg_id'];
    $r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT r.*,c.Max_Seats,c.Cou_ID,c.Cou_Name,s.Stu_ID,s.Stu_Name FROM registrations r JOIN courses c ON r.Cou_ID=c.Cou_ID JOIN students s ON r.Stu_ID=s.Stu_ID WHERE r.Reg_ID=$rid"));
    if($r){
        $enrolled=get_enrolled_count($conn,$r['Cou_ID']);
        if($enrolled>=$r['Max_Seats']){
            $msg='error:Course is full — cannot approve.';
        } else {
            mysqli_query($conn,"UPDATE registrations SET status='Approved',reviewed_at=NOW() WHERE Reg_ID=$rid");
            notify_student($conn,$r['Stu_ID'],'✅ Application Approved',"Your application for {$r['Cou_Name']} has been approved! Welcome aboard.",'success');
            audit_log($conn,'Admin',$admin_id,$admin_nm,'APPROVE_APPLICATION','Registration',$rid,"Approved {$r['App_ID']} for {$r['Stu_Name']}");
            $msg='success:Application approved successfully.';
        }
    }else{
        $msg='error:Registration not found.';
    }
    $_SESSION['flash']=$msg; header("Location: registrations.php"); exit();
}

// ── REJECT ───────────────────────────────────────────────────
if(isset($_POST['do_reject'])){
    $rid=(int)$_POST['reg_id'];
    $reason=mysqli_real_escape_string($conn,trim($_POST['reason']));
    $r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT r.*,c.Cou_ID,c.Cou_Name,s.Stu_ID,s.Stu_Name FROM registrations r JOIN courses c ON r.Cou_ID=c.Cou_ID JOIN students s ON r.Stu_ID=s.Stu_ID WHERE r.Reg_ID=$rid"));
    if($r){
        mysqli_query($conn,"UPDATE registrations SET status='Rejected',rejection_reason='$reason',reviewed_at=NOW() WHERE Reg_ID=$rid");
        notify_student($conn,$r['Stu_ID'],'❌ Application Not Approved',"Your application for {$r['Cou_Name']} was not approved. Reason: $reason",'error');
        audit_log($conn,'Admin',$admin_id,$admin_nm,'REJECT_APPLICATION','Registration',$rid,"Rejected {$r['App_ID']}");
        // Auto-promote waitlisted
        $nxt=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM registrations WHERE Cou_ID={$r['Cou_ID']} AND status='Waitlisted' ORDER BY Waitlist_Position ASC LIMIT 1"));
        if($nxt){
            mysqli_query($conn,"UPDATE registrations SET status='Pending',Waitlist_Position=NULL WHERE Reg_ID={$nxt['Reg_ID']}");
            notify_student($conn,$nxt['Stu_ID'],'🎉 Waitlist Promoted',"A seat opened! Your application for {$r['Cou_Name']} moved to Pending.",'success');
        }
    }
    $_SESSION['flash']='success:Application rejected.';
    header("Location: registrations.php"); exit();
}

$flash=$_SESSION['flash']??''; unset($_SESSION['flash']);
$filter=isset($_GET['filter'])?$_GET['filter']:'';
$search=isset($_GET['s'])?trim($_GET['s']):'';

// Build WHERE — NO year filter, show ALL registrations
$where="WHERE 1=1";
if($filter && in_array($filter,['Pending','Approved','Rejected','Waitlisted','Doc_Review','Withdrawn']))
    $where.=" AND r.status='$filter'";
if($search)
    $where.=" AND (s.Stu_Name LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR r.App_ID LIKE '%".mysqli_real_escape_string($conn,$search)."%' OR s.Stu_NIC LIKE '%".mysqli_real_escape_string($conn,$search)."%')";

$regs=mysqli_query($conn,"
    SELECT r.*,
           s.Stu_Name, s.Stu_Email, s.Stu_NIC, s.Stu_Phone,
           s.NVQ_Level, s.Doc_Verified,
           c.Cou_Name, c.Cou_Duration,
           d.Dept_Name, d.Dept_Icon
    FROM registrations r
    JOIN students s  ON r.Stu_ID = s.Stu_ID
    JOIN courses c   ON r.Cou_ID = c.Cou_ID
    LEFT JOIN departments d ON c.Dept_ID = d.Dept_ID
    $where
    ORDER BY r.registered_at DESC
");

if(!$regs){
    // Show DB error for debugging
    die("Query Error: ".mysqli_error($conn));
}

$total=mysqli_num_rows($regs);

function count_status($conn,$st){
    $r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM registrations WHERE status='$st'"));
    return $r?$r['c']:0;
}
$total_all=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM registrations"))['c']??0;

page_head('Registrations');
?>
<style>
.status-tabs{display:flex;gap:8px;flex-wrap:wrap;margin-bottom:20px;}
.st-tab{
  padding:8px 16px;border-radius:10px;font-size:12px;font-weight:700;
  text-decoration:none;border:1.5px solid var(--border);
  color:var(--muted);background:#fff;transition:.2s;
}
.st-tab:hover,.st-tab.active{border-color:var(--sb-dark);color:var(--sb-dark);background:var(--accent);}
.st-tab .cnt{
  background:var(--sb-dark);color:#fff;
  padding:1px 8px;border-radius:100px;font-size:10px;margin-left:5px;
}
.app-id-badge{
  font-family:"Space Grotesk",sans-serif;font-size:11px;font-weight:700;
  color:var(--sb-dark);background:var(--accent);
  border:1px solid var(--border);padding:3px 9px;border-radius:7px;
  white-space:nowrap;
}
.stu-name{font-weight:700;font-size:13px;color:var(--text);}
.stu-sub{font-size:11px;color:var(--muted);margin-top:1px;}
.course-name{font-size:13px;font-weight:600;color:var(--text);}
.dept-tag{font-size:11px;color:var(--muted);margin-top:2px;}
/* Status pills */
.s-pending{background:#FFFDE7;color:#E65100;border:1px solid #FFF176;padding:4px 11px;border-radius:100px;font-size:11px;font-weight:700;}
.s-approved{background:#E8F5E9;color:#2E7D32;border:1px solid #A5D6A7;padding:4px 11px;border-radius:100px;font-size:11px;font-weight:700;}
.s-rejected{background:#FFEBEE;color:#C62828;border:1px solid #FFCDD2;padding:4px 11px;border-radius:100px;font-size:11px;font-weight:700;}
.s-waitlisted{background:#F3E5F5;color:#6A1B9A;border:1px solid #CE93D8;padding:4px 11px;border-radius:100px;font-size:11px;font-weight:700;}
.s-doc_review{background:var(--accent);color:var(--sb-dark);border:1px solid var(--border);padding:4px 11px;border-radius:100px;font-size:11px;font-weight:700;}
.s-withdrawn{background:#F5F5F5;color:#616161;border:1px solid #E0E0E0;padding:4px 11px;border-radius:100px;font-size:11px;font-weight:700;}
</style>
<?php sidebar('registrations.php'); ?>
<div class="main">
<?php topbar('📋 Registrations','Manage all student applications &amp; enrollment',$ay['Year_Label']); ?>
<div class="content">

<?php if($flash):[$ft,$fm]=explode(':',$flash,2); ?>
<div class="alert alert-<?php echo $ft=='success'?'success':'error'; ?>">
  <?php echo $ft=='success'?'✅':'⚠️'; ?> <?php echo htmlspecialchars($fm); ?>
</div>
<?php endif; ?>

<!-- STATUS TABS -->
<div class="status-tabs">
  <a href="registrations.php" class="st-tab <?php echo!$filter?'active':''; ?>">
    All <span class="cnt"><?php echo $total_all; ?></span>
  </a>
  <a href="?filter=Pending" class="st-tab <?php echo$filter=='Pending'?'active':''; ?>">
    ⏳ Pending <span class="cnt"><?php echo count_status($conn,'Pending'); ?></span>
  </a>
  <a href="?filter=Approved" class="st-tab <?php echo$filter=='Approved'?'active':''; ?>">
    ✅ Approved <span class="cnt"><?php echo count_status($conn,'Approved'); ?></span>
  </a>
  <a href="?filter=Waitlisted" class="st-tab <?php echo$filter=='Waitlisted'?'active':''; ?>">
    📋 Waitlisted <span class="cnt"><?php echo count_status($conn,'Waitlisted'); ?></span>
  </a>
  <a href="?filter=Rejected" class="st-tab <?php echo$filter=='Rejected'?'active':''; ?>">
    ❌ Rejected <span class="cnt"><?php echo count_status($conn,'Rejected'); ?></span>
  </a>
</div>

<!-- SEARCH -->
<form method="GET" style="margin-bottom:18px;display:flex;gap:10px;align-items:center;">
  <?php if($filter): ?>
  <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
  <?php endif; ?>
  <input type="text" name="s"
    placeholder="🔍 Search by student name, NIC or App ID..."
    value="<?php echo htmlspecialchars($search); ?>"
    style="flex:1;padding:10px 14px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;outline:none;background:#fff;max-width:400px;">
  <button type="submit" class="btn btn-primary btn-sm">Search</button>
  <?php if($search): ?>
  <a href="registrations.php<?php echo$filter?"?filter=$filter":''; ?>"
     style="font-size:13px;color:var(--danger);font-weight:600;text-decoration:none;">✕ Clear</a>
  <?php endif; ?>
  <span class="badge badge-blue" style="margin-left:auto;"><?php echo $total; ?> results</span>
</form>

<!-- TABLE -->
<div class="card">
<div class="tbl-wrap">
<table>
<thead>
  <tr>
    <th>App ID</th>
    <th>Student Details</th>
    <th>NVQ</th>
    <th>Course / Department</th>
    <th>Document</th>
    <th>Status</th>
    <th>Applied Date</th>
    <th>Actions</th>
  </tr>
</thead>
<tbody>
<?php if($total>0): ?>
<?php while($r=mysqli_fetch_assoc($regs)):
  $sc='s-'.strtolower($r['status']);
  $nvq_cls=$r['NVQ_Level']=='5'?'badge-green':'badge-yellow';
?>
<tr>
  <!-- App ID -->
  <td>
    <span class="app-id-badge"><?php echo htmlspecialchars($r['App_ID']??'—'); ?></span>
  </td>

  <!-- Student -->
  <td>
    <div class="stu-name"><?php echo htmlspecialchars($r['Stu_Name']); ?></div>
    <div class="stu-sub">📧 <?php echo htmlspecialchars($r['Stu_Email']??'—'); ?></div>
    <div class="stu-sub">🪪 <?php echo htmlspecialchars($r['Stu_NIC']??'—'); ?></div>
  </td>

  <!-- NVQ -->
  <td>
    <span class="badge <?php echo $nvq_cls; ?>">
      Level <?php echo htmlspecialchars($r['NVQ_Level']??'—'); ?>
    </span>
  </td>

  <!-- Course / Dept -->
  <td>
    <div class="course-name"><?php echo htmlspecialchars($r['Cou_Name']??'—'); ?></div>
    <div class="dept-tag">
      <?php echo htmlspecialchars($r['Dept_Icon']??''); ?>
      <?php echo htmlspecialchars($r['Dept_Name']??'—'); ?>
    </div>
    <?php if(!empty($r['Cou_Duration'])): ?>
    <div class="stu-sub">⏱ <?php echo htmlspecialchars($r['Cou_Duration']); ?></div>
    <?php endif; ?>
  </td>

  <!-- Document -->
  <td>
    <?php if($r['Doc_Verified']): ?>
      <span class="badge badge-green">✅ Verified</span>
    <?php elseif(!empty($r['Doc_Upload'] ?? '')): ?>
      <span class="badge badge-yellow">📄 Uploaded</span>
    <?php else: ?>
      <span class="badge badge-gray">— None</span>
    <?php endif; ?>
  </td>

  <!-- Status -->
  <td>
    <span class="<?php echo $sc; ?>">
      <?php echo str_replace('_',' ',$r['status']); ?>
    </span>
    <?php if($r['status']=='Waitlisted' && !empty($r['Waitlist_Position'])): ?>
    <div style="font-size:10px;color:var(--muted);margin-top:3px;">
      Position #<?php echo $r['Waitlist_Position']; ?>
    </div>
    <?php endif; ?>
    <?php if(!empty($r['rejection_reason'])): ?>
    <div style="font-size:10px;color:#C62828;margin-top:3px;" title="<?php echo htmlspecialchars($r['rejection_reason']); ?>">
      Reason: <?php echo htmlspecialchars(substr($r['rejection_reason'],0,30)); ?>...
    </div>
    <?php endif; ?>
  </td>

  <!-- Date -->
  <td style="font-size:12px;color:var(--muted);white-space:nowrap;">
    <?php echo date('d M Y',strtotime($r['registered_at'])); ?>
    <div style="font-size:10px;"><?php echo date('H:i',strtotime($r['registered_at'])); ?></div>
  </td>

  <!-- Actions -->
  <td>
    <div style="display:flex;gap:6px;flex-wrap:wrap;">
    <?php if(in_array($r['status'],['Pending','Doc_Review'])): ?>
      <form method="POST" style="display:inline">
        <input type="hidden" name="reg_id" value="<?php echo $r['Reg_ID']; ?>">
        <button name="do_approve" class="btn btn-sm btn-success"
          onclick="return confirm('Approve <?php echo addslashes($r['Stu_Name']); ?>\'s application?')">
          ✅ Approve
        </button>
      </form>
      <button class="btn btn-sm btn-danger"
        onclick="openReject(<?php echo $r['Reg_ID']; ?>, '<?php echo addslashes($r['Stu_Name']); ?>')">
        ❌ Reject
      </button>
    <?php elseif($r['status']=='Approved'): ?>
      <span style="font-size:12px;color:#2E7D32;font-weight:700;">● Enrolled</span>
    <?php elseif($r['status']=='Rejected'): ?>
      <span style="font-size:12px;color:#C62828;font-weight:700;">● Rejected</span>
    <?php elseif($r['status']=='Waitlisted'): ?>
      <span style="font-size:12px;color:#6A1B9A;font-weight:700;">● Waitlisted</span>
    <?php endif; ?>
    </div>
  </td>
</tr>
<?php endwhile; ?>
<?php else: ?>
<tr>
  <td colspan="8">
    <div class="empty-state">
      <div class="empty-icon">📋</div>
      <p>No registrations found.</p>
      <?php if($filter||$search): ?>
      <p style="margin-top:8px;font-size:12px;">
        <a href="registrations.php" style="color:var(--sb-dark);font-weight:600;">Clear filters →</a>
      </p>
      <?php endif; ?>
    </div>
  </td>
</tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>

<!-- REJECT MODAL -->
<div class="modal-overlay" id="rejectModal">
  <div class="modal">
    <h3>❌ Reject Application</h3>
    <p id="rejectStudentName" style="font-size:13px;color:var(--muted);margin-bottom:16px;"></p>
    <form method="POST">
      <input type="hidden" name="reg_id" id="rj_reg_id">
      <div class="field">
        <label>Rejection Reason *</label>
        <textarea name="reason" required
          placeholder="Please provide a clear reason for rejection..."
          style="min-height:100px;"></textarea>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('rejectModal')">Cancel</button>
        <button type="submit" name="do_reject" class="btn btn-danger">❌ Confirm Reject</button>
      </div>
    </form>
  </div>
</div>

</div><!-- /content -->
</div><!-- /main -->

<script>
function openReject(id, name){
  document.getElementById('rj_reg_id').value = id;
  document.getElementById('rejectStudentName').textContent = 'Student: ' + name;
  document.getElementById('rejectModal').classList.add('show');
}
function closeModal(id){
  document.getElementById(id).classList.remove('show');
}
document.querySelectorAll('.modal-overlay').forEach(function(el){
  el.addEventListener('click', function(ev){
    if(ev.target === el) el.classList.remove('show');
  });
});
</script>
</body></html>