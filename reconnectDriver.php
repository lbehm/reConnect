<?php
interface reconnectDriver{
	/*connection*/
	public static function connect($dbal,$data,&$handle);
	public static function selectDB($dbal,$dbName,$handle);
	public static function close($dbal,$handle);
	public static function set_charset($dbal,$charset,$handle);
	/*collection/query*/
	public static function getTables($data=false,$handle=false);
	public static function createCollection($data=false,$handle=false);
	public static function removeCollection($data=false,$handle=false);
	public static function getTypeByArray($data);
	public static function query_array($data);
	public static function fetch_assoc($resource=false);
	public static function getLastError($handle=false);
	public static function affected_rows($resource=false);
	public static function escape($data,$handle=false);
	public static function escapeKey($data,$handle=false);
}
?>