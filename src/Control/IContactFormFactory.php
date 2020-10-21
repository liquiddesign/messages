<?php

declare(strict_types=1);

namespace Messages\Control;

use Forms\Form;

interface IContactFormFactory
{
	public function create(): Form;
}
