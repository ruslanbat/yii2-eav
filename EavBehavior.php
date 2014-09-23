<?php


namespace asdfstudio\eav;


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
        $properties = [];
        $query = new Query();
        $query->select('name, value');
        $query->from($this->tableName);
        $query->where([$this->primaryKey => $this->owner->{$this->primaryKey}]);

        foreach ($query->all() as $property) {
            $properties[$property['name']] = Json::decode($property['value']);
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
                'model_id' => $this->owner->{$this->primaryKey},
                'name' => $name,
                'value' => Json::encode($value),
            ];
        }
        Yii::$app->db->createCommand()
            ->batchInsert($this->tableName, ['model_id' => 'model_id', 'name' => 'name', 'value' => 'value'], $properties)
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
