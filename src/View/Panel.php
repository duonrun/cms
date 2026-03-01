<?php

declare(strict_types=1);

namespace Duon\Cms\View;

use Duon\Cms\Cms;
use Duon\Cms\Collection;
use Duon\Cms\Config;
use Duon\Cms\Context;
use Duon\Cms\Locales;
use Duon\Cms\Middleware\Permission;
use Duon\Cms\Node\Factory as NodeFactory;
use Duon\Cms\Node\Node;
use Duon\Cms\Node\PathManager;
use Duon\Cms\Node\Serializer;
use Duon\Cms\Node\Store;
use Duon\Cms\Node\Types;
use Duon\Cms\Plugin;
use Duon\Cms\Section;
use Duon\Container\Container;
use Duon\Core\Exception\HttpBadRequest;
use Duon\Core\Exception\HttpNotFound;
use Duon\Core\Factory;
use Duon\Core\Request;
use Duon\Core\Response;
use Duon\Wire\Creator;

class Panel
{
	private const int LIMIT_DEFAULT = 50;
	private const int LIMIT_MAX = 250;

	protected string $publicPath;

	public function __construct(
		protected readonly Request $request,
		protected readonly Config $config,
		protected readonly Container $container,
		protected readonly Locales $locales,
		protected readonly Types $types,
	) {
		$this->publicPath = $config->get('path.public');
	}

	public function boot(): array
	{
		$config = $this->config;
		$localesList = array_map(
			function ($locale) {
				return [
					'id' => $locale->id,
					'title' => $locale->title,
					'fallback' => $locale->fallback,
				];
			},
			iterator_to_array($this->locales),
			[], // Add an empty array to remove the assoc array keys
			//    See: https://www.php.net/manual/en/function.array-map.php#refsect1-function.array-map-returnvalues
		);

		return [
			'locales' => $localesList,
			'locale' => $this->locales->getDefault()->id, // TODO: set the correct user locale
			'defaultLocale' => $this->locales->getDefault()->id,
			'debug' => $config->debug(),
			'env' => $config->env(),
			'csrfToken' => 'TOKEN', // TODO: real token
			'logo' => $config->get('panel.logo', null),
			'theme' => $this->themeStylesheets(),
			'api' => $config->apiPath(),
			'assets' => $config->get('path.assets'),
			'cache' => $config->get('path.cache'),
			'prefix' => $config->get('path.prefix'),
			'sessionExpires' => $config->get('session.options')['gc_maxlifetime'],
			'transliterate' => $config->get('slug.transliterate'),
			'allowedFiles' => [
				'file' => array_merge(...array_values($config->get('upload.mimetypes.file'))),
				'image' => array_merge(...array_values($config->get('upload.mimetypes.image'))),
				'video' => array_merge(...array_values($config->get('upload.mimetypes.video'))),
			],
		];
	}

	public function index(Factory $factory): Response
	{
		return Response::create($factory)->file($this->getPanelIndex());
	}

	public function catchall(Factory $factory, string $slug): Response
	{
		$file = $this->publicPath . '/panel/' . $slug;

		if (is_file($file)) {
			return Response::create($factory)->file($file);
		}

		return Response::create($factory)->file($this->getPanelIndex());
	}

	#[Permission('panel')]
	public function collections(): array
	{
		$creator = new Creator($this->container);
		$tag = $this->container->tag(Collection::class);
		$collections = [];

		foreach ($tag->entries() as $id) {
			$class = $tag->entry($id)->definition();

			if (is_object($class)) {
				$item = $class;
			} else {
				$item = $creator->create($class, predefinedTypes: [Request::class => $this->request]);
			}

			if ($item::class === Section::class) {
				$collections[] = [
					'type' => 'section',
					'name' => $item->name,
				];
			} else {
				$collections[] = [
					'type' => 'collection',
					'slug' => $id,
					'name' => $item->name(),
				];
			}
		}

		return $collections;
	}

	#[Permission('panel')]
	public function collection(string $collection): array
	{
		$creator = new Creator($this->container);
		$obj = $creator->create(
			$this->container->tag(Collection::class)->entry($collection)->definition(),
			predefinedTypes: [Request::class => $this->request],
		);
		$blueprints = [];
		$offset = $this->intParam('offset', 0, min: 0);
		$limit = $this->intParam('limit', self::LIMIT_DEFAULT, min: 1, max: self::LIMIT_MAX);
		$q = $this->stringParam('q');
		$sort = $this->stringParam('sort');
		$dir = strtolower($this->stringParam('dir'));

		if ($dir !== '' && !in_array($dir, ['asc', 'desc'], true)) {
			throw new HttpBadRequest($this->request);
		}

		$sorts = $obj->sorts();

		if ($sort !== '' && !array_key_exists($sort, $sorts)) {
			throw new HttpBadRequest($this->request);
		}

		$listing = $obj->list(
			offset: $offset,
			limit: $limit,
			q: $q,
			sort: $sort,
			dir: $dir,
		);

		foreach ($obj->blueprints() as $blueprint) {
			$blueprints[] = [
				'slug' => (string) $this->types->get($blueprint, 'handle'),
				'name' => (string) $this->types->get($blueprint, 'label'),
			];
		}

		return [
			'name' => $obj->name(),
			'slug' => $collection,
			'header' => $obj->header(),
			'showPublished' => $obj->showPublished(),
			'showHidden' => $obj->showHidden(),
			'showLocked' => $obj->showLocked(),
			'total' => $listing['total'],
			'offset' => $listing['offset'],
			'limit' => $listing['limit'],
			'q' => $listing['q'],
			'sort' => $listing['sort'],
			'dir' => $listing['dir'],
			'sorts' => array_keys($sorts),
			'nodes' => $listing['nodes'],
			'blueprints' => $blueprints,
		];
	}

