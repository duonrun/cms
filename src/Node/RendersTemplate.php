<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

use Duon\Core\Response;
use Duon\Registry\Exception\NotFoundException;

trait RendersTemplate
{
	public static function renderer(): array
	{
		return ['template', NodeMeta::forClass(static::class)->renderer];
	}

	public function render(array $context = []): Response
	{
		try {
			return $this->templateRenderer->renderPage(
				$this,
				$this->fieldNames,
				$this->find,
				$this->request,
				$this->config,
				$context,
			);
		} catch (NotFoundException) {
			return parent::render();
		}
	}
}
