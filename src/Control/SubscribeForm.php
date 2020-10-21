<?php

declare(strict_types=1);

namespace Messages\Control;

use Forms\Form;
use Messages\DB\EmailRepository;
use Messages\DB\TemplateRepository;
use Nette;

class SubscribeForm extends Form
{
	public function __construct(?\Nette\ComponentModel\IContainer $parent = null, ?string $name = null, ?EmailRepository $emailRepository = null, ?TemplateRepository $templateRepository = null)
	{
		parent::__construct($parent, $name);
		
		$this->addText('email')->setRequired()->addRule($this::EMAIL);
		$this->addAntispam('');
		$this->addDoubleClickProtection();
		$this->addSubmit('submit');
		
		$this->onSubmit[] = function (Form $form) use ($emailRepository, $templateRepository): void {
			$values=$form->getValues();
			$emailRepository->createOne((array)$values + ["created" => new \DateTime()]);
			$mail = $templateRepository->createMessage("contactInfo", [], $values->email);
			$mailer = new Nette\Mail\SendmailMailer();
			$mailer->send($mail);
		};
	}
}
