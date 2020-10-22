<?php

namespace Messages\Tests\Cases;

require_once __DIR__ . '/../../vendor/autoload.php';

use Messages\Control\IContactFormFactory;
use Messages\Tests\Bootstrap;
use Nette\DI\Container;
use Tester\Assert;
use Tester\TestCase;

/**
 * Class Test
 * @package Tests
 * @testCase
 */
class Test extends TestCase
{
	
	/** @var \Messages\DB\TemplateRepository $templateRepository */
    private $templateRepository;
    
    private Container $container;
    
    public function setUp(): void
    {
        $this->container = Bootstrap::createContainer();
		
        $this->templateRepository = $this->container->getByType(\Messages\DB\TemplateRepository::class);
    }
    
	public function testExists(): void
	{
		$container = Bootstrap::createContainer();
		
		Assert::notNull($container->getByType(\Messages\DB\TemplateRepository::class));

	}
	
	public function testLoadDbTemplates(): void
	{
        Assert::notNull($this->templateRepository->createMessage("contact", ["text"=>"Ahojky!!!"], ));
		Assert::notNull($this->templateRepository->createMessage("contactInfo", [], "petr@lqd.cz"));
		
	}
	
	public function testUpdateDbTemplates(): void
	{
		Assert::noError(function (){
			$this->templateRepository->updateDatabaseTemplates();
		});
	}
	
	public function testForms(): void
	{
		/** @var \Messages\Control\ISubscribeFormFactory $subscribeFormFactory */
		$subscribeFormFactory = $this->container->getByType(\Messages\Control\ISubscribeFormFactory::class);
		
		$form = $subscribeFormFactory->create();
		
		Assert::notNull($form);
		
		/** @var \Messages\Control\IContactFormFactory $contactFormFactory */
		$contactFormFactory = $this->container->getByType(IContactFormFactory::class);
		
		$form = $contactFormFactory->create();
		
		Assert::notNull($form);
	}
}

(new Test())->run();
