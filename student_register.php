<?php
error_reporting(E_ALL); ini_set('display_errors',1);
session_start(); include("config/db.php");
$error=''; $success=''; $step=1;
if($_SERVER['REQUEST_METHOD']=='POST' && isset($_POST['do_register'])){
    $fname  = trim($_POST['fname']);
    $lname  = trim($_POST['lname']);
    $email  = trim($_POST['email']);
    $phone  = preg_replace('/[^0-9]/','',trim($_POST['phone']));
    $nic    = strtoupper(trim($_POST['nic']));
    $dob    = trim($_POST['dob']);
    $gender = trim($_POST['gender']);
    $addr   = trim($_POST['address']);
    $nvq    = in_array($_POST['nvq_level'],['4','5'])?$_POST['nvq_level']:'';
    $dept   = !empty($_POST['dept_id'])?(int)$_POST['dept_id']:null;
    $pw     = $_POST['password'];
    $cpw    = $_POST['confirm'];
    $full   = $fname.' '.$lname;

    // Validation
    if(!$fname||!$lname||!$email||!$phone||!$nic||!$dob||!$gender||!$nvq||!$pw||!$cpw)
        $error='Please fill in all required fields including NVQ Level.';
    elseif(!filter_var($email,FILTER_VALIDATE_EMAIL))
        $error='Invalid email address.';
    elseif(strlen($phone)!==10)
        $error='Phone number must be exactly 10 digits.';
    elseif(!preg_match('/^([0-9]{9}[vVxX]|[0-9]{12})$/',$nic))
        $error='Invalid NIC. Use old format (123456789V) or new (200012345678).';
    elseif($pw!==$cpw)
        $error='Passwords do not match.';
    elseif(strlen($pw)<8)
        $error='Password must be at least 8 characters.';
    elseif(!empty($dob) && $dob >= date('Y-m-d'))
        $error='Date of Birth cannot be today or a future date. Please enter your real birth date.';
    elseif(!empty($dob) && intval(substr($dob,0,4)) > intval(date('Y')))
        $error='Invalid year in Date of Birth. Year cannot be in the future.';
    elseif(!empty($dob) && intval(substr($dob,0,4)) < 1950)
        $error='Please enter a valid Date of Birth (year must be after 1950).';
    elseif(!empty($dob) && (intval(date('Y')) - intval(substr($dob,0,4))) < 5)
        $error='Invalid Date of Birth. Please enter your correct birth date.';
    else {
        // NIC uniqueness check (prevents duplicate accounts)
        $chk=mysqli_prepare($conn,"SELECT Stu_ID FROM students WHERE Stu_NIC=?");
        mysqli_stmt_bind_param($chk,"s",$nic); mysqli_stmt_execute($chk); mysqli_stmt_store_result($chk);
        if(mysqli_stmt_num_rows($chk)>0){$error='An account with this NIC already exists. Each person may only have one account.';}
        else {
            $chke=mysqli_prepare($conn,"SELECT Stu_ID FROM students WHERE Stu_Email=?");
            mysqli_stmt_bind_param($chke,"s",$email); mysqli_stmt_execute($chke); mysqli_stmt_store_result($chke);
            if(mysqli_stmt_num_rows($chke)>0){$error='This email is already registered.';}
            else {
                // Handle document upload
                $doc_file=null;
                if(isset($_FILES['doc_upload']) && $_FILES['doc_upload']['error']===0){
                    $ext=strtolower(pathinfo($_FILES['doc_upload']['name'],PATHINFO_EXTENSION));
                    if($ext!=='pdf'){$error='Document must be a PDF file.';}
                    else {
                        $new_name=$nic.'_NVQ'.$nvq.'_Cert.pdf';
                        $dest='uploads/'.$new_name;
                        if(move_uploaded_file($_FILES['doc_upload']['tmp_name'],$dest)) $doc_file=$new_name;
                        else $error='Failed to upload document. Check uploads folder permissions.';
                    }
                }
                if(!$error){
                    $hashed=password_hash($pw,PASSWORD_DEFAULT);
                    $dept_val = $dept ? (int)$dept : null;
                    $doc_val  = $doc_file ?? null;
                    $stmt=mysqli_prepare($conn,"INSERT INTO students(Stu_Name,Stu_Email,Stu_Phone,Stu_NIC,Stu_DOB,Stu_Gender,Stu_Address,Stu_Password,NVQ_Level,Dept_ID,Doc_Upload,Level_Chosen,created_at) VALUES(?,?,?,?,?,?,?,?,?,?,?,1,NOW())");
                    mysqli_stmt_bind_param($stmt,"sssssssssis",$full,$email,$phone,$nic,$dob,$gender,$addr,$hashed,$nvq,$dept_val,$doc_val);
                    if(mysqli_stmt_execute($stmt)){
                        $new_id=mysqli_insert_id($conn);
                        notify_student($conn,$new_id,'🎉 Registration Submitted','Welcome! Your account is pending admin approval. You will be notified once approved.','warning');
                        audit_log($conn,'System',0,'System','STUDENT_REGISTER','Student',$new_id,"New student registered (Pending): $full ($nic)");
                        $success='Registration successful! Your account is pending admin approval. You will receive an email once approved.';
                        echo '<script>setTimeout(()=>location.href="index.php",2500);</script>';
                    } else { $error='Database error: '.mysqli_error($conn); }
                }
            }
        }
    }
}
// Fetch departments
$depts=mysqli_query($conn,"SELECT Dept_ID,Dept_Name,Dept_Icon FROM departments ORDER BY Dept_Name");
$dept_rows=[]; while($dr=mysqli_fetch_assoc($depts)) $dept_rows[]=$dr;
?>
<!DOCTYPE html><html lang="en"><head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Student Registration — SLGTI Enterprise</title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
:root{--navy:#090446;--navy2:#0d0659;--navy3:#11096b;--navy4:#1a1a5e;--cream:#F1DEDE;--cream-light:#FDF6F6;--white:#fff;--off:#FDF6F6;--bg:#F1DEDE;--border:#E8D5D5;--text:#090446;--muted:#6b5b5b;--success:#059669;--warning:#d97706;--danger:#dc2626;--glass:rgba(255,255,255,0.95);}
body{font-family:"Plus Jakarta Sans",sans-serif;min-height:100vh;background:var(--bg);display:flex;align-items:center;justify-content:center;padding:30px 16px;}
body::before{content:"";position:fixed;inset:0;background-image:linear-gradient(rgba(9,4,70,0.03) 1px,transparent 1px),linear-gradient(90deg,rgba(9,4,70,0.03) 1px,transparent 1px);background-size:50px 50px;pointer-events:none;z-index:0;}
.container{width:100%;max-width:800px;position:relative;z-index:1;animation:containerFloat 1s cubic-bezier(0.34,1.56,0.64,1) both;}
@keyframes containerFloat{from{opacity:0;transform:translateY(40px) scale(0.95);}to{opacity:1;transform:translateY(0) scale(1);}}
.header{text-align:center;margin-bottom:30px;animation:fadeInUp 0.6s 0.2s ease both;}
@keyframes fadeInUp{from{opacity:0;transform:translateY(20px);}to{opacity:1;transform:translateY(0);}}
.header-logo{width:70px;height:70px;background:linear-gradient(135deg,var(--cream) 0%,var(--white) 100%);border-radius:20px;display:flex;align-items:center;justify-content:center;font-size:32px;margin:0 auto 18px;box-shadow:0 10px 40px rgba(9,4,70,0.25),0 0 0 4px rgba(241,222,222,0.5);}
@keyframes logoPulse{0%,100%{transform:scale(1);box-shadow:0 10px 40px rgba(14,165,233,0.4),0 0 0 4px rgba(255,255,255,0.5);}50%{transform:scale(1.05);box-shadow:0 15px 50px rgba(14,165,233,0.5),0 0 0 4px rgba(255,255,255,0.6);}}
.header h1{font-family:"Space Grotesk",sans-serif;font-size:30px;font-weight:700;color:var(--navy);margin-bottom:8px;}
.header p{color:var(--muted);font-size:14px;}
.wizard{background:var(--glass);backdrop-filter:blur(20px);border-radius:24px;border:1px solid rgba(255,255,255,0.8);box-shadow:0 25px 50px -12px rgba(9,4,70,0.15),0 0 0 1px rgba(255,255,255,0.6) inset;overflow:hidden;}
/* WIZARD STEPS */
.steps-bar{display:flex;background:var(--cream-light);padding:20px 28px;gap:0;border-bottom:1.5px solid var(--border);}
.step{flex:1;display:flex;align-items:center;gap:10px;position:relative;}
.step:not(:last-child)::after{content:"";position:absolute;right:0;top:50%;width:100%;height:1px;background:var(--border);z-index:0;left:50%;}
.step-num{background:var(--bg);border:1.5px solid var(--border);color:#94a3b8;}
.step.active .step-num{background:linear-gradient(135deg,var(--navy),var(--navy3));border-color:transparent;color:var(--white);box-shadow:0 0 20px rgba(9,4,70,0.4);}
.step.done .step-num{background:rgba(5,150,105,.3);border-color:rgba(5,150,105,.5);color:#34d399;}
.step-lbl{color:#64748b;}
.step.active .step-lbl{color:var(--navy);}
.step.done .step-lbl{color:#059669;}
/* FORM PANELS */
.form-panel{display:none;padding:36px 40px;}
.form-panel.active{display:block;animation:fadeSlide 0.5s cubic-bezier(0.34,1.56,0.64,1) both;}
@keyframes fadeSlide{from{opacity:0;transform:translateX(30px) scale(0.98);}to{opacity:1;transform:translateX(0) scale(1);}}
.panel-title{font-family:"Space Grotesk",sans-serif;font-size:24px;font-weight:800;color:var(--navy);margin-bottom:8px;}
.panel-sub{color:var(--muted);font-size:14px;margin-bottom:24px;}
.fg{margin-bottom:20px;}
.fg label{display:block;font-size:12px;font-weight:600;color:var(--navy);text-transform:uppercase;letter-spacing:0.5px;margin-bottom:8px;}
.fg input,.fg select,.fg textarea{width:100%;padding:14px 18px;background:var(--cream-light);border:2px solid var(--border);border-radius:14px;color:var(--navy);font-size:15px;font-family:"Plus Jakarta Sans",sans-serif;outline:none;transition:all 0.3s;}
.fg input:focus,.fg select:focus,.fg textarea:focus{border-color:var(--navy);background:#fff;box-shadow:0 0 0 4px rgba(9,4,70,0.08);}
.fg input::placeholder,.fg textarea::placeholder{color:#94a3b8;transition:opacity 0.3s;}
.fg input:focus::placeholder,.fg textarea:focus::placeholder{opacity:0.5;}
.fg select option{background:var(--white);color:var(--navy);}
.fg textarea{resize:vertical;min-height:100px;}
.row2{display:grid;grid-template-columns:1fr 1fr;gap:20px;}
/* NVQ Level Picker */
.nvq-picker{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:8px;}
.nvq-card input[type=radio]{display:none;}
.nvq-card label{display:block;padding:24px;background:var(--cream-light);border:2px solid var(--border);border-radius:16px;cursor:pointer;transition:all 0.3s;text-align:center;}
.nvq-card label:hover{border-color:rgba(9,4,70,0.3);background:rgba(9,4,70,0.04);transform:translateY(-4px);box-shadow:0 10px 30px rgba(9,4,70,0.1);}
.nvq-card input[type=radio]:checked+label{border-color:var(--navy);background:linear-gradient(135deg,rgba(9,4,70,0.08),rgba(9,4,70,0.12));box-shadow:0 0 25px rgba(9,4,70,0.2);}
.nvq-card .nvq-icon{font-size:36px;margin-bottom:12px;transition:transform 0.3s;}
.nvq-card label:hover .nvq-icon{transform:scale(1.1);}
.nvq-card .nvq-title{font-family:"Space Grotesk",sans-serif;font-size:18px;font-weight:700;color:var(--navy);margin-bottom:8px;}
.nvq-card .nvq-desc{color:var(--muted);font-size:13px;margin-bottom:12px;line-height:1.5;}
.nvq-card .nvq-req{display:inline-block;padding:6px 12px;background:var(--accent);color:var(--muted);font-size:11px;border-radius:20px;font-weight:500;}
/* Dept Picker */
.dept-picker{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;}
.dp-card input[type=radio]{display:none;}
.dp-card label{display:flex;align-items:center;gap:12px;padding:16px;background:var(--cream-light);border:2px solid var(--border);border-radius:14px;cursor:pointer;transition:all 0.3s;}
.dp-card label:hover{border-color:rgba(9,4,70,0.3);background:rgba(9,4,70,0.04);transform:translateY(-3px);box-shadow:0 8px 25px rgba(9,4,70,0.1);}
.dp-card input[type=radio]:checked+label{border-color:var(--navy);background:linear-gradient(135deg,rgba(9,4,70,0.1),rgba(9,4,70,0.15));box-shadow:0 0 20px rgba(9,4,70,0.2);}
.dp-card .dp-icon{font-size:24px;flex-shrink:0;transition:transform 0.3s;}
.dp-card label:hover .dp-icon{transform:scale(1.15) rotate(5deg);}
.dp-card .dp-name{font-weight:600;color:var(--navy);font-size:14px;}
/* Doc Upload */
.doc-area{padding:40px;text-align:center;background:var(--cream-light);border:2px dashed var(--border);border-radius:16px;cursor:pointer;transition:all 0.3s;}
.doc-area:hover{border-color:var(--navy);background:rgba(9,4,70,0.03);transform:translateY(-3px);box-shadow:0 10px 30px rgba(9,4,70,0.08);}
.doc-area input[type=file]{display:none;}
.doc-icon{font-size:42px;margin-bottom:12px;transition:transform 0.3s;}
.doc-area:hover .doc-icon{transform:scale(1.1) translateY(-5px);}
.doc-text{color:var(--muted);font-size:14px;font-weight:500;margin-bottom:6px;}
.doc-sub{color:#94a3b8;font-size:12px;}
/* Password strength */
.pw-strength{height:4px;background:#e2e8f0;border-radius:2px;margin-top:8px;overflow:hidden;}
.pw-bar{height:100%;border-radius:2px;transition:all 0.4s cubic-bezier(0.34,1.56,0.64,1);width:0;}
/* Navigation buttons */
.form-nav{display:flex;justify-content:space-between;align-items:center;padding:24px 40px;border-top:2px solid var(--border);background:var(--cream-light);}
.btn-next{padding:14px 32px;background:linear-gradient(135deg,var(--navy) 0%,var(--navy3) 100%);color:var(--white);border:none;border-radius:14px;font-size:15px;font-weight:600;cursor:pointer;transition:all 0.3s;font-family:"Plus Jakarta Sans",sans-serif;display:flex;align-items:center;gap:10px;box-shadow:0 8px 25px rgba(9,4,70,0.3);}
.btn-next::before{content:"";position:absolute;inset:0;background:linear-gradient(90deg,transparent,rgba(255,255,255,0.3),transparent);transform:translateX(-100%);transition:transform 0.6s;}
.btn-next:hover{transform:translateY(-3px);box-shadow:0 12px 35px rgba(9,4,70,0.35);}
.btn-next:hover::before{transform:translateX(100%);}
.btn-next:active{transform:translateY(-1px);}
.btn-back{padding:14px 28px;background:var(--cream-light);border:2px solid var(--border);color:var(--muted);border-radius:14px;font-size:15px;font-weight:500;cursor:pointer;transition:all 0.3s;font-family:"Plus Jakarta Sans",sans-serif;}
.btn-back:hover{background:var(--border);color:var(--navy);transform:translateY(-2px);}
.alert-error{padding:16px 20px;background:linear-gradient(135deg,#fef2f2,#fee2e2);border:2px solid #fecaca;border-radius:12px;color:#991b1b;font-size:14px;margin:20px 40px 0;display:flex;align-items:center;gap:10px;animation:shake 0.5s cubic-bezier(0.36,0.07,0.19,0.97);box-shadow:0 4px 15px rgba(220,38,38,0.1);}
@keyframes shake{0%,100%{transform:translateX(0);}10%,30%,50%,70%,90%{transform:translateX(-6px);}20%,40%,60%,80%{transform:translateX(6px);}}
.alert-success{padding:16px 20px;background:linear-gradient(135deg,#f0fdf4,#dcfce7);border:2px solid #bbf7d0;border-radius:12px;color:#166534;font-size:14px;margin:20px 40px 0;display:flex;align-items:center;gap:10px;animation:fadeIn 0.4s ease;box-shadow:0 4px 15px rgba(22,163,74,0.1);}
@keyframes fadeIn{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
.back-link{display:inline-flex;align-items:center;gap:8px;margin-top:24px;color:var(--muted);text-decoration:none;font-size:14px;font-weight:500;transition:all 0.3s;padding:12px 20px;border-radius:12px;}
.back-link:hover{color:var(--navy);background:rgba(9,4,70,0.06);transform:translateX(-5px);}
@media(max-width:600px){.row2,.nvq-picker,.dept-picker{grid-template-columns:1fr;}.steps-bar{gap:4px;}.step-lbl{display:none;}.form-panel{padding:22px 18px;}}
</style></head><body>
<div class="container">
<div class="header">
    <div class="header-logo">🎓</div>
    <h1>Student Registration</h1>
    <p>SLGTI Enterprise Academic Portal · <?php echo date('Y'); ?></p>
</div>

<?php if($success): ?>
<div class="wizard"><div class="form-panel active" style="text-align:center;padding:60px;">
    <div style="font-size:64px;margin-bottom:20px;">🎉</div>
    <div style="font-family:'Space Grotesk',sans-serif;font-size:24px;font-weight:700;color:#fff;margin-bottom:10px;">Registration Successful!</div>
    <div style="font-size:14px;color:rgba(255,255,255,.5);margin-bottom:24px;">Redirecting you to login in a moment...</div>
    <div style="width:60px;height:4px;background:linear-gradient(90deg,var(--blue),var(--sky));border-radius:2px;margin:0 auto;animation:loadBar 2.5s linear;"></div>
    <style>@keyframes loadBar{from{width:0;margin-left:auto}to{width:100%;margin-left:0}}</style>
</div></div>
<?php else: ?>

<div class="wizard">
    <!-- STEPS BAR -->
    <div class="steps-bar" id="stepsBar">
        <div class="step active" id="stp1"><div class="step-num">1</div><div class="step-lbl">Personal Info</div></div>
        <div class="step" id="stp2"><div class="step-num">2</div><div class="step-lbl">NVQ Level</div></div>
        <div class="step" id="stp3"><div class="step-num">3</div><div class="step-lbl">Department</div></div>
        <div class="step" id="stp4"><div class="step-num">4</div><div class="step-lbl">Documents</div></div>
        <div class="step" id="stp5"><div class="step-num">5</div><div class="step-lbl">Password</div></div>
    </div>

    <?php if($error): ?><div style="padding:14px 36px 0"><div class="alert-error">⚠️ <?php echo htmlspecialchars($error); ?></div></div><?php endif; ?>

    <form method="POST" enctype="multipart/form-data" id="regForm">

    <!-- PANEL 1: Personal Info -->
    <div class="form-panel active" id="panel1">
        <div class="panel-title">📋 Personal Information</div>
        <div class="panel-sub">Enter your personal details exactly as they appear on your NIC</div>
        <div class="row2">
            <div class="fg"><label>First Name *</label><input type="text" name="fname" placeholder="e.g. Kavindi" value="<?php echo htmlspecialchars($_POST['fname']??''); ?>" required></div>
            <div class="fg"><label>Last Name *</label><input type="text" name="lname" placeholder="e.g. Perera" value="<?php echo htmlspecialchars($_POST['lname']??''); ?>" required></div>
        </div>
        <div class="row2">
            <div class="fg"><label>Email Address *</label><input type="email" name="email" placeholder="your@email.com" value="<?php echo htmlspecialchars($_POST['email']??''); ?>" required></div>
            <div class="fg"><label>Phone Number * (10 digits)</label><input type="tel" name="phone" placeholder="0771234567" value="<?php echo htmlspecialchars($_POST['phone']??''); ?>" required maxlength="10"></div>
        </div>
        <div class="row2">
            <div class="fg"><label>NIC Number * (unique — one account per NIC)</label><input type="text" name="nic" placeholder="123456789V or 200012345678" value="<?php echo htmlspecialchars($_POST['nic']??''); ?>" required></div>
            <div class="fg"><label>Date of Birth *</label><input type="date" name="dob" value="<?php echo htmlspecialchars($_POST['dob']??''); ?>" required max="<?php echo date('Y-m-d'); ?>" min="1950-01-01" onchange="validateDOB(this)"></div>
        </div>
        <div class="row2">
            <div class="fg"><label>Gender *</label>
                <select name="gender" required>
                    <option value="">-- Select --</option>
                    <option value="Male" <?php echo(isset($_POST['gender'])&&$_POST['gender']=='Male')?'selected':''; ?>>Male</option>
                    <option value="Female" <?php echo(isset($_POST['gender'])&&$_POST['gender']=='Female')?'selected':''; ?>>Female</option>
                    <option value="Other" <?php echo(isset($_POST['gender'])&&$_POST['gender']=='Other')?'selected':''; ?>>Other</option>
                </select>
            </div>
            <div class="fg"><label>Address</label><input type="text" name="address" placeholder="Your address" value="<?php echo htmlspecialchars($_POST['address']??''); ?>"></div>
        </div>
        <div class="form-nav"><div></div><button type="button" class="btn-next" onclick="nextStep(1)">Next: NVQ Level →</button></div>
    </div>

    <!-- PANEL 2: NVQ Level -->
    <div class="form-panel" id="panel2">
        <div class="panel-title">🎓 Select Your NVQ Level</div>
        <div class="panel-sub">This is a permanent choice — it determines which courses you will see</div>
        <div class="nvq-picker">
            <div class="nvq-card">
                <input type="radio" name="nvq_level" id="nvq4" value="4" <?php echo(isset($_POST['nvq_level'])&&$_POST['nvq_level']=='4')?'checked':''; ?>>
                <label for="nvq4">
                    <div class="nvq-icon">🎓</div>
                    <div class="nvq-title">NVQ Level 4</div>
                    <div class="nvq-desc">Foundation diploma level. Entry-level professional qualification for school leavers.</div>
                    <div class="nvq-req">📄 Requires: O/L Results PDF</div>
                </label>
            </div>
            <div class="nvq-card">
                <input type="radio" name="nvq_level" id="nvq5" value="5" <?php echo(isset($_POST['nvq_level'])&&$_POST['nvq_level']=='5')?'checked':''; ?>>
                <label for="nvq5">
                    <div class="nvq-icon">⭐</div>
                    <div class="nvq-title">NVQ Level 5</div>
                    <div class="nvq-desc">Advanced diploma level. For graduates who hold NVQ Level 4 and seek specialisation.</div>
                    <div class="nvq-req">📄 Requires: NVQ Level 4 Certificate PDF</div>
                </label>
            </div>
        </div>
        <div class="form-nav"><button type="button" class="btn-back" onclick="prevStep(2)">← Back</button><button type="button" class="btn-next" onclick="nextStep(2)">Next: Department →</button></div>
    </div>

    <!-- PANEL 3: Department -->
    <div class="form-panel" id="panel3">
        <div class="panel-title">🏫 Select Your Department</div>
        <div class="panel-sub">Choose the technology department you wish to study in</div>
        <div class="dept-picker">
        <?php foreach($dept_rows as $dr): ?>
        <div class="dp-card">
            <input type="radio" name="dept_id" id="dept<?php echo $dr['Dept_ID']; ?>" value="<?php echo $dr['Dept_ID']; ?>" <?php echo(isset($_POST['dept_id'])&&$_POST['dept_id']==$dr['Dept_ID'])?'checked':''; ?>>
            <label for="dept<?php echo $dr['Dept_ID']; ?>">
                <span class="dp-icon"><?php echo $dr['Dept_Icon']; ?></span>
                <span class="dp-name"><?php echo htmlspecialchars($dr['Dept_Name']); ?></span>
            </label>
        </div>
        <?php endforeach; ?>
        </div>
        <div class="form-nav"><button type="button" class="btn-back" onclick="prevStep(3)">← Back</button><button type="button" class="btn-next" onclick="nextStep(3)">Next: Documents →</button></div>
    </div>

    <!-- PANEL 4: Documents -->
    <div class="form-panel" id="panel4">
        <div class="panel-title">📂 Document Upload</div>
        <div class="panel-sub">Upload the required certificate. System auto-names the file as [NIC]_NVQ[Level]_Cert.pdf</div>
        <div class="doc-area" onclick="document.getElementById('docFile').click()">
            <input type="file" name="doc_upload" id="docFile" accept=".pdf" onchange="showFileName(this)">
            <div class="doc-icon">📄</div>
            <div class="doc-text" id="docText">Click to upload PDF · NVQ Level 4: O/L Results &​nbsp;|&​nbsp; NVQ Level 5: Level 4 Certificate</div>
            <div class="doc-sub">PDF files only · Max 5MB</div>
        </div>
        <div style="font-size:11px;color:rgba(255,255,255,.3);margin-top:10px;padding:10px;background:rgba(255,255,255,.04);border-radius:9px;">
        </div>
        <div class="form-nav"><button type="button" class="btn-back" onclick="prevStep(4)">← Back</button><button type="button" class="btn-next" onclick="nextStep(4)">Next: Password →</button></div>
    </div>

    <!-- PANEL 5: Password -->
    <div class="form-panel" id="panel5">
        <div class="panel-title">🔒 Set Your Password</div>
        <div class="panel-sub">Choose a strong password — minimum 8 characters</div>
        <div class="row2">
            <div class="fg">
                <label>Password *</label>
                <input type="password" name="password" id="pwField" placeholder="Min 8 characters" required oninput="checkPw(this.value)">
                <div class="pw-strength"><div class="pw-bar" id="pwBar"></div></div>
                <div style="font-size:10px;color:rgba(255,255,255,.3);margin-top:5px;" id="pwTxt">Enter password</div>
            </div>
            <div class="fg"><label>Confirm Password *</label><input type="password" name="confirm" placeholder="Repeat password" required></div>
        </div>
        <div style="padding:14px;background:rgba(255,255,255,.04);border-radius:12px;margin-top:4px;border:1px solid rgba(255,255,255,.06);">
            <div style="font-size:12px;font-weight:600;color:rgba(255,255,255,.5);margin-bottom:8px;">📋 Registration Summary</div>
            <div id="summary" style="font-size:12px;color:rgba(255,255,255,.4);line-height:1.8;"></div>
        </div>
        <div class="form-nav">
            <button type="button" class="btn-back" onclick="prevStep(5)">← Back</button>
            <button type="submit" name="do_register" class="btn-next">🎓 Create My Account ✓</button>
        </div>
    </div>

    </form>
</div>
<?php endif; ?>

<a href="index.php" class="back-link">← Back to Login</a>
</div>

<script>
// DOB validation - block future dates and current year
function validateDOB(input){
    var val = input.value;
    if(!val) return;
    var parts = val.split('-');
    var year = parseInt(parts[0]);
    var today = new Date();
    var curYear = today.getFullYear();
    
    if(year > curYear){
        input.value = '';
        alert('Year ' + year + ' is invalid! Date of Birth cannot be in the future.');
        return;
    }
    if(year === curYear){
        var dob = new Date(val);
        if(dob >= today){
            input.value = '';
            alert('Date of Birth cannot be today or a future date!');
            return;
        }
    }
    if(year < 1950){
        input.value = '';
        alert('Please enter a valid year (after 1950).');
        return;
    }
    // Check age - must be at least 5 years old to register
    var age = curYear - year;
    if(age < 5){
        input.value = '';
        alert('Invalid birth year. Please enter your correct date of birth.');
        return;
    }
}

var currentStep=1;
var stepData={fname:'',lname:'',nvq:'',dept:''};

function nextStep(from){
    // Validate current panel
    if(from===1){
        var req=['fname','lname','email','phone','nic','dob','gender'];
        for(var i=0;i<req.length;i++){
            var el=document.querySelector('[name='+req[i]+']');
            if(el&&!el.value.trim()){el.focus();alert('Please fill in all required fields.');return;}
        }
    }
    if(from===2){
        if(!document.querySelector('[name=nvq_level]:checked')){alert('Please select an NVQ Level.');return;}
    }
    if(from===3){
        if(!document.querySelector('[name=dept_id]:checked')){alert('Please select a department.');return;}
    }
    document.getElementById('panel'+from).classList.remove('active');
    document.getElementById('stp'+from).classList.remove('active');
    document.getElementById('stp'+from).classList.add('done');
    currentStep=from+1;
    document.getElementById('panel'+currentStep).classList.add('active');
    document.getElementById('stp'+currentStep).classList.add('active');
    if(currentStep===5) updateSummary();
}

function prevStep(from){
    document.getElementById('panel'+from).classList.remove('active');
    document.getElementById('stp'+from).classList.remove('active');
    currentStep=from-1;
    document.getElementById('panel'+currentStep).classList.add('active');
    document.getElementById('stp'+currentStep).classList.remove('done');
    document.getElementById('stp'+currentStep).classList.add('active');
}

function updateSummary(){
    var nm=(document.querySelector('[name=fname]').value||'')+" "+(document.querySelector('[name=lname]').value||'');
    var em=document.querySelector('[name=email]').value||'';
    var nvqEl=document.querySelector('[name=nvq_level]:checked');
    var nvq=nvqEl?'NVQ Level '+nvqEl.value:'Not selected';
    var dEl=document.querySelector('[name=dept_id]:checked');
    var dept=dEl?dEl.nextElementSibling.querySelector('.dp-name').textContent:'Not selected';
    document.getElementById('summary').innerHTML='<b>Name:</b> '+nm+'<br><b>Email:</b> '+em+'<br><b>NVQ Level:</b> '+nvq+'<br><b>Department:</b> '+dept;
}

function checkPw(v){
    var bar=document.getElementById('pwBar'),txt=document.getElementById('pwTxt');
    var strength=0,color='';
    if(v.length>=8)strength++;
    if(/[A-Z]/.test(v))strength++;
    if(/[0-9]/.test(v))strength++;
    if(/[^A-Za-z0-9]/.test(v))strength++;
    var labels=['Too short','Weak','Fair','Strong','Very Strong'];
    var colors=['#ef4444','#f97316','#eab308','#22c55e','#10b981'];
    bar.style.width=(strength/4*100)+'%';
    bar.style.background=colors[strength]||colors[0];
    txt.textContent=labels[strength]||'';
    txt.style.color=colors[strength]||'#ef4444';
}

function showFileName(input){
    if(input.files&&input.files[0]){
        document.getElementById('docText').textContent='✅ Selected: '+input.files[0].name;
    }
}
</script>
</body></html>