	#[Permission('panel')]
	public function blueprint(string $type, Context $context, Cms $cms): array
	{
		$content = [];
		$defaults = $this->request->param('content', null);

		if ($defaults !== null) {
			// TODO: check security concerns
			$content = json_decode($defaults, true);
		}

		$factory = $cms->nodeFactory();
		$class = $this->container->tag(Plugin::NODE_TAG)->entry($type)->definition();
		$obj = $factory->blueprint($class, $context, $cms);

		$serializer = new Serializer(
			$factory->hydrator(),
			$this->types,
		);

		return $serializer->blueprint(
			$obj,
			NodeFactory::fieldNamesFor($obj),
			$context->locales(),
			$content,
		);
	}

	#[Permission('panel')]
	public function createNode(
		string $type,
		Context $context,
		Cms $cms,
		Factory $factory,
	): Response {
		if ($this->request->header('Content-Type') !== 'application/json') {
			throw new HttpBadRequest($this->request);
		}

		$data = $this->request->json();
		$class = $this->container->tag(Plugin::NODE_TAG)->entry($type)->definition();
		$obj = $cms->nodeFactory()->create($class, $context, $cms, $data);

		$store = new Store($context->db, new PathManager(), $this->types);
		$result = $store->save($obj, $data, $this->request, $context->locales());

		return (new Response(
			$factory
				->response()
				->withStatus(201)
				->withHeader('Content-Type', 'application/json'),
		))->body(json_encode($result));
	}

	#[Permission('panel')]
	public function node(Context $context, Cms $cms, Factory $factory, string $uid): Response
	{
		$result = $cms->node->byUid($uid, published: null);

		if (!$result) {
			throw new HttpNotFound($this->request);
		}

		$node = Node::unwrap($result);
		$nodeFactory = $cms->nodeFactory();
		$serializer = new Serializer($nodeFactory->hydrator(), $this->types);
		$store = new Store($context->db, new PathManager(), $this->types);
		$method = $this->request->method();

		$result = match ($method) {
			'GET' => $serializer->read($node, NodeFactory::dataFor($node), NodeFactory::fieldNamesFor($node)),
			'PUT' => $this->saveNode($node, $store, $context),
			'DELETE' => $store->delete($node, $this->request),
			default => throw new HttpBadRequest($this->request),
		};

		$content = json_encode($result, JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

		return (new Response(
			$factory
				->response()
				->withStatus($method === 'POST' ? 201 : 200)
				->withHeader('Content-Type', 'application/json'),
		))->body($content);
	}

	private function saveNode(object $node, Store $store, Context $context): array
	{
		if ($this->request->header('Content-Type') !== 'application/json') {
			throw new HttpBadRequest($this->request);
		}

		return $store->save($node, $this->request->json(), $this->request, $context->locales());
	}

	private function themeStylesheets(): array
	{
		$theme = $this->config->get('panel.theme', null);

		if (is_string($theme)) {
			$theme = trim($theme);

			return $theme === '' ? [] : [$theme];
		}

		if (!is_array($theme)) {
			return [];
		}

		$stylesheets = [];

		foreach ($theme as $item) {
			if (!is_string($item)) {
				continue;
			}

			$item = trim($item);

			if ($item === '') {
				continue;
			}

			$stylesheets[] = $item;
		}

		return $stylesheets;
	}

	private function intParam(
		string $key,
		int $default,
		int $min,
		?int $max = null,
	): int {
		$value = $this->request->param($key, (string) $default);

		if (is_int($value)) {
			$int = $value;
		} elseif (is_string($value) && preg_match('/^-?[0-9]+$/', $value)) {
			$int = (int) $value;
		} else {
			throw new HttpBadRequest($this->request);
		}

		if ($int < $min) {
			throw new HttpBadRequest($this->request);
		}

		if ($max !== null && $int > $max) {
			throw new HttpBadRequest($this->request);
		}

		return $int;
	}

	private function stringParam(string $key): string
	{
		$value = $this->request->param($key, '');

		if (!is_string($value)) {
			throw new HttpBadRequest($this->request);
		}

		return trim($value);
	}

	protected function getPanelIndex(): string
	{
		return $this->publicPath . $this->config->get('path.panel') . '/index.html';
	}
}
