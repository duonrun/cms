<?php

declare(strict_types=1);

namespace Duon\Cms\Field;

use Duon\Cms\Config;
use Duon\Cms\Locale;
use Duon\Cms\Locales;
use Duon\Core\Request;

interface Owner
{
	public function uid(): string;

	public function locale(): Locale;

	public function defaultLocale(): Locale;

	public function locales(): Locales;

	public function request(): Request;

	public function config(): Config;
}
