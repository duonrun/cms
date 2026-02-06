<?php

declare(strict_types=1);

namespace Duon\Cms\Commands;

use Duon\Cms\Config;
use Duon\Quma\Commands\Command;
use Duon\Quma\Database;

class Fulltext extends Command
{
	protected string $group = 'Database';
	protected string $prefix = 'db';
	protected string $name = 'fulltext';
	protected string $description = 'Updates the fulltext index';

	public function run(): int
	{
		if (!$this->fulltextEnabled($this->env->db)) {
			return 0;
		}

		$db = $this->env->db;
		if ($db->getPdoDriver() === 'pgsql') {
			$db->fulltext->rebuild()->run();

			return 0;
		}

		return 0;
	}

	private function fulltextEnabled(Database $db): bool
	{
		$config = $this->env->options['config'] ?? null;
		if ($config instanceof Config) {
			return $config->fulltextEnabled($db->getPdoDriver());
		}

		return $db->getPdoDriver() !== 'sqlite';
	}
}
