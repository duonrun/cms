<?php

declare(strict_types=1);

namespace Duon\Cms\Node\Schema;

use Duon\Cms\Schema\Deletable;
use Duon\Cms\Schema\FieldOrder;
use Duon\Cms\Schema\Handle;
use Duon\Cms\Schema\Label;
use Duon\Cms\Schema\Permission;
use Duon\Cms\Schema\Render;
use Duon\Cms\Schema\Route;
use Duon\Cms\Schema\Title;

class Registry
{
	/** @var array<class-string, Handler> */
	private array $handlers = [];

	/** @param class-string $schema */
	public function register(string $schema, Handler $handler): void
	{
		$this->handlers[$schema] = $handler;
	}

	public function getHandler(object $schema): ?Handler
	{
		return $this->handlers[$schema::class] ?? null;
	}

	public static function withDefaults(): self
	{
		$registry = new self();
		$registry->register(Handle::class, new HandleHandler());
		$registry->register(Label::class, new LabelHandler());
		$registry->register(Route::class, new RouteHandler());
		$registry->register(Render::class, new RenderHandler());
		$registry->register(Permission::class, new PermissionHandler());
		$registry->register(Title::class, new TitleHandler());
		$registry->register(FieldOrder::class, new FieldOrderHandler());
		$registry->register(Deletable::class, new DeletableHandler());

		return $registry;
	}
}
