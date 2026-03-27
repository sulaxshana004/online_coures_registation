<?php
include("config/db.php");
$h = password_hash("1234", PASSWORD_DEFAULT);
$stmt = mysqli_prepare($conn, "UPDATE admin SET Password=? WHERE Id=1");
mysqli_stmt_bind_param($stmt,"s",$h);
mysqli_stmt_execute($stmt);
echo "✅ Done! Password = <strong>1234</strong><br><a href='index.php'>→ Go to Login</a>";
?>
