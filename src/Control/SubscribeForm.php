<?php

declare(strict_types=1);

namespace Messages\Control;

use Forms\Form;

class SubscribeForm extends Form
{
	public function __construct(?\Nette\ComponentModel\IContainer $parent = null, ?string $name = null)
	{
		parent::__construct($parent, $name);
		
		$this->addText('email');
		$this->addAntispam('');
		$this->addDoubleClickProtection();
		$this->addSubmit('submit');
		
	}
	
	public function render(...$args):void
	{
		$this->template->setFile(__DIR__ . '/'.basename(__FILE__, '.php').'.latte');
		$this->template->render();
	}
}
