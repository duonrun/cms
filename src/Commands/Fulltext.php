<?php

declare(strict_types=1);

namespace Duon\Cms\Commands;

use Duon\Core\ConfigInterface;
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
		if (!$this->isFulltextEnabled()) {
			$driver = $this->env->db->getPdoDriver();
			echo "Fulltext search is not available for the '{$driver}' driver.\n";
			echo "To enable fulltext, set 'db.features.fulltext' => true in your config.\n";

			return 1;
		}

		$this->env->db->fulltext->clean()->run();
		$this->update($this->env->db);

		return 0;
	}

	/**
	 * Check if fulltext search is enabled for this database.
	 *
	 * Uses config override if available, otherwise auto-detects by driver:
	 * - PostgreSQL: enabled (native tsvector/GIN support)
	 * - SQLite: disabled (FTS5 not yet implemented)
	 */
	private function isFulltextEnabled(): bool
	{
		$config = $this->env->options['config'] ?? null;

		if ($config instanceof ConfigInterface) {
			$override = $config->get('db.features.fulltext', null);

			if ($override !== null) {
				return (bool) $override;
			}
		}

		return match ($this->env->db->getPdoDriver()) {
			'pgsql' => true,
			'sqlite' => false,
			default => false,
		};
	}

	private function update(Database $db): void
	{
		foreach ($db->fulltext->nodes()->lazy() as $node) {
			$json = json_decode($node['content'], true);
			error_log(print_r($json, true));
			break;
		}
	}
}
