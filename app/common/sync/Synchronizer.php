<?php

namespace common\sync;

use common\components\adapters\IdStoreInterface;
use common\components\notify\NotifierInterface;
use common\components\notify\NullNotifier;
use common\models\User as ModelUser;
use common\sync\User as SyncUser;
use Exception;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Yii;

class Synchronizer
{
    /** @var float */
    public const MIN_NUM_CHANGES_ALLOWED = 10;
    public const SAFETY_CUTOFF_DEFAULT = 0.15;

    public $dateTimeFormat = 'n/j/y g:ia T';

    /** @var IdStoreInterface */
    private $idStore;

    /** @var LoggerInterface */
    private $logger;

    /** @var NotifierInterface */
    private $notifier;

    /** @var float */
    private $safetyCutoff;

    /** @var bool */
    private $enableNewUserNotification;

    /**
     * Create a new Synchronizer object.
     *
     * @param IdStoreInterface $idStore The ID Store to communicate with.
     * @param LoggerInterface|null $logger (Optional:) The PSR-3 logger to send
     *     log data to.
     * @param NotifierInterface|null $notifier (Optional:) An object for sending
     *     notifications.
     * @param float|null $safetyCutoff (Optional:) The cutoff for what fraction
     *     of the active users in ID Broker may be changed by a single
     *     operation (e.g. limiting how many accounts can be deactivated in a
     *     single syncAll() run). When used to calculate the actual number of
     *     changes that can be made for a given sync, the resulting value is
     *     rounded up (so that we can always make at least 1 change, assuming
     *     this value is > 0.0 and there are any active users in ID Broker). If
     *     no value is given, a default value will be used.
     * @param bool $enableNewUserNotification (Optional:) Enable notification
     *     email on new user creation.
     */
    public function __construct(
        IdStoreInterface $idStore,
        LoggerInterface $logger = null,
        NotifierInterface $notifier = null,
        $safetyCutoff = null,
        bool $enableNewUserNotification = false
    ) {
        $this->idStore = $idStore;
        $this->logger = $logger ?? new NullLogger();
        $this->notifier = $notifier ?? new NullNotifier();
        $this->safetyCutoff = $safetyCutoff ?? self::SAFETY_CUTOFF_DEFAULT;
        $this->enableNewUserNotification = $enableNewUserNotification;

        $this->logger->info('Safety cutoff: {value}', [
            'value' => var_export($this->safetyCutoff, true),
        ]);

        $this->assertValidSafetyCutoff($this->safetyCutoff);
    }

    /**
     * Abort the sync (and log an error message) due to surpassing the safety
     * cutoff.
     *
     * @param string $changeType The type of change (e.g. 'create', 'update', or
     *     'deactivate').
     * @param int $numChanges The number of changes of that type that would have
     *     been attempted.
     * @param int $numChangesAllowed The number of changes allowed.
     * @param int $numActiveUsersInBroker The number of active users currently
     *     in ID Broker.
     * @param int $errorCode The error code.
     * @throws Exception
     */
    protected function abortSync(
        $changeType,
        $numChanges,
        $numChangesAllowed,
        $numActiveUsersInBroker,
        $errorCode
    ) {
        $errorMessage = sprintf(
            'This sync was aborted because it would have tried to %s %s users '
            . 'in ID Broker, which is above our safety threshold of %s '
            . '(%s%% of the %s active users currently in ID Broker).',
            $changeType,
            $numChanges,
            $numChangesAllowed,
            ($this->safetyCutoff * 100),
            $numActiveUsersInBroker
        );
        $this->logger->error($errorMessage);
        throw new Exception($errorMessage, $errorCode);
    }

