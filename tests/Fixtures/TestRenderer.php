<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures;

use Duon\Cms\Node\Node;
use Duon\Cms\Renderer;
use RuntimeException;

final class TestRenderer implements Renderer
{
	public function render(string $id, array $context): string
	{
		if (($context['fail'] ?? false) === true) {
			throw new RuntimeException('Forced renderer failure');
		}

		$node = $context['node'] ?? $context['page'] ?? null;

		if (!$node instanceof Node) {
			throw new RuntimeException('Missing node context');
		}

		return $id . ':' . $node->title();
	}

	public function contentType(): string
	{
		return 'text/html';
	}
}
