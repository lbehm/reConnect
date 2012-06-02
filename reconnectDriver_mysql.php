<?php
class reconnectDriver_mysql{
	public static $last_link = false;
	public static $last_db = false;
	
	public function connect($dbal,$data,$handle){
		$handle=@mysql_connect($data['host'].':'.$data['port'],$data['user'],$data['pass'],false,$data['options']['flags']);
		self::$last_link=$handle;
		return ($handle)?true:false;
	}
	public function selectDB($dbal,$dbName,$handle){
		if(!$handle)
			return false;
		$r = @mysql_select_db($dbName,$handle);
		if($r)
			self::$last_db = $dbName;
		return $r;
	}
	public function close($dbal,$handle){
		if($handle){
			self::$last_link=false;
			return @mysql_close($handle);
		}
	}
	public function set_charset($dbal,$charset,$handle){
		if(!$handle)
			return false;
		return @mysql_set_charset($charset, $handle);
	}
}
?>