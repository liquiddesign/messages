<?php

declare(strict_types=1);

namespace Messages\DB;

use _HumbugBoxaf515cad4e15\Nette\Neon\Exception;
use Latte\Loaders\StringLoader;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\ITemplateFactory;
use Nette\IOException;
use Nette\Mail\Message;
use Nette\Utils\FileSystem;
use Nette\Utils\Validators;
use StORM\DIConnection;
use StORM\Exception\NotExistsException;
use StORM\Repository;
use StORM\SchemaManager;

class TemplateRepository extends Repository
{
	private LinkGenerator $linkGenerator;
	
	private ITemplateFactory $templateFactory;
	
	/**
	 * @var mixed[]
	 */
	private array $config;
	
	public function __construct(DIConnection $connection, SchemaManager $schemaManager, LinkGenerator $linkGenerator, ITemplateFactory $templateFactory)
	{
		parent::__construct($connection, $schemaManager);
		
		$this->linkGenerator = $linkGenerator;
		$this->templateFactory = $templateFactory;
		$this->schemaManager = $schemaManager;
	}
	
	public function setUp(?array $config): void
	{
		if ($config === null) {
			return;
		}
		
		$this->config = $config;
	}
	
	public function createMessage(string $id, array $params, ?string $email = null): Message
	{
		$template = $this->createTemplate();
		
		$parsedPath = \explode(\DIRECTORY_SEPARATOR, __DIR__);
		$rootLevel = \count($parsedPath) - \array_search('src', $parsedPath);
		
		require \dirname(__DIR__, $rootLevel) . '/vendor/autoload.php';
		
		/** @var \Messages\DB\Template|null $message */
		$message = $this->one($id, false);
		
		if (!$message) {
			$message = new Template([]);
			$file = $this->getFileTemplate($id);
			
			if (!$file) {
				throw new \InvalidArgumentException();
			}

			$html = $template->renderToString($file, $params + ['message' => $message]);
			
			try {
				$message->getValue('type');
			} catch (NotExistsException $e) {
				throw new Exception('Missing parameter "type" in template file!');
			}
			
			try {
				$message->getValue('cc');
			} catch (NotExistsException $e) {
				$message->cc=null;
			}
		} else {
			$html = $template->renderToString($message->html, $params + ['message' => $message]);
		}
		
		$mail = new Message();
		
		$mailAddress = $this->config['email'] ?: $message->email;
		$alias = $this->config['alias'] ?: $message->alias;
		
		if ($message->type === 'outgoing') {
			$mail->setFrom($mailAddress, $alias);
			$mail->addTo($email);
		} else {
			$mail->setFrom($email);
			$mail->addTo($mailAddress, $alias);
		}
		
		if ($message->cc !== null) {
			foreach (\explode(';', $message->cc) as $item) {
				$email = \trim($item);
				
				if (!Validators::isEmail($email)) {
					continue;
				}
				
				$mail->addCc($email);
			}
		}
		
		$mail->setSubject($message->subject ?: '');
		
		$mail->setHtmlBody($html);
		
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
	
	private function getFileTemplate(string $fileName): ?string
	{
		//@TODO spravna logika?
		$directoryLevel = \max($this->config["templateMapping"]->rootPaths);
		
		$filePath = \dirname(__DIR__, $directoryLevel);
		$filePath .= \DIRECTORY_SEPARATOR;
		$filePath .= $this->config["templateMapping"]->directory;
		$filePath .= \DIRECTORY_SEPARATOR;
		$filePath .= $fileName;
		$filePath .= '.latte';
		
		try {
			$fileContent = FileSystem::read($filePath);
		} catch (IOException $e) {
			return null;
		}
		
		return $fileContent;
	}
	
	private function createDbRecord(Message $message): void
	{
		//@TODO
	}
}
