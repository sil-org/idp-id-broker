<?php

namespace common\components\notify;

use common\sync\User;

class FakeEmailNotifier implements NotifierInterface
{
    /* @var array */
    public $emailsSent = [];

    public function forgetFakeEmailsSent()
    {
        $this->emailsSent = [];
    }

    public function sendMissingEmailNotice(array $users)
    {
        $numUsers = count($users);
        $this->emailsSent[] = [
            'to_address' => 'fake@example.com',
            'subject' => sprintf(
                'Email address missing for %s orgName user%s',
                $numUsers,
                ($numUsers === 1 ? '' : 's')
            ),
            'html_body' => 'This is the html body',
            'text_body' => 'This is the text body',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function sendNewUserNotice(User $user)
    {
        $this->emailsSent[] = [
            'to_address' => $user->getHRContactEmail(),
            'subject' => sprintf(
                'New user in orgName IdP (%s)',
                $user->getEmployeeId()
            ),
            'html_body' => 'This is the html body',
            'text_body' => 'This is the text body',
        ];
    }

    public function findEmailBySubject($subject): array
    {
        foreach ($this->emailsSent as $email) {
            if (str_contains($email['subject'], $subject)) {
                return $email;
            }
        }
        return [];
    }
}
