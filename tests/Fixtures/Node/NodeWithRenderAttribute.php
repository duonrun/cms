<?php

declare(strict_types=1);

namespace Duon\Cms\Tests\Fixtures\Node;

use Duon\Cms\Node\Contract\HasTitle;
use Duon\Cms\Node\Meta\Render;

#[Render('template-defined-by-render-attribute')]
class NodeWithRenderAttribute implements HasTitle
{
	public function title(): string
	{
		return 'with render';
	}
}
