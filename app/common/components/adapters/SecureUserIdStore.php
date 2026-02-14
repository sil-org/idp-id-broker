<?php

namespace common\components\adapters;

use common\sync\User;
use Exception;
use GuzzleHttp\Client;
use InvalidArgumentException;
use yii\helpers\Json;

class SecureUserIdStore extends IdStoreBase
{
    public $apiUrl = null;
    public $apiKey = null;
    public $apiSecret = null;

    public $timeout = 45; // Timeout in seconds (per call to ID Store API).

    protected $httpClient = null;

    public function init()
    {
        $requiredProperties = [
            'apiUrl',
            'apiKey',
            'apiSecret',
        ];
        foreach ($requiredProperties as $requiredProperty) {
            if (empty($this->$requiredProperty)) {
                throw new InvalidArgumentException(sprintf(
                    'No %s was provided.',
                    $requiredProperty
                ), 1642083101);
            }
        }

        parent::init();
    }

    public static function getFieldNameMap(): array
    {
        return [
            'employee_number' => User::EMPLOYEE_ID,
            'first_name' => User::FIRST_NAME,
            'last_name' => User::LAST_NAME,
            'display_name' => User::DISPLAY_NAME,
            'email' => User::EMAIL,
            'username' => User::USERNAME,
            'locked' => User::LOCKED,
            'manager_email' => User::MANAGER_EMAIL,
        ];
    }

    /**
     * Get the specified user's information. Note that inactive users will be
     * treated as non-existent users.
     *
     * @param string $employeeId The Employee ID.
     * @return User|null Information about the specified user, or null if no
     *     such active user was found.
     * @throws Exception
     */
    public function getActiveUser(string $employeeId)
    {
        $allUsers = $this->getAllActiveUsers();
        foreach ($allUsers as $user) {
            if ((string)$user->getEmployeeId() === (string)$employeeId) {
                return $user;
            }
        }
        return null;
    }

    /**
     * Get a list of users' information (containing at least an Employee ID) for
     * all users changed since the specified time.
     *
     * @param int $unixTimestamp The time (as a UNIX timestamp).
     * @return User[]
     * @throws Exception
     */
    public function getUsersChangedSince(int $unixTimestamp)
    {
        throw new Exception(__FUNCTION__ . ' not yet implemented');
    }

    /**
     * Get information about each of the (active) users.
     *
     * @return User[] A list of Users.
     * @throws Exception
     */
    public function getAllActiveUsers()
    {
        $client = $this->getHttpClient();

        $api_sig = hash_hmac('sha256', time() . $this->apiKey, $this->apiSecret);

        $response = $client->get($this->apiUrl, [
            'connect_timeout' => $this->timeout,
            'headers' => [
                'Accept' => 'application/json',
                'x-api-key' => $this->apiKey,
                'x-auth-hmac-sha256' => $api_sig,
            ],
            'http_errors' => false,
        ]);

        $statusCode = (int)$response->getStatusCode();
        if (($statusCode >= 200) && ($statusCode <= 299)) {
            $allUsersInfo = Json::decode($response->getBody());
        } else {
            throw new Exception(sprintf(
                'Unexpected response (%s %s): %s',
                $response->getStatusCode(),
                $response->getReasonPhrase(),
                $response->getBody()
            ), $response->getStatusCode());
        }

        if (! is_array($allUsersInfo)) {
            throw new Exception(sprintf(
                'Unexpected result when getting all active users: %s',
                var_export($allUsersInfo, true)
            ), 1642083103);
        }

        $allActiveUsersInfo = array_filter(
            $allUsersInfo,
            function ($user) {
                return ($user[User::ACTIVE] === true);
            }
        );

        return array_map(
            function ($entry) {
                // Unset 'active', since ID Stores only return active users.
                unset($entry[User::ACTIVE]);

                // Convert the resulting user info to a User.
                return self::getAsUser($entry);
            },
            $allActiveUsersInfo
        );
    }

    /**
     * Get the HTTP client to use.
     *
     * @return Client
     */
    protected function getHttpClient()
    {
        if ($this->httpClient === null) {
            $this->httpClient = new Client();
        }
        return $this->httpClient;
    }

    public function getIdStoreName(): string
    {
        return 'SecureUser';
    }
}
