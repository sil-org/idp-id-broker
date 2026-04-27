<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "mfa".
 *
 * @property int $id
 * @property int $user_id
 * @property string $type
 * @property string|null $external_uuid
 * @property string|null $label
 * @property int $verified
 * @property string $created_utc
 * @property string|null $last_used_utc
 * @property string|null $key_handle_hash
 *
 * @property MfaBackupcode[] $mfaBackupcodes
 * @property MfaFailedAttempt[] $mfaFailedAttempts
 * @property MfaWebauthn[] $mfaWebauthns
 * @property User $user
 */
class MfaBase extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const TYPE_TOTP = 'totp';
    const TYPE_U2F = 'u2f';
    const TYPE_BACKUPCODE = 'backupcode';
    const TYPE_MANAGER = 'manager';
    const TYPE_WEBAUTHN = 'webauthn';
    const TYPE_RECOVERY = 'recovery';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'mfa';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['external_uuid', 'label', 'last_used_utc', 'key_handle_hash'], 'default', 'value' => null],
            [['user_id', 'type', 'verified', 'created_utc'], 'required'],
            [['user_id', 'verified'], 'integer'],
            [['type'], 'string'],
            [['created_utc', 'last_used_utc'], 'safe'],
            [['external_uuid', 'label'], 'string', 'max' => 64],
            [['key_handle_hash'], 'string', 'max' => 255],
            ['type', 'in', 'range' => array_keys(self::optsType())],
            [['user_id'], 'exist', 'skipOnError' => true, 'targetClass' => User::class, 'targetAttribute' => ['user_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'user_id' => Yii::t('app', 'User ID'),
            'type' => Yii::t('app', 'Type'),
            'external_uuid' => Yii::t('app', 'External Uuid'),
            'label' => Yii::t('app', 'Label'),
            'verified' => Yii::t('app', 'Verified'),
            'created_utc' => Yii::t('app', 'Created Utc'),
            'last_used_utc' => Yii::t('app', 'Last Used Utc'),
            'key_handle_hash' => Yii::t('app', 'Key Handle Hash'),
        ];
    }

    /**
     * Gets query for [[MfaBackupcodes]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMfaBackupcodes()
    {
        return $this->hasMany(MfaBackupcode::class, ['mfa_id' => 'id']);
    }

    /**
     * Gets query for [[MfaFailedAttempts]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMfaFailedAttempts()
    {
        return $this->hasMany(MfaFailedAttempt::class, ['mfa_id' => 'id']);
    }

    /**
     * Gets query for [[MfaWebauthns]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMfaWebauthns()
    {
        return $this->hasMany(MfaWebauthn::class, ['mfa_id' => 'id']);
    }

    /**
     * Gets query for [[User]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getUser()
    {
        return $this->hasOne(User::class, ['id' => 'user_id']);
    }


    /**
     * column type ENUM value labels
     * @return string[]
     */
    public static function optsType()
    {
        return [
            self::TYPE_TOTP => Yii::t('app', 'totp'),
            self::TYPE_U2F => Yii::t('app', 'u2f'),
            self::TYPE_BACKUPCODE => Yii::t('app', 'backupcode'),
            self::TYPE_MANAGER => Yii::t('app', 'manager'),
            self::TYPE_WEBAUTHN => Yii::t('app', 'webauthn'),
            self::TYPE_RECOVERY => Yii::t('app', 'recovery'),
        ];
    }

    /**
     * @return string
     */
    public function displayType()
    {
        return self::optsType()[$this->type];
    }

    /**
     * @return bool
     */
    public function isTypeTotp()
    {
        return $this->type === self::TYPE_TOTP;
    }

    public function setTypeToTotp()
    {
        $this->type = self::TYPE_TOTP;
    }

    /**
     * @return bool
     */
    public function isTypeU2f()
    {
        return $this->type === self::TYPE_U2F;
    }

    public function setTypeToU2f()
    {
        $this->type = self::TYPE_U2F;
    }

    /**
     * @return bool
     */
    public function isTypeBackupcode()
    {
        return $this->type === self::TYPE_BACKUPCODE;
    }

    public function setTypeToBackupcode()
    {
        $this->type = self::TYPE_BACKUPCODE;
    }

    /**
     * @return bool
     */
    public function isTypeManager()
    {
        return $this->type === self::TYPE_MANAGER;
    }

    public function setTypeToManager()
    {
        $this->type = self::TYPE_MANAGER;
    }

    /**
     * @return bool
     */
    public function isTypeWebauthn()
    {
        return $this->type === self::TYPE_WEBAUTHN;
    }

    public function setTypeToWebauthn()
    {
        $this->type = self::TYPE_WEBAUTHN;
    }

    /**
     * @return bool
     */
    public function isTypeRecovery()
    {
        return $this->type === self::TYPE_RECOVERY;
    }

    public function setTypeToRecovery()
    {
        $this->type = self::TYPE_RECOVERY;
    }
}
