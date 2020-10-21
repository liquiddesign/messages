<?php

declare(strict_types=1);

namespace Messages\Control;

use Forms\Form;

interface ISubscribeFormFactory
{
	public function create(): Form;
}
