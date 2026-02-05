<?php

declare(strict_types=1);

namespace Duon\Cms\Database;

use Duon\Core\ConfigInterface;
use Duon\Quma\Database;

/**
 * Runtime feature detection for database capabilities.
 *
 * Provides centralized access to feature flags that may depend on:
 * - Explicit configuration overrides
 * - Database driver auto-detection
 * - Future feature implementations
 */
final readonly class Features
{
	/**
	 * @param ConfigInterface $config Application configuration
	 * @param Database $db Database connection for driver detection
	 */
	public function __construct(
		private ConfigInterface $config,
		private Database $db,
	) {}

	/**
	 * Check if fulltext search is enabled.
	 *
	 * When `db.features.fulltext` is:
	 * - `true`: fulltext is enabled regardless of driver
	 * - `false`: fulltext is disabled regardless of driver
	 * - `null` (default): auto-detect based on driver
	 *   - PostgreSQL: enabled (native tsvector/GIN support)
	 *   - SQLite: disabled (FTS5 not yet implemented)
	 */
	public function fulltext(): bool
	{
		$configured = $this->config->get('db.features.fulltext', null);

		if ($configured !== null) {
			return (bool) $configured;
		}

		return match ($this->db->getPdoDriver()) {
			'pgsql' => true,
			'sqlite' => false,
			default => false,
		};
	}

	/**
	 * Get the current database driver name.
	 */
	public function driver(): string
	{
		return $this->db->getPdoDriver();
	}
}
