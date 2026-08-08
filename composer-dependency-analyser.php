<?php

declare(strict_types=1);

use ShipMonk\ComposerDependencyAnalyser\Config\Configuration;
use ShipMonk\ComposerDependencyAnalyser\Config\ErrorType;

return (new Configuration())
    ->disableComposerAutoloadPathScan()
    ->setFileExtensions(['php'])
    ->addPathToScan(__DIR__ . '/src', isDev: false)
    ->addPathToScan(__DIR__ . '/tests', isDev: true)
    // `ext-pdo_pgsql` provides no PHP-visible symbols of its own (only the "pgsql:" PDO DSN driver),
    // so static analysis can never detect its usage even though the whole package depends on it.
    ->ignoreErrorsOnExtension('ext-pdo_pgsql', [ErrorType::UNUSED_DEPENDENCY]);
