<?php
/* ================================================================
   SLGTI Online Course System — Admin Dashboard (Redesigned)
   Summary Cards | Charts | Filters | Student Table
   CSS: config/style.css (common) + css/dashboard.css (page-only)
   ================================================================ */
session_start();
include("config/db.php");
include("config/layout.php");
if (!isset($_SESSION['admin'])) { header("Location: index.php"); exit(); }

$ay       = get_active_year($conn);
$admin_nm = $_SESSION['admin'];
$admin_ch = strtoupper(substr($admin_nm, 0, 1));

/* ── SUMMARY COUNTS ─────────────────────────────────────────── */
$total_students  = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM students"))['c'];
$male_students   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM students WHERE Stu_Gender='Male'"))['c'];
$female_students = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM students WHERE Stu_Gender='Female'"))['c'];
$total_courses   = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM courses WHERE Is_Active=1"))['c'];

/* ── GENDER DISTRIBUTION ────────────────────────────────────── */
$gender_data = [];
$gq = mysqli_query($conn,"SELECT Stu_Gender, COUNT(*) c FROM students GROUP BY Stu_Gender ORDER BY c DESC");
while ($row = mysqli_fetch_assoc($gq)) $gender_data[] = $row;

/* ── COURSE-WISE REGISTRATIONS ──────────────────────────────── */
$course_data = [];
$cq = mysqli_query($conn,"SELECT c.Cou_Name, COUNT(r.Reg_ID) cnt FROM courses c LEFT JOIN registrations r ON r.Cou_ID=c.Cou_ID AND r.status NOT IN('Rejected','Withdrawn') WHERE c.Is_Active=1 GROUP BY c.Cou_ID,c.Cou_Name ORDER BY cnt DESC LIMIT 8");
while ($row = mysqli_fetch_assoc($cq)) $course_data[] = $row;

/* ── DISTRICT-WISE (from Stu_Address) ───────────────────────── */
$srilanka_districts = ['Ampara','Anuradhapura','Badulla','Batticaloa','Colombo','Galle','Gampaha','Hambantota','Jaffna','Kalutara','Kandy','Kegalle','Kilinochchi','Kurunegala','Mannar','Matale','Matara','Monaragala','Mullaitivu','Nuwara Eliya','Polonnaruwa','Puttalam','Ratnapura','Trincomalee','Vavuniya'];
$district_data = [];
foreach ($srilanka_districts as $dist) {
    $esc = mysqli_real_escape_string($conn,$dist);
    $cnt = mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) c FROM students WHERE Stu_Address LIKE '%$esc%'"))['c'];
    if ($cnt > 0) $district_data[] = ['district'=>$dist,'count'=>(int)$cnt];
}
usort($district_data, fn($a,$b) => $b['count']-$a['count']);
$district_data = array_slice($district_data,0,10);

/* ── DROPDOWN VALUES ────────────────────────────────────────── */
$courses_list = mysqli_query($conn,"SELECT Cou_ID,Cou_Name FROM courses WHERE Is_Active=1 ORDER BY Cou_Name");

/* ── FILTER PARAMS ──────────────────────────────────────────── */
$f_gender    = isset($_GET['gender'])    ? trim($_GET['gender'])    : '';
$f_district  = isset($_GET['district'])  ? trim($_GET['district'])  : '';
$f_course    = isset($_GET['course'])    ? (int)$_GET['course']     : 0;
$f_date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$f_date_to   = isset($_GET['date_to'])   ? trim($_GET['date_to'])   : '';
$f_search    = isset($_GET['search'])    ? trim($_GET['search'])    : '';

/* ── STUDENT TABLE QUERY ────────────────────────────────────── */
$where = "WHERE 1=1";
if ($f_gender   && in_array($f_gender,['Male','Female','Other']))
    $where .= " AND s.Stu_Gender='".mysqli_real_escape_string($conn,$f_gender)."'";
if ($f_district)
    $where .= " AND s.Stu_Address LIKE '%".mysqli_real_escape_string($conn,$f_district)."%'";
if ($f_course)
    $where .= " AND r.Cou_ID=$f_course";
