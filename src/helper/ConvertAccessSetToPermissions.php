<?php

namespace xisio\rbacgenerator\helper;
use Symfony\Component\Inflector\Inflector;

class ConvertAccessSetToPermissions {
	private $access;
	private $modelName;
	private $references;
	public function __construct($access,$modelName,array $references=[]){
		$this->access = $access;
		$this->modelName = $modelName;
		$this->references = $references;
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
			if(preg_match('/read/',$str)) {
				$permission[] = [
					'name' => $str.ucfirst($this->modelName),
					'rule' => $rule,
					'action' => 'view',
					'reference' => false,
				];
			}
			$permission[] = [
				'name' => $str.ucfirst($this->modelName),
				'rule' => $rule,
				'action' => $action,
				'reference' => false,
			];
		}
		return $permission;
	}

}
