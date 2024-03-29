<?php

declare(strict_types=1);

namespace Messages\DB;

use Latte\Loaders\StringLoader;
use Latte\Sandbox\SecurityPolicy;
use Nette\Application\LinkGenerator;
use Nette\Application\UI\ITemplateFactory;
use Nette\Http\Request;
use Nette\IOException;
use Nette\Mail\Message;
use Nette\Utils\FileSystem;
use Nette\Utils\Validators;
use StORM\DIConnection;
use StORM\Entity;
use StORM\Exception\NotExistsException;
use StORM\Repository;
use StORM\SchemaManager;

class TemplateRepository extends Repository
{
	private LinkGenerator $linkGenerator;

	private ITemplateFactory $templateFactory;

	private ?string $defaultEmail;

	private ?string $alias;

	private ?string $baseUrl;

	/**
	 * @var array<mixed>
	 */
	private array $rootPaths = [];

	private string $directory = 'templates';

	private string $fileMask = 'email-%s.latte';

	/**
	 * @var array<mixed>
	 */
	private array $dbTemplates = [];

	/**
	 * @var array<mixed>
	 */
	private array $dbRootPaths = [];

	private string $globalFileMask = 'global-%s.latte';

	private string $globalDirectory = 'globalTemplates';

	private ?string $mutation = null;

	private ?string $defaultMutation = null;

	public function __construct(
		DIConnection $connection,
		SchemaManager $schemaManager,
		LinkGenerator $linkGenerator,
		Request $request,
		ITemplateFactory $templateFactory
	) {
		parent::__construct($connection, $schemaManager);

		$this->linkGenerator = $linkGenerator;
		$this->templateFactory = $templateFactory;
		$this->schemaManager = $schemaManager;

		$this->baseUrl = $request->getUrl()->getBaseUrl();
	}

	public function setEmailAndAlias(?string $defaultEmail, ?string $alias): void
	{
		$this->defaultEmail = $defaultEmail;
		$this->alias = $alias;
	}

	public function setTemplateMapping(array $rootPaths, string $directory, string $fileMask): void
	{
		$this->rootPaths = $rootPaths;
		$this->directory = $directory;
		$this->fileMask = $fileMask;
	}

	public function setGlobalTemplateMapping(string $globalDirectory, string $globalFileMask): void
	{
		$this->globalDirectory = $globalDirectory;
		$this->globalFileMask = $globalFileMask;
	}

	public function setDbTemplates(array $templates, array $dbRootPaths): void
	{
		$this->dbTemplates = $templates;
		$this->dbRootPaths = $dbRootPaths;
	}

	public function setMutation(?string $mutation): void
	{
		$this->mutation = $mutation;
	}

	public function setDefaultMutation(?string $mutation): void
	{
		$this->defaultMutation = $mutation;
	}

