<?php
use yii\helpers\Html as yHtml;

/**
 * @var string $displayName        Name of the account holder. Provided by user profile.
 * @var string $username           Username of the account holder. Provided by user profile.
 * @var string $alternateAddress   Recovery email address added. Generated at runtime.
 * @var string $idpDisplayName     Display name of IDP instance. Provided by environment variable IDP_DISPLAY_NAME.
 * @var string $supportName        Help center name.  Provided by environment variable SUPPORT_NAME.
 * @var string $supportEmail       Help center email address.  Provided by environment variable SUPPORT_EMAIL.
 * @var string $emailSignature     Email signature. Provided by environment variable EMAIL_SIGNATURE.
 * @var string $passwordProfileUrl URL of password manager. Provided by environment variable PASSWORD_PROFILE_URL.
 */

?>
<p>Hello,</p>

<p>
    The email address <?= yHtml::encode($alternateAddress) ?> was added as a password recovery method on the
    <?= yHtml::encode($idpDisplayName) ?> Identity account belonging to
    <?= yHtml::encode($displayName) ?> (<?= yHtml::encode($username) ?>).
</p>
<p>
    A password recovery method can be used to reset the password for that account, so you are receiving this
    message at every address associated with it.
</p>
<p>
    If this was expected, no action is needed. To review the recovery methods on the account, go to
    <?= yHtml::a(yHtml::encode($passwordProfileUrl), $passwordProfileUrl) ?> and log in if needed.
</p>
<p>
    If you did not do this, it could be a sign someone else has compromised the account. Please contact
    <?= yHtml::encode($supportName) ?> at <?= yHtml::encode($supportEmail) ?>
    as soon as possible to report the incident.
</p>
<p>
    Thanks,
</p>
<p><i><?= nl2br(yHtml::encode($emailSignature), false) ?></i></p>
