<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use common\models\Reset;
use Webmozart\Assert\Assert;

class ResetContext extends UnitTestsContext
{
    /** @var string|null */
    protected ?string $previousResetUuid = null;

    /** @var Reset|null */
    protected ?Reset $reset = null;

    #[Given('the user has a password recovery email :arg1')]
    public function theUserHasAPasswordRecoveryEmail($arg1): void
    {
        $this->createMethod($arg1, 1, $this->tempUser);
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

    #[When('the user requests a password reset')]
    public function theUserRequestsAPasswordReset(): void
    {
        Reset::create($this->tempUser);
    }

    #[Then('a :template email should be sent to their primary email')]
    public function aTemplateEmailShouldBeSentToTheirPrimaryEmail($template): void
    {
        $address = $this->tempUser->email;
        $emails = $this->fakeEmailer->getFakeEmailsOfTypeSentToUser($template, $address, $this->tempUser);

        Assert::greaterThan(count($emails), 0, sprintf('Did not find any %s emails sent to %s.', $template, $address));
    }

    #[Then('a :template email should be sent to :email')]
    public function aTemplateEmailShouldBeSentToEmail($template, $email): void
    {
        $emails = $this->fakeEmailer->getFakeEmailsOfTypeSentToUser($template, $email, $this->tempUser);

        Assert::greaterThan(count($emails), 0, sprintf('Did not find any %s emails sent to %s.', $template, $email));
    }

}
