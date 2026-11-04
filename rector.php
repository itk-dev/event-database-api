<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;

return RectorConfig::configure()
    ->withPaths([
        __DIR__.'/src',
        __DIR__.'/tests',
    ])
    ->withCache(__DIR__.'/var/cache/rector')
    // Coding style and import (`use`) management are owned by php-cs-fixer
    // (PostToolUse hook / coding standards), so Rector does not touch them.
    //
    // Modernize to the language level declared in composer.json (PHP >= 8.3).
    ->withPhpSets()
    // Version-based upgrade + quality rules resolved from the installed package
    // versions (Symfony 7.4, PHPUnit 13). No domain Doctrine here — the entities
    // and migrations live in event-database-imports — so the Doctrine sets are
    // intentionally omitted.
    ->withComposerBased(symfony: true, phpunit: true)
    // Convert any remaining annotations to native PHP attributes.
    ->withAttributesSets(symfony: true, phpunit: true)
    ->withPreparedSets(
        deadCode: true,
        codeQuality: true,
        typeDeclarations: true,
        privatization: true,
        instanceOf: true,
        earlyReturn: true,
        symfonyCodeQuality: true,
        phpunitCodeQuality: true,
    );
