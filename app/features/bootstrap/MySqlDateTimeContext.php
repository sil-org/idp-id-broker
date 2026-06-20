<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Behat\Context\Context;
use common\helpers\MySqlDateTime;
use Webmozart\Assert\Assert;
use Behat\Step\Given;
use Behat\Step\When;
use Behat\Step\Then;

class MySqlDateTimeContext implements Context
{
    /** @var  bool */
    protected $dateIsRecent;

    /** @var  int */
    protected $recentDays;

    /** @var  string */
    protected $interval;

    #[Given('I say that recent is in the last :arg1 days')]
    public function iSayThatRecentIsInTheLastXDays($recentDays)
    {
        $this->recentDays = $recentDays;
    }

    #[When('I ask if :arg1 days ago is recent')]
    public function iAskIfXDaysAgoIsRecent($daysAgo)
    {
        $diffConfig = "-" . $daysAgo . " days";
        $dbDate = MySqlDateTime::relative($diffConfig);

        $this->dateIsRecent = MySqlDateTime::dateIsRecent($dbDate, $this->recentDays);
    }

    #[When('I parse interval :interval')]
    public function iParseInterval($interval)
    {
        $this->interval = MySqlDateTime::interval($interval);
    }

    #[When('I invert interval :interval')]
    public function iInvertInterval($interval)
    {
        $this->interval = MySqlDateTime::invertInterval($interval);
    }

    #[Then('I see that that date is recent')]
    public function iSeeThatThatDateIsRecent()
    {
        Assert::true($this->dateIsRecent);
    }

    #[Then('I see that that date is not recent')]
    public function iSeeThatThatDateIsNotRecent()
    {
        Assert::false($this->dateIsRecent);
    }

    #[Then(':interval interval is returned')]
    public function intervalIsReturned($interval)
    {
        if ($interval == "<null>") {
            $interval = null;
        }

        Assert::eq($this->interval, $interval);
    }
}
