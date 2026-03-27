<?php session_start(); include("config/db.php"); include("config/layout.php");
if(!isset($_SESSION['admin'])){header("Location: index.php");exit();}
$ay=get_active_year($conn);
$msg='';
if(isset($_POST['do_change_pw'])){
    $current=$_POST['current_pw'];
    $admin=mysqli_fetch_assoc(mysqli_query($conn,"SELECT * FROM admin WHERE UserName='{$_SESSION['admin']}' LIMIT 1"));
    if(password_verify($current,$admin['Password'])){
        $new=password_hash($_POST['new_pw'],PASSWORD_DEFAULT);
        mysqli_query($conn,"UPDATE admin SET Password='$new' WHERE Id={$admin['Id']}");
        $msg='success:Password changed successfully.';
    } else $msg='error:Current password is incorrect.';
}
page_head('Settings'); sidebar('settings.php');
?>
<div class="main">
<?php topbar('⚙️ Settings','System configuration &amp; account settings',$ay['Year_Label']); ?>
<div class="content">
<?php if($msg):[$t,$m]=explode(':',$msg,2);echo "<div class='alert alert-$t'>".htmlspecialchars($m)."</div>";endif;?>
<div style="max-width:500px">
<div class="card"><div class="card-head"><h3>🔒 Change Admin Password</h3></div><div class="card-body">
<form method="POST">
    <div class="field"><label>Current Password</label><input type="password" name="current_pw" required></div>
    <div class="field"><label>New Password</label><input type="password" name="new_pw" required minlength="8"></div>
    <button type="submit" name="do_change_pw" class="btn btn-primary">Update Password</button>
</form>
</div></div>
<div class="card" style="margin-top:18px"><div class="card-head"><h3>🏫 System Info</h3></div><div class="card-body">
<div style="display:flex;flex-direction:column;gap:12px;font-size:13px">
    <div><strong>Active Year:</strong> <?php echo $ay['Year_Label'];?></div>
    <div><strong>PHP Version:</strong> <?php echo PHP_VERSION;?></div>
    <div><strong>MySQL:</strong> <?php echo mysqli_get_server_info($conn);?></div>
    <div><strong>System:</strong> SLGTI Enterprise Academic Portal v2.0</div>
</div>
</div></div>
</div>
</div></div>
</body></html>
