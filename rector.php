<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Set\SymfonySetList;

/*
 * Upgrade-branch configuration (see UPGRADE_PLAN.md, section 3).
 *
 * Rector 2.x has no per-version SymfonySetList::SYMFONY_XX sets any more: the
 * version-specific rules are enabled by withComposerBased() according to the
 * package versions that are *installed*. So the workflow per step is:
 * bump composer constraints -> composer update -> rector --dry-run -> review -> process.
 *
 * Generic refactoring sets (deadCode, codeQuality, typeDeclarations, ...) are
 * intentionally NOT enabled here; they belong in a separate branch so that the
 * upgrade diffs stay reviewable.
 */
return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/config',
    ])
    ->withSkip([
        __DIR__ . '/config/bundles.php',
        __DIR__ . '/config/preload.php',
        __DIR__ . '/var',
        __DIR__ . '/migrations',                       // never rewrite executed migrations
        '*/error_log',                                  // hosting log files, not PHP
        __DIR__ . '/src/Security/AppAuthenticator.php', // unused stub, left untouched on purpose
        // Would turn our EventSubscriberInterface classes into #[AsEventListener] listeners:
        // changes the project's subscriber pattern and conflicts with the explicit
        // kernel.event_subscriber tags in config/services.yaml.
        \Rector\Symfony\CodeQuality\Rector\Class_\EventSubscriberToAttributeRector::class,
    ])
    // run `bin/console cache:warmup` (dev) first so this file is current
    ->withSymfonyContainerXml(__DIR__ . '/var/cache/dev/App_KernelDevDebugContainer.xml')
    ->withComposerBased(symfony: true, doctrine: true, phpunit: true)
    ->withAttributesSets(symfony: true, doctrine: true, phpunit: true)
    ->withSets([
        SymfonySetList::SYMFONY_CONSTRUCTOR_INJECTION,
    ])
    // Enabled in the commit that bumps nelmio/api-doc-bundle to ^5
    // (Nelmio 5 removes the Annotation namespace; no Rector set covers it):
    // ->withConfiguredRule(\Rector\Renaming\Rector\Name\RenameClassRector::class, [
    //     'Nelmio\ApiDocBundle\Annotation\Model' => 'Nelmio\ApiDocBundle\Attribute\Model',
    //     'Nelmio\ApiDocBundle\Annotation\Security' => 'Nelmio\ApiDocBundle\Attribute\Security',
    // ])
    // Enabled last, each in its own commit:
    // ->withPreparedSets(symfonyCodeQuality: true, symfonyConfigs: true)
    // ->withPhpSets(php84: true)
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withParallel()
    ->withCache(__DIR__ . '/var/cache/rector');
