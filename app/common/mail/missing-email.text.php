<?php

use models\User;

/* @var $organizationName string */
/* @var $users User[] */

$numUsers = count($users);
$isOnlyOne = ($numUsers === 1);
?>
Missing Email <?= $isOnlyOne ? 'Address' : 'Addresses' ?>
-----------------------

The following <?= $isOnlyOne ? 'user does' : $numUsers . ' users do' ?>
not have an email address. Without this, they will be unable to log in to
certain <?= $organizationName ?> websites.

<?php
$counter = 0;
foreach ($users as $user) {
    echo sprintf('%s. Employee ID %s', ++$counter, $user->getEmployeeId());
    if ($user->getUsername() !== null) {
        echo sprintf(' (%s)', $user->getUsername());
    }
    echo "\n";
}
