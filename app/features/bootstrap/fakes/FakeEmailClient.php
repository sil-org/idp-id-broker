<?php

namespace Sil\SilIdBroker\Behat\Context\fakes;

use common\components\EmailClient;
use common\models\Email;
use yii\db\Exception;

class FakeEmailClient extends EmailClient
{
    public $emailsSent = [];

    public function email(array $config = []): void
    {
        // Validate like the real EmailClient does, so tests see the same failures.
        $email = new Email();
        $email->attributes = $config;
        if (!$email->validate()) {
            throw new Exception(current($email->getFirstErrors()));
        }

        $this->emailsSent[] = $config;
    }
}
