<?php


namespace asdfstudio\eav;

use ArrayObject;
use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\Query;
use yii\helpers\Json;

/**
 * Class EavBehavior
 * @package asdfstudio\eav
 * @property ActiveRecord $owner
 */
class EavBehavior extends Behavior
{
    /**
     * EAV properties
     * @var array
     */
    public $properties = [];
    /**
     * Primary key for getting extended attributes
     * @var string
     */
    public $primaryKey = 'id';
    /**
     * Properties key for getting extended attributes
     * @var string
     */ 
    public $propertiesKey = 'id';
    /**
     * Properties field with attributes name
     * @var string
     */ 
    public $propertiesName = 'name';
    /**
     * Properties field with attributes value
     * @var string
     */ 
    public $propertiesValue = 'value';
    /**
     * Table name for storing extended attributes
     * @var string
     */
    public $tableName = null;

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_FIND => 'afterFind',
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    /**
     * Load all EAV of model
     */
    public function afterFind()
    {
        $this->properties = new ArrayObject();
        $this->properties ->setFlags(ArrayObject::STD_PROP_LIST|ArrayObject::ARRAY_AS_PROPS);

        $query = new Query();
        $query->select('name, value');
        $query->from($this->tableName);
        $query->where([$this->primaryKey => $this->owner->{$this->primaryKey}]);

        foreach ($query->all() as $property) {
            if(!empty($property['name'])){
                $this->properties->{$property['name']} = $property['value'];
            }
        } 
    }

    /**
     * Delete all EAV related to model
     */
    protected function deleteAll()
    {
        Yii::$app->db->createCommand()
            ->delete($this->tableName, [$this->primaryKey => $this->owner->{$this->primaryKey}])
            ->execute();
    }

    /**
     * Save all EAV
     */
    public function afterSave()
    {
        $this->deleteAll();
        $properties = [];
        if($this->properties){
            foreach ($this->properties as $name => $value) {
                $properties[] = [
                    $this->propertiesKey => $this->owner->{$this->primaryKey},
                    $this->propertiesName => $name,
                    $this->propertiesValue => $value,
                ];
            }
            if($properties){
                Yii::$app->db->createCommand()
                ->batchInsert($this->tableName, [$this->propertiesKey => $this->propertiesKey, $this->propertiesName => $this->propertiesName, $this->propertiesValue => $this->propertiesValue], $properties)
                ->execute(); 
            }
        }
    }

    /**
     * Delete all EAV
     */
    public function afterDelete()
    {
        $this->deleteAll();
    }
}