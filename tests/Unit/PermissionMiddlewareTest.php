<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Unit;

use Duon\Cms\Middleware\Permission;
use Duon\Cms\Session;
use Duon\Cms\Tests\TestCase;
use Duon\Cms\User;
use Duon\Cms\Users;
use Duon\Core\Exception\HttpForbidden;
use Duon\Core\Exception\HttpUnauthorized;
use Duon\Core\Factory;
use Duon\Quma\Database;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * @internal
 *
 * @coversNothing
 */
final class PermissionMiddlewareTest extends TestCase
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

	public function testUnauthorizedWhenNoUser(): void
	{
		$middleware = new Permission('edit-users');
		$middleware->init(new Users($this->db()), $this->config());
		$request = $this->factory()->serverRequestFactory()->createServerRequest('GET', '/');

		$this->throws(HttpUnauthorized::class);
		$middleware->process($request, $this->handler());
	}

	public function testForbiddenWhenPermissionMissing(): void
	{
		$session = new Session('permission-test', ['use_cookies' => 0]);
		$session->start();
		$session->setUser(42);

		$user = new User([
			'usr' => 42,
			'uid' => 'test-editor',
			'username' => 'test-editor',
			'email' => 'editor@example.com',
			'pwhash' => 'hash',
			'role' => 'editor',
			'active' => true,
			'created' => '2024-01-01T00:00:00+00:00',
			'changed' => '2024-01-01T00:00:00+00:00',
			'deleted' => null,
			'expires' => null,
		]);

		$users = new class ($this->db(), $user) extends Users {
			public function __construct(Database $db, private User $user)
			{
				parent::__construct($db);
			}

			public function byId(int $id): ?User
			{
				if ($id === $this->user->id) {
					return $this->user;
				}

				return null;
			}
		};

		$middleware = new Permission('edit-users');
		$middleware->init($users, $this->config());
		$request = $this->factory()->serverRequestFactory()->createServerRequest('GET', '/');
		$request = $request->withAttribute('session', $session);

		$this->throws(HttpForbidden::class);
		$middleware->process($request, $this->handler());
	}

	private function handler(): RequestHandlerInterface
	{
		return new class ($this->factory()) implements RequestHandlerInterface {
			public function __construct(private Factory $factory) {}

			public function handle(ServerRequestInterface $request): ResponseInterface
			{
				return $this->factory->responseFactory()->createResponse();
			}
		};
	}
}
