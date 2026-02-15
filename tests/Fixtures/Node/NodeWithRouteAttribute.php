<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Node\Contract\HasTitle;
use Duon\Cms\Node\Meta\Page;
use Duon\Cms\Node\Meta\Route;

#[Page]
#[Route('/node-with-custom/{route}')]
class NodeWithRouteAttribute implements HasTitle
{
	public function title(): string
	{
		return 'with route';
	}
}
