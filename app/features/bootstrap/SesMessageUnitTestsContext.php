<?php

namespace Sil\SilIdBroker\Behat\Context;

use Behat\Hook\BeforeScenario;
use Behat\Step\Given;
use Behat\Step\Then;
use Behat\Step\When;
use common\components\SesMessage;
use Webmozart\Assert\Assert;
use yii\base\NotSupportedException;

class SesMessageUnitTestsContext extends YiiContext
{
    /** @var SesMessage */
    protected $message;

    protected $exceptionThrown;

    #[BeforeScenario]
    public function beforeScenario()
    {
        $this->exceptionThrown = null;
    }

    #[Given('I have a new SesMessage')]
    public function iHaveANewSesMessage()
    {
        $this->message = new SesMessage();
    }

    #[When('I set the charset to :charset')]
    public function iSetTheCharsetTo($charset)
    {
        $this->message->setCharset($charset);
    }

    #[Then('the charset should be :charset')]
    public function theCharsetShouldBe($charset)
    {
        Assert::eq($this->message->getCharset(), $charset);
    }

    #[When('I set the from address to :from')]
    public function iSetTheFromAddressTo($from)
    {
        $this->message->setFrom($from);
    }

    #[Then('the from address should be :from')]
    public function theFromAddressShouldBe($from)
    {
        Assert::eq($this->message->getFrom(), $from);
    }

    #[When('I set the from address to an array with :email as key and :name as value')]
    public function iSetTheFromAddressToAnArrayWithEmailAsKeyAndNameAsValue($email, $name)
    {
        $this->message->setFrom([$email => $name]);
    }

    #[When('I set the to address to :to')]
    public function iSetTheToAddressTo($to)
    {
        $this->message->setTo($to);
    }

    #[Then('the to address should contain :to')]
    public function theToAddressShouldContain($to)
    {
        $addresses = $this->message->getTo();
        if (is_array($addresses)) {
            Assert::inArray($to, $addresses);
        } else {
            Assert::eq($addresses, $to);
        }
    }

    #[When('I set the to address to an array with :r1 and :r2')]
    public function iSetTheToAddressToAnArrayWithAnd($r1, $r2)
    {
        $this->message->setTo([$r1, $r2]);
    }

    #[When('I set the to address to the following:')]
    public function iSetTheToAddressToTheFollowing(\Behat\Gherkin\Node\TableNode $table)
    {
        $addresses = [];
        foreach ($table as $row) {
            $addresses[$row['email']] = $row['name'];
        }
        $this->message->setTo($addresses);
    }

    #[When('I set the reply-to address to :replyTo')]
    public function iSetTheReplyToAddressTo($replyTo)
    {
        $this->message->setReplyTo($replyTo);
    }

    #[Then('the reply-to address should contain :replyTo')]
    public function theReplyToAddressShouldContain($replyTo)
    {
        $addresses = $this->message->getReplyTo();
        if (is_array($addresses)) {
            Assert::inArray($replyTo, $addresses);
        } else {
            Assert::eq($addresses, $replyTo);
        }
    }

    #[Then('the reply-to address should be empty')]
    public function theReplyToAddressShouldBeEmpty()
    {
        Assert::isEmpty($this->message->getReplyTo());
    }

    #[When('I set the cc address to :cc')]
    public function iSetTheCcAddressTo($cc)
    {
        $this->message->setCc($cc);
    }

    #[Then('the cc address should contain :cc')]
    public function theCcAddressShouldContain($cc)
    {
        $addresses = $this->message->getCc();
        if (is_array($addresses)) {
            Assert::inArray($cc, $addresses);
        } else {
            Assert::eq($addresses, $cc);
        }
    }

    #[When('I set the bcc address to :bcc')]
    public function iSetTheBccAddressTo($bcc)
    {
        $this->message->setBcc($bcc);
    }

    #[Then('the bcc address should contain :bcc')]
    public function theBccAddressShouldContain($bcc)
    {
        $addresses = $this->message->getBcc();
        if (is_array($addresses)) {
            Assert::inArray($bcc, $addresses);
        } else {
            Assert::eq($addresses, $bcc);
        }
    }

    #[When('I set the subject to :subject')]
    public function iSetTheSubjectTo($subject)
    {
        $this->message->setSubject($subject);
    }

    #[Then('the subject should be :subject')]
    public function theSubjectShouldBe($subject)
    {
        Assert::eq($this->message->getSubject(), $subject);
    }

    #[When('I set the text body to :text')]
    public function iSetTheTextBodyTo($text)
    {
        $this->message->setTextBody($text);
    }

    #[Then('the text body should be :text')]
    public function theTextBodyShouldBe($text)
    {
        Assert::eq($this->message->getTextBody(), $text);
    }

    #[When('I set the html body to :html')]
    public function iSetTheHtmlBodyTo($html)
    {
        $this->message->setHtmlBody($html);
    }

    #[Then('the html body should be :html')]
    public function theHtmlBodyShouldBe($html)
    {
        Assert::eq($this->message->getHtmlBody(), $html);
    }

    #[Then('toString should return :expected')]
    public function tostringShouldReturn($expected)
    {
        Assert::eq($this->message->toString(), $expected);
    }

    #[When('I try to attach a file')]
    public function iTryToAttachAFile()
    {
        try {
            $this->message->attach('test.txt');
        } catch (NotSupportedException $e) {
            $this->exceptionThrown = $e;
        }
    }

    #[When('I try to attach content')]
    public function iTryToAttachContent()
    {
        try {
            $this->message->attachContent('content');
        } catch (NotSupportedException $e) {
            $this->exceptionThrown = $e;
        }
    }

    #[When('I try to embed a file')]
    public function iTryToEmbedAFile()
    {
        try {
            $this->message->embed('test.txt');
        } catch (NotSupportedException $e) {
            $this->exceptionThrown = $e;
        }
    }

    #[When('I try to embed content')]
    public function iTryToEmbedContent()
    {
        try {
            $this->message->embedContent('content');
        } catch (NotSupportedException $e) {
            $this->exceptionThrown = $e;
        }
    }

    #[Then('it should throw a NotSupportedException')]
    public function itShouldThrowANotSupportedException()
    {
        Assert::isInstanceOf($this->exceptionThrown, NotSupportedException::class);
    }
}
