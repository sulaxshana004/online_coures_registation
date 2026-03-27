<?php session_start(); include("config/db.php");
if(isset($_SESSION['student_id'])){
    $sid=(int)$_SESSION['student_id'];
    mysqli_query($conn,"UPDATE Notifications SET Is_Read=1 WHERE Stu_ID=$sid");
}
