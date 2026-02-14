<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Locale;
use Duon\Quma\Database;

use function Duon\Cms\Util\nanoid;

abstract class Page extends Node
{
	use RendersTemplate;

	public function path(?Locale $locale = null): string
	{
		$paths = $this->data['paths'];

		if (!$locale) {
			$locale = $this->request->get('locale');
		}

		while ($locale) {
			if (isset($paths[$locale->id])) {
				return $paths[$locale->id];
			}

			$locale = $locale->fallback();
		}

		throw new RuntimeException('No url path found');
	}

	public function blueprint(array $values = []): array
	{
		$result = parent::blueprint($values);
		$result['route'] = NodeMeta::route(static::class);

		return $result;
	}

	/**
	 * Overrides Node::persist to add persisting of url paths.
	 */
	protected function persist(Database $db, array $data, int $editor): void
	{
		$node = $this->persistNode($db, $data, $editor);
		$this->persistUrlPaths($db, $data, $editor, $node);
	}

	protected function prepareUrlPath(Database $db, string $path): string
	{
		if (!str_starts_with($path, '/')) {
			$path = '/' . $path;
		}
		// If this is a new path it could already be in the
		// list of inactive ones. So delete it if it exists.
		$db->nodes->deleteInactivePath(['path' => $path])->run();

		return $path;
	}

	protected function createUrlPaths(Database $db, array $paths, int $editor, int $node): void
	{
		$alreadyPersisted = [];

		foreach ($paths as $locale => $path) {
			if ($path) {
				$this->prepareUrlPath($db, $path);

				if (in_array($path, $alreadyPersisted)) {
					continue;
				}

				if ($db->nodes->pathExists(['path' => $path])->one()) {
					// The new path already exists, add a unique part
					$path = $path . '-' . substr(nanoid(), 0, 5);
				}

				$db->nodes->savePath([
					'node' => $node,
					'path' => $path,
					'locale' => $locale,
					'editor' => $editor,
				])->run();

				$alreadyPersisted[] = $path;
			}
		}
	}

	protected function saveUrlPaths(
		Database $db,
		array $currentPaths,
		array $paths,
		int $editor,
		int $node,
	): void {
		$alreadyPersisted = [];

		foreach ($currentPaths as $locale => $currentPath) {
			$newPath = trim($paths[$locale] ?? '');

			if ($newPath) {
				$newPath = $this->prepareUrlPath($db, $newPath);

				if ($currentPath) {
					if ($currentPath === $newPath) {
						$alreadyPersisted[] = $newPath;

						continue;
					}

					// The paths differ, so deactivate the old one
					$db->nodes->deactivatePath([
						'path' => $currentPath,
						'locale' => $locale,
						'editor' => $editor,
					])->run();
				}

				if (in_array($newPath, $alreadyPersisted)) {
					continue;
				}

				if ($db->nodes->pathExists(['path' => $newPath])->one()) {
					// The new path already exists, add a unique part
					$newPath = $newPath . '-' . substr(nanoid(), 0, 5);
				}

				$db->nodes->savePath([
					'node' => $node,
					'path' => $newPath,
					'locale' => $locale,
					'editor' => $editor,
				])->run();

				$alreadyPersisted[] = $newPath;
			} else {
				// The value existed but has been emptied.
				if ($currentPath) {
					$db->nodes->deactivatePath([
						'path' => $currentPath,
						'locale' => $locale,
						'editor' => $editor,
					])->run();
				}
			}
		}
	}

	protected function persistUrlPaths(Database $db, array $data, int $editor, int $node): void
	{
		$noPathsGiven = true;

		foreach ($data['paths'] ?? [] as $path) {
			if ($path) {
				$noPathsGiven = false;
				break;
			}
		}

		if ($noPathsGiven) {
			$data['paths'] = $data['generatedPaths'];
		}

		$locales = $this->context->locales();
		$defaultLocale = $locales->getDefault();
		$defaultPath = trim($data['paths'][$defaultLocale->id] ?? '');

		if (!$defaultPath) {
			throw new RuntimeException(_('Der URL-Pfad für die Hauptsprache {$defaultLocale->title} muss gesetzt sein'));
		}

		$currentPaths = array_column($db->nodes->getPaths(['node' => $node])->all(), 'path', 'locale');

		if ($currentPaths) {
			$baseStructure = [];

			foreach ($locales as $locale) {
				$baseStructure[$locale->id] = '';
			}

			$this->saveUrlPaths(
				$db,
				array_merge($baseStructure, $currentPaths),
				$data['paths'],
				$editor,
				$node,
			);
		} else {
			$this->createUrlPaths($db, $data['paths'], $editor, $node);
		}
	}
}
