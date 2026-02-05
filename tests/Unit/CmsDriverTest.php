<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Cms;
use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Tests\TestCase;
use PDO;
use ReflectionMethod;

/**
 * @internal
 *
 * @coversNothing
 */
final class CmsDriverTest extends TestCase
{
	public function testValidateDriverThrowsForMissingDriver(): void
	{
		$cms = new class extends Cms {};
		$method = new ReflectionMethod($cms, 'validateDriver');

		$this->throws(RuntimeException::class, "PDO driver 'nonexistent' is not available");

		$method->invoke($cms, 'nonexistent:foo=bar');
	}

	public function testValidateDriverIncludesExtensionHint(): void
	{
		$cms = new class extends Cms {};
		$method = new ReflectionMethod($cms, 'validateDriver');

		try {
			// Use 'firebird' which is a real PDO driver name but unlikely to be installed
			$method->invoke($cms, 'firebird:host=localhost');
			$this->fail('Expected RuntimeException');
		} catch (RuntimeException $e) {
			$this->assertStringContainsString('ext-pdo_firebird', $e->getMessage());
		}
	}

	public function testValidateDriverShowsAvailableDrivers(): void
	{
		$cms = new class extends Cms {};
		$method = new ReflectionMethod($cms, 'validateDriver');

		try {
			$method->invoke($cms, 'fake:host=localhost');
			$this->fail('Expected RuntimeException');
		} catch (RuntimeException $e) {
			$this->assertStringContainsString('Available drivers:', $e->getMessage());
		}
	}

	public function testValidateDriverAcceptsAvailableDriver(): void
	{
		$cms = new class extends Cms {};
		$method = new ReflectionMethod($cms, 'validateDriver');

		$available = PDO::getAvailableDrivers();
		if (count($available) === 0) {
			$this->markTestSkipped('No PDO drivers available');
		}

		// Should not throw for an available driver
		$method->invoke($cms, $available[0] . ':test=value');
		$this->assertTrue(true);
	}
}
