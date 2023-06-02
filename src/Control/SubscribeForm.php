<?php

declare(strict_types=1);

namespace Messages\Control;

use Forms\Form;
use Messages\DB\EmailRepository;
use Messages\DB\TemplateRepository;
use Nette;

class SubscribeForm extends Form
{
	public function __construct(
		?\Nette\ComponentModel\IContainer $parent = null,
		?string $name = null,
		?EmailRepository $emailRepository = null,
		?TemplateRepository $templateRepository = null,
		?Nette\Mail\Mailer $mailer = null,
	) {
		parent::__construct($parent, $name);
		
		$this->addText('email')->setRequired()->addRule($this::EMAIL);
		$this->addAntispam('');
		$this->addDoubleClickProtection();
		$this->addSubmit('submit');
		
		$this->onSubmit[] = function (Form $form) use ($emailRepository, $templateRepository, $mailer): void {
			/** @var \stdClass $values */
			$values = $form->getValues();

			if (!$mailer) {
				return;
			}

			$emailRepository->createOne((array) $values + ['created' => new \Carbon\Carbon()]);

			$mail = $templateRepository->createMessage('contactInfo', [], $values->email);
			$mailer->send($mail);
		};
	}
}
