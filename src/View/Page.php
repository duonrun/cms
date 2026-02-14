<?php

declare(strict_types=1);

namespace Duon\Cms\View;

use Duon\Cms\Context;
use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Finder\Finder;
use Duon\Cms\Middleware\Permission;
use Duon\Cms\Node\Contract\HandlesFormPost;
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

	public function catchall(Context $context, Finder $find): Response
	{
		$request = $context->request;
		$config = $context->config;
		$path = $request->uri()->getPath();
		$prefix = $config->get('path.prefix', '');

		if ($prefix) {
			$path = preg_replace('/^' . preg_quote($prefix, '/') . '/', '', $path);
		}

		$page = $find->node->byPath($path === '' ? '/' : $path);

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
				return $this->jsonRead($page);
			}

			throw new HttpBadRequest();
		}

		return $this->dispatch($page, $request->method(), $request->form());
	}

	#[Permission('panel')]
	public function preview(Finder $find, string $slug): Response
	{
		$page = $find->node->byPath('/' . $slug);

		return $page->render();
	}

	private function dispatch(object $page, string $method, ?array $formBody): Response
	{
		return match ($method) {
			'GET' => $page->render(),
			'POST' => $this->handleFormPost($page, $formBody),
			default => throw new HttpBadRequest(),
		};
	}

	private function jsonRead(object $node): Response
	{
		$content = json_encode(
			$node->read(),
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
		if ($node instanceof HandlesFormPost) {
			return $node->formPost($formBody);
		}

		if (method_exists($node, 'formPost')) {
			$method = new ReflectionMethod($node, 'formPost');

			return $method->invoke($node, $formBody);
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
