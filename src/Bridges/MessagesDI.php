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
			'email' => Expect::string(),
			'alias' => Expect::string(),
			'templateMapping' => Expect::structure([
				'rootPaths' => Expect::array(),
				'directory' => Expect::string("templates"),
				'filemask' => Expect::string("email-%s.latte"),
			]),
			'templates' => Expect::structure([
				'messages' => Expect::list([]),
			]),
		]);
	}
	
	public function loadConfiguration(): void
	{
		$config = (array)$this->getConfig();
		
		/** @var \Nette\DI\ContainerBuilder $builder */
		$builder = $this->getContainerBuilder();
		
		$pages = $builder->addDefinition($this->prefix('db'))->setType(\Messages\DB\TemplateRepository::class);
		$pages->addSetup('setUp', [$config]);
		
		return;
	}
}
