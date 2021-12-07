<?php

namespace Messages;

use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Nette\SmartObject;
use SendGrid;
use SendGrid\Email;

/**
 * @deprecated Use sendgrid-package package instead
 */
class SendgridMailer implements Mailer
{
	use SmartObject;
	
	public const ENDPOINT = "https://api.sendgrid.com/";

	private string $key;

	private string $tempFolder;
	
	/**
	 * @var string[]
	 */
	private array $tempFiles = [];

	/**
	 * MailSender constructor
	 * @param string $key
	 * @param string $tempFolder
	 */
	public function __construct(string $key, string $tempFolder)
	{
		$this->key = $key;
		$this->tempFolder = $tempFolder;
	}

	public function setKey(string $key): void
	{
		$this->key = $key;
	}

	/**
	 * @param \Nette\Mail\Message $message
	 * @throws \SendGrid\Exception
	 */
	public function send(Message $message): void
	{
		$sendGrid = new SendGrid($this->key);
		$email = new Email();

		$from = $message->getFrom();
		\reset($from);
		$key = \key($from);

		$email->setFrom($key)
			->setFromName($from[$key])
			->setSubject($message->getSubject())
			->setText($message->getBody())
			->setHtml($message->getHtmlBody())
			->addHeader('dummy', '1');

		foreach ($message->getAttachments() as $attachement) {
			$header = $attachement->getHeader('Content-Disposition');
			\preg_match('/filename\=\"(.*)\"/', $header, $result);
			$originalFileName = $result[1];

			$filePath = $this->saveTempAttachement($attachement->getBody());

			$email->addAttachment($filePath, $originalFileName);
		}

		foreach (\array_keys($message->getHeader('To')) as $recipient) {
			$email->addTo($recipient);
		}

		if ($message->getHeader('Cc')) {
			foreach (\array_keys($message->getHeader('Cc')) as $recipient) {
				$email->addCc($recipient);
			}
		}

		if ($message->getHeader('Bcc')) {
			foreach (\array_keys($message->getHeader('Bcc')) as $recipient) {
				$email->addBcc($recipient);
			}
		}

		$sendGrid->send($email);

		$this->cleanUp();
	}

	private function saveTempAttachement($body): string
	{
		$filePath = $this->tempFolder . '/' . \md5($body);
		\file_put_contents($filePath, $body);
		\array_push($this->tempFiles, $filePath);

		return $filePath;
	}

	private function cleanUp(): void
	{
		foreach ($this->tempFiles as $file) {
			if (\is_file($file)) {
				\unlink($file);
			}
		}
	}
}
