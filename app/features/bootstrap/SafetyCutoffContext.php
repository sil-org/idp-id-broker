<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Context\Context;
use common\components\adapters\FakeIdStore;
use common\components\adapters\IdStoreInterface;
use common\components\notify\ConsoleNotifier;
use common\components\notify\NotifierInterface;
use common\sync\Synchronizer;
use common\sync\User as SyncUser;
use Exception;
use Psr\Log\LoggerInterface;
use Sil\Psr3Adapters\Psr3ConsoleLogger;
use Webmozart\Assert\Assert;

/**
 * Defines application features from the specific context.
 */
class SafetyCutoffContext implements Context
{
    /** @var Exception */
    private $exceptionThrown = null;

    /** @var IdBrokerInterface */
    private $idBroker;

    /** @var IdStoreInterface */
    private $idStore;

    /** @var LoggerInterface */
    protected $logger;

    /** @var NotifierInterface */
    protected $notifier;

    /** @var float|null */
    private $safetyCutoff = null;

    /** @var int */
    private $tempTimestamp;

    public function __construct()
    {
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
        $idBrokerUsers = [];
        for ($i = 1; $i <= $number; $i++) {
            $tempEmployeeId = 10000 + $i;
            $idBrokerUsers[$tempEmployeeId] = [
                SyncUser::EMPLOYEE_ID => (string)$tempEmployeeId,
                SyncUser::DISPLAY_NAME => 'Person ' . $i,
                SyncUser::USERNAME => 'person_' . $i,
                SyncUser::FIRST_NAME => 'Person',
                SyncUser::LAST_NAME => (string)$i,
                SyncUser::EMAIL => 'person_' . $i . '@example.com',
                SyncUser::ACTIVE => 'yes',
            ];
        }

        $this->idBroker = new FakeIdBroker($idBrokerUsers);
    }

    /**
     * @Given running a full sync would deactivate :numToDeactivate users
     */
    public function runningAFullSyncWouldDeactivateUsers($numToDeactivate)
    {
        Assert::notEmpty(
            $this->idBroker,
            'Set up the ID Broker before using this step.'
        );

        $usersFromBroker = $this->idBroker->listUsers();

        $numInBroker = count($usersFromBroker);
        $numToHaveInStore = $numInBroker - $numToDeactivate;

        $activeIdStoreUsers = [];
        for ($i = 0; $i < $numToHaveInStore; $i++) {
            /* @var $user SyncUser */
            $user = $usersFromBroker[$i];
            $activeIdStoreUsers[$user->getEmployeeId()] = [
                'employeenumber' => (string)$user->getEmployeeId(),
                'displayname' => $user->getDisplayName(),
                'username' => $user->getUsername(),
                'firstname' => $user->getFirstName(),
                'lastname' => $user->getLastName(),
                'email' => $user->getEmail(),
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
        Assert::notEmpty(
            $this->idBroker,
            'Set up the ID Broker before using this step.'
        );

        $usersFromBroker = $this->idBroker->listUsers();
        $activeIdStoreUsers = [];

        // Add all users from ID Broker to ID Store.
        foreach ($usersFromBroker as $user) {
            $activeIdStoreUsers[$user->getEmployeeId()] = [
                'employeenumber' => (string)$user->getEmployeeId(),
                'displayname' => $user->getDisplayName(),
                'username' => $user->getUsername(),
                'firstname' => $user->getFirstName(),
                'lastname' => $user->getLastName(),
                'email' => $user->getEmail(),
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
        Assert::notEmpty(
            $this->idBroker,
            'Set up the ID Broker before using this step.'
        );

        $usersFromBroker = $this->idBroker->listUsers();
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

            /* @var $user SyncUser */
            $user = $usersFromBroker[$i];

            // Make a note that the first $numToUpdate were changed recently
            // enough to be included in our incremental sync.
            if ($i < $numToUpdate) {
                $idStoreUserChanges[] = [
                    'changedat' => $this->tempTimestamp + $i,
                    'employeenumber' => (string)$user->getEmployeeId(),
                ];
            }

            // Exclude the last $numToDeactivate from Store, and make a note
            // that those were changed recently enough to be included in our
            // incremental sync.
            if ($i < $numInBrokerToHaveInStore) {
                $activeIdStoreUsers[$user->getEmployeeId()] = [
                    'employeenumber' => (string)$user->getEmployeeId(),
                    'displayname' => $user->getDisplayName(),
                    'username' => $user->getUsername(),
                    'firstname' => $user->getFirstName(),
                    'lastname' => $user->getLastName(),
                    'email' => $user->getEmail(),
                ];
            } else {
                $idStoreUserChanges[] = [
                    'changedat' => $this->tempTimestamp + $i,
                    'employeenumber' => (string)$user->getEmployeeId(),
                ];
            }
        }

        $this->idStore = new FakeIdStore($activeIdStoreUsers, $idStoreUserChanges);
    }
}
