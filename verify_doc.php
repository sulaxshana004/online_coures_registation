<?php session_start(); include("config/db.php");
if(!isset($_SESSION['admin'])){header("Location: index.php");exit();}
if(isset($_GET['id'])){
    $id=(int)$_GET['id'];
    mysqli_query($conn,"UPDATE students SET Doc_Verified=1 WHERE Stu_ID=$id");
    $stu=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Stu_Name FROM students WHERE Stu_ID=$id"));
    notify_student($conn,$id,'📄 Document Verified','Your certificate has been verified by the admin. Your application is being processed.','success');
    audit_log($conn,'Admin',$_SESSION['admin_id'],$_SESSION['admin'],'VERIFY_DOC','Student',$id,"Admin verified document for {$stu['Stu_Name']}");
}
header("Location: students.php"); exit();
