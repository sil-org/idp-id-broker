<?php

namespace common\models;

use common\helpers\MySqlDateTime;
use common\helpers\Utils;
use common\components\Emailer;
use yii\helpers\ArrayHelper;
use yii\web\BadRequestHttpException;
use yii\web\ServerErrorHttpException;
use yii\web\TooManyRequestsHttpException;

/**
 * Class Reset
 * @package common\models
 * @method static Reset|null findOne(mixed $condition)
 */
class Reset extends ResetBase
{
    public const TYPE_PRIMARY    = 'primary';
    public const TYPE_SUPERVISOR = 'supervisor';
    public const TYPE_METHOD     = 'method';

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return ArrayHelper::merge(
            [
                [
                    ['uid'], 'default', 'value' => Utils::generateRandomString(),
                ],
                [
                    ['created'], 'default', 'value' => MySqlDateTime::now(),
                ],
                [
                    ['expires'], 'default', 'value' => self::calculateExpireTime(),
                ],
                [
                    ['type'], 'default', 'value' => self::TYPE_PRIMARY,
                ],
                [
                    ['attempts'], 'default', 'value' => 0,
                ],
                [
                    ['type'], 'in', 'range' => [
                        self::TYPE_PRIMARY, self::TYPE_METHOD, self::TYPE_SUPERVISOR,
                    ],
                    'message' => 'Reset type must be one of: ' . self::TYPE_PRIMARY . ', '
                        . self::TYPE_METHOD . ', ' . self::TYPE_SUPERVISOR . '.',
                ],
                [
                    ['email'], 'email',
                ],
            ],
            parent::rules()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function fields()
    {
        return [
            'uid',
            'methods' => function (self $model) {
                return $model->user->getMaskedMethods();
            },
        ];
    }

    /**
     * Find an existing Reset for the given user, or create a new one.
     * Always resets the type back to primary on find.
     *
     * @param User $user
     * @return Reset
     * @throws \Exception
     */
    public static function findOrCreate(User $user): Reset
    {
        $reset = $user->reset;
        if ($reset === null) {
            $reset = new Reset();
            $reset->user_id = $user->id;
            $reset->type = self::TYPE_PRIMARY;

            if (!$reset->save()) {
                throw new ServerErrorHttpException(
                    'Unable to create reset record: '
                    . json_encode($reset->getFirstErrors()),
                    1461173400
                );
            }
        } else {
            // Return existing reset but reset its type to primary
            $reset->setType(self::TYPE_PRIMARY);
        }

        return $reset;
    }

    /**
     * Track an attempt then send the verification email for the current type/method.
     *
     * @throws \Exception
     */
    public function send(): void
    {
        $this->trackAttempt('send');

        switch ($this->type) {
            case self::TYPE_PRIMARY:
                $this->sendPrimary();
                break;
            case self::TYPE_SUPERVISOR:
                $this->sendSupervisor();
                break;
            case self::TYPE_METHOD:
                $this->sendMethod();
                break;
            default:
                throw new \Exception('Reset is configured with unknown type.', 1456784825);
        }
    }

    /**
     * Send the reset email to the user's primary address.
     *
     * @throws \Exception
     */
    private function sendPrimary(): void
    {
        $this->sendEmail($this->user->getEmailAddress(), EmailLog::MESSAGE_TYPE_PASSWORD_RESET);
    }

    /**
     * Send the reset email on behalf of the user to their supervisor.
     *
     * @throws \Exception
     */
    private function sendSupervisor(): void
    {
        $supervisorEmail = $this->user->manager_email;
        if (empty($supervisorEmail)) {
            throw new \Exception('User does not have a supervisor on record.', 1461173406);
        }

        $this->sendOnBehalf($supervisorEmail);
    }

    /**
     * Send a reset email on behalf of the user to a third-party address.
     *
     * @param string $toAddress
     * @throws \Exception
     */
    private function sendOnBehalf(string $toAddress): void
    {
        $this->sendEmail(
            $toAddress,
            EmailLog::MESSAGE_TYPE_PASSWORD_RESET_ON_BEHALF,
            $this->user->getEmailAddress()
        );
    }

    /**
     * Send the reset email to the alternate method address.
     *
     * @param string|null $address Override address (for send-all). Defaults to $this->email.
     * @throws \Exception
     */
    private function sendMethod(?string $address = null): void
    {
        $toAddress = $address ?? $this->email;
        if (empty($toAddress)) {
            throw new \Exception('No email defined for reset method.', 1456608512);
        }

        $this->sendEmail(
            $toAddress,
            EmailLog::MESSAGE_TYPE_PASSWORD_RESET_ON_BEHALF,
            $this->user->getEmailAddress()
        );
    }

    /**
     * Send the reset email to all addresses: primary and all verified methods.
     *
     * @throws \Exception
     */
    private function sendAll(): void
    {
        $this->sendPrimary();

        $verifiedMethods = $this->user->getVerifiedMethodOptions();
        foreach ($verifiedMethods as $method) {
            $this->sendMethod($method->value);
        }
    }

    /**
     * Build the reset URL and dispatch an email via the Emailer component.
     *
     * @param string $toAddress
     * @param string $messageType EmailLog::MESSAGE_TYPE_PASSWORD_RESET or MESSAGE_TYPE_PASSWORD_RESET_ON_BEHALF
     * @param string|null $ccAddress Optional cc address (e.g. the user's email when sending on-behalf).
     * @throws \Exception
     */
    private function sendEmail(string $toAddress, string $messageType, ?string $ccAddress = null): void
    {
        if ($this->code === null) {
            $this->code = $this->createCode();
            if (!$this->save()) {
                throw new ServerErrorHttpException(
                    'Unable to save reset code: ' . json_encode($this->getFirstErrors()),
                    1461173401
                );
            }
        }

        $resetUrl = $this->buildResetUrl();

        $data = [
            'toAddress'   => $toAddress,
            'code'        => $this->code,
            'resetUrl'    => $resetUrl,
            'expireTime'  => Utils::getFriendlyDate($this->expires),
            'displayName' => $this->user->getDisplayName(),
        ];

        if (!empty($ccAddress)) {
            $data['ccAddress'] = $ccAddress;
        }

        /* @var Emailer $emailer */
        $emailer = \Yii::$app->emailer;
        $emailer->sendMessageTo($messageType, $this->user, $data);

        \Yii::warning([
            'action'     => 'reset send email',
            'user'       => $this->user->getEmailAddress(),
            'to_address' => $toAddress,
            'type'       => $messageType,
        ]);
    }

    /**
     * Build the password-reset URL that is included in the email.
     *
     * @return string
     */
    private function buildResetUrl(): string
    {
        $baseUrl = \Yii::$app->params['passwordResetUrl'] ?? '';
        return rtrim($baseUrl, '/') . '/password/reset/' . $this->uid . '/verify/' . $this->code;
    }

    /**
     * Generate a new random verification code.
     *
     * @return string
     */
    protected function createCode(): string
    {
        return Utils::generateRandomString(32);
    }

    /**
     * Check whether the user-provided code matches the stored code.
     * Tracks the attempt first.
     *
     * @param string $userProvided
     * @return bool
     * @throws TooManyRequestsHttpException
     * @throws ServerErrorHttpException
     */
    public function isUserProvidedCodeCorrect(string $userProvided): bool
    {
        $this->trackAttempt('verify');

        return hash_equals((string) $this->code, (string) $userProvided);
    }

    /**
     * Restart a reset: reset attempts, regenerate code, recalculate expiry, send.
     *
     * @throws \Exception
     */
    public function restart(): void
    {
        $this->attempts = 0;
        $this->code     = $this->createCode();
        $this->expires  = self::calculateExpireTime();

        if (!$this->save()) {
            throw new ServerErrorHttpException(
                'Unable to restart reset: ' . json_encode($this->getFirstErrors()),
                1461173402
            );
        }

        $this->send();
    }

    /**
     * Calculate the expiration datetime based on the configured lifetime.
     *
     * @return string MySQL datetime string
     * @throws ServerErrorHttpException
     */
    public static function calculateExpireTime(): string
    {
        $params = \Yii::$app->params;

        if (!isset($params['passwordReset']['lifetimeSeconds'])
            || !is_int($params['passwordReset']['lifetimeSeconds'])
        ) {
            throw new ServerErrorHttpException(
                'Application configuration for password reset lifetime is not set.',
                1458676224
            );
        }

        return MySqlDateTime::formatDateTime(time() + $params['passwordReset']['lifetimeSeconds']);
    }

    /**
     * @return bool
     * @throws ServerErrorHttpException
     */
    public function isExpired(): bool
    {
        $expiresTs = strtotime($this->expires);
        if ($expiresTs === false) {
            throw new ServerErrorHttpException('Unable to check reset expiration.', 1545341112);
        }

        return $expiresTs < time();
    }

    /**
     * @return bool
     */
    public function isDisabled(): bool
    {
        if ($this->disable_until !== null) {
            $disableUntilTs = strtotime($this->disable_until);
            if ($disableUntilTs == false) {
                return true;
            }

            return $disableUntilTs > time();
        }

        return false;
    }

    /**
     * Disable the reset until the configured disable-duration expires.
     *
     * @throws ServerErrorHttpException
     */
    public function disable(): void
    {
        $disableDuration = \Yii::$app->params['passwordReset']['disableDuration'] ?? 600;
        $this->disable_until = MySqlDateTime::formatDateTime(time() + (int) $disableDuration);

        if (!$this->save()) {
            throw new ServerErrorHttpException(
                'Unable to disable reset: ' . json_encode($this->getFirstErrors()),
                1461173403
            );
        }

        \Yii::warning([
            'action'        => 'disable reset',
            'reset_id'      => $this->id,
            'attempts'      => $this->attempts,
            'disable_until' => $this->disable_until,
            'user'          => $this->user->getEmailAddress(),
        ]);
    }

    /**
     * Re-enable the reset (clear disable_until and reset attempt counter).
     *
     * @throws ServerErrorHttpException
     */
    public function enable(): void
    {
        $this->disable_until = null;
        $this->attempts      = 0;

        if (!$this->save()) {
            throw new ServerErrorHttpException(
                'Unable to enable reset: ' . json_encode($this->getFirstErrors()),
                1461173404
            );
        }

        \Yii::warning([
            'action'   => 'enable reset',
            'reset_id' => $this->id,
            'user'     => $this->user->getEmailAddress(),
        ]);
    }

    /**
     * If the disable_until time has passed, enable the reset. Otherwise, if
     * the attempts limit is reached, disable it.
     *
     * @throws ServerErrorHttpException
     */
    public function enableOrDisableIfNeeded(): void
    {
        if ($this->disable_until !== null) {
            $disableUntilTs = strtotime($this->disable_until);
            if ($disableUntilTs == false) {
                throw new ServerErrorHttpException('Unable to check disable timeout.', 1463146757);
            }

            if ($disableUntilTs < time()) {
                $this->enable();
            }
        } else {
            $maxAttempts = \Yii::$app->params['passwordReset']['maxAttempts'] ?? 10;
            if ($this->attempts >= (int) $maxAttempts) {
                $this->disable();
            }
        }
    }

    /**
     * Change the reset type and, for method resets, look up and store the
     * target email address.
     *
     * @param string $type  One of TYPE_PRIMARY, TYPE_SUPERVISOR, TYPE_METHOD.
     * @param string|null $methodUid  Required when $type === TYPE_METHOD.
     * @throws BadRequestHttpException
     * @throws ServerErrorHttpException
     */
    public function setType(string $type, ?string $methodUid = null): void
    {
        if (in_array($type, [self::TYPE_PRIMARY, self::TYPE_SUPERVISOR], true)) {
            $this->type  = $type;
            $this->email = null;
        } elseif ($type === self::TYPE_METHOD) {
            if ($methodUid === null) {
                throw new BadRequestHttpException(
                    'methodId is required when type is "method".',
                    1462988984
                );
            }

            $method = Method::findOne(['uid' => $methodUid, 'verified' => 1]);
            if ($method === null || $method->user_id !== $this->user_id) {
                throw new BadRequestHttpException(
                    'Verified method not found.',
                    1462988985
                );
            }

            $this->type  = self::TYPE_METHOD;
            $this->email = $method->value;
        } else {
            throw new BadRequestHttpException(
                'Invalid reset type: ' . htmlspecialchars($type, ENT_QUOTES),
                1462989489
            );
        }

        if (!$this->save()) {
            throw new ServerErrorHttpException(
                'Unable to update reset type: ' . json_encode($this->getFirstErrors()),
                1461173405
            );
        }
    }

    /**
     * Increment the attempts counter, save, then enable or disable as needed.
     * Throws TooManyRequestsHttpException if the reset is (or becomes) disabled.
     *
     * @param string $action  Used in log messages ('send' or 'verify').
     * @throws TooManyRequestsHttpException
     * @throws ServerErrorHttpException
     */
    public function trackAttempt(string $action): void
    {
        $this->attempts++;

        if (!$this->save()) {
            throw new ServerErrorHttpException(
                'Unable to save attempt count: ' . json_encode($this->getFirstErrors()),
                1461173406
            );
        }

        $this->enableOrDisableIfNeeded();

        if ($this->isDisabled()) {
            \Yii::warning([
                'action'        => $action . ' reset',
                'reset_id'      => $this->id,
                'attempts'      => $this->attempts,
                'status'        => 'error',
                'error'         => 'Reset is currently disabled until ' . $this->disable_until,
                'user'          => $this->user->getEmailAddress(),
            ]);
            throw new TooManyRequestsHttpException();
        }
    }

    /**
     * Return a masked version of the email address currently in use for this reset.
     *
     * @return string
     * @throws \yii\web\BadRequestHttpException
     */
    public function getMaskedValue(): string
    {
        switch ($this->type) {
            case self::TYPE_PRIMARY:
                return Utils::maskEmail($this->user->getEmailAddress());
            case self::TYPE_SUPERVISOR:
                return Utils::maskEmail($this->user->manager_email ?? '');
            case self::TYPE_METHOD:
                return Utils::maskEmail($this->email ?? '');
            default:
                return '';
        }
    }
}
