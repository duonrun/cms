<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

use Duon\Cms\Cms;
use Duon\Cms\Context;
use Duon\Cms\Finder\Finder;
use Duon\Cms\Node\NodeFactory;
use Duon\Cms\Node\NodeMeta;
use Duon\Cms\Node\NodeProxy;
use Duon\Core\Exception\HttpBadRequest;

class Node
{
	public function __construct(
		private readonly Context $context,
		private readonly Finder $find,
		private readonly NodeFactory $nodeFactory,
	) {}

	public function byPath(
		string $path,
		?bool $deleted = false,
		?bool $published = true,
	): ?NodeProxy {
		return $this->get([
			'path' => $path,
			'published' => $published,
			'deleted' => $deleted,
			'kind' => 'page',
		]);
	}

	public function byUid(
		string $uid,
		?bool $deleted = false,
		?bool $published = true,
	): ?NodeProxy {
		return $this->get([
			'uid' => $uid,
			'published' => $published,
			'deleted' => $deleted,
		]);
	}

	public function get(
		array $params,
	): ?NodeProxy {
		$data = $this->context->db->nodes->find($params)->one();

		if (!$data) {
			return null;
		}

		$data['content'] = json_decode($data['content'], true);
		$data['editor_data'] = json_decode($data['editor_data'], true);
		$data['creator_data'] = json_decode($data['creator_data'], true);
		$data['paths'] = json_decode($data['paths'], true);
		$class = $this
			->context
			->registry
			->tag(Cms::NODE_TAG)
			->entry($data['handle'])
			->definition();

		if (NodeMeta::isNode($class)) {
			$node = $this->nodeFactory->create($class, $this->context, $this->find, $data);

			return $this->nodeFactory->proxy($node, $this->context->request);
		}

		throw new HttpBadRequest($this->context->request);
	}

	public function find(
		string $query,
	): array {
		return [];
	}
}
