<?php

declare(strict_types=1);

namespace Messages\DB;

use Latte\Loaders\StringLoader;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\ITemplateFactory;
use Nette\IOException;
use Nette\Mail\Message;
use Nette\Neon\Exception;
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
			$file = $this->getFileTemplate($id, $rootLevel);
			
			if (!$file) {
				throw new \InvalidArgumentException('Template file not found!');
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
				$message->cc = null;
			}
			
			try {
				$mailAddress = $message->getValue('email');
			} catch (NotExistsException $e) {
				if ($this->config['email'] === null) {
					throw new \InvalidArgumentException("No email specified!");
				}
				
				$mailAddress = $this->config['email'];
			}
			
			try {
				$alias = $message->getValue('alias');
			} catch (NotExistsException $e) {
				$alias = $this->config['alias'] ?? '';
			}
		} else {
			$html = $template->renderToString($message->html, $params + ['message' => $message]);
			
			if ($message->email !== null) {
				$mailAddress = $message->email;
			} elseif ($this->config['email'] !== null) {
				$mailAddress = $this->config['email'];
			} else {
				throw new \InvalidArgumentException("No email specified!");
			}
			
			if ($message->alias !== null) {
				$alias = $message->alias;
			} elseif ($this->config['alias'] !== null) {
				$alias = $this->config['alias'];
			} else {
				$alias = '';
			}
		}
		
		$mail = new Message();
		
		if ($message->type === 'outgoing') {
			$mail->setFrom($mailAddress, $alias);
			$mail->addTo($email);
		} else {
			$mail->setFrom($email ?: $this->config['email']);
			$mail->addTo($mailAddress, $alias);
		}
		
		if ($message->cc !== null) {
			foreach (\explode(';', $message->cc) as $item) {
				$tmpEmail = \trim($item);
				
				if (!Validators::isEmail($tmpEmail)) {
					continue;
				}
				
				$mail->addCc($tmpEmail);
			}
		}
		
		$mail->setSubject($message->subject ?: '');
		
		try {
			$text = $message->getValue("text");
			if ($text !== null) {
				$body = $template->renderToString($text, $params + ['message' => $message]);
				$mail->setBody($body);
			}
			
		} catch (NotExistsException $ignored) {
		}
		
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
	
	private function getFileTemplate(string $fileName, int $rootLevel): ?string
	{
		if (\strpos($this->config["templateMapping"]->filemask, '%s') === false) {
			throw new \InvalidArgumentException("Wrong file mask format!");
		}
		
		if (empty($this->config["templateMapping"]->rootPaths)) {
			$this->config["templateMapping"]->rootPaths = ["src" => 0, "app" => 1];
		}
		
		$filePath = \dirname(__DIR__, $rootLevel);
		$filePath .= \DIRECTORY_SEPARATOR;
		foreach ($this->config["templateMapping"]->rootPaths as $key => $value) {
			$filePath .= $key;
			$filePath .= \DIRECTORY_SEPARATOR;
		}
		
		$filePath .= $this->config["templateMapping"]->directory;
		$filePath .= \DIRECTORY_SEPARATOR;
		$filePath .= $this->config["templateMapping"]->filemask;
		
		$filePath = \str_replace('%s', $fileName, $filePath);
		
		try {
			$fileContent = FileSystem::read($filePath);
		} catch (IOException $e) {
			return null;
		}
		
		return $fileContent;
	}
}
