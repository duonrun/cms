<?php

declare(strict_types=1);

namespace Duon\Cms;

use Duon\Cms\Config;
use Duon\Cms\Finder\Dialect\DialectFactory;
use Duon\Cms\Finder\Dialect\SqlDialect;
use Duon\Cms\Locale;
use Duon\Cms\Locales;
use Duon\Core\Factory;
use Duon\Core\Request;
use Duon\Quma\Database;
use Duon\Registry\Registry;

final class Context
{
	public function __construct(
		public readonly Database $db,
		public readonly Request $request,
		public readonly Config $config,
		public readonly Registry $registry,
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

	/**
	 * Get the SQL dialect for the current database driver.
	 */
	public function dialect(): SqlDialect
	{
		return DialectFactory::forDriver($this->db->getPdoDriver());
	}
}
