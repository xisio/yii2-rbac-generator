<?php

namespace xisio\rbacgenerator\helper;

class ConvertAccessSetToPermissions {
	private $access;
	private $modelName;
	public function __construct($access,$modelName){
		$this->access = $access;
		$this->modelName = $modelName;
	}	

	public function convert(){
		$charSet = str_split($this->access);	
		return $this->convertCharToPermission($charSet);
	}
	
	public function convertCharToPermission($charSet){
		$permission = [] ;
		$action = '';
		foreach($charSet as $character ){
			$rule = [];
			$str = '';
			switch($character){
				case "R":
					$str = 'read';	
					$action='index';
					break;
				case 'r':
					$str = 'read';	
					$action='index';
					break;
				case 't':
					$str = 'read';
					$action='index';
					break;
				case 'o':
					$str = 'readOwn';
					$action='index';
					$rule[] = [
						'name' => 'isAuthor',
						'class' => '\common\rbac\AuthorRule',
						'extend' => 'read'.ucfirst($this->modelName),
					];
					break;
				case 'C':
					$str = 'create';
					$action='create';
					break;
				case 'U':
					$str = 'update';
					$action='update';
					break;
				case 'u':
					$str = 'updateOwn';
					$action='update';
					$rule[] = [
						'name' => 'isAuthor',
						'class' => '\common\rbac\AuthorRule',
						'extend' => 'update'.ucfirst($this->modelName),
					];
						
					break;
				case 'X':
					$str = 'delete';
					$action='delete';
					break;
				case 'D':
					$str = 'delete';
					$action='delete';
					break;
				case 'd':
					$str = 'deleteOwn';
					$action='delete';
					$rule[] = [
						'name' => 'isAuthor',
						'class' => '\common\rbac\AuthorRule',
						'extend' => 'delete'.ucfirst($this->modelName),
					];
					break;
				default : 
					break;
			}
			$permission[] = [
				'name' => $str.ucfirst($this->modelName),
				'rule' => $rule,
				'action' => $action,
			];
		}
		return $permission;
	}

}
