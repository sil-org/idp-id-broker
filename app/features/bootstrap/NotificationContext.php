<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Context\Context;
use common\components\notify\FakeEmailNotifier;
use common\sync\User;
use Exception;
use Webmozart\Assert\Assert;

/**
 * Defines application features from the specific context.
 */
class NotificationContext implements Context
{
    /** @var array */
    private $users;

    public function __construct()
    {
        $this->notifier = new FakeEmailNotifier();
    }

    /**
     * @Given at least one user has no email address
     */
    public function atLeastOneUserHasNoEmailAddress()
    {
        $this->users[] = new User(['employee_id' => 1]);
    }

    /**
     * @When I call the sendMissingEmailNotice function
     */
    public function iCallTheSendmissingemailnoticeFunction()
    {
        $this->notifier->sendMissingEmailNotice($this->users);
    }

    /**
     * @Then an email is sent
     */
    public function anEmailIsSent()
    {
        Assert::notEmpty($this->notifier->emailsSent);
    }

    /**
     * @Given a specific user exists in the ID Store without an email address
     */
    public function aSpecificUserExistsInTheIdStoreWithoutAnEmailAddress()
    {
        $tempIdStoreUserInfo = [
            'employeenumber' => '10001',
            'displayname' => 'Person One',
            'username' => 'person_one',
            'firstname' => 'Person',
            'lastname' => 'One',
        ];

        $this->makeFakeIdStoreWithUser($tempIdStoreUserInfo);
    }

    /**
     * @Then the email subject contains :subject
     */
    public function theEmailSubjectContains($subject)
    {
        Assert::notEmpty($this->notifier->findEmailBySubject($subject));
    }

    /**
     * @Then an email is not sent
     */
    public function anEmailIsNotSent()
    {
        Assert::isEmpty($this->notifier->emailsSent);
    }

    /**
     * @Then an email with subject :subject is not sent
     */
    public function anEmailWithSubjectIsNotSent($subject)
    {
        Assert::isEmpty($this->notifier->findEmailBySubject($subject));
    }

    /**
     * @Then a :subject email is sent to the user's HR contact
     */
    public function aEmailIsSentToTheUsersHrContact($subject)
    {
        $email = $this->notifier->findEmailBySubject($subject);
        Assert::notEmpty($email, "No email was found with the subject: " . $subject);

        $user = $this->idStore->getActiveUser($this->tempEmployeeId);
        Assert::contains(
            $user->getHRContactEmail(),
            $email['to_address'],
            "Email was not sent to " . $user->getHRContactEmail()
        );
    }

    /**
     * @Given new user email notifications are :enabledOrDisabled
     * @throws Exception
     */
    public function newUserEmailNotificationsAre($enabledOrDisabled)
    {
        if ($enabledOrDisabled === "enabled") {
            $this->enableNewUserNotifications = true;
        } elseif ($enabledOrDisabled === "disabled") {
            $this->enableNewUserNotifications = false;
        } else {
            throw new Exception("invalid option '$enabledOrDisabled' for email new user email notifications");
        }
    }
}