if ($f_date_from)
    $where .= " AND DATE(r.registered_at)>='".mysqli_real_escape_string($conn,$f_date_from)."'";
if ($f_date_to)
    $where .= " AND DATE(r.registered_at)<='".mysqli_real_escape_string($conn,$f_date_to)."'";
if ($f_search) {
    $s = mysqli_real_escape_string($conn,$f_search);
    $where .= " AND (s.Stu_Name LIKE '%$s%' OR s.Stu_Email LIKE '%$s%' OR s.Stu_NIC LIKE '%$s%')";
}
$student_rows = mysqli_query($conn,"SELECT s.Stu_ID,s.Stu_Name,s.Stu_Gender,s.Stu_Address,c.Cou_Name,r.registered_at,r.status FROM students s LEFT JOIN registrations r ON r.Stu_ID=s.Stu_ID LEFT JOIN courses c ON c.Cou_ID=r.Cou_ID $where ORDER BY r.registered_at DESC,s.Stu_Name ASC LIMIT 200");
$row_count = mysqli_num_rows($student_rows);

/* ── JSON FOR CHARTS ────────────────────────────────────────── */
$j_gender   = json_encode($gender_data);
$j_course   = json_encode($course_data);
$j_district = json_encode($district_data);
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Dashboard — SLGTI Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="config/style.css">
<link rel="stylesheet" href="css/dashboard.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body class="dash-body">

<!-- SIDEBAR -->
<aside class="sidebar">
  <div class="sb-brand">
    <div class="sb-logo">🎓</div>
    <div>
      <div class="sb-name">SLGTI</div>
      <div class="sb-sub">Admin Panel</div>
    </div>
  </div>
  <nav class="sb-nav">
    <div class="sb-section">Main</div>
    <a href="admin_dashboard.php" class="active"><span class="nav-icon">📊</span> Dashboard</a>
    <a href="students.php"><span class="nav-icon">👥</span> Students</a>
    <a href="registrations.php"><span class="nav-icon">📋</span> Registrations</a>
    <a href="courses.php"><span class="nav-icon">📚</span> Courses</a>
    <div class="sb-section">Management</div>
    <a href="departments.php"><span class="nav-icon">🏛️</span> Departments</a>
    <a href="academic_years.php"><span class="nav-icon">📅</span> Academic Years</a>
    <a href="reports.php"><span class="nav-icon">📈</span> Reports</a>
    <a href="audit_log.php"><span class="nav-icon">🔍</span> Audit Log</a>
    <a href="settings.php"><span class="nav-icon">⚙️</span> Settings</a>
  </nav>
  <div class="sb-footer">
    <div class="sb-admin">
      <div class="sb-av"><?= $admin_ch ?></div>
      <div>
        <div class="sb-an"><?= htmlspecialchars($admin_nm) ?></div>
        <div class="sb-ar">Administrator</div>
      </div>
    </div>
    <a href="admin_logout.php" class="sb-logout">🚪 Logout</a>
  </div>
</aside>

<!-- TOPBAR -->
<div class="topbar">
  <div class="tb-left">
    <h1>📊 Dashboard</h1>
    <p>Student &amp; Course Analytics Overview</p>
  </div>
  <div class="tb-right">
    <span class="year-badge">📅 <?= htmlspecialchars($ay['Year_Name'] ?? 'AY') ?></span>
    <span class="date-pill" id="topbar-date"></span>
    <button class="dm-toggle" onclick="toggleDark()" title="Toggle dark mode" aria-label="Toggle dark mode">
      <span class="dm-icon-moon">🌙</span>
      <span class="dm-icon-sun">☀️</span>
    </button>
  </div>
</div>

