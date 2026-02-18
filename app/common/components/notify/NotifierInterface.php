<?php

namespace common\components\notify;

use common\sync\SyncUser;

interface NotifierInterface
{
    /**
     * Send a notification that there are Users that lack an email address.
     *
     * @param SyncUser[] $users The list of Users.
     */
    public function sendMissingEmailNotice(array $users);

    /**
     * Send a notification when a user is created.
     *
     * @param SyncUser $user The new SyncUser.
     */
    public function sendNewUserNotice(SyncUser $user);
}
