<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

$container = \Messages\Tests\Bootstrap::createContainer();

/** @var \Messages\DB\TemplateRepository $repoTemplate */
$repoTemplate = $container->getByType(\Messages\DB\TemplateRepository::class);

echo $repoTemplate->getTest();