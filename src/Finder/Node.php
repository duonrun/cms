<?php

declare(strict_types=1);

namespace Duon\Cms\Finder;

use Duon\Cms\Cms;
use Duon\Cms\Context;
use Duon\Cms\Node\Factory;
use Duon\Cms\Node\Meta;
use Duon\Cms\Node\Node as NodeWrapper;
use Duon\Cms\Plugin;
use Duon\Core\Exception\HttpBadRequest;

class Node
{
	public function __construct(
		private readonly Context $context,
		private readonly Cms $cms,
		private readonly Factory $nodeFactory,
		private readonly Meta $meta = new Meta(),
	) {}

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

		$data['content'] = json_decode($data['content'], true);
		$data['editor_data'] = json_decode($data['editor_data'], true);
		$data['creator_data'] = json_decode($data['creator_data'], true);
		$data['paths'] = json_decode($data['paths'], true);
		$class = $this
			->context
			->registry
			->tag(Plugin::NODE_TAG)
			->entry($data['handle'])
			->definition();

		if ($this->meta->isNode($class)) {
			$node = $this->nodeFactory->create($class, $this->context, $this->cms, $data);

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