<!-- MAIN -->
<main class="main">
<div class="dash-content">

  <!-- SUMMARY CARDS -->
  <div class="sum-grid">
    <div class="sum-card sum-blue">
      <div class="sum-card-stripe"></div>
      <div class="sum-top"><div class="sum-icon">👥</div><span class="sum-badge">Total</span></div>
      <div class="sum-num" data-count="<?= $total_students ?>">0</div>
      <div class="sum-lbl">Total Students</div>
      <div class="sum-sub">All registered students</div>
    </div>
    <div class="sum-card sum-teal">
      <div class="sum-card-stripe"></div>
      <div class="sum-top"><div class="sum-icon">👨</div><span class="sum-badge">Male</span></div>
      <div class="sum-num" data-count="<?= $male_students ?>">0</div>
      <div class="sum-lbl">Male Students</div>
      <div class="sum-sub"><?= $total_students>0?round($male_students/$total_students*100):0 ?>% of total</div>
    </div>
    <div class="sum-card sum-rose">
      <div class="sum-card-stripe"></div>
      <div class="sum-top"><div class="sum-icon">👩</div><span class="sum-badge">Female</span></div>
      <div class="sum-num" data-count="<?= $female_students ?>">0</div>
      <div class="sum-lbl">Female Students</div>
      <div class="sum-sub"><?= $total_students>0?round($female_students/$total_students*100):0 ?>% of total</div>
    </div>
    <div class="sum-card sum-violet">
      <div class="sum-card-stripe"></div>
      <div class="sum-top"><div class="sum-icon">📚</div><span class="sum-badge">Active</span></div>
      <div class="sum-num" data-count="<?= $total_courses ?>">0</div>
      <div class="sum-lbl">Total Courses</div>
      <div class="sum-sub">Currently active courses</div>
    </div>
  </div>

  <!-- FILTER BAR -->
  <form method="GET" action="admin_dashboard.php" id="filter-form">
    <div class="filter-bar">
      <div class="filter-label">
        <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2a1 1 0 01-.293.707L13 13.414V19a1 1 0 01-.553.894l-4 2A1 1 0 017 21v-7.586L3.293 6.707A1 1 0 013 6V4z"/></svg>
        Filters
      </div>
      <div class="filter-group">
        <select name="district" class="filter-select" onchange="this.form.submit()" aria-label="Filter by District">
          <option value="">🗺️ All Districts</option>
          <?php foreach ($srilanka_districts as $d): ?>
          <option value="<?= htmlspecialchars($d) ?>" <?= $f_district===$d?'selected':'' ?>><?= htmlspecialchars($d) ?></option>
          <?php endforeach; ?>
        </select>
        <select name="gender" class="filter-select" onchange="this.form.submit()" aria-label="Filter by Gender">
          <option value="">⚥ All Genders</option>
          <option value="Male"   <?= $f_gender==='Male'?'selected':''   ?>>👨 Male</option>
          <option value="Female" <?= $f_gender==='Female'?'selected':'' ?>>👩 Female</option>
          <option value="Other"  <?= $f_gender==='Other'?'selected':''  ?>>🧑 Other</option>
        </select>
        <select name="course" class="filter-select" onchange="this.form.submit()" aria-label="Filter by Course">
          <option value="">📚 All Courses</option>
          <?php mysqli_data_seek($courses_list,0); while($c=mysqli_fetch_assoc($courses_list)): ?>
          <option value="<?= $c['Cou_ID'] ?>" <?= $f_course==$c['Cou_ID']?'selected':'' ?>><?= htmlspecialchars($c['Cou_Name']) ?></option>
          <?php endwhile; ?>
        </select>
        <input type="date" name="date_from" class="filter-date" value="<?= htmlspecialchars($f_date_from) ?>" onchange="this.form.submit()" aria-label="From date">
        <input type="date" name="date_to"   class="filter-date" value="<?= htmlspecialchars($f_date_to) ?>"   onchange="this.form.submit()" aria-label="To date">
        <input type="text" name="search" class="filter-input" value="<?= htmlspecialchars($f_search) ?>" placeholder="🔍 Search name / NIC / email…" onkeydown="if(event.key==='Enter')this.form.submit()" aria-label="Search">
        <?php if($f_gender||$f_district||$f_course||$f_date_from||$f_date_to||$f_search): ?>
        <a href="admin_dashboard.php" class="filter-reset">✕ Reset</a>
        <?php endif; ?>
      </div>
    </div>
  </form>

  <!-- CHARTS -->
  <div class="sec-hdr">
    <div class="sec-hdr-title">📈 Analytics Overview</div>
    <div class="sec-hdr-line"></div>
  </div>
  <div class="charts-grid">
    <div class="chart-card">
      <div class="chart-head">
        <div class="chart-title"><span class="chart-title-dot" style="background:#3b82f6"></span>District-wise Student Count</div>
        <div class="chart-sub">By address keyword match</div>
      </div>
      <div class="chart-body"><canvas id="districtChart"></canvas></div>
    </div>
    <div class="chart-card">
      <div class="chart-head">
        <div class="chart-title"><span class="chart-title-dot" style="background:#e11d48"></span>Gender Split</div>
        <div class="chart-sub">All registered students</div>
      </div>
      <div class="chart-body">
        <div class="doughnut-wrap">
          <canvas id="genderChart"></canvas>
          <div class="doughnut-center">
            <div class="doughnut-center-num"><?= $total_students ?></div>
            <div class="doughnut-center-lbl">Students</div>
          </div>
        </div>
      </div>
      <div class="chart-legend" id="gender-legend"></div>
    </div>
    <div class="chart-card">
      <div class="chart-head">
        <div class="chart-title"><span class="chart-title-dot" style="background:#7c3aed"></span>Course Registrations</div>
        <div class="chart-sub">Active courses (top 8)</div>
      </div>
      <div class="chart-body"><canvas id="courseChart"></canvas></div>
    </div>
  </div>

  <!-- STUDENT TABLE -->
  <div class="sec-hdr">
    <div class="sec-hdr-title">📋 Student Registration Details</div>
    <div class="sec-hdr-line"></div>
  </div>
  <div class="table-card">
    <div class="table-head-bar">
      <div class="table-head-left">
        <div class="table-head-title">
          👥 Students
          <span class="table-count" id="visible-count"><?= $row_count ?></span>
        </div>
        <?php if($f_gender||$f_district||$f_course): ?>
        <span style="font-size:11px;color:var(--dash-muted);">Filtered<?= $f_gender?' · '.$f_gender:'' ?><?= $f_district?' · '.$f_district:'' ?></span>
        <?php endif; ?>
      </div>
      <div class="table-search-wrap">
        <span class="table-search-icon">🔍</span>
        <input type="text" class="table-search" id="live-search" placeholder="Quick search…" value="<?= htmlspecialchars($f_search) ?>" aria-label="Live search">
      </div>
    </div>
    <div class="tbl-scroll">
      <table class="dash-table" id="stu-table">
        <thead>
          <tr>
            <th>#</th>
            <th>Student Name</th>
            <th>Gender</th>
            <th>District</th>
            <th>Course</th>
            <th>Reg. Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody id="stu-tbody">
