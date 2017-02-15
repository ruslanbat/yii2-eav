<?php

namespace asdfstudio\eav;

use ArrayObject;
use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\db\Query;

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
    private $properties;

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

    /**
     * Old properties values indexed by properties names
     * @var array|null
     */
    private $_oldProperties;

    public function init(){
        $this->properties  = new ArrayObject();
        return parent::init();
    }

    public function events()
    {
        return [
            ActiveRecord::EVENT_AFTER_INSERT => 'afterSave',
            ActiveRecord::EVENT_AFTER_UPDATE => 'afterSave',
            ActiveRecord::EVENT_AFTER_DELETE => 'afterDelete',
        ];
    }

    /**
     * Sets the old properties values.
     */
    public function setOldProperties($values)
    {
        $this->_oldProperties = $values;
    }

    /**
     * Get the old properties values.
     */
    public function getOldProperties()
    {
        return $this->_oldProperties;
    }

    /**
     * Returns the propertie values that have been modified since they are loaded or saved most recently.
     * @return array the changed propertie values (name-value pairs)
     */
    public function getDirtyProperties()
    {
        $properties = [];
        if ($this->_oldProperties === null) {
            if (!empty($this->properties)) {
                foreach ($this->properties as $name => $value) {
                    $properties[$name] = $value;
                }
            }
        } else {
            foreach ($this->properties as $name => $value) {
                if (!array_key_exists($name, $this->_oldProperties) || $value !== $this->_oldProperties[$name]) {
                    $properties[$name] = $value;
                }
            }
        }
        return $properties;
    }
    
    /**
     * Returns the properties values. Load properties on first running.
     * @return ArrayObject properties
     */
    public function getProperties()
    {
        if (empty($this->properties)) {
            $this->properties = new ArrayObject();
            $this->properties->setFlags(ArrayObject::STD_PROP_LIST | ArrayObject::ARRAY_AS_PROPS);

            $query = new Query();
            $query->select([$this->propertiesName, $this->propertiesValue]);
            $query->from($this->tableName);
            $query->where([$this->primaryKey => $this->owner->{$this->primaryKey}]);

            foreach ($query->all() as $property) {
                if (!empty($property[$this->propertiesName])) {
                    $this->properties->{$property[$this->propertiesName]} = $property[$this->propertiesValue];
                }
            }
            $this->setOldProperties($this->properties->getArrayCopy());
        }
        return $this->properties;
    }
    
    /**
     * Setting the properties values
     * @param ArrayObject $properties
     */
    public function setProperties($properties)
    {
        if (is_array($properties)) {
            $this->properties = new ArrayObject($properties);
        } else {
            $this->properties = $properties;
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
        $dirtyProperties = $this->dirtyProperties;
        if (empty($dirtyProperties)) {
            return;
        }
        
        Yii::$app->db->createCommand()
            ->delete($this->tableName, [
                $this->primaryKey => $this->owner->{$this->primaryKey},
                $this->propertiesName => array_keys($dirtyProperties),
            ])
            ->execute();
        
        $properties = [];
        foreach ($dirtyProperties as $name => $value) {
            $properties[] = [
                $this->owner->{$this->primaryKey},
                $name,
                $value,
            ];
        }
        Yii::$app->db->createCommand()
            ->batchInsert($this->tableName, [
                $this->propertiesKey,
                $this->propertiesName,
                $this->propertiesValue
            ], $properties)
            ->execute();
    }

    /**
     * Delete all EAV
     */
    public function afterDelete()
    {
        $this->deleteAll();
    }

    /**
     * Replace properties from array
     */
    public function replaceProperties($properties)
    {
        $this->getProperties();
        $new_properties = array_merge($this->properties->getArrayCopy(), $properties);
        $this->properties->exchangeArray($new_properties);
    }
}
