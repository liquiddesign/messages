<?php

declare(strict_types=1);

namespace Messages\DB;

use Latte\Loaders\StringLoader;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\ITemplateFactory;
use Nette\Mail\Message;
use StORM\DIConnection;
use StORM\Repository;
use StORM\SchemaManager;

class TemplateRepository extends Repository
{
	private LinkGenerator $linkGenerator;
	
	private ITemplateFactory $templateFactory;
	
	private string $test = '';
	
	public function __construct(DIConnection $connection, SchemaManager $schemaManager, LinkGenerator $linkGenerator, ITemplateFactory $templateFactory)
	{
		parent::__construct($connection, $schemaManager);

		$this->linkGenerator = $linkGenerator;
		$this->templateFactory = $templateFactory;
	}
	
	public function setTest(string $test): void
	{
		$this->test = $test;
		
		//@TODO remove this
		return;
	}
	
	public function getTest(): string
	{
		return $this->test;
	}
	
	public function createMessage(string $id, array $params, ?string $email = null): Message
	{
		$template = $this->createTemplate();
		
		$parsedPath = \explode(\DIRECTORY_SEPARATOR, __DIR__);
		$rootLevel = \count($parsedPath) - \array_search('src', $parsedPath);
		
		require \dirname(__DIR__, $rootLevel) . '/vendor/autoload.php';
		
		$message = $this->one($id, false);

		if (!$message) {
			$message = new Template([]);
			$file = $this->getFileTemplate();

			if (!$file) {
				throw new \InvalidArgumentException();
			}
		} else {
			//@TODO
		}
		
		$html = $template->renderToString('--{$test}--{do $message->subject = "test"}', $params + ['message' => $message]);
		
		\dump($message);
		$mail = new Message();
		$mail->setHtmlBody($html);
		$mail->addTo($email);
		
		return $mail;
	}

	private function createTemplate(): \Nette\Bridges\ApplicationLatte\Template
	{
		/** @var \Nette\Bridges\ApplicationLatte\Template $template */
		$template = $this->templateFactory->createTemplate();
		$template->getLatte()->addProvider('uiControl', $this->linkGenerator);
		$template->getLatte()->setLoader(new StringLoader());
		
		return $template;
	}
	
	private function getFileTemplate(): string
	{
		//@TODO IMPLEMENT
		return '';
	}
}
