<?php echo '<?php';

$modelActions = [
    'index' => [
        'class' => yii\rest\IndexAction::class,
    ],
    'view' => [
        'class' => yii\rest\ViewAction::class,
        'implementation' => <<<'PHP'
        $model = $this->findModel($id);
        $this->checkAccess(ACTION_ID, $model);
        return $model;
PHP
    ],
    'create' => [
        'class' => yii\rest\CreateAction::class,
    ],
    'update' => [
        'class' => yii\rest\UpdateAction::class,
        'implementation' => <<<'PHP'
        $model = $this->findModel($id);
        $this->checkAccess(ACTION_ID, $model);

        $model->load(Yii::$app->getRequest()->getBodyParams(), '');
        if ($model->save() === false && !$model->hasErrors()) {
            throw new ServerErrorHttpException('Failed to update the object for unknown reason.');
        }

        return $model;
PHP
    ],
    'delete' => [
        'class' => yii\rest\DeleteAction::class,
        'implementation' => <<<'PHP'
        $model = $this->findModel($id);
        $this->checkAccess(ACTION_ID, $model);

        if ($model->delete() === false) {
            throw new ServerErrorHttpException('Failed to delete the object for unknown reason.');
        }

        \Yii::$app->response->setStatusCode(204);
PHP
    ],
];
$findModel = [];

?>


namespace <?= $namespace ?>;
use yii\data\ActiveDataProvider;
use Yii;

class <?= $className ?> extends \yii\rest\Controller
{
	private $limit = 20;
	private $modelClass ='<?=$modelNamespace?>\<?=ucfirst($modelClass)?>';
	private $modelSearch ='<?=$modelNamespace?>\<?=ucfirst($modelClass)?>Search';
	private $reservedParams = ['sort','q'];
	private $pageSize = 20;
	private $offset = 0;


    public function actions()
	{
		$actions =parent::actions();

        $newActions= [
<?php

foreach ($actions as $action):
    if (isset($modelActions[$action['id']], $action['modelClass']) && ($action['idParam'] === null || $action['idParam'] === 'id')): ?>
            <?= var_export($action['id'], true) ?> => [
                'class' => \<?= $modelActions[$action['id']]['class'] ?>::class,
                'modelClass' => <?= '\\' . $action['modelClass'] . '::class' ?>,
                'checkAccess' => [$this, 'checkAccess'],
            ],
<?php endif;
    // TODO model scenario for 'create' and 'update'
endforeach;
    ?>
            'options' => [
                'class' => \yii\rest\OptionsAction::class,
            ],
		];
		/* Apply Search */

        if(!empty($this->modelSearch)) {
                $newActions['index']['prepareDataProvider'] = [$this, 'indexDataProvider'];
        }
		return array_merge($actions,$newActions);
    }
<?php
    $serializerConfigs = [];
    foreach ($actions as $action) {
        if (isset($modelActions[$action['id']]) && !empty($action['responseWrapper'])) {
            if (!empty($action['responseWrapper'][0])) {
                $serializerConfigs[] = '        if ($action->id === ' . var_export($action['id'], true) . ") {\n"
                    . '            return ['.var_export($action['responseWrapper'][0], true).' => $serializer->serialize($result)];' . "\n"
                    . '        }';
            } elseif (!empty($action['responseWrapper'][1])) {
                $serializerConfigs[] = '        if ($action->id === ' . var_export($action['id'], true) . ") {\n"
                    . '            $serializer->collectionEnvelope = ' . var_export($action['responseWrapper'][1], true) . ";\n"
                    . '            return $serializer->serialize($result);' . "\n"
                    . '        }';
            }
        }
    }
    if (!empty($serializerConfigs)): ?>

    /**
     * {@inheritdoc}
     */
    public function afterAction($action, $result)
    {
        $result = parent::afterAction($action, $result);
        /** @var $serializer \yii\rest\Serializer */
        $serializer = \Yii::createObject($this->serializer);
<?= implode("\n", $serializerConfigs) ?>

        return $serializer->serialize($result);
    }
<?php endif; ?>

