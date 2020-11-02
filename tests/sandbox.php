<?php

declare(strict_types=1);

use Latte\Loaders\StringLoader;

require_once __DIR__ . '/../vendor/autoload.php';

$container = \Messages\Tests\Bootstrap::createContainer();

/** @var \Messages\DB\TemplateRepository $repoTemplate */
$repoTemplate = $container->getByType(\Messages\DB\TemplateRepository::class);

//$repoTemplate->updateDatabaseTemplates(["test"=>"Ahoj databaze!!!"]);

$repoTemplate->getConnection()->setMutation("en");

dump($repoTemplate->createMessage("example", [], "nekdo@gmail.com"));


