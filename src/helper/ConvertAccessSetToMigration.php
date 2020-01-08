<?php

namespace xisio\rbacgenerator\helper;

class ConvertAccessSetToMigration {
	private $access;
	private $modelName;
	public function __construct($access,$modelName){
		$this->access = $access;
		$this->modelName = $modelName;
	}	

	public function convert(){
		$charSet = str_split($this->access);	
		return $this->convertCharToName($charSet);
	}
	
	public function convertCharToName($charSet){
		$permission = [] ;
		foreach($charSet as $character ){
			$rule = [];
			$str = '';
			switch($character){
				case "R":
					$str = 'read';	
					break;
				case 'r':
					$str = 'read';	
					break;
				case 't':
					$str = 'read';
					break;
				case 'o':
					$str = 'read';
					$rule[] = [
						'name' => 'isAuthor',
						'class' => 'common\rbac\AuthorRule',
						'extend' => 'read'.ucfirst($this->modelName),
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
					$rule[] = [
						'name' => 'isAuthor',
						'class' => 'common\rbac\AuthorRule',
						'extend' => 'update'.ucfirst($this->modelName),
					];
						
					break;
				case 'X':
					$str = 'delete';
					break;
				case 'D':
					$str = 'delete';
					break;
				case 'd':
					$str = 'deleteOwn';
					$rule[] = [
						'name' => 'isAuthor',
						'class' => 'common\rbac\AuthorRule',
						'extend' => 'delete'.ucfirst($this->modelName),
					];
					break;
				default : 
					break;
			}
			$permission[] = [
				'name' => $str.ucfirst($this->modelName),
				'rule' => $rule
			];
		}
		return $permission;
	}

}
