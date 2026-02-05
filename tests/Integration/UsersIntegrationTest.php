<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Integration;

use DateTimeImmutable;
use Duon\Cms\Tests\IntegrationTestCase;
use Duon\Cms\Users;

final class UsersIntegrationTest extends IntegrationTestCase
{
	public function testSessionLookupUsesLoginSessionsUsrKey(): void
	{
		$userId = $this->createTestUser([
			'uid' => 'session-user',
			'username' => 'session-user',
			'email' => 'session-user@example.com',
		]);
		$hash = bin2hex(random_bytes(16));
		$expires = (new DateTimeImmutable('+1 day'))->format(DateTimeImmutable::ATOM);

		$users = new Users($this->db());
		$users->remember($hash, $userId, $expires);

		$user = $users->bySession($hash);

		$this->assertNotNull($user);
		$this->assertSame($userId, $user->id);
		$this->assertNotNull($user->expires);
	}

	public function testAuthtokensChangedTimestampUpdatesOnWrite(): void
	{
		$userId = $this->createTestUser([
			'uid' => 'authtoken-user',
			'username' => 'authtoken-user',
			'email' => 'authtoken-user@example.com',
		]);
		$token = bin2hex(random_bytes(16));
		$oldChanged = '2000-01-01 00:00:00+00';

		$this->db()->execute(
			'INSERT INTO cms.authtokens (token, usr, creator, editor, changed)
			VALUES (:token, :usr, :creator, :editor, :changed)',
			[
				'token' => $token,
				'usr' => $userId,
				'creator' => $userId,
				'editor' => $userId,
				'changed' => $oldChanged,
			],
		)->run();

		$this->db()->execute(
			'UPDATE cms.authtokens SET editor = :editor WHERE token = :token',
			[
				'editor' => $userId,
				'token' => $token,
			],
		)->run();

		$updated = $this->db()->execute(
			'SELECT changed FROM cms.authtokens WHERE token = :token',
			['token' => $token],
		)->one();

		$this->assertNotNull($updated);
		$this->assertNotSame($oldChanged, $updated['changed']);
	}
}
