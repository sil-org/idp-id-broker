<?php

namespace common\components;

use yii\base\NotSupportedException;
use yii\mail\BaseMessage;
use yii\mail\MessageInterface;

/*
 * SesMessage is a Yii2 message component to send email messages using AWS Simple Email Service
 *
 * Copied from sil-org/email-service
 */
class SesMessage extends BaseMessage
{
    /** @var string */
    private string $charset = '';

    /** @var string */
    private string $from = '';

    /** @var string[] */
    private array $to = [];

    /** @var string[] */
    private array $replyTo = [];

    /** @var string[] */
    private array $cc = [];

    /** @var string[] */
    private array $bcc = [];

    /** @var string */
    private string $subject = '';

    /** @var string */
    private string $textBody = '';

    /** @var string */
    private string $htmlBody = '';

    /**
     * @inheritdoc
     */
    public function getCharset(): string
    {
        return $this->charset ?? 'UTF-8';
    }

    /**
     * @inheritdoc
     */
    public function setCharset($charset): SesMessage|static
    {
        $this->charset = $charset;
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getFrom(): string
    {
        return $this->from;
    }

    /**
     * @inheritdoc
     */
    public function setFrom($from): SesMessage|static
    {
        if (is_array($from)) {
            if (isset($from[0])) {
                $this->from = $from[0];
            } else {
                $addresses = array_keys($from);
                $names = array_values($from);
                $this->from = sprintf('%s <%s>', $names[0], $addresses[0]);
            }
        } else {
            $this->from = $from;
        }
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getTo(): string|array
    {
        return $this->to;
    }

    /**
     * @inheritdoc
     */
    public function setTo($to): SesMessage|static
    {
        $this->to = static::formatAddresses($to);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getReplyTo(): string|array
    {
        if ($this->replyTo) {
            return $this->replyTo;
        }
        return $this->getFrom();
    }

    /**
     * @inheritdoc
     */
    public function setReplyTo($replyTo): SesMessage|static
    {
        $this->replyTo = static::formatAddresses($replyTo);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCc(): string|array
    {
        return $this->cc;
    }

    /**
     * @inheritdoc
     */
    public function setCc($cc): SesMessage|static
    {
        $this->cc = static::formatAddresses($cc);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getBcc(): string|array
    {
        return $this->bcc;
    }

    /**
     * @inheritdoc
     */
    public function setBcc($bcc): SesMessage|static
    {
        $this->bcc = static::formatAddresses($bcc);
        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getSubject(): string
    {
        return $this->subject;
    }

    /**
     * @inheritdoc
     */
    public function setSubject($subject): SesMessage|static
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * @return string
     */
    public function getTextBody(): string
    {
        if (empty($this->textBody)) {
            return strip_tags($this->htmlBody);
        }
        return $this->textBody;
    }

    /**
     * @inheritdoc
     */
    public function setTextBody($text): SesMessage|static
    {
        $this->textBody = $text;
        return $this;
    }

    /**
     * @return string
     */
    public function getHtmlBody(): string
    {
        if (empty($this->htmlBody)) {
            return htmlspecialchars($this->textBody);
        }
        return $this->htmlBody;
    }

    /**
     * @inheritdoc
     */
    public function setHtmlBody($html): SesMessage|static
    {
        $this->htmlBody = $html;
        return $this;
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException
     */
    public function attach($fileName, array $options = []): MessageInterface
    {
        throw new NotSupportedException('attach is not implemented');
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException
     */
    public function attachContent($content, array $options = []): MessageInterface
    {
        throw new NotSupportedException('attachContent is not implemented');
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException
     */
    public function embed($fileName, array $options = []): string
    {
        throw new NotSupportedException('embed is not implemented');
    }

    /**
     * @inheritdoc
     * @throws NotSupportedException
     */
    public function embedContent($content, array $options = []): string
    {
        throw new NotSupportedException('embedContent is not implemented');
    }

    /**
     * @inheritdoc
     */
    public function toString(): string
    {
        return $this->textBody;
    }

    /**
     * Format addresses as an array of strings, given a string or array as defined by Yii's MailInterface.
     * @param $email string|array
     * @return array
     */
    private static function formatAddresses(string|array $email): array
    {
        $email = is_array($email) ? $email : [$email];

        if (array_is_list($email)) {
            return $email;
        }

        $out = [];
        foreach ($email as $addr => $name) {
            $out[] = sprintf('%s <%s>', $name, $addr);
        }
        return $out;
    }
}
