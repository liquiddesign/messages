<?php

namespace Messages\Tests\Cases;

require_once __DIR__ . '/../../vendor/autoload.php';

use Messages\Tests\Bootstrap;
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
    
    public function setUp(): void
    {
        $container = Bootstrap::createContainer();
		
        $this->templateRepository = $container->getByType(\Messages\DB\TemplateRepository::class);
    }
    
	public function testExists(): void
	{
		$container = Bootstrap::createContainer();
		
		Assert::notNull($container->getByType(\Messages\DB\TemplateRepository::class));

	}
	
	public function testLoadDbTemplates(): void
	{
        Assert::notNull($this->templateRepository->createMessage("testFile", ["test"=>"Ahojky!!!"], "petr@lqd.cz"));
		Assert::notNull($this->templateRepository->createMessage("test", ["test"=>"Helloooo!!!"], "petr@lqd.cz"));
		Assert::notNull($this->templateRepository->createMessage("test_i", ["test"=>"Helloooo!!!"]));
		$tmp=$this->templateRepository->createMessage("test_i", []);
		Assert::notNull($tmp);
		
	}
	
	public function testUpdateDbTemplates(): void
	{
		Assert::noError(function (){
			$this->templateRepository->updateDatabaseTemplates(["test"=>"Ahoj databaze!!!"]);
		});
	}
}

(new Test())->run();
