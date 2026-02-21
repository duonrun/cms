<?php

declare(strict_types=1);

namespace Duon\Cms;

use Duon\Cms\Config;
use Duon\Cms\Locale;
use Duon\Cms\Locales;
use Duon\Container\Container;
use Duon\Core\Factory;
use Duon\Core\Request;
use Duon\Quma\Database;

final class Context
{
	public function __construct(
		public readonly Database $db,
		public readonly Request $request,
		public readonly Config $config,
		public readonly Container $registry,
		public readonly Factory $factory,
	) {}

	public function locales(): Locales
	{
		return $this->request->get('locales');
	}

	public function locale(): Locale
	{
		return $this->request->get('locale');
	}

	public function localeId(): string
	{
		return $this->request->get('locale')->id;
	}
}