	public function createMessage(
		string $id,
		array $params,
		?string $email = null,
		?string $ccEmails = null,
		?string $replyTo = null,
		?string $mutation = null
	): ?Message {
		$template = $this->createTemplate();
		$latte = $template->getLatte();
		$policy = SecurityPolicy::createSafePolicy();

		if (\version_compare(\Latte\Engine::VERSION, '3', '<')) {
			$policy->allowMacros(['include']);
		} else {
			$policy->allowTags(['include']);
		}

		$policy->allowProperties(\ArrayObject::class, (array) $policy::ALL);
		$policy->allowProperties(Entity::class, (array) $policy::ALL);
		$policy->allowMethods(Entity::class, (array) $policy::ALL);
		$policy->allowFilters(['price', 'date', 'noescape', 'translate']);
		$latte->setPolicy($policy);
		$latte->setSandboxMode();
		$parsedPath = \explode(\DIRECTORY_SEPARATOR, __DIR__);
		$rootLevel = \count($parsedPath) - \array_search('src', $parsedPath);

		if ($this->defaultMutation) {
			$prevMutation = $this->getConnection()->getMutation();
			$this->getConnection()->setMutation($this->defaultMutation);
		} else {
			if ($mutation) {
				$prevMutation = $this->getConnection()->getMutation();
				$this->getConnection()->setMutation($mutation);
			}

			if (!$mutation && $this->mutation) {
				$prevMutation = $this->getConnection()->getMutation();
				$this->getConnection()->setMutation($this->mutation);
			}
		}

		if (\file_exists(\dirname(__DIR__, $rootLevel) . '/vendor/autoload.php')) {
			require \dirname(__DIR__, $rootLevel) . '/vendor/autoload.php';
		} else {
			$rootLevel = \count($parsedPath) - \array_search('vendor', $parsedPath);
		}

		/** @var \Messages\DB\Template|null $message */
		$message = $this->one($id, false);

		if (!$message) {
			$messageArray = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);
			$file = $this->getFileTemplate($id, $rootLevel, $this->fileMask, $this->rootPaths, $this->directory);

			if (!$file) {
				throw new \InvalidArgumentException('Template file not found!');
			}

			$html = $template->renderToString($file, $params + ['message' => $messageArray]);

			foreach (\array_keys($this->schemaManager->getConnection()->getAvailableMutations()) as $key) {
				if (isset($messageArray->html[$key])) {
					$messageArray->html[$key] .= $html;
				}
			}

			$message = new Template(
				$messageArray->getArrayCopy(),
				$this,
				$this->getConnection()->getAvailableMutations(),
				$this->getConnection()->getMutation(),
			);

			try {
				$globalLayout = $this->getFileTemplate(
					$message->getValue('layout'),
					$rootLevel,
					$this->globalFileMask,
					$this->rootPaths,
					$this->globalDirectory,
				);

				if (!$globalLayout) {
					throw new \InvalidArgumentException('Global template file not found!');
				}

				if (!isset($messageArray->html[$this->getConnection()->getMutation()])) {
					return null;
				}

				$html = $template->renderToString(
					'{define email_co}' . $messageArray->html[$this->getConnection()->getMutation()] . '{/define} ' . $globalLayout,
					$params + ['message' => $message, 'baseUrl' => $this->baseUrl],
				);
			} catch (NotExistsException $ignored) {
				$html = $messageArray->html[$this->getConnection()->getMutation()];
			}

			try {
				$message->getValue('type');
			} catch (NotExistsException $e) {
				$message->type = 'outgoing';
			}

			try {
				$message->getValue('cc');
			} catch (NotExistsException $e) {
				$message->cc = null;
			}

			try {
				$mailAddress = $message->getValue('email');
			} catch (NotExistsException $e) {
				if ($this->defaultEmail === null) {
					throw new \InvalidArgumentException('No email specified!');
				}

				$mailAddress = $this->defaultEmail;
			}

			try {
				$alias = $message->getValue('alias');
			} catch (NotExistsException $e) {
				$alias = $this->alias ?: '';
			}

			$message->subject = $messageArray->subject[$this->getConnection()->getMutation()];
		} else {
			if ($message->layout !== null) {
				$html = '{define email_co}' . $message->html . '{/define}';
				$globalLayout = $this->getFileTemplate(
					$message->layout,
					$rootLevel,
					$this->globalFileMask,
					$this->rootPaths,
					$this->globalDirectory,
				);

				if (!$globalLayout) {
					throw new \InvalidArgumentException('Global template file not found!');
				}

				$html = $template->renderToString(
					$globalLayout . $html,
					$params + ['message' => $message, 'baseUrl' => $this->baseUrl],
				);
			} else {
				$html = $template->renderToString(
					$message->html,
					$params + ['message' => $message, 'baseUrl' => $this->baseUrl],
				);
			}

			$mailAddress = $message->email ?: ($this->defaultEmail ?: '');
			$alias = $message->alias ?: ($this->alias ?: '');
		}

		try {
			if ($message->getValue('active') !== true) {
				return null;
			}
		} catch (NotExistsException $e) {
			return null;
		}

		$mail = new Message();

		if ($replyTo) {
			$mail->addReplyTo($replyTo);
		}

		if ($message->type === 'outgoing') {
			$mail->setFrom($mailAddress, $alias);
			$mail->addTo($email);
		} else {
			$mail->setFrom($email ?: $this->defaultEmail, $email ?: $this->defaultEmail);
			$mail->addTo($mailAddress, $alias);
		}

