<?php

declare(strict_types=1);

namespace Duon\Cms\View;

use Duon\Cms\Collection;
use Duon\Cms\Config;
use Duon\Cms\Context;
use Duon\Cms\Finder\Finder;
use Duon\Cms\Locales;
use Duon\Cms\Middleware\Permission;
use Duon\Cms\Node\Node;
use Duon\Cms\Node\NodeFactory;
use Duon\Cms\Node\NodeMeta;
use Duon\Cms\Node\NodeSerializer;
use Duon\Cms\Section;
use Duon\Core\Exception\HttpBadRequest;
use Duon\Core\Exception\HttpNotFound;
use Duon\Core\Factory;
use Duon\Core\Request;
use Duon\Core\Response;
use Duon\Registry\Registry;
use Duon\Wire\Creator;

class Panel
{
	protected string $publicPath;

	public function __construct(
		protected readonly Request $request,
		protected readonly Config $config,
		protected readonly Registry $registry,
		protected readonly Locales $locales,
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
		$creator = new Creator($this->registry);
		$tag = $this->registry->tag(Collection::class);
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
		$creator = new Creator($this->registry);
		$obj = $creator->create(
			$this->registry->tag(Collection::class)->entry($collection)->definition(),
			predefinedTypes: [Request::class => $this->request],
		);
		$blueprints = [];

		foreach ($obj->blueprints() as $blueprint) {
			$blueprints[] = [
				'slug' => NodeMeta::handle($blueprint),
				'name' => NodeMeta::name($blueprint),
			];
		}

		return [
			'name' => $obj->name(),
			'slug' => $collection,
			'header' => $obj->header(),
			'showPublished' => $obj->showPublished(),
			'showHidden' => $obj->showHidden(),
			'showLocked' => $obj->showLocked(),
			'nodes' => $obj->listing(),
			'blueprints' => $blueprints,
		];
	}

	#[Permission('panel')]
	public function blueprint(string $type, Context $context, Finder $find): array
	{
		$content = [];
		$defaults = $this->request->param('content', null);

		if ($defaults !== null) {
			// TODO: check security concerns
			$content = json_decode($defaults, true);
		}

		$factory = $find->nodeFactory();
		$class = $this->registry->tag(Node::class)->entry($type)->definition();
		$obj = $factory->blueprint($class, $context, $find);

		$serializer = new NodeSerializer(
			$this->registry,
			$factory->hydrator(),
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
		Finder $find,
		Factory $factory,
	): Response {
		if ($this->request->header('Content-Type') !== 'application/json') {
			throw new HttpBadRequest($this->request);
		}

		$data = $this->request->json();
		$class = $this->registry->tag(Node::class)->entry($type)->definition();
		$obj = $find->nodeFactory()->create($class, $context, $find, $data);

		$result = $obj->save($data);

		return (new Response(
			$factory
				->response()
				->withStatus(201)
				->withHeader('Content-Type', 'application/json'),
		))->body(json_encode($result));
	}

	#[Permission('panel')]
	public function node(Finder $find, Factory $factory, string $uid): Response
	{
		$node = $find->node->byUid($uid, published: null);

		if (!$node) {
			throw new HttpNotFound($this->request);
		}

		$method = $this->request->method();

		$result = match ($method) {
			'GET' => $node->read(),
			'PUT' => $this->saveNode($node),
			'DELETE' => $node->delete(),
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

	private function saveNode(object $node): array
	{
		if ($this->request->header('Content-Type') !== 'application/json') {
			throw new HttpBadRequest($this->request);
		}

		return $node->save($this->request->json());
	}

	protected function getPanelIndex(): string
	{
		return $this->publicPath . $this->config->get('path.panel') . '/index.html';
	}
}