    /**
     * Checks the privilege of the current user.
     *
     * This method checks whether the current user has the privilege
     * to run the specified action against the specified data model.
     * If the user does not have access, a [[ForbiddenHttpException]] should be thrown.
     *
     * @param string $action the ID of the action to be executed
     * @param object $model the model to be accessed. If null, it means no specific model is being accessed.
     * @param array $params additional parameters
     * @throws \yii\web\ForbiddenHttpException if the user does not have access
     */
    public function checkAccess($action, $model = null, $params = [])
    {
        // TODO implement checkAccess
    }
<?php
    foreach ($actions as $action):
        if (isset($modelActions[$action['id']], $action['modelClass'])) {
            if ($action['idParam'] === null || $action['idParam'] === 'id') {
                continue;
            }
            if (isset($modelActions[$action['id']]['implementation'])) {
                $implementation = $modelActions[$action['id']]['implementation'];
                $findModel[$action['modelClass']] = 'find' . \yii\helpers\StringHelper::basename($action['modelClass']) . 'Model';
                $implementation = str_replace('findModel', $findModel[$action['modelClass']], $implementation);
                $implementation = str_replace('$id', '$'.$action['idParam'], $implementation);
                $implementation = str_replace('ACTION_ID', var_export($action['id'], true), $implementation);
            }
        }

        $actionName = 'action' . \yii\helpers\Inflector::id2camel($action['id']);
        $actionParams = implode(', ', array_map(function ($p) {
            return "\$$p";
        }, $action['params']));
        ?>

    public function <?= $actionName ?>(<?= $actionParams ?>)
    {
<?= $implementation ?? '        // TODO implement ' . $actionName ?>

    }
<?php endforeach; ?>
<?php foreach ($findModel as $modelName => $methodName): ?>

    /**
     * Returns the <?= \yii\helpers\StringHelper::basename($modelName) ?> model based on the primary key given.
     * If the data model is not found, a 404 HTTP exception will be raised.
     * @param string $id the ID of the model to be loaded.
     * @return \<?= $modelName ?> the model found
     * @throws NotFoundHttpException if the model cannot be found.
     */
    public function <?= $methodName ?>($id)
    {
        $model = \<?= $modelName ?>::findOne($id);
        if ($model !== null) {
            return $model;
        }
        throw new NotFoundHttpException("Object not found: $id");
    }
<?php endforeach; ?>


    public function indexDataProvider() {
        $params = \Yii::$app->request->queryParams;

        $model = new $this->modelClass;
        /* sollte man lieber durch safeAttributes und scenario ersetzen.*/
        $modelAttr = $model->attributes;

        /* hält die Filter für uns vor*/
        $search = [];

        if (!empty($params)) {
            foreach ($params as $key => $value) {
                /*Wenn die Werte nicht skalar sind, dann hier Ende ...*/
                if(!is_scalar($key) or !is_scalar($value)) {
                    throw new \yii\web\BadRequestHttpException('Bad Request');
                }
                /* Prüfe ob der Key $key in $modelAttr existiert */
                if (!in_array(strtolower($key), $this->reservedParams)
                    && \yii\helpers\ArrayHelper::keyExists($key, $modelAttr, false)) {
                    $search[$key] = $value;
                }
            }
        }
        /* $modelSearchnamen aus der Klasse Oben rausfiltern */
        $modelSearchname = \end(explode('\\',$this->modelSearch));
        $searchByAttr[$modelSearchname] = $search;
        $searchModel = new $this->modelSearch();

        $pagination = null;
        //$pagination = new \yii\data\Pagination(['pageSize' => $this->pageSize]);
		$query = $searchModel->search($searchByAttr);
		$dataprovider = new ActiveDataProvider(
			[
				'query' => $query
			]
		);
        //$dataprovider->setPagination($pagination);
        $dataprovider->setPagination(false);
        if(isset($params['limit'])){
                $query->limit($params['limit']);
        }
        if(isset($params['offset'])){
                $query->offset($params['offset']);
        }
        return $dataprovider;
    }
}
