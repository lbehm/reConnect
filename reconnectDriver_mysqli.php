<?php
require_once('reconnectDriver.php');
class reconnectDriver_mysqli implements reconnectDriver{
	public static $last_link = false;
	public static $last_db = false;
	
	/*connection*/
	public static function connect($dbal,$data,&$handle){
		$handle=mysqli_connect($data['host'],$data['user'],$data['pass'],null,$data['port']);
		self::$last_link=$handle;
		return (!mysqli_connect_error())?true:false;
	}
	public static function selectDB($dbal,$dbName,$handle){
		if(!$handle)
			return false;
		$r = mysqli_select_db($handle,$dbName);
		if($r)
			self::$last_db = $dbName;
		return $r;
	}
	public static function close($dbal,$handle){
		if($handle){
			self::$last_link=false;
			return mysqli_close($handle);
		}
	}
	public static function set_charset($dbal,$charset,$handle){
		if(!$handle)
			return false;
		self::$last_link=$handle;
		return mysqli_set_charset($handle,$charset);
	}
	/*table/query*/
	public static function getTables($data=false,$handle=false){
		if(!$handle||!is_array($data)||!isset($data['db']))
			return false;
		$query=self::query_sql("SHOW TABLES FROM `".self::escapeKey($data['db'])."`;",$handle);
		$return=array();
		while($re=self::fetch_assoc($query)){
			$return[]=$re['Tables_in_test'];
		}
		return $return;
	}
	public static function createCollection($data=false,$handle=false){
		if(!$handle||!is_array($data))
			return false;
		$dataTemplate=array('copy'=>false,'temporaray'=>false,'quiet'=>false,'primary'=>false,'options'=>array('autoincrement'=>false,'type'=>'myisam','row_format'=>'compact','charset'=>'utf8','collate'=>'utf8_bin','comment'=>'','min_rows'=>false,'max_rows'=>false,'avg_row_length'=>false,'checksum'=>0,'PACK_KEYS'=>false,'delay_key_write'=>false,));
		$data=array_merge($dataTemplate,$data);
		//parsing $data
		$query=array();
		foreach($data as $key=>$value){
			switch($key){
				case'name':
					$query['tbl_name']=$value;
					break;
				case'copy':
					if($value!==false)
						$query['like']=$value;
					break;
				case'fields':
					if(isset($value)&&is_array($value)){
						//do some awesome stuff here
						$i=0;
						foreach($value as $fieldname=>$arr){
							$query['fields'][$i]['name']=self::escapeKey($fieldname);
							foreach($arr as $k=>$v){
								switch($k){
									case'type':
										if(is_string($v))
											$query['fields'][$i]['type']=self::escapeKey($v);
										break;
									case'length':
										if(is_int($v))
											$query['fields'][$i]['length']=$v;
										break;
									case'decimals':
										if(is_int($v))
											$query['fields'][$i]['decimals']=$v;
										break;
									case'unsigned':
										if($v===true)
											$query['fields'][$i]['unsigned']=' UNSIGNED';
										break;
									case'zerofill':
										if($v===true)
											$query['fields'][$i]['zerofill']=' ZEROFILL';
										break;
									case'mode':
										if(is_string($v) && (mb_strtolower($v)=='unicode' || mb_strtolower($v)=='ascii' || mb_strtolower($v)=='binary'))
											$query['fields'][$i]['mode']=mb_strtoupper($v);
										break;
									case'data':
										if(is_array($v)){
											foreach($v as $enumopt){
												if(isset($query['fields'][$i]['data']))
													$query['fields'][$i]['data'].=((is_int($enumopt))?', '.$enumopt:", '".self::escape($enumopt)."'");
												else
													$query['fields'][$i]['data'].=((is_int($enumopt))?' '.$enumopt:" '".self::escape($enumopt)."'");
											}
										}
										break;
									case'null':
										if(is_int($v)&&($v===-1 || $v===1))
											$query['fields'][$i]['null']=(($v===1)?' NULL':' NOT NULL');
										break;
									case'default':
										if(is_string($v) || is_int($v) || is_float($v))
											$query['fields'][$i]['default']=' DEFAULT '.((is_int($v))?$v:"'".self::escape($v)."'");
										break;
									case'auto_increment':
										if($v===true)
											$query['fields'][$i]['auto_increment']=' AUTO_INCREMENT';
										break;
									case'unique':
										if($v===true)
											$query['fields'][$i]['unique']=' UNIQUE';
										break;
									case'primary':
										if($v===true){
											$query['primaray_arr'][$fieldname]=$fieldname;
										}
										break;
									case'comment':
										if(is_string($v))
											$query['fields'][$i]['comment']=" COMMENT '".self::escape($v)."'";
										break;
								}
							}
							$i++;
						}
						$query['field']='';
						foreach($query['fields'] as $id=>$field){
							if(!empty($query['field'])){
								$query['field'].=',';
							}
							$query['field'].=" `".$field['name']."`";
							if(isset($field['type'])){
								$query['field'].=" ".$field['type'];
								if(isset($field['length'])){
									$query['field'].="(".$field['length'];
									if(isset($field['decimals']))
										$query['field'].=','.$field['decimals'];
									$query['field'].=")";
								}
							}
							$query['field'].=((isset($field['unsigned']))?$field['unsigned']:'');
							$query['field'].=((isset($field['zerofill']))?$field['zerofill']:'');
							$query['field'].=((isset($field['mode']))?$field['mode']:'');
							$query['field'].=((isset($field['data']))?$field['data']:'');
							$query['field'].=((isset($field['null']))?$field['null']:'');
							$query['field'].=((isset($field['default']))?$field['default']:'');
							$query['field'].=((isset($field['auto_increment']))?$field['auto_increment']:'');
							$query['field'].=((isset($field['unique']))?$field['unique']:'');
							$query['field'].=((isset($field['comment']))?$field['comment']:'');
						}
					}
					break;
				case'temporary':
					if($value===true)
						$query['temporary']=' TEMPORARY';
					break;
				case'quiet':
					if($value===true)
						$query['quiet']=' IF NOT EXISTS';
					break;
				case'primary':
					if($value!==false){
						if(is_string($value))
							$query['primaray_arr'][$value]=$value;
						elseif(is_array($value)){
							foreach($value as $v)
								$query['primaray_arr'][$v]=$v;
						}
					}
					break;
				case'options':
					$query['table_options']='';
					foreach($value as $k=>$v){
						switch($k){
							case'autoincrement':
								//$v: int | false
								if($v!==false && is_int($v)){
									$query['table_options'].=' AUTO_INCREMENT = '.intval(self::escape($v));
								}
								break;
							case'type':
								//$v: string ( myisam | innodb | memory | ... )
								if(is_string($v)){
									$query['table_options'].=' ENGINE = '.self::escape($v);
								}
								break;
							case'row_format':
								//$v:	( DEFAULT | DYNAMIC | FIXED | COMPRESSED | REDUNDANT | COMPACT)
								$t=array('DEFAULT','DYNAMIC','FIXED','COMPRESSED','REDUNDANT','COMPACT');
								if(is_string($v)&&in_array(mb_strtoupper($v),$t)){
									$query['table_options'].=' ROW_FORMAT = '.self::escape(mb_strtoupper($v));
								}
								unset($t);
								break;
							case'charset':
								if(is_string($v)){
									$query['table_options'].=' DEFAULT CHARACTER SET '.self::escape($v);
									if(isset($value['collate']) && is_string($value['collate']))
										$query['table_options'].=' COLLATE '.self::escape($value['collate']);
								}
								break;
							case'comment':
								if(is_string($v)){
									$query['table_options'].=" COMMENT = '".self::escape($v)."'";
								}
								break;
							case'min_rows':
								//$v: int | false
								if($v!==false && is_int($v)){
									$query['table_options'].=' MIN_ROWS = '.intval($v);
								}
								break;
							case'max_rows':
								//$v: int | false
								if($v!==false && is_int($v)){
									$query['table_options'].=' MAX_ROWS = '.intval($v);
								}
								break;
							case'avg_row_length':
								//$v: int | false
								if($v!==false && is_int($v)){
									$query['table_options'].=' AVG_ROW_LENGTH = '.intval($v);
								}
								break;
							case'checksum':
								//$v: true | false; default: false
								if((mb_strtolower($value['type'])=='myisam')&&is_bool($v)){
									$query['table_options'].=' CHECKSUM = '.(($v)?1:0);
								}
								break;
							case'pack_keys':
								//$v: ( 0 | 1 | false) => ( off | on | default )
								if(mb_strtolower($value['type'])=='myisam'){
									if($v===false)
										$query['table_options'].=' PACK_KEYS = DEFAULT';
									elseif(is_int($v))
										$query['table_options'].=' PACK_KEYS = '.intval($v);
								}
								break;
							case'delay_key_write':
								//$v: true | false; default: false
								if((mb_strtolower($value['type'])=='myisam')&&is_bool($v)){
									$query['table_options'].=' DELAY_KEY_WRITE = '.(($v)?1:0);
								}
								break;
						}
					}
					break;
				default:
			}
		}
		if(isset($query['primaray_arr'])){
			$query['primaray']='';
			foreach($query['primaray_arr'] as $str){
				$query['primaray'].=((!empty($query['primaray']))?', ':'').'`'.self::escapeKey($str).'`';
			}
		}
		
		//build $query['sql']
		$query['sql'] ='CREATE';
		$query['sql'].=((isset($query['temporary']))?$query['temporary']:'');
		$query['sql'].=' TABLE';
		$query['sql'].=((isset($query['quiet']))?$query['quiet']:'');
		$query['sql'].=' `'.self::escapeKey($data['db']).'`.`'.self::escapeKey($query['tbl_name']).'`';
		if(isset($query['like'])&&is_string($query['like'])){
			$query['sql'].=' LIKE `'.self::escapeKey($query['like']).'`';
		}
		else{
			$query['sql'].=' (';
			$query['sql'].=$query['field'];
			$query['sql'].=((isset($query['primaray']) && !empty($query['primaray']))?', PRIMARY KEY ('.$query['primaray'].')':'');
			$query['sql'].=' )';
			if(isset($query['table_options']) && !empty($query['table_options']))
				$query['sql'].=$query['table_options'];
		}
		$query['sql'].=';';
		return self::query_sql($query['sql'],$handle);
	}
	public static function removeCollection($data=false,$handle=false){
		if(!$handle||!is_array($data)||!isset($data['collection'])||!isset($data['db']))
			return false;
		$query="DROP".((isset($data['temporary'])&&$data['temporary']===true)?' TEMPORARY':'')." TABLE `".self::escapeKey($data['db'])."`.`".self::escapeKey($data['collection'])."`;";
		return self::query_sql($query,$handle);
	}
	public static function query_sql($query,$handle=false){
		if(!$handle)
			return false;
		self::$last_link=$handle;
		return mysqli_query($handle,$query);
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
					$query='';
					$queryWhere='';
					$queryOrder='';
					$queryLimit='';
					
					if($data['where']==true){
						$queryWhere=self::whereToSql($data['where']);
					}
					if($data['sort']==true){
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
					$query='DELETE FROM `'.self::escapeKey($data['db']).'`.`'.self::escapeKey($data['table']).'`'.
						(($queryWhere!='')?' WHERE '.$queryWhere:'').
						(($queryOrder!='')?' ORDER BY '.$queryOrder:'').
						(($queryLimit!='')?' LIMIT '.$queryLimit:'').
						';';
					return self::query_sql($query,$data['handle']);
					break;
				case'update':
					//build update query
					$query = $querySet = $queryWhere = $queryLimit='';
					$i=0;
					foreach($data['update'] as $field=>$value){
						//toDo escape
						$querySet.=(($i)?',':'').' `'.self::escapeKey($field).'` = '.((is_int($value))?$value:"'".self::escape($value)."'");
						$i++;
					}
					if($data['where']==true){
						$queryWhere=self::whereToSql($data['where']);
						if($queryWhere!='')
							$queryWhere=' WHERE'.$queryWhere;
					}
					if($data['limit']==true){
						$queryLimit.=" LIMIT ".intval($data['limit']);
						if($data['offset']==true){
							$queryLimit.=" OFFSET ".intval($data['offset']);
						}
					}
					$query='UPDATE `'.self::escapeKey($data['db']).'`.`'.self::escapeKey($data['table']).'` SET '.$querySet.$queryWhere.$queryLimit.';';
					return self::query_sql($query,$data['handle']);
					break;
				case'replace':
					//build replace into query
					$query = $queryField = $queryValue='';
					$i=0;
					foreach($data['replace'] as $field=>$value){
						$queryField.=(($i)?', ':' ')." `".self::escapeKey($field)."`";
						$queryValue.=(($i)?', ':' ').((is_int($value))?$value:"'".self::escape($value)."'");
						$i++;
					}
					$query='REPLACE INTO `'.self::escapeKey($data['db']).'`.`'.self::escapeKey($data['table']).'` ('.$queryField.' ) VALUES('.$queryValue.' );';
					return self::query_sql($query,$data['handle']);
					break;
				case'insert':
					//build insert into query
					$query = $queryField = $queryValue='';
					$i=0;
					foreach($data['insert'] as $field=>$value){
						$queryField.=(($i)?', ':' ')." `".self::escapeKey($field)."`";
						$queryValue.=(($i)?', ':' ').((is_int($value))?$value:"'".self::escape($value)."'");
						$i++;
					}
					$query='INSERT INTO `'.self::escapeKey($data['db']).'`.`'.self::escapeKey($data['table']).'` ('.$queryField.' ) VALUES('.$queryValue.' );';
					return self::query_sql($query,$data['handle']);
					break;
				case'select':
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
							$queryField.=' `'.self::escapeKey($field).'`';
							if(isset($data['field_alias'][$field]))
								$queryField.=' AS `'.self::escapeKey($data['field_alias'][$field]).'`';
						}
					}
					else{
						if(count($data['field']))
							foreach($data['field'] as $i=>$field){
								if($i)
									$queryField.=',';
								$queryField.=' `'.self::escapeKey($field).'`';
							}
						else
							$queryField=' *';
					}
					
