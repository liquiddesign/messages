<?php

namespace Messages;

use Composer\Script\Event;
use Messages\DB\TemplateRepository;
use StORM\DIConnection;

class Scripts
{
	/**
	 * Trigger as event from composer
	 * @param \Composer\Script\Event $event Composer event
	 */
	public static function createTemplates(Event $event): void
	{
		$arguments = $event->getArguments();
		
		$class = $arguments[0] ?? '\App\Bootstrap';
		
		$container = \method_exists($class, 'createContainer') ? $class::createContainer() : $class::boot()->createContainer();
		
		//@TODO doplnit vytvoreni DB zaznamu dle konfigu a template
		$templates = $container->getByType(TemplateRepository::class);
	}
}
