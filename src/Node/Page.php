<?php

declare(strict_types=1);

namespace Duon\Cms\Node;

use Duon\Cms\Exception\RuntimeException;
use Duon\Cms\Locale;

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
}
