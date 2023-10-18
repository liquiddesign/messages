<?php

namespace Messages;

use Nette\Mail\Mailer;
use Nette\Mail\Message;
use Tracy\Debugger;

class VoidMailer implements Mailer
{
	public function send(Message $mail): void
	{
		Debugger::$maxLength = 5000;
		Debugger::barDump($mail);
		Debugger::log($mail);
	}
}
