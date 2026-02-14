<?php

use models\User;

/* @var $organizationName string */
/* @var $user User */

?>
  <?= $user->getHRContactName() ?>,

  The <?= $organizationName ?> IDP account you requested for
  <?php if (empty($user->getDisplayName())) {
      echo $user->getFirstName() . ' ' . $user->getLastName();
  } else {
      echo $user->getDisplayName();
  }?> (Staff ID <?= $user->getEmployeeId() ?>)
  has been created. Their username is <?= $user->getUsername() ?>.

  An invite message will be sent to <?= $user->getFirstName() ?> at
  the following address: <?php if (empty($user->getEmail())) {
      echo $user->getPersonalEmail();
  } else {
      echo $user->getEmail();
  }?>

--

This is an automated message.
