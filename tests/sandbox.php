<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$container = \Messages\Tests\Bootstrap::createContainer();

/** @var \Messages\DB\TemplateRepository $repoTemplate */
$repoTemplate = $container->getByType(\Messages\DB\TemplateRepository::class);


echo "1\n";
dump($repoTemplate->createMessage("testFile", ["test"=>"Ahojky!!!"], "petr@lqd.cz"));
echo "2\n";
dump($repoTemplate->createMessage("test", ["test"=>"Helloooo!!!"], "petr@lqd.cz"));
echo "3\n";
$tmp=($repoTemplate->createMessage("test_i", []));
dump($tmp->getHeaders());

$repoTemplate->updateDatabaseTemplates(["test"=>"Ahoj databaze!!!"]);
