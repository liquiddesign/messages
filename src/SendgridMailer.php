<?php

namespace Messages;

use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Nette\SmartObject;
use SendGrid;
use SendGrid\Email;

class SendgridMailer implements Mailer
{
	use SmartObject;

    const ENDPOINT = "https://api.sendgrid.com/";

    private string $key;

    private string $tempFolder;

    private array $tempFiles = [];

	/**
	 * MailSender constructor
	 *
	 * @param string $key
	 * @param string $tempFolder
	 */
    public function __construct(string $key, string $tempFolder)
    {
        $this->key = $key;
        $this->tempFolder = $tempFolder;
    }

	/**
	 * @param string $key
	 */
    public function setKey(string $key)
    {
        $this->key = $key;
    }

	/**
	 * @param Message $message
	 * @throws SendGrid\Exception
	 */
    public function send(Message $message):void
    {
        $sendGrid = new SendGrid($this->key);
        $email = new Email();

        $from = $message->getFrom();
        reset($from);
        $key = key($from);

        $email->setFrom($key)
            ->setFromName($from[$key])
            ->setSubject($message->getSubject())
            ->setText($message->getBody())
            ->setHtml($message->getHtmlBody());

        foreach ($message->getAttachments() as $attachement) {
            $header = $attachement->getHeader('Content-Disposition');
            preg_match('/filename\=\"(.*)\"/', $header, $result);
            $originalFileName = $result[1];

            $filePath = $this->saveTempAttachement($attachement->getBody());

            $email->addAttachment($filePath, $originalFileName);
        }

        foreach ($message->getHeader('To') as $recipient => $name) {
            $email->addTo($recipient);
        }

        foreach ($message->getHeader('Cc') as $recipient => $name) {
            $email->addCc($recipient);
        }

        foreach ($message->getHeader('Bcc') as $recipient => $name) {
            $email->addBcc($recipient);
        }

        $sendGrid->send($email);

        $this->cleanUp();
    }

    private function saveTempAttachement($body)
    {
        $filePath = $this->tempFolder . '/' . md5($body);
        file_put_contents($filePath, $body);
        array_push($this->tempFiles, $filePath);

        return $filePath;
    }

    private function cleanUp()
    {
        foreach ($this->tempFiles as $file) {
            unlink($file);
        }
    }

}
