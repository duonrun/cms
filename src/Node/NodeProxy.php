<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

use Duon\Cms\Field\FieldHydrator;
use Duon\Cms\Value\Value;

class NodeProxy
{
	public function __construct(
		private readonly object $node,
		private readonly array $fieldNames,
		private readonly FieldHydrator $hydrator,
	) {}

	public function __get(string $name): ?Value
	{
		if (in_array($name, $this->fieldNames, true)) {
			$field = $this->hydrator->getField($this->node, $name);
			$value = $field->value();

			if ($value->isset()) {
				return $value;
			}

			return null;
		}

		if (property_exists($this->node, $name)) {
			return $this->node->{$name};
		}

		return null;
	}

	public function __isset(string $name): bool
	{
		if (in_array($name, $this->fieldNames, true)) {
			$field = $this->hydrator->getField($this->node, $name);

			return $field->value()->isset();
		}

		return isset($this->node->{$name});
	}

	public function __call(string $name, array $args): mixed
	{
		return $this->node->{$name}(...$args);
	}
}
