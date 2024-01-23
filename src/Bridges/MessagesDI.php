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
			'defaultMutation' => Expect::string(),
			/** Production - normal behaviour, Develop - change all target emails to specified email(s) (if none - dev@lqd.cz) */
			'mode' => Expect::anyOf('production', 'develop'),
			'developEmails' => Expect::listOf(Expect::email()),
		]);
	}
	
	public function loadConfiguration(): void
	{
		$config = (array) $this->getConfig();
		
		/** @var \Nette\DI\ContainerBuilder $builder */
		$builder = $this->getContainerBuilder();
		
		$templateRepository = $builder->addDefinition($this->prefix('db'))->setType(\Messages\DB\TemplateRepository::class);
		$templateRepository->addSetup('setEmailAndAlias', [$config['email'], $config['alias']]);
		$templateRepository->addSetup('setTemplateMapping', [
			$config['templateMapping']->rootPaths,
			$config['templateMapping']->directory,
			$config['templateMapping']->fileMask,
		]);
		$templateRepository->addSetup('setGlobalTemplateMapping', [
			$config['templateMapping']->globalDirectory,
			$config['templateMapping']->globalFileMask,
		]);
		$templateRepository->addSetup('setDbTemplates', [
			$config['templates']->messages,
			$config['templates']->rootPaths,
		]);
		$templateRepository->addSetup('setDefaultMutation', [$config['defaultMutation']]);

		if ($config['mode'] === 'develop') {
			$templateRepository->addSetup('setDevelopEmails', [$config['developEmails'] ?: ['dev@lqd.cz']]);
		}

		return;
	}
}
