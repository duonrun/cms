<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

use Duon\Cms\Field\FieldHydrator;
use Duon\Cms\Locales;
use Duon\Cms\Node\Contract\HasTitle;
use ReflectionMethod;

use function Duon\Cms\Util\nanoid;

class NodeSerializer
{
	public function __construct(
		private readonly FieldHydrator $hydrator,
	) {}

	public function content(object $node, array $rawData, array $fieldNames): array
	{
		$content = [];

		foreach ($fieldNames as $fieldName) {
			$field = $this->hydrator->getField($node, $fieldName);
			$structure = $field->structure();
			$content[$fieldName] = array_merge($structure, $rawData['content'][$fieldName] ?? []);
			$content[$fieldName]['type'] = $structure['type'];
		}

		return $content;
	}

	public function data(object $node, array $rawData, array $fieldNames): array
	{
		return [
			'uid' => $rawData['uid'],
			'published' => $rawData['published'],
			'hidden' => $rawData['hidden'],
			'locked' => $rawData['locked'],
			'created' => $rawData['created'],
			'changed' => $rawData['changed'],
			'deleted' => $rawData['deleted'],
			'paths' => $rawData['paths'],
			'type' => [
				'handle' => $rawData['handle'],
				'kind' => $rawData['kind'],
				'class' => $node::class,
			],
			'editor' => [
				'uid' => $rawData['editor_uid'],
				'email' => $rawData['editor_email'],
				'username' => $rawData['editor_username'],
				'data' => $rawData['editor_data'],
			],
			'creator' => [
				'uid' => $rawData['creator_uid'],
				'email' => $rawData['creator_email'],
				'username' => $rawData['creator_username'],
				'data' => $rawData['creator_data'],
			],
			'content' => $this->content($node, $rawData, $fieldNames),
			'deletable' => $this->resolveDeletable($node),
		];
	}

	public function blueprint(
		object $node,
		array $fieldNames,
		Locales $locales,
		array $values = [],
	): array {
		$content = [];
		$paths = [];

		foreach ($fieldNames as $fieldName) {
			$field = $this->hydrator->getField($node, $fieldName);
			$content[$fieldName] = $field->structure($values[$fieldName] ?? null);
		}

		$class = $node::class;
		$kind = NodeMeta::kind($class);

		foreach ($locales as $locale) {
			$paths[$locale->id] = '';
		}

		$result = [
			'title' => _('Neues Dokument:') . ' ' . NodeMeta::name($class),
			'fields' => $this->fields($node, $fieldNames),
			'uid' => nanoid(),
			'published' => false,
			'hidden' => false,
			'locked' => false,
			'deletable' => $this->resolveDeletable($node),
			'content' => $content,
			'type' => [
				'handle' => NodeMeta::handle($class),
				'kind' => $kind,
				'class' => $class,
			],
			'paths' => $paths,
			'generatedPaths' => [],
		];

		if ($kind === 'page') {
			$result['route'] = NodeMeta::route($class);
		}

		return $result;
	}

	public function fields(object $node, array $fieldNames): array
	{
		$fields = [];
		$orderedFields = $this->order($node, $fieldNames);
		$missingFields = array_diff($fieldNames, $orderedFields);
		$allFields = array_merge($orderedFields, $missingFields);

		foreach ($allFields as $fieldName) {
			$fields[] = $this->hydrator->getField($node, $fieldName)->properties();
		}

		return $fields;
	}

	public function read(object $node, array $rawData, array $fieldNames): array
	{
		$data = $this->data($node, $rawData, $fieldNames);

		return array_merge([
			'title' => $this->resolveTitle($node),
			'uid' => $rawData['uid'],
			'fields' => $this->fields($node, $fieldNames),
		], $data);
	}

	public function resolveTitle(object $node): string
	{
		if ($node instanceof HasTitle) {
			return $node->title();
		}

		if (method_exists($node, 'title')) {
			return $node->title();
		}

		$titleField = NodeMeta::titleField($node::class);

		if ($titleField) {
			$field = $this->hydrator->getField($node, $titleField);

			return $field->value()->unwrap() ?? '';
		}

		return '';
	}

	/**
	 * @return string[]
	 */
	private function order(object $node, array $fieldNames): array
	{
		$metaOrder = NodeMeta::fieldOrder($node::class);

		if ($metaOrder !== null) {
			return $metaOrder;
		}

		if (method_exists($node, 'order')) {
			return $node->order();
		}

		return $fieldNames;
	}

	private function resolveDeletable(object $node): bool
	{
		if (method_exists($node, 'deletable')) {
			$method = new ReflectionMethod($node, 'deletable');

			return $method->invoke($node);
		}

		return NodeMeta::deletable($node::class);
	}
}
