<?php

use craft\helpers\App;

/** @var string $fsHandle */
$fsHandle = App::env('CRAFT_DEBUG_FS');

return $fsHandle ? [
	'fs' => Craft::$app->getFs()->getFilesystemByHandle($fsHandle),
	'dataPath' => 'debug',
] : [];
