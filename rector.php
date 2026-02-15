<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Rector\FuncCall\RemoveDumpDataDeadCodeRector;
use RectorLaravel\Rector\MethodCall\WhereToWhereLikeRector;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
                   ->withPaths([
                       __DIR__ . '/app',
                       __DIR__ . '/bootstrap',
                       __DIR__ . '/config',
                       __DIR__ . '/public',
                       __DIR__ . '/resources',
                       __DIR__ . '/routes',
                       __DIR__ . '/tests',
                   ])
                   ->withPhpSets(php84: true)
                   ->withTypeCoverageLevel(1)
                   ->withDeadCodeLevel(1)
                   ->withCodeQualityLevel(1)
                   ->withSets([
                       LaravelSetList::LARAVEL_CODE_QUALITY,
                       LaravelSetList::LARAVEL_COLLECTION,
                       LaravelSetList::LARAVEL_IF_HELPERS,
                   ])
                   ->withComposerBased(laravel: true)
                   ->withConfiguredRule(RemoveDumpDataDeadCodeRector::class, [
                       'dd',
                       'dump',
                       'var_dump',
                   ])
                   ->withConfiguredRule(WhereToWhereLikeRector::class, []);
