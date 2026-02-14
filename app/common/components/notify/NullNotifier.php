<?php

namespace common\components\notify;

use common\sync\User;

/**
 * This Notifier can be used to avoid conditional log calls.
 */
class NullNotifier implements NotifierInterface
{
    public function sendMissingEmailNotice(array $users)
    {
        // noop
    }

    public function sendNewUserNotice(User $user)
    {
        // noop
    }
}
