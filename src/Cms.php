<?php

declare(strict_types=1);

namespace Duon\Cms;

use Duon\Cms\Config;
use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Node\Block;
use Duon\Cms\Node\Document;
use Duon\Cms\Node\Node;
use Duon\Cms\Node\Page as PageNode;
use Duon\Core\App;
use Duon\Core\Factory;
use Duon\Core\Plugin;
use Duon\Quma\Connection;
use Duon\Quma\Database;
use Duon\Registry\Entry;
use Duon\Registry\Registry;
use Duon\Router\Route;
use PDO;

class Cms implements Plugin
{
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
				->tag(Node::class)
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
		$handle = $class::handle();

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

		try {
			$this->connection = new Connection(
				$this->config->get('db.dsn'),
				$sql,
				$namespacedMigrations,
				fetchMode: PDO::FETCH_ASSOC,
				options: $this->config->get('db.options'),
				print: $this->config->get('db.print'),
			);
		} catch (\RuntimeException $e) {
			$driverMessage = $this->missingDriverMessage($e);

			if ($driverMessage !== null) {
				throw new RuntimeException($driverMessage, previous: $e);
			}

			throw $e;
		}
		$this->db = new CmsDatabase($this->connection, $this->config);
	}

	private function missingDriverMessage(\RuntimeException $exception): ?string
	{
		$prefix = 'PDO driver not supported: ';
		$message = $exception->getMessage();

		if (!str_starts_with($message, $prefix)) {
			return null;
		}

		$driver = trim(substr($message, strlen($prefix)));

		if ($driver === '') {
			return null;
		}

		$extension = match ($driver) {
			'pgsql' => 'pdo_pgsql',
			'sqlite' => 'pdo_sqlite',
			default => 'pdo_' . $driver,
		};

		return sprintf(
			'PDO driver not supported: %s. Install extension ext-%s.',
			$driver,
			$extension,
		);
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

		throw new RuntimeException('Renderers must imlement the `Duon\Cms\Renderer` interface');
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
					'kind' => match (true) {
						is_a($class, Block::class, true) => 'block',
						is_a($class, PageNode::class, true) => 'page',
						is_a($class, Document::class, true) => 'document',
					},
				])->run();
			}
		}
	}
}
