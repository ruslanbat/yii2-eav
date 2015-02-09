<?php


namespace app\components;


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
     * @var object
     */
    public $properties;
    /**
     * Primary key for getting extended attributes
     * @var string
     */
    public $primaryKey = 'id';
    /**
     * Properties key for getting extended attributes
     * @var string
     */ 
    public $propertiesKey = 'id'
    /**
     * Properties field with attributes name
     * @var string
     */ 
    public $propertiesName = 'name'
    /**
     * Properties field with attributes name
     * @var string
     */ 
    public $propertiesValue = 'value'
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
        $properties = (object) [];
        $query = new Query();
        $query->select('name, value');
        $query->from($this->tableName);
        $query->where([$this->primaryKey => $this->owner->{$this->primaryKey}]);

        foreach ($query->all() as $property) {
            $properties->{$property['name']} = $property['value'];
        }
        $this->properties = $properties;
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
        foreach ($this->properties as $name => $value) {
            $properties[] = [
                $this->propertiesKey => $this->owner->{$this->primaryKey},
                $this->propertiesName => $name,
                $this->propertiesValue => $value,
            ];
        }
        Yii::$app->db->createCommand()
            ->batchInsert($this->tableName, [$this->propertiesKey => $this->propertiesKey, $this->propertiesName => $this->propertiesName, $this->propertiesValue => $this->propertiesValue], $properties)
            ->execute();
    }

    /**
     * Delete all EAV
     */
    public function afterDelete()
    {
        $this->deleteAll();
    }
}