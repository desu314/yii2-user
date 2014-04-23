<?php

namespace amnah\yii2\user\models;

use Yii;
use yii\db\ActiveRecord;
use yii\helpers\Security;

/**
 * This is the model class for table "tbl_user_key".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $type
 * @property string  $key
 * @property string  $create_time
 * @property string  $consume_time
 * @property string  $expire_time
 *
 * @property User    $user
 */
class UserKey extends ActiveRecord {

    /**
     * @var int Key for email activations (for registrations)
     */
    const TYPE_EMAIL_ACTIVATE = 1;

    /**
     * @var int Key for email changes (=updating account page)
     */
    const TYPE_EMAIL_CHANGE = 2;

    /**
     * @var int Key for password resets
     */
    const TYPE_PASSWORD_RESET = 3;

    /**
     * @inheritdoc
     */
    public static function tableName() {
        return static::getDb()->tablePrefix . "user_key";
    }

    /**
     * No inputs are used for userKeys
     *
     * @inheritdoc
     */
    /*
    public function rules() {
        return [
            [['user_id', 'type', 'key'], 'required'],
            [['user_id', 'type'], 'integer'],
            [['create_time', 'consume_time', 'expire_time'], 'safe'],
            [['key'], 'string', 'max' => 255],
            [['key'], 'unique']
        ];
    }
    */

    /**
     * @inheritdoc
     */
    public function attributeLabels() {
        return [
            'id'           => Yii::t('app', 'ID'),
            'user_id'      => Yii::t('app', 'User ID'),
            'type'         => Yii::t('app', 'Type'),
            'key'          => Yii::t('app', 'Key'),
            'create_time'  => Yii::t('app', 'Create Time'),
            'consume_time' => Yii::t('app', 'Consume Time'),
            'expire_time'  => Yii::t('app', 'Expire Time'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getUser() {
        $user = Yii::$app->getModule("user")->model("User");
        return $this->hasOne($user::className(), ['id' => 'user_id']);
    }

    /**
     * @inheritdoc
     */
    public function behaviors() {
        return [
            'timestamp' => [
                'class' => 'yii\behaviors\TimestampBehavior',
                'attributes' => [
                    // set only create_time because there is no update_time
                    ActiveRecord::EVENT_BEFORE_INSERT => ['create_time'],
                ],
                'value' => function() { return date("Y-m-d H:i:s"); },
            ],
        ];
    }

    /**
     * Generate/reuse a userKey
     *
     * @param int    $userId
     * @param int    $type
     * @param string $expireTime
     * @return static
     */
    public static function generate($userId, $type, $expireTime = null) {

        // attempt to find existing record
        // otherwise create new
        $model = static::findActiveByUser($userId, $type);
        if (!$model) {
            $model = new static();
        }

        // set/update data
        $model->user_id     = $userId;
        $model->type        = $type;
        $model->create_time = date("Y-m-d H:i:s");
        $model->expire_time = $expireTime;
        $model->key         = Security::generateRandomKey();
        $model->save(false);
        return $model;
    }

    /**
     * Find an active userKey by userId
     *
     * @param int       $userId
     * @param array|int $type
     * @return static
     */
    public static function findActiveByUser($userId, $type) {

        $now = date("Y-m-d H:i:s");
        return static::find()
            ->where([
                "user_id"      => $userId,
                "type"         => $type,
                "consume_time" => null,
            ])
            ->andWhere("([[expire_time]] >= '$now' or [[expire_time]] is NULL)")
            ->one();
    }

    /**
     * Find an active userKey by key
     *
     * @param string    $key
     * @param array|int $type
     * @return static
     */
    public static function findActiveByKey($key, $type) {

        $now = date("Y-m-d H:i:s");
        return static::find()
            ->where([
                "key"          => $key,
                "type"         => $type,
                "consume_time" => null,
            ])
            ->andWhere("([[expire_time]] >= '$now' or [[expire_time]] is NULL)")
            ->one();
    }

    /**
     * Consume userKey record
     *
     * @return static
     */
    public function consume() {
        $this->consume_time = date("Y-m-d H:i:s");
        $this->save(false);
        return $this;
    }

    /**
     * Expire userKey record
     *
     * @return static
     */
    public function expire() {
        $this->expire_time = date("Y-m-d H:i:s");
        $this->save(false);
        return $this;
    }
}