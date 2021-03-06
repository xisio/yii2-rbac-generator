<?php

/**
 * @copyright Copyright (c) 2018 Carsten Brandt <mail@cebe.cc> and contributors
 * @license https://github.com/cebe/yii2-openapi/blob/master/LICENSE
 */

namespace xisio\rbacgenerator\generator;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RegexIterator;
use Yii;
use yii\gii\CodeFile;
use yii\gii\Generator;
use yii\helpers\FileHelper;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\helpers\StringHelper;

use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

use xisio\rbacgenerator\helper\ConvertAccessSetToRule;
use xisio\rbacgenerator\helper\ConvertAccessSetToPermissions;
use xisio\rbacgenerator\helper\ConvertAccessSetToFilter;


class RbacGenerator extends Generator
{
	/**
	 * @var string path to the OpenAPI specification file. This can be an absolute path or a Yii path alias.
	 */
	public $rbacYamlPath;


	public $accessClassPath = '@app/controller';
	/**
	 * @var bool whether to generate Access from the yaml.
	 */
	public $generateAccessClass = true;
	/**
	 * @var bool whether to generate Migrations rbac from the yaml.
	 */
	public $generateRbac = true;

	/**
	 * @var bool namespace to create models in. This must be resolvable via Yii alias.
	 * Defaults to `app\models`.
	 */
	public $accessClassNamespace = 'app\\models';
	/**
	 * @var array List of accessclasses to exclude.
	 */
	public $excludeClasses = [];

	/**
	 * @var bool whether to generate database migrations.
	 */
	public $generateRbacMigrations = true;
	/**
	 * @var string path to create migration files in.
	 * Defaults to `@app/migrations`.
	 */
	public $migrationPath = '@app/migrations';
	/**
	 * @var string namespace to create migrations in.
	 * Defaults to `null` which means that migrations are generated without namespace.
	 */
	public $migrationNamespace = 'app/migrations';

	public $generateFilters = true;
	public $filterNamespace = 'app/models/filters';

	private $permissions = [];
	private $filters = [];


