<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use common\components\adapters\WorkdayIdStore;
use Webmozart\Assert\Assert;

class WorkdayContext implements Context
{
    private $idStore;

    private $users = [];

    /**
     * @Given a Workday ID Store with default configuration
     */
    public function aWorkdayIdStoreWithDefaultConfiguration()
    {
        $this->idStore = $this->createWorkdayIdStore([]);
    }

    /**
     * @Given a Workday ID Store with configuration:
     */
    public function aWorkdayIdStoreWithConfiguration(TableNode $table)
    {
        $config = $table->getRowsHash();
        $this->idStore = $this->createWorkdayIdStore($config);
    }

    /**
     * @Then the ID store name should be :name
     */
    public function theIdStoreNameShouldBe($name)
    {
        Assert::eq($this->idStore->getIdStoreName(), $name);
    }

    /**
     * @When I generate group lists for the following users:
     */
    public function iGenerateGroupListsForTheFollowingUsers(TableNode $table)
    {
        $this->users = $table->getHash();
        $this->idStore->generateGroupsLists($this->users);
    }

    /**
     * @Then user :index should have the groups :expectedGroups
     */
    public function userShouldHaveTheGroups($index, $expectedGroups)
    {
        Assert::eq($this->users[$index]['Groups'], $expectedGroups);
    }

    private function createWorkdayIdStore(array $config): WorkdayIdStore
    {
        return new WorkdayIdStore(array_merge($config, [
            'apiUrl' => 'https://workday.example.com',
            'username' => 'username',
            'password' => 'password',
        ]));
    }
}
