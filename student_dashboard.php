<?php
session_start(); include("config/db.php");
if(!isset($_SESSION['student_id'])){header("Location: index.php");exit();}
$sid=(int)$_SESSION['student_id'];
$stu=mysqli_fetch_assoc(mysqli_query($conn,"SELECT s.*,d.Dept_Name,d.Dept_Icon,d.Dept_Color FROM students s LEFT JOIN departments d ON s.Dept_ID=d.Dept_ID WHERE s.Stu_ID=$sid"));
$ay=get_active_year($conn); $yid=$ay['Year_ID'];

if(isset($_POST['do_apply'])){
    $cid=(int)$_POST['cou_id'];
    // Age check — must be at least 17 years old
    if(!empty($stu['Stu_DOB'])){
        $age=(int)floor((time()-strtotime($stu['Stu_DOB']))/(365.25*24*60*60));
        if($age < 17){
            $_SESSION['flash']='error:You must be at least 17 years old to apply. Your current age is '.$age.' years.';
            header("Location: student_dashboard.php"); exit();
        }
    }
    $has_reg=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Reg_ID,status FROM registrations WHERE Stu_ID=$sid AND Year_ID='$yid' LIMIT 1"));
    if($has_reg){
        $flash='error:You already have an active application. One application per academic year is allowed.';
    } else {
        $cou=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM courses WHERE Cou_ID=$cid AND NVQ_Level='{$stu['NVQ_Level']}' AND Is_Active=1"));
        if(!$cou){$flash='error:This course is not available for your NVQ level.';}
        else {
            $enrolled=get_enrolled_count($conn,$cid);
            $is_waitlist=($enrolled>=$cou['Max_Seats']);
            $app_id=generate_app_id($conn);
            $status=$is_waitlist?'Waitlisted':'Pending';
            $wpos=$is_waitlist?(int)($enrolled-$cou['Max_Seats']+1):0;
            $stmt=mysqli_prepare($conn,"INSERT INTO registrations(App_ID,Stu_ID,Cou_ID,Year_ID,status,Waitlist_Position,registered_at) VALUES(?,?,?,?,?,?,NOW())");
            mysqli_stmt_bind_param($stmt,"siiisi",$app_id,$sid,$cid,$yid,$status,$wpos);
            if(mysqli_stmt_execute($stmt)){
                $msg=$is_waitlist?"Waitlisted at position #$wpos for {$cou['Cou_Name']}."
                                 :"Application submitted (ID: $app_id). Awaiting admin review.";
                notify_student($conn,$sid,$is_waitlist?'📋 Waitlisted':'✅ Application Submitted',$msg,$is_waitlist?'warning':'info');
                audit_log($conn,'Student',$sid,$stu['Stu_Name'],'APPLY_COURSE','Registration',mysqli_insert_id($conn),"Applied: $app_id");
                $flash='success:'.$msg;
            } else {$flash='error:Database error: '.mysqli_error($conn);}
        }
    }
    $_SESSION['flash']=$flash; header("Location: student_dashboard.php"); exit();
}

if(isset($_POST['do_upload_doc'])){
    if(isset($_FILES['doc_file'])&&$_FILES['doc_file']['error']===0){
        $ext=strtolower(pathinfo($_FILES['doc_file']['name'],PATHINFO_EXTENSION));
        if($ext!=='pdf'){$_SESSION['flash']='error:Only PDF files allowed.';}
        else {
            $name=strtoupper($stu['Stu_NIC']).'_NVQ'.$stu['NVQ_Level'].'_Cert.pdf';
            if(move_uploaded_file($_FILES['doc_file']['tmp_name'],'uploads/'.$name)){
                mysqli_query($conn,"UPDATE students SET Doc_Upload='$name' WHERE Stu_ID=$sid");
                $_SESSION['flash']='success:Document uploaded. Awaiting verification.';
            } else $_SESSION['flash']='error:Upload failed. Contact admin.';
        }
    }
    header("Location: student_dashboard.php"); exit();
}

$flash=$_SESSION['flash']??''; unset($_SESSION['flash']);
$my_reg=mysqli_fetch_assoc(mysqli_query($conn,"SELECT r.*,c.Cou_Name,c.Cou_Duration,d.Dept_Name,d.Dept_Icon FROM registrations r JOIN courses c ON r.Cou_ID=c.Cou_ID LEFT JOIN departments d ON c.Dept_ID=d.Dept_ID WHERE r.Stu_ID=$sid AND r.Year_ID='$yid' LIMIT 1"));
$courses_q=mysqli_query($conn,"SELECT c.*,(SELECT COUNT(*) FROM registrations WHERE Cou_ID=c.Cou_ID AND status NOT IN('Rejected','Withdrawn')) AS enrolled FROM courses c WHERE c.NVQ_Level='{$stu['NVQ_Level']}' AND c.Dept_ID='{$stu['Dept_ID']}' AND c.Is_Active=1 ORDER BY c.Cou_Name");
$notifs=mysqli_query($conn,"SELECT * FROM notifications WHERE Stu_ID=$sid AND Is_Read=0 ORDER BY created_at DESC LIMIT 5");
$notif_count=mysqli_num_rows($notifs);
$first_name=htmlspecialchars(explode(' ',$stu['Stu_Name'])[0]);
$avatar=strtoupper(substr($stu['Stu_Name'],0,1));
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Student Dashboard — SLGTI</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{
  --navy:#090446;--navy2:#0d0659;--navy3:#11096b;--navy4:#1a1a5e;
  --cream:#F1DEDE;--cream-light:#FDF6F6;
  --white:#fff;--off:#FDF6F6;--bg:#F1DEDE;
  --border:#E8D5D5;--muted:#6b5b5b;--text:#090446;
  --success:#059669;--warning:#d97706;--danger:#dc2626;
}
body{font-family:"Plus Jakarta Sans",sans-serif;background:var(--bg);min-height:100vh;color:var(--text);}

