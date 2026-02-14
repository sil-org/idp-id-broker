<?php

namespace Sil\SilIdBroker\Behat\Context;

use common\components\adapters\GoogleSheetsClient;
use common\components\adapters\GoogleSheetsIdStore;
use common\models\User;
use Sil\PhpEnv\Env;

/**
 * Defines application features from the specific context.
 */
class GoogleSheetsIntegrationContext extends IdStoreIntegrationContextBase
{
    private $googleSheetsClient = null;

    public function __construct()
    {
        echo 'Testing integration with Google Sheets.' . PHP_EOL;
        parent::__construct();
    }

    /**
     * @Given I can make authenticated calls to the ID Store
     */
    public function iCanMakeAuthenticatedCallsToTheIdStore()
    {
        $googleSheetsConfig = Env::getArrayFromPrefix('TEST_GOOGLE_SHEETS_CONFIG_');
        $this->idStore = new GoogleSheetsIdStore($googleSheetsConfig);
    }

    /**
     * @When I ask the ID Store for a specific active user
     */
    public function iAskTheIdStoreForASpecificActiveUser()
    {
        $this->activeEmployeeId = Env::requireEnv('TEST_GOOGLE_SHEETS_EMPLOYEE_ID');
        $this->result = $this->idStore->getActiveUser($this->activeEmployeeId);
    }

    protected function getAttributeForEachUser(string $attributeName): array
    {
        $googleSheetsClient = $this->getGoogleSheetsClient();
        $allUsersInfo = $googleSheetsClient->getAllUsersInfo();
        $attributeValues = [];

        foreach ($allUsersInfo as $userInfo) {
            $employeeId = $userInfo[User::EMPLOYEE_ID];
            $attributeValues[$employeeId] = $userInfo[$attributeName];
        }

        return $attributeValues;
    }

    protected function getAttributesForEachUser(array $attributeNames)
    {
        $googleSheetsClient = $this->getGoogleSheetsClient();
        $allInfoForAllUsers = $googleSheetsClient->getAllUsersInfo();
        $desiredInfoForAllUsers = [];
        foreach ($allInfoForAllUsers as $allInfoForThisUser) {
            $desiredInfoForThisUser = [];
            foreach ($attributeNames as $attributeName) {
                $desiredInfoForThisUser[$attributeName] = $allInfoForThisUser[$attributeName];
            }
            $employeeId = $allInfoForThisUser[User::EMPLOYEE_ID];
            $desiredInfoForAllUsers[$employeeId] = $desiredInfoForThisUser;
        }
        return $desiredInfoForAllUsers;
    }

    protected function getGoogleSheetsClient()
    {
        if ($this->googleSheetsClient === null) {
            $googleSheetsConfig = Env::getArrayFromPrefix('TEST_GOOGLE_SHEETS_CONFIG_');
            $this->googleSheetsClient = new GoogleSheetsClient($googleSheetsConfig);
        }
        return $this->googleSheetsClient;
    }

    /**
     * @When I update the last-synced value for a specific active user
     */
    public function iUpdateTheLastSyncedValueForASpecificActiveUser()
    {
        $this->activeEmployeeId = Env::requireEnv('TEST_GOOGLE_SHEETS_EMPLOYEE_ID');
        $this->idStore->updateSyncDatesIfSupported([$this->activeEmployeeId]);
    }
}
