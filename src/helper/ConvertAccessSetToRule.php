<?php


namespace xisio\rbacgenerator\helper;
class ConvertAccessSetToRule {
	private $rules;
	private $defaultRules;
	private $modelName;
	private $filter = [];
	private $access = [];
	public function __construct($rules,$defaultRules,$modelName){
		$this->rules = $rules;
		$this->defaultRules = $defaultRules;
		$this->modelName = $modelName;
		
		
	}	

	public function convert(){
	
		var_dump($this->rules);	die();
		$charSet = str_split($access['rules']);	
		foreach($charSet as $character ){

		}
	}
	
	public function convertCharToFilter($character){
		$str = [] ;//'';
		$filter = [];
		switch($character){
			case "R":
				$str = 'readAll';	
				break;
			case 'r':
				$str = 'read';	
				$filter['deleted'] = [
					'condition' => 'compare',
					'value' => 0
				];
				break;
			case 't':
				$str ='readAllWithDeletedNullHiddenNullStarttimeBefore';	
				$filter['hidden'] = [
					'condition' => '==',
					'value' => 0
				];
				$filter['deleted'] = [
					'condition' => '==',
					'value' => 0
				];
				$filter['starttime'] = [
					'condition' => '<=',
					'function' => time(),
					'value' => ''
				];
				break;
			case 'o':
				$str = 'readOwnDeleted';
				$filter['created_by'] = [
					'rule' => true,
					'class' => 'AuthorRule' 
				];
				break;
			case 'C':
				$str = 'create';
				break;
			case 'U':
				$str = 'update';
				break;
			case 'u':
				$str = 'updateOwn';
				$filter[$str]['created_by'] = [
					'rule' => true,
					'class' => 'AuthorRule' 
				];
				break;
			case 'X':
				$str = 'delete';
				break;
			case 'D':
				$str = 'deleteDeleted';
				break;
			case 'd':
				$str = 'deleteOwn';
				break;
			default : 
				$filter = [];
				break;
		}
		return $filter;
	}

}