/* ══════════════ TOPBAR ══════════════ */
.topbar{
  background:linear-gradient(135deg,var(--navy) 0%,var(--navy2) 50%,var(--navy3) 100%);
  height:70px;padding:0 32px;display:flex;align-items:center;justify-content:space-between;
  box-shadow:0 4px 30px rgba(9,4,70,0.25);position:sticky;top:0;z-index:100;
  animation:slideDown 0.6s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes slideDown{from{opacity:0;transform:translateY(-20px);}to{opacity:1;transform:translateY(0);}}
.tb-brand{display:flex;align-items:center;gap:14px;}
.tb-logo{
  width:46px;height:46px;background:linear-gradient(135deg,var(--cream),var(--white));
  border-radius:14px;display:flex;align-items:center;justify-content:center;
  font-size:23px;box-shadow:0 4px 20px rgba(9,4,70,0.3),0 0 0 3px rgba(241,222,222,0.4);flex-shrink:0;
  animation:pulse 2s ease-in-out infinite;
}
@keyframes pulse{0%,100%{box-shadow:0 4px 20px rgba(9,4,70,0.3),0 0 0 3px rgba(241,222,222,0.4);}50%{box-shadow:0 6px 25px rgba(9,4,70,0.4),0 0 0 3px rgba(241,222,222,0.5);}}
.tb-title{font-family:"Space Grotesk",sans-serif;font-size:18px;font-weight:700;color:#fff;letter-spacing:1px;}
.tb-sub{font-size:11px;color:rgba(147,197,253,0.7);text-transform:uppercase;letter-spacing:2px;margin-top:2px;}
.tb-right{display:flex;align-items:center;gap:14px;}
.notif-wrap{position:relative;}
.notif-btn{
  width:44px;height:44px;background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);
  border-radius:12px;display:flex;align-items:center;justify-content:center;
  cursor:pointer;font-size:20px;transition:all 0.3s;color:#fff;
}
.notif-btn:hover{background:rgba(255,255,255,0.18);border-color:rgba(255,255,255,0.3);transform:translateY(-2px);box-shadow:0 4px 15px rgba(0,0,0,0.1);}
.notif-dot{
  position:absolute;top:-4px;right:-4px;min-width:20px;height:20px;
  background:#ef4444;border-radius:10px;font-size:11px;font-weight:700;color:#fff;
  display:flex;align-items:center;justify-content:center;padding:0 5px;
  border:2px solid var(--navy);animation:bounce 1s ease infinite;
}
@keyframes bounce{0%,100%{transform:scale(1);}50%{transform:scale(1.1);}}
.user-pill{
  display:flex;align-items:center;gap:12px;
  background:rgba(255,255,255,0.1);border:1px solid rgba(255,255,255,0.2);
  border-radius:14px;padding:8px 16px;cursor:default;transition:all 0.3s;
}
.user-pill:hover{background:rgba(255,255,255,0.15);transform:translateY(-2px);}
.user-av{
  width:36px;height:36px;background:linear-gradient(135deg,var(--cream),var(--white));
  border-radius:50%;display:flex;align-items:center;justify-content:center;
  font-size:15px;font-weight:800;color:var(--navy);flex-shrink:0;box-shadow:0 2px 10px rgba(9,4,70,0.25);
}
.user-name{font-size:14px;font-weight:700;color:#fff;}
.user-lvl{font-size:11px;color:rgba(147,197,253,0.7);margin-top:1px;}
.btn-logout{
  display:flex;align-items:center;gap:8px;padding:10px 18px;
  background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);
  color:#fca5a5;border-radius:12px;text-decoration:none;font-size:13px;font-weight:700;
  transition:all 0.3s;letter-spacing:0.5px;
}
.btn-logout:hover{background:rgba(239,68,68,0.3);color:#fff;transform:translateY(-2px);box-shadow:0 4px 15px rgba(239,68,68,0.2);}

/* ══════════════ NOTIF DROPDOWN ══════════════ */
.notif-panel{
  display:none;position:absolute;right:0;top:calc(100% + 10px);
  width:330px;background:#fff;border:1px solid var(--border);
  border-radius:18px;box-shadow:0 20px 60px rgba(13,27,62,.15);z-index:300;overflow:hidden;
}
.notif-panel.open{display:block;animation:popIn .2s cubic-bezier(.34,1.56,.64,1);}
@keyframes popIn{from{opacity:0;transform:translateY(-8px) scale(.97)}to{opacity:1;transform:translateY(0) scale(1)}}
.np-head{
  padding:16px 18px;background:linear-gradient(135deg,var(--navy),var(--navy3));
  display:flex;align-items:center;justify-content:space-between;
}
.np-head h4{font-size:13px;font-weight:700;color:#fff;display:flex;align-items:center;gap:7px;}
.np-badge{background:rgba(255,255,255,.15);color:#fff;padding:2px 8px;border-radius:6px;font-size:11px;font-weight:700;}
.np-item{padding:13px 18px;border-bottom:1px solid #f1f5ff;display:flex;gap:11px;align-items:flex-start;}
.np-item:last-child{border-bottom:none;}
.np-icon{font-size:20px;flex-shrink:0;margin-top:1px;}
.np-title{font-size:12px;font-weight:700;color:var(--text);}
.np-msg{font-size:11px;color:var(--muted);margin-top:2px;line-height:1.5;}
.np-empty{padding:28px;text-align:center;font-size:13px;color:var(--muted);}
.np-empty .np-ei{font-size:32px;margin-bottom:8px;opacity:.4;}

/* ══════════════ PAGE LAYOUT ══════════════ */
.page{max-width:1140px;margin:0 auto;padding:28px 20px;}

/* ══════════════ WELCOME CARD ══════════════ */
.welcome-card{
  background:linear-gradient(135deg,var(--navy) 0%,var(--navy3) 55%,var(--navy4) 100%);
  border-radius:24px;padding:36px 40px;margin-bottom:28px;
  display:flex;align-items:center;justify-content:space-between;gap:28px;
  position:relative;overflow:hidden;animation:fadeInUp 0.8s cubic-bezier(0.34,1.56,0.64,1) both;
  box-shadow:0 12px 50px rgba(9,4,70,0.2);
}
@keyframes fadeInUp{from{opacity:0;transform:translateY(30px);}to{opacity:1;transform:translateY(0);}}
.welcome-card::before{
  content:"";position:absolute;right:-80px;top:-80px;
  width:320px;height:320px;
  background:radial-gradient(circle,rgba(37,99,235,0.25) 0%,transparent 70%);
  border-radius:50%;pointer-events:none;animation:float 6s ease-in-out infinite;
}
@keyframes float{0%,100%{transform:translate(0,0);}50%{transform:translate(20px,20px);}}
.welcome-card::after{
  content:"";position:absolute;left:30%;bottom:-100px;
  width:250px;height:250px;
  background:radial-gradient(circle,rgba(14,165,233,0.15) 0%,transparent 70%);
  border-radius:50%;pointer-events:none;
}
.wc-left{position:relative;z-index:1;}
.wc-greeting{
  display:inline-flex;align-items:center;gap:7px;
  background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.1);
  border-radius:8px;padding:5px 12px;font-size:11px;font-weight:700;
  color:rgba(147,197,253,.8);text-transform:uppercase;letter-spacing:1.5px;margin-bottom:14px;
}
.wc-name{font-family:"Space Grotesk",sans-serif;font-size:28px;font-weight:700;color:#fff;margin-bottom:6px;letter-spacing:-.5px;}
.wc-year{font-size:13px;color:rgba(255,255,255,.45);margin-bottom:18px;}
.wc-dept{
  display:inline-flex;align-items:center;gap:10px;
  background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.12);
  border-radius:13px;padding:11px 18px;
}
.wcd-icon{font-size:22px;}
.wcd-name{font-size:13px;font-weight:700;color:#fff;}
.wcd-lvl{font-size:11px;color:rgba(147,197,253,.55);margin-top:2px;}
.wc-right{position:relative;z-index:1;flex-shrink:0;}
.app-box{
  background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.12);
  border-radius:18px;padding:22px 28px;text-align:center;min-width:200px;
  backdrop-filter:blur(10px);
}
.app-box-lbl{font-size:10px;color:rgba(147,197,253,.5);text-transform:uppercase;letter-spacing:2px;margin-bottom:8px;}
.app-box-id{font-family:"Space Grotesk",sans-serif;font-size:20px;font-weight:700;color:#fff;margin-bottom:10px;letter-spacing:.5px;}
.app-box-no{font-size:13px;color:rgba(255,255,255,.4);margin-top:10px;}

/* ══════════════ STATUS PILLS ══════════════ */
.spill{display:inline-flex;align-items:center;gap:5px;padding:5px 13px;border-radius:100px;font-size:12px;font-weight:700;}
.sp-pending{background:#fef9c3;color:#854d0e;}
.sp-approved{background:#dcfce7;color:#15803d;}
.sp-rejected{background:#fee2e2;color:#b91c1c;}
.sp-waitlisted{background:#ede9fe;color:#6d28d9;}
.sp-doc_review{background:#dbeafe;color:#1d4ed8;}
.sp-withdrawn{background:#f1f5f9;color:#64748b;}

/* ══════════════ TIMELINE CARD ══════════════ */
.timeline-card{
  background:#fff;border-radius:20px;border:1px solid var(--border);
  padding:26px 28px;margin-bottom:24px;
  box-shadow:0 4px 24px rgba(13,27,62,.06);
}
.tc-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:24px;}
.tc-title{font-family:"Space Grotesk",sans-serif;font-size:15px;font-weight:700;color:var(--navy2);}
.tc-course{font-size:12px;color:var(--muted);margin-top:3px;}
.timeline{display:flex;align-items:flex-start;position:relative;padding:0 20px;}
.tl-line{
  position:absolute;top:16px;left:calc(16.66% + 16px);right:calc(16.66% + 16px);
  height:2px;background:#e2e8f0;z-index:0;
}
.tl-progress{height:100%;background:linear-gradient(90deg,var(--blue),var(--sky));border-radius:2px;transition:.5s;}
.tl-node{flex:1;display:flex;flex-direction:column;align-items:center;position:relative;z-index:1;}
.tl-circle{
  width:34px;height:34px;border-radius:50%;background:#f1f5f9;border:2px solid #e2e8f0;
  display:flex;align-items:center;justify-content:center;font-size:15px;
  margin-bottom:10px;transition:.3s;
}
.tl-node.done .tl-circle{background:linear-gradient(135deg,var(--blue),var(--sky));border-color:transparent;color:#fff;box-shadow:0 4px 12px rgba(37,99,235,.3);}
.tl-node.active .tl-circle{background:#fff;border-color:var(--blue);border-width:3px;box-shadow:0 0 0 5px rgba(37,99,235,.1);}
.tl-node.rejected .tl-circle{background:#fee2e2;border-color:#fca5a5;color:#dc2626;}
.tl-node-lbl{font-size:12px;font-weight:600;color:var(--muted);text-align:center;}
.tl-node.done .tl-node-lbl,.tl-node.active .tl-node-lbl{color:var(--navy2);}
.tl-node-sub{font-size:10px;color:var(--muted);margin-top:2px;text-align:center;}
.tc-info{
  display:flex;gap:0;margin-top:22px;background:var(--off);
  border-radius:14px;overflow:hidden;border:1px solid var(--border);
}
.tc-info-item{flex:1;padding:14px 18px;border-right:1px solid var(--border);}
.tc-info-item:last-child{border-right:none;}
.tci-lbl{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:1px;margin-bottom:5px;}
.tci-val{font-size:13px;font-weight:700;color:var(--navy2);}
.tc-alert{
  margin-top:18px;padding:13px 16px;border-radius:13px;
  font-size:13px;display:flex;align-items:flex-start;gap:10px;line-height:1.5;
}
.tc-alert.info{background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;}
.tc-alert.danger{background:#fff1f2;border:1px solid #fecdd3;color:#be123c;}

/* ══════════════ MAIN GRID ══════════════ */
.main-grid{display:grid;grid-template-columns:1fr 360px;gap:22px;align-items:start;}

/* ══════════════ SECTION HEADER ══════════════ */
.sec-header{
  display:flex;align-items:center;gap:10px;margin-bottom:16px;
  padding-bottom:12px;border-bottom:2px solid var(--border);
}
.sec-header h3{font-family:"Space Grotesk",sans-serif;font-size:15px;font-weight:700;color:var(--navy2);}
.sec-badge{
  background:linear-gradient(135deg,var(--navy3),var(--navy4));
  color:var(--cream);padding:3px 10px;border-radius:7px;font-size:11px;font-weight:700;
}

/* ══════════════ COURSE CARD ══════════════ */
.course-card{
  background:var(--white);border-radius:18px;border:1px solid var(--border);
  padding:24px;margin-bottom:16px;animation:fadeInUp 0.8s cubic-bezier(0.34,1.56,0.64,1) both;
  box-shadow:0 2px 12px rgba(30,58,95,0.06);transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);
  position:relative;overflow:hidden;
}
.course-card:hover{box-shadow:0 12px 40px rgba(30,58,95,0.12);transform:translateY(-4px) scale(1.01);}
.course-card::before{
  content:"";position:absolute;left:0;top:0;bottom:0;width:4px;
  background:linear-gradient(180deg,var(--blue),var(--sky));border-radius:4px 0 0 4px;
}
.course-card:hover::before{width:5px;}
.cc-top{display:flex;align-items:flex-start;justify-content:space-between;margin-bottom:12px;}
.cc-name{font-family:"Space Grotesk",sans-serif;font-size:15px;font-weight:700;color:var(--navy2);}
.cc-desc{font-size:12px;color:var(--muted);margin-top:4px;line-height:1.5;}
.nvq-tag{padding:3px 10px;border-radius:7px;font-size:11px;font-weight:700;flex-shrink:0;}
.nvq-l4{background:#fef9c3;color:#854d0e;border:1px solid #fde68a;}
.nvq-l5{background:#dcfce7;color:#15803d;border:1px solid #bbf7d0;}
.cc-bottom{display:flex;align-items:center;gap:14px;margin-top:14px;}
.seats-wrap{flex:1;}
.seats-lbl{display:flex;justify-content:space-between;font-size:11px;color:var(--muted);margin-bottom:5px;}
.seats-lbl .full-txt{color:var(--danger);font-weight:700;}
.seats-track{height:6px;background:#e2e8f0;border-radius:3px;overflow:hidden;}
.seats-fill{height:100%;border-radius:3px;background:linear-gradient(90deg,var(--blue),var(--sky));transition:.4s;}
.seats-fill.full{background:linear-gradient(90deg,#f97316,#ef4444);}
.btn-apply{
  padding:10px 20px;background:linear-gradient(135deg,var(--navy3),var(--navy));
  color:var(--white);border:none;border-radius:11px;font-size:13px;font-weight:700;
  cursor:pointer;transition:.25s;font-family:"Plus Jakarta Sans",sans-serif;
  white-space:nowrap;box-shadow:0 4px 14px rgba(9,4,70,0.25);
}
.btn-apply:hover{transform:translateY(-2px);box-shadow:0 8px 22px rgba(9,4,70,0.35);}
.btn-apply.waitlist{background:linear-gradient(135deg,#7c3aed,#6d28d9);box-shadow:0 4px 14px rgba(124,58,237,.25);}
.btn-apply.waitlist:hover{box-shadow:0 8px 22px rgba(124,58,237,.4);}
.btn-apply:disabled{background:#e2e8f0;color:var(--muted);cursor:not-allowed;transform:none;box-shadow:none;}
.no-courses{text-align:center;padding:50px 20px;background:#fff;border-radius:16px;border:1px solid var(--border);}
.no-courses .nc-icon{font-size:44px;margin-bottom:14px;opacity:.4;}
.no-courses p{font-size:14px;color:var(--muted);}

/* ══════════════ RIGHT PANEL CARDS ══════════════ */
.panel-card{background:var(--white);border-radius:18px;border:1px solid var(--border);padding:22px;margin-bottom:18px;box-shadow:0 2px 12px rgba(9,4,70,0.04);min-height:unset;}
.panel-card-title{font-family:"Space Grotesk",sans-serif;font-size:14px;font-weight:700;color:var(--navy2);margin-bottom:16px;display:flex;align-items:center;gap:8px;}
.doc-zone{
  border:2px dashed var(--border);border-radius:14px;padding:24px 16px;
  text-align:center;cursor:pointer;transition:.25s;background:var(--off);
}
.doc-zone:hover{border-color:var(--navy);background:rgba(9,4,70,0.03);}
.doc-zone .dz-icon{font-size:34px;margin-bottom:10px;}
.doc-zone .dz-title{font-size:13px;font-weight:700;color:var(--navy2);margin-bottom:4px;}
.doc-zone .dz-sub{font-size:11px;color:var(--muted);}
.doc-uploaded{
  display:flex;align-items:center;gap:12px;
  background:var(--off);border:1px solid var(--border);border-radius:13px;padding:14px;
}
.du-icon{font-size:28px;flex-shrink:0;}
.du-name{font-size:12px;font-weight:700;color:var(--navy2);word-break:break-all;}
.du-status{font-size:11px;font-weight:600;margin-top:3px;}
.du-verified{color:var(--success);}
.du-pending{color:var(--warning);}
.profile-row{display:flex;align-items:center;gap:12px;padding:11px 0;border-bottom:1px solid #f1f5ff;}
.profile-row:last-child{border-bottom:none;}
.pr-icon{font-size:17px;width:20px;text-align:center;flex-shrink:0;}
.pr-label{font-size:10px;color:var(--muted);text-transform:uppercase;letter-spacing:.8px;}
.pr-val{font-size:13px;font-weight:600;color:var(--text);margin-top:2px;}

/* ══════════════ ALERT ══════════════ */
.page-alert{
  display:flex;align-items:center;gap:11px;padding:14px 18px;
  border-radius:13px;font-size:13px;margin-bottom:22px;font-weight:500;
  animation:slideDown .3s;
}
@keyframes slideDown{from{opacity:0;transform:translateY(-8px)}to{opacity:1;transform:translateY(0)}}
.pa-success{background:#f0fdf4;border:1px solid #bbf7d0;color:#15803d;}
.pa-error{background:#fff1f2;border:1px solid #fecdd3;color:#be123c;}
.pa-info{background:var(--cream-light);border:1px solid var(--border);color:var(--navy);}

@media(max-width:900px){
  .main-grid{grid-template-columns:1fr;}
  .welcome-card{flex-direction:column;align-items:flex-start;}
  .app-box{width:100%;}
  .tc-info{flex-direction:column;}
  .tc-info-item{border-right:none;border-bottom:1px solid var(--border);}
  .tc-info-item:last-child{border-bottom:none;}
}
</style></head><body>

<!-- ═══════════════ TOPBAR ═══════════════ -->
<div class="topbar">
  <div class="tb-brand">
    <div class="tb-logo">🎓</div>
    <div>
      <div class="tb-title">SLGTI</div>
      <div class="tb-sub">Student Portal</div>
    </div>
  </div>
  <div class="tb-right">
    <!-- Notifications -->
    <div class="notif-wrap">
      <div class="notif-btn" onclick="toggleNotifs()" title="Notifications">
        🔔
        <?php if($notif_count>0): ?><span class="notif-dot"><?php echo $notif_count; ?></span><?php endif; ?>
      </div>
      <div class="notif-panel" id="notifPanel">
        <div class="np-head">
          <h4>🔔 Notifications</h4>
          <?php if($notif_count>0): ?><span class="np-badge"><?php echo $notif_count; ?> new</span><?php endif; ?>
        </div>
        <?php if($notif_count>0): mysqli_data_seek($notifs,0); while($n=mysqli_fetch_assoc($notifs)):
          $ni=$n['Type']=='success'?'✅':($n['Type']=='error'?'❌':($n['Type']=='warning'?'⚠️':'ℹ️'));
        ?>
        <div class="np-item">
          <div class="np-icon"><?php echo $ni; ?></div>
          <div>
            <div class="np-title"><?php echo htmlspecialchars($n['Title']); ?></div>
            <div class="np-msg"><?php echo htmlspecialchars(substr($n['Message'],0,80)); ?></div>
          </div>
        </div>
        <?php endwhile; else: ?>
        <div class="np-empty"><div class="np-ei">🔕</div><p>No new notifications</p></div>
        <?php endif; ?>
      </div>
    </div>
    <!-- User -->
    <div class="user-pill">
      <div class="user-av"><?php echo $avatar; ?></div>
      <div>
        <div class="user-name"><?php echo htmlspecialchars($stu['Stu_Name']); ?></div>
        <div class="user-lvl">NVQ Level <?php echo $stu['NVQ_Level']??'—'; ?> · <?php echo htmlspecialchars($stu['Dept_Name']??'—'); ?></div>
      </div>
    </div>
    <a href="studentdashboard_logout.php" class="btn-logout">⏻ Logout</a>
  </div>
</div>

<div class="page">

<!-- ═══════════════ FLASH ═══════════════ -->
<?php if($flash):[$ft,$fm]=explode(':',$flash,2);
  $pc=$ft=='success'?'pa-success':($ft=='error'?'pa-error':'pa-info');
  $pi=$ft=='success'?'✅':($ft=='error'?'❌':'ℹ️');
?>
<div class="page-alert <?php echo $pc; ?>"><?php echo $pi; ?> <?php echo htmlspecialchars($fm); ?></div>
<?php endif; ?>

<!-- ═══════════════ WELCOME CARD ═══════════════ -->
<div class="welcome-card">
  <div class="wc-left">
    <div class="wc-greeting">✦ Academic Year <?php echo $ay['Year_Label']; ?></div>
    <div class="wc-name">Welcome back, <?php echo $first_name; ?>! 👋</div>
    <div class="wc-year">Logged in as Student · <?php echo htmlspecialchars($stu['Stu_Email']); ?></div>
    <?php if($stu['Dept_ID']): ?>
    <div class="wc-dept">
      <span class="wcd-icon"><?php echo $stu['Dept_Icon']; ?></span>
      <div>
        <div class="wcd-name"><?php echo htmlspecialchars($stu['Dept_Name']); ?></div>
        <div class="wcd-lvl">NVQ Level <?php echo $stu['NVQ_Level']; ?> Programme</div>
      </div>
    </div>
    <?php endif; ?>
  </div>
  <?php if($my_reg): ?>
  <div class="wc-right">
    <div class="app-box">
      <div class="app-box-lbl">Application ID</div>
      <div class="app-box-id"><?php echo htmlspecialchars($my_reg['App_ID']); ?></div>
      <span class="spill sp-<?php echo strtolower($my_reg['status']); ?>"><?php echo str_replace('_',' ',$my_reg['status']); ?></span>
      <div class="app-box-no">Applied <?php echo date('d M Y',strtotime($my_reg['registered_at'])); ?></div>
    </div>
  </div>
  <?php endif; ?>
</div>

<!-- ═══════════════ STATUS TIMELINE ═══════════════ -->
<?php if($my_reg):
  $status=$my_reg['status'];
  $steps_map=['Pending'=>0,'Doc_Review'=>1,'Approved'=>2];
  $cur_step=$steps_map[$status]??-1;
  $rejected=($status=='Rejected');
  $waitlisted=($status=='Waitlisted');
  $nodes=[['📝','Applied','Registration submitted'],['📂','Doc Review','Documents verified'],['🎓','Enrolled','Officially enrolled']];
?>
<div class="timeline-card">
  <div class="tc-head">
    <div>
      <div class="tc-title">📋 Application Progress</div>
      <div class="tc-course"><?php echo htmlspecialchars($my_reg['Cou_Name']); ?> · <?php echo ($my_reg['Dept_Icon']??'').' '.htmlspecialchars($my_reg['Dept_Name']??''); ?></div>
    </div>
    <span class="spill sp-<?php echo strtolower($status); ?>"><?php echo str_replace('_',' ',$status); ?></span>
  </div>

  <div class="timeline">
    <div class="tl-line">
      <div class="tl-progress" style="width:<?php echo $rejected?'0':min(100,$cur_step*50); ?>%"></div>
    </div>
    <?php foreach($nodes as $i=>$n):
      $cls='';
      if($rejected) $cls=($i===0?'rejected':'');
      elseif($i<$cur_step) $cls='done';
      elseif($i===$cur_step) $cls='active';
    ?>
    <div class="tl-node <?php echo $cls; ?>">
      <div class="tl-circle"><?php echo $rejected&&$i===0?'❌':$n[0]; ?></div>
      <div class="tl-node-lbl"><?php echo $n[1]; ?></div>
      <div class="tl-node-sub"><?php echo $n[2]; ?></div>
    </div>
    <?php endforeach; ?>
  </div>

  <?php if($rejected): ?>
  <div class="tc-alert danger">❌ <div><strong>Application Rejected</strong><br><?php echo htmlspecialchars($my_reg['rejection_reason']??'No reason provided. Contact the admin.'); ?></div></div>
  <?php elseif($waitlisted): ?>
  <div class="tc-alert info">📋 <div><strong>You are on the Waitlist</strong> — Position #<?php echo $my_reg['Waitlist_Position']; ?><br>You will be automatically notified when a seat becomes available.</div></div>
  <?php endif; ?>

  <div class="tc-info">
    <div class="tc-info-item"><div class="tci-lbl">Course</div><div class="tci-val"><?php echo htmlspecialchars($my_reg['Cou_Name']); ?></div></div>
    <div class="tc-info-item"><div class="tci-lbl">Department</div><div class="tci-val"><?php echo ($my_reg['Dept_Icon']??'').' '.htmlspecialchars($my_reg['Dept_Name']??'—'); ?></div></div>
    <div class="tc-info-item"><div class="tci-lbl">Applied On</div><div class="tci-val"><?php echo date('d M Y',strtotime($my_reg['registered_at'])); ?></div></div>
    <div class="tc-info-item"><div class="tci-lbl">Status</div><div class="tci-val"><?php echo str_replace('_',' ',$status); ?></div></div>
  </div>
</div>
<?php endif; ?>

<!-- ═══════════════ MAIN GRID ═══════════════ -->
<div class="main-grid">

<!-- LEFT: COURSES -->
<div>
  <div class="sec-header">
    <h3>📚 Available Courses</h3>
    <span class="sec-badge"><?php echo htmlspecialchars($stu['Dept_Icon']??''); ?> <?php echo htmlspecialchars($stu['Dept_Name']??''); ?> · NVQ Level <?php echo $stu['NVQ_Level']??'?'; ?></span>
  </div>

  <?php
  $cou_count=mysqli_num_rows($courses_q);
  if($cou_count>0): mysqli_data_seek($courses_q,0); while($c=mysqli_fetch_assoc($courses_q)):
    $pct=$c['Max_Seats']>0?min(100,round($c['enrolled']/$c['Max_Seats']*100)):0;
    $full=($c['enrolled']>=$c['Max_Seats']);
    $lvl_cls=$c['NVQ_Level']=='5'?'nvq-l5':'nvq-l4';
  ?>
  <div class="course-card">
    <div class="cc-top">
      <div>
        <div class="cc-name"><?php echo htmlspecialchars($c['Cou_Name']); ?></div>
        <?php if(!empty($c['Cou_Description'])): ?>
        <div class="cc-desc"><?php echo htmlspecialchars(substr($c['Cou_Description'],0,130)); ?></div>
        <?php endif; ?>
      </div>
      <span class="nvq-tag <?php echo $lvl_cls; ?>">NVQ <?php echo $c['NVQ_Level']; ?></span>
    </div>
    <div class="cc-bottom">
      <div class="seats-wrap">
        <div class="seats-lbl">
          <span><?php echo $c['enrolled']; ?>/<?php echo $c['Max_Seats']; ?> seats taken</span>
          <?php if($full): ?><span class="full-txt">FULL · Waitlist open</span><?php endif; ?>
        </div>
        <div class="seats-track">
          <div class="seats-fill <?php echo $full?'full':''; ?>" style="width:<?php echo $pct; ?>%"></div>
        </div>
      </div>
      <?php
        $stu_age=!empty($stu['Stu_DOB'])?(int)floor((time()-strtotime($stu['Stu_DOB']))/(365.25*24*60*60)):99;
      ?>
      <?php if(!$my_reg): ?>
        <?php if($stu_age < 17): ?>
        <div style="font-size:11px;color:#C62828;font-weight:700;background:#FFEBEE;padding:6px 10px;border-radius:8px;border:1px solid #FFCDD2;white-space:nowrap;">
          🔞 Age <?php echo $stu_age; ?> — Min. 17 required
        </div>
        <?php else: ?>
        <form method="POST">
          <input type="hidden" name="cou_id" value="<?php echo $c['Cou_ID']; ?>">
          <button type="submit" name="do_apply" class="btn-apply <?php echo $full?'waitlist':''; ?>">
            <?php echo $full?'📋 Waitlist':'✅ Apply'; ?>
          </button>
        </form>
        <?php endif; ?>
      <?php else: ?>
      <button class="btn-apply" disabled>🔒 Applied</button>
      <?php endif; ?>
    </div>
  </div>
  <?php endwhile; else: ?>
  <div class="no-courses">
    <div class="nc-icon">📚</div>
    <p>No courses available for your department and NVQ level yet.<br>Please check back soon.</p>
  </div>
  <?php endif; ?>
</div>

<!-- RIGHT: SIDEBAR PANELS -->
<div style="position:sticky;top:80px;">
  <!-- Document -->
  <div class="panel-card">
    <div class="panel-card-title">📂 My Certificate</div>
    <?php if(!empty($stu['Doc_Upload'])): ?>
    <div class="doc-uploaded">
      <div class="du-icon">📄</div>
      <div>
        <div class="du-name"><?php echo htmlspecialchars($stu['Doc_Upload']); ?></div>
        <?php if($stu['Doc_Verified']): ?>
        <div class="du-status du-verified">✅ Verified by Admin</div>
        <?php else: ?>
        <div class="du-status du-pending">⏳ Pending Verification</div>
        <?php endif; ?>
      </div>
    </div>
    <?php else: ?>
    <form method="POST" enctype="multipart/form-data" id="docForm">
      <input type="file" name="doc_file" id="docFileInput" accept=".pdf" style="display:none" onchange="document.getElementById('docForm').submit()">
      <div class="doc-zone" onclick="document.getElementById('docFileInput').click()">
        <div class="dz-icon">📤</div>
        <div class="dz-title">Upload Your Certificate</div>
        <div class="dz-sub">
          <?php echo $stu['NVQ_Level']=='5'?'NVQ Level 4 Certificate required':'O/L Results PDF required'; ?>
          <br>PDF only · Auto-named: NIC_NVQ<?php echo $stu['NVQ_Level']; ?>_Cert.pdf
        </div>
      </div>
      <button type="submit" name="do_upload_doc" style="display:none"></button>
    </form>
    <?php endif; ?>
  </div>

  <!-- Profile -->
  <div class="panel-card">
    <div class="panel-card-title">👤 My Profile</div>
    <?php
    $prows=[
      ['📧','Email Address',$stu['Stu_Email']],
      ['📱','Phone',$stu['Stu_Phone']??'—'],
      ['🪪','NIC Number',$stu['Stu_NIC']],
      ['🎓','NVQ Level','Level '.$stu['NVQ_Level']],
      ['🏫','Department',$stu['Dept_Name']??'—'],
      ['📅','Member Since',date('d M Y',strtotime($stu['created_at']))],
    ];
    foreach($prows as $r):
    ?>
    <div class="profile-row">
      <span class="pr-icon"><?php echo $r[0]; ?></span>
      <div>
        <div class="pr-label"><?php echo $r[1]; ?></div>
        <div class="pr-val"><?php echo htmlspecialchars($r[2]); ?></div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <!-- Academic Year Info -->
  <div class="panel-card" style="background:linear-gradient(135deg,var(--navy),var(--navy3));border-color:rgba(255,255,255,.06)">
    <div class="panel-card-title" style="color:#fff;">📅 Academic Year</div>
    <div style="font-family:'Space Grotesk',sans-serif;font-size:26px;font-weight:700;color:#fff;margin-bottom:6px;"><?php echo $ay['Year_Label']; ?></div>
    <div style="font-size:12px;color:rgba(147,197,253,.55);">Current active academic year</div>
    <div style="margin-top:14px;padding:11px;background:rgba(255,255,255,.06);border-radius:10px;border:1px solid rgba(255,255,255,.08);">
      <div style="font-size:11px;color:rgba(147,197,253,.5);margin-bottom:4px;text-transform:uppercase;letter-spacing:1px;">Application Status</div>
      <?php if($my_reg): ?>
      <span class="spill sp-<?php echo strtolower($my_reg['status']); ?>"><?php echo str_replace('_',' ',$my_reg['status']); ?></span>
      <?php else: ?>
      <span style="font-size:13px;color:rgba(255,255,255,.5);">No application yet</span>
      <?php endif; ?>
    </div>
  </div>
</div>

</div><!-- /main-grid -->
</div><!-- /page -->

<script>
function toggleNotifs(){
    var p=document.getElementById('notifPanel');
    p.classList.toggle('open');
    if(p.classList.contains('open')) fetch('mark_notifs_read.php?sid=<?php echo $sid; ?>');
}
document.addEventListener('click',function(e){
    if(!e.target.closest('.notif-wrap')) document.getElementById('notifPanel').classList.remove('open');
});
// Auto-hide flash after 5s
var fa=document.querySelector('.page-alert');
if(fa) setTimeout(()=>fa.style.animation='slideDown .3s reverse forwards',5000);
</script>
</body>
</html>