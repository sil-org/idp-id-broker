<?php

namespace common\components\adapters;

use common\sync\User;
use Exception;
use GuzzleHttp\Client;
use InvalidArgumentException;
use yii\helpers\Json;

class WorkdayIdStore extends IdStoreBase
{
    public $apiUrl = null;
    public $username = null;
    public $password = null;
    public $groupsFields = null;

    public $timeout = 45; // Timeout in seconds (per call to ID Store API).

    protected $httpClient = null;

    private const ManagerEmail = 'Manager_Email';

    public function init()
    {
        $requiredProperties = [
            'apiUrl',
            'username',
            'password',
        ];
        foreach ($requiredProperties as $requiredProperty) {
            if (empty($this->$requiredProperty)) {
                throw new InvalidArgumentException(sprintf(
                    'No %s was provided.',
                    $requiredProperty
                ), 1532982562);
            }
        }

        parent::init();
    }

    public static function getFieldNameMap(): array
    {
        return [
            // 'active' field isn't needed, since all Workday records returned are active.
            'Employee_Number' => User::EMPLOYEE_ID,
            'First_Name' => User::FIRST_NAME,
            'Last_Name' => User::LAST_NAME,
            'Display_Name' => User::DISPLAY_NAME,
            'Email' => User::EMAIL,
            'Username' => User::USERNAME,
            'Account_Locked__Disabled_or_Expired' => User::LOCKED,
            'requireMfa' => User::REQUIRE_MFA,
            self::ManagerEmail => User::MANAGER_EMAIL,
            'Personal_Email' => User::PERSONAL_EMAIL,
            'Groups' => User::GROUPS,

            'HR_Contact_Name' => User::HR_CONTACT_NAME,
            'HR_Contact_Email' => User::HR_CONTACT_EMAIL,
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
        throw new Exception(__FUNCTION__ . ' not yet implemented');
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
        $response = $this->getHttpClient()->get($this->apiUrl, [
            'auth' => [$this->username, $this->password, 'basic'],
            'connect_timeout' => $this->timeout,
            'headers' => [
                'Accept' => 'application/json',
                'Accept-Encoding' => 'gzip',
            ],
            'http_errors' => false,
        ]);

        $statusCode = (int)$response->getStatusCode();
        if ($statusCode === 404) {
            $allActiveUsers = null;
        } elseif (($statusCode >= 200) && ($statusCode <= 299)) {
            $data = Json::decode($response->getBody());
            $allActiveUsers = $data['Report_Entry'] ?? null;
        } else {
            throw new Exception(sprintf(
                'Unexpected response (%s %s): %s',
                $response->getStatusCode(),
                $response->getReasonPhrase(),
                $response->getBody()
            ), $response->getStatusCode());
        }

        if (! is_array($allActiveUsers)) {
            throw new Exception(sprintf(
                'Unexpected result when getting all active users: %s',
                var_export($allActiveUsers, true)
            ), 1532982679);
        }

        static::addBlankProperty(self::ManagerEmail, $allActiveUsers);

        $this->generateGroupsLists($allActiveUsers);

        return self::getAsUsers($allActiveUsers);
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
        return 'Workday';
    }

    public function generateGroupsLists(array &$users)
    {
        if ($this->groupsFields === null) {
            $groupsFields = [
                'company_ids',
                'ou_tree',
            ];
        } else {
            $groupsFields = explode(',', $this->groupsFields);
        }

        foreach ($users as $key => $user) {
            $groups = [];
            foreach ($groupsFields as $groupsField) {
                $value = $user[$groupsField] ?? '';
                if (strlen($value) > 0) {
                    $groupsSubList = explode(' ', $value);
                    $groups = array_merge($groups, $groupsSubList);
                }
            }
            $users[$key]['Groups'] = implode(',', $groups);
        }
    }

    private static function addBlankProperty(string $property, array &$items)
    {
        foreach ($items as &$next) {
            $next[$property] ??= '';
        }
    }
}
