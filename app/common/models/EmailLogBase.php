<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "email_log".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $message_type
 * @property string $sent_utc
 * @property string|null $non_user_address
 *
 * @property User $user
 */
class EmailLogBase extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const MESSAGE_TYPE_INVITE = 'invite';
    const MESSAGE_TYPE_WELCOME = 'welcome';
    const MESSAGE_TYPE_MFA_RATE_LIMIT = 'mfa-rate-limit';
    const MESSAGE_TYPE_PASSWORD_CHANGED = 'password-changed';
    const MESSAGE_TYPE_GET_BACKUP_CODES = 'get-backup-codes';
    const MESSAGE_TYPE_REFRESH_BACKUP_CODES = 'refresh-backup-codes';
    const MESSAGE_TYPE_LOST_SECURITY_KEY = 'lost-security-key';
    const MESSAGE_TYPE_MFA_OPTION_ADDED = 'mfa-option-added';
    const MESSAGE_TYPE_MFA_OPTION_REMOVED = 'mfa-option-removed';
    const MESSAGE_TYPE_MFA_ENABLED = 'mfa-enabled';
    const MESSAGE_TYPE_MFA_DISABLED = 'mfa-disabled';
    const MESSAGE_TYPE_METHOD_VERIFY = 'method-verify';
    const MESSAGE_TYPE_METHOD_REMINDER = 'method-reminder';
    const MESSAGE_TYPE_METHOD_PURGED = 'method-purged';
    const MESSAGE_TYPE_PASSWORD_EXPIRING = 'password-expiring';
    const MESSAGE_TYPE_PASSWORD_EXPIRED = 'password-expired';
    const MESSAGE_TYPE_PASSWORD_PWNED = 'password-pwned';
    const MESSAGE_TYPE_EXT_GROUP_SYNC_ERRORS = 'ext-group-sync-errors';
    const MESSAGE_TYPE_ABANDONED_USERS = 'abandoned-users';
    const MESSAGE_TYPE_MFA_RECOVERY = 'mfa-recovery';
    const MESSAGE_TYPE_MFA_RECOVERY_HELP = 'mfa-recovery-help';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'email_log';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'message_type', 'non_user_address'], 'default', 'value' => null],
            [['user_id'], 'integer'],
            [['message_type'], 'string'],
            [['sent_utc'], 'required'],
            [['sent_utc'], 'safe'],
            [['non_user_address'], 'string', 'max' => 255],
            ['message_type', 'in', 'range' => array_keys(self::optsMessageType())],
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
            'message_type' => Yii::t('app', 'Message Type'),
            'sent_utc' => Yii::t('app', 'Sent Utc'),
            'non_user_address' => Yii::t('app', 'Non User Address'),
        ];
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
     * column message_type ENUM value labels
     * @return string[]
     */
    public static function optsMessageType()
    {
        return [
            self::MESSAGE_TYPE_INVITE => Yii::t('app', 'invite'),
            self::MESSAGE_TYPE_WELCOME => Yii::t('app', 'welcome'),
            self::MESSAGE_TYPE_MFA_RATE_LIMIT => Yii::t('app', 'mfa-rate-limit'),
            self::MESSAGE_TYPE_PASSWORD_CHANGED => Yii::t('app', 'password-changed'),
            self::MESSAGE_TYPE_GET_BACKUP_CODES => Yii::t('app', 'get-backup-codes'),
            self::MESSAGE_TYPE_REFRESH_BACKUP_CODES => Yii::t('app', 'refresh-backup-codes'),
            self::MESSAGE_TYPE_LOST_SECURITY_KEY => Yii::t('app', 'lost-security-key'),
            self::MESSAGE_TYPE_MFA_OPTION_ADDED => Yii::t('app', 'mfa-option-added'),
            self::MESSAGE_TYPE_MFA_OPTION_REMOVED => Yii::t('app', 'mfa-option-removed'),
            self::MESSAGE_TYPE_MFA_ENABLED => Yii::t('app', 'mfa-enabled'),
            self::MESSAGE_TYPE_MFA_DISABLED => Yii::t('app', 'mfa-disabled'),
            self::MESSAGE_TYPE_METHOD_VERIFY => Yii::t('app', 'method-verify'),
            self::MESSAGE_TYPE_METHOD_REMINDER => Yii::t('app', 'method-reminder'),
            self::MESSAGE_TYPE_METHOD_PURGED => Yii::t('app', 'method-purged'),
            self::MESSAGE_TYPE_PASSWORD_EXPIRING => Yii::t('app', 'password-expiring'),
            self::MESSAGE_TYPE_PASSWORD_EXPIRED => Yii::t('app', 'password-expired'),
            self::MESSAGE_TYPE_PASSWORD_PWNED => Yii::t('app', 'password-pwned'),
            self::MESSAGE_TYPE_EXT_GROUP_SYNC_ERRORS => Yii::t('app', 'ext-group-sync-errors'),
            self::MESSAGE_TYPE_ABANDONED_USERS => Yii::t('app', 'abandoned-users'),
            self::MESSAGE_TYPE_MFA_RECOVERY => Yii::t('app', 'mfa-recovery'),
            self::MESSAGE_TYPE_MFA_RECOVERY_HELP => Yii::t('app', 'mfa-recovery-help'),
        ];
    }

    /**
     * @return string
     */
    public function displayMessageType()
    {
        return self::optsMessageType()[$this->message_type];
    }

    /**
     * @return bool
     */
    public function isMessageTypeInvite()
    {
        return $this->message_type === self::MESSAGE_TYPE_INVITE;
    }

    public function setMessageTypeToInvite()
    {
        $this->message_type = self::MESSAGE_TYPE_INVITE;
    }

    /**
     * @return bool
     */
    public function isMessageTypeWelcome()
    {
        return $this->message_type === self::MESSAGE_TYPE_WELCOME;
    }

    public function setMessageTypeToWelcome()
    {
        $this->message_type = self::MESSAGE_TYPE_WELCOME;
    }

    /**
     * @return bool
     */
    public function isMessageTypeMfaRateLimit()
    {
        return $this->message_type === self::MESSAGE_TYPE_MFA_RATE_LIMIT;
    }

    public function setMessageTypeToMfaRateLimit()
    {
        $this->message_type = self::MESSAGE_TYPE_MFA_RATE_LIMIT;
    }

    /**
     * @return bool
     */
    public function isMessageTypePasswordChanged()
    {
        return $this->message_type === self::MESSAGE_TYPE_PASSWORD_CHANGED;
    }

    public function setMessageTypeToPasswordChanged()
    {
        $this->message_type = self::MESSAGE_TYPE_PASSWORD_CHANGED;
    }

    /**
     * @return bool
     */
    public function isMessageTypeGetBackupCodes()
    {
        return $this->message_type === self::MESSAGE_TYPE_GET_BACKUP_CODES;
    }

    public function setMessageTypeToGetBackupCodes()
    {
        $this->message_type = self::MESSAGE_TYPE_GET_BACKUP_CODES;
    }

    /**
     * @return bool
     */
    public function isMessageTypeRefreshBackupCodes()
    {
        return $this->message_type === self::MESSAGE_TYPE_REFRESH_BACKUP_CODES;
    }

    public function setMessageTypeToRefreshBackupCodes()
    {
        $this->message_type = self::MESSAGE_TYPE_REFRESH_BACKUP_CODES;
    }

    /**
     * @return bool
     */
    public function isMessageTypeLostSecurityKey()
    {
        return $this->message_type === self::MESSAGE_TYPE_LOST_SECURITY_KEY;
    }

    public function setMessageTypeToLostSecurityKey()
    {
        $this->message_type = self::MESSAGE_TYPE_LOST_SECURITY_KEY;
    }

    /**
     * @return bool
     */
    public function isMessageTypeMfaOptionAdded()
    {
        return $this->message_type === self::MESSAGE_TYPE_MFA_OPTION_ADDED;
    }

    public function setMessageTypeToMfaOptionAdded()
    {
        $this->message_type = self::MESSAGE_TYPE_MFA_OPTION_ADDED;
    }

    /**
     * @return bool
     */
    public function isMessageTypeMfaOptionRemoved()
    {
        return $this->message_type === self::MESSAGE_TYPE_MFA_OPTION_REMOVED;
    }

    public function setMessageTypeToMfaOptionRemoved()
    {
        $this->message_type = self::MESSAGE_TYPE_MFA_OPTION_REMOVED;
    }

    /**
     * @return bool
     */
    public function isMessageTypeMfaEnabled()
    {
        return $this->message_type === self::MESSAGE_TYPE_MFA_ENABLED;
    }

    public function setMessageTypeToMfaEnabled()
    {
        $this->message_type = self::MESSAGE_TYPE_MFA_ENABLED;
    }

    /**
     * @return bool
     */
    public function isMessageTypeMfaDisabled()
    {
        return $this->message_type === self::MESSAGE_TYPE_MFA_DISABLED;
    }

    public function setMessageTypeToMfaDisabled()
    {
        $this->message_type = self::MESSAGE_TYPE_MFA_DISABLED;
    }

    /**
     * @return bool
     */
    public function isMessageTypeMethodVerify()
    {
        return $this->message_type === self::MESSAGE_TYPE_METHOD_VERIFY;
    }

    public function setMessageTypeToMethodVerify()
    {
        $this->message_type = self::MESSAGE_TYPE_METHOD_VERIFY;
    }

    /**
     * @return bool
     */
    public function isMessageTypeMethodReminder()
    {
        return $this->message_type === self::MESSAGE_TYPE_METHOD_REMINDER;
    }

    public function setMessageTypeToMethodReminder()
    {
        $this->message_type = self::MESSAGE_TYPE_METHOD_REMINDER;
    }

    /**
     * @return bool
     */
    public function isMessageTypeMethodPurged()
    {
        return $this->message_type === self::MESSAGE_TYPE_METHOD_PURGED;
    }

    public function setMessageTypeToMethodPurged()
    {
        $this->message_type = self::MESSAGE_TYPE_METHOD_PURGED;
    }

    /**
     * @return bool
     */
    public function isMessageTypePasswordExpiring()
    {
        return $this->message_type === self::MESSAGE_TYPE_PASSWORD_EXPIRING;
    }

    public function setMessageTypeToPasswordExpiring()
    {
        $this->message_type = self::MESSAGE_TYPE_PASSWORD_EXPIRING;
    }

    /**
     * @return bool
     */
    public function isMessageTypePasswordExpired()
    {
        return $this->message_type === self::MESSAGE_TYPE_PASSWORD_EXPIRED;
    }

    public function setMessageTypeToPasswordExpired()
    {
        $this->message_type = self::MESSAGE_TYPE_PASSWORD_EXPIRED;
    }

    /**
     * @return bool
     */
    public function isMessageTypePasswordPwned()
    {
        return $this->message_type === self::MESSAGE_TYPE_PASSWORD_PWNED;
    }

    public function setMessageTypeToPasswordPwned()
    {
        $this->message_type = self::MESSAGE_TYPE_PASSWORD_PWNED;
    }

    /**
     * @return bool
     */
    public function isMessageTypeExtGroupSyncErrors()
    {
        return $this->message_type === self::MESSAGE_TYPE_EXT_GROUP_SYNC_ERRORS;
    }

    public function setMessageTypeToExtGroupSyncErrors()
    {
        $this->message_type = self::MESSAGE_TYPE_EXT_GROUP_SYNC_ERRORS;
    }

    /**
     * @return bool
     */
    public function isMessageTypeAbandonedUsers()
    {
        return $this->message_type === self::MESSAGE_TYPE_ABANDONED_USERS;
    }

    public function setMessageTypeToAbandonedUsers()
    {
        $this->message_type = self::MESSAGE_TYPE_ABANDONED_USERS;
    }

    /**
     * @return bool
     */
    public function isMessageTypeMfaRecovery()
    {
        return $this->message_type === self::MESSAGE_TYPE_MFA_RECOVERY;
    }

    public function setMessageTypeToMfaRecovery()
    {
        $this->message_type = self::MESSAGE_TYPE_MFA_RECOVERY;
    }

    /**
     * @return bool
     */
    public function isMessageTypeMfaRecoveryHelp()
    {
        return $this->message_type === self::MESSAGE_TYPE_MFA_RECOVERY_HELP;
    }

    public function setMessageTypeToMfaRecoveryHelp()
    {
        $this->message_type = self::MESSAGE_TYPE_MFA_RECOVERY_HELP;
    }
}
