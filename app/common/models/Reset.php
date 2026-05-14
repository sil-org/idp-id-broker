<?php

namespace common\models;

use common\components\Emailer;
use common\helpers\MySqlDateTime;
use Exception;
use Ramsey\Uuid\Uuid;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * Class Reset
 * @package common\models
 */
class Reset extends ResetBase
{
    public const string LIFETIME = '60 minutes';

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return ArrayHelper::merge(
            [
                [
                    'uuid', 'default', 'value' => Uuid::uuid4()->toString(),
                ],
                [
                    ['created'], 'default', 'value' => MySqlDateTime::now(),
                ],
                [
                    ['expires'], 'default', 'value' => static::calculateExpireTime(),
                ],
            ],
            parent::rules()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function fields(): array
    {
        // Prevent any properties of this class from being returned in a JSON response.
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return array_merge(parent::attributeLabels(), [
            'uuid' => Yii::t('app', 'UUID'),
        ]);
    }

    /**
     * Create a reset for the given user. If one exists, it will be reused.
     *
     * @param User $user
     * @return void
     * @throws Exception
     */
    public static function create(User $user): void
    {
        if ($user->isLocked()) {
            return;
        }

        $reset = self::findOne(['user_id' => $user->id]);

        if ($reset !== null && $reset->isExpired()) {
            if ($reset->delete() === false) {
                Yii::error("failed to delete reset for employee_id: " . $reset->user->employee_id);
            }
            $reset = null;
        }

        if ($reset === null) {
            $reset = new Reset();
            $reset->user_id = $user->id;

            if (!$reset->insert()) {
                Yii::error([
                    'action' => 'create reset',
                    'status' => 'error',
                    'user_id' => $user->id,
                    'employee_id' => $user->employee_id,
                    'errors' => $reset->getFirstErrors(),
                ]);
                throw new Exception(implode(", ", $reset->getFirstErrors()));
            }

            Yii::info([
                'action' => 'create reset',
                'status' => 'success',
                'user_id' => $user->id,
                'employee_id' => $user->employee_id,
                'reset_uuid' => $reset->uuid,
            ]);
        }

        $reset->send();
    }

    /**
     * Calculate and return the expiration datetime for a new reset.
     *
     * @return string
     */
    public static function calculateExpireTime(): string
    {
        return MySqlDateTime::relativeTime(static::LIFETIME);
    }

    /**
     * Check whether this reset has expired.
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        return strtotime($this->expires) <= time();
    }

    /**
     * Send the reset email
     * @throws Exception
     */
    protected function send(): void
    {
        Yii::info("sending reset to employee '{$this->user->employee_id}' primary email: " . $this->user->email);
        $this->sendPrimary();

        $methods = $this->user->getVerifiedMethodOptions();
        if (empty($methods) && !empty($this->user->manager_email)) {
            Yii::info("sending reset to employee '{$this->user->employee_id}' manager: {$this->user->manager_email}");
            $this->sendManager();
            return;
        }

        Yii::info("sending reset to employee '{$this->user->employee_id}' password reset emails");
        $this->sendMethods($methods);
    }

    /**
     * Send reset email to the user's primary email.
     * @return void
     */
    protected function sendPrimary(): void
    {
        /* @var $emailer Emailer */
        $emailer = Yii::$app->emailer;
        $emailer->sendMessageTo(EmailLog::MESSAGE_TYPE_RESET_SELF, $this->user, $this->dataForEmail());
    }

    /**
     * Send reset email to the user's manager_email.
     * @throws Exception
     */
    protected function sendManager(): void
    {
        if (empty($this->user->manager_email)) {
            throw new Exception('User does not have manager_email', 1461173406);
        }

        /* @var $emailer Emailer */
        $emailer = Yii::$app->emailer;
        $emailer->sendMessageTo(
            EmailLog::MESSAGE_TYPE_RESET_ON_BEHALF,
            null,
            $this->dataForEmail($this->user->manager_email),
        );
    }

    /**
     * Send reset email to the user's password recovery emails.
     * @param Method[] $methods
     * @return void
     */
    protected function sendMethods(array $methods): void
    {
        foreach ($methods as $method) {
            /* @var $emailer Emailer */
            $emailer = Yii::$app->emailer;
            $emailer->sendMessageTo(
                EmailLog::MESSAGE_TYPE_RESET_SELF,
                // Don't log this as a user email because the Emailer won't log the email address in that case.
                // If non_user_address validation is changed to allow this, this should be changed to `$this->user`.
                null,
                $this->dataForEmail($method->value),
            );
        }
    }

    /**
     * Get the data items needed to render the email template. Provide the toAddress if the email is being sent
     * to an address other than the user's primary email.
     * @param string|null $toAddress
     * @return array
     */
    protected function dataForEmail(?string $toAddress = null): array
    {
        $resetUrl = sprintf('%s/password/reset/%s/verify/0', \Yii::$app->params['passwordProfileUrl'], $this->uuid);
        $data = [
            'resetUrl' => $resetUrl,
            'lifetime' => self::LIFETIME,
            'displayName' => $this->user->getDisplayName(),
            'firstName' => $this->user->first_name,
        ];
        if (!empty($toAddress)) {
            $data['toAddress'] = $toAddress;
        }
        return $data;
    }

}
