<?php
class reconnectDB{
	private $connection	= false;
	private $open		= false;
	private $handle		= false;
	private $dbType		= false;//mysql,mongodb,...
	private $dbName		= false;
	private $childs		= array();
	private $authkey	= false;
	private $authkeys	= array();
	
	public function __construct($conn,$authkey,$name){
		$data = $conn->getData($authkey);
		if(!$data)
			throw new Exception();
		foreach($data as $key=>$val){
			$this->$key=$val;
		}
		$this->authkey=$authkey;
		unset($this->currentDB);
		$this->dbName=$name;
	}
	public function __get($name){
		return $this->selectCollection($name);
	}
	public function selectTable($name){
		return $this->selectCollection($name);
	}
	public function selectView($name){
		return $this->selectCollection($name);
	}
	public function selectCollection($name){
		if(!$this->handle||!is_string($name))
			return false;
		if(isset($this->childs[$name]))
			return $this->childs[$name];
		// create new reconnectCollection
		$key=md5('reconnect'.rand(1000,9999).time());
		$this->authkeys[]=$key;
		require_once('reconnectCollection.php');
		$coll = new reconnectCollection($this,$key,$name);
		if($coll){
			$this->childs[$name]=$coll;
			return $coll;
		}
		$this->childs[$name]=false;
		return false;
	}
	public function create($name,$args){
		$this->createTable($name,$args);
	}
	public function createTable(){}
	public function createView(){}
	public function createCollection(){}
	public function getData($key){
		if(!in_array($key,$this->authkeys))
			return false;
		return array(
			'connection'=>$this->connection,
			'open'=>$this->open,
			'handle'=>$this->handle,
			'dbType'=>$this->dbType,
			'dbName'=>$this->dbName
		);
	}
	public function __call($function,$args){
		$driverClass='reconnectDriver_'.mb_strtolower($this->dbType);
		$funct="db_".$function;
		if(function_exists($driverClass::$funct))
			return $driverClass::$funct($args);
		elseif(function_exists($driverClass::$function))
			return $driverClass::$function($args);
		else
			return false;//toDo: throw Exception
	}
}
?>