    /**
     * Update the given user in the ID Broker database, setting it to be active.
     *
     * @param SyncUser $syncUser The user's information.
     */
    protected function activateAndUpdateUser(SyncUser $syncUser)
    {
        $modelUser = ModelUser::findOne(['employee_id' => $syncUser->getEmployeeId()]);
        if ($modelUser === null) {
            return;
        }

        $modelUser->attributes = $syncUser->toArray();
        $modelUser->active = 'yes';
        $modelUser->scenario = ModelUser::SCENARIO_UPDATE_USER;

        try {
            $modelUser->save();
        } catch (Exception $e) {
            $this->logger->error('exception while updating user: ' . $e->getMessage());
            return;
        }

        $this->logger->info('Updated/activated user: ' . $syncUser->getEmployeeId());
    }

    /**
     * Update the given Users in the ID Broker, setting them to be active.
     *
     * @param User[] $usersToUpdateAndActivate The user's information.
     */
    protected function activateAndUpdateUsers(array $usersToUpdateAndActivate)
    {
        $usersWithErrors = [];
        $lastError = null;

        $employeeIdsOfUsersUpdated = [];
        foreach ($usersToUpdateAndActivate as $nextUser) {
            try {
                $this->activateAndUpdateUser($nextUser);
                $employeeIdsOfUsersUpdated[] = $nextUser->getEmployeeId();
            } catch (Exception $e) {
                $usersWithErrors[] = $nextUser->getStringForLogMessage();
                $lastError = $e;
            }
        }

        if ($lastError !== null) {
            $this->logger->error(sprintf(
                'Failed to update/activate user(s) in the ID Broker. Last error %s: %s. [%s].'
                . '  Failed for %s users, including: %s',
                $lastError->getCode(),
                $lastError->getMessage(),
                1494360119,
                count($usersWithErrors),
                join(', ', array_slice($usersWithErrors, -6)) // last six users
            ));
        }

        $this->logger->notice([
            'action' => 'update',
            'attempted' => count($usersToUpdateAndActivate),
            'succeeded' => count($employeeIdsOfUsersUpdated),
        ]);

        $this->idStore->updateSyncDatesIfSupported($employeeIdsOfUsersUpdated);
    }

    protected function assertValidSafetyCutoff($value)
    {
        if (! self::isValidSafetyCutoff($value)) {
            $errorMessage = sprintf(
                'The safety cutoff must be a number from 0.0 to 1.0 (not %s).',
                var_export($value, true)
            );
            $this->logger->error($errorMessage);
            throw new InvalidArgumentException($errorMessage, 1500322937);
        }
    }

    /**
     * Create the given user in the ID Broker.
     *
     * NOTE: Make sure you catch (and handle) any `MissingEmailException`s
     *       thrown by this method.
     *
     * @param SyncUser $syncUser The user's information.
     * @throws MissingEmailException
     */
    protected function createUser(SyncUser $syncUser)
    {
        $modelUser = new ModelUser();
        $modelUser->scenario = ModelUser::SCENARIO_NEW_USER;
        $modelUser->attributes = $syncUser->toArray();
        $modelUser->save();

        /*
         * Refresh user model to retrieve database default values
         */
        $modelUser->refresh();

        Yii::info([
            'action' => 'user/create',
            'status' => 'created',
            'id' => $modelUser->id,
            'employeeId' => $modelUser->employee_id,
            'scenario' => $modelUser->scenario,
            'email' => $modelUser->email,
        ], 'application');

        if ($this->enableNewUserNotification) {
            $this->logger->info('Sending new user notification to HR Contact for user: ' . $syncUser->getEmployeeId());
            try {
                $this->notifier->sendNewUserNotice($syncUser);
            } catch (Exception $e) {
                $this->logger->error(sprintf(
                    'Could not send New User notification for user (%s) Error %s: %s. [%s]',
                    $syncUser->getStringForLogMessage(),
                    $e->getCode(),
                    $e->getMessage(),
                    1641936120
                ));
            }
        }
    }

