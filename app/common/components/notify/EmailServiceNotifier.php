<?php

namespace common\components\notify;

use common\components\EmailClient;
use common\sync\SyncUser;
use InvalidArgumentException;
use yii\base\Component;

/**
 * NOTE: If you add public methods to this class, first add them to the
 *       NotifierInterface.
 */
class EmailServiceNotifier extends Component implements NotifierInterface
{
    /** @var EmailClient */
    protected $emailClient = null;

    /**
     * What address to send the email to.
     * @var string
     */
    public $emailTo;

    /**
     * The name of the organization.
     * @var string
     */
    public $organizationName;

    public function init()
    {
        $this->assertConfigIsValid();
        parent::init();
    }

    protected function assertConfigIsValid()
    {
        $requiredParams = [
            'accessToken',
            'assertValidIp',
            'baseUrl',
            'trustedIpRanges',
        ];

        foreach ($requiredParams as $param) {
            if (! isset($this->emailServiceConfig[$param])) {
                throw new InvalidArgumentException(
                    'Missing ' . $param . ' value (for EmailServiceNotifier).',
                    1502820156
                );
            }
        }
    }

    /**
     * @return EmailClient
     */
    protected function getEmailClient()
    {
        if ($this->emailClient === null) {
            $this->emailClient = new EmailClient();
        }

        return $this->emailClient;
    }

    /**
     * {@inheritdoc}
     */
    public function sendMissingEmailNotice(array $users)
    {
        // preserve the "missing email notification not needed" capability
        // when the Notifier is not NullNotifier
        if (empty($this->emailTo)) {
            return;
        }

        $templateVars = [
            'organizationName' => $this->organizationName,
            'users' => $users,
        ];
        $htmlBody = \Yii::$app->view->render(
            '@common/mail/missing-email.html.php',
            $templateVars
        );
        $textBody = \Yii::$app->view->render(
            '@common/mail/missing-email.text.php',
            $templateVars
        );

        $numUsers = count($users);
        $this->getEmailClient()->email([
            'to_address' => $this->emailTo,
            'subject' => sprintf(
                'Email address missing for %s %s user%s',
                $numUsers,
                $this->organizationName,
                ($numUsers === 1 ? '' : 's')
            ),
            'html_body' => $htmlBody,
            'text_body' => $textBody,
        ]);
    }

    /**
     * {@inheritdoc}
     * @throws \Exception if no email address is available
     */
    public function sendNewUserNotice(SyncUser $user)
    {
        $templateVars = [
            'organizationName' => $this->organizationName,
            'user' => $user,
        ];
        $htmlBody = \Yii::$app->view->render(
            '@common/mail/new-user.html.php',
            $templateVars
        );
        $textBody = \Yii::$app->view->render(
            '@common/mail/new-user.text.php',
            $templateVars
        );

        $name = empty($user->getDisplayName())
            ? $user->getFirstName() . ' ' . $user->getLastName()
            : $user->getDisplayName();
        $this->getEmailClient()->email([
            'to_address' => $this->getEmailTo($user),
            'subject' => sprintf(
                'Created %s IDP user for %s [do not reply]',
                $this->organizationName,
                $name
            ),
            'html_body' => $htmlBody,
            'text_body' => $textBody,
        ]);
    }

    /**
     * @throws \Exception if no email address is available
     */
    protected function getEmailTo(SyncUser $user): string
    {
        try {
            return $user->getHRContactEmail();
        } catch (\Exception $e) {
            if (! empty($this->emailTo)) {
                return $this->emailTo;
            }
            throw new \Exception('no notifier email found for user ' . $user->getEmployeeId());
        }
    }
}
