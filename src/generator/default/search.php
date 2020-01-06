<?php echo "<?php"; ?> 

namespace <?= $namespace ?>;

use yii\base\Model;
use yii\data\ActiveDataProvider;
use common\models\<?= $modelClass ?>;

/**
 * HlzpubPublicationSearch represents the model behind the search form of `common\models\HlzpubPublication`.
 */
class <?= $modelClass ?>Search extends <?= $modelClass ?>
{
    /**
     * {@inheritdoc}
     */
    public $category='';
    public $series='';
    public function rules()
	{
		return [
	<?php
    $safeAttributes = [];
    $requiredAttributes = [];
    $integerAttributes = [];
    $stringAttributes = [];
    $compareEqualAttributes = [];
		foreach($attributes as $key=>$attribute){
			$name = $attribute['name'];
			if ($attribute['readOnly']) {
				continue;
			}
			if ($attribute['required']) {
				$requiredAttributes[$name] = $name;
			}
			switch($attribute['type']){
				case 'integer' :
				case 'boolean' : 
				case 'bool' : 
					$integerAttributes[$name] = $name;
					break;
				case 'string' :
					if(preg_match('/uuid/',$name)){
						$compareEqualAttributes[$name] = $name;
					}else {
						$stringAttributes[$name] = $name;
					}
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
    if (!empty($compareEqualAttributes)) {
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

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params)
    {
	   $query = <?=$modelClass?>::find();
        // add conditions that should always apply here
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
		]);

        $this->load($params);
        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }
        // grid filtering conditions
		$query->andFilterWhere([
<?php foreach ($integerAttributes as $name) : ?> 
			'<?=$name?>' => $this-><?= $name?>,
<?php endforeach; ?>

<?php foreach($safeAttributes as $name) : ?>
			'<?=$name?>' => $this-><?= $name?>,
<?php endforeach;  ?>


<?php foreach($compareEqualAttributes as $name) : ?>
			'<?=$name?>' => $this-><?= $name?>,
<?php endforeach  ?>
        ]);
		<?php
			if(count($stringAttributes) > 0) : 
		?>
		$query
			<?php foreach ($stringAttributes as $name) :  ?>
				->andFilterWhere(['like','<?=$name?>',$this-><?=$name?> ])
			<?php endforeach; ?> ;
		<?php
			endif;
?>	
        return $query;
        //return $dataProvider;
    }
}
