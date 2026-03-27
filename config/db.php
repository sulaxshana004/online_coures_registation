<?php
$conn = mysqli_connect("localhost","root","","online_course");
if(!$conn) die("Connection Failed: ".mysqli_connect_error());
mysqli_set_charset($conn,"utf8mb4");

function audit_log($conn,$actor_type,$actor_id,$actor_name,$action,$target_type='',$target_id=null,$details=''){
    $ip=$_SERVER['REMOTE_ADDR']??'CLI';
    $stmt=mysqli_prepare($conn,"INSERT INTO audit_log(Actor_Type,Actor_ID,Actor_Name,Action,Target_Type,Target_ID,Details,IP_Address) VALUES(?,?,?,?,?,?,?,?)");
    if($stmt){mysqli_stmt_bind_param($stmt,"ssississ",$actor_type,$actor_id,$actor_name,$action,$target_type,$target_id,$details,$ip);mysqli_stmt_execute($stmt);}
}
function generate_app_id($conn){
    mysqli_query($conn,"UPDATE app_id_counter SET last_val=last_val+1 WHERE id=1");
    $row=mysqli_fetch_assoc(mysqli_query($conn,"SELECT last_val FROM app_id_counter WHERE id=1"));
    return 'SLGTI-'.date('Y').'-'.str_pad($row['last_val'],5,'0',STR_PAD_LEFT);
}
function get_active_year($conn){
    $r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT Year_ID,Year_Label FROM academic_years WHERE Is_Active=1 LIMIT 1"));
    return $r?:['Year_ID'=>null,'Year_Label'=>'N/A'];
}
function get_enrolled_count($conn,$cou_id){
    $r=mysqli_fetch_assoc(mysqli_query($conn,"SELECT COUNT(*) AS c FROM registrations WHERE Cou_ID=$cou_id AND status NOT IN('Rejected','Withdrawn')"));
    return (int)$r['c'];
}
function notify_student($conn,$stu_id,$title,$message,$type='info'){
    $stmt=mysqli_prepare($conn,"INSERT INTO notifications(Stu_ID,Title,Message,Type) VALUES(?,?,?,?)");
    if($stmt){mysqli_stmt_bind_param($stmt,"isss",$stu_id,$title,$message,$type);mysqli_stmt_execute($stmt);}
}
?>
