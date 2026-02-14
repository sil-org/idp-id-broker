<?php

namespace Sil\SilIdBroker\Behat\Context;

use common\components\adapters\WorkdayIdStore;
use Sil\PhpEnv\Env;

/**
 * Defines application features from the specific context.
 */
class WorkdayIntegrationContext extends IdStoreIntegrationContextBase
{
    public function __construct()
    {
        echo 'Testing integration with Workday.' . PHP_EOL;
        parent::__construct();
    }

    /**
     * @Given I can make authenticated calls to the ID Store
     */
    public function iCanMakeAuthenticatedCallsToTheIdStore()
    {
        $workdayApiUrl = Env::requireEnv('TEST_WORKDAY_CONFIG_apiUrl');
        $workdayUsername = Env::requireEnv('TEST_WORKDAY_CONFIG_username');
        $workdayPassword = Env::requireEnv('TEST_WORKDAY_CONFIG_password');

        $this->idStore = new WorkdayIdStore([
            'apiUrl' => $workdayApiUrl,
            'username' => $workdayUsername,
            'password' => $workdayPassword,
        ]);
    }

    /**
     * @When I ask the ID Store for a specific active user
     */
    public function iAskTheIdStoreForASpecificActiveUser()
    {
        $this->activeEmployeeId = Env::requireEnv('TEST_WORKDAY_EMPLOYEE_ID');
        $this->result = $this->idStore->getActiveUser($this->activeEmployeeId);
    }
}
