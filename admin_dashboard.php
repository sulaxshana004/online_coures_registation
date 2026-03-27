<?php
session_start(); include("config/db.php"); include("config/layout.php");
if(!isset($_SESSION['admin'])){header("Location: index.php");exit();}
$ay = get_active_year($conn);
$yid = $ay['Year_ID'];
$admin_nm = $_SESSION['admin'];
$admin_char = strtoupper(substr($admin_nm,0,1));

$total_students  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM students"))['c'];
$total_pending_students = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM students WHERE Account_Status='Pending'"))['c'];
$total_courses   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM courses WHERE Is_Active=1"))['c'];
$total_pending   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM registrations WHERE status='Pending'"))['c'];
$total_approved  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM registrations WHERE status='Approved'"))['c'];
$total_waitlist  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM registrations WHERE status='Waitlisted'"))['c'];
$total_doc_rev   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM registrations WHERE status='Doc_Review'"))['c'];
$total_rejected  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM registrations WHERE status='Rejected'"))['c'];

$dept_stats = mysqli_query($conn,"SELECT d.Dept_Name,d.Dept_Icon,d.Dept_Color,
    (SELECT COUNT(*) FROM courses WHERE Dept_ID=d.Dept_ID AND Is_Active=1) AS cou_count,
    (SELECT COUNT(*) FROM registrations r JOIN courses c ON r.Cou_ID=c.Cou_ID WHERE c.Dept_ID=d.Dept_ID AND r.status NOT IN('Rejected','Withdrawn')) AS reg_count
    FROM departments d ORDER BY d.Dept_Name");

$recent_regs = mysqli_query($conn,"SELECT r.App_ID,r.status,r.registered_at,s.Stu_Name,s.NVQ_Level,c.Cou_Name,d.Dept_Name,d.Dept_Icon,r.Reg_ID
    FROM registrations r
    JOIN students s ON r.Stu_ID=s.Stu_ID
    JOIN courses c ON r.Cou_ID=c.Cou_ID
    LEFT JOIN departments d ON c.Dept_ID=d.Dept_ID
    ORDER BY r.registered_at DESC LIMIT 8");

$recent_audit = mysqli_query($conn,"SELECT Actor_Name,Actor_Type,Action,Details,logged_at FROM audit_log ORDER BY logged_at DESC LIMIT 7");

page_head('Admin Dashboard');
?>
<style>
/* ══ COUNTER ANIMATION ══ */
.kpi-val{font-family:"Space Grotesk",sans-serif;font-size:32px;font-weight:700;line-height:1;}

/* ══ KPI GRID ══ */
.kpi-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:28px;}
.kpi{
  background:var(--white);border-radius:20px;border:1px solid var(--border);
  padding:26px 24px;position:relative;overflow:hidden;
  transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);
  cursor:default;box-shadow:var(--shadow);
}
.kpi:hover{transform:translateY(-6px) scale(1.02);box-shadow:0 20px 50px rgba(9,4,70,0.15);}
.kpi-bg{
  position:absolute;right:-30px;top:-30px;
  width:120px;height:120px;border-radius:50%;
  transition:all 0.3s;
}
.kpi:hover .kpi-bg{transform:scale(1.4);}
.kpi-icon{
  width:52px;height:52px;border-radius:16px;
  display:flex;align-items:center;justify-content:center;
  font-size:24px;margin-bottom:18px;position:relative;z-index:1;transition:all 0.3s;
}
.kpi:hover .kpi-icon{transform:scale(1.1) rotate(5deg);}
.kpi-val{position:relative;z-index:1;margin-bottom:6px;font-size:36px;}
.kpi-lbl{font-size:13px;color:var(--muted);font-weight:500;position:relative;z-index:1;}
.kpi-bar{height:4px;border-radius:2px;margin-top:16px;background:#e2e8f0;overflow:hidden;position:relative;z-index:1;}
.kpi-bar-fill{height:100%;border-radius:2px;transition:width 1.4s cubic-bezier(0.34,1.56,0.64,1);}

/* KPI color variants */
.kpi-blue .kpi-bg{background:rgba(37,99,235,.07);}
.kpi-blue .kpi-icon{background:#eff6ff;}
.kpi-blue .kpi-val{color:#1d4ed8;}
.kpi-blue .kpi-bar-fill{background:linear-gradient(90deg,#3b82f6,#0ea5e9);}
.kpi-green .kpi-bg{background:rgba(5,150,105,.07);}
.kpi-green .kpi-icon{background:#f0fdf4;}
.kpi-green .kpi-val{color:#065f46;}
.kpi-green .kpi-bar-fill{background:linear-gradient(90deg,#10b981,#34d399);}
.kpi-amber .kpi-bg{background:rgba(217,119,6,.07);}
.kpi-amber .kpi-icon{background:#fffbeb;}
.kpi-amber .kpi-val{color:#92400e;}
.kpi-amber .kpi-bar-fill{background:linear-gradient(90deg,#f59e0b,#fbbf24);}
.kpi-purple .kpi-bg{background:rgba(124,58,237,.07);}
.kpi-purple .kpi-icon{background:#faf5ff;}
.kpi-purple .kpi-val{color:#5b21b6;}
.kpi-purple .kpi-bar-fill{background:linear-gradient(90deg,#7c3aed,#a78bfa);}
.kpi-cyan .kpi-bg{background:rgba(8,145,178,.07);}
.kpi-cyan .kpi-icon{background:#ecfeff;}
.kpi-cyan .kpi-val{color:#0e7490;}
.kpi-cyan .kpi-bar-fill{background:linear-gradient(90deg,#0891b2,#22d3ee);}
.kpi-red .kpi-bg{background:rgba(220,38,38,.07);}
.kpi-red .kpi-icon{background:#fff1f2;}
.kpi-red .kpi-val{color:#991b1b;}
.kpi-red .kpi-bar-fill{background:linear-gradient(90deg,#dc2626,#f87171);}

/* ══ SECTION TITLE ══ */
.sec-title{
  font-family:"Space Grotesk",sans-serif;font-size:15px;font-weight:700;
  color:var(--navy2);margin-bottom:16px;
  display:flex;align-items:center;gap:10px;
}
.sec-title::after{content:"";flex:1;height:1px;background:linear-gradient(90deg,var(--border),transparent);}

/* ══ DEPT CARDS ══ */
.dept-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:28px;}
.dept-card{
  background:var(--white);border-radius:18px;border:1px solid var(--border);
  padding:22px;box-shadow:var(--shadow);
  transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);position:relative;overflow:hidden;
}
.dept-card:hover{transform:translateY(-5px) scale(1.02);box-shadow:0 15px 40px rgba(9,4,70,0.12);}
.dept-card::before{content:"";position:absolute;top:0;left:0;right:0;height:4px;border-radius:4px 4px 0 0;}
.dept-top{display:flex;align-items:center;gap:14px;margin-bottom:16px;}
.dept-emoji{font-size:32px;line-height:1;transition:all 0.3s;}
.dept-card:hover .dept-emoji{transform:scale(1.15) rotate(5deg);}
.dept-info-name{font-family:"Space Grotesk",sans-serif;font-size:14px;font-weight:700;color:var(--navy2);}
.dept-info-meta{font-size:12px;color:var(--muted);margin-top:2px;}
.dept-nums{display:flex;gap:14px;margin-bottom:14px;}
.dept-num{flex:1;background:var(--off);border-radius:12px;padding:12px;text-align:center;transition:all 0.3s;}
.dept-card:hover .dept-num{background:var(--accent);}
.dept-num-val{font-family:"Space Grotesk",sans-serif;font-size:20px;font-weight:700;color:var(--navy2);}
.dept-num-lbl{font-size:11px;color:var(--muted);margin-top:3px;}
.dept-prog-bar{height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden;}
.dept-prog-fill{height:100%;border-radius:3px;transition:width 1.4s cubic-bezier(0.34,1.56,0.64,1);}
.dept-pct{font-size:11px;color:var(--muted);margin-top:6px;text-align:right;}

/* ══ QUICK ACTIONS ══ */
.qa-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:28px;}
.qa{
  background:var(--white);border-radius:18px;border:2px solid var(--border);
  padding:20px;text-decoration:none;display:flex;flex-direction:column;gap:12px;
  transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);position:relative;overflow:hidden;
}
.qa::before{
  content:"";position:absolute;bottom:0;left:0;right:0;height:0;
  background:linear-gradient(135deg,var(--sb-dark),var(--blue2));
  transition:all 0.3s;border-radius:0 0 16px 16px;z-index:0;
}
.qa:hover{transform:translateY(-4px);border-color:var(--blue2);box-shadow:0 12px 35px rgba(9,4,70,0.15);}
.qa:hover::before{height:5px;}
.qa-icon{font-size:28px;position:relative;z-index:1;transition:all 0.3s;}
.qa:hover .qa-icon{transform:scale(1.1);}
.qa-title{font-family:"Space Grotesk",sans-serif;font-size:14px;font-weight:700;color:var(--navy2);position:relative;z-index:1;}
.qa-desc{font-size:12px;color:var(--muted);position:relative;z-index:1;}
.qa-arrow{font-size:18px;color:var(--blue2);position:relative;z-index:1;transition:all 0.3s;width:fit-content;}
.qa:hover .qa-arrow{transform:translateX(6px);color:var(--sb-dark);}

/* ══ BOTTOM GRID ══ */
.bottom-grid{display:grid;grid-template-columns:1fr 360px;gap:20px;}

/* ══ TABLE ══ */
.dash-table{width:100%;border-collapse:collapse;}
.dash-table thead th{
  font-size:10px;font-weight:700;color:var(--muted);
  text-transform:uppercase;letter-spacing:.1em;
  padding:10px 16px;text-align:left;
  border-bottom:2px solid var(--off);
  background:linear-gradient(180deg,#fafbff,#f4f7ff);
}
.dash-table tbody tr{border-bottom:1px solid #f8faff;transition:.15s;}
.dash-table tbody tr:last-child{border-bottom:none;}
.dash-table tbody tr:hover{background:#f8fbff;}
.dash-table td{padding:12px 16px;font-size:13px;vertical-align:middle;}
.app-id{font-family:"Space Grotesk",sans-serif;font-size:11px;font-weight:700;color:var(--navy2);background:var(--off);border:1px solid var(--border);padding:3px 9px;border-radius:7px;white-space:nowrap;}
.spill{display:inline-flex;align-items:center;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:700;}
.sp-pending{background:#fef9c3;color:#854d0e;}
.sp-approved{background:#f0fdf4;color:#15803d;}
.sp-rejected{background:#fff1f2;color:#be123c;}
.sp-waitlisted{background:#faf5ff;color:#7e22ce;}
.sp-doc_review{background:#dbeafe;color:#1d4ed8;}
.sp-withdrawn{background:#f9fafb;color:#4b5563;}

/* ══ AUDIT FEED ══ */
.audit-feed{padding:0 4px;}
.af-item{
  display:flex;align-items:flex-start;gap:12px;
  padding:12px 0;border-bottom:1px solid #f4f7ff;
  animation:fadeSlideIn .4s ease both;
}
.af-item:last-child{border-bottom:none;}
@keyframes fadeSlideIn{from{opacity:0;transform:translateX(-8px);}to{opacity:1;transform:translateX(0);}}
.af-icon{
  width:34px;height:34px;border-radius:10px;flex-shrink:0;
  display:flex;align-items:center;justify-content:center;font-size:15px;
}
.af-icon.admin{background:#eff6ff;}
.af-icon.student{background:#f0fdf4;}
.af-icon.system{background:#faf5ff;}
.af-body{flex:1;}
.af-action{font-size:12px;font-weight:700;color:var(--navy2);}
.af-detail{font-size:11px;color:var(--muted);margin-top:2px;line-height:1.4;}
.af-time{font-size:10px;color:var(--muted);white-space:nowrap;font-weight:500;margin-top:2px;}
.live-dot{
  display:inline-flex;align-items:center;gap:6px;
  font-size:11px;font-weight:700;color:#22c55e;
}
.live-dot::before{
  content:"";width:7px;height:7px;border-radius:50%;background:#22c55e;
  box-shadow:0 0 8px #22c55e;
  animation:livePulse 1.5s infinite;
}
@keyframes livePulse{0%,100%{transform:scale(1);opacity:1;}50%{transform:scale(1.4);opacity:.5;}}

/* ══ WELCOME BANNER ══ */
.welcome-bar{
  background:linear-gradient(135deg,#090446 0%,#0d0659 50%,#11096b 100%);
  border-radius:22px;padding:28px 36px;margin-bottom:28px;
  display:flex;align-items:center;justify-content:space-between;
  position:relative;overflow:hidden;
  box-shadow:0 12px 40px rgba(9,4,70,0.25);
  animation:fadeInUp 0.8s cubic-bezier(0.34,1.56,0.64,1) both;
}
@keyframes fadeInUp{from{opacity:0;transform:translateY(30px);}to{opacity:1;transform:translateY(0);}}
.welcome-bar::before{
  content:"";position:absolute;right:-80px;top:-80px;
  width:280px;height:280px;
  background:radial-gradient(circle,rgba(241,222,222,0.2),transparent 70%);
  border-radius:50%;animation:wbFloat 6s ease-in-out infinite;
}
@keyframes wbFloat{0%,100%{transform:translate(0,0);}50%{transform:translate(-20px,10px);}}
.welcome-bar::after{
  content:"";position:absolute;left:30%;bottom:-80px;
  width:200px;height:200px;
  background:radial-gradient(circle,rgba(255,255,255,0.1),transparent 70%);
  border-radius:50%;animation:wbFloat 8s ease-in-out infinite reverse;
}
.wb-left{position:relative;z-index:1;}
.wb-tag{
  display:inline-flex;align-items:center;gap:7px;
  background:rgba(241,222,222,0.15);border:1px solid rgba(241,222,222,0.25);
  color:#F1DEDE;padding:5px 12px;border-radius:8px;
  font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:1.5px;margin-bottom:10px;
}
.wb-name{font-family:"Space Grotesk",sans-serif;font-size:24px;font-weight:800;color:#fff;margin-bottom:4px;text-shadow:0 2px 10px rgba(0,0,0,0.3);}
.wb-sub{font-size:13px;color:#F1DEDE;}
.wb-right{display:flex;gap:12px;position:relative;z-index:1;}
.wb-stat{
  background:rgba(241,222,222,0.1);border:1px solid rgba(241,222,222,0.15);
  border-radius:14px;padding:14px 20px;text-align:center;min-width:90px;
}
.wb-stat-num{font-family:"Space Grotesk",sans-serif;font-size:24px;font-weight:700;color:#fff;}
.wb-stat-lbl{font-size:10px;color:#F1DEDE;text-transform:uppercase;letter-spacing:1px;margin-top:3px;}

@media(max-width:1200px){.kpi-grid{grid-template-columns:repeat(3,1fr);}.dept-grid{grid-template-columns:repeat(2,1fr);}.qa-grid{grid-template-columns:repeat(2,1fr);}}
@media(max-width:900px){.bottom-grid{grid-template-columns:1fr;}.kpi-grid{grid-template-columns:repeat(2,1fr);}}
</style>

<?php sidebar('admin_dashboard.php'); ?>
<div class="main">
<?php topbar('📊 Dashboard','Enterprise Overview',$ay['Year_Label']); ?>
<div class="content">

<!-- WELCOME BANNER -->
<div class="welcome-bar">
  <div class="wb-left">
    <div class="wb-tag">✦ <?php echo date('l, d M Y'); ?></div>
    <div class="wb-name">Good <?php echo (date('H')<12)?'Morning':(date('H')<17?'Afternoon':'Evening'); ?>, <?php echo htmlspecialchars($admin_nm); ?>! 👋</div>
    <div class="wb-sub">Academic Year <strong style="color:#93c5fd"><?php echo $ay['Year_Label']; ?></strong> · Here's your system overview</div>
  </div>
  <div class="wb-right">
    <div class="wb-stat"><div class="wb-stat-num" data-target="<?php echo $total_students; ?>">0</div><div class="wb-stat-lbl">Students</div></div>
    <div class="wb-stat"><div class="wb-stat-num" data-target="<?php echo $total_approved; ?>">0</div><div class="wb-stat-lbl">Enrolled</div></div>
    <div class="wb-stat"><div class="wb-stat-num" data-target="<?php echo $total_pending; ?>">0</div><div class="wb-stat-lbl">Pending</div></div>
  </div>
</div>

<!-- KPI CARDS -->
<div class="kpi-grid">
  <?php
  $kpis=[
    ['blue','👥','Total Students',$total_students,100],
    ['amber','⏳','Pending Students',$total_pending_students,50],
    ['green','✅','Approved',$total_approved,100],
    ['cyan','📂','Doc Review',$total_doc_rev,30],
    ['purple','📋','Waitlisted',$total_waitlist,40],
    ['red','📚','Active Courses',$total_courses,60],
  ];
  foreach($kpis as $k):
    $pct=min(100,$k[3]>0?round($k[3]/$k[4]*100):0);
  ?>
  <div class="kpi kpi-<?php echo $k[0]; ?>">
    <div class="kpi-bg"></div>
    <div class="kpi-icon"><?php echo $k[1]; ?></div>
    <div class="kpi-val" data-target="<?php echo $k[3]; ?>">0</div>
    <div class="kpi-lbl"><?php echo $k[2]; ?></div>
    <div class="kpi-bar"><div class="kpi-bar-fill" style="width:0%" data-width="<?php echo $pct; ?>%"></div></div>
  </div>
  <?php endforeach; ?>
</div>

<!-- DEPT OVERVIEW -->
<div class="sec-title">🏫 Department Overview</div>
<div class="dept-grid">
<?php
mysqli_data_seek($dept_stats,0);
$di=0;
while($d=mysqli_fetch_assoc($dept_stats)):
  $pct=$d['cou_count']>0?min(100,round(($d['reg_count']/($d['cou_count']*30))*100)):0;
  $color=htmlspecialchars($d['Dept_Color']??'#2563eb');
  $di++;
?>
<div class="dept-card" style="--dc:<?php echo $color; ?>" data-delay="<?php echo $di*80; ?>">
  <div style="position:absolute;top:0;left:0;right:0;height:3px;background:<?php echo $color; ?>;border-radius:3px 3px 0 0;"></div>
  <div class="dept-top">
    <div class="dept-emoji"><?php echo $d['Dept_Icon']; ?></div>
    <div>
      <div class="dept-info-name"><?php echo htmlspecialchars($d['Dept_Name']); ?></div>
      <div class="dept-info-meta">Academic <?php echo $ay['Year_Label']; ?></div>
    </div>
  </div>
  <div class="dept-nums">
    <div class="dept-num"><div class="dept-num-val"><?php echo $d['cou_count']; ?></div><div class="dept-num-lbl">Courses</div></div>
    <div class="dept-num"><div class="dept-num-val"><?php echo $d['reg_count']; ?></div><div class="dept-num-lbl">Registrations</div></div>
  </div>
  <div class="dept-prog-bar"><div class="dept-prog-fill" data-width="<?php echo $pct; ?>%" style="width:0%;background:<?php echo $color; ?>"></div></div>
  <div class="dept-pct"><?php echo $pct; ?>% capacity used</div>
</div>
<?php endwhile; ?>
</div>

<!-- QUICK ACTIONS -->
<div class="sec-title">⚡ Quick Actions</div>
<div class="qa-grid">
  <a href="students.php?status=Pending" class="qa">
    <div class="qa-icon">🎓</div>
    <div><div class="qa-title">Review New Students</div><div class="qa-desc"><?php echo $total_pending_students; ?> pending approval</div></div>
    <div class="qa-arrow">→</div>
  </a>
  <a href="registrations.php?filter=Pending" class="qa">
    <div class="qa-icon">�</div>
    <div><div class="qa-title">Review Applications</div><div class="qa-desc"><?php echo $total_pending; ?> course applications</div></div>
    <div class="qa-arrow">→</div>
  </a>
  <a href="registrations.php?filter=Doc_Review" class="qa">
    <div class="qa-icon">�</div>
    <div><div class="qa-title">Verify Documents</div><div class="qa-desc"><?php echo $total_doc_rev; ?> awaiting check</div></div>
    <div class="qa-arrow">→</div>
  </a>
  <a href="reports.php" class="qa">
    <div class="qa-icon">📊</div>
    <div><div class="qa-title">Enrollment Report</div><div class="qa-desc">Full statistics &amp; export</div></div>
    <div class="qa-arrow">→</div>
  </a>
</div>

<!-- BOTTOM GRID -->
<div class="bottom-grid">

  <!-- RECENT REGISTRATIONS -->
  <div class="card">
    <div class="card-head">
      <h3>📋 Recent Applications</h3>
      <a href="registrations.php" style="font-size:12px;color:var(--blue2);text-decoration:none;font-weight:600;display:flex;align-items:center;gap:4px;">View All <span style="transition:.2s" id="vaArrow">→</span></a>
    </div>
    <div class="tbl-wrap">
      <table class="dash-table">
        <thead><tr><th>App ID</th><th>Student</th><th>Course</th><th>NVQ</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php while($r=mysqli_fetch_assoc($recent_regs)):
          $sc='sp-'.strtolower($r['status']);
        ?>
        <tr>
          <td><span class="app-id"><?php echo htmlspecialchars($r['App_ID']); ?></span></td>
          <td style="font-weight:700;font-size:13px"><?php echo htmlspecialchars($r['Stu_Name']); ?></td>
          <td style="font-size:12px;color:var(--muted);max-width:140px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap"><?php echo htmlspecialchars($r['Cou_Name']); ?></td>
          <td><span class="spill <?php echo $r['NVQ_Level']=='5'?'sp-approved':'sp-pending'; ?>">L<?php echo $r['NVQ_Level']; ?></span></td>
          <td><span class="spill <?php echo $sc; ?>"><?php echo str_replace('_',' ',$r['status']); ?></span></td>
          <td style="font-size:11px;color:var(--muted);white-space:nowrap"><?php echo date('d M',strtotime($r['registered_at'])); ?></td>
          <td><a href="registrations.php?review=<?php echo $r['Reg_ID']; ?>" class="btn btn-sm btn-outline">Review</a></td>
        </tr>
        <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- AUDIT FEED -->
  <div class="card">
    <div class="card-head">
      <h3>🔍 Live Activity</h3>
      <span class="live-dot">LIVE</span>
    </div>
    <div class="card-body audit-feed">
      <?php
      $ai=0;
      while($a=mysqli_fetch_assoc($recent_audit)):
        $ai++;
        $ic_cls=strtolower($a['Actor_Type']);
        $ic=$a['Actor_Type']=='Admin'?'🛡️':($a['Actor_Type']=='Student'?'🎓':'⚙️');
      ?>
      <div class="af-item" style="animation-delay:<?php echo $ai*80; ?>ms">
        <div class="af-icon <?php echo $ic_cls; ?>"><?php echo $ic; ?></div>
        <div class="af-body">
          <div class="af-action"><?php echo htmlspecialchars($a['Actor_Name']??'System'); ?> &mdash; <?php echo htmlspecialchars($a['Action']); ?></div>
          <div class="af-detail"><?php echo htmlspecialchars(substr($a['Details']??'',0,52)); ?></div>
          <div class="af-time">🕐 <?php echo date('d M · H:i',strtotime($a['logged_at'])); ?></div>
        </div>
      </div>
      <?php endwhile; ?>
      <a href="audit_log.php" style="display:flex;align-items:center;justify-content:center;gap:6px;margin-top:14px;padding:10px;background:var(--off);border-radius:10px;font-size:12px;color:var(--blue2);font-weight:700;text-decoration:none;transition:.2s;" onmouseover="this.style.background='#dbeafe'" onmouseout="this.style.background='var(--off)'">View Full Audit Log →</a>
    </div>
  </div>

</div><!-- /bottom-grid -->
</div><!-- /content -->
</div><!-- /main -->

<script>
// ── Animated counters ──────────────────────────────────
function animateCount(el){
  var target=parseInt(el.dataset.target)||0;
  if(target===0){el.textContent='0';return;}
  var duration=1200,start=null;
  function step(ts){
    if(!start)start=ts;
    var prog=Math.min((ts-start)/duration,1);
    var ease=1-Math.pow(1-prog,3);
    el.textContent=Math.round(ease*target);
    if(prog<1)requestAnimationFrame(step);
    else el.textContent=target;
  }
  requestAnimationFrame(step);
}
document.querySelectorAll('[data-target]').forEach(function(el){
  setTimeout(function(){animateCount(el);},200);
});

// ── Progress bar animations ────────────────────────────
setTimeout(function(){
  document.querySelectorAll('[data-width]').forEach(function(el){
    el.style.transition='width 1.3s cubic-bezier(.34,1.56,.64,1)';
    el.style.width=el.dataset.width;
  });
},400);

// ── Dept cards stagger in ──────────────────────────────
document.querySelectorAll('.dept-card').forEach(function(el){
  el.style.opacity='0';
  el.style.transform='translateY(16px)';
  el.style.transition='opacity .4s ease, transform .4s ease';
  setTimeout(function(){
    el.style.opacity='1';
    el.style.transform='translateY(0)';
  },parseInt(el.dataset.delay)||200);
});

// ── KPI cards stagger in ───────────────────────────────
document.querySelectorAll('.kpi').forEach(function(el,i){
  el.style.opacity='0';
  el.style.transform='translateY(12px)';
  el.style.transition='opacity .35s ease, transform .35s ease';
  setTimeout(function(){
    el.style.opacity='1';
    el.style.transform='translateY(0)';
  },i*70+100);
});

// ── QA cards stagger in ───────────────────────────────
document.querySelectorAll('.qa').forEach(function(el,i){
  el.style.opacity='0';
  el.style.transform='translateY(10px)';
  el.style.transition='opacity .35s ease, transform .35s ease';
  setTimeout(function(){
    el.style.opacity='1';
    el.style.transform='translateY(0)';
  },i*80+300);
});
</script>
</body></html>