<?php if($row_count===0): ?>
          <tr><td colspan="7"><div class="tbl-empty"><div class="tbl-empty-icon">🔍</div><div class="tbl-empty-text">No students found</div><div class="tbl-empty-sub">Try adjusting the filters above</div></div></td></tr>
<?php else: $i=0; while($row=mysqli_fetch_assoc($student_rows)):
  $i++;
  $gender  = htmlspecialchars($row['Stu_Gender']??'Other');
  $gc      = in_array(strtolower($gender),['male','female']) ? strtolower($gender) : 'other';
  $initials= strtoupper(substr($row['Stu_Name'],0,1));
  $addr    = $row['Stu_Address']??'';
  $found_dist='—';
  foreach($srilanka_districts as $d){ if(stripos($addr,$d)!==false){$found_dist=$d;break;} }
  $reg_date= $row['registered_at'] ? date('d M Y',strtotime($row['registered_at'])) : '—';
  $status  = $row['status']??'—';
  $sc      = match($status){'Approved'=>'sp-approved','Pending'=>'sp-pending','Rejected'=>'sp-rejected','Waitlisted'=>'sp-waitlisted','Doc_Review'=>'sp-doc_review',default=>'sp-withdrawn'};
?>
          <tr>
            <td><span class="row-num"><?= $i ?></span></td>
            <td><div class="stu-cell"><div class="stu-avatar <?= $gc ?>"><?= $initials ?></div><span class="stu-name"><?= htmlspecialchars($row['Stu_Name']) ?></span></div></td>
            <td><span class="g-pill <?= $gc ?>"><?= $gc==='male'?'👨':($gc==='female'?'👩':'🧑') ?> <?= $gender ?></span></td>
            <td><span class="dist-chip"><?= htmlspecialchars($found_dist) ?></span></td>
            <td><?php if($row['Cou_Name']): ?><span class="course-tag" title="<?= htmlspecialchars($row['Cou_Name']) ?>"><?= htmlspecialchars($row['Cou_Name']) ?></span><?php else: ?><span class="date-txt">—</span><?php endif; ?></td>
            <td><span class="date-txt"><?= $reg_date ?></span></td>
            <td><?php if($status!=='—'): ?><span class="spill <?= $sc ?>"><?= $status ?></span><?php else: ?><span class="date-txt">—</span><?php endif; ?></td>
          </tr>
