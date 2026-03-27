<?php session_start(); include("config/db.php"); include("config/layout.php");
if(!isset($_SESSION['admin'])){header("Location: index.php");exit();}
$logs=mysqli_query($conn,"SELECT * FROM audit_log ORDER BY logged_at DESC LIMIT 200");
page_head('Audit Log'); sidebar('audit_log.php');
$ay=get_active_year($conn);
?>
<div class="main">
<?php topbar('🔍 Audit Log','Complete system activity trail',$ay['Year_Label']); ?>
<div class="content">
<div class="card">
<div class="card-head"><h3>🔍 System Audit Trail</h3><span class="badge badge-blue">Last 200 entries</span></div>
<div class="tbl-wrap">
<table>
<thead><tr><th>Time</th><th>Actor</th><th>Type</th><th>Action</th><th>Target</th><th>Details</th><th>IP</th></tr></thead>
<tbody>
<?php while($r=mysqli_fetch_assoc($logs)):?>
<tr>
  <td style="font-size:11px;white-space:nowrap;color:var(--muted)"><?php echo date('d M Y H:i',strtotime($r['logged_at']));?></td>
  <td style="font-weight:600;font-size:13px"><?php echo htmlspecialchars($r['Actor_Name']??'—');?></td>
  <td style="font-size:12px;font-weight:600"><?php echo htmlspecialchars($r['Action']);?></td>
  <td style="font-size:11px;color:var(--muted)"><?php echo htmlspecialchars($r['Target_Type']??'');?> <?php echo $r['Target_ID']?'#'.$r['Target_ID']:'';?></td>
  <td style="font-size:12px;max-width:300px"><?php echo htmlspecialchars(substr($r['Details']??'',0,80));?></td>
  <td style="font-size:11px;color:var(--muted);font-family:monospace"><?php echo htmlspecialchars($r['IP_Address']??'');?></td>
</tr>
<?php endwhile;?>
</tbody>
</table>
</div>
</div>
</div></div>
</body></html>
