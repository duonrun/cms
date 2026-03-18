<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

use Duon\Cms\Cms;
use Duon\Cms\Context;
use Duon\Cms\Node\Factory;
use Duon\Cms\Node\Node as NodeWrapper;
use Duon\Cms\Node\Types;
use Duon\Core\Exception\HttpBadRequest;

class Node
{
	private readonly NodeRecordMapper $records;

	public function __construct(
		private readonly Context $context,
		private readonly Cms $cms,
		private readonly Factory $nodeFactory,
		private readonly Types $types,
	) {
		$this->records = new NodeRecordMapper($this->context, $this->cms, $this->nodeFactory);
	}

	public function byPath(
		string $path,
		?bool $deleted = false,
		?bool $published = true,
	): ?NodeWrapper {
		return $this->get([
			'path' => $path,
			'published' => $published,
			'deleted' => $deleted,
		]);
	}

	public function byUid(
		string $uid,
		?bool $deleted = false,
		?bool $published = true,
	): ?NodeWrapper {
		return $this->get([
			'uid' => $uid,
			'published' => $published,
			'deleted' => $deleted,
		]);
	}

	public function get(
		array $params,
	): ?NodeWrapper {
		$data = $this->context->db->nodes->find($params)->one();

		if (!$data) {
			return null;
		}

		$class = $this->records->className($data);

		if ($this->types->isNode($class)) {
			return $this->records->proxy($data, $class);
		}

		throw new HttpBadRequest($this->context->request);
	}

	public function find(
		string $query,
	): array {
		return [];
	}
}
