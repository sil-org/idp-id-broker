<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use common\helpers\MySqlDateTime;
use common\models\Method;
use common\models\Reset;
use common\models\User;
use GuzzleHttp\Client;
use Sil\PhpEnv\Env;
use Webmozart\Assert\Assert;

class ResetContext extends \FeatureContext
{
    /** @var Reset|null */
    protected $tempReset = null;

    /** @var string|null The uid of the last reset that was created/found */
    protected $tempResetCode = null;

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

        $knownCode = 'RESETCODE1' . str_repeat('0', 22); // 32-char code
        $this->tempReset->code = $knownCode;
        Assert::true(
            $this->tempReset->save(),
            'Failed to set reset code: ' . implode(', ', $this->tempReset->getFirstErrors())
        );
        $this->tempResetCode = $knownCode;
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
        $user->scenario = \common\models\User::SCENARIO_UPDATE_USER;
        $user->manager_email = $managerEmail;
        Assert::true(
            $user->save(),
            'Failed to set manager_email: ' . implode(', ', $user->getFirstErrors())
        );
    }

    #[Given('that user has an email of :email')]
    public function thatUserHasAnEmailOf(string $email): void
    {
        // This step just documents the email we use for login; the user was
        // already created in the Background using iAddAUserWithAnOf (john_smith
        // for employee_id 123, or "test_user" for employee_id 456 in specific
        // scenarios). The step is effectively a no-op because iAddAUserWithAnOf
        // already sets whatever is given.
    }

    #[Given('I also provide the method id in the request')]
    public function iAlsoProvideTheMethodIdInTheRequest(): void
    {
        // Find the verified method belonging to user 123
        $user = User::findOne(['employee_id' => '123']);
        Assert::notNull($user);
        $method = Method::findOne(['user_id' => $user->id, 'verified' => 1]);
        Assert::notNull($method, 'No verified method found for user 123.');

        $this->reqBody['id'] = $method->uid;
    }

    #[Given('I request "/reset" be created with username :username')]
    public function iRequestResetBeCreatedWithUsername(string $username): void
    {
        $this->reqBody['username'] = $username;
        $this->iRequestTheResourceBe('/reset', self::CREATED);
    }

    // -------------------------------------------------------------------------
    // When / action steps
    // -------------------------------------------------------------------------

    #[When('I submit too many incorrect codes for the reset')]
    public function iSubmitTooManyIncorrectCodesForTheReset(): void
    {
        $maxAttempts = \Yii::$app->params['passwordReset']['maxAttempts'] ?? 10;
        $hostname    = Env::get('TEST_SERVER_HOSTNAME');

        $client = new Client([
            'base_uri'    => "http://$hostname",
            'http_errors' => false,
            'headers'     => $this->reqHeaders,
        ]);

        for ($i = 0; $i <= (int) $maxAttempts; $i++) {
            $this->response = $client->put(
                '/reset/' . $this->tempUid . '/validate',
                ['json' => ['code' => 'WRONG' . str_repeat('X', 27)]]
            );
        }

        $this->resBody = json_decode(
            $this->response->getBody()->getContents(),
            true
        ) ?? [];
    }

    #[When('I send a "GET" to :resource with no uid substitution')]
    public function iSendAGetWithNoUidSubstitution(string $resource): void
    {
        $hostname = Env::get('TEST_SERVER_HOSTNAME');
        $client   = new Client([
            'base_uri'    => "http://$hostname",
            'http_errors' => false,
            'headers'     => $this->reqHeaders,
        ]);

        $this->response = $client->get($resource);
        $this->resBody  = json_decode(
            $this->response->getBody()->getContents(),
            true
        ) ?? [];
    }

    // -------------------------------------------------------------------------
    // Then / assertion steps
    // -------------------------------------------------------------------------

    #[Then('the response should contain a :property property')]
    public function theResponseShouldContainAProperty(string $property): void
    {
        Assert::keyExists(
            $this->resBody,
            $property,
            sprintf(
                'Response does not contain "%s". Body: %s',
                $property,
                var_export($this->resBody, true)
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
            $count,
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
