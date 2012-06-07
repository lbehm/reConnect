<?php
require_once('reconnect.php');

$conn = new reconnect("mySQL://root@localhost");
//$conn->test->remove('table');
$table=$conn->test->table;
if(!$table)
	$table=$conn->test->create(array(
		'name'=>'table',
		'fields'=>array(
			'id'=>array(
				'type'=>'bigint',
				'unsigned'=>true,
				'zerofill'=>false,
				'null'=>1,
				'auto_increment'=>true,
				'primary'=>true,
				'comment'=>'Entry-ID'
			),
			'foo'=>array(
				'type'=>'varchar',
				'length'=>255,
				'null'=>-1
			),
			'bar'=>array(
				'type'=>'varchar',
				'length'=>255
			)
		),
		'primary'=>array(
			'id'
		),
		'options'=>array(
			'type'=>'Memory',
			'charset'=>'utf8',
			'collate'=>'utf8_bin'
		)
	));
if(!$table)
	die("Can't create collection");
$table->insert(array('foo'=>'TESTTEXT','bar'=>'Lorem Ipsum'))->query();
$query=$table->select()->query();
$data = $query->getAssoc();
$conn->close();

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