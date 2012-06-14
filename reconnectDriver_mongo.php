<?php
require_once('reconnectDriver.php');
class reconnectDriver_mongo implements reconnectDriver{
	public static $last_link = false;
	public static $last_db = false;
	
	/*connection*/
	public static function connect($dbal,$data,&$handle){
		$handle=new Mongo("mongodb://".$data['user'].((!empty($data['pass']))?':'.$data['pass']:'')."@".$data['host'].((!empty($data['port']))?':'.$data['port']:'').((!empty($data['db']))?'/'.$data['db']:''));
		self::$last_link=$handle;
		return (!mysqli_connect_error())?true:false;
	}
	public static function selectDB($dbal,$dbName,$handle){
		if(!$handle)
			return false;
		return $handle->selectDB($dbName);
	}
	public static function close($dbal,$handle){
		if($handle){
			self::$last_link=false;
			return $handle->close();
		}
	}
	public static function set_charset($dbal,$charset,$handle){
		return true;
	}
	/*table/query*/
	public static function getCollections($data=false,$handle=false){
		if(!$handle||!is_array($data)||!isset($data['db']))
			return false;
		$result = array();
		foreach($handle->selectDB($data['db'])->listCollections() as $c){
			$result[]=$c->getName();
		}
		return $result;
	}
	public static function createCollection($data=false,$handle=false){
		if(!$handle||!is_array($data)||!isset($data['name'])||!isset($data['db']))
			return false;
		//parsing $data
		$query=array();
		foreach($data as $key=>$value){
			switch($key){
				case'name':
					$query['create']=$value;
					break;
				case'size':
					if(is_int($value))
						$query['size']=$value;
					break;
				case'capped':
					if(is_int($value))
						$query['capped']=$value;
					break;
				case'max':
					if(is_int($value))
						$query['max']=$value;
					break;
				default:
			}
		}
		return ($handle->selectDB($data['db'])->command($query))?true:false;
	}
	public static function removeCollection($data=false,$handle=false){
		if(!$handle||!is_array($data)||!isset($data['collection'])||!isset($data['db']))
			return false;
		$response=$handle->selectDB($data['db'])->selectCollection($data['collection'])->drop();
		return ($response['ok']===1)?true:false;
	}
	public static function getTypeByArray($data){
		if($data['remove']===true)
			return 'remove';
		elseif($data['update']==true)
			return 'update';
		elseif($data['replace']==true)
			return 'replace';
		elseif($data['insert']==true)
			return 'insert';
		elseif(is_array($data['field'])||($data['field']==true)||($data['count']==true)||($data['distinct']==true)||($data['where']==true)||($data['sort']==true)||($data['limit']==true))
			return 'select';
		else
			return false;
	}
	public static function query_array($data){
		if(isset($data['handle']) && isset($data['db']) && isset($data['table'])){
			self::$last_link=$data['handle'];
			switch(self::getTypeByArray($data)){
				case'remove':
					//build delete query
					$queryWhere=array();
					if($data['where']==true){
					//	$queryWhere=self::whereToSql($data['where']);
					}
					/*if($data['sort']==true){
						foreach($data['sort'] as $i=>$arr)
							foreach($arr as $key=>$direction){
								if($i)
									$queryOrder.=',';
								$queryOrder.=' `'.self::escapeKey($key).'`'.(($direction == -1)?' DESC':' ASC');
							}
					}
					if($data['limit']==true){
						$queryLimit.=intval($data['limit']);
					}
					*/
					$result = $data['handle']->selectDB($data['db'])->selectCollection($data['table'])->remove($queryWhere);
					return $result;
					break;
				case'update':
					//build update query
					$queryWhere=array();
					if($data['where']==true){
						$queryWhere=array();
						//toDo
					}
					$result = $data['handle']->selectDB($data['db'])->selectCollection($data['table'])->update($queryWhere,array('$set'=>$data['update']),array('multiple'=>true));
					/* toDO
					if(is_int($data['limit']))
						$cursor=$cursor->limit(intval($data['limit']))
					if(is_int($data['offset']))
						$cursor=$cursor->skip(intval($data['offset']))
					*/
					return $result;
					break;
				case'replace':
					//build replace into query
					return $data['handle']->selectDB($data['db'])->selectCollection($data['table'])->save($data['replace']);
					break;
				case'insert':
					//build insert into query
					return $data['handle']->selectDB($data['db'])->selectCollection($data['table'])->insert($data['insert']);
					break;
				case'select':
					//build select query
					$queryField=array();
					$queryWhere=array();
					
					if(count($data['where'])){
						//toDo: where
					}
					
					if(count($data['field_alias'])){
						//toDo: aliases for fields
					}
					if(count($data['field']))
						foreach($data['field'] as $i=>$field){
							$queryField[$field]=true;
						}
					else
						$queryField=array();
					$cursor = $data['handle']->selectDB($data['db'])->selectCollection($data['table'])->find($queryWhere,$queryField);
					
					if($data['distinct']==true){
						//toDo: distinct
					}
					if(count($data['sort'])){
						$sort=array();
						foreach($data['sort'] as $i=>$arr)
							foreach($arr as $key=>$direction){
								$sort[$key]=$direction;
							}
						$cursor=$cursor->sort($sort);
					}
					
					if(is_int($data['limit']))
						$cursor=$cursor->limit(intval($data['limit']));
					if(is_int($data['offset']))
						$cursor=$cursor->skip(intval($data['offset']));
					if($data['count']==true)
						return $cursor->count();
					
					return $cursor;
					break;
				default:
			}
		}
		throw new Exception("Unable to build query");
	}
	public static function fetch_assoc($cursor=false){
		if(!$cursor)
			return false;
		return $cursor->getNext();
	}
	public static function getLastError($handle=false){
		if(!$handle)
			$handle=self::$last_link;
		return false;
	}
	public static function affected_rows($cursor=false){
		if(is_bool($cursor))
			return false;
		return ((isset($cursor['n']))?$cursor['n']:false);
	}
	public static function escape($data,$handle=false){
		return $data;
	}
	public static function escapeKey($data,$handle=false){
		return $data;
	}
	
	/*helper*/
}
?>