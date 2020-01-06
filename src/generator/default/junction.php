<?= '<?php' ?>

<?php if (isset($namespace)) {
echo "\nnamespace $namespace;\n";
}
?>
<?php
$foreignSource = strtolower($reference['source']);
$foreignTarget = strtolower($reference['target']);

$junctionTableName =  $tableName ; 
//strtolower($foreignSource).'_'.strtolower($foreignTarget);


$foreignColumn1 = strtolower($foreignSource.'_uuid');
$foreignName1 = $junctionTableName.'-'.$foreignColumn1;
$foreignTable1 = [
	'name' => 'fk-'.$foreignName1,
	'table' => strtolower($tableName),
	'tableColumn' => $foreignColumn1,
	'tableforeign' => $foreignSource,
	'foreignColumn' => 'uuid',
];
$foreignColumn2 = strtolower($foreignTarget.'_uuid');

$foreignName2 = $junctionTableName.'-'.$foreignColumn2;
$foreignTable2 = [
	'name' => 'fk-'. $foreignName2,
	'table' => strtolower($tableName),
	'tableColumn' => $foreignColumn2,
	'tableforeign' => $foreignTarget,
	'foreignColumn' => 'uuid',
];

$indexTable1 = [
	'name' => 'id-' . $foreignName1 ,
	'table' => strtolower($tableName),
	'column' => $foreignTable1['tableColumn']
];
$indexTable2 = [
	'name' => 'id-' . $foreignName2 ,
	'table' => strtolower($tableName),
	'column' => $foreignTable2['tableColumn']
];

?>
class <?= $className ?> extends \yii\db\Migration
{
    public function up()
    {
		$this->createTable('<?= strtolower($tableName) ?>',
			[
				'<?= $foreignTable1['tableColumn']?>' => $this->string(36), 	
				'<?= $foreignTable2['tableColumn']?>' => $this->string(36), 	
				'PRIMARY KEY(<?=$foreignTable1['tableColumn']?>,<?=$foreignTable2['tableColumn']?> )',
			]
		);

		
		$this->addForeignKey( 
		<?php foreach($foreignTable1 as $key=>$value) : ?>
			'<?=$value?>'  <?= ($value == end($foreignTable1)) ? '':",\n" ?>
		<?php endforeach; ?> 
		);

		$this->addForeignKey(

		<?php foreach($foreignTable2 as $key=>$value) : ?>
		'<?=$value?>' <?= ($value == end($foreignTable2)) ? '':",\n" ?>
		<?php endforeach; ?> 
		);

		$this->createIndex(
		<?php foreach($indexTable1 as $key=>$value) : ?>
		'<?=$value?>' <?= ($value == end($indexTable1)) ? '':",\n" ?>
		<?php endforeach; ?> 
		);

		$this->createIndex(
		<?php foreach($indexTable2 as $key=>$value) : ?>
		'<?=$value?>' <?= ($value == end($indexTable2)) ? '':",\n" ?>
		<?php endforeach; ?> 
		);
    }

    public function down()
    {
        $this->dropTable('<?= $tableName ?>');
    }
}
