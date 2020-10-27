<?php

declare(strict_types=1);

use Latte\Loaders\StringLoader;

require_once __DIR__ . '/../vendor/autoload.php';

$container = \Messages\Tests\Bootstrap::createContainer();

/** @var \Messages\DB\TemplateRepository $repoTemplate */
$repoTemplate = $container->getByType(\Messages\DB\TemplateRepository::class);

//$repoTemplate->updateDatabaseTemplates(["test"=>"Ahoj databaze!!!"]);

$repoTemplate->getConnection()->setMutation("en");

echo "1\n";
dump($repoTemplate->createMessage("contact",["text"=>"Testovaci zprava123"]));
echo "2\n";
dump($repoTemplate->createMessage("contactInfo",[],"nekdo@seznam.cz"));
echo "3\n";
dump($repoTemplate->createMessage("example", [], "nekdo@gmail.com"));


