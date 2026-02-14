<?php

namespace common\components\notify;

use common\sync\User;

interface NotifierInterface
{
    /**
     * Send a notification that there are Users that lack an email address.
     *
     * @param User[] $users The list of Users.
     */
    public function sendMissingEmailNotice(array $users);

    /**
     * Send a notification when a user is created.
     *
     * @param User $user The new User.
     */
    public function sendNewUserNotice(User $user);
}
