<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Gherkin\Node\TableNode;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use common\helpers\MySqlDateTime;
use common\models\Method;
use common\models\Reset;
use common\models\User;
use Webmozart\Assert\Assert;

class ResetContext extends \FeatureContext
{
    /** @var Reset|null */
    protected $tempReset = null;

    // -------------------------------------------------------------------------
    // Given / setup steps
    // -------------------------------------------------------------------------

    #[Given('a reset exists for user :employeeId')]
    public function aResetExistsForUser(string $employeeId): void
    {
        $user = User::findOne(['employee_id' => $employeeId]);
        Assert::notNull($user, "No user found with employee_id=$employeeId.");

        $reset = new Reset([
            'user_id' => $user->id,
            'type'    => Reset::TYPE_PRIMARY,
        ]);

        Assert::true(
            $reset->save(),
            'Failed to create reset: ' . implode(', ', $reset->getFirstErrors())
        );

        $this->tempReset = $reset;
        $this->tempUid   = $reset->uid;
    }

    #[Given('a reset exists for user :employeeId with a known code')]
    public function aResetExistsForUserWithAKnownCode(string $employeeId): void
    {
        $this->aResetExistsForUser($employeeId);

        // Use a short code that matches exactly what the validate test scenarios send.
        $this->tempReset->code = 'RESETCODE1';
        Assert::true(
            $this->tempReset->save(),
            'Failed to set reset code: ' . implode(', ', $this->tempReset->getFirstErrors())
        );
    }

    #[Given('a reset exists for user :employeeId that is expired')]
    public function aResetExistsForUserThatIsExpired(string $employeeId): void
    {
        $this->aResetExistsForUserWithAKnownCode($employeeId);

        $this->tempReset->expires = MySqlDateTime::relativeTime('-1 hour');
        Assert::true(
            $this->tempReset->save(),
            'Failed to expire reset: ' . implode(', ', $this->tempReset->getFirstErrors())
        );
    }

    #[Given('that user has a manager_email of :managerEmail')]
    public function thatUserHasAManagerEmailOf(string $managerEmail): void
    {
        $user = User::findOne(['employee_id' => '123']);
        Assert::notNull($user, 'User with employee_id=123 not found.');
        $user->scenario = User::SCENARIO_UPDATE_USER;
        $user->manager_email = $managerEmail;
        Assert::true(
            $user->save(),
            'Failed to set manager_email: ' . implode(', ', $user->getFirstErrors())
        );
    }

    #[Given('/^user with employee id (.*) has (?:a|an) (verified|unverified) Method "(.*)"$/')]
    public function userHasAMethod(string $employeeId, string $verified, string $value): void
    {
        $user = User::findOne(['employee_id' => $employeeId]);
        Assert::notEmpty($user, "Unable to find user with employee_id=$employeeId.");

        $method = new Method([
            'user_id'  => $user->id,
            'verified' => $verified === 'verified' ? 1 : 0,
            'value'    => $value,
        ]);
        Assert::true(
            $method->save(),
            'Failed to add Method record: ' . implode(', ', $method->getFirstErrors())
        );

        $this->tempUid = $method->uid;
    }

    #[Given('I also provide the method id in the request')]
    public function iAlsoProvideTheMethodIdInTheRequest(): void
    {
        $user = User::findOne(['employee_id' => '123']);
        Assert::notNull($user, 'User with employee_id=123 not found.');
        $method = Method::findOne(['user_id' => $user->id, 'verified' => 1]);
        Assert::notNull($method, 'No verified method found for user 123.');

        $this->setRequestBody('id', $method->uid);

        // Restore tempUid to the reset's uid (it was overwritten above by the
        // Method creation step that sets tempUid to the method's uid).
        if ($this->tempReset !== null) {
            $this->tempUid = $this->tempReset->uid;
        }
    }

    #[Given('I request "/reset" be created with username :username')]
    public function iRequestResetBeCreatedWithUsername(string $username): void
    {
        $this->iProvideTheFollowingValidData(new TableNode([
            ['property', 'value'],
            ['username', $username],
        ]));
        $this->iRequestTheResourceBe('/reset', self::CREATED);
    }

    // -------------------------------------------------------------------------
    // When / action steps
    // -------------------------------------------------------------------------

    #[When('I submit too many incorrect codes for the reset')]
    public function iSubmitTooManyIncorrectCodesForTheReset(): void
    {
        $maxAttempts = \Yii::$app->params['passwordReset']['maxAttempts'] ?? 10;

        for ($i = 0; $i <= (int) $maxAttempts; $i++) {
            // Use the inherited method to properly set FeatureContext's private
            // $reqBody and then call the inherited request method which also
            // sets FeatureContext's private $response / $resBody.
            $this->iProvideTheFollowingValidData(new TableNode([
                ['property', 'value'],
                ['code', 'WRONGCODE1QRSTUVWXYZ1234567890AB'],
            ]));
            $this->iSendAToWithAValidUid('PUT', '/reset/{uid}/validate');
        }
    }

    #[When('I send a "GET" to :resource with no uid substitution')]
    public function iSendAGetWithNoUidSubstitution(string $resource): void
    {
        // Delegate to the inherited step. If $resource has no {uid} placeholder,
        // str_replace is a no-op and the URL is sent as-is.
        $this->iSendAToWithAValidUid('GET', $resource);
    }

    // -------------------------------------------------------------------------
    // Then / assertion steps
    // -------------------------------------------------------------------------

    #[Then('the response should contain a :property property')]
    public function theResponseShouldContainAProperty(string $property): void
    {
        $resBody = $this->getResponseBody();
        Assert::keyExists(
            $resBody,
            $property,
            sprintf(
                'Response does not contain "%s". Body: %s',
                $property,
                var_export($resBody, true)
            )
        );
    }

    #[Then('a reset record exists for user :employeeId')]
    public function aResetRecordExistsForUser(string $employeeId): void
    {
        $user = User::findOne(['employee_id' => $employeeId]);
        Assert::notNull($user);

        $reset = Reset::findOne(['user_id' => $user->id]);
        Assert::notNull(
            $reset,
            "No reset record found for user with employee_id=$employeeId."
        );

        $this->tempReset = $reset;
        $this->tempUid   = $reset->uid;
    }

    #[Then('a reset record still exists for user :employeeId')]
    public function aResetRecordStillExistsForUser(string $employeeId): void
    {
        $this->aResetRecordExistsForUser($employeeId);
    }

    #[Then('only one reset record exists for user :employeeId')]
    public function onlyOneResetRecordExistsForUser(string $employeeId): void
    {
        $user = User::findOne(['employee_id' => $employeeId]);
        Assert::notNull($user);

        $count = Reset::find()->where(['user_id' => $user->id])->count();
        Assert::eq(
            1,
            (int) $count,
            "Expected exactly 1 reset record for user $employeeId, found $count."
        );
    }

    #[Then('the reset record no longer exists')]
    public function theResetRecordNoLongerExists(): void
    {
        Assert::notNull($this->tempUid, 'No tempUid set – cannot check whether reset was deleted.');

        $reset = Reset::findOne(['uid' => $this->tempUid]);
        Assert::null(
            $reset,
            "Reset record with uid={$this->tempUid} still exists but should have been deleted."
        );
    }
}
