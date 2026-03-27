<?php
error_reporting(E_ALL); ini_set('display_errors',1);
session_start(); include("config/db.php");
$message="";
if(isset($_POST['login'])){
    $user_type=$_POST['user_type'];
    if($user_type=="admin"){
        $u=mysqli_real_escape_string($conn,trim($_POST['username']));
        $r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM admin WHERE UserName='$u' LIMIT 1"));
        if($r && password_verify($_POST['password'],$r['Password'])){
            $_SESSION['admin']=$r['UserName'];$_SESSION['admin_id']=$r['Id'];
            audit_log($conn,'Admin',$r['Id'],$r['UserName'],'LOGIN','Admin',$r['Id'],'Admin login');
            header("Location: admin_dashboard.php");exit();
        }else{$message="Invalid admin credentials.";}
    }elseif($user_type=="student"){
        $e=mysqli_real_escape_string($conn,trim($_POST['username']));
        $r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM students WHERE Stu_Email='$e' LIMIT 1"));
        if($r && password_verify($_POST['password'],$r['Stu_Password'])){
            if($r['Account_Status']=='Pending'){
                $message="Your account is pending admin approval. Please wait for approval email.";
            }elseif($r['Account_Status']=='Rejected'){
                $message="Your registration was rejected. Contact admin for details.";
            }elseif($r['Account_Status']=='Suspended'){
                $message="Your account has been suspended. Contact admin.";
            }elseif($r['Account_Status']=='Active'){
                $_SESSION['student_id']=$r['Stu_ID'];$_SESSION['student_name']=$r['Stu_Name'];
                header("Location: student_dashboard.php");exit();
            }else{$message="Account status error. Contact admin.";}
        }else{$message="Invalid student credentials.";}
    }
}
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>SLGTI — Login</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Space+Grotesk:wght@400;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--navy:#090446;--navy-light:#1a1a5e;--cream:#F1DEDE;--cream-light:#FDF6F6;--white:#ffffff;--text:#090446;--border:#E8D5D5;--shadow:rgba(9,4,70,0.15);}
html,body{height:100%;overflow:auto;}
body{font-family:"Plus Jakarta Sans",sans-serif;display:flex;background:var(--cream);}

/* Animated Background Particles */
.particles{position:fixed;inset:0;overflow:hidden;pointer-events:none;z-index:0;}
.particle{position:absolute;border-radius:50%;background:rgba(9,4,70,0.04);animation:particleFloat 25s infinite ease-in-out;}
.particle:nth-child(1){width:300px;height:300px;left:-5%;top:10%;animation-delay:0s;}
.particle:nth-child(2){width:200px;height:200px;right:10%;top:60%;animation-delay:-5s;}
.particle:nth-child(3){width:150px;height:150px;left:30%;bottom:10%;animation-delay:-10s;}
.particle:nth-child(4){width:250px;height:250px;right:-5%;top:20%;animation-delay:-15s;}
@keyframes particleFloat{0%,100%{transform:translateY(0) rotate(0deg);}50%{transform:translateY(-50px) rotate(180deg);}}

/* Grid Overlay */
.grid-overlay{position:fixed;inset:0;background-image:linear-gradient(rgba(9,4,70,0.02) 1px,transparent 1px),linear-gradient(90deg,rgba(9,4,70,0.02) 1px,transparent 1px);background-size:60px 60px;pointer-events:none;z-index:1;}

/* LEFT PANEL */
.left{
  flex:1;display:flex;flex-direction:column;justify-content:space-between;
  padding:50px 56px;
  background:linear-gradient(135deg,#090446 0%,#0d0659 50%,#11096b 100%);
  position:relative;overflow:hidden;box-shadow:inset -20px 0 40px rgba(0,0,0,0.2);
}
/* Animated Shining circles */
.circle{position:absolute;border-radius:50%;filter:blur(40px);animation:cFloat ease-in-out infinite;}
.c1{width:500px;height:500px;background:rgba(241,222,222,0.08);top:-150px;right:-100px;animation-duration:20s;}
.c2{width:350px;height:350px;background:rgba(255,255,255,0.06);bottom:50px;left:-80px;animation-duration:25s;animation-delay:-8s;}
.c3{width:250px;height:250px;background:rgba(241,222,222,0.05);top:40%;right:15%;animation-duration:18s;animation-delay:-5s;}
@keyframes cFloat{0%,100%{transform:translate(0,0) scale(1);}33%{transform:translate(30px,-30px) scale(1.1);}66%{transform:translate(-20px,20px) scale(0.9);}}
/* Glass Grid */
.left::before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,0.05) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,0.05) 1px,transparent 1px);background-size:50px 50px;pointer-events:none;}
/* Shining right separator */
.left::after{content:"";position:absolute;right:0;top:0;bottom:0;width:3px;background:linear-gradient(180deg,transparent,rgba(255,255,255,0.6) 30%,rgba(255,255,255,0.9) 50%,rgba(255,255,255,0.6) 70%,transparent);filter:drop-shadow(0 0 10px rgba(255,255,255,0.5));}

