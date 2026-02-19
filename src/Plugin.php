<?php

declare(strict_types=1);

namespace Duon\Cms;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Node\Meta;
use Duon\Core\App;
use Duon\Core\Factory;
use Duon\Core\Plugin as CorePlugin;
use Duon\Quma\Connection;
use Duon\Quma\Database;
use Duon\Registry\Entry;
use Duon\Registry\Registry;
use Duon\Router\Route;
use PDO;

class Plugin implements CorePlugin
{
	public const string NODE_TAG = 'duon.cms.node';

	protected readonly Config $config;
	protected readonly Factory $factory;
	protected readonly Registry $registry;
	protected readonly Database $db;
	protected readonly Connection $connection;
	protected readonly Routes $routes;

	/** @property array<Entry> */
	protected array $renderers = [];

	protected array $collections = [];
	protected array $nodes = [];

	public function __construct(protected readonly bool $sessionEnabled = false) {}

	public function load(App $app): void
	{
		$this->factory = $app->factory();
		$this->registry = $app->registry();
		$this->config = $app->config();
		$this->collect();
		$this->database();

		$this->registry->add($this->registry::class, $this->registry);
		$this->registry->add(Connection::class, $this->connection);
		$this->registry->add(Database::class, $this->db);
		$this->registry->add(Factory::class, $this->factory);

		$this->routes = new Routes($app->config(), $this->db, $this->factory, $this->sessionEnabled);
		$this->routes->add($app);
	}

	protected function collect(): void
	{
		foreach ($this->collections as $name => $collection) {
			$this->registry
				->tag(Collection::class)
				->add($name, $collection);
		}

		foreach ($this->nodes as $name => $node) {
			$this->registry
				->tag(self::NODE_TAG)
				->add($name, $node);
		}

		foreach ($this->renderers as $entry) {
			$this->registry
				->tag(Renderer::class)
				->addEntry($entry);
		}
	}

	public function section(string $name): void
	{
		$this->collections[$name] = new Section($name);
	}

	public function collection(string $class): void
	{
		$handle = $class::handle();

		if (isset($this->collections[$handle])) {
			throw new RuntimeException('Duplicate collection handle: ' . $handle);
		}

		$this->collections[$class::handle()] = $class;
	}

	public function node(string $class): void
	{
		$handle = Meta::handle($class);

		if (isset($this->nodes[$handle])) {
			throw new RuntimeException('Duplicate node handle: ' . $handle);
		}

		$this->nodes[$handle] = $class;
	}

	protected function database(): void
	{
		if (!$this->config) {
			throw new RuntimeException('No config given');
		}

		$root = dirname(__DIR__);
		$sqlConfig = $this->config->get('db.sql', []);
		$sql = array_merge(
			[$root . '/db/sql'],
			$sqlConfig ? (is_array($sqlConfig) ? $sqlConfig : [$sqlConfig]) : [],
		);
		$migrations = $this->config->get('db.migrations', []);
		$namespacedMigrations = [];
		$namespacedMigrations['install'] = [$root . '/db/migrations/install'];
		$namespacedMigrations['default'] = array_merge(
			$migrations ? (is_array($migrations) ? $migrations : [$migrations]) : [],
			[$root . '/db/migrations/update'],
		);

		$this->connection = new Connection(
			$this->config->get('db.dsn'),
			$sql,
			$namespacedMigrations,
			fetchMode: PDO::FETCH_ASSOC,
			options: $this->config->get('db.options'),
			print: $this->config->get('db.print'),
		);
		$this->db = new Database($this->connection);
	}

	/**
	 * Catchall for page url paths.
	 *
	 * Should be the last one
	 */
	public function catchallRoute(): Route
	{
		return $this->routes->catchallRoute();
	}

	public function renderer(string $id, string $class): Entry
	{
		if (is_a($class, Renderer::class, true)) {
			$entry = new Entry($id, $class);
			$this->renderers[] = $entry;

			return $entry;
		}

		throw new RuntimeException('Renderers must imlement the `Duon\\Cms\\Renderer` interface');
	}

	protected function synchronizeNodes(): void
	{
		if (!$this->db->sys->isInitialized()->one()['value']) {
			return;
		}

		$types = array_map(fn($record) => $record['handle'], $this->db->nodes->types()->all());

		foreach ($this->nodes as $handle => $class) {
			if (!in_array($handle, $types)) {
				$this->db->nodes->addType([
					'handle' => $handle,
				])->run();
			}
		}
	}
}
