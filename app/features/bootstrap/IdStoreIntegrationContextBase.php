<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Context\Context;
use common\components\adapters\IdStoreInterface;
use common\sync\SyncUser;
use Webmozart\Assert\Assert;

/**
 * Defines application features from the specific context.
 */
class IdStoreIntegrationContextBase implements Context
{
    /** @var IdStoreInterface */
    protected $idStore;

    protected $activeEmployeeId;
    protected $lastSyncedValues = [];
    protected $result;

    public function __construct()
    {
        require_once __DIR__ . '/../../vendor/yiisoft/yii2/Yii.php';
    }

    /**
     * @Then I should get back information about that user
     */
    public function iShouldGetBackInformationAboutThatUser()
    {
        Assert::notNull($this->result, sprintf(
            'Did not find user %s. Are you sure that they exist and are active?',
            var_export($this->activeEmployeeId, true)
        ));
        Assert::true($this->result instanceof SyncUser);
    }

    /**
     * @When I ask the ID Store for all active users
     */
    public function iAskTheIdStoreForAllActiveUsers()
    {
        $this->result = $this->idStore->getAllActiveUsers();
    }

    /**
     * @Then I should get back a list of information about active users
     */
    public function iShouldGetBackAListOfInformationAboutActiveUsers()
    {
        Assert::notNull($this->result);
        Assert::notEmpty($this->result);
        Assert::true(is_array($this->result));
        foreach ($this->result as $user) {
            Assert::true($user instanceof SyncUser);
        }
    }

    /**
     * @When I ask the ID Store for all users changed since a specific point in time
     */
    public function iAskTheIdStoreForAllUsersChangedSinceASpecificPointInTime()
    {
        $this->result = $this->idStore->getUsersChangedSince(strtotime('-2 months'));
    }

    /**
     * @Then I should get back a list of information about changed users
     */
    public function iShouldGetBackAListOfInformationAboutChangedUsers()
    {
        Assert::notNull($this->result);
        Assert::notEmpty($this->result);
        Assert::true(is_array($this->result));
        foreach ($this->result as $user) {
            Assert::true($user instanceof SyncUser);
        }
    }

    /**
     * @Given I have a record of each user's last-synced value
     */
    public function iHaveARecordOfEachUsersLastSyncedValue()
    {
        $this->lastSyncedValues = $this->getAttributeForEachUser('last_synced');
        Assert::notEmpty($this->lastSyncedValues);
    }

    /**
     * @Given those last-synced values are all in the past or empty
     */
    public function thoseLastSyncedValuesAreAllInThePastOrEmpty()
    {
        sleep(1);
        $nowTimestamp = time();
        foreach ($this->lastSyncedValues as $lastSyncedValue) {
            if (! empty($lastSyncedValue)) {
                $lastSyncedTimestamp = strtotime($lastSyncedValue);
                Assert::notFalse($lastSyncedTimestamp);
                Assert::lessThan($nowTimestamp, $lastSyncedTimestamp);
            }
        }
    }

    /**
     * @Then NONE of the users' last-synced values should have changed
     */
    public function noneOfTheUsersLastSyncedValuesShouldHaveChanged()
    {
        $newLastSyncedValues = $this->getAttributeForEachUser('last_synced');
        foreach ($this->lastSyncedValues as $employeeId => $oldLastSyncedValue) {
            Assert::eq(
                $oldLastSyncedValue,
                $newLastSyncedValues[$employeeId]
            );
        }
    }

    /**
     * Get a specific attribute's value for each user. The keys will be the
     * Employee ID, and the values will be the attribute's value.
     *
     * EXAMPLE:
     * Calling `getAttributeForEachUser('last_synced')` will return an
     * array similar to this:
     *
     *     [
     *         123 => '2018-12-21T20:53:14+00:00',
     *         1234 => '2018-12-21T20:53:14+00:00',
     *     ]
     *
     *
     * @param string $attributeName The name of the desired attribute.
     * @return array<string,string>
     * @throws \Exception
     */
    protected function getAttributeForEachUser(string $attributeName): array
    {
        // NOTE: Override this method in the applicable subclasses.
        $this->throwNotYetImplementedException(__FUNCTION__, static::class);
    }

    /**
     * Throw an exception that explains which class lacks which function.
     *
     * @param $functionName
     * @param $className
     * @throws \Exception
     */
    protected function throwNotYetImplementedException($functionName, $className)
    {
        throw new Exception(sprintf(
            'You have not yet implemented the %s() function on the %s class.',
            $functionName,
            $className
        ));
    }