.brand{display:flex;align-items:center;gap:15px;position:relative;z-index:2;animation:slideInLeft 0.8s cubic-bezier(0.34,1.56,0.64,1) both;}
@keyframes slideInLeft{from{opacity:0;transform:translateX(-50px);}to{opacity:1;transform:translateX(0);}}
.brand-logo{width:56px;height:56px;background:linear-gradient(135deg,var(--cream) 0%,var(--white) 100%);border-radius:14px;display:flex;align-items:center;justify-content:center;font-size:26px;box-shadow:0 4px 20px rgba(0,0,0,0.25);}
@keyframes logoPulse{0%,100%{transform:scale(1);}50%{transform:scale(1.05);}}
.brand-name{font-family:"Space Grotesk",sans-serif;font-size:26px;font-weight:700;color:var(--cream);letter-spacing:1px;}
.brand-sub{font-size:11px;color:var(--cream-light);text-transform:uppercase;letter-spacing:2px;margin-top:3px;font-weight:500;}

.hero{flex:1;display:flex;flex-direction:column;justify-content:center;position:relative;z-index:2;}
.live-pill{display:inline-flex;align-items:center;gap:8px;background:rgba(241,222,222,0.12);border:1px solid rgba(241,222,222,0.25);color:var(--cream);padding:6px 14px;border-radius:100px;font-size:11px;font-weight:500;letter-spacing:1px;text-transform:uppercase;width:fit-content;margin-bottom:24px;}
@keyframes pulseGlow{0%,100%{box-shadow:0 4px 20px rgba(0,0,0,0.1),0 0 0 0 rgba(255,255,255,0.4);}50%{box-shadow:0 4px 20px rgba(0,0,0,0.1),0 0 0 10px rgba(255,255,255,0);}}
.ldot{width:8px;height:8px;background:#69F0AE;border-radius:50%;box-shadow:0 0 12px #69F0AE;animation:blink 1.5s ease-in-out infinite;}
@keyframes blink{0%,100%{opacity:1;transform:scale(1);}50%{opacity:0.5;transform:scale(0.8);}}

.hero-title{font-family:"Space Grotesk",sans-serif;font-size:44px;font-weight:700;color:var(--white);line-height:1.1;letter-spacing:-0.5px;margin-bottom:16px;text-shadow:0 2px 10px rgba(0,0,0,0.2);}
@keyframes fadeInUp{from{opacity:0;transform:translateY(30px);}to{opacity:1;transform:translateY(0);}}
.shine{background:linear-gradient(90deg,#fff 0%,#dbeafe 25%,#fff 50%,#bfdbfe 75%,#fff 100%);background-size:200% auto;-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;animation:shimmer 3s linear infinite;}
@keyframes shimmer{0%{background-position:200% center;}100%{background-position:-200% center;}}

.hero-sub{font-size:15px;color:var(--cream-light);line-height:1.7;max-width:400px;margin-bottom:32px;font-weight:400;}
.feats{display:flex;flex-direction:column;gap:12px;animation:fadeInUp 0.6s 0.5s ease both;}
.feat{display:flex;align-items:center;gap:14px;padding:12px 16px;background:rgba(241,222,222,0.08);border:1px solid rgba(241,222,222,0.12);border-radius:12px;transition:all 0.2s ease;cursor:pointer;}
.feat:hover{background:rgba(241,222,222,0.12);transform:translateX(4px);}
.fic{width:36px;height:36px;border-radius:10px;background:rgba(241,222,222,0.18);display:flex;align-items:center;justify-content:center;font-size:16px;flex-shrink:0;}
.feat:hover .fic{transform:scale(1.1) rotate(5deg);}
.ftx{font-size:14px;color:var(--cream);font-weight:400;}
.ftx strong{color:#fff;font-weight:700;}

.stats-row{display:flex;background:rgba(241,222,222,0.1);border:1px solid rgba(241,222,222,0.15);border-radius:12px;overflow:hidden;position:relative;z-index:2;}
.sr{flex:1;padding:18px 14px;text-align:center;border-right:1px solid rgba(255,255,255,0.1);transition:background 0.2s;}
.sr:hover{background:rgba(255,255,255,0.08);}
.sr:last-child{border-right:none;}
.sr-n{font-family:"Space Grotesk",sans-serif;font-size:28px;font-weight:700;color:var(--white);margin-bottom:2px;}
.sr-l{font-size:10px;color:var(--cream);text-transform:uppercase;letter-spacing:1.5px;font-weight:500;}

/* RIGHT PANEL */
.right{width:480px;flex-shrink:0;display:flex;align-items:center;justify-content:center;padding:40px;perspective:1000px;position:relative;z-index:2;}
.card{
  width:100%;background:var(--cream-light);border-radius:16px;padding:40px 36px;
  border:1px solid var(--border);
  box-shadow:0 10px 40px var(--shadow);
  animation:fadeIn 0.5s ease both;
}
@keyframes cardFloat{from{opacity:0;transform:translateY(40px) rotateX(10deg) scale(0.95);}to{opacity:1;transform:translateY(0) rotateX(0) scale(1);}}
@keyframes floatCard{0%,100%{transform:translateY(0);}50%{transform:translateY(-8px);}}
/* Top accent line */
.card::before{
  content:"";display:block;height:3px;border-radius:3px;margin-bottom:32px;
  background:linear-gradient(90deg,#F1DEDE,#fff);
}
@keyframes lineShine{0%{background-position:0% 50%;}100%{background-position:300% 50%;}}

.ct{font-family:"Space Grotesk",sans-serif;font-size:24px;font-weight:700;color:var(--text);margin-bottom:4px;}
@keyframes fadeIn{from{opacity:0;}to{opacity:1;}}
.cs{font-size:14px;color:var(--navy);margin-bottom:28px;}

/* Role tabs */
.tabs{display:grid;grid-template-columns:1fr 1fr;gap:6px;background:var(--cream-light);border-radius:10px;padding:5px;margin-bottom:28px;border:1px solid var(--border);}
.tabs input[type=radio]{display:none;}
.tabs label{display:flex;align-items:center;justify-content:center;gap:6px;padding:10px 14px;border-radius:8px;font-size:13px;font-weight:500;color:var(--navy);cursor:pointer;transition:all 0.2s ease;position:relative;overflow:hidden;}
.tabs label::before{content:"";position:absolute;inset:0;background:#090446;opacity:0;transition:opacity 0.2s;z-index:0;border-radius:8px;}
.tabs input:checked+label{color:var(--cream);font-weight:600;box-shadow:0 2px 8px var(--shadow);}
.tabs input:checked+label::before{opacity:1;}
.tabs label span{position:relative;z-index:1;}
.tabs label:hover:not(:has(input:checked)){color:#090446;background:rgba(9,4,70,0.05);}

/* Fields */
.fld{margin-bottom:20px;animation:fadeIn 0.5s 0.5s ease both;}
.fld:nth-child(2){animation-delay:0.55s;}
.fld-lbl{display:block;font-size:11px;font-weight:700;color:var(--text);text-transform:uppercase;letter-spacing:1.5px;margin-bottom:8px;transition:color 0.3s;}
.fld-wrap{position:relative;}
.fld-ic{position:absolute;left:16px;top:50%;transform:translateY(-50%);font-size:18px;opacity:0.4;transition:all 0.3s cubic-bezier(0.34,1.56,0.64,1);z-index:2;}
.fld-wrap input{width:100%;padding:12px 14px 12px 44px;background:var(--cream-light);border:1.5px solid var(--border);border-radius:10px;color:var(--text);font-size:14px;outline:none;transition:all 0.2s ease;font-family:"Plus Jakarta Sans",sans-serif;}
.fld-wrap input:focus{border-color:#090446;background:var(--white);box-shadow:0 0 0 3px rgba(9,4,70,0.08);}
.fld-wrap input:focus+.fld-ic{opacity:1;color:#090446;}
.fld-wrap input::placeholder{color:#90A4AE;transition:opacity 0.3s;}
.fld-wrap input:focus::placeholder{opacity:0.5;}
.eye-btn{position:absolute;right:14px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#90A4AE;font-size:18px;transition:all 0.3s;padding:4px;width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:8px;}
.eye-btn:hover{color:#090446;background:rgba(9,4,70,0.05);}

.err{background:linear-gradient(135deg,#FFEBEE,#FFCDD2);border:1.5px solid #EF9A9A;color:#C62828;padding:12px 16px;border-radius:12px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:10px;animation:shake 0.5s cubic-bezier(0.36,0.07,0.19,0.97) both;box-shadow:0 4px 15px rgba(198,40,40,0.1);}
@keyframes shake{0%,100%{transform:translateX(0);}10%,30%,50%,70%,90%{transform:translateX(-6px);}20%,40%,60%,80%{transform:translateX(6px);}}

/* Submit button */
.btn-sub{
  width:100%;padding:14px;background:#090446;color:var(--white);border:none;border-radius:10px;font-size:14px;font-weight:600;cursor:pointer;letter-spacing:0.3px;transition:all 0.2s ease;font-family:"Plus Jakarta Sans",sans-serif;box-shadow:0 4px 14px var(--shadow);margin-top:8px;
}
@keyframes gradientShift{0%{background-position:0% 50%;}50%{background-position:100% 50%;}100%{background-position:0% 50%;}}
.btn-sub::before{content:"";position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent);transform:translateX(-100%);transition:transform 0.6s;}
.btn-sub:hover{background:#0d0659;transform:translateY(-2px);box-shadow:0 6px 20px rgba(9,4,70,0.25);}
.btn-sub:hover::before{transform:translateX(100%);}
.btn-sub:active{transform:translateY(-1px);}
/* Ripple effect */
.ripple{position:absolute;border-radius:50%;background:rgba(255,255,255,0.4);transform:scale(0);animation:rippleEffect 0.6s ease-out;pointer-events:none;}
@keyframes rippleEffect{to{transform:scale(4);opacity:0;}}

.reg-area{display:none;margin-top:20px;animation:fadeIn 0.4s ease;}
.reg-div{display:flex;align-items:center;gap:12px;margin-bottom:16px;}
.reg-div span{font-size:12px;color:#78909C;white-space:nowrap;font-weight:500;}
.reg-div::before,.reg-div::after{content:"";flex:1;height:1px;background:linear-gradient(90deg,transparent,var(--border),transparent);}
.reg-link{display:flex;align-items:center;justify-content:center;gap:8px;padding:12px 20px;background:var(--cream-light);border:1.5px solid var(--border);color:var(--text);border-radius:10px;text-decoration:none;font-size:13px;font-weight:500;transition:all 0.2s ease;}
.reg-link:hover{background:#090446;border-color:#090446;color:var(--white);}
.reg-link .arr{transition:transform 0.3s;}
.reg-link:hover .arr{transform:translateX(5px);}
.foot-note{text-align:center;margin-top:24px;font-size:12px;color:var(--navy);font-weight:400;}

@media(max-width:900px){.left{display:none;}.right{width:100%;padding:24px;}}
@media(max-width:480px){.card{padding:32px 24px;}}
</style></head><body>

<div class="particles"><div class="particle"></div><div class="particle"></div><div class="particle"></div><div class="particle"></div></div>
<div class="grid-overlay"></div>

<div class="left">
  <div class="circle c1"></div><div class="circle c2"></div><div class="circle c3"></div>
  <div class="brand">
    <div class="brand-logo">🎓</div>
    <div><div class="brand-name">SLGTI</div><div class="brand-sub">Sri Lanka German Training Institute</div></div>
  </div>
  <div class="hero">
    <div class="live-pill"><span class="ldot"></span> Live System · 2025/2026</div>
    <div class="hero-title">Smart Academic<br><span class="shine">NVQ Management</span><br>System</div>
    <div class="hero-sub">A unified platform for NVQ Level 4 &amp; 5 course registration, document verification, and academic workflow.</div>
    <div class="feats">
      <div class="feat"><div class="fic">🏫</div><div class="ftx"><strong>6 Departments</strong> — ICT, Mechanical, Electrical, Food, Auto, Construction</div></div>
      <div class="feat"><div class="fic">🛡️</div><div class="ftx"><strong>NIC Lock</strong> — One person, one account, one application</div></div>
      <div class="feat"><div class="fic">⚡</div><div class="ftx"><strong>Auto Waitlist</strong> — Smart seat promotion when slots open</div></div>
      <div class="feat"><div class="fic">🔍</div><div class="ftx"><strong>Audit Trail</strong> — Every action logged with timestamp</div></div>
    </div>
  </div>
  <div class="stats-row">
    <div class="sr"><div class="sr-n">6</div><div class="sr-l">Departments</div></div>
    <div class="sr"><div class="sr-n">L4/L5</div><div class="sr-l">NVQ Levels</div></div>
    <div class="sr"><div class="sr-n">30</div><div class="sr-l">Seats/Course</div></div>
    <div class="sr"><div class="sr-n">100%</div><div class="sr-l">Digital</div></div>
  </div>
</div>

<div class="right">
  <div class="card">
    <div class="ct">Welcome Back 👋</div>
    <div class="cs">Sign in to your SLGTI portal</div>
    <?php if($message): ?><div class="err">⚠️ <?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <form method="POST" id="lf" autocomplete="off">
      <div class="tabs">
        <input type="radio" name="user_type" id="ra" value="admin" <?php echo(!isset($_POST['user_type'])||$_POST['user_type']=='admin')?'checked':'';?>>
        <label for="ra">🛡️ Admin</label>
        <input type="radio" name="user_type" id="rs" value="student" <?php echo(isset($_POST['user_type'])&&$_POST['user_type']=='student')?'checked':'';?>>
        <label for="rs">🎓 Student</label>
      </div>
      <div class="fld">
        <label class="fld-lbl" id="ulbl">Username</label>
        <div class="fld-wrap">
          <span class="fld-ic">👤</span>
          <input type="text" name="username" id="uinp" placeholder="Enter username" required value="<?php echo htmlspecialchars($_POST['username']??''); ?>">
        </div>
      </div>
      <div class="fld">
        <label class="fld-lbl">Password</label>
        <div class="fld-wrap">
          <span class="fld-ic">🔑</span>
          <input type="password" name="password" id="pinp" placeholder="Enter password" required>
          <button type="button" class="eye-btn" onclick="togglePw()">👁</button>
        </div>
      </div>
      <button type="submit" name="login" class="btn-sub" id="lbtn">🛡️ Sign In as Admin</button>
    </form>
    <div class="reg-area" id="regArea">
      <div class="reg-div"><span>New to SLGTI?</span></div>
      <a href="student_register.php" class="reg-link">✏️ Create Student Account <span class="arr">→</span></a>
    </div>
    <div class="foot-note">🔐 Secured · SLGTI Enterprise · <?php echo date('Y'); ?></div>
  </div>
</div>

<script>
var card=document.querySelector('.card'),lbtn=document.getElementById('lbtn'),ra=document.getElementById('ra'),rs=document.getElementById('rs'),ulbl=document.getElementById('ulbl'),uinp=document.getElementById('uinp'),reg=document.getElementById('regArea');

// 3D Tilt Effect
if(card){card.addEventListener('mousemove',function(e){var r=card.getBoundingClientRect(),x=e.clientX-r.left,y=e.clientY-r.top,cx=r.width/2,cy=r.height/2,rx=(y-cy)/25,ry=(cx-x)/25;card.style.transform='perspective(1000px) rotateX('+rx+'deg) rotateY('+ry+'deg)';});card.addEventListener('mouseleave',function(){card.style.transform='perspective(1000px) rotateX(0) rotateY(0)';});}

// Ripple Effect
if(lbtn){lbtn.addEventListener('click',function(e){var r=lbtn.getBoundingClientRect(),ripple=document.createElement('span');ripple.className='ripple';ripple.style.left=(e.clientX-r.left)+'px';ripple.style.top=(e.clientY-r.top)+'px';lbtn.appendChild(ripple);setTimeout(function(){ripple.remove()},600);});}

function update(){if(rs.checked){ulbl.textContent='Email Address';uinp.type='email';uinp.placeholder='your@email.com';lbtn.innerHTML='<span class=\'spinner\'></span><span class=\'btn-text\'>🎓 Sign In as Student</span>';reg.style.display='block';reg.style.animation='fadeIn 0.4s ease';}else{ulbl.textContent='Username';uinp.type='text';uinp.placeholder='Enter username';lbtn.innerHTML='<span class=\'spinner\'></span><span class=\'btn-text\'>🛡️ Sign In as Admin</span>';reg.style.display='none';}}
ra.addEventListener('change',update);rs.addEventListener('change',update);update();

function togglePw(){var p=document.getElementById('pinp'),b=p.nextElementSibling;p.type=p.type==='password'?'text':'password';b.textContent=p.type==='password'?'👁':'🙈';}

document.getElementById('lf').addEventListener('submit',function(){lbtn.classList.add('loading');});
</script>
</body></html>