    /**
     * Create the given Users in the ID Broker.
     *
     * @param User[] $usersToAdd The list of Users to be added.
     */
    protected function createUsers(array $usersToAdd)
    {
        $employeeIdsOfUsersAdded = [];
        $usersWithoutEmail = [];
        foreach ($usersToAdd as $userToAdd) {
            try {
                $this->createUser($userToAdd);
                $employeeIdsOfUsersAdded[] = $userToAdd->getEmployeeId();
            } catch (MissingEmailException $e) {
                $this->logger->info(sprintf(
                    'A User (%s) lacked an email address.',
                    $userToAdd->getStringForLogMessage()
                ));
                $usersWithoutEmail[] = $userToAdd;
            } catch (Exception $e) {
                $this->logger->error(sprintf(
                    'Failed to add user to ID Broker (%s). '
                    . 'Error %s: %s. [%s]',
                    $userToAdd->getStringForLogMessage(),
                    $e->getCode(),
                    $e->getMessage(),
                    1494360152
                ));
            }
        }

        $this->logger->notice([
            'action' => 'create',
            'attempted' => count($usersToAdd),
            'succeeded' => count($employeeIdsOfUsersAdded),
        ]);

        $this->idStore->updateSyncDatesIfSupported($employeeIdsOfUsersAdded);

        if (! empty($usersWithoutEmail)) {
            $this->notifier->sendMissingEmailNotice($usersWithoutEmail);
        }
    }

    public static function countActiveUsers($usersInfo, $activeFieldName = 'active')
    {
        return array_reduce($usersInfo, function ($carry, $userInfo) use ($activeFieldName) {
            if (strcasecmp($userInfo[$activeFieldName], 'yes') === 0) {
                return $carry + 1;
            }
            return $carry;
        }, 0);
    }

    /**
     * Deactivate the specified user in the ID Broker.
     *
     * @param string $employeeId The Employee ID of the user to deactivate.
     */
    protected function deactivateUser($employeeId)
    {
        $modelUser = ModelUser::findOne(['employee_id' => $employeeId]);
        if ($modelUser === null) {
            return;
        }

        $modelUser->active = 'no';
        $modelUser->scenario = ModelUser::SCENARIO_UPDATE_USER;

        try {
            $modelUser->save();
        } catch (Exception $e) {
            $this->logger->error('exception while deactivating user: ' . $e->getMessage());
            return;
        }

        $this->logger->info('Deactivated user: ' . $employeeId);
    }

    /**
     * Deactivate the specified users in the ID Broker.
     *
     * @param string[] $employeeIdsToDeactivate The Employee IDs of the users to
     *     deactivate.
     */
    protected function deactivateUsers($employeeIdsToDeactivate)
    {
        $employeeIdsDeactivated = [];
        foreach ($employeeIdsToDeactivate as $employeeIdToDeactivate) {
            try {
                $this->deactivateUser($employeeIdToDeactivate);
                $employeeIdsDeactivated[] = $employeeIdToDeactivate;
            } catch (Exception $e) {
                $this->logger->error(sprintf(
                    'Failed to deactivate user in the ID Broker (Employee ID: %s). '
                    . 'Error %s: %s. [%s]',
                    var_export($employeeIdToDeactivate, true),
                    $e->getCode(),
                    $e->getMessage(),
                    1494360189
                ));
            }
        }

        $this->logger->notice([
            'action' => 'deactivate',
            'attempted' => count($employeeIdsToDeactivate),
            'succeeded' => count($employeeIdsDeactivated),
        ]);

        $this->idStore->updateSyncDatesIfSupported($employeeIdsDeactivated);
    }

