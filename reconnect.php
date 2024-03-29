<?php
class reconnect{
	private $connection;
	private $open 		= false;
	private $handle		= false;
	private $dbType		= false;//mysql,mongodb,...
	private $currentDB	= false;
	private $childs		= array();
	private $authkeys	= array();
	
	/*ASCENDING and DESCENDING for sort functions*/
	const ASC	=  1;
	const DESC	= -1;
	
	public function __construct($connection){
		if(is_string($connection)){
			if(preg_match('/\w+:\/\/\w+(:|(:\w+))?@\w+(:\d+)?(\/\w+)?/',$connection)){
				$connection=@parse_url($connection);
			}
		}
		if(is_array($connection)){
			$this->connection=array(
				'scheme'=>	((isset($connection['scheme']))?mb_strtolower(rawurldecode($connection['scheme'])):false),
				'host'=>	((isset($connection['host']))?	rawurldecode($connection['host']):'localhost'),
				'port'=>	((isset($connection['port']))?	rawurldecode($connection['port']):''),
				'user'=>	((isset($connection['user']))?	rawurldecode($connection['user']):'root'),
				'pass'=>	((isset($connection['pass']))?	rawurldecode($connection['pass']):''),
				'db'=>		((isset($connection['path']))?	substr(rawurldecode($connection['path']),1):'')
			);
			if(isset($connection['query'])){
				parse_str($connection['query'],$options);
				foreach($options as $k=>$v){
					$this->connection['options'][$k]=((mb_strtoupper($v)=='TRUE')?true:((mb_strtoupper($v)=='FALSE')?false:$v));
				}
				unset($options);
			}
			unset($connection);
		}
		elseif(is_a($connection,'link')){
			$this->connection=array(
				'scheme'=>	($connection->get('scheme'))?mb_strtolower($connection->get('scheme')):false,
				'host'=>	($connection->get('host'))?	$connection->get('host'):'localhost',
				'port'=>	($connection->get('port'))?	$connection->get('port'):'',
				'user'=>	($connection->get('user'))?	$connection->get('user'):'root',
				'pass'=>	($connection->get('pass'))?	$connection->get('pass'):'',
				'db'=>		($connection->get('path'))?	$connection->get('path'):'',
				'options'=>	$connection->getOption()
			);
			
		}
		if($this->connection['scheme']!=false){
			if(!isset($this->connection['options']['flags']))
				$this->connection['options']['flags']=0;
			$this->dbType=$this->connection['scheme'];
			$driverClass='reconnectDriver_'.mb_strtolower($this->dbType);
			require_once($driverClass.'.php');
			if(class_exists($driverClass)){
				if($driverClass::connect($this,$this->connection,$this->handle)){
					$this->open=true;
					if(isset($this->connection['options']['charset']))
						$this->set_charset($this->connection['options']['charset']);
					if($this->connection['db']!='')
						$this->selectDB($this->connection['db']);
					return;
				}
			}
		}
		
		throw new Exception("Connection failed");
	}
	
	public function __get($name){
		return $this->selectDB($name);
	}
	
	public function close(){
		$driverClass='reconnectDriver_'.mb_strtolower($this->dbType);
		if($driverClass::close($this,$this->handle)){
			$this->handle=false;
			return true;
		}
		return false;
	}
	public function query($arg){
		require_once('reconnectQuery.php');
		if(is_string($arg)){
			//execute query-string
			$query = new reconnectQuery(array(
				'sql'=>$arg,
				'handle'=>$this->handle,
				'driverClass'=>'reconnectDriver_'.mb_strtolower($this->dbType)
			));
			return $query;
		}
		else{
			throw new Exception('Unexpected parameter for function reconnectCollection::query()');
		}
	}
	
	public function selectDB($dbName){
		if(!$this->handle)
			return false;
		if($dbName==''||!is_string($dbName)){
			return $this->currentDB;
		}
		if(isset($this->childs[$dbName])){
			return $this->childs[$dbName];
		}
		// create new reconnectDB
		$key=md5('reconnect'.rand(1000,9999).time());
		$this->authkeys[]=$key;
		require_once('reconnectDB.php');
		$db = new reconnectDB($this,$key,$dbName);
		if($db){
			$this->childs[$dbName]=$db;
			$this->currentDB=$db;
			return $db;
		}
		$this->childs[$dbName]=false;
		return false;
	}
	public function dropDB(){}
	public function getCurrentDB(){
		return $this->currentDB;
	}
	public function getData($key){
		if(!in_array($key,$this->authkeys))
			return false;
		return array(
			'connection'=>$this->connection,
			'open'=>$this->open,
			'handle'=>$this->handle,
			'dbType'=>$this->dbType,
			'currentDB'=>$this->currentDB
		);
	}
	public function listDBs(){}//return array(string,string,...)
	public function getDBs(){}//return array(reconnectDB,reconnectDB,...)
	public function set_charset($charset){
		if(!$this->handle)
			return false;
		$this->connection['options']['charset']=$charset;
		$driverClass='reconnectDriver_'.mb_strtolower($this->dbType);
		return $driverClass::set_charset($this,$charset,$this->handle);
	}
	public function __toString(){
		$options=array();
		foreach($this->connection['options'] as $key=>$val){
			$options[]=rawurlencode($key).'='.rawurlencode(($val===true)?'TRUE':(($val===false)?'false':$val));
		}
		return $this->connection['scheme'].'://'.$this->connection['host'].':'.$this->connection['pass'].'@'.$this->connection['host'].':'.$this->connection['port'].((isset($this->connection['db']))?'/'.$this->connection['db']:'').((count($options))?('?'.implode('&',$options)):'');
	}
	public function __call($function,$args){
		$driverClass='reconnectDriver_'.mb_strtolower($this->dbType);
		if(method_exists($driverClass,$function))
			return $driverClass::$function($args,$this->handle);
		else
			return false;//toDo: throw Exception
	}
	public function escape($data){
		$driverClass='reconnectDriver_'.mb_strtolower($this->dbType);
		return $driverClass::escape($data,$this->handle);
	}
}
?>