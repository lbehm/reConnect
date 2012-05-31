<?php
class reconnectQuery{
	public function __construct($data){
		if(is_array($data)){
			
		}
		elseif(is_string($data)){
			
		}
		else{
			throw new Exception('Unexpected parameter for new class reconnectQuery::__construct( { array | string } )');
		}
	}
}