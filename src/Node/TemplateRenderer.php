<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

use Duon\Cms\Cms;
use Duon\Cms\Config;
use Duon\Cms\Field\FieldHydrator;
use Duon\Cms\Node\Contract\ProvidesRenderContext;
use Duon\Cms\Renderer;
use Duon\Core\Factory;
use Duon\Core\Request;
use Duon\Core\Response;
use Duon\Registry\Registry;

class TemplateRenderer
{
	public function __construct(
		private readonly Registry $registry,
		private readonly Factory $factory,
		private readonly FieldHydrator $hydrator,
	) {}

	/**
	 * Render a page node to an HTML response.
	 *
	 * The node is wrapped in a Node and passed to the template as
	 * '$page'. If the node implements ProvidesRenderContext, its
	 * extra context is merged in.
	 */
	public function renderPage(
		object $node,
		array $fieldNames,
		Cms $cms,
		Request $request,
		Config $config,
		array $context = [],
	): Response {
		$proxy = new Node($node, $fieldNames, $this->hydrator, $request);

		$baseContext = [
			'page' => $proxy,
			'cms' => $cms,
			'locale' => $request->get('locale'),
			'locales' => $request->get('locales'),
			'request' => $request,
			'registry' => $this->registry,
			'debug' => $config->debug,
			'env' => $config->env,
		];

		if ($node instanceof ProvidesRenderContext) {
			$baseContext = array_merge($baseContext, $node->renderContext());
		}

		$baseContext = array_merge($baseContext, $context);

		return $this->doRender($node, $baseContext);
	}

	/**
	 * Render a node to an HTML string.
	 *
	 * The node is wrapped in a Node and passed to the template as
	 * '$node'.
	 */
	public function renderNode(
		object $node,
		array $fieldNames,
		Cms $cms,
		Request $request,
		Config $config,
		array $context = [],
	): string {
		$proxy = new Node($node, $fieldNames, $this->hydrator, $request);

		$baseContext = array_merge([
			'node' => $proxy,
			'cms' => $cms,
			'locale' => $request->get('locale'),
			'locales' => $request->get('locales'),
			'request' => $request,
			'registry' => $this->registry,
			'debug' => $config->debug,
			'env' => $config->env,
		], $context);

		[$type, $id] = $this->resolveRenderer($node);
		$renderer = $this->registry->tag(Renderer::class)->get($type);

		return $renderer->render($id, $baseContext);
	}

	/**
	 * Resolve the renderer type and template ID for a node.
	 *
	 * @return array{0: string, 1: string} [rendererType, templateId]
	 */
	public function resolveRenderer(object $node): array
	{
		return ['template', NodeMeta::forClass($node::class)->renderer];
	}

	private function doRender(object $node, array $context): Response
	{
		[$type, $id] = $this->resolveRenderer($node);
		$renderer = $this->registry->tag(Renderer::class)->get($type);

		return (new Response(
			$this->factory
				->response()
				->withHeader('Content-Type', 'text/html; charset=utf-8'),
		))->body(
			$renderer->render($id, $context),
		);
	}
}