<?php endwhile; endif; ?>
        </tbody>
      </table>
    </div>
    <div class="table-footer">
      <div>Showing <strong id="showing-count"><?= $row_count ?></strong> of <strong><?= $row_count ?></strong> records<?= ($f_gender||$f_district||$f_course||$f_search)?' (filtered)':'' ?></div>
    </div>
  </div>

</div><!-- /dash-content -->
</main>

<script>
const genderRaw   = <?= $j_gender ?>;
const courseRaw   = <?= $j_course ?>;
const districtRaw = <?= $j_district ?>;

const BLUE_PALETTE=['#1d4ed8','#2563eb','#3b82f6','#60a5fa','#93c5fd','#bfdbfe','#dbeafe','#e0f2fe','#0ea5e9','#0284c7'];
const GENDER_COLORS={Male:'#0d9488',Female:'#e11d48',Other:'#7c3aed'};

function isDark(){return document.documentElement.getAttribute('data-theme')==='dark';}
function gridColor(){return isDark()?'rgba(148,163,184,0.08)':'rgba(100,116,139,0.1)';}
function textColor(){return isDark()?'#94a3b8':'#64748b';}

function animateCounters(){
  document.querySelectorAll('.sum-num[data-count]').forEach(el=>{
    const target=parseInt(el.dataset.count,10),dur=900,start=performance.now();
    const upd=now=>{const p=Math.min((now-start)/dur,1),e=1-Math.pow(1-p,3);el.textContent=Math.floor(e*target);if(p<1)requestAnimationFrame(upd);};
    requestAnimationFrame(upd);
  });
}

function toggleDark(){
  const html=document.documentElement;
  const next=html.getAttribute('data-theme')==='dark'?'light':'dark';
  html.setAttribute('data-theme',next);
  localStorage.setItem('slgti_theme',next);
  updateChartThemes();
}
function loadTheme(){
  const saved=localStorage.getItem('slgti_theme')||'light';
  document.documentElement.setAttribute('data-theme',saved);
}

const liveSearch=document.getElementById('live-search');
if(liveSearch){
  liveSearch.addEventListener('input',function(){
    const q=this.value.toLowerCase().trim();
    let visible=0;
    document.querySelectorAll('#stu-tbody tr').forEach(tr=>{
      const show=!q||tr.textContent.toLowerCase().includes(q);
      tr.style.display=show?'':'none';
      if(show&&tr.cells.length>1)visible++;
    });
    const vc=document.getElementById('visible-count'),sc=document.getElementById('showing-count');
    if(vc)vc.textContent=visible;if(sc)sc.textContent=visible;
  });
}

(function(){
  const el=document.getElementById('topbar-date');
  if(!el)return;
  el.textContent=new Date().toLocaleDateString('en-GB',{day:'2-digit',month:'short',year:'numeric'});
})();

