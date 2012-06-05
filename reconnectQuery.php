<?php
class reconnectQuery{
	private $queryData,
			$resultData,
			$affectedRows,
			$cursorPos=false;
	public function __construct($data){
		$this->queryData=$data;
		$driver=$data['driverClass'];
		require_once($driver.'.php');
		
		if(is_array($data)){
			if(is_string($data['sql'])){
				if(method_exists($driver,'query_sql')){
					$query=$driver::query_sql($data['sql'],$data['handle']);
					//fetch result
					$line=0;
					while($result=$driver::fetch_assoc($query)){
						if($result===false)
							$this->printError();
						if(count($result)&&$result!==false){
							foreach($result as $field => $val){
								$this->resultData[$line][$field]=$val;
							}
							$line++;
						}
					}
				}
				//toDo
			}
			else{
				//parse array / build query
				$query=$driver::query_array($data);
				//fetch result
				$line=0;
				while($result=$driver::fetch_assoc($query)){
					if($result===false)
						$this->printError();
					if(count($result)&&$result!==false){
						foreach($result as $field => $val){
							$this->resultData[$line][$field]=$val;
						}
						$line++;
					}
				}
			}
		}
		else{
			throw new Exception('Unexpected parameter for new class reconnectQuery::__construct( { array | string } )');
		}
	}
	public function printError($err="Unexpected Error"){
		if(isset($this->queryData['driverClass'])){
			$driver=$this->queryData['driverClass'];
			$driverErr=$driver::getLastError();
			$err=($driverErr=='')?$err:$driverErr;
		}
		throw new Exception($err);
	}
	
	public function getSql(){
		//return string
		return (isset($this->queryData['sql']))?$this->queryData['sql']:false;
	}
	public function getArray(){
		foreach($this->resultData as $key=>$val){
			$ret[]=$val;
			$ret[$key]=$val;
		}
		return $ret;
	}
	public function getAssoc(){
		return $this->resultData;
	}
	public function getAffectedRows(){}
	public function getRow($row){}
	public function getField($row,$field){}
	public function first(){
		return $this->getField(0,0);
	}
	
	/*result cursor*/
	/*move the cursor through the result entrys/rows and optionaly select the field content*/
	public function getCurrent($field=false){
		//return array or fieldcontent
	}
	public function next(){
		//move cursor forward
		return $this;
	}
	public function getNext($field=false){
		//return array or fieldcontent
	}
	public function prev(){
		//move cursor back
		return $this;
	}
	public function getPrev($field=false){
		//return array or fieldcontent
	}
	public function getFirst($field=false){
		//return array or fieldcontent
	}
	public function getEnd($field=false){
		//return array or fieldcontent
	}
	public function seek($value=0){
		//move cursor by $value
		return $this;
	}
	public function pos($value=false){
		//return int of current cursor position
	}
}