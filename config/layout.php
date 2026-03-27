<?php
function page_head($title='SLGTI Admin'){
    echo '<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>'.htmlspecialchars($title).' — SLGTI</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>'.base_css().'</style></head><body>';
}

function base_css(){return '
*{margin:0;padding:0;box-sizing:border-box;}
:root{
  /* #090446, #F1DEDE, White Theme */
  --sb-dark:   #090446;   /* sidebar top - deep navy */
  --sb-mid:    #0d0659;   /* sidebar mid */
  --sb-light:  #11096b;   /* sidebar bottom */
  --blue:      #1a1a5e;   /* primary blue */
  --blue2:     #252580;   /* light blue */
  --blue3:     #F1DEDE;   /* cream accent */
  --accent:    #FDF6F6;   /* lightest cream tint */
  --white:     #FFFFFF;
  --bg:        #F1DEDE;   /* page background */
  --surface:   #FFFFFF;   /* card surface */
  --off:       #FDF6F6;   /* subtle cream tint */
  --border:    #E8D5D5;   /* border */
  --text:      #090446;   /* dark text */
  --text2:     #0d0659;
  --muted:     #6b5b5b;
  --success:   #059669;
  --danger:    #dc2626;
  --warning:   #d97706;
  --sb-w:      262px;
  --radius:    16px;
  --shadow:    0 4px 20px rgba(9,4,70,0.08);
}
body{font-family:"Plus Jakarta Sans",sans-serif;background:var(--bg);display:flex;min-height:100vh;color:var(--text);}

/* ══ SIDEBAR ══ */
.sidebar{
  width:var(--sb-w);min-height:100vh;
  background:linear-gradient(175deg,#090446 0%,#0d0659 50%,#11096b 100%);
  position:fixed;top:0;left:0;
  display:flex;flex-direction:column;z-index:100;
  box-shadow:4px 0 30px rgba(9,4,70,0.25);
  animation:slideIn 0.6s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes slideIn{from{opacity:0;transform:translateX(-30px);}to{opacity:1;transform:translateX(0);}}
/* Shining right edge */
.sidebar::after{
  content:"";position:absolute;right:0;top:0;bottom:0;width:3px;
  background:linear-gradient(180deg,
    transparent 0%,
    rgba(255,255,255,.3) 20%,
    rgba(255,255,255,.6) 50%,
    rgba(255,255,255,.3) 80%,
    transparent 100%);
  filter:drop-shadow(0 0 8px rgba(255,255,255,0.5));
}
.sb-brand{
  padding:26px 20px 22px;
  border-bottom:1px solid rgba(255,255,255,.15);
  display:flex;align-items:center;gap:14px;
  background:rgba(0,0,0,.08);
}
.sb-logo{
  width:50px;height:50px;flex-shrink:0;
  background:linear-gradient(135deg,#F1DEDE,#fff);border-radius:15px;
  display:flex;align-items:center;justify-content:center;font-size:25px;
  box-shadow:0 6px 20px rgba(0,0,0,.2),0 0 0 3px rgba(241,222,222,.4);
  animation:logoPulse 2.5s ease-in-out infinite;
}
@keyframes logoPulse{0%,100%{transform:scale(1);}50%{transform:scale(1.05);}}
.sb-name{font-family:"Space Grotesk",sans-serif;font-size:18px;font-weight:700;color:#fff;letter-spacing:2px;}
.sb-sub{font-size:10px;color:rgba(255,255,255,.65);text-transform:uppercase;letter-spacing:3px;margin-top:3px;}

.sb-nav{padding:14px 10px;flex:1;overflow-y:auto;}
.sb-nav::-webkit-scrollbar{width:2px;}
.sb-nav::-webkit-scrollbar-thumb{background:rgba(255,255,255,.2);}
.sb-section{
  font-size:9px;color:rgba(255,255,255,.45);
  text-transform:uppercase;letter-spacing:2.5px;
  padding:0 10px;margin:16px 0 7px;
}
.sb-nav a{
  display:flex;align-items:center;gap:12px;
  padding:12px 14px;border-radius:12px;
  color:rgba(255,255,255,0.7);text-decoration:none;
  font-size:14px;font-weight:500;margin-bottom:4px;transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);
}
.sb-nav a .nav-icon{font-size:18px;width:24px;text-align:center;flex-shrink:0;transition:transform 0.3s;}
.sb-nav a:hover{
  background:rgba(255,255,255,0.15);
  color:#fff;transform:translateX(5px);
}
.sb-nav a:hover .nav-icon{transform:scale(1.2);}
.sb-nav a.active{
  background:rgba(255,255,255,0.22);
  color:#fff;font-weight:700;
  box-shadow:inset 0 0 0 1px rgba(255,255,255,0.25),0 4px 15px rgba(0,0,0,0.1);
  border-left:4px solid #fff;padding-left:10px;
}

.sb-footer{padding:16px;border-top:1px solid rgba(255,255,255,0.12);}
.sb-admin{
  display:flex;align-items:center;gap:12px;
  background:rgba(255,255,255,0.12);
  border:1px solid rgba(255,255,255,0.2);
  border-radius:14px;padding:12px;margin-bottom:12px;
  transition:all 0.3s;
}
.sb-admin:hover{background:rgba(255,255,255,0.18);transform:translateY(-2px);}
.sb-av{
  width:40px;height:40px;flex-shrink:0;
  background:linear-gradient(135deg,#F1DEDE,#fff);border-radius:50%;
  display:flex;align-items:center;justify-content:center;
  font-size:16px;font-weight:800;color:var(--sb-dark);
  box-shadow:0 2px 10px rgba(0,0,0,0.1);
}
.sb-an{font-size:14px;font-weight:700;color:#fff;}
.sb-ar{font-size:11px;color:rgba(255,255,255,0.6);margin-top:2px;}
.sb-logout{
  display:flex;align-items:center;justify-content:center;gap:8px;
  padding:12px;background:rgba(255,255,255,0.1);
  border:1px solid rgba(255,255,255,0.2);
  color:rgba(255,255,255,0.85);border-radius:12px;
  text-decoration:none;font-size:13px;font-weight:700;transition:all 0.3s;
}
.sb-logout:hover{background:rgba(220,38,38,0.3);color:#fff;border-color:rgba(220,38,38,0.4);transform:translateY(-2px);}

/* ══ MAIN ══ */
.main{margin-left:var(--sb-w);flex:1;display:flex;flex-direction:column;min-height:100vh;}
.topbar{
  background:#fff;padding:0 28px;height:65px;
  display:flex;align-items:center;justify-content:space-between;
  border-bottom:2px solid var(--off);
  box-shadow:0 4px 20px rgba(9,4,70,0.08);
  position:sticky;top:0;z-index:50;
  animation:slideDown 0.5s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes slideDown{from{opacity:0;transform:translateY(-20px);}to{opacity:1;transform:translateY(0);}}
.tb-left h1{
  font-family:"Space Grotesk",sans-serif;font-size:20px;font-weight:700;
  color:var(--sb-dark);display:flex;align-items:center;gap:10px;
}
.tb-left p{font-size:13px;color:var(--muted);margin-top:3px;}
.tb-right{display:flex;align-items:center;gap:10px;}
.year-badge{
  background:var(--sb-dark);color:#fff;
  padding:6px 14px;border-radius:9px;
  font-size:11px;font-weight:700;letter-spacing:.5px;
}
.date-pill{
  background:var(--off);border:1px solid var(--border);
  padding:6px 13px;border-radius:9px;
  font-size:12px;color:var(--muted);font-weight:500;
}
.content{padding:30px;}

/* ══ CARDS ══ */
.card{background:var(--white);border-radius:var(--radius);border:1px solid var(--border);box-shadow:var(--shadow);transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);}
.card:hover{box-shadow:0 12px 40px rgba(9,4,70,0.12);transform:translateY(-3px);}
.card-head{
  padding:18px 24px;border-bottom:1px solid var(--off);
  background:linear-gradient(135deg,#fff,var(--accent));
  border-radius:var(--radius) var(--radius) 0 0;
  display:flex;align-items:center;justify-content:space-between;
}
.card-head h3{font-family:"Space Grotesk",sans-serif;font-size:15px;font-weight:700;color:var(--sb-dark);display:flex;align-items:center;gap:10px;}
.card-body{padding:24px;}

/* ══ STATS ══ */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(200px,1fr));gap:20px;margin-bottom:28px;}
.stat-card{
  background:var(--white);border-radius:var(--radius);
  border:1px solid var(--border);
  padding:22px;display:flex;align-items:center;gap:16px;
  box-shadow:var(--shadow);transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);position:relative;overflow:hidden;
}
.stat-card::after{
  content:"";position:absolute;top:0;left:0;right:0;height:4px;
  background:linear-gradient(90deg,var(--sb-dark),var(--blue2));
  opacity:0;transition:all 0.3s;
}
.stat-card:hover{transform:translateY(-5px);box-shadow:0 15px 40px rgba(9,4,70,0.15);}
.stat-card:hover::after{opacity:1;}
.stat-icon{width:52px;height:52px;border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:24px;flex-shrink:0;background:var(--accent);transition:all 0.3s;}
.stat-card:hover .stat-icon{transform:scale(1.1) rotate(5deg);}
.stat-val{font-family:"Space Grotesk",sans-serif;font-size:30px;font-weight:700;color:var(--sb-dark);line-height:1;}
.stat-lbl{font-size:13px;color:var(--muted);margin-top:5px;font-weight:500;}

/* ══ TABLE ══ */
.tbl-wrap{overflow-x:auto;border-radius:0 0 var(--radius) var(--radius);}
table{width:100%;border-collapse:collapse;}
thead th{
  font-size:11px;font-weight:700;color:var(--muted);
  text-transform:uppercase;letter-spacing:.08em;
  padding:11px 18px;text-align:left;
  background:linear-gradient(135deg,var(--off),var(--accent));
  border-bottom:2px solid var(--border);white-space:nowrap;
}
tbody tr{transition:.15s;border-bottom:1px solid var(--off);}
tbody tr:hover{background:var(--accent);}
tbody tr:last-child{border-bottom:none;}
tbody td{padding:13px 18px;font-size:13px;color:var(--text);vertical-align:middle;}

/* ══ BADGES ══ */
.badge{display:inline-flex;align-items:center;gap:4px;padding:3px 10px;border-radius:100px;font-size:11px;font-weight:700;white-space:nowrap;}
.badge-blue{background:var(--accent);color:var(--sb-dark);border:1px solid var(--border);}
.badge-green{background:#E8F5E9;color:#2E7D32;border:1px solid #A5D6A7;}
.badge-yellow{background:#FFFDE7;color:#E65100;border:1px solid #FFF176;}
.badge-red{background:#FFEBEE;color:#C62828;border:1px solid #FFCDD2;}
.badge-purple{background:#F3E5F5;color:#6A1B9A;border:1px solid #CE93D8;}
.badge-cyan{background:#E0F7FA;color:#00695C;border:1px solid #80DEEA;}
.badge-gray{background:#F5F5F5;color:#616161;border:1px solid #E0E0E0;}
.badge-navy{background:var(--accent);color:var(--sb-dark);border:1px solid var(--border);}

/* ══ BUTTONS ══ */
.btn{display:inline-flex;align-items:center;gap:7px;padding:10px 18px;border-radius:10px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:.2s;text-decoration:none;font-family:"Plus Jakarta Sans",sans-serif;}
.btn-primary{background:var(--sb-dark);color:#fff;box-shadow:0 3px 12px rgba(21,101,192,.25);}
.btn-primary:hover{background:var(--sb-mid);transform:translateY(-1px);box-shadow:0 7px 20px rgba(21,101,192,.35);}
.btn-sm{padding:6px 13px;font-size:12px;border-radius:8px;}
.btn-outline{background:#fff;border:1.5px solid var(--border);color:var(--sb-dark);}
.btn-outline:hover{background:var(--accent);border-color:var(--blue);}
.btn-danger{background:#FFEBEE;border:1px solid #FFCDD2;color:var(--danger);}
.btn-danger:hover{background:var(--danger);color:#fff;}
.btn-success{background:#E8F5E9;border:1px solid #A5D6A7;color:var(--success);}
.btn-success:hover{background:var(--success);color:#fff;}

/* ══ FORM ══ */
.field{margin-bottom:15px;}
.field label{display:block;font-size:11px;font-weight:700;color:var(--sb-dark);text-transform:uppercase;letter-spacing:.08em;margin-bottom:6px;}
.field input,.field select,.field textarea{
  width:100%;padding:10px 13px;
  border:1.5px solid var(--border);border-radius:10px;
  font-size:13px;background:var(--off);color:var(--text);
  outline:none;transition:.2s;font-family:"Plus Jakarta Sans",sans-serif;
}
.field input:focus,.field select:focus,.field textarea:focus{
  border-color:var(--blue);background:#fff;
  box-shadow:0 0 0 3px rgba(25,118,210,.1);
}
.field textarea{resize:vertical;min-height:75px;}
.field-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.field-row3{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;}

/* ══ MODAL ══ */
.modal-overlay{display:none;position:fixed;inset:0;background:rgba(13,71,161,.4);z-index:200;align-items:center;justify-content:center;backdrop-filter:blur(5px);}
.modal-overlay.show{display:flex;animation:fadeIn .2s;}
@keyframes fadeIn{from{opacity:0}to{opacity:1}}
.modal{background:#fff;border-radius:20px;padding:28px;width:100%;max-width:520px;box-shadow:0 24px 70px rgba(25,118,210,.2);max-height:94vh;overflow-y:auto;animation:slideUp .25s;}
@keyframes slideUp{from{transform:translateY(16px);opacity:0}to{transform:translateY(0);opacity:1}}
.modal-lg{max-width:680px;}
.modal h3{font-family:"Space Grotesk",sans-serif;font-size:16px;font-weight:700;color:var(--sb-dark);margin-bottom:20px;padding-bottom:14px;border-bottom:2px solid var(--off);}
.modal-foot{display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:16px;border-top:1px solid var(--off);}

/* ══ FILTER BAR ══ */
.filter-bar{display:flex;align-items:center;gap:10px;margin-bottom:20px;flex-wrap:wrap;}
.filter-bar input,.filter-bar select{padding:9px 13px;border:1.5px solid var(--border);border-radius:10px;font-size:13px;background:#fff;outline:none;font-family:"Plus Jakarta Sans",sans-serif;transition:.2s;}
.filter-bar input:focus,.filter-bar select:focus{border-color:var(--blue);}
.filter-bar input{flex:1;min-width:180px;}

/* ══ ALERTS ══ */
.alert{padding:12px 16px;border-radius:11px;font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:10px;}
.alert-success{background:#E8F5E9;border:1px solid #A5D6A7;color:#2E7D32;}
.alert-error{background:#FFEBEE;border:1px solid #FFCDD2;color:#C62828;}
.alert-info{background:var(--accent);border:1px solid var(--border);color:var(--sb-dark);}
.alert-warning{background:#FFF3E0;border:1px solid #FFCC80;color:#E65100;}

/* ══ PROGRESS ══ */
.progress-bar{height:6px;background:var(--off);border-radius:3px;overflow:hidden;}
.progress-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,var(--sb-dark),var(--blue2));transition:.4s;}

/* ══ EMPTY STATE ══ */
.empty-state{padding:50px 20px;text-align:center;color:var(--muted);}
.empty-state .empty-icon{font-size:40px;margin-bottom:12px;opacity:.4;}
.empty-state p{font-size:14px;}

/* ══ STATUS PILLS ══ */
.status-pill{padding:4px 11px;border-radius:100px;font-size:11px;font-weight:700;}
.s-pending{background:#FFFDE7;color:#E65100;}
.s-approved{background:#E8F5E9;color:#2E7D32;}
.s-rejected{background:#FFEBEE;color:#C62828;}
.s-waitlisted{background:#F3E5F5;color:#6A1B9A;}
.s-doc_review{background:var(--accent);color:var(--sb-dark);}
.s-withdrawn{background:#F5F5F5;color:#616161;}

/* ══ CSS VARS COMPAT ══ */
.navy2{color:var(--sb-dark);}
:root{--navy2:var(--sb-dark);--navy:#0D47A1;--blue2:var(--blue2);--off:var(--off);--border:var(--border);--muted:var(--muted);--text:var(--text);--success:var(--success);--danger:var(--danger);}

@media(max-width:900px){
  .sidebar{display:none;}.main{margin-left:0;}
  .stats-grid{grid-template-columns:1fr 1fr;}
  .field-row,.field-row3{grid-template-columns:1fr;}
}
';}

function sidebar($active_page=''){
    $admin=$_SESSION['admin']??'';
    $char=strtoupper(substr($admin,0,1))?:'A';
    $nav=[
        'admin_dashboard.php'=>['📊','Dashboard'],
        'students.php'=>['👥','Students'],
        'courses.php'=>['📚','Courses'],
        'registrations.php'=>['📋','Registrations'],
        'departments.php'=>['🏫','Departments'],
        'academic_years.php'=>['📅','Academic Years'],
        'reports.php'=>['📊','Reports'],
        'audit_log.php'=>['🔍','Audit Log'],
        'settings.php'=>['⚙️','Settings'],
    ];
    $main=['admin_dashboard.php','students.php','courses.php','registrations.php','departments.php','academic_years.php'];
    $tools=['reports.php','audit_log.php','settings.php'];
    echo '<div class="sidebar">';
    echo '<div class="sb-brand"><div class="sb-logo">🎓</div><div><div class="sb-name">SLGTI</div><div class="sb-sub">Enterprise Portal</div></div></div>';
    echo '<div class="sb-nav"><div class="sb-section">Main</div>';
    foreach($main as $p){$c=basename($active_page)==$p?'active':'';echo "<a href='$p' class='$c'><span class='nav-icon'>{$nav[$p][0]}</span>{$nav[$p][1]}</a>";}
    echo '<div class="sb-section">Tools</div>';
    foreach($tools as $p){$c=basename($active_page)==$p?'active':'';echo "<a href='$p' class='$c'><span class='nav-icon'>{$nav[$p][0]}</span>{$nav[$p][1]}</a>";}
    echo '</div>';
    echo "<div class='sb-footer'>";
    echo "<div class='sb-admin'><div class='sb-av'>$char</div><div><div class='sb-an'>".htmlspecialchars($admin)."</div><div class='sb-ar'>Super Admin</div></div></div>";
    echo "<a href='admin_logout.php' class='sb-logout'>⏻ &​nbsp;Sign Out</a>";
    echo "</div></div>";
}
function topbar($title,$subtitle='',$year_label=''){
    echo "<div class='topbar'><div class='tb-left'><h1>$title</h1>".($subtitle?"<p>$subtitle</p>":'')."</div><div class='tb-right'>";
    if($year_label) echo "<span class='year-badge'>📅 $year_label</span>";
    echo "<span class='date-pill'>".date('D, d M Y')."</span>";
    echo "<a href='admin_logout.php' class='btn btn-sm' style='margin-left:8px;background:#7f1d1d;color:#fff;border:1px solid #991b1b;'>⏻ Sign Out</a>";
    echo "</div></div>";
}
?>