    /**
     * Get specific attributes for each user. The keys will be the Employee ID,
     * and the values will be an array attribute name/value pairs.
     *
     * EXAMPLE:
     * Calling `getAttributesForEachUser(['email', 'active'])` will return an
     * array similar to this:
     *
     *     [
     *         123 => [
     *             'email' => 'test_user@example.com',
     *             'active' => 'yes',
     *         ],
     *         1234 => [
     *             'email' => 'another_user@example.com',
     *             'active' => 'no',
     *         ],
     *     ]
     *
     * @param string[] $attributeNames The names of the desired attributes.
     * @return array<mixed,array>
     * @throws \Exception
     */
    protected function getAttributesForEachUser(array $attributeNames)
    {
        // NOTE: Override this method in the applicable subclasses.
        $this->throwNotYetImplementedException(__FUNCTION__, static::class);
    }

    /**
     * @Then ONLY that user's last-synced value should have changed
     */
    public function onlyThatUsersLastSyncedValueShouldHaveChanged()
    {
        $newLastSyncedValues = $this->getAttributeForEachUser('last_synced');
        Assert::greaterThan(
            1,
            count($newLastSyncedValues),
            "To prove that other users' last-synced dates did NOT change, this test requires more than 1 user."
        );

        foreach ($newLastSyncedValues as $employeeId => $newLastSyncedValue) {
            if ($employeeId == $this->activeEmployeeId) {
                Assert::notEq(
                    $this->lastSyncedValues[$employeeId],
                    $newLastSyncedValues[$employeeId]
                );
            } else {
                Assert::eq(
                    $this->lastSyncedValues[$employeeId],
                    $newLastSyncedValues[$employeeId]
                );
            }
        }
    }

    /**
     * @When I update the last-synced value for every user
     */
    public function iUpdateTheLastSyncedValueForEveryUser()
    {
        $allEmployeeIds = array_keys($this->lastSyncedValues);
        $this->idStore->updateSyncDatesIfSupported($allEmployeeIds);
    }

    /**
     * @Then every users' last-synced values should have changed
     */
    public function everyUsersLastSyncedValuesShouldHaveChanged()
    {
        $newLastSyncedValues = $this->getAttributeForEachUser('last_synced');
        foreach ($this->lastSyncedValues as $employeeId => $oldLastSyncedValue) {
            Assert::notEq(
                $oldLastSyncedValue,
                $newLastSyncedValues[$employeeId]
            );
        }
    }

    /**
     * @When I update the last-synced values of users with a :field of :value
     */
    public function iUpdateTheLastSyncedValuesOfUsersWithAFieldOfValue($field, $value)
    {
        $employeeIdsToUpdate = [];
        $attributeValues = $this->getAttributeForEachUser($field);
        foreach ($attributeValues as $employeeId => $attributeValue) {
            if ($attributeValue === $value) {
                $employeeIdsToUpdate[] = $employeeId;
            }
        }

        $this->idStore->updateSyncDatesIfSupported($employeeIdsToUpdate);
    }

    /**
     * @Then ONLY last-synced values of users with a :field of :value should have changed
     */
    public function onlyLastSyncedValuesOfUsersWithAOfShouldHaveChanged($field, $value)
    {
        $numberOfMatchingUsers = 0;
        $numberOfNonMatchingUsers = 0;
        $allUsersDesiredInfo = $this->getAttributesForEachUser(['last_synced', $field]);

        foreach ($allUsersDesiredInfo as $employeeId => $thisUsersDesiredInfo) {
            $thisUsersValue = $thisUsersDesiredInfo[$field];

            if ($thisUsersValue === $value) {
                $numberOfMatchingUsers++;
                Assert::notEq(
                    $this->lastSyncedValues[$employeeId],
                    $thisUsersDesiredInfo['last_synced']
                );
            } else {
                $numberOfNonMatchingUsers++;
                Assert::eq(
                    $this->lastSyncedValues[$employeeId],
                    $thisUsersDesiredInfo['last_synced']
                );
            }
        }

        Assert::greaterThan(0, $numberOfMatchingUsers, sprintf(
            'No users had a(n) %s of %s, so we could not prove that their last_synced values changed.',
            $field,
            $value
        ));
        Assert::greaterThan(0, $numberOfNonMatchingUsers, sprintf(
            'All of the users had a(n) %s of %s, so we could not prove that '
            . "the other users' last_synced values did NOT change.",
            $field,
            $value
        ));
    }
}
