<?php

namespace amnah\yii2\user\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * This is the model class for table "tbl_role".
 *
 * @property integer $id
 * @property string  $name
 * @property string  $create_time
 * @property string  $update_time
 * @property integer $can_admin
 *
 * @property User[]  $users
 */
class Role extends ActiveRecord {

    /**
     * @var int Admin user role
     */
    const ROLE_ADMIN = 1;

    /**
     * @var int Default user role
     */
    const ROLE_USER = 2;

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return static::getDb()->tablePrefix . "role";
    }

    /**
     * @inheritdoc
     */
    public function rules() {
        return [
            [['name'], 'required'],
//            [['create_time', 'update_time'], 'safe'],
            [['can_admin'], 'integer'],
            [['name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id'          => Yii::t('app', 'ID'),
            'name'        => Yii::t('app', 'Name'),
            'create_time' => Yii::t('app', 'Create Time'),
            'update_time' => Yii::t('app', 'Update Time'),
            'can_admin'   => Yii::t('app', 'Can Admin'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUsers() {
        $user = Yii::$app->getModule("user")->model("User");
        return $this->hasMany($user::className(), ['role_id' => 'id']);
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => 'create_time',
                    ActiveRecord::EVENT_BEFORE_UPDATE => 'update_time',
                ],
                'value' => function() { return date("Y-m-d H:i:s"); },
            ],
        ];
    }

    /**
     * Check permission
     *
     * @param string $permission
     * @return bool
     */
    public function checkPermission($permission) {
        $roleAttribute = "can_{$permission}";
        return $this->$roleAttribute ? true : false;
    }

    /**
     * Get list of roles for creating dropdowns
     *
     * @return array
     */
    public static function dropdown() {

        // get and cache data
        static $dropdown;
        if ($dropdown === null) {

            // get all records from database and generate
            $models = static::find()->all();
            foreach ($models as $model) {
                $dropdown[$model->id] = $model->name;
            }
        }

        return $dropdown;
    }
}