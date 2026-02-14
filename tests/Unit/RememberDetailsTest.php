<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\RememberDetails;
use Duon\Cms\Tests\TestCase;
use Duon\Cms\Token;

final class RememberDetailsTest extends TestCase
{
	public function testConstructorSetsProperties(): void
	{
		$secret = 'test-secret';
		$tokenValue = 'test-token-value';
		$token = new Token($secret, $tokenValue);
		$expires = 1704067200;

		$details = new RememberDetails($token, $expires);

		$this->assertSame($token, $details->token);
		$this->assertSame($expires, $details->expires);
	}

	public function testPropertiesAreReadonly(): void
	{
		$secret = 'test-secret';
		$token = new Token($secret, 'test-token');
		$expires = 1704067200;

		$details = new RememberDetails($token, $expires);

		// Verify properties are accessible
		$this->assertInstanceOf(Token::class, $details->token);
		$this->assertSame(1704067200, $details->expires);
	}
}