    /**
     * Get a list of all users in the ID Broker, indexed by `employee_id`.
     *
     * @param array|null $fields (Optional:) The list of fields desired about
     *     each user in the result.
     * @return array<string,array>
     */
    protected function getAllIdBrokerUsersByEmployeeId($fields = null)
    {
        if (is_array($fields) && ! in_array(User::EMPLOYEE_ID, $fields)) {
            throw new InvalidArgumentException(sprintf(
                'The list of fields, if provided, must include %s. Given list: %s',
                User::EMPLOYEE_ID,
                join(', ', $fields)
            ), 1501181580);
        }

        $rawList = ModelUser::search(['fields' => $fields]);
        $usersByEmployeeId = [];

        foreach ($rawList as $user) {
            /* @var $user SyncUser */
            $employeeId = $user->getEmployeeId();

            // Prevent duplicates.
            if (array_key_exists($employeeId, $usersByEmployeeId)) {
                $this->logger->error(sprintf(
                    'Duplicate Employee ID found: %s. Skipping it. [%s]',
                    $employeeId,
                    1494360205
                ));
                continue;
            }

            $userInfo = $user->toArray();
            unset($userInfo[SyncUser::EMPLOYEE_ID]);
            $usersByEmployeeId[$employeeId] = $userInfo;
        }

        return $usersByEmployeeId;
    }

    protected function getNumActiveUsersInBroker($idBrokerUsersInfo = null)
    {
        if ($idBrokerUsersInfo === null) {
            $idBrokerUsersInfo = $this->getAllIdBrokerUsersByEmployeeId([
                'employee_id',
                'active',
            ]);
        }
        return self::countActiveUsers($idBrokerUsersInfo);
    }

    protected function getNumChangesAllowed($numActiveUsersInBroker)
    {
        // If Broker is essentially empty, don't limit how many changes can be
        // made (so that we can run a full sync of a new IdP, even if an
        // incremental sync has happened).
        if ($numActiveUsersInBroker < 10) {
            return PHP_INT_MAX;
        }

        return max(
            ceil($numActiveUsersInBroker * $this->safetyCutoff),
            self::MIN_NUM_CHANGES_ALLOWED
        );
    }

    public static function isValidSafetyCutoff($value)
    {
        return is_numeric($value) && ($value >= 0.0);
    }


    /**
     * Do a full synchronization, requesting all users from the ID Store and
     * updating all records in the ID Broker.
     *
     * If there is an exception, send out an email about it.
     */
    public function syncAllNotifyException()
    {
        try {
            $this->syncAll();
        } catch (Exception $e) {
            $code = $e->getCode();
            $message = sprintf(
                'There was an error with the sync process. Code: %s, Message: %s',
                $code,
                $e->getMessage(),
            );

            if ($code == 503 || $code == 504) {
                $this->logger->warning($message);
            } else {
                $this->logger->error($message);
            }
        }
    }


