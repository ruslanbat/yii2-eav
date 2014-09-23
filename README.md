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
            ],
        ];
    }

    public function init() {
        parent::init();
        print_r($this->properties); // show properties as array

        $this->properties = [
            'foo' => 'foo',
            'bar' => [1, 2, 3],
            'test' => [
                'foo' => 'bar',
            ]
        ];
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