		if ($ccEmails) {
			$message->cc = $ccEmails;
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

		try {
			$message->getValue('replyTo');
		} catch (NotExistsException $e) {
			$message->replyTo = null;
		}

		if ($message->replyTo !== null) {
			foreach (\explode(';', $message->replyTo) as $item) {
				$tmpEmail = \trim($item);

				if (!Validators::isEmail($tmpEmail)) {
					continue;
				}

				$mail->addReplyTo($tmpEmail);
			}
		}

		try {
			$subject = $message->subject ?: '';

			if ($subject !== null && $subject !== '') {
				$renderedSubject = $template->renderToString(
					$subject,
					$params + ['message' => $message, 'baseUrl' => $this->baseUrl],
				);

				$mail->setSubject($renderedSubject);
			}
		} catch (\Throwable $ignored) {
			$mail->setSubject($message->subject ?: '');
		}

		try {
			$text = $message->getValue('text');

			if ($text !== null && $text !== '') {
				$body = $template->renderToString(
					$text,
					$params + ['message' => $message, 'baseUrl' => $this->baseUrl],
				);
				$mail->setBody($body);
			}
		} catch (NotExistsException $ignored) {
		}

		$mail->setHtmlBody($html);

		if ($mutation || (!$mutation && $this->mutation) || $this->defaultMutation) {
			if (isset($prevMutation)) {
				$this->getConnection()->setMutation($prevMutation);
			}
		}

		return $mail;
	}

	public function updateDatabaseTemplates(array $params = []): void
	{
		$template = $this->createTemplate();

		$parsedPath = \explode(\DIRECTORY_SEPARATOR, __DIR__);
		$rootLevel = \count($parsedPath) - \array_search('src', $parsedPath);

		if (\file_exists(\dirname(__DIR__, $rootLevel) . '/vendor/autoload.php')) {
			require \dirname(__DIR__, $rootLevel) . '/vendor/autoload.php';
		} else {
			$rootLevel = \count($parsedPath) - \array_search('vendor', $parsedPath);
		}

		$path = \dirname(__DIR__, $rootLevel) . \DIRECTORY_SEPARATOR;

		foreach (\array_keys($this->dbRootPaths) as $key) {
			$path .= $key;
			$path .= \DIRECTORY_SEPARATOR;
		}

		foreach ($this->dbTemplates as $item) {
			$message = new \ArrayObject([], \ArrayObject::ARRAY_AS_PROPS);
			$fileContent = FileSystem::read($path . $item . '.latte');
			$htmlTemplateRendered = $template->renderToString(
				$fileContent,
				$params + ['message' => $message, 'baseUrl' => $this->baseUrl],
			);

			foreach (\array_keys($this->schemaManager->getConnection()->getAvailableMutations()) as $key) {
				$message->html[$key] .= $htmlTemplateRendered;
			}

			$item = $this->one($message->uuid);

			if ($item === null) {
				$this->createOne($message->getArrayCopy());
			} else {
				$item->update($message->getArrayCopy());
			}
		}
	}

	private function createTemplate(): \Nette\Bridges\ApplicationLatte\Template
	{
		/** @var \Nette\Bridges\ApplicationLatte\Template $template */
		$template = $this->templateFactory->createTemplate();
		$template->getLatte()->addProvider('uiControl', $this->linkGenerator);
		$template->getLatte()->setLoader(new StringLoader());

		return $template;
	}

	/**
	 * Open file based on parameters.
	 * @param string $fileName Name of file. Will be processed with mask.
	 * @param int $rootLevel Count to server root.
	 * @param string $mask Mask of file. Example: 'prefix-%s.latte'
	 * @param array $rootPaths Path from server root to directory.
	 * @param string $directory Name of directory.
	 * @return string|null Content of file or null on error.
	 */
	private function getFileTemplate(
		string $fileName,
		int $rootLevel,
		string $mask,
		array $rootPaths,
		string $directory
	): ?string {
		if (\strpos($mask, '%s') === false) {
			throw new \InvalidArgumentException('Wrong file mask format!');
		}

		$filePath = \dirname(__DIR__, $rootLevel);
		$filePath .= \DIRECTORY_SEPARATOR;

		foreach (\array_keys($rootPaths) as $key) {
			$filePath .= $key;
			$filePath .= \DIRECTORY_SEPARATOR;
		}

		$filePath .= $directory;
		$filePath .= \DIRECTORY_SEPARATOR;
		$filePath .= $mask;

		$filePath = \str_replace('%s', $fileName, $filePath);

		try {
			$fileContent = FileSystem::read($filePath);
		} catch (IOException $e) {
			return null;
		}

		return $fileContent;
	}
}
