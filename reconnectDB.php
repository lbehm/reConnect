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
		// does $name exist?
		$driver='reconnectDriver_'.mb_strtolower($this->dbType);
		if(in_array($name,$driver::getTables(array('db'=>$this->dbName),$this->handle))){
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
		}
		return false;
	}
	public function create($data=false){
		if(!$this->handle||!is_array($data))
			return false;
		$data['db']=$this->dbName;
		$driver='reconnectDriver_'.mb_strtolower($this->dbType);
		if($driver::createCollection($data,$this->handle))
			return $this->selectCollection($data['name']);
		else
			return false;
	}
	public function remove($name=false,$temp=false){
		if(!$this->handle || !$name)
			return false;
		$data=array('collection'=>$name,'db'=>$this->dbName,'temporary'=>$temp);
		$driver='reconnectDriver_'.mb_strtolower($this->dbType);
		if($driver::removeCollection($data,$this->handle)){
			unset($this->childs[$name]);
			return true;
		}
		return false;
	}
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
		if(method_exists($driverClass,$funct))
			return $driverClass::$funct($args);
		elseif(method_exists($driverClass,$function))
			return $driverClass::$function($args);
		else
			return false;//toDo: throw Exception
	}
}
?>