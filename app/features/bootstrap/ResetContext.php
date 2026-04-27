<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Step\Given;
use Behat\Step\Then;
use common\models\Reset;
use common\models\User;
use Webmozart\Assert\Assert;

class ResetContext extends \FeatureContext
{
    /** @var string|null */
    protected ?string $previousResetUid = null;

    /** @var Reset|null */
    protected ?Reset $reset = null;

    #[Then('a reset record exists for employee :employeeId')]
    public function aResetRecordExistsForEmployee(string $employeeId): void
    {
        $user = User::findOne(['employee_id' => $employeeId]);
        Assert::notNull($user, 'User not found for employee_id ' . $employeeId);

        $this->reset = Reset::findOne(['user_id' => $user->id]);
        Assert::notNull($this->reset, 'No reset record found for employee_id ' . $employeeId);
    }

    #[Then('the reset record has a non-empty uid')]
    public function theResetRecordHasANonEmptyUid(): void
    {
        Assert::notEmpty($this->reset->uid);

        $this->previousResetUid = $this->reset->uid;
    }

    #[Then('the reset record has a non-empty code')]
    public function theResetRecordHasANonEmptyCode(): void
    {
        Assert::notEmpty($this->reset->code);
    }

    #[Then('the response uid matches the previously created reset')]
    public function theResponseUidMatchesThePreviouslyCreatedReset(): void
    {
        $resBody = $this->getResponseBody();
        Assert::keyExists($resBody, 'uid');
        Assert::eq(
            $resBody['uid'],
            $this->previousResetUid,
            sprintf(
                'Expected reset uid "%s" but got "%s"',
                $this->previousResetUid,
                $resBody['uid']
            )
        );
    }

    #[Given('a user that has an existing reset record')]
    public function aUserThatHasAnExistingResetRecord(): void
    {
        $user = User::findOne(['employee_id' => $this->tempEmployeeId]);
        Reset::create($user);
        Assert::notEmpty($user->reset);
    }
}
