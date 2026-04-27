<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "user".
 *
 * @property int $id
 * @property string $uuid
 * @property string $employee_id
 * @property string $first_name
 * @property string $last_name
 * @property string|null $display_name
 * @property string $username
 * @property string|null $email
 * @property int|null $current_password_id
 * @property string $active
 * @property string $locked
 * @property string $last_changed_utc
 * @property string $last_synced_utc
 * @property string|null $require_mfa
 * @property string $review_profile_after
 * @property string|null $last_login_utc
 * @property string|null $manager_email
 * @property string $hide
 * @property string|null $groups
 * @property string $groups_external
 * @property string|null $personal_email
 * @property string|null $expires_on
 * @property string $nag_for_mfa_after
 * @property string $nag_for_method_after
 * @property string|null $created_utc
 * @property string|null $deactivated_utc
 *
 * @property Password $currentPassword
 * @property EmailLog[] $emailLogs
 * @property Invite[] $invites
 * @property Method[] $methods
 * @property Mfa[] $mfas
 */
class UserBase extends \yii\db\ActiveRecord
{

    /**
     * ENUM field values
     */
    const ACTIVE_YES = 'yes';
    const ACTIVE_NO = 'no';
    const LOCKED_NO = 'no';
    const LOCKED_YES = 'yes';
    const REQUIRE_MFA_NO = 'no';
    const REQUIRE_MFA_YES = 'yes';
    const HIDE_NO = 'no';
    const HIDE_YES = 'yes';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['display_name', 'email', 'current_password_id', 'require_mfa', 'last_login_utc', 'manager_email', 'groups', 'personal_email', 'expires_on', 'created_utc', 'deactivated_utc'], 'default', 'value' => null],
            [['hide'], 'default', 'value' => 'no'],
            [['groups_external'], 'default', 'value' => ''],
            [['uuid', 'employee_id', 'first_name', 'last_name', 'username', 'active', 'locked', 'last_changed_utc', 'last_synced_utc', 'review_profile_after', 'nag_for_mfa_after', 'nag_for_method_after'], 'required'],
            [['current_password_id'], 'integer'],
            [['active', 'locked', 'require_mfa', 'hide'], 'string'],
            [['last_changed_utc', 'last_synced_utc', 'review_profile_after', 'last_login_utc', 'expires_on', 'nag_for_mfa_after', 'nag_for_method_after', 'created_utc', 'deactivated_utc'], 'safe'],
            [['uuid'], 'string', 'max' => 64],
            [['employee_id', 'first_name', 'last_name', 'display_name', 'username', 'email', 'manager_email', 'groups', 'groups_external', 'personal_email'], 'string', 'max' => 255],
            ['active', 'in', 'range' => array_keys(self::optsActive())],
            ['locked', 'in', 'range' => array_keys(self::optsLocked())],
            ['require_mfa', 'in', 'range' => array_keys(self::optsRequireMfa())],
            ['hide', 'in', 'range' => array_keys(self::optsHide())],
            [['employee_id'], 'unique'],
            [['username'], 'unique'],
            [['email'], 'unique'],
            [['current_password_id'], 'exist', 'skipOnError' => true, 'targetClass' => Password::class, 'targetAttribute' => ['current_password_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('app', 'ID'),
            'uuid' => Yii::t('app', 'Uuid'),
            'employee_id' => Yii::t('app', 'Employee ID'),
            'first_name' => Yii::t('app', 'First Name'),
            'last_name' => Yii::t('app', 'Last Name'),
            'display_name' => Yii::t('app', 'Display Name'),
            'username' => Yii::t('app', 'Username'),
            'email' => Yii::t('app', 'Email'),
            'current_password_id' => Yii::t('app', 'Current Password ID'),
            'active' => Yii::t('app', 'Active'),
            'locked' => Yii::t('app', 'Locked'),
            'last_changed_utc' => Yii::t('app', 'Last Changed Utc'),
            'last_synced_utc' => Yii::t('app', 'Last Synced Utc'),
            'require_mfa' => Yii::t('app', 'Require Mfa'),
            'review_profile_after' => Yii::t('app', 'Review Profile After'),
            'last_login_utc' => Yii::t('app', 'Last Login Utc'),
            'manager_email' => Yii::t('app', 'Manager Email'),
            'hide' => Yii::t('app', 'Hide'),
            'groups' => Yii::t('app', 'Groups'),
            'groups_external' => Yii::t('app', 'Groups External'),
            'personal_email' => Yii::t('app', 'Personal Email'),
            'expires_on' => Yii::t('app', 'Expires On'),
            'nag_for_mfa_after' => Yii::t('app', 'Nag For Mfa After'),
            'nag_for_method_after' => Yii::t('app', 'Nag For Method After'),
            'created_utc' => Yii::t('app', 'Created Utc'),
            'deactivated_utc' => Yii::t('app', 'Deactivated Utc'),
        ];
    }

    /**
     * Gets query for [[CurrentPassword]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getCurrentPassword()
    {
        return $this->hasOne(Password::class, ['id' => 'current_password_id']);
    }

    /**
     * Gets query for [[EmailLogs]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getEmailLogs()
    {
        return $this->hasMany(EmailLog::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Invites]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getInvites()
    {
        return $this->hasMany(Invite::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Methods]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMethods()
    {
        return $this->hasMany(Method::class, ['user_id' => 'id']);
    }

    /**
     * Gets query for [[Mfas]].
     *
     * @return \yii\db\ActiveQuery
     */
    public function getMfas()
    {
        return $this->hasMany(Mfa::class, ['user_id' => 'id']);
    }


    /**
     * column active ENUM value labels
     * @return string[]
     */
    public static function optsActive()
    {
        return [
            self::ACTIVE_YES => Yii::t('app', 'yes'),
            self::ACTIVE_NO => Yii::t('app', 'no'),
        ];
    }

    /**
     * column locked ENUM value labels
     * @return string[]
     */
    public static function optsLocked()
    {
        return [
            self::LOCKED_NO => Yii::t('app', 'no'),
            self::LOCKED_YES => Yii::t('app', 'yes'),
        ];
    }

    /**
     * column require_mfa ENUM value labels
     * @return string[]
     */
    public static function optsRequireMfa()
    {
        return [
            self::REQUIRE_MFA_NO => Yii::t('app', 'no'),
            self::REQUIRE_MFA_YES => Yii::t('app', 'yes'),
        ];
    }

    /**
     * column hide ENUM value labels
     * @return string[]
     */
    public static function optsHide()
    {
        return [
            self::HIDE_NO => Yii::t('app', 'no'),
            self::HIDE_YES => Yii::t('app', 'yes'),
        ];
    }

    /**
     * @return string
     */
    public function displayActive()
    {
        return self::optsActive()[$this->active];
    }

    /**
     * @return bool
     */
    public function isActiveYes()
    {
        return $this->active === self::ACTIVE_YES;
    }

    public function setActiveToYes()
    {
        $this->active = self::ACTIVE_YES;
    }

    /**
     * @return bool
     */
    public function isActiveNo()
    {
        return $this->active === self::ACTIVE_NO;
    }

    public function setActiveToNo()
    {
        $this->active = self::ACTIVE_NO;
    }

    /**
     * @return string
     */
    public function displayLocked()
    {
        return self::optsLocked()[$this->locked];
    }

    /**
     * @return bool
     */
    public function isLockedNo()
    {
        return $this->locked === self::LOCKED_NO;
    }

    public function setLockedToNo()
    {
        $this->locked = self::LOCKED_NO;
    }

    /**
     * @return bool
     */
    public function isLockedYes()
    {
        return $this->locked === self::LOCKED_YES;
    }

    public function setLockedToYes()
    {
        $this->locked = self::LOCKED_YES;
    }

    /**
     * @return string
     */
    public function displayRequireMfa()
    {
        return self::optsRequireMfa()[$this->require_mfa];
    }

    /**
     * @return bool
     */
    public function isRequireMfaNo()
    {
        return $this->require_mfa === self::REQUIRE_MFA_NO;
    }

    public function setRequireMfaToNo()
    {
        $this->require_mfa = self::REQUIRE_MFA_NO;
    }

    /**
     * @return bool
     */
    public function isRequireMfaYes()
    {
        return $this->require_mfa === self::REQUIRE_MFA_YES;
    }

    public function setRequireMfaToYes()
    {
        $this->require_mfa = self::REQUIRE_MFA_YES;
    }

    /**
     * @return string
     */
    public function displayHide()
    {
        return self::optsHide()[$this->hide];
    }

    /**
     * @return bool
     */
    public function isHideNo()
    {
        return $this->hide === self::HIDE_NO;
    }

    public function setHideToNo()
    {
        $this->hide = self::HIDE_NO;
    }

    /**
     * @return bool
     */
    public function isHideYes()
    {
        return $this->hide === self::HIDE_YES;
    }

    public function setHideToYes()
    {
        $this->hide = self::HIDE_YES;
    }
}
