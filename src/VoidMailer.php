<?php

namespace Messages;

use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Tracy\Debugger;
use Tracy\ILogger;

class VoidMailer implements Mailer
{
	public function send(Message $mail): void
	{
		Debugger::barDump($mail);
		Debugger::$maxLength = 1500;

		Debugger::barDump($mail->getBody());
		Debugger::log($mail->getBody());
	}
}