    /**
     * Do a full synchronization, requesting all users from the ID Store and
     * updating all records in the ID Broker.
     *
     * @throws Exception
     */
    public function syncAll()
    {
        $this->logger->info(sprintf(
            'Syncing all users... (%s)',
            $this->idStore->getIdStoreName()
        ));

        try {
            $idStoreUsers = $this->idStore->getAllActiveUsers();
        } catch (Exception $e) {
            throw new Exception('Failed to get active users from ID Store: ' . $e->getMessage(), $e->getCode(), $e);
        }

        try {
            $idBrokerUserInfoByEmployeeId = $this->getAllIdBrokerUsersByEmployeeId([
                'employee_id',
                'active',
            ]);
        } catch (Exception $e) {
            throw new Exception('Failed to list users in the ID Broker: ' . $e->getMessage(), $e->getCode(), $e);
        }

        $numActiveUsersInBroker = $this->getNumActiveUsersInBroker(
            $idBrokerUserInfoByEmployeeId
        );

        $usersToAdd = [];
        $usersToUpdateAndActivate = [];
        $employeeIdsToDeactivate = [];
        $employeeIdsAlreadyInactive = [];

        $this->logger->info(sprintf(
            'Found %s ID Store users and %s ID Broker users.',
            count($idStoreUsers),
            count($idBrokerUserInfoByEmployeeId)
        ));

        foreach ($idStoreUsers as $idStoreUser) {
            $employeeId = $idStoreUser->getEmployeeId();

            if (empty($employeeId)) {
                $this->logger->warning(sprintf(
                    'Received an empty Employee ID (%s) in the list of users '
                    . 'from the ID Store. Skipping it.',
                    var_export($employeeId, true)
                ));
                continue;
            }

            if (array_key_exists($employeeId, $idBrokerUserInfoByEmployeeId)) {
                // User exists in both places. Update and set as active:
                $usersToUpdateAndActivate[] = $idStoreUser;
            } else {
                // User is only in the ID Store. Add to ID Broker:
                $usersToAdd[] = $idStoreUser;
            }

            // Remove that user from the list of ID Broker users who have not
            // yet been processed.
            unset($idBrokerUserInfoByEmployeeId[$employeeId]);
        }

        // Deactivate the remaining (unprocessed) users in the ID Broker list.
        foreach ($idBrokerUserInfoByEmployeeId as $employeeId => $userInfo) {
            // If this user not currently inactive, deactivate them.
            if ($userInfo['active'] !== 'no') {
                $employeeIdsToDeactivate[] = $employeeId;
            } else {
                $employeeIdsAlreadyInactive[] = $employeeId;
            }
        }

        $numChangesAllowed = $this->getNumChangesAllowed($numActiveUsersInBroker);

        if (count($usersToAdd) > $numChangesAllowed) {
            $this->abortSync(
                'create',
                count($usersToAdd),
                $numChangesAllowed,
                $numActiveUsersInBroker,
                1501165932
            );
        }

        /**
         * NOTE: If we begin intelligently comparing users found in both places
         * and only updating those that need it, then we could limit updates to
         * the safety cutoff as well. For now, we try to update all users found
         * to exist in both places, so for a full sync that will almost always
         * be above the safety cutoff.
         */

        if (count($employeeIdsToDeactivate) > $numChangesAllowed) {
            $this->abortSync(
                'deactivate',
                count($employeeIdsToDeactivate),
                $numChangesAllowed,
                $numActiveUsersInBroker,
                1499971625
            );
        }

        $this->createUsers($usersToAdd);
        $this->activateAndUpdateUsers($usersToUpdateAndActivate);
        $this->deactivateUsers($employeeIdsToDeactivate);

        $this->logger->notice([
            'action' => 'none (already inactive)',
            'count' => count($employeeIdsAlreadyInactive),
        ]);
        $this->idStore->updateSyncDatesIfSupported($employeeIdsAlreadyInactive);

        $this->logger->info('Done attempting to sync all users.');
    }

    /**
     * Synchronize a specific user, requesting their information from the
     * ID Store and updating it accordingly in the ID Broker.
     *
     * @param string $employeeId The Employee ID of the user to sync.
     */
    public function syncUser($employeeId)
    {
        try {
            $this->syncUserInternal($employeeId);
            $this->idStore->updateSyncDatesIfSupported([$employeeId]);
        } catch (MissingEmailException $e) {
            $this->logger->info(sprintf(
                'That User (Employee ID: %s) lacked an email address.',
                $employeeId
            ));
            $user = new User([User::EMPLOYEE_ID => $employeeId]);
            $this->notifier->sendMissingEmailNotice([$user]);
        } catch (Exception $e) {
            $this->logger->error(sprintf(
                'Failed to sync the specified user (Employee ID: '
                . '%s). Error (%s): %s. [%s]',
                var_export($employeeId, true),
                $e->getCode(),
                $e->getMessage(),
                1495036251
            ));
        }
    }

