<?php

declare(strict_types=1);

namespace Messages\Control;

interface IContactFormFactory
{
	public function create(): ContactForm;
}
