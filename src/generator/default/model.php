<?= '<?php' ?>


namespace <?= $namespace ?>;

use Ramsey\Uuid\Uuid;
use Yii;
use yii\db\Expression;

/**
 * <?= str_replace("\n", "\n * ", trim($description)) ?>

 *
<?php foreach ($attributes as $attribute): ?>
 * @property <?= $attribute['type'] ?? 'mixed' ?> $<?= str_replace("\n", "\n * ", rtrim($attribute['name'] . ' ' . $attribute['description'])) ?>

<?php endforeach; ?>
 */
class <?= $className ?> extends \yii\db\ActiveRecord
{
    public static function tableName()
    {
        return <?= var_export($tableName) ?>;
    }

   public function beforeSave($insert) {
        if ($this->isNewRecord) {
	    //if(isset($this->uuid)){
		    $this->uuid= Uuid::uuid4()->toString();
	//	}
	    if(empty($this->crdate)){
		$this->crdate = date('c');
	    }
	    if(empty($this->hidden)){
		$this->hidden = 0;
	    }
	    if(empty($this->deleted)){
		$this->deleted = 0;
	    }
        } 
	$this->tstamp = date('c');
        return parent::beforeSave($insert);
    }
 

    public function rules()
    {
        return [
<?php
    $safeAttributes = [];
    $requiredAttributes = [];
    $integerAttributes = [];
    $stringAttributes = [];

    foreach ($attributes as $attribute) {
        if ($attribute['readOnly']) {
            continue;
        }
        if ($attribute['required']) {
            $requiredAttributes[$attribute['name']] = $attribute['name'];
        }
        switch ($attribute['type']) {
            case 'integer':
                $integerAttributes[$attribute['name']] = $attribute['name'];
                break;
            case 'string':
                $stringAttributes[$attribute['name']] = $attribute['name'];
                break;
            default:
            case 'array':
                $safeAttributes[$attribute['name']] = $attribute['name'];
                break;
        }
    }
    if (!empty($stringAttributes)) {
        echo "            [['" . implode("', '", $stringAttributes) . "'], 'trim'],\n";
    }
    if (!empty($requiredAttributes)) {
        echo "            [['" . implode("', '", $requiredAttributes) . "'], 'required'],\n";
    }
    if (!empty($stringAttributes)) {
        echo "            [['" . implode("', '", $stringAttributes) . "'], 'string'],\n";
    }
    if (!empty($integerAttributes)) {
        echo "            [['" . implode("', '", $integerAttributes) . "'], 'integer'],\n";
    }
    if (!empty($safeAttributes)) {
        echo "            // TODO define more concreate validation rules!\n";
        echo "            [['" . implode("','", $safeAttributes) . "'], 'safe'],\n";
    }

?>
        ];
    }
<?php
	$extraFields = [];
?>
<?php foreach ($relations as $relationName => $relation):
		$extraFields[] = strtolower($relationName);

?>
    public function get<?= ucfirst($relationName) ?>()
    {
        return $this-><?= $relation['method'] ?>(<?= $relation['class'] ?>::class, <?php
            echo str_replace(
                    [',', '=>', ', ]'],
                    [', ', ' => ', ']'],
                    preg_replace('~\s+~', '', \yii\helpers\VarDumper::export($relation['link']))
            )
        ?>);
	}
<?php endforeach; ?>

<?php if(count($extraFields)) :  ?> 
	public function extraFields(){
		return [
			<?php foreach($extraFields as $field) : ?>
				'<?=$field?>',
			<?php endforeach; ?>
		];


}	
<?php endif; ?>

	public static function find() {
		$timestamp = date('c');
		$null = new Expression('NULL');
		$query =  parent::find()->where(['hidden' => 0,'deleted' => 0])
		->andWhere(['or',
			['>=','starttime',$timestamp],
			['is','starttime',$null]
			]
		)
		->andWhere(['or',
			['<','endtime',$timestamp],
			['is','endtime',$null]
			]
		);
		return $query;
	}

}
