<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Cms;
use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Tests\TestCase;
use Duon\Core\App;

final class DatabaseDriverTest extends TestCase
{
	public function testMissingPdoDriverMessage(): void
	{
		$config = $this->config([
			'db.dsn' => 'missing:dbname=duoncms',
		]);
		$app = App::create($this->factory(), $config);
		$cms = new Cms();

		$this->throws(
			RuntimeException::class,
			'PDO driver not supported: missing. Install extension ext-pdo_missing.',
		);

		$cms->load($app);
	}
}
