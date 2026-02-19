<?php

declare(strict_types=1);

namespace Duon\Cms\View;

use Duon\Cms\Cms;
use Duon\Cms\Context;
use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Middleware\Permission;
use Duon\Cms\Node\Contract\HandlesFormPost;
use Duon\Cms\Node\Node;
use Duon\Cms\Node\Factory as NodeFactory;
use Duon\Cms\Node\Serializer;
use Duon\Cms\Node\TemplateRenderer;
use Duon\Cms\Util\Path;
use Duon\Core\Exception\HttpBadRequest;
use Duon\Core\Exception\HttpNotFound;
use Duon\Core\Factory;
use Duon\Core\Response;
use Duon\Registry\Registry;
use ReflectionMethod;

class Page
{
	public function __construct(
		protected readonly Factory $factory,
		protected readonly Registry $registry,
	) {}

	public function catchall(Context $context, Cms $cms): Response
	{
		$request = $context->request;
		$config = $context->config;
		$path = $request->uri()->getPath();
		$prefix = $config->get('path.prefix', '');

		if ($prefix) {
			$path = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $path);
		}

		$page = $cms->node->byPath($path === '' ? '/' : $path);

		if (!$page) {
			try {
				$path = Path::inside($config->get('path.public'), $path);

				return Response::create($this->factory)->file($path);
			} catch (RuntimeException $e) {
				$this->redirectIfExists($context, $path);

				throw new HttpNotFound($request, previous: $e);
			}
		}

		if ($request->get('isXhr', false)) {
			if ($request->method() === 'GET') {
				return $this->jsonRead($page, $cms);
			}

			throw new HttpBadRequest();
		}

		return $this->dispatch($page, $context, $cms, $request->method(), $request->form());
	}

	#[Permission('panel')]
	public function preview(Context $context, Cms $cms, string $slug): Response
	{
		$page = $cms->node->byPath('/' . $slug);

		return $this->renderPage($page, $context, $cms);
	}

	private function dispatch(object $page, Context $context, Cms $cms, string $method, ?array $formBody): Response
	{
		return match ($method) {
			'GET' => $this->renderPage($page, $context, $cms),
			'POST' => $this->handleFormPost($page, $formBody),
			default => throw new HttpBadRequest(),
		};
	}

	private function renderPage(object $page, Context $context, Cms $cms): Response
	{
		$node = Node::unwrap($page);

		if (is_callable([$node, 'render'])) {
			return $node->render();
		}

		$hydrator = $cms->nodeFactory()->hydrator();
		$renderer = new TemplateRenderer($this->registry, $this->factory, $hydrator);

		return $renderer->renderPage(
			$node,
			NodeFactory::fieldNamesFor($node),
			$cms,
			$context->request,
			$context->config,
		);
	}

	private function jsonRead(object $node, Cms $cms): Response
	{
		$inner = Node::unwrap($node);

		if (method_exists($inner, 'read')) {
			$data = $inner->read();
		} else {
			$hydrator = $cms->nodeFactory()->hydrator();
			$serializer = new Serializer($hydrator);
			$data = $serializer->read(
				$inner,
				NodeFactory::dataFor($inner),
				NodeFactory::fieldNamesFor($inner),
			);
		}

		$content = json_encode(
			$data,
			JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
		);

		return (new Response(
			$this->factory
				->response()
				->withHeader('Content-Type', 'application/json'),
		))->body($content);
	}

	private function handleFormPost(object $node, ?array $formBody): Response
	{
		$inner = Node::unwrap($node);

		if ($inner instanceof HandlesFormPost) {
			return $inner->formPost($formBody);
		}

		if (method_exists($inner, 'formPost')) {
			$method = new ReflectionMethod($inner, 'formPost');

			return $method->invoke($inner, $formBody);
		}

		throw new HttpBadRequest();
	}

	protected function redirectIfExists(Context $context, string $path): void
	{
		$db = $context->db;
		$path = $db->paths->byPath(['path' => $path])->one();

		if ($path && !($path['inactive'] === null)) {
			$paths = $db->paths->activeByNode(['node' => $path['node']])->all();

			$pathsByLocale = array_combine(
				array_map(fn($p) => $p['locale'], $paths),
				array_map(fn($p) => $p['path'], $paths),
			);

			$locale = $context->request->get('locale');

			while ($locale) {
				$path = $pathsByLocale[$locale->id] ?? null;

				if ($path) {
					header('Location: ' . $path, true, 301);
					exit;
				}

				$locale = $locale->fallback();
			}
		}
	}
}
