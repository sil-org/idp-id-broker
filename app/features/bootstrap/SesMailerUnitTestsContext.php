<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use common\components\SesMailer;
use common\components\SesMessage;
use Webmozart\Assert\Assert;

class SesMailerUnitTestsContext extends YiiContext
{
    /** @var SesMailer */
    protected $mailer;

    /** @var SesMessage */
    protected $message;

    /** @var array */
    protected $emailArgs;

    #[Given('I have a new SesMailer')]
    public function iHaveANewSesMailer()
    {
        $this->mailer = new class () extends SesMailer {
            public function getEmailArgs($message): array
            {
                return $this->buildEmailArgs($message);
            }
        };
    }

    #[Given('I have a new SesMessage')]
    public function iHaveANewSesMessage()
    {
        $this->message = new SesMessage();
    }

    #[When('I initialize the SesMailer')]
    public function iInitializeTheSesMailer()
    {
        $this->mailer->init();
    }

    #[When('I set the AWS region to :region')]
    public function iSetTheAwsRegionTo($region)
    {
        $this->mailer->awsRegion = $region;
    }

    #[Then('the AWS region should be :region')]
    public function theAwsRegionShouldBe($region)
    {
        Assert::eq($this->mailer->awsRegion, $region);
    }

    #[When('I set the from address to :from')]
    public function iSetTheFromAddressTo($from)
    {
        $this->message->setFrom($from);
    }

    #[When('I set the to address to :to')]
    public function iSetTheToAddressTo($to)
    {
        $this->message->setTo($to);
    }

    #[When('I set the cc address to :cc')]
    public function iSetTheCcAddressTo($cc)
    {
        $this->message->setCc($cc);
    }

    #[When('I set the bcc address to :bcc')]
    public function iSetTheBccAddressTo($bcc)
    {
        $this->message->setBcc($bcc);
    }

    #[When('I set the subject to :subject')]
    public function iSetTheSubjectTo($subject)
    {
        $this->message->setSubject($subject);
    }

    #[When('I set the text body to :text')]
    public function iSetTheTextBodyTo($text)
    {
        $this->message->setTextBody($text);
    }

    #[When('I set the html body to :html')]
    public function iSetTheHtmlBodyTo($html)
    {
        $this->message->setHtmlBody($html);
    }

    #[When('I set the cc address to an array with :cc1 and :cc2')]
    public function iSetTheCcAddressToAnArrayWithAnd($cc1, $cc2)
    {
        $this->message->setCc([$cc1, $cc2]);
    }

    #[Then('the email arguments should be correctly built')]
    public function theEmailArgumentsShouldBeCorrectlyBuilt()
    {
        $this->emailArgs = $this->mailer->getEmailArgs($this->message);

        Assert::eq($this->emailArgs['Source'], $this->message->getFrom());
        Assert::eq($this->emailArgs['Destination']['ToAddresses'], $this->message->getTo());
        Assert::eq($this->emailArgs['Message']['Subject']['Data'], $this->message->getSubject());
        Assert::eq($this->emailArgs['Message']['Body']['Text']['Data'], $this->message->getTextBody());
        Assert::eq($this->emailArgs['Message']['Body']['Html']['Data'], $this->message->getHtmlBody());
    }

    #[Then('the email arguments should contain CC address :cc')]
    public function theEmailArgumentsShouldContainCcAddress($cc)
    {
        Assert::keyExists($this->emailArgs['Destination'], 'CcAddresses');
        Assert::inArray($cc, $this->emailArgs['Destination']['CcAddresses']);
    }

    #[Then('the email arguments should contain BCC address :bcc')]
    public function theEmailArgumentsShouldContainBccAddress($bcc)
    {
        Assert::keyExists($this->emailArgs['Destination'], 'BccAddresses');
        Assert::inArray($bcc, $this->emailArgs['Destination']['BccAddresses']);
    }

    #[Then('the email arguments should not contain CC addresses')]
    public function theEmailArgumentsShouldNotContainCcAddresses()
    {
        Assert::keyNotExists($this->emailArgs['Destination'], 'CcAddresses');
    }

    #[Then('the email arguments should not contain BCC addresses')]
    public function theEmailArgumentsShouldNotContainBccAddresses()
    {
        Assert::keyNotExists($this->emailArgs['Destination'], 'BccAddresses');
    }
}
