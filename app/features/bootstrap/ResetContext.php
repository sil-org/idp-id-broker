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
    protected ?string $previousResetUuid = null;

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

    #[Given('a user that has an existing reset record')]
    public function aUserThatHasAnExistingResetRecord(): void
    {
        $user = User::findOne(['employee_id' => $this->tempEmployeeId]);
        Reset::create($user);
        Assert::notEmpty($user->reset);
    }
}
