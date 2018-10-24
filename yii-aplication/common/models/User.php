<?php
namespace common\models;

use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\helpers\ArrayHelper;
use yii\web\IdentityInterface;
use yii\web\NotFoundHttpException;

/**
 * User model
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $auth_key
 * @property integer $status
 * @property integer $created_at
 * @property integer $updated_at
 * @property string $password write-only password
 */
class User extends ActiveRecord implements IdentityInterface
{
    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;


    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%user}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['status', 'default', 'value' => self::STATUS_ACTIVE],
            ['status', 'in', 'range' => [self::STATUS_ACTIVE, self::STATUS_DELETED]],
            ['username', 'trim'],
            ['username', 'required'],
            ['username', 'string', 'min' => 2, 'max' => 255],
            [['username', 'auth_key', 'password_hash', 'email', 'phone', 'name', 'surname'], 'required'],
            [['status',], 'integer'],
            [['username', 'email', 'phone'], 'string', 'max' => 255],
            [['name', 'surname'], 'string', 'max' => 50],
            [['username'], 'unique'],
            [['email'], 'unique'],
            [['email'], 'email'],
            [['phone'], 'unique'],
            [['password_reset_token'], 'unique'],

        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentity($id)
    {
        return static::findOne(['id' => $id, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * {@inheritdoc}
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @return static|null
     */
    public static function findByUsername($username)
    {
        return static::findOne(['username' => $username, 'status' => self::STATUS_ACTIVE]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return bool
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }

        $timestamp = (int)substr($token, strrpos($token, '_') + 1);
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        return $timestamp + $expire >= time();
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * {@inheritdoc}
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return bool if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }

    public function fields()
    {
        $fields = parent::fields();

        // удаляем небезопасные поля
        unset($fields['auth_key'],
            $fields['password_hash'],
            $fields['password_reset_token'],
            $fields['status'],
            $fields['created_at'],
            $fields['updated_at']);

        return $fields;
    }


    public function getUsers()
    {
        return ArrayHelper::toArray(static::find()
            ->where(['status' => static::STATUS_ACTIVE])
            ->all(), [
            static::className()
        ]);
    }


    public function searchUser($search)
    {
        return ArrayHelper::toArray(static::find()
            ->where(['status' => static::STATUS_ACTIVE])
            ->andFilterWhere(['or',
                ['like', 'name', $search],
                ['like' , 'surname', $search],
            ])
            ->all(), static::className());
    }

    public function updateUser($id)
    {
        $user = User::find()
        ->where(['status' => static::STATUS_ACTIVE])
        ->andWhere(['id' => $id])
        ->one();
        if (!is_null($user)){
            $user->load(Yii::$app->request->getBodyParams(), '');
            $user->save();
            return true;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

    public function deleteUser($id)
    {
        $user = User::find()
            ->where(['status' => static::STATUS_ACTIVE])
            ->andWhere(['id' => $id])
            ->one();
        if (!is_null($user)){
            $user->status = self::STATUS_DELETED;
            $user->save();
            return true;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }

}
