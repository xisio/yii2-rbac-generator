<?php
use yii\helpers\Inflector;
use yii\helpers\StringHelper;
?>
<?= '<?php' ?>

<?php if (isset($namespace)) {
    echo "\nnamespace $namespace;\n";
} ?>

/**
 * <?= $description ?>

 */
class <?= $className ?> extends \yii\db\Migration
{
    public function up()
    {
	$this->execute("SET foreign_key_checks = 0;");

        $this->createTable('<?= $tableName ?>', [
<?php foreach ($attributes as $attribute): ?>
            '<?= $attribute['dbName'] ?>' => '<?= $attribute['dbType'] ?>',
<?php endforeach; ?>

		<?php if(isset($attributes['uuid'])): ?>
				'PRIMARY KEY(uuid)',
		<?php endif; ?>
		]);
<?php if(count($relations)) : ?>

	<?php foreach ($relations as $key=>$relation): 
	if(isset($relation['method']) && $relation['method'] =='hasOne'):
		$local_column = key(array_flip($relation['link']));
		$foreign_column = key($relation['link']);
	   $local_table = $tableName;
	   $foreign_table= '{{%' . Inflector::camel2id(StringHelper::basename(Inflector::pluralize($relation['class'])), '_') . '}}'	
	?>
	$this->addForeignKey('fk-<?=$key?>','<?=$tableName?>','<?=$local_column?>','<?=$foreign_table?>','<?=$foreign_column?>');

	<?php endif; ?>
	
	<?php endforeach; ?> 
<?php endif; ?> 

	

	$this->execute("SET foreign_key_checks = 1;");
        // TODO generate foreign keys
    }

    public function down()
    {
        $this->dropTable('<?= $tableName ?>');
    }
}
