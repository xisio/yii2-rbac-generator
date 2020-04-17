<?php
/* class starts here */
echo "<?php\n"; 
?>
<?php if(strlen($namespaceName) > 0) : ?>
namespace <?= $namespaceName ?> ; 
<?php endif; ?> 

use Yii;

class <?= $className ?>
{
    private $timestamp = null;
    private $tableName = null;
    public function append($query,$tableName){
    $this->tableName = $tableName;
    $this->timestamp = date('c');
<?php foreach($filters as $permission=>$filter) : ?>
    <?php if($filter['role'] == 'other') : ?> 
  
      if(Yii::$app->user->isGuest)
    <?php else: ?>
      if(Yii::$app->user->can('<?=$permission?>'))
    <?php endif; ?>
    {
      <?php foreach($filter['filter'] as $filterQuery) : ?>
        <?= $filterQuery?> ; 
      <?php endforeach; ?>
    }
<?php endforeach ?>    
    }
}
