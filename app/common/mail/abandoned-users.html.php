<?php

use yii\helpers\Html as yHtml;

/**
 * @var string $idpDisplayName
 * @var string $abandonedPeriod
 * @var string $bestPracticeUrl
 * @var string $deactivateInstructionsUrl
 * @var array  $users
 * @var string $emailSignature
 */
?>
<p>Dear HR,</p>

<p>
    As GTIS works towards securing <?= yHtml::encode($idpDisplayName) ?> accounts, we are auditing
    <?= yHtml::encode($idpDisplayName) ?> Identity access and asking you to consider deactivating accounts that
    haven't been used in more than <?= yHtml::encode(ltrim($abandonedPeriod, '+')) ?>.
</p>

<p>
    Identity accounts are used to gain access to other systems. Often, when an account is not used,
    the staff member uses their Identity from another organization.
</p>

<?php if (!empty($bestPracticeUrl)) { ?>
    <p>
        <?= yHtml::a("Link to Best Practice", $bestPracticeUrl) ?>
    </p>
<?php } ?>

<?php if (!empty($deactivateInstructionsUrl)) { ?>
    <p>
          Go here for instructions on how to change access and deactivate email accounts:
    </p>
    <p>
        <?= yHtml::a(yHtml::encode($deactivateInstructionsUrl), $deactivateInstructionsUrl) ?>
    </p>
<?php } ?>

<p>
    Here is a list of Staff IDs, Usernames, and last login time. Please deactivate those
    you decide are unneeded.
</p>

<h1>Unused <?= yHtml::encode($idpDisplayName) ?> Identity Accounts</h1>
<table>
    <tr>
        <th>Staff Id</th>
        <th>Username</th>
        <th>Last IdP Login</th>
    </tr>
    <?php foreach ($users as $user): ?>
    <tr>
        <td><?= $user['employee_id'] ?></td>
        <td><?= $user['username'] ?></td>
        <td><?= $user['last_login_utc'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>

<p>Thanks,</p>

<p><i><?= nl2br(yHtml::encode($emailSignature), false) ?></i></p>
