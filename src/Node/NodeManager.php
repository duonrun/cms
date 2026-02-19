<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Locales;
use Duon\Cms\Validation\ValidatorFactory;
use Duon\Core\Exception\HttpBadRequest;
use Duon\Core\Request;
use Duon\Quma\Database;
use Throwable;

class NodeManager
{
	public function __construct(
		private readonly Database $db,
		private readonly PathManager $pathManager,
	) {}

	public function save(object $node, array $data, Request $request, Locales $locales): array
	{
		$data = $this->validate($node, $data, $locales, $request);

		if ($data['locked']) {
			throw new HttpBadRequest($request, payload: ['message' => _('This document is locked')]);
		}

		try {
			$editor = $request->get('session')->authenticatedUserId();

			if (!$editor) {
				$editor = 1;
			}
		} catch (Throwable) {
			$editor = 1;
		}

		try {
			$this->db->begin();

			$this->persist($node, $data, $editor, $locales);

			$this->db->commit();
		} catch (Throwable $e) {
			$this->db->rollback();

			throw new RuntimeException(
				_('Fehler beim Speichern: ') . $e->getMessage(),
				(int) $e->getCode(),
				previous: $e,
			);
		}

		return [
			'success' => true,
			'uid' => $data['uid'],
		];
	}

	public function create(object $node, array $data, Request $request, Locales $locales): array
	{
		$existing = $this->db->nodes->find(['uid' => $data['uid']])->one();

		if ($existing) {
			throw new HttpBadRequest($request, payload: [
				'message' => _('A node with the same uid already exists: ') . $data['uid'],
			]);
		}

		return $this->save($node, $data, $request, $locales);
	}

	public function delete(object $node, Request $request): array
	{
		if ($request->header('Accept') !== 'application/json') {
			throw new HttpBadRequest($request);
		}

		$uid = NodeFactory::meta($node, 'uid');

		$this->db->nodes->delete([
			'uid' => $uid,
			'editor' => $request->get('session')->authenticatedUserId(),
		])->run();

		return [
			'success' => true,
			'error' => false,
		];
	}

	public function validate(object $node, array $data, Locales $locales, Request $request): array
	{
		$factory = new ValidatorFactory($node, $locales);
		$schema = $factory->create();
		$result = $schema->validate($data);

		if (!$result->isValid()) {
			throw new HttpBadRequest($request, payload: [
				'message' => _('Incomplete or invalid data'),
				'errors' => $result->errors(),
			]);
		}

		return $result->values();
	}

	private function persist(object $node, array $data, int $editor, Locales $locales): void
	{
		$nodeId = $this->persistNode($node, $data, $editor);

		if (Meta::routable($node::class)) {
			$this->pathManager->persist($this->db, $data, $editor, $nodeId, $locales);
		}
	}

	private function persistNode(object $node, array $data, int $editor): int
	{
		$class = $node::class;
		$handle = Meta::handle($class);
		$this->ensureTypeExists($handle);

		return (int) $this->db->nodes->save([
			'uid' => $data['uid'],
			'hidden' => $data['hidden'],
			'published' => $data['published'],
			'locked' => $data['locked'],
			'type' => $handle,
			'content' => json_encode($data['content']),
			'editor' => $editor,
		])->one()['node'];
	}

	private function ensureTypeExists(string $handle): void
	{
		$type = $this->db->nodes->type(['handle' => $handle])->one();

		if (!$type) {
			$this->db->nodes->addType([
				'handle' => $handle,
			])->run();
		}
	}
}
