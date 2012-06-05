<?php
class reconnectCollection{
	private $connection	= false;
	private $open		= false;
	private $handle		= false;
	private $dbType		= false;//mysql,mongodb,...
	private $dbName		= false;
	private $db			= false;
	private $name		= false;
	private $authkey	= false;
	
	private $query_field;
	private $query_field_alias;
	private $query_where;
	private $query_limit;
	private $query_offset;
	private $query_sort;
	private $query_distinct;
	private $query_count;
	private $query_update;
	private $query_remove;
	
	public function __construct($db,$authkey,$name){
		$data = $db->getData($authkey);
		if(!$data)
			throw new Exception();
		foreach($data as $key=>$val){
			$this->$key=$val;
		}
		$this->db=$db;
		$this->authkey=$authkey;
		$this->name=$name;
	}
	public function __call($function,$args){
		$driverClass='reconnectDriver_'.mb_strtolower($this->dbType);
		$funct="collection_".$function;
		if(method_exists($driverClass,$funct))
			return $driverClass::$funct($args);
		elseif(method_exists($driverClass,$function))
			return $driverClass::$function($args);
		else
			return false;//toDo: throw Exception
	}
	public function getName(){
		return (string)($this->dbName.'.'.$this->name);
	}
	
	//collectionFunctions / building a nice query
	public function select($data=array('*')){
		if(is_array($data)){
			/* deal with it:
				->select(array('field1','id'=>'foo'))
				SQL: select field1, id AS foo from ...
			*/
			if(in_array('*',$data)||isset($data['*'])){
				$this->query_field=array();
			}
			else{
				foreach($data as $key=>$val){
					if(is_numeric($key)){
						//no alias found - work with $val as fieldname
						if(is_int($key) && is_string($val) && !is_numeric($val))
							$this->query_field[]=$val;	
						else
							throw new Exception("ERROR: Unexpected parameter as fieldname at reconnectCollection->select( array )!!");
					}
					elseif(is_string($key) && is_string($val)){
						//there is a alias at $val - $key is our fieldname
						$this->query_field[]=$key;
						$this->query_field_alias[$key]=$val;
					}
					else{
						throw new Exception("ERROR: Unexpected parameter at reconnectCollection->select( array )!");
					}
				}
			}
		}
		else{
			//expecting: *
			$this->query_field=array();
		}
		return $this;
	}
	public function where($data,$mode='AND'){
		if(!is_array($data))
			throw new Exception("ERROR: Unexpected parameter at reconnectCollection->where( array [, string ] )!");
		/* deal with it:
			->where(array("col"=>5,array('%or'=>array("col"=>array('%ne'=>5),"id"=>array('%lt'=>50,'%gt'=>25)))))
			SQL: ...WHERE `col`= 5 AND ( `col` <> 5 OR (`id` < 50 AND  `id` > 25))
		*/
		if(count($this->query_where)){
			/*example:
				$this->query_where==array(array("col"=>5))
				SQL: ...WHERE `col` = 5
			*/
			switch(strtolower($mode)){
				case 'or':
					$this->query_where=array('%or'=>array($this->query_where,$data));
					break;
				default :
				case 'and':
					$this->query_where[]=$data;
			}
		}
		else{
			$this->query_where = array($data);
		}
		return $this;
	}
	public function limit($data){
		if(!is_int($data))
			throw new Exception("ERROR: Unexpected parameter at reconnectCollection->limit( int )!");
		/* deal with it:
			->limit(10)
			SQL: ...LIMIT 10
		*/
		$this->query_limit = $data;
		return $this;
	}
	public function offset($data){
		if(!is_int($data))
			throw new Exception("ERROR: Unexpected parameter at reconnectCollection->offset( int )!");
		/* deal with it:
			->offset(10)
			SQL: ...{ LIMIT X OFFSET 10 | LIMIT 10, X }
			WARNING: in mySQL 'OFFSET' will be ignored if 'LIMIT' isn't set!
		*/
		$this->query_offset = $data;
		return $this;
	}
	public function sort($data){
		if(!is_array($data))
			throw new Exception("ERROR: Unexpected parameter at reconnectCollection->sort( array )!");
		/* deal with it:
			->sort(array('col'=>reconnect::DESC,"id"))
			SQL: ...ORDER BY `col` DESC, `id` [ASC]
			reconnect::ASC	=  1
			reconnect::DESC	= -1
		*/
		foreach($data as $key=>$val){
			if(is_numeric($key)){
				//no sort direction found - work with $val as fieldname
				if(is_int($key) && is_string($val) && !is_numeric($val))
					$this->query_sort[]=array($val=>1);
				else
					throw new Exception("ERROR: Unexpected parameter as fieldname at reconnectCollection->sort( array )!!");
			}
			elseif(is_string($key) && is_numeric($val)){
				//there is a sort direction at $val - $key is our fieldname
				$this->query_sort[]=array($key=>intval($val));
			}
			else{
				throw new Exception("ERROR: Unexpected parameter at reconnectCollection->sort( array )!");
			}
		}
		return $this;
	}
	public function distinct($data=true){
		/* deal with it:
			->select()->distinct()
			SQL: SELECT DISTINCT {...} FROM...
		*/
		if(!is_bool($data))
			throw new Exception("ERROR: Unexpected parameter at reconnectCollection->distinct( bool )!");
		$this->query_distinct = (bool) $data;
		return $this;
	}
	public function count($data=true){
		/* deal with it:
			->select()->count()
			SQL: SELECT COUNT(*) FROM...
		*/
		if(!is_bool($data))
			throw new Exception("ERROR: Unexpected parameter at reconnectCollection->count( bool )!");
		$this->query_count = (bool) $data;
		return $this;
	}
	public function update($data,$overwrite=true){
		/* deal with it:
			->update(array("col"=>"abc","field"=>array('%inc'=>1)))
			SQL: UPDATE {table} SET `col`='abc', `field`=`field`+1 {...}
		*/
		if(!is_array($data) || !is_bool($overwrite))
			throw new Exception("ERROR: Unexpected parameter at reconnectCollection->update( array [, bool ] )!");
		if(count($this->query_update)){
			/*example:
				$this->query_update==array("col"=>5)
				SQL: {...} SET `col` = 5 {...}
			*/
			$this->query_update = ($overwrite)?array_merge($this->query_update,$data):array_merge($data,$this->query_update);
		}
		else
			$this->query_update = $data;
		return $this;
	}
	public function remove($data=true){
		/* deal with it:
			->remove()->where(array("z" => "abc"))
			SQL: DELETE FROM {table} WHERE `z`= 'abc'
		*/
		if(!is_bool($data))
			throw new Exception("ERROR: Unexpected parameter at reconnectCollection->remove( bool )!");
		$this->query_remove = (bool) $data;
		return $this;
	}
	public function delete(){
		return $this->remove();
	}
	public function flushQuery(){
		$this->query_field=null;
		$this->query_field_alias=null;
		$this->query_where=null;
		$this->query_limit=null;
		$this->query_offset=null;
		$this->query_sort=null;
		$this->query_distinct=null;
		$this->query_count=null;
		$this->query_update=null;
		$this->query_remove=null;
		return $this;
	}
	
	
	//assembling query-data
	//return reconnectQuery
	public function query($arg=false){
		require_once('reconnectQuery.php');
		if($arg===false){
			//$conn->db->table->select('*')->count()->query()
			//gather query blocks
			$data=array(
				'db'=>$this->dbName,
				'table'=>$this->name,
				'handle'=>$this->handle,
				'dbType'=>$this->dbType,
				'driverClass'=>'reconnectDriver_'.mb_strtolower($this->dbType),
				'field'=>$this->query_field,
				'field_alias'=>$this->query_field_alias,
				'where'=>$this->query_where,
				'limit'=>$this->query_limit,
				'offset'=>$this->query_offset,
				'sort'=>$this->query_sort,
				'distinct'=>$this->query_distinct,
				'count'=>$this->query_count,
				'update'=>$this->query_update,
				'remove'=>$this->query_remove
			);
			//build and execute the query
			$query = new reconnectQuery($data);
			if($query)
				$this->flushQuery();
			$this->lastQuery = $query;
			return $query;
		}
		elseif(is_string($arg)){
			//execute query-string; ignores any previus collectionFunctions like ->select('foo')
			$query = new reconnectQuery(array(
				'sql'=>$arg,
				'handle'=>$this->handle,
				'driverClass'=>'reconnectDriver_'.mb_strtolower($this->dbType)
			));
			if($query)
				$this->flushQuery();
			$this->lastQuery = $query;
			return $query;
		}
		else{
			throw new Exception('Unexpected parameter for function reconnectCollection::query()');
		}
	}
}
?>