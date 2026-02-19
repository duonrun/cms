<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

use Duon\Cms\Cms;
use Duon\Cms\Config;
use Duon\Cms\Context;
use Duon\Cms\Field\FieldHydrator;
use Duon\Cms\Field\Schema\Registry as SchemaRegistry;
use Duon\Cms\Node\Contract\HasInit;
use Duon\Core\Factory as CoreFactory;
use Duon\Core\Request;
use Duon\Quma\Database;
use Duon\Registry\Registry;
use Duon\Wire\Creator;
use WeakMap;

use function Duon\Cms\Util\nanoid;

class Factory
{
	/** @var WeakMap<object, array{data: array, fieldNames: string[]}> */
	private static WeakMap $nodeState;

	private readonly FieldHydrator $hydrator;

	public function __construct(
		private readonly Registry $registry,
		?SchemaRegistry $schemaRegistry = null,
	) {
		$this->hydrator = new FieldHydrator($schemaRegistry ?? SchemaRegistry::withDefaults());
		self::$nodeState ??= new WeakMap();
	}

	/**
	 * Create a node instance from a class name and raw DB data.
	 *
	 * Uses Wire Creator for autowired construction,
	 * then FieldHydrator for field initialization.
	 */
	public function create(string $class, Context $context, Cms $cms, array $data): object
	{
		$serializer = new NodeSerializer($this->hydrator);
		$manager = new NodeManager($context->db, new PathManager());
		$templateRenderer = new TemplateRenderer(
			$this->registry,
			$context->factory,
			$this->hydrator,
		);

		$creator = new Creator($this->registry);
		$node = $creator->create($class, predefinedTypes: [
			Context::class => $context,
			Cms::class => $cms,
			Request::class => $context->request,
			Config::class => $context->config,
			Database::class => $context->db,
			Registry::class => $context->registry,
			CoreFactory::class => $context->factory,
			self::class => $this,
			TemplateRenderer::class => $templateRenderer,
			NodeSerializer::class => $serializer,
			NodeManager::class => $manager,
			FieldHydrator::class => $this->hydrator,
		]);

		$uid = $data['uid'] ?? nanoid();
		$owner = new FieldOwner($context, $uid);
		$fieldNames = $this->hydrator->hydrate($node, $data['content'] ?? [], $owner);

		if ($node instanceof HasInit) {
			$node->init();
		}

		self::$nodeState[$node] = [
			'data' => $data,
			'fieldNames' => $fieldNames,
		];

		return $node;
	}

	/**
	 * Wrap a node for template-friendly access.
	 */
	public function proxy(object $node, Request $request): Node
	{
		return new Node(
			$node,
			self::fieldNamesFor($node),
			$this->hydrator,
			$request,
		);
	}

	/**
	 * Create a blueprint (empty) node for admin panel schema generation.
	 */
	public function blueprint(string $class, Context $context, Cms $cms): object
	{
		return $this->create($class, $context, $cms, []);
	}

	/**
	 * Get the raw DB data associated with a node instance.
	 */
	public static function dataFor(object $node): array
	{
		self::$nodeState ??= new WeakMap();
		$node = Node::unwrap($node);

		return (self::$nodeState[$node] ?? [])['data'] ?? [];
	}

	/**
	 * Get the field names for a node instance.
	 */
	public static function fieldNamesFor(object $node): array
	{
		self::$nodeState ??= new WeakMap();
		$node = Node::unwrap($node);

		return (self::$nodeState[$node] ?? [])['fieldNames'] ?? [];
	}

	/**
	 * Get a metadata value from the raw DB data for a node instance.
	 */
	public static function meta(object $node, string $key): mixed
	{
		return self::dataFor($node)[$key] ?? null;
	}

	public function hydrator(): FieldHydrator
	{
		return $this->hydrator;
	}
}
