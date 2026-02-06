<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use Duon\Cms\Commands\Fulltext;
use Duon\Cms\Tests\IntegrationTestCase;
use Duon\Cms\Tests\Support\TestDbConfig;

final class FulltextCommandTest extends IntegrationTestCase
{
	protected bool $useTransactions = false;

	public function testFulltextCommandRunsOnPgsql(): void
	{
		if (TestDbConfig::driver() !== 'pgsql') {
			$this->markTestSkipped('Fulltext command is only validated on pgsql');
		}

		$table = $this->table('fulltext');
		$this->assertTrue($this->tableExists($table));

		$command = new Fulltext(
			TestDbConfig::connection(),
			['config' => $this->config(['db.features.fulltext.enabled' => true])],
		);

		$this->assertSame(0, $command->run());
		$this->db()->execute("DELETE FROM {$table}")->run();
	}
}
