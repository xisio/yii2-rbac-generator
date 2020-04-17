<?php

namespace xisio\rbacgenerator\helper;
use Symfony\Component\Inflector\Inflector;

class ConvertAccessSetToFilter {
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

  private function getDeletedQuery($value=0){
            return '$query->andWhere(["$this->tableName.deleted" => '.$value.'])';

  }
  private function getHiddenQuery($value=0){
            return '$query->andWhere(["$this->tableName.hidden" => '.$value.'])';

  }
  private function getStarttimeQuery(){
        return 
            '$query->andWhere([\'or\',
              ["<=","$this->tableName.starttime",$this->timestamp],
              ["is","$this->tableName.starttime",new \yii\db\Expression("NULL")],
              ["=","UNIX_TIMESTAMP($this->tableName.starttime)",0]
          ])';
  }
  private function getEndtimeQuery(){
        return 
            '$query->andWhere([\'or\',
              [">","$this->tableName.endtime",$this->timestamp],
              ["is","$this->tableName.endtime",new \yii\db\Expression("NULL")],
              ["=","UNIX_TIMESTAMP($this->tableName.endtime)",0]
          ])';
  }

	public function convertCharToPermission($charSet){
		$manipulators = [] ;
		$action = '';
		foreach($charSet as $character ){
			$rule = [];
			$filter = [];
                              /*gibt an ob der manipulator active, das bedeutet manipulieren vor update/save*/
                              $active = false;
			switch($character){
				case "R":
					break;
				case 'r':
					$str = 'read';
                                          $filter = [
                                            $this->getDeletedQuery(),
                                          ];
					$action='index';
					break;
				case 't':
                                        $str = 'read';
                                          $filter = [
                                            $this->getDeletedQuery(),
                                            $this->getHiddenQuery(),
                                            $this->getStarttimeQuery(),
                                            $this->getEndtimeQuery(),
                                          ];
					$action='index';
					break;
				case 'o':
					$str = 'readOwn';
					$action='index';
                                          $filter = [
                                            $this->getDeletedQuery(),
                                            '$query->andWhere([\'created_by\' => Yii::$app->user->id])'
                                          ];
                                        /*
					$rule[] = [
						'name' => 'isAuthor',
						'class' => '\common\rbac\AuthorRule',
						'extend' => 'read'.ucfirst($this->modelName),
					];*/
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
                                        /*
					$rule[] = [
						'name' => 'isAuthor',
						'class' => '\common\rbac\AuthorRule',
						'extend' => 'update'.ucfirst($this->modelName),
					];*/
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
                        if(count($filter)>0){
                                /*apply view action*/
                                /*
                                if(preg_match('/read/',$str)) {
                                        $manipulators[] = [
                                                'class' => $str.ucfirst($this->modelName),
                                                'action' => 'view',
                                                'filter' => $filter,
                                        ];
                                }
                                */
                                $manipulators[] = [
                                        'class' => ucfirst($this->modelName),
                                        'filter' => $filter,
                                        'action' => $action,
                                        'permission' => $str.ucfirst($this->modelName),
                                ];
        /*
                                if(in_array($action,['create','update','delete']) ){
                                        foreach($this->references as $refname) {
                                                $name = 'add-'.strtolower(Inflector::pluralize($this->modelName)). '-'.Inflector::pluralize($refname);
                                                $manipulators[] = [
                                                        'name' => $name,
                                                        'rule' => $rule,
                                                        'action' => $name,
                                                        'reference' => true,

                                                ];	
                                        }
                                }*/


                        }
		}
		return $manipulators;
	}

}
