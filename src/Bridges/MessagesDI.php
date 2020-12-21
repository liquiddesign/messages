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
				'directory' => Expect::string('templates'),
				'fileMask' => Expect::string('email-%s.latte'),
				'globalDirectory' => Expect::string('globalTemplates'),
				'globalFileMask' => Expect::string('global-%s.latte'),
			]),
			'templates' => Expect::structure([
				'rootPaths' => Expect::array(),
				'messages' => Expect::array(),
			]),
		]);
	}
	
	public function loadConfiguration(): void
	{
		$config = (array)$this->getConfig();
		
		/** @var \Nette\DI\ContainerBuilder $builder */
		$builder = $this->getContainerBuilder();
		
		$pages = $builder->addDefinition($this->prefix('db'))->setType(\Messages\DB\TemplateRepository::class);
		$pages->addSetup('setEmailAndAlias', [$config['email'], $config['alias']]);
		$pages->addSetup('setTemplateMapping', [
			$config['templateMapping']->rootPaths,
			$config['templateMapping']->directory,
			$config['templateMapping']->fileMask,
		]);
		$pages->addSetup('setGlobalTemplateMapping', [
			$config['templateMapping']->globalDirectory,
			$config['templateMapping']->globalFileMask,
		]);
		$pages->addSetup('setDbTemplates', [
			$config['templates']->messages,
			$config['templates']->rootPaths,
		]);
		
		return;
	}
}
