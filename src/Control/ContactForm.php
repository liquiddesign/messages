<?php

declare(strict_types=1);

namespace Messages\Control;

use Forms\Form;
use Messages\DB\TemplateRepository;
use Nette;

class ContactForm extends Form
{
	public function __construct(?\Nette\ComponentModel\IContainer $parent = null, ?string $name = null, ?TemplateRepository $templateRepository = null)
	{
		parent::__construct($parent, $name);
		
		$this->addText('email')->setRequired()->addRule($this::EMAIL);
		$this->addText("message");
		$this->addAntispam('');
		$this->addDoubleClickProtection();
		$this->addSubmit('submit');
		
		$this->onSubmit[] = function (Form $form) use ($templateRepository): void {
			$values = $form->getValues();
			$mailer = new Nette\Mail\SendmailMailer();

			$mail = $templateRepository->createMessage("contact", ["text"=>$values->message]);
			$mailer->send($mail);
			
			$mail = $templateRepository->createMessage("contactInfo", ["text"=>$values->message], $values->email);
			$mailer->send($mail);
		};
	}
}
