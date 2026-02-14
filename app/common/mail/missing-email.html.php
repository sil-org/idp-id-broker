<?php

use models\User;
use yii\helpers\Html;

/* @var $organizationName string */
/* @var $users User[] */

$numUsers = count($users);
$isOnlyOne = ($numUsers === 1);
?>
<h2>Missing Email <?= $isOnlyOne ? 'Address' : 'Addresses' ?></h2>
<p>
  The following
  <?= Html::encode($isOnlyOne ? 'user does' : $numUsers . ' users do') ?>
  not have an email address. Without this, they will be unable to log in to
  certain <?= Html::encode($organizationName) ?> websites.
</p>
<ol>
  <?php foreach ($users as $user): ?>
    <li>
      Employee ID <?= Html::encode($user->getEmployeeId()) ?>
      <?php if ($user->getUsername() !== null): ?>
        (<?= Html::encode($user->getUsername()) ?>)
      <?php endif; ?>
    </li>
  <?php endforeach; ?>
</ol>
