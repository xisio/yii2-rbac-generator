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



use cebe\yii2openapi\helper\ModelClass;

/**
 *
 *
 */
class ApiGenerator extends Generator
{
    /**
     * @var string path to the OpenAPI specification file. This can be an absolute path or a Yii path alias.
     */
    public $rbacYamlPath;
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
            [['openApiPath'], 'filter', 'filter' => 'trim'],

            [['controllerNamespace', 'migrationNamespace'], 'default', 'value' => null],

            [['ignoreSpecErrors', 'generateUrls', 'generateModels', 'generateModelFaker', 'generateControllers'], 'boolean'],

            ['rbacYamlPath', 'required'],
            ['rbacYamlPath', 'validateYaml'],

            [['accessClassNamespace'], 'required', 'when' => function (RbacGenerator $rbac) {
                return (bool) $rbac->generateRbac;
            }],
            [['migrationPath'], 'required', 'when' => function (RbacGenerator $rbac) {
                return (bool) $model->generateMigrations;
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
	protected function readYaml($file)

	{
		try {
			$this->_yaml = Yaml::parse($file);
		} catch (ParseException $exception) {
			printf('Unable to parse the YAML string: %s', $exception->getMessage());
		}
        return $this->_yaml;
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
		if($this->generateAccessClass){
			/*
                $files[] = new CodeFile(
                    Yii::getAlias("$migrationPath/$className.php"),
                    $this->render('junction.php', [
						'tableName' => $tableName,
						'className' => $className,
						'reference' => $referenceTables,
                    ])
				);
		}*/

		}
        return $files;
    }

    private function getPathFromNamespace($namespace)
    {
        return Yii::getAlias('@' . str_replace('\\', '/', $namespace));
    }
}