let districtChart,genderChart,courseChart;
function buildCharts(){
  const bf={family:"'Plus Jakarta Sans',sans-serif",size:11};

  const dCtx=document.getElementById('districtChart');
  if(dCtx&&districtRaw.length>0){
    districtChart=new Chart(dCtx,{type:'bar',data:{labels:districtRaw.map(d=>d.district),datasets:[{label:'Students',data:districtRaw.map(d=>d.count),backgroundColor:BLUE_PALETTE.slice(0,districtRaw.length),borderRadius:6,borderSkipped:false}]},options:{responsive:true,maintainAspectRatio:true,plugins:{legend:{display:false},tooltip:{callbacks:{title:i=>i[0].label+' District',label:i=>' '+i.raw+' students'}}},scales:{x:{ticks:{color:textColor(),font:bf},grid:{display:false},border:{display:false}},y:{ticks:{color:textColor(),font:bf,stepSize:1},grid:{color:gridColor()},border:{display:false}}}}});
  } else if(dCtx){dCtx.parentElement.innerHTML='<div class="tbl-empty"><div class="tbl-empty-icon">🗺️</div><div class="tbl-empty-text">No district data yet</div></div>';}

  const gCtx=document.getElementById('genderChart');
  if(gCtx&&genderRaw.length>0){
    const gColors=genderRaw.map(g=>GENDER_COLORS[g.Stu_Gender]||'#94a3b8');
    genderChart=new Chart(gCtx,{type:'doughnut',data:{labels:genderRaw.map(g=>g.Stu_Gender),datasets:[{data:genderRaw.map(g=>g.c),backgroundColor:gColors,borderWidth:3,borderColor:isDark()?'#161b25':'#ffffff',hoverOffset:6}]},options:{responsive:true,cutout:'68%',plugins:{legend:{display:false},tooltip:{callbacks:{label:item=>' '+item.raw+' students ('+Math.round(item.raw/genderRaw.reduce((a,g)=>a+parseInt(g.c),0)*100)+'%)'}}}}});
    const total=genderRaw.reduce((a,g)=>a+parseInt(g.c),0);
    const leg=document.getElementById('gender-legend');
    if(leg)leg.innerHTML=genderRaw.map((g,i)=>`<div class="legend-item"><span class="legend-dot" style="background:${gColors[i]}"></span><span>${g.Stu_Gender}</span><span class="legend-val">${g.c}</span><span class="legend-pct">${total>0?Math.round(g.c/total*100):0}%</span></div>`).join('');
  }

  const cCtx=document.getElementById('courseChart');
  if(cCtx&&courseRaw.length>0){
    courseChart=new Chart(cCtx,{type:'bar',data:{labels:courseRaw.map(c=>c.Cou_Name.length>22?c.Cou_Name.substring(0,20)+'…':c.Cou_Name),datasets:[{label:'Registrations',data:courseRaw.map(c=>c.cnt),backgroundColor:'rgba(124,58,237,0.15)',borderColor:'#7c3aed',borderWidth:2,borderRadius:6,borderSkipped:false}]},options:{indexAxis:'y',responsive:true,maintainAspectRatio:true,plugins:{legend:{display:false},tooltip:{callbacks:{label:item=>' '+item.raw+' registrations'}}},scales:{x:{ticks:{color:textColor(),font:bf,stepSize:1},grid:{color:gridColor()},border:{display:false}},y:{ticks:{color:textColor(),font:bf},grid:{display:false},border:{display:false}}}}});
  } else if(cCtx){cCtx.parentElement.innerHTML='<div class="tbl-empty"><div class="tbl-empty-icon">📚</div><div class="tbl-empty-text">No course data yet</div></div>';}
}

function updateChartThemes(){
  [districtChart,genderChart,courseChart].forEach(ch=>{
    if(!ch)return;
    if(ch.options.scales)Object.values(ch.options.scales).forEach(sc=>{if(sc.ticks)sc.ticks.color=textColor();if(sc.grid&&sc.grid.color!==undefined)sc.grid.color=gridColor();});
    if(ch.config.type==='doughnut')ch.data.datasets[0].borderColor=isDark()?'#161b25':'#ffffff';
    ch.update('none');
  });
}

document.addEventListener('DOMContentLoaded',()=>{loadTheme();animateCounters();buildCharts();});
</script>
</body>
</html>