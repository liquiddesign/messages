<?php

declare(strict_types=1);

namespace Messages\Bridges;

use Nette\Schema\Expect;
use Nette\Schema\Schema;

class MessagesDI extends \Nette\DI\CompilerExtension
{
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'test' => Expect::string()->required(),
		]);
	}
	
	public function loadConfiguration(): void
	{
		$config = (array) $this->getConfig();
		
		/** @var \Nette\DI\ContainerBuilder $builder */
		$builder = $this->getContainerBuilder();
		
		$pages = $builder->addDefinition($this->prefix('db'))->setType(\Messages\DB\TemplateRepository::class);
		$pages->addSetup('setTest', [$config['test']]);
		
		return;
	}
}
