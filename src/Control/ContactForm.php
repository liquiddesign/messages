<?php

declare(strict_types=1);

namespace Messages\Control;

use Forms\Form;
use Messages\DB\TemplateRepository;
use Nette;
use Tracy\Debugger;

class ContactForm extends Form
{
	public function __construct(?\Nette\ComponentModel\IContainer $parent = null, ?string $name = null, ?TemplateRepository $templateRepository = null, ?Nette\Mail\Mailer $mailer = null)
	{
		parent::__construct($parent, $name);
		
		$this->addText('email')->setRequired()->addRule($this::EMAIL);
		$this->addText('message');
		$this->addAntispam('spam');
		$this->addDoubleClickProtection();
		$this->addSubmit('submit');
		
		$this->onSuccess[] = function (Form $form) use ($templateRepository, $mailer): void {
			/** @var \stdClass $values */
			$values = $form->getValues();

			if (!$mailer) {
				return;
			}

			$emailVariables = ['text' => $values->message, 'email' => $values->email];

			Debugger::log(Nette\Utils\Json::encode($emailVariables), 'contactForm');

			$mail = $templateRepository->createMessage('contact', $emailVariables, null, null, $values->email);
			$mailer->send($mail);
			
			$mail = $templateRepository->createMessage('contactInfo', $emailVariables, $values->email);
			$mailer->send($mail);
		};
	}
}
