<?php

namespace Sil\SilIdBroker\Behat\Context;

use common\models\Reset;
use common\models\User;
use Webmozart\Assert\Assert;
use Behat\Step\Given;
use Behat\Step\Then;

class ResetContext extends \FeatureContext
{
    /** @var string|null */
    protected $previousResetUid = null;

    #[Then('a reset record exists for employee :employeeId')]
    public function aResetRecordExistsForEmployee(string $employeeId): void
    {
        $user = User::findOne(['employee_id' => $employeeId]);
        Assert::notNull($user, 'User not found for employee_id ' . $employeeId);

        $reset = Reset::findOne(['user_id' => $user->id]);
        Assert::notNull($reset, 'No reset record found for employee_id ' . $employeeId);
    }

    #[Then('the reset record has a non-empty uid')]
    public function theResetRecordHasANonEmptyUid(): void
    {
        Assert::keyExists($this->resBody, 'uid');
        Assert::notEmpty($this->resBody['uid']);

        $this->previousResetUid = $this->resBody['uid'];
    }

    #[Then('the reset record has a non-empty code')]
    public function theResetRecordHasANonEmptyCode(): void
    {
        Assert::keyExists($this->resBody, 'code');
        Assert::notEmpty($this->resBody['code']);
    }

    #[Then('the response uid matches the previously created reset')]
    public function theResponseUidMatchesThePreviouslyCreatedReset(): void
    {
        Assert::keyExists($this->resBody, 'uid');
        Assert::eq(
            $this->resBody['uid'],
            $this->previousResetUid,
            sprintf(
                'Expected reset uid "%s" but got "%s"',
                $this->previousResetUid,
                $this->resBody['uid']
            )
        );
    }
}
