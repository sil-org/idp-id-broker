<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use common\helpers\SentryHelper;
use Sentry\Event;
use Sentry\ExceptionDataBag;
use Webmozart\Assert\Assert;

class SentryHelperUnitTestsContext extends YiiContext
{
    /** @var Event */
    protected $event;

    #[Given('I have a Sentry event with the exception :value')]
    public function iHaveASentryEventWithTheException($value): void
    {
        $this->event = Event::createEvent();
        $this->event->setExceptions([new ExceptionDataBag(new \PDOException($value))]);
    }

    #[When('I group DB connection errors')]
    public function iGroupDbConnectionErrors(): void
    {
        SentryHelper::groupDbConnectionErrors($this->event);
    }

    #[Then('the event should be grouped as :group')]
    public function theEventShouldBeGroupedAs($group): void
    {
        Assert::eq($this->event->getFingerprint(), ['db-connection-error', $group]);
    }

    #[Given('I have a Sentry event with the message :message')]
    public function iHaveASentryEventWithTheMessage($message): void
    {
        $this->event = Event::createEvent();
        $this->event->setMessage($message);
    }

    #[Then('the event should keep its default grouping')]
    public function theEventShouldKeepItsDefaultGrouping(): void
    {
        Assert::isEmpty($this->event->getFingerprint());
    }
}
