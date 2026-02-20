<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Node\Contract\Title;
use Duon\Cms\Schema\Route;

#[Route('/node-with-custom/{route}')]
class NodeWithRouteAttribute implements Title
{
	public function title(): string
	{
		return 'with route';
	}
}
