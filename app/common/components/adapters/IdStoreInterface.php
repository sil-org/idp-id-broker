<?php

namespace common\components\adapters;

use common\sync\User;

interface IdStoreInterface
{
    /**
     * Get the specified user's information. Note that inactive users will be
     * treated as non-existent users.
     *
     * @param string $employeeId The Employee ID.
     * @return User|null Information about the specified user, or null if no
     *     such active user was found.
     */
    public function getActiveUser(string $employeeId);

    /**
     * Get information about each of the (active) users.
     *
     * @return User[] A list of Users.
     */
    public function getAllActiveUsers();

    /**
     * Get a user-friendly name for this ID Store.
     *
     * @return string The name of the ID Store.
     */
    public function getIdStoreName();

    /**
     * Get a list of users who have had qualifying changes (name, email, locked,
     * activated, added) since the given Unix timestamp.
     *
     * @param int $unixTimestamp The date/time, as a Unix timestamp.
     * @return User[] A list of Users.
     */
    public function getUsersChangedSince(int $unixTimestamp);

    /**
     * If the ID Store adapter supports updating when specific users' records
     * were last synced, do so. Otherwise, this function call does nothing.
     *
     * @param string[] $employeeIds The Employee IDs of the users we just synced.
     */
    public function updateSyncDatesIfSupported(array $employeeIds);
}
