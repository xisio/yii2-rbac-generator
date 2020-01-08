<?php 
$invertAccess = [];
$roles = [];

foreach($permissions as $permission){
	$rules = [];
	foreach($permission['access'] as $access ) {
		
		if(!isset($invertAccess[ $access['name'] ]) ) {
			$invertAccess[ $access['name'] ] = [];
		}
		$invertAccess[ $access['name'] ]['role'][] =  $permission['role'];
		$roles[] = $permission['role'];
		if(isset($access['rule']) && count($access['rule'])){
			$rule = $invertAccess[ $access['name'] ]['rule'] ?? [];
			$rule = array_merge($rule,array_values($access['rule']) ) ;
			$invertAccess[ $access['name'] ] ['rule'] = $rule;
		}
	}
}
$roles = array_unique($roles);

/* array of created role's , so we don't have to create new one */
$createdRole = [];

/* class starts here */
echo '<?php'; ?>

namespace <?= $namespaceName ?> ; 

use yii\db\Migration;

class m<?=date('Ymd_His_')?><?=$className?> extends Migration
{
    public function up()
    {
        $auth = Yii::$app->authManager;

<?php foreach($roles as $role): ?> 

        $<?=$role?> = $auth->createRole('<?=$role?>');
        $auth->add($<?=$role?>);
<?php endforeach; ?>

<?php foreach($rules as $rule): ?>
	$<?$rule['name']?> = new <?=$rule['class']?>;
	$auth->add($<?=$rule['name']?>);
<?php endforeach; ?>
<?php foreach($invertAccess as $permissionName=>$access) : 
?>
        // add "<?=$permissionName?>" permission
        $<?=$permissionName?> = $auth->createPermission('<?=$permissionName?>');
<?php
	$hasRule = $access['rule'] ?? false;
	if($hasRule) : 	
		$rule = array_shift($access['rule']);
?>
        $<?=$permissionName?>->ruleName =  = $<?=$rule['name']?>->name ;
	<?php endif; ?> 
	$auth->add($<?=$permissionName?>);

	<?php if($hasRule) : ?>
		$auth->addChild($<?=$permissionName?>,$<?=$rule['extend']?>);
		
	<?php endif; ?>
		<?php foreach($access['role'] as $role ) : ?> 
			$auth->addChild($<?=$role?>,$<?=$permissionName?>);
		<?php endforeach; ?> 
		

<?php endforeach; ?> 

}