    /**
     * Actually make the calls to synchronize the specified user.
     *
     * @param string $employeeId The Employee ID of the user to sync.
     */
    protected function syncUserInternal($employeeId)
    {
        if (empty($employeeId)) {
            throw new \InvalidArgumentException(
                'Employee ID cannot be empty',
                1491336331
            );
        }

        $idStoreUser = $this->idStore->getActiveUser($employeeId);
        $idBrokerUser = ModelUser::findOne(['employee_id' => $employeeId]);

        $isInIdStore = ($idStoreUser !== null);
        $isInIdBroker = ($idBrokerUser !== null);

        if ($isInIdStore) {
            if ($isInIdBroker) {
                $this->activateAndUpdateUser($idStoreUser);
            } else {
                $this->createUser($idStoreUser);
            }
        } else {
            if ($isInIdBroker) {
                $this->deactivateUser($idBrokerUser->getEmployeeId());
            } else {
                $this->logger->error(sprintf(
                    'Cannot find user anywhere: %s. [%s]',
                    $employeeId,
                    1494360236
                ));
            }
        }
    }

    /**
     * Synchronize a specific set of users.
     *
     * @param string[] $employeeIds A list of Employee IDs indicating which
     *     users to sync.
     */
    public function syncUsers(array $employeeIds)
    {
        $this->logger->info(sprintf(
            'Syncing %s specific users... (%s)',
            count($employeeIds),
            $this->idStore->getIdStoreName()
        ));

        $usersWithoutEmail = [];
        $employeeIdsSynced = [];

        foreach ($employeeIds as $employeeId) {
            if (empty($employeeId)) {
                $this->logger->warning(sprintf(
                    'The list of users to sync included an empty Employee ID '
                    . '(%s). Skipping it.',
                    var_export($employeeId, true)
                ));
                continue;
            }

            try {
                $this->syncUserInternal($employeeId);
                $employeeIdsSynced[] = $employeeId;
            } catch (MissingEmailException $e) {
                $this->logger->info(sprintf(
                    'A User (Employee ID: %s) lacked an email address.',
                    $employeeId
                ));
                $usersWithoutEmail[] = new User([
                    User::EMPLOYEE_ID => $employeeId,
                ]);
            } catch (Exception $e) {
                $code = $e->getCode();
                $message = sprintf(
                    'Failed to sync one of the specified users (Employee ID: '
                    . '%s). Error (%s): %s. [%s]',
                    var_export($employeeId, true),
                    $code,
                    $e->getMessage(),
                    1494360265
                );

                if ($code == 503 || $code == 504) {
                    $this->logger->warning($message);
                } else {
                    $this->logger->error($message);
                }
            }
        }

        $this->idStore->updateSyncDatesIfSupported($employeeIdsSynced);

        if (! empty($usersWithoutEmail)) {
            $this->notifier->sendMissingEmailNotice($usersWithoutEmail);
        }

        $this->logger->info(sprintf(
            'Done attempting to sync %s specific users.',
            count($employeeIds)
        ));
    }

    /**
     * Get the list of users that the ID Store believes have been changed
     * (added, altered, or removed/deactivated) since the given timestamp, then
     * synchronize those specific users with the ID Broker.
     *
     * @param int $timestamp The date/time, as a Unix timestamp.
     */
    public function syncUsersChangedSince($timestamp)
    {
        $this->logger->info(sprintf(
            'Syncing users changed since %s... (%s)',
            date($this->dateTimeFormat, $timestamp),
            $this->idStore->getIdStoreName()
        ));

        $changedUsers = $this->idStore->getUsersChangedSince($timestamp);
        $employeeIds = [];
        foreach ($changedUsers as $changedUser) {
            $employeeIds[] = $changedUser->getEmployeeId();
        }

        $numActiveUsersInBroker = $this->getNumActiveUsersInBroker();
        $numChangesAllowed = $this->getNumChangesAllowed($numActiveUsersInBroker);

        if (count($employeeIds) > $numChangesAllowed) {
            $this->abortSync(
                'change',
                count($employeeIds),
                $numChangesAllowed,
                $numActiveUsersInBroker,
                1501177946
            );
        }

        $this->syncUsers($employeeIds);

        $this->logger->info(sprintf(
            'Done attempting to sync users changed since %s.',
            date($this->dateTimeFormat, $timestamp)
        ));
    }
}
