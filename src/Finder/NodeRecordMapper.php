<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

use Duon\Cms\Cms;
use Duon\Cms\Context;
use Duon\Cms\Node\Factory;
use Duon\Cms\Node\Node;
use Duon\Cms\Plugin;

final class NodeRecordMapper
{
	public function __construct(
		private readonly Context $context,
		private readonly Cms $cms,
		private readonly Factory $nodeFactory,
	) {}

	public function className(array $record): string
	{
		return $this->context
			->container
			->tag(Plugin::NODE_TAG)
			->entry($record['handle'])
			->definition();
	}

	public function node(array $record, ?string $class = null): object
	{
		$class ??= $this->className($record);

		return $this->nodeFactory->create($class, $this->context, $this->cms, $this->hydrate($record));
	}

	public function proxy(array $record, ?string $class = null): Node
	{
		$node = $this->node($record, $class);

		return $this->nodeFactory->proxy($node, $this->context->request, $this->context, $this->cms);
	}

	private function hydrate(array $record): array
	{
		return [
			...$record,
			'published' => $this->boolean($record, 'published'),
			'hidden' => $this->boolean($record, 'hidden'),
			'locked' => $this->boolean($record, 'locked'),
			'content' => $this->decode($record, 'content'),
			'editor_data' => $this->decode($record, 'editor_data'),
			'creator_data' => $this->decode($record, 'creator_data'),
			'paths' => $this->decode($record, 'paths'),
		];
	}

	private function boolean(array $record, string $key): ?bool
	{
		$value = $record[$key] ?? null;

		if ($value === null || is_bool($value)) {
			return $value;
		}

		return match ($value) {
			0, '0' => false,
			1, '1' => true,
			default => (bool) $value,
		};
	}

	private function decode(array $record, string $key): mixed
	{
		$value = $record[$key] ?? null;

		if (!is_string($value)) {
			return $value;
		}

		return json_decode($value, true);
	}
}