					if($data['distinct']==true){
						$queryDistinct=' DISTINCT';
					}
					
					if($data['where']==true){
						$queryWhere=self::whereToSql($data['where']);
						$queryWhere=(isset($queryWhere)&&$queryWhere!='')?' WHERE'.$queryWhere:'';
					}
					
					if($data['sort']==true){
						foreach($data['sort'] as $i=>$arr)
							foreach($arr as $key=>$direction){
								if($i)
									$queryOrder.=',';
								$queryOrder.=' `'.self::escapeKey($key).'`'.(($direction == -1)?' DESC':' ASC');
							}
						$queryOrder=($queryOrder!='')?' ORDER BY'.$queryOrder:'';
					}
					if($data['limit']==true){
						$queryLimit=" LIMIT ".intval($data['limit']);
						if($data['offset']==true){
							$queryLimit.=" OFFSET ".intval($data['offset']);
						}
					}
					$query="SELECT".
						$queryDistinct.
						$queryField.
						' FROM `'.self::escapeKey($data['db']).'`.`'.self::escapeKey($data['table']).'`'.
						$queryWhere.
						$queryOrder.
						$queryLimit.';';
					return self::query_sql($query,$data['handle']);
					break;
				default:
			}
		}
		throw new Exception("Unable to build query");
	}
	public static function fetch_assoc($resource=false){
		if(!$resource)
			return false;
		return mysqli_fetch_assoc($resource);
	}
	public static function getLastError($handle=false){
		if(!$handle)
			$handle=self::$last_link;
		return mysqli_error($handle);
	}
	public static function affected_rows($resource=false){
		if(is_bool($resource))
			$resource=self::$last_link;
		return mysqli_affected_rows($resource);
	}
	public static function escape($data,$handle=false){
		if(!$handle)
			$handle=self::$last_link;
		if(is_array($data)){
			foreach($data as $key=>&$value){
				$value=self::escape($value,$handle);
			}
			return $data;
		}
		elseif(is_string($data) || is_numeric($data)){
			return mysqli_real_escape_string($handle,$data);
		}
		else
			return false;
	}
	public static function escapeKey($data,$handle=false){
		if(is_array($data)){
			foreach($data as $key=>&$value){
				$value=self::escapeKey($value);
			}
			return $data;
		}
		elseif(is_string($data) || is_numeric($data)){
			return str_replace(array('`'),array('``'),$data);
		}
		else
			return false;
	}
	
	/*helper*/
	public static function whereToSql($array,$glue=' AND'){
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
						$ret.=' '.$str." = '".self::escape($val)."'";
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
														$ret.=' '.$str.'  <> '.((is_int($v1))?$v1:"'".self::escape($v1)."'");
														break;
													case'%lt':
														$ret.=' '.$str.' < '.((is_int($v1))?$v1:"'".self::escape($v1)."'");
														break;
													case'%gt':
														$ret.=' '.$str.' > '.((is_int($v1))?$v1:"'".self::escape($v1)."'");
														break;
													case'%lte':
														$ret.=' '.$str.' <= '.((is_int($v1))?$v1:"'".self::escape($v1)."'");
														break;
													case'%gte':
														$ret.=' '.$str.' >= '.((is_int($v1))?$v1:"'".self::escape($v1)."'");
														break;
													case'%match':
														$ret.=' '.$str." REGEXP '".str_replace('\\','\\\\',self::escape($v1))."'";
														break;
													case'%notmatch':
														$ret.=' '.$str." NOT REGEXP '".str_replace('\\','\\\\',self::escape($v1))."'";
														break;
													default:
														$ret.=' '.$str.' = '.((is_int($v1))?$v1:"'".self::escape($v1)."'");
												}
											elseif(is_int($k1))
												$ret.=' '.$str.' = '.((is_int($v1))?$v1:"'".self::escape($v1)."'");
											$k++;
										}
										$ret.=' )';
										break;
									case '%ne':
										$ret.=' '.$str.'  <> '.((is_int($v))?$v:"'".self::escape($v)."'");
										break;
									case '%lt':
										$ret.=' '.$str.' < '.((is_int($v))?$v:"'".self::escape($v)."'");
										break;
									case '%gt':
										$ret.=' '.$str.' > '.((is_int($v))?$v:"'".self::escape($v)."'");
										break;
									case '%lte':
										$ret.=' '.$str.' <= '.((is_int($v))?$v:"'".self::escape($v)."'");
										break;
									case '%gte':
										$ret.=' '.$str.' >= '.((is_int($v))?$v:"'".self::escape($v)."'");
										break;
									case '%match':
										$ret.=' '.$str." REGEXP '".str_replace('\\','\\\\',self::escape($v))."'";
										break;
									case '%notmatch':
										$ret.=' '.$str." NOT REGEXP '".str_replace('\\','\\\\',self::escape($v))."'";
										break;
									default :
										$ret.=' '.$str." = '".self::escape($s)."'";
								}
							}
							elseif(is_int($s)){
								$ret.=' '.$str.' = '.((is_int($v))?$v:"'".self::escape($v)."'");
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