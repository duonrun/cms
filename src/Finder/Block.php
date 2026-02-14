<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

use Duon\Cms\Context;
use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Field\FieldHydrator;
use Duon\Cms\Finder\Finder;
use Duon\Cms\Node\Block as BlockNode;
use Duon\Cms\Node\Node;
use Duon\Cms\Node\NodeMeta;
use Duon\Cms\Node\TemplateRenderer;
use Duon\Core\Exception\HttpBadRequest;
use Throwable;

class Block
{
	protected BlockNode $block;

	public function __construct(
		private readonly Context $context,
		private readonly Finder $find,
		string $uid,
		private readonly array $templateContext = [],
		?bool $deleted = false,
		?bool $published = true,
	) {
		$data = $this->context->db->nodes->find([
			'uid' => $uid,
			'published' => $published,
			'deleted' => $deleted,
			'kind' => 'block',
		])->one();
		$class = $this
			->context
			->registry
			->tag(Node::class)
			->entry($data['handle'])
			->definition();

		if (NodeMeta::kind($class) !== 'block') {
			throw new RuntimeException('Invalid block class' . $class);
		}

		$data['content'] = json_decode($data['content'], true);
		$this->block = new $class($context, $find, $data);
	}

	public function __toString(): string
	{
		try {
			$renderer = new TemplateRenderer(
				$this->context->registry,
				$this->context->factory,
				new FieldHydrator(),
			);

			return $renderer->renderBlock(
				$this->block,
				$this->block->fieldNames(),
				$this->find,
				$this->context->request,
				$this->context->config,
				$this->templateContext,
			);
		} catch (Throwable $e) {
			if ($this->context->config->debug()) {
				throw $e;
			}

			throw new HttpBadRequest($this->context->request, previous: $e);
		}
	}
}
