<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\FieldHydrator;
use Duon\Cms\Locale;
use Duon\Cms\Value\Value;
use Duon\Core\Request;

class NodeProxy
{
	public function __construct(
		private readonly object $node,
		private readonly array $fieldNames,
		private readonly FieldHydrator $hydrator,
		private readonly ?Request $request = null,
	) {}

	/**
	 * Resolve the locale-aware URL path for this node.
	 *
	 * Uses the paths stored in NodeFactory's WeakMap data,
	 * walking the locale fallback chain until a path is found.
	 */
	public function path(?Locale $locale = null): string
	{
		$data = NodeFactory::dataFor($this->node);
		$paths = $data['paths'] ?? [];

		if (!$locale && $this->request) {
			$locale = $this->request->get('locale');
		}

		while ($locale) {
			if (isset($paths[$locale->id])) {
				return $paths[$locale->id];
			}

			$locale = $locale->fallback();
		}

		throw new RuntimeException('No url path found');
	}

	/**
	 * Get the underlying plain node object.
	 */
	public function node(): object
	{
		return $this->node;
	}

	/**
	 * Return the inner node if the given object is a NodeProxy,
	 * otherwise return the object unchanged.
	 */
	public static function unwrap(object $object): object
	{
		return $object instanceof self ? $object->node : $object;
	}

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
