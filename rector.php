<?php
declare(strict_types = 1);

use fostercommerce\rector\RectorConfig;
use fostercommerce\rector\SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/config',
        __DIR__ . '/modules',
        __FILE__,
    ])
    ->withSets([SetList::CRAFT_CMS_40]);
