<?php

declare(strict_types=1);

namespace Messages\Control;

use Forms\Form;

class ContactForm extends Form
{
	public function __construct(?\Nette\ComponentModel\IContainer $parent = null, ?string $name = null)
	{
		parent::__construct($parent, $name);
		
		$this->addText('email')->setRequired()->addRule($this::EMAIL);
		$this->addText("message");
		$this->addAntispam('');
		$this->addDoubleClickProtection();
		$this->addSubmit('submit');
		
		//@TODO doplnit onSubmit
		//odeslat emaily jak spravci tak zakaznikovi
		
		$this->onSubmit[] = function (Form $form): void {
			$values = $form->getValues();
		};
	}
}
