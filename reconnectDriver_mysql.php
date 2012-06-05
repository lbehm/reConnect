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
		self::$last_link=$handle;
		return @mysql_set_charset($charset, $handle);
	}
	public function query_sql($query,$handle=false){
		if(!$handle)
			return false;
		self::$last_link=$handle;
		return @mysql_query($query,$handle);

	}
	public function query_array($data){
		if(isset($data['handle']) && isset($data['db']) && isset($data['table'])){
			self::$last_link=$data['handle'];
			
			if($data['remove']===true){
				//build delete query
				$query='';
				$queryWhere='';
				$queryOrder='';
				$queryLimit='';
				
				if($data['where']==true){
					$queryWhere=$this->whereToSql($data['where']);
				}
				if($data['sort']==true){}
				if($data['limit']==true){}
				$query='DELETE FROM `'.$data['db'].'`.`'.$data['table'].'`'.
					(($queryWhere!='')?' WHERE '.$queryWhere:'').
					(($queryOrder!='')?' ORDER BY '.$queryOrder:'').
					(($$queryLimit!='')?' LIMIT '.$queryLimit:'').
					';';
				return @mysql_query($query,$data['handle']);
			}
			elseif($data['update']==true){}
			elseif($data['insert']==true){}
			elseif($data['field']==true){
				//build select query
				$query='';
				$queryDistinct='';
				$queryField='';
				$queryWhere='';
				$queryOrder='';
				$queryLimit='';
				
				if($data['count']==true){
					$queryField=' COUNT(*)';
				}
				elseif($data['field_alias']==true){
					foreach($data['field'] as $i=>$field){
						if($i)
							$queryField.=',';
						$queryField.=' `'.$field.'`';
						if(isset($data['field_alias'][$field]))
							$queryField.=' AS `'.$data['field_alias'][$field].'`';
					}
				}
				else{
					foreach($data['field'] as $i=>$field){
						if($i)
							$queryField.=',';
						$queryField.=' `'.$field.'`';
					}
				}
				
				if($data['distinct']==true){
					$queryDistinct=' DISTINCT';
				}
				
				if($data['where']==true){
					$tmp=self::whereToSql($data['where']);
					$queryWhere=(isset($tmp)&&$tmp!='')?' WHERE'.$tmp:'';
					unset($tmp);
				}
				
				if($data['sort']==true){
					foreach($data['sort'] as $i=>$arr)
						foreach($arr as $key=>$direction){
							if($i)
								$queryOrder.=',';
							$queryOrder.=' `'.$key.'`'.(($direction == -1)?' DESC':' ASC');
						}
					$queryOrder=($queryOrder!='')?' ORDER BY'.$queryOrder:'';
				}
				if($data['limit']==true){
					$queryLimit.=" LIMIT ".$data['limit'];
					if($data['offset']==true){
						$queryLimit.=" OFFSET ".$data['offset'];
					}
				}
				$query="SELECT".
					$queryDistinct.
					$queryField.
					' FROM `'.$data['db'].'`.`'.$data['table'].'`'.
					$queryWhere.
					$queryOrder.
					$queryLimit.';';
				return self::query_sql($query,$data['handle']);
			}
		}
		throw new Exception("Unable to build query");
	}
	public function fetch_assoc($resource){
		if(!$resource)
			return false;
		return @mysql_fetch_assoc($resource);
	}
	public function getLastError($handle=false){
		if(!$handle)
			$handle=self::$last_link;
		return @mysql_error($handle);
	}
	
	/*helper*/
	public function whereToSql($array,$glue=' AND'){
		/* deal with it:
			->where(array(
				"col"=>5,
				array('%or'=>array(
					"col"=>array('%ne'=>5),
					"id"=>array(
						'%lt'=>50,
						'%gt'=>25
					)
				))
			))
			SQL: ...WHERE `col`= 5 AND ( `col` <> 5 OR (`id` < 50 AND  `id` > 25))
		*/
		$ret='';
		$i=0;
		foreach($array as $str=>$val){
			//must we merge some arrays?
			if($i)
				$ret.=$glue;
			switch($str){
				case'%and':
					$ret.=' ('.self::whereToSql($val,' AND').' )';
					break;
				case'%or':
					$ret.=' ('.self::whereToSql($val,' OR').' )';
					break;
				default:
					if(is_int($val)){
						//'x' = 12345
						$ret.=' '.$str.' = '.$val;
					}
					elseif(is_string($val)){
						//'x' = 'foo'
						//toDo Escape $val
						$ret.=' '.$str." = '".$val."'";
					}
					elseif(is_array($val)){
						//'x' = array('%ne'=>'3')
						$j=0;
						foreach($val as $s=>$v){
							if($j)
								$ret.=' AND';
							if(is_string($s)){
								switch($s){
									case '%and':
									case '%or':
										//'x' = array('%or'=>array(5,'%gt'=>6))
										//(x = 5 OR x > 6)
										$ret.=' (';
										$k=0;
										foreach($v as $k1=>$v1){
											if($k)
												$ret.=(($s=='%or')?' OR':' AND');
											if(is_string($k1))
												switch($k1){
													case'%ne':
														$ret.=' '.$str.'  <> '.((is_int($v1))?$v1:"'".$v1."'");
														break;
													case'%lt':
														$ret.=' '.$str.' < '.((is_int($v1))?$v1:"'".$v1."'");
														break;
													case'%gt':
														$ret.=' '.$str.' > '.((is_int($v1))?$v1:"'".$v1."'");
														break;
													case'%lte':
														$ret.=' '.$str.' <= '.((is_int($v1))?$v1:"'".$v1."'");
														break;
													case'%gte':
														$ret.=' '.$str.' >= '.((is_int($v1))?$v1:"'".$v1."'");
														break;
													case'%match':
														$ret.=' '.$str." REGEXP '".str_replace('\\','\\\\',$v1)."'";
														break;
													case'%notmatch':
														$ret.=' '.$str." NOT REGEXP '".str_replace('\\','\\\\',$v1)."'";
														break;
													default:
														$ret.=' '.$str.' = '.((is_int($v1))?$v1:"'".$v1."'");
												}
											elseif(is_int($k1))
												$ret.=' '.$str.' = '.((is_int($v1))?$v1:"'".$v1."'");
											$k++;
										}
										$ret.=' )';
										break;
									case '%ne':
										$ret.=' '.$str.'  <> '.((is_int($v))?$v:"'".$v."'");
										break;
									case '%lt':
										$ret.=' '.$str.' < '.((is_int($v))?$v:"'".$v."'");
										break;
									case '%gt':
										$ret.=' '.$str.' > '.((is_int($v))?$v:"'".$v."'");
										break;
									case '%lte':
										$ret.=' '.$str.' <= '.((is_int($v))?$v:"'".$v."'");
										break;
									case '%gte':
										$ret.=' '.$str.' >= '.((is_int($v))?$v:"'".$v."'");
										break;
									case '%match':
										$ret.=' '.$str." REGEXP '".str_replace('\\','\\\\',$v)."'";
										break;
									case '%notmatch':
										$ret.=' '.$str." NOT REGEXP '".str_replace('\\','\\\\',$v)."'";
										break;
									default :
										$ret.=' '.$str.' = '."'".$s."'";
								}
							}
							elseif(is_int($s)){
								$ret.=' '.$str.' = '.((is_int($v))?$v:"'".$v."'");
							}
							$j++;
						}
					}
			}
			$i++;
		}
		return $ret;
	}
	
}
?>