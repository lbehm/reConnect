<?php
require_once('reconnect.php');

$conn = new reconnect("mySQL://demo@localhost");
$table=$conn->mysql->db;
$query = $table->select(array('Host'=>'HOSTNAME','Db'=>'DATABASE','User'))->where(array('Host'=>'localhost','User'=>array('%ne'=>'root')))->sort(array('Db'))->query();
$conn->close();
var_dump($table);

/*
$data = $query->fetchAssoc();
?>
<html>
<head><title>Test-DB-Zugriff</title></head>
<body>
<table>
	<tr>
<?php
foreach($data[0] as $k=>$v){
	echo("\t\t<td>".$k."</td>\r\n");
}
echo("\t</tr>\r\n");
foreach($data as $id=>$arr){
	echo("\t<tr>\r\n");
	foreach($arr as $k=>$v){
		echo("\t\t<td>".$v."</td>\r\n");
	}
	echo("\t</tr>\r\n");
}
?>
</table>
<textarea><?php var_dump($data);?></textarea>
</body>
</html>
*/