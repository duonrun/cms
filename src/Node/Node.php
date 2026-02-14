<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

use Duon\Cms\Config;
use Duon\Cms\Context;
use Duon\Cms\Exception\NoSuchField;
use Duon\Cms\Field\Field;
use Duon\Cms\Field\FieldHydrator;
use Duon\Cms\Field\FieldOwner;
use Duon\Cms\Finder\Finder;
use Duon\Cms\Locale;
use Duon\Cms\Locales;
use Duon\Cms\Value\Value;
use Duon\Core\Factory;
use Duon\Core\Request;
use Duon\Core\Response;
use Duon\Quma\Database;
use Duon\Registry\Registry;

abstract class Node implements FieldOwner
{
	public readonly Request $request;
	public readonly Config $config;
	protected readonly Database $db;
	protected readonly Registry $registry;
	protected readonly Factory $factory;
	protected array $fieldNames = [];
	private readonly FieldHydrator $hydrator;
	private readonly NodeSerializer $serializer;
	private readonly NodeManager $manager;
	protected readonly TemplateRenderer $templateRenderer;

	final public function __construct(
		public readonly Context $context,
		protected readonly Finder $find,
		protected ?array $data = null,
	) {
		$this->hydrator = new FieldHydrator();
		$this->serializer = new NodeSerializer($this->hydrator);
		$this->manager = new NodeManager($context->db, new PathManager());
		$this->templateRenderer = new TemplateRenderer($context->registry, $context->factory, $this->hydrator);
		$this->fieldNames = $this->hydrator->hydrate($this, $this->data['content'] ?? [], $this);

		$this->init();

		$this->db = $context->db;
		$this->request = $context->request;
		$this->config = $context->config;
		$this->registry = $context->registry;
		$this->factory = $context->factory;
	}

	public static function name(): string
	{
		return NodeMeta::name(static::class);
	}

	public static function handle(): string
	{
		return NodeMeta::handle(static::class);
	}

	public static function permission(): array
	{
		return NodeMeta::forClass(static::class)->permission;
	}

	public static function route(): string
	{
		return NodeMeta::route(static::class);
	}

	final public function __get(string $fieldName): ?Value
	{
		return $this->getValue($fieldName);
	}

	// TODO: should be optimized as this could result
	//       in many ::get() calls
	final public function __isset(string $fieldName): bool
	{
		if (isset($this->{$fieldName})) {
			return $this->{$fieldName}->value()->isset();
		}

		return false;
	}

	final public function setData(array $data): static
	{
		$this->data = $data;
		$this->fieldNames = $this->hydrator->hydrate($this, $this->data['content'] ?? [], $this);

		return $this;
	}

	final public function getValue(string $fieldName): ?Value
	{
		if (!property_exists($this, $fieldName)) {
			$type = $this::class;

			throw new NoSuchField("The field '{$fieldName}' does not exist on node with type '{$type}'.");
		}

		$field = $this->{$fieldName};
		$value = $field->value();

		if ($value->isset()) {
			return $value;
		}

		return null;
	}

	final public function getField(string $fieldName): Field
	{
		return $this->{$fieldName};
	}

	public function meta(string $fieldName): mixed
	{
		return $this->data[$fieldName];
	}

	public function content(): array
	{
		return $this->serializer->content($this, $this->data, $this->fieldNames);
	}

	public function data(): array
	{
		return $this->serializer->data($this, $this->data, $this->fieldNames);
	}

	public function blueprint(array $values = []): array
	{
		return $this->serializer->blueprint($this, $this->fieldNames, $this->context->locales(), $values);
	}

	public function fillData(array $data): array
	{
		return $this->blueprint($data);
	}

	/**
	 * Is called after field hydration during construction.
	 *
	 * Can be used to make adjustments to the already initialized fields.
	 */
	public function init(): void {}

	public static function className(): string
	{
		return basename(str_replace('\\', '/', static::class));
	}

	/**
	 * Should return the general title of the node.
	 *
	 * Shown in the admin interface. But can also be used in the frontend.
	 */
	abstract public function title(): string;

	public function uid(): string
	{
		return $this->data['uid'];
	}

	public function order(): array
	{
		return $this->fieldNames;
	}

	public function fieldNames(): array
	{
		return $this->fieldNames;
	}

	public function fields(): array
	{
		return $this->serializer->fields($this, $this->fieldNames);
	}

	public function render(array $context = []): Response
	{
		return (new Response($this->factory
			->response()
			->withHeader('Content-Type', 'application/json')))->body(
				json_encode(
					$this->read(),
					JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
				),
			);
	}

	/**
	 * Called on GET request.
	 */
	public function read(): array
	{
		return $this->serializer->read($this, $this->data, $this->fieldNames);
	}

	public function delete(): array
	{
		return $this->manager->delete($this, $this->request);
	}

	public function save(array $data): array
	{
		return $this->manager->save($this, $data, $this->request, $this->context->locales());
	}

	public function locale(): Locale
	{
		return $this->request->get('locale');
	}

	public function defaultLocale(): Locale
	{
		return $this->request->get('defaultLocale');
	}

	public function locales(): Locales
	{
		return $this->context->locales();
	}

	public function request(): Request
	{
		return $this->request;
	}

	public function config(): Config
	{
		return $this->config;
	}

	protected function getResponse(): Response
	{
		$factory = $this->registry->get(Factory::class);

		return Response::create($factory);
	}

	protected function deletable(): bool
	{
		return true;
	}
}
