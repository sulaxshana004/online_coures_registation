<?php
include("config/db.php");

echo "<h2>Debug Registrations</h2>";
echo "<pre>";

// Check registrations table
$r = mysqli_query($conn, "SELECT COUNT(*) c FROM registrations");
echo "Total registrations: " . mysqli_fetch_assoc($r)['c'] . "\n";

// Check students table  
$r = mysqli_query($conn, "SELECT COUNT(*) c FROM students");
echo "Total students: " . mysqli_fetch_assoc($r)['c'] . "\n";

// Try the exact query
$q = mysqli_query($conn, "
    SELECT r.Reg_ID, r.App_ID, r.status, r.Stu_ID, r.Cou_ID,
           s.Stu_Name, s.Stu_Email,
           c.Cou_Name
    FROM registrations r
    JOIN students s ON r.Stu_ID = s.Stu_ID
    JOIN courses c ON r.Cou_ID = c.Cou_ID
    LIMIT 5
");

if(!$q){
    echo "QUERY ERROR: " . mysqli_error($conn) . "\n";
} else {
    echo "Query OK! Rows: " . mysqli_num_rows($q) . "\n";
    while($row = mysqli_fetch_assoc($q)){
        echo "Reg_ID:{$row['Reg_ID']} App_ID:{$row['App_ID']} Student:{$row['Stu_Name']} Course:{$row['Cou_Name']} Status:{$row['status']}\n";
    }
}

// Check if Doc_Upload column exists in students
$cols = mysqli_query($conn, "SHOW COLUMNS FROM students");
echo "\nStudents columns:\n";
while($c = mysqli_fetch_assoc($cols)) echo "  " . $c['Field'] . " (" . $c['Type'] . ")\n";

echo "</pre>";
?>