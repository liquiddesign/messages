<?php

declare(strict_types=1);

namespace Messages\Control;

use Forms\Form;

abstract class ISubscribeFormFactory
{
	public static function create(?\Nette\ComponentModel\IContainer $parent = null, ?string $name = null): Form
	{
		return new SubscribeForm($parent, $name);
	}
}
