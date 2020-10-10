<?php

declare(strict_types=1);

namespace Messages\DB;

use Latte\Loaders\StringLoader;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\ITemplate;
use Nette\Application\UI\ITemplateFactory;
use Nette\Mail\Message;
use StORM\DIConnection;
use StORM\Repository;
use StORM\SchemaManager;

class TemplateRepository extends Repository
{
	/**
	 * @var \Nette\Application\LinkGenerator
	 */
	private LinkGenerator $linkGenerator;
	
	/**
	 * @var \Nette\Application\UI\ITemplateFactory
	 */
	private ITemplateFactory $templateFactory;
	
	public function __construct(DIConnection $connection, SchemaManager $schemaManager, LinkGenerator $linkGenerator, ITemplateFactory $templateFactory)
	{
		parent::__construct($connection, $schemaManager);
		$this->linkGenerator = $linkGenerator;
		$this->templateFactory = $templateFactory;
	}
	
	private function createTemplate(): ITemplate
	{
		$template = $this->templateFactory->createTemplate();
		$template->getLatte()->addProvider('uiControl', $this->linkGenerator);
		$template->getLatte()->setLoader(new StringLoader());
		
		return $template;
	}
	
	public function createEmail($params): Message
	{
		$template = $this->createTemplate();
		
		$html = $template->renderToString('--{$test}--', $params);
		
		$mail = new Message;
		$mail->setHtmlBody($html);
		
		return $mail;
	}
}