	/**
	 * @return string name of the code generator
	 */
	public function getName()
	{
		return 'RBAC Generator';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDescription()
	{
		return 'This generator generates Rbac Rules depend on a yaml file';
	}

	/**
	 * {@inheritdoc}
	 */
	public function rules()
	{
		return array_merge(parent::rules(), [
				[['rbacYamlPath'], 'filter', 'filter' => 'trim'],

				[['accessClassNamespace','migrationNamespace'], 'default', 'value' => null],

				[['generateRbac','generateAccessClass','generateRbacMigrations','generateFilters'], 'boolean'],

				['rbacYamlPath', 'required'],
				['rbacYamlPath', 'validateYaml'],

				[['accessClassNamespace'], 'required', 'when' => function (RbacGenerator $rbac) {
					return (bool) $rbac->generateRbac;
				}],
				[['migrationPath'], 'required', 'when' => function (RbacGenerator $rbac) {
					return (bool) $rbac->generateRbacMigrations;
				}],
				[['accessClassPath'], 'required', 'when' => function (RbacGenerator $rbac) {
					return (bool) $rbac->generateAccessClass;
				}],

		]);
	}

	public function validateYaml($attribute)
	{
		//if ($this->ignoreSpecErrors) {
		//    return;
		//}
		/* TODO apply yaml try catch*/
		//    $this->addError($attribute, 'Failed to validate Yaml :' . Html::ul($getErrors()));
	}

	/**
	 * {@inheritdoc}
	 */
	public function attributeLabels()
	{
		return array_merge(parent::attributeLabels(), [
				'rbacYamlPath' => 'Rbac Yaml',
				'generateCheckClass' => '',
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function hints()
	{
		return array_merge(parent::hints(), [
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function autoCompleteData()
	{
		$vendor = Yii::getAlias('@vendor');
		$app = Yii::getAlias('@app');
		$runtime = Yii::getAlias('@runtime');
		$paths = [];
		$pathIterator = new RecursiveDirectoryIterator($app);
		$recursiveIterator = new RecursiveIteratorIterator($pathIterator);
		$files = new RegexIterator($recursiveIterator, '~.+\.(json|yaml|yml)$~i', RegexIterator::GET_MATCH);
		foreach ($files as $file) {
			if (strpos($file[0], $vendor) === 0) {
				$file = '@vendor' . substr($file[0], strlen($vendor));
				if (DIRECTORY_SEPARATOR === '\\') {
					$file = str_replace('\\', '/', $file);
				}
			} elseif (strpos($file[0], $runtime) === 0) {
				$file = null;
			} elseif (strpos($file[0], $app) === 0) {
				$file = '@app' . substr($file[0], strlen($app));
				if (DIRECTORY_SEPARATOR === '\\') {
					$file = str_replace('\\', '/', $file);
				}
			} else {
				$file = $file[0];
			}

			if ($file !== null) {
				$paths[] = $file;
			}
		}

		$namespaces = array_map(function ($alias) {
				$path = Yii::getAlias($alias, false);
				if (in_array($alias, ['@web', '@runtime', '@vendor', '@bower', '@npm'])) {
				return [];
				}
				if (!file_exists($path)) {
				return [];
				}
				try {
				return array_map(function ($dir) use ($path, $alias) {
						return str_replace('/', '\\', substr($alias, 1) . substr($dir, strlen($path)));
						}, FileHelper::findDirectories($path, ['except' => [
							'vendor/',
							'runtime/',
							'assets/',
							'.git/',
							'.svn/',
						]]));
				} catch (\Throwable $e) {
				// ignore errors with file permissions
				Yii::error($e);
				return [];
				}
		}, array_keys(Yii::$aliases));
		$namespaces = array_merge(...$namespaces);

		return [
			'rbacYamlPath' => $paths,
			'rulesNamespace' => $namespaces,
		];
	}

	/**
	 * {@inheritdoc}
	 */
	public function requiredTemplates()
	{
		$required = [];
		if ($this->generateRbacMigrations) {
			$required[] = 'migration.php';
		}
		if ($this->generateRbac) {
			$required[] = 'rbac.php';
		}
		return $required;
	}

	/**
	 * {@inheritdoc}
	 */
	public function stickyAttributes()
	{
		return array_merge(parent::stickyAttributes(), ['rbacYamlPath', 'generateRbacMigrations', 'generateAccessClass']);
	}



	/**
	 * @var Yaml Content
	 */
	private $_yaml;


	/**
	 * @return OpenApi
	 */
	protected function readYaml($filename)
	{
		try {
			$fileContent = file_get_contents($filename);
			$this->_yaml = Yaml::parse($fileContent);
		} catch (ParseException $exception) {
			printf('Unable to parse the YAML string: %s', $exception->getMessage());
		}
		return $this->_yaml;
	}

	private function createPermissions(){
                if(count($this->permissions)>0){
                        return ;
                }
                $defaultRules =  $this->_yaml['default'] ?? [];
                foreach($this->_yaml['rules'] as $rule){
                        $localRules= [] ; //$defaultRules;
                        $ruleRoles = [];
                        if(empty($rule['access'])){
                                if(empty($defaultRules)){
                                        die('No defaultRules specified. Cannot create access for '.$rules['class']);
                                }
                                $rule['access'] = $defaultRules;
                        }
                        foreach($rule['access'] as $role=>$access){
                                $localRules[$role] = '';
                                $this->permissions[] = $this->convertAccessToPermission($role,$access,$rule);
                        }
                        $rules = array_diff_key($defaultRules,$localRules);
                        if(count($rules)>0){
                                foreach($rules as $role=>$access){
                                        $this->permissions[] = $this->convertAccessToPermission($role,$access,$rule);
                                }
                        }
                }
                /*Create Default Rules*/
                if(count($defaultRules)> 0){
                        $tempRule = [
                                'class' => 'default',
                                'access' => $defaultRules,
                        ];	
                        foreach($tempRule['access'] as $role=>$access){
                                $this->permissions[] = $this->convertAccessToPermission($role,$access,$tempRule);
                        }

                }

	}
        private function createFilters(){
                if(count($this->filters)>0){
                        return ;
                }
                $defaultRules =  $this->_yaml['default'] ?? [];
                foreach($this->_yaml['rules'] as $rule){
                        $localRules= [] ; //$defaultRules;
                        $ruleRoles = [];
                        if(empty($rule['access'])){
                                if(empty($defaultRules)){
                                        die('No defaultRules specified. Cannot create access for '.$rules['class']);
                                }
                                $rule['access'] = $defaultRules;
                        }
                        foreach($rule['access'] as $role=>$access){
                                $localRules[$role] = '';
                                $filter = $this->convertAccessToFilter($role,$access,$rule);
                                $this->filters = array_merge($this->filters,$filter);
                        }
                        $rules = array_diff_key($defaultRules,$localRules);
                        if(count($rules)>0){
                                foreach($rules as $role=>$access){
                                    $filter = $this->convertAccessToFilter($role,$access,$rule);
                                    $this->filters = array_merge($this->filters,$filter);
                                }
                        }
                }
                /*Create Default Rules*/
                /*
                  if(count($defaultRules)> 0){
                          $tempRule = [
                                  'class' => 'default',
                                  'access' => $defaultRules,
                          ];	
                          foreach($tempRule['access'] as $role=>$access){
                                  $this->filters[] = $this->convertAccessToFilter($role,$access,$tempRule);
                          }

                  }
                */
                  
       }
	private function convertAccessToFilter($role,$access,$rule) {
				$references = $rule['references'] ?? [];
				$filterConverter = new ConvertAccessSetToFilter($access,$rule['class'],$references);
				$filters = $filterConverter->convert();
        /* hier kann auch ein array von arrays übergeben werden*/
        $filterList = [];
        foreach($filters as $filter){
          $className = $filter['class'];
          $permission = $filter['permission'];
          unset($filter['class']);
          unset($filter['permission']);

          if(!isset($filterList[$className ] )) {
            $filterList[$className] = [];
          }
          $filter['role'] = $role;
          $filterList[$className][$permission] = $filter;
        }
        return $filterList;
	}

	private function convertAccessToPermission($role,$access,$rule) {
				$references = $rule['references'] ?? [];
				$permissionConverter = new ConvertAccessSetToPermissions($access,$rule['class'],$references);
				$permission = $permissionConverter->convert();
				return [
					'role' => $role,
					'access' => $permission,
					'class' => $rule['class'],
				];
	}


	/**
	 * Generates the code based on the current user input and the specified code template files.
	 * This is the main method that child classes should implement.
	 * Please refer to [[\yii\gii\generators\controller\Generator::generate()]] as an example
	 * on how to implement this method.
	 * @return CodeFile[] a list of code files to be created.
	 */
	public function generate()
	{
		$files = [];
		$this->readYaml($this->rbacYamlPath);

		if ($this->generateRbacMigrations) {
			$this->createPermissions(); 
			$className = 'm'.date('ymd_000000').'_rbac';
			$files[] = new CodeFile(
				Yii::getAlias("$this->migrationPath/$className.php"),
				$this->render('migration.php', [
					'permissions' => $this->permissions,
					'namespaceName' => $this->migrationNamespace ,
					'className' => $className,
				])
			);
		}

		if($this->generateAccessClass) {
			$this->createPermissions(); 
			$actions = [];
			foreach($this->permissions as $permission){
				$className = $permission['class'];
				foreach($permission['access'] as $access) {
					$actionName = $access['action'];
					if(!isset($actions[$className][$actionName])) {
						if(!isset($actions[$className])){
							$actions[$className] = [];
						}
						$actions[$className] = array_merge($actions[$className],[
							$actionName => [
								'allow' => true,
								'roles' => [],
							]
						]); 
					}
					$actions[$className][$actionName]['roles'] = array_merge($actions[$className][$actionName]['roles'],[$access['name']]);
					$actions[$className][$actionName]['roles'] = array_unique($actions[$className][$actionName]['roles']) ; // = array_merge($actions[$className][$actionName]['roles'],[$permission['role']]);
          
					//$actions[$className][$actionName]['roles'] = array_merge($actions[$className][$actionName]['roles'],[$permission['role']]);
					//$actions[$className][$actionName]['roles'] = array_unique($actions[$className][$actionName]['roles']) ; // = array_merge($actions[$className][$actionName]['roles'],[$permission['role']]);
				}
			}
			foreach($actions as $className=>$access){
				$className = Inflector::pluralize(ucfirst($className)).'AccessControl';
				$rules = [];
        
				foreach($access as $actionName=>$accesscontrol){
					$accesscontrol['actions']= [$actionName];
					$rules[] = $accesscontrol;
				}
				$files[] = new CodeFile(
					Yii::getAlias("$this->accessClassPath/$className.php"),
					$this->render('access.php', [
						'rules' => $rules,
						'namespaceName' => $this->accessClassNamespace,
						'className' => $className,
					])
				);
				
			}
		}
    if($this->generateFilters){
      $this->createFilters(); 
      foreach($this->filters as $className=>$filter){

				$className = Inflector::pluralize(ucfirst($className)).'Filter';
        $filterPath = $this->getPathFromNamespace($this->filterNamespace);
				$files[] = new CodeFile(
					Yii::getAlias("$filterPath/$className.php"),
					$this->render('filter.php', [
						'filters' => $filter,
						'namespaceName' => $this->filterNamespace,
						'className' => $className,
					])
				);

      }
    }
		return $files;
	}

	private function getPathFromNamespace($namespace)
	{
		return Yii::getAlias('@' . str_replace('\\', '/', $namespace));
	}
}
