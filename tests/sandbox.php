<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$container = \Messages\Tests\Bootstrap::createContainer();

/** @var \Messages\DB\TemplateRepository $repoTemplate */
$repoTemplate = $container->getByType(\Messages\DB\TemplateRepository::class);


dump($repoTemplate->createMessage("testFile", ["test"=>"Ahojky!!!"], "petr@lqd.cz"));
dump($repoTemplate->createMessage("test", ["test"=>"Helloooo!!!"], "petr@lqd.cz"));
$tmp=($repoTemplate->createMessage("test_i", ["test"=>"Helloooo!!!"]));
dump($tmp->getHeaders());
