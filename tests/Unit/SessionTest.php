<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Session;
use Duon\Cms\Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class SessionTest extends TestCase
{
	protected function tearDown(): void
	{
		if (session_status() === PHP_SESSION_ACTIVE) {
			$_SESSION = [];
			session_unset();
			session_destroy();
		}

		parent::tearDown();
	}

	public function testAuthenticatedUserIdRoundTrip(): void
	{
		$session = new Session('test-session', ['use_cookies' => 0]);
		$session->start();
		$session->setUser(42);

		$this->assertSame(42, $session->authenticatedUserId());
	}

	public function testSignalActivityPersistsTimestamp(): void
	{
		$session = new Session('test-session', ['use_cookies' => 0]);
		$session->start();
		$session->signalActivity();

		$this->assertIsInt($session->lastActivity());
		$this->assertGreaterThan(0, $session->lastActivity());
	}
}
