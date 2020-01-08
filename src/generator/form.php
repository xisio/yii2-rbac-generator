<?php

/* @var $this yii\web\View */
/* @var $form yii\widgets\ActiveForm */
/* @var $generator \xisio\rbacgenerator\generator\RbacGenerator */

echo $form->field($generator, 'rbacYamlPath')->error(['encode' => false]);
?>
<div class="panel panel-default card">
    <div class="panel-heading card-header">
        <?= $form->field($generator, 'generateRbac')->checkbox() ?>
		<?= $form->field($generator, 'migrationPath') ?>
    </div>
</div>

<div class="panel panel-default card">
    <div class="panel-body card-body">
        <?= $form->field($generator, 'generateAccessClass')->checkbox() ?>
    </div>
    <div class="panel-body card-body">
        <?= $form->field($generator, 'accessClassNamespace') ?>
    </div>
</div>


<div class="panel panel-default card">
    <div class="panel-heading card-header">
        <?= $form->field($generator, 'generateRbacMigrations')->checkbox() ?>
    </div>
    <div class="panel-body card-body">
        <?= $form->field($generator, 'migrationPath') ?>
        <?= $form->field($generator, 'migrationNamespace') ?>
    </div>
</div>
