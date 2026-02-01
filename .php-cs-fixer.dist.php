<?php

declare(strict_types=1);

use Duon\Development\PhpCsFixer\Config;
use PhpCsFixer\Finder;

$paths = [__DIR__ . '/src', __DIR__ . '/tests'];

if (is_dir(__DIR__ . '/docs')) {
	$paths[] = __DIR__ . '/docs';
}

$finder = Finder::create()->in($paths);
$config = new Config();

return $config->setFinder($finder);
