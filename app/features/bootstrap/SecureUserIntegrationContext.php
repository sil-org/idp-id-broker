<?php

namespace Sil\SilIdBroker\Behat\Context;

use common\components\adapters\SecureUserIdStore;
use Sil\PhpEnv\Env;

/**
 * Defines application features from the specific context.
 */
class SecureUserIntegrationContext extends IdStoreIntegrationContextBase
{
    public function __construct()
    {
        echo 'Testing integration with Secure User.' . PHP_EOL;
        parent::__construct();
    }

    /**
     * @Given I can make authenticated calls to the ID Store
     */
    public function iCanMakeAuthenticatedCallsToTheIdStore()
    {
        $secureUserApiUrl = Env::requireEnv('TEST_SECURE_USER_CONFIG_apiUrl');
        $secureUserApiKey = Env::requireEnv('TEST_SECURE_USER_CONFIG_apiKey');
        $secureUserApiSecret = Env::requireEnv('TEST_SECURE_USER_CONFIG_apiSecret');

        $this->idStore = new SecureUserIdStore([
            'apiUrl' => $secureUserApiUrl,
            'apiKey' => $secureUserApiKey,
            'apiSecret' => $secureUserApiSecret,
        ]);
    }

    /**
     * @When I ask the ID Store for a specific active user
     */
    public function iAskTheIdStoreForASpecificActiveUser()
    {
        $this->activeEmployeeId = Env::requireEnv('TEST_SECURE_USER_EMPLOYEE_ID');
        $this->result = $this->idStore->getActiveUser($this->activeEmployeeId);
    }
}
