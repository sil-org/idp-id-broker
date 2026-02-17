<?php

namespace Sil\SilIdBroker\Behat\Context;

use common\components\adapters\FakeIdStore;
use common\components\notify\ConsoleNotifier;
use common\models\User;
use common\sync\Synchronizer;
use Exception;
use Sil\Psr3Adapters\Psr3ConsoleLogger;
use Webmozart\Assert\Assert;

/**
 * Defines application features from the specific context.
 */
class SafetyCutoffContext extends SyncContext
{
    /** @var float|null */
    private $safetyCutoff = null;

    /** @var int */
    private $tempTimestamp;

    public function __construct()
    {
        parent::__construct();
        $this->logger = new Psr3ConsoleLogger();
        $this->notifier = new ConsoleNotifier();
    }

    /**
     * @Then an exception SHOULD have been thrown
     */
    public function anExceptionShouldHaveBeenThrown()
    {
        Assert::notNull(
            $this->exceptionThrown,
            "An exception should have been thrown, but wasn't"
        );
    }

    protected function createSynchronizer()
    {
        return new Synchronizer(
            $this->idStore,
            $this->logger,
            $this->notifier,
            $this->safetyCutoff
        );
    }

    /**
     * @Given :number users are active in the ID Broker
     */
    public function usersAreActiveInTheIdBroker($number)
    {
        $this->purgeDatabase();
        for ($i = 1; $i <= $number; $i++) {
            $tempEmployeeId = 10000 + $i;
            $properties = [
                'employee_id' => (string)$tempEmployeeId,
                'display_name' => 'Person ' . $i,
                'username' => 'person_' . $i,
                'first_name' => 'Person',
                'last_name' => (string)$i,
                'email' => 'person_' . $i . '@example.com',
                'active' => 'yes',
            ];
            $this->createNewUserInDatabase($properties['username'], $properties);
        }
    }

    /**
     * @Given running a full sync would deactivate :numToDeactivate users
     */
    public function runningAFullSyncWouldDeactivateUsers($numToDeactivate)
    {
        /* @var $usersFromBroker User[] */
        $usersFromBroker = User::search([]);
        Assert::notEmpty(
            $usersFromBroker,
            'Set up the ID Broker before using this step.'
        );

        $numInBroker = count($usersFromBroker);
        $numToHaveInStore = $numInBroker - $numToDeactivate;

        $activeIdStoreUsers = [];
        for ($i = 0; $i < $numToHaveInStore; $i++) {
            $user = $usersFromBroker[$i];
            $activeIdStoreUsers[$user->employee_id] = [
                'employeenumber' => $user->employee_id,
                'displayname' => $user->display_name,
                'username' => $user->username,
                'firstname' => $user->first_name,
                'lastname' => $user->last_name,
                'email' => $user->email,
            ];
        }
        $this->idStore = new FakeIdStore($activeIdStoreUsers);
    }

    /**
     * @Given the safety cutoff is :value
     */
    public function theSafetyCutoffIs($value)
    {
        $this->safetyCutoff = $value;
    }

    /**
     * @Given running a full sync would create :numToCreate users
     */
    public function runningAFullSyncWouldCreateUsers($numToCreate)
    {
        /* @var $usersFromBroker User[] */
        $usersFromBroker = User::search([]);
        Assert::notEmpty(
            $usersFromBroker,
            'Set up the ID Broker before using this step.'
        );

        $activeIdStoreUsers = [];

        // Add all users from ID Broker to ID Store.
        foreach ($usersFromBroker as $user) {
            $activeIdStoreUsers[$user->employee_id] = [
                'employeenumber' => $user->employee_id,
                'displayname' => $user->display_name,
                'username' => $user->username,
                'firstname' => $user->first_name,
                'lastname' => $user->last_name,
                'email' => $user->email,
            ];
        }

        // Add $numToCreate more users to ID Store.
        $numInBroker = count($usersFromBroker);
        $numToHaveInStore = $numInBroker + $numToCreate;
        for ($i = $numInBroker; $i <= $numToHaveInStore; $i++) {
            $tempEmployeeId = 10000 + $i;
            $activeIdStoreUsers[$tempEmployeeId] = [
                'employeenumber' => (string)$tempEmployeeId,
                'displayname' => 'Person ' . $i,
                'username' => 'person_' . $i,
                'firstname' => 'Person',
                'lastname' => (string)$i,
                'email' => 'person_' . $i . '@example.com',
            ];
        }

        $this->idStore = new FakeIdStore($activeIdStoreUsers);
    }

    /**
     * @When I run an incremental sync
     */
    public function iRunAnIncrementalSync()
    {
        try {
            $synchronizer = $this->createSynchronizer();
            $synchronizer->syncUsersChangedSince($this->tempTimestamp);
        } catch (Exception $e) {
            $this->exceptionThrown = $e;
        }
    }

    /**
     * @Given an incremental sync would add :numToAdd, update :numToUpdate, and deactivate :numToDeactivate users
     */
    public function anIncrementalSyncWouldAddUpdateAndDeactivateUsers(
        $numToAdd,
        $numToUpdate,
        $numToDeactivate
    ) {
        /* @var $usersFromBroker User[] */
        $usersFromBroker = User::search([]);
        Assert::notEmpty(
            $usersFromBroker,
            'Set up the ID Broker before using this step.'
        );

        $numInBroker = count($usersFromBroker);

        $activeIdStoreUsers = [];
        $idStoreUserChanges = [];
        $this->tempTimestamp = 1500000000; // Arbitrary time for tests.

        // Add $numToAdd new users to ID Store (that aren't in ID Broker),
        // ensuring Employee ID's won't collide.
        for ($i = 0; $i < $numToAdd; $i++) {
            $tempEmployeeId = 30000 + $i;
            $activeIdStoreUsers[$tempEmployeeId] = [
                'employeenumber' => (string)$tempEmployeeId,
                'displayname' => 'Person ' . $i,
                'username' => 'person_' . $i,
                'firstname' => 'Person',
                'lastname' => (string)$i,
                'email' => 'person_' . $i . '@example.com',
            ];
            $idStoreUserChanges[] = [
                'changedat' => $this->tempTimestamp + $i,
                'employeenumber' => (string)$tempEmployeeId,
            ];
        }

        // Set up for Store to SOME of the users that are in Broker.
        $numInBrokerToHaveInStore = $numInBroker - $numToDeactivate;
        for ($i = 0; $i < $numInBroker; $i++) {
            $user = $usersFromBroker[$i];

            // Make a note that the first $numToUpdate were changed recently
            // enough to be included in our incremental sync.
            if ($i < $numToUpdate) {
                $idStoreUserChanges[] = [
                    'changedat' => $this->tempTimestamp + $i,
                    'employeenumber' => $user->employee_id,
                ];
            }

            // Exclude the last $numToDeactivate from Store, and make a note
            // that those were changed recently enough to be included in our
            // incremental sync.
            if ($i < $numInBrokerToHaveInStore) {
                $activeIdStoreUsers[$user->employee_id] = [
                    'employeenumber' => $user->employee_id,
                    'displayname' => $user->display_name,
                    'username' => $user->username,
                    'firstname' => $user->first_name,
                    'lastname' => $user->last_name,
                    'email' => $user->email,
                ];
            } else {
                $idStoreUserChanges[] = [
                    'changedat' => $this->tempTimestamp + $i,
                    'employeenumber' => $user->employee_id,
                ];
            }
        }

        $this->idStore = new FakeIdStore($activeIdStoreUsers, $idStoreUserChanges);
    }
}
