<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

class Meta
{
	private readonly object $node;
	public readonly string $uid;
	public readonly Type $type;

	public function __construct(
		object $node,
		private readonly Types $types,
	) {
		$this->node = Node::unwrap($node);
		$this->uid = (string) (Factory::meta($this->node, 'uid') ?? '');
		$this->type = $this->types->typeOf($this->node::class);
	}

	public function __get(string $name): mixed
	{
		return $this->get($name);
	}

	public function __isset(string $name): bool
	{
		if (in_array($name, ['name', 'class', 'classname'], true)) {
			return true;
		}

		$data = Factory::dataFor($this->node);

		if (array_key_exists($name, $data)) {
			return $data[$name] !== null;
		}

		if ($this->type->has($name)) {
			return $this->type->get($name) !== null;
		}

		return false;
	}

	public function get(string $key, mixed $default = null): mixed
	{
		$data = Factory::dataFor($this->node);

		if (array_key_exists($key, $data)) {
			return $data[$key];
		}

		return match ($key) {
			'name' => $this->type->label,
			'class' => $this->node::class,
			'classname' => basename(str_replace('\\', '/', $this->node::class)),
			default => $this->type->get($key, $default),
		};
	}

	/**
	 * @return array<string, mixed>
	 */
	public function all(): array
	{
		return array_merge($this->type->all(), Factory::dataFor($this->node), [
			'uid' => $this->uid,
			'name' => $this->type->label,
			'class' => $this->node::class,
			'classname' => basename(str_replace('\\', '/', $this->node::class)),
		]);
	}
}
