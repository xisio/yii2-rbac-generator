<?php
/* class starts here */
echo "<?php\n"; 
?>
<?php if(strlen($namespaceName) > 0) : ?>
namespace <?= $namespaceName ?> ; 
<?php endif; ?> 

use yii\filters\AccessControl;

class <?= $className ?> extends AccessControl
{
    public $rules = [ 
<?php foreach($rules as $access) : ?>
[
<?php foreach($access as $key=>$value) : ?> 
<?php if($key == 'allow') : ?> '<?= $key ?>' => <?= ($value==1?'true':'false') ?>, 
<?php else: ?>
<?php if(is_array($value)) : ?>
'<?=$key?>' => [ <?php foreach($value as $singleValue): ?> '<?= $singleValue ?>', <?php endforeach; ?> ],
<?php endif; ?> 
<?php endif; ?>
<?php endforeach; ?> ],
<?php endforeach; ?> ];
}
