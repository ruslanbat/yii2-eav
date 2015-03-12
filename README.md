#Yii2 EAV behavior

Easy way to store extended attribtes of model.

##Usage

```php
class MyModel extends ActiveRecord {
    public function behaviors() {
        return [
            'properties' => [
                'class' => EavBehavior::className(),
                'primaryKey' => 'id', // id related to model
                'tableName' => 'my_model_properties', // table name for store attributes
                'propertiesKey' => 'id', // id related to properties model
                'propertiesName' => 'name', // Properties field with attributes name
                'propertiesValue' => 'value', // Properties field with attributes value
                
            ],
        ];
    }

    public function init() {
        parent::init();
        print_r($this->properties); // show properties as ArrayObject

        $this->properties->foo = 'foo';
        $this->properties->bar = 'bar';

        $this->properties['arrfoo'] = 'foo';
        $this->properties['arrbar'] = 'bar';

        $value_in_array = array('in_arr1' => 'value_arr1', 'in_arr2' => 'value_arr2');
        //this method marge exist properties with properties from $value_in_array
        $this->replaceProperties($value_in_array);
    }
}
```

EAV table schema should contain fields:

* model_id;
* name;
* value;

For example (MySQL):

```sql
CREATE TABLE `my_model_properties` (
  `model_id` int(10) unsigned NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` varchar(255) DEFAULT NULL,
  UNIQUE KEY `u_model_name` (`name`,`model_id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8;
```
