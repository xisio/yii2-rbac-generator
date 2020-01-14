<?php 
$invertAccess = [];
$roles = [];
$rules = [];
foreach($permissions as $permission){
	foreach($permission['access'] as $access ) {
		$accessName = $access['name'];
		
		if(!isset($invertAccess[ $accessName ]) ) {
			$invertAccess[ $accessName ] = [];
		}
		$invertAccess[ $accessName ]['role'][] =  $permission['role'];
		$roles[] = $permission['role'];
		if(isset($access['rule']) && count($access['rule'])){
			$accessrule = $access['rule'] ?? [];
			if(!isset($invertAccess[ $accessName ] ['rule'])) {
				$invertAccess[ $accessName ] ['rule'] = [];
			}

			foreach($accessrule as $rule){
				$ruleName = $rule['name'];
				$rules[$ruleName] = $rule;
				$invertAccess[$accessName]['rule'][$ruleName] = $rule;
			}
		}
	}
}
$roles = array_unique($roles);

/* array of created role's , for prevent of duplicate */
$createdRole = [];

/* class starts here */
echo "<?php\n"; 

?>
<?php if(strlen($namespaceName) > 0) : ?>
namespace <?= $namespaceName ?> ; 
<?php endif; ?> 

use yii\db\Migration;

class <?=$className?> extends Migration
{
    public function up()
    {
        $auth = Yii::$app->authManager;

<?php foreach($roles as $role): ?> 

        $<?=$role?> = $auth->createRole('<?=$role?>');
        $auth->add($<?=$role?>);
<?php endforeach; 
?>
<?php foreach($rules as $rule): ?>
	$<?=$rule['name']?> = new <?=$rule['class']?>;
	$auth->add($<?=$rule['name']?>);
<?php endforeach; ?>

/*create permissions*/
<?php foreach($invertAccess as $permissionName=>$access) : 
?>
        // add "<?=$permissionName?>" permission
        $<?=$permissionName?> = $auth->createPermission('<?=$permissionName?>');
	<?php if(empty($access['rule'])) : ?>
		$auth->add($<?=$permissionName?>);
	<?php endif; ?> 
<?php endforeach; ?> 


<?php foreach($invertAccess as $permissionName=>$access) : 
	$hasRule = $access['rule']?? false;
	$rule = [];
	if(is_array($hasRule)) : 	
		$rule = array_shift($access['rule']);
?>
        $<?=$permissionName?>->ruleName = $<?=$rule['name']?>->name ;
	<?php endif; ?> 

	<?php if(isset($rule['extend'])) : ?>
       		$auth->add($<?=$permissionName?>);
		$auth->addChild($<?=$permissionName?>,$<?=$rule['extend']?>);
	<?php endif; ?>
		<?php foreach($access['role'] as $role ) : ?> 
			$auth->addChild($<?=$role?>,$<?=$permissionName?>);
		<?php endforeach; ?> 
		

<?php endforeach; ?> 
}

}
