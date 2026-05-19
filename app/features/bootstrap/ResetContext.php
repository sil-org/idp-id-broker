<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use common\helpers\MySqlDateTime;
use common\models\Reset;
use Webmozart\Assert\Assert;

class ResetContext extends UnitTestsContext
{
    /** @var string|null */
    protected ?string $previousResetUuid = null;

    /** @var Reset|null */
    protected ?Reset $reset = null;

    protected int $emailCount = 0;

    #[Given('the user has a password recovery email :arg1')]
    public function theUserHasAPasswordRecoveryEmail($arg1): void
    {
        $this->createMethod($arg1, 1, $this->tempUser);
    }

    #[Given('there is a user in the database with a valid password reset')]
    public function thereIsAUserInTheDatabaseWithAValidPasswordReset(): void
    {
        $this->thereIsAUserInTheDatabase();
        $this->reset = new Reset();
        $this->reset->user_id = $this->tempUser->id;
        Assert::true($this->reset->save(), 'failed to save a new Reset');
    }

    #[Given('there is a user in the database with an expired password reset')]
    public function thereIsAUserInTheDatabaseWithAnExpiredPasswordReset(): void
    {
        $this->thereIsAUserInTheDatabase();
        $this->reset = new Reset();
        $this->reset->user_id = $this->tempUser->id;
        $this->reset->expires = MySqlDateTime::now();
        Assert::true($this->reset->save(), 'failed to save a new Reset');
    }

    #[When('the user requests a password reset')]
    public function theUserRequestsAPasswordReset(): void
    {
        $this->emailCount = 0;
        $this->fakeEmailer->forgetFakeEmailsSent();
        Reset::create($this->tempUser);
    }

    #[Then('a reset record exists for the user')]
    public function aResetRecordExistsForTheUser(): void
    {
        $this->reset = Reset::findOne(['user_id' => $this->tempUser->id]);
        Assert::notNull($this->reset, 'No reset record found');
    }

    #[Then('the reset record has a non-empty UUID')]
    public function theResetRecordHasANonEmptyUuid(): void
    {
        Assert::notEmpty($this->reset->uuid);

        $this->previousResetUuid = $this->reset->uuid;
    }

    #[Then('the reset record has an expiry in the future')]
    public function theResetRecordHasAnExpiryInTheFuture(): void
    {
        Assert::greaterThan(
            $this->reset->expires,
            date('Y-m-d H:i:s'),
            'Expires should be in the future: ' . $this->reset->expires
        );
    }

    #[Then('a :template email should be sent to their primary email')]
    public function aTemplateEmailShouldBeSentToTheirPrimaryEmail($template): void
    {
        $address = $this->tempUser->email;
        $emails = $this->fakeEmailer->getFakeEmailsOfTypeSentToUser($template, $address, $this->tempUser);

        Assert::greaterThan(count($emails), 0, sprintf('Did not find any %s emails sent to %s.', $template, $address));
        $this->emailCount++;
    }

    #[Then('a :template email should be sent to :email')]
    public function aTemplateEmailShouldBeSentToEmail($template, $email): void
    {
        $emails = $this->fakeEmailer->getFakeEmailsOfTypeSentToUser($template, $email, $this->tempUser);

        Assert::greaterThan(count($emails), 0, sprintf('Did not find any %s emails sent to %s.', $template, $email));
        $this->emailCount++;
    }

    #[Then('no other emails should be sent')]
    public function noOtherEmailsShouldBeSent(): void
    {
        $receiveCount = count($this->fakeEmailer->getFakeEmailsSent());
        Assert::eq(
            $receiveCount,
            $this->emailCount,
            "Received more emails than expected. Got $receiveCount, expected $this->emailCount.",
        );
    }
}
