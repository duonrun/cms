<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Config;
use Duon\Cms\Tests\TestCase;

final class ConfigFulltextTest extends TestCase
{
	public function testFulltextEnabledDefaultsByDriver(): void
	{
		$config = new Config();

		$this->assertTrue($config->fulltextEnabled('pgsql'));
		$this->assertFalse($config->fulltextEnabled('sqlite'));
	}

	public function testFulltextEnabledOverride(): void
	{
		$config = new Config(settings: ['db.features.fulltext.enabled' => false]);
		$this->assertFalse($config->fulltextEnabled('pgsql'));

		$config = new Config(settings: ['db.features.fulltext.enabled' => true]);
		$this->assertTrue($config->fulltextEnabled('sqlite'));
	}
}
