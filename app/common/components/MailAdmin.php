<?php

namespace common\components;

use common\helpers\ApiClient;
use Yii;

class MailAdmin
{
    /**
     * Get the email addresses of the active mail admins for the given account.
     *
     * Returns an empty array when the feature is not configured or when the call
     * to Mail Admin fails, so that a Mail Admin outage never blocks the email that
     * triggered the lookup.
     *
     * @param string $accountEmail The email address of the account whose mail admins to fetch.
     * @return string[] The mail admins' email addresses.
     */
    public static function getEmailsFor(string $accountEmail): array
    {
        $apiUrl = Yii::$app->params['accountMailAdminsApiUrl'] ?? '';
        $apiKey = Yii::$app->params['accountMailAdminsApiKey'] ?? '';

        if (empty($apiUrl) || empty($accountEmail)) {
            return [];
        }

        try {
            $mailAdmins = (new ApiClient($apiKey))->call($apiUrl, ['email' => $accountEmail]);
        } catch (\Throwable $e) {
            Yii::error([
                'action' => 'fetch mail admins',
                'status' => 'error',
                'error' => $e->getMessage(),
            ]);
            return [];
        }

        if (!is_array($mailAdmins)) {
            return [];
        }

        return array_column($mailAdmins, 'email');
    }
}
