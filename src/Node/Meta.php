<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

class Meta
{
	private readonly object $node;

	public function __construct(
		object $node,
		private readonly Types $types,
	) {
		$this->node = Node::unwrap($node);
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

		$schema = $this->types->forClass($this->node::class)->properties();

		return array_key_exists($name, $schema) && $schema[$name] !== null;
	}

	public function get(string $key, mixed $default = null): mixed
	{
		$data = Factory::dataFor($this->node);

		if (array_key_exists($key, $data)) {
			return $data[$key];
		}

		$schema = $this->types->forClass($this->node::class);

		return match ($key) {
			'name' => $schema->label,
			'class' => $this->node::class,
			'classname' => basename(str_replace('\\', '/', $this->node::class)),
			default => $schema->properties()[$key] ?? $default,
		};
	}

	/**
	 * @return array<string, mixed>
	 */
	public function all(): array
	{
		$schema = $this->types->forClass($this->node::class)->properties();

		return array_merge($schema, Factory::dataFor($this->node), [
			'name' => $schema['label'],
			'class' => $this->node::class,
			'classname' => basename(str_replace('\\', '/', $this->node::class)),
		]);
	}
}
