<?php

$finder = PhpCsFixer\Finder::create()
    ->in([
        __DIR__.'/src',
        __DIR__.'/config',
        __DIR__.'/deployer',
        __DIR__.'/tools',
    ])
    ->append([
        __DIR__.'/wp-config.php',
        __DIR__.'/public/wp-content/mu-plugins/app.php',
    ])
;

return (new PhpCsFixer\Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@PSR2' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/var/.php-cs-fixer.cache')
;
