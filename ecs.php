<?php

declare(strict_types=1);

use fostercommerce\ecs\ECSConfig;

return ECSConfig::configure()
	->withPaths([
		__DIR__ . '/config',
		__DIR__ . '/modules',
		__FILE__,
	]);
