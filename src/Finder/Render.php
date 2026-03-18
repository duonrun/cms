<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

use Duon\Cms\Cms;
use Duon\Cms\Context;
use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Node\Factory;
use Duon\Cms\Node\TemplateRenderer;
use Duon\Cms\Node\Types;
use Duon\Core\Exception\HttpBadRequest;
use Throwable;

class Render
{
	protected object $node;
	private readonly NodeRecordMapper $records;

	public function __construct(
		private readonly Context $context,
		private readonly Cms $cms,
		private readonly Factory $nodeFactory,
		private readonly Types $types,
		string $uid,
		private readonly array $templateContext = [],
		?bool $deleted = false,
		?bool $published = true,
	) {
		$this->records = new NodeRecordMapper($this->context, $this->cms, $this->nodeFactory);
		$data = $this->context->db->nodes->find([
			'uid' => $uid,
			'published' => $published,
			'deleted' => $deleted,
		])->one();
		$class = $this->records->className($data);

		if (!(bool) $this->types->get($class, 'renderable', false)) {
			throw new RuntimeException('Invalid renderable node class ' . $class);
		}

		$this->node = $this->records->node($data, $class);
	}

	public function __toString(): string
	{
		try {
			$renderer = new TemplateRenderer(
				$this->context->container,
				$this->context->factory,
				$this->nodeFactory->hydrator(),
				$this->types,
			);

			return $renderer->renderNode(
				$this->node,
				Factory::fieldNamesFor($this->node),
				$this->cms,
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
