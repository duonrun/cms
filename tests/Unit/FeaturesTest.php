<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Config;
use Duon\Cms\Database\Features;
use Duon\Cms\Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class FeaturesTest extends TestCase
{
	public function testFulltextEnabledByDefaultOnPgsql(): void
	{
		$config = new Config('test');
		$db = $this->dbPgsql();
		$features = new Features($config, $db);

		$this->assertTrue($features->fulltext());
		$this->assertSame('pgsql', $features->driver());
	}

	public function testFulltextDisabledByDefaultOnSqlite(): void
	{
		$config = new Config('test');
		$db = $this->dbSqlite();
		$features = new Features($config, $db);

		$this->assertFalse($features->fulltext());
		$this->assertSame('sqlite', $features->driver());
	}

	public function testFulltextConfigOverrideEnables(): void
	{
		$config = new Config('test', settings: [
			'db.features.fulltext' => true,
		]);
		$db = $this->dbSqlite();
		$features = new Features($config, $db);

		$this->assertTrue($features->fulltext());
	}

	public function testFulltextConfigOverrideDisables(): void
	{
		$config = new Config('test', settings: [
			'db.features.fulltext' => false,
		]);
		$db = $this->dbPgsql();
		$features = new Features($config, $db);

		$this->assertFalse($features->fulltext());
	}

	public function testFulltextConfigNullAutoDetects(): void
	{
		$config = new Config('test', settings: [
			'db.features.fulltext' => null,
		]);

		$dbPgsql = $this->dbPgsql();
		$featuresPgsql = new Features($config, $dbPgsql);
		$this->assertTrue($featuresPgsql->fulltext());

		$dbSqlite = $this->dbSqlite();
		$featuresSqlite = new Features($config, $dbSqlite);
		$this->assertFalse($featuresSqlite->fulltext());
	